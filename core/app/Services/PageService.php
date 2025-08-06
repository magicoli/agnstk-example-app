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
        $pages = config('app-defaults.pages', []);
        return $pages[$pageId] ?? null;
    }

    /**
     * Get all enabled pages
     */
    public static function getEnabledPages(): array {
        $pages = config('app-defaults.pages', []);
        return array_filter($pages, fn($page) => $page['enabled'] ?? false);
    }

    /**
     * Get menu items
     */
    public static function getMenuItems(): array {
        $pages = self::getEnabledPages();
        $menuItems = [];

        foreach ($pages as $pageId => $page) {
            if (isset($page['menu']) && ($page['menu']['enabled'] ?? false)) {
                $menuItems[] = [
                    'pageId' => $pageId,
                    'label' => $page['menu']['label'] ?? $page['title'],
                    'url' => base_url($page['slug']),
                    'order' => $page['menu']['order'] ?? 10, // Default order if not set
                    'auth_required' => $page['menu']['auth_required'] ?? false,
                ];
            }
        }

        // Sort by order
        usort($menuItems, fn($a, $b) => $a['order'] <=> $b['order']);

        return $menuItems;
    }

    /**
     * Render page content based on its configuration
     */
    public static function renderPageContent(string $pageId): string {
        $page = self::getPageConfig($pageId);
        
        if (!$page || !($page['enabled'] ?? false)) {
            return '<div class="alert alert-warning">Page not found or disabled.</div>';
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
        error_log('DEBUG: Requested source file: ' . $source);
        // Get the file root from config (defaults to base_path if not set)
        $fileRoot = config('app.file_root', base_path());
        
        $filePath = $fileRoot . '/' . ltrim($source, '/');
        error_log('DEBUG: Resolved file path: ' . $filePath);
        
        if (File::exists($filePath)) {
            return $filePath;
        }
        error_log('DEBUG: File not found: ' . $filePath);        

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
        $blocks = config('app-defaults.blocks', []);
        $block = $blocks[$blockId] ?? null;
        
        if (!$block || !($block['enabled'] ?? false)) {
            return '<div class="alert alert-warning">Block "' . $blockId . '" not found or disabled.</div>';
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
            $fullServiceClass = "App\\Services\\{$serviceClass}";
            
            if (class_exists($fullServiceClass) && method_exists($fullServiceClass, $method)) {
                return app($fullServiceClass)->$method();
            }
        }
        
        return '<div class="alert alert-warning">Service "' . $serviceCall . '" not found.</div>';
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
