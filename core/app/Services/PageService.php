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
    public static function getPageConfig(string $pageId): ?array {
        $page = config("pages.$pageId", null);
        // Sanitize and set defaults
        if (!empty($page)) {
            $page = array_merge([
                'enabled' => true, // Default to enabled
                'uri' => $pageId, // Default uri to pageId
                'title' => ucfirst($pageId), // Default title to capitalized pageId
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
                'label' => $page['title'] ?? $pageId, // Default label to title or capitalized pageId
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
            foreach($pages as $pageId => $page) {
                $pages[$pageId] = self::getPageConfig($pageId);
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
        foreach ($pages as $pageId => $page) {
            if (isset($page['menu']) && ($page['menu']['enabled'] ?? false)) {
                $menuItems[] = [
                    'pageId' => $pageId,
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
                    'pageId' => class_basename($menuConfig['service_class']),
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
     * Render page content based on its configuration
     */
    public static function renderPageContent(string $pageId): string {
        $page = self::getPageConfig($pageId);
        
        if (!$page) {
            abort(404);
        }
        if (!($page['enabled'] ?? false)) {
            abort(403);
        }

        // Handle both old 'source' format and new 'content_source' format
        if (isset($page['source'])) {
            // New generic source format - determine type by file extension or content
            return self::renderSourceContent($page['source']);
        }

        // Legacy content_source format
        $contentSource = $page['content_source'] ?? 'view';
        $contentId = $page['content_id'] ?? $pageId;

        switch ($contentSource) {
            case 'block':
                return self::renderBlockContent($contentId);
                
            case 'service':
                return self::renderServiceContent($contentId);
                
            case 'view':
                return self::renderViewContent($contentId);
                
            default:
                return '<div class="alert alert-warning">Unknown content source: ' . $contentSource . '</div>';
        }
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
            $filePath = self::resolveSourceFilePath($source);
            
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
     * Resolve source file path relative to application root
     */
    private static function resolveSourceFilePath(string $source): ?string {
        // Get the application root from bundle config
        // $appRoot = config('bundle.app_root', dirname(base_path()));
        $appRoot = config('app.app_root');
        
        $filePath = $appRoot . '/' . ltrim($source, '/');

        if (File::exists($filePath)) {
            return $filePath;
        }

        return null;
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
        $filePath = base_path($filename);
        
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
        // Process shortcodes before markdown conversion
        $shortcodeService = app(\App\Services\ShortcodeService::class);
        $markdown = $shortcodeService->processShortcodes($markdown);
        
        // Configure environment with GitHub-flavored markdown
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'code_block_class' => 'language-', // This helps with Prism.js syntax highlighting
        ]);
        
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        
        $converter = new MarkdownConverter($environment);
        
        $html = $converter->convert($markdown);
        
        // Post-process to ensure proper Prism.js classes
        $html = preg_replace('/<code class="language-(\w+)"/', '<code class="language-$1"', $html);
        
        return '<div class="markdown-content">' . $html . '</div>';
    }

    /**
     * Render block content
     */
    private static function renderBlockContent(string $blockId): string {
        $blockConfig = config('blocks.' . $blockId, []);
        if(empty($blockConfig) || !($blockConfig['enabled'] ?? false)) {
            // If debug enabled, return debug message, otherwise empty content
            if (config('app.debug', false)) {
                return '<div class="alert alert-warning">Block "' . $blockId . '" not found or disabled.</div>';
            }
            return '';
        }

        return '<div class="block-content">' . ($block['content'] ?? '') . '</div>';
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
