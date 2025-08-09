<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class PageService {
    /**
     * Get page configuration
     */
    public static function getPageConfig(string $slug): ?array {
        $page = config("pages.$slug", null);
        // Sanitize and set defaults
        if (!empty($page)) {
            $page = array_merge([
                'enabled' => true, // Default to enabled
                'uri' => $slug, // Default uri to slug
                'title' => ucfirst($slug), // Default title to capitalized slug
                'enabled' => true, // Default enabled to true
            ], $page);

            if(is_string($page['enabled'])) {
                switch ($page['enabled']) {
                    case 'logged_in':
                        $page['enabled'] = auth()->check();
                        break;
                    case 'logged_out':
                    case 'guest':
                        $page['enabled'] = !auth()->check();
                        break;
                    case 'auth_required':
                        // enable page and set auth_required to true
                        $page['enabled'] = true;
                        $page['auth_required'] = true;
                        break;
                    default:
                        $page['enabled'] = false; // Default to false if not recognized
                }
            }

            $menu = $page['menu'] ?? false;
            $menu = is_bool($menu) ? ['enabled' => $menu] : $menu;
            $menu = is_string($menu) ? ['menu_id' => $menu, 'enabled' => true] : $menu;
            $menu = is_array($menu) ? $menu : [];

            $page['menu'] = array_merge([
                'label' => $page['title'] ?? $slug, // Default label to title or capitalized slug
                'order' => 10, // Default order if not set
                'enabled' => $page['auth_required'] ?? false, // Default auth requirement to false
            ], $menu);
        }
        return $page;
    }

    /**
     * Get all enabled pages
     */
    public static function getEnabledPages(): array {
        try {
            $pages = config('pages', []);
            $app_defaults_pages = config('app-defaults.pages', []);
            foreach($pages as $slug => $page) {
                $pages[$slug] = self::getPageConfig($slug);
            }
            $pages = array_filter($pages, fn($page) => $page['enabled'] ?? false);
        } catch (\Error $e) {
            error_log("Error retrieving enabled pages: " . $e->getMessage());
            throw new \Error("Error retrieving enabled pages: " . $e->getMessage());
        }
        return $pages ?? [];
    }

    /**
     * Get menu items
     */
    public static function getMenuItems(): array {
        $pages = self::getEnabledPages();
        $menuItems = [];

        // Process menu items from page config
        foreach ($pages as $slug => $page) {
            if (isset($page['menu']) && ($page['menu']['enabled'] ?? false)) {
                $menuItems[] = [
                    'slug' => $slug,
                    'label' => $page['menu']['label'] ?? $page['title'],
                    'url' => base_url($page['uri']),
                    'order' => $page['menu']['order'] ?? 10, // Default order if not set
                    'auth_required' => $page['menu']['auth_required'] ?? false,
                ];
            }
        }
        
        // Process menu items from service registrations
        $registeredMenus = config('app.registered_menus', []);
        foreach ($registeredMenus as $menuConfig) {
            if ($menuConfig['enabled'] ?? false) {
                // Get service URI from the service class
                $serviceUri = self::getServiceUri($menuConfig['service_class']);
                
                $menuItems[] = [
                    'slug' => class_basename($menuConfig['service_class']),
                    'label' => $menuConfig['label'] ?? class_basename($menuConfig['service_class']),
                    'url' => base_url($serviceUri),
                    'order' => $menuConfig['order'] ?? 10,
                    'auth_required' => $menuConfig['auth_required'] ?? false,
                    'service_class' => $menuConfig['service_class'],
                ];
            }
        }

        // Sort by order
        usort($menuItems, fn($a, $b) => $a['order'] <=> $b['order']);

        return $menuItems;
    }
    
    /**
     * Get URI for a service class by checking its $provides array
     */
    private static function getServiceUri(string $serviceClass): string {
        try {
            $reflection = new \ReflectionClass($serviceClass);
            if ($reflection->hasProperty('provides')) {
                $providesProperty = $reflection->getProperty('provides');
                $providesProperty->setAccessible(true);
                $provides = $providesProperty->getValue();
                
                if (is_array($provides) && !empty($provides['uri'])) {
                    return $provides['uri'];
                }
            }
        } catch (\Exception $e) {
            error_log("ERROR: Failed to get URI for service {$serviceClass}: " . $e->getMessage());
        }
        
        // Fallback: generate URI from class name
        return '/' . strtolower(str_replace('Service', '', class_basename($serviceClass)));
    }

    /**
     * Get page configuration including proper title fallback
     */
    public function getConfig(string $slug): array {
        error_log('[DEBUG] ' . __METHOD__ . " called with slug: {$slug}");
        
        // First, check for config-based pages
        $pages = config('pages', []);
        $pageConfig = $pages[$slug] ?? [];
        
        // If not found in config, check for service-based pages
        if (empty($pageConfig)) {
            $servicePages = config('app.registered_service_pages', []);
            if (isset($servicePages[$slug])) {
                $pageConfig = $this->buildServicePageConfig($servicePages[$slug]);
            }
        }
        
        // If still no page config found, return empty configuration
        if (empty($pageConfig)) {
            error_log("[DEBUG] No page configuration found for slug: {$slug}");
            return [
                'title' => 'Page Not Found',
                'description' => '',
                'keywords' => '',
                'content' => '',
                'contentBlock' => null,
                'template' => 'default',
                'showContentTitle' => false,
            ];
        }

        // Create content block based on different content sources
        $contentBlock = null;
        $contentSource = $this->determineContentSource($pageConfig);
        
        if ($contentSource) {
            $blockService = app(\App\Services\BlockService::class);
            $blockOptions = [
                'id' => $slug . '-content',
                'title' => $pageConfig['content_title'] ?? null,
            ];
            
            // Single line to create block - let BlockService handle all the logic
            $contentBlock = $blockService->createFromContentSource($contentSource, $blockOptions, $pageConfig);
        }
        

        // Handle page title with proper fallback logic
        $pageTitle = isset($pageConfig['title']) ? $pageConfig['title'] : 'no page title';
        $pageTitle .= ' (' . ($contentBlock ? ($contentBlock->getTitle() ?: 'no content title') : 'no content block') . ')';

        $pageConfig['title'] = $pageTitle;
        
        return [
            'title' => $pageConfig['title'],
            'description' => $pageConfig['description'] ?? '',
            'keywords' => $pageConfig['keywords'] ?? '',
            'content' => $pageConfig['content'] ?? '',
            'contentBlock' => $contentBlock,
            'template' => $pageConfig['template'] ?? 'default',
            'showContentTitle' => $contentBlock ? $contentBlock->shouldShowContentTitle($pageTitle) : false,
        ];
    }
    
    /**
     * Build page configuration from service registration
     */
    protected function buildServicePageConfig(array $serviceRegistration): array {
        $serviceClass = $serviceRegistration['service_class'];
        error_log("[DEBUG] Building service page config for: {$serviceClass}");
        
        try {
            // Get service instance and its configuration
            $service = app($serviceClass);
            
            // Get page config from service if available
            $pageConfig = method_exists($service, 'getPageConfig') ? $service->getPageConfig() : [];
            
            // Build unified page config
            $config = [
                'title' => $pageConfig['title'] ?? $service->getTitle() ?? 'Service Page',
                'description' => $pageConfig['description'] ?? $pageConfig['meta_description'] ?? '',
                'callback' => $serviceClass . '@render', // Use service callback
                'type' => 'service',
                'service_class' => $serviceClass,
                'template' => $pageConfig['template'] ?? 'page',
            ];
            
            error_log("[DEBUG] Built service config: " . json_encode($config));
            return $config;
            
        } catch (\Exception $e) {
            error_log("[ERROR] Failed to build service page config for {$serviceClass}: " . $e->getMessage());
            return [
                'title' => 'Service Error',
                'content' => 'Service temporarily unavailable.',
                'type' => 'error',
            ];
        }
    }
    
    /**
     * Determine what type of content source we have
     */
    protected function determineContentSource(array $pageConfig): ?array {
        if (isset($pageConfig['content'])) {
            return ['type' => 'content', 'data' => $pageConfig['content']];
        }
        
        if (isset($pageConfig['source'])) {
            return ['type' => 'source', 'data' => $pageConfig['source']];
        }
        
        if (isset($pageConfig['callback'])) {
            return ['type' => 'callback', 'data' => $pageConfig['callback']];
        }
        
        if (isset($pageConfig['view'])) {
            return ['type' => 'view', 'data' => $pageConfig['view']];
        }
        
        return null;
    }
    
    /**
     * Render page content using BlockService
     */
    public function renderPageContent(string $content): string {
        $block = app(\App\Services\BlockService::class);
        return $block->postProcessContent($content, 'html');
    }

    /**
     * Render content from a source file/string, auto-detecting format
     */
    private static function renderSourceContent(string $source): string {
        // Check if it's a service call (contains @)
        if (strpos($source, '@') !== false) {
            return self::callService($source);
        }
        
        // Check if it's a file path
        if (self::isFilePath($source)) {
            $filePath = resolve_file_path($source);
            
            if (!$filePath || !File::exists($filePath)) {
                return '<div class="alert alert-warning">File not found: ' . $source . '</div>';
            }
            
            // Determine format by file extension
            $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'md':
                case 'markdown':
                    return self::renderMarkdownFileFromPath($filePath);
                    
                case 'html':
                case 'htm':
                    return File::get($filePath);
                    
                case 'txt':
                    return '<pre>' . htmlspecialchars(File::get($filePath)) . '</pre>';
                    
                default:
                    return '<div class="alert alert-warning">Unsupported file format: ' . $extension . '</div>';
            }
        }
        
        // If not a file path, treat as raw content and try to detect format
        if (self::looksLikeMarkdown($source)) {
            return self::renderMarkdownContent($source);
        }
        
        // Default: treat as HTML
        return $source;
    }
    
    /**
     * Render markdown from a resolved file path
     */
    private static function renderMarkdownFileFromPath(string $filePath): string {
        $markdown = File::get($filePath);
        return self::renderMarkdownContent($markdown);
    }

    /**
     * Check if a string looks like a file path
     */
    private static function isFilePath(string $source): bool {
        return strpos($source, '.') !== false && !str_contains($source, ' ') && strlen($source) < 255;
    }

    /**
     * Check if content looks like markdown
     */
    private static function looksLikeMarkdown(string $content): bool {
        // Simple heuristics to detect markdown
        return preg_match('/^#[^#]|^\*\*|^-\s|^\d+\.\s|```/m', $content);
    }

    /**
     * Render markdown file content as HTML
     */
    private static function renderMarkdownFile(string $filename): string {
        $filePath = resolve_file_path($filename);
        
        if (!File::exists($filePath)) {
            return '<div class="alert alert-warning">' . $filename . ' not found.</div>';
        }

        $markdown = File::get($filePath);
        return self::renderMarkdownContent($markdown);
    }

    /**
     * Render markdown content as HTML
     */
    private static function renderMarkdownContent(string $markdown): string {
        // Configure environment with GitHub-flavored markdown
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'code_block_class' => 'language-', // This helps with Prism.js syntax highlighting
        ]);
        
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        
        $converter = new MarkdownConverter($environment);
        
        // First convert markdown to HTML
        $html = $converter->convert($markdown)->getContent();
        
        // Use BlockService for post-processing (includes shortcodes and other processing)
        $block = app(\App\Services\BlockService::class);
        $html = $block->postProcessContent($html, 'markdown');
        
        // Post-process to ensure proper Prism.js classes
        $html = preg_replace('/<code class="language-(\w+)"/', '<code class="language-$1"', $html);
        
        return view('sections.content-markdown', ['content' => $html])->render();
    }
    
    /**
     * Extract the first heading (H1) from markdown content for use as title
     */
    public static function extractMarkdownTitle(string $markdown): ?string {
        // Look for first-level heading (# Title)
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Render service content
     */
    private static function renderServiceContent(string $serviceCall): string {
        // Parse service@method format
        if (strpos($serviceCall, '@') !== false) {
            [$serviceClass, $method] = explode('@', $serviceCall, 2);
            
            // Try multiple namespaces
            $possibleClasses = [
                "App\\Services\\{$serviceClass}",        // Core services
                "YourApp\\Services\\{$serviceClass}",    // Application services
            ];
            
            foreach ($possibleClasses as $fullServiceClass) {
                if (class_exists($fullServiceClass) && method_exists($fullServiceClass, $method)) {
                    return app($fullServiceClass)->$method();
                }
            }
        }
        
        return '<div class="alert alert-warning">Service "' . $serviceCall . '" not found.</div>';
    }

    /**
     * Call service method (used for source format)
     */
    private static function callService(string $serviceCall): string {
        return self::renderServiceContent($serviceCall);
    }

    /**
     * Render view content
     */
    private static function renderViewContent(string $viewId): string {
        try {
            return view($viewId)->render();
        } catch (\Exception $e) {
            return '<div class="alert alert-warning">View "' . $viewId . '" not found.</div>';
        }
    }
}
