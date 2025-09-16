<?php

namespace Agnstk\Services;

class ShortcodeService {
    
    /**
     * Class properties to store all necessary data
     */
    private string $content;
    private string $sourceFormat;
    private array $shortcodes;
    private string $pattern;
    
    /**
     * Process all shortcodes in the given content
     */
    public function processShortcodes(string $content, string $sourceFormat = null): string {
        // Safety check - ensure we always receive a string
        if (!is_string($content)) {
            error_log("[ERROR] ShortcodeService::processShortcodes() - received " . gettype($content) . " instead of string");
            if (is_array($content)) {
                $content = $content['content'] ?? '';
            }
            $content = (string) $content;
        }
        
        // Set class properties
        $this->content = $content;
        
        // Get shortcodes config with proper fallback handling
        $shortcodes = config('shortcodes');
        if (!is_array($shortcodes)) {
            $shortcodes = config('shortcodes');
            if (!is_array($shortcodes)) {
                $shortcodes = [];
            }
        }
        $this->shortcodes = $shortcodes;        
        
        // Auto-detect source format if not provided
        if ($sourceFormat === null) {
            $this->sourceFormat = $this->detectSourceFormat($this->content);
        } else {
            $this->sourceFormat = $sourceFormat;
        }
        
        // Always process {{shortcode}} syntax
        $this->pattern = '/\{\{(\w+)(?:\s+(.*?))?\}\}/s';
        $this->content = preg_replace_callback($this->pattern, [$this, 'processShortcodeMatch'], $this->content);
        
        // Process [shortcode] syntax only for non-markdown sources  
        if ($this->sourceFormat !== 'markdown') {
            $this->pattern = '/\[(\w+)(?:\s+(.*?))?\]/s';
            $this->content = preg_replace_callback($this->pattern, [$this, 'processShortcodeMatch'], $this->content);
        }
        
        return $this->content;
    }
    
    /**
     * Callback method for preg_replace_callback - processes individual shortcode matches
     * All necessary data is available through class properties
     */
    public function processShortcodeMatch(array $matches): string {
        $shortcode = $matches[1];
        $attributeString = isset($matches[2]) ? trim($matches[2]) : '';
        
        if (!isset($this->shortcodes[$shortcode])) {
            return $matches[0]; // Return original if shortcode not found
        }
        
        $serviceClass = $this->shortcodes[$shortcode];
        
        try {
            // Parse attributes
            $attributes = $this->parseShortcodeAttributes($attributeString);
            
            // Debug: check if class exists
            if (!class_exists($serviceClass)) {
                error_log("ERROR: Service class {$serviceClass} does not exist");
                return $matches[0];
            }
            
            // Create service instance
            $service = new $serviceClass($attributes);
            
            // Call render method with attributes
            $result = $service->render($attributes);
            
            // Handle both string and array returns
            if (is_array($result)) {
                // Convert array to HTML string for shortcode display
                $content = $result['content'] ?? '';
                $title = $result['title'] ?? '';
                $attributes = $result['attributes'] ?? [];
                
                // Build HTML output
                $html = '';
                if (!empty($title)) {
                    $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
                }
                if (!empty($content)) {
                    $html .= $content;
                }
                
                // Add CSS classes if provided
                if (!empty($attributes['class'])) {
                    $html = '<div class="' . htmlspecialchars($attributes['class']) . '">' . $html . '</div>';
                }
                
                return $html;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("ERROR: Failed to process shortcode {$shortcode}: " . $e->getMessage());
            return $matches[0]; // Return original on error
        }
    }
    
    /**
     * Detect source format from content
     */
    private function detectSourceFormat(string $content): string {
        // Simple heuristics to detect markdown
        if (preg_match('/^#\s+/m', $content) || // Headers
            preg_match('/\*\*[^*]+\*\*/', $content) || // Bold
            preg_match('/\*[^*]+\*/', $content) || // Italic
            preg_match('/```/', $content) || // Code blocks
            preg_match('/\n\s*\*\s+/', $content)) { // Lists
            return 'markdown';
        }
        
        return 'html'; // Default to HTML
    }
    
    /**
     * Parse shortcode attributes from string
     * Supports formats like: title="Custom Title" class="my-class" data-foo="bar"
     */
    private function parseShortcodeAttributes(string $attributeString): array {
        $attributes = [];
        
        if (empty($attributeString)) {
            return $attributes;
        }
        
        // Parse key="value" pairs, handling escaped quotes
        $pattern = '/(\w+)=(["\'])((?:\\.|(?!\2).)*)\2/';
        preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[3];
            
            // Handle special attribute keys
            if ($key === 'class') {
                $attributes['attributes']['class'] = $value;
            } else {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Render shortcode from Blade directive
     * Usage: @shortcode('hello', ['title' => 'Custom Title'])
     */
    public function renderShortcodeDirective(string $shortcodeName, array $attributes = []): string {
        $shortcodes = config('shortcodes', []);
        
        if (!isset($shortcodes[$shortcodeName])) {
            return config('app.debug') ? "[shortcode '{$shortcodeName}' not found]" : '';
        }
        
        $serviceClass = $shortcodes[$shortcodeName];
        
        try {
            $service = new $serviceClass($attributes);
            return $service->render($attributes);
        } catch (\Exception $e) {
            error_log("ERROR: Blade shortcode rendering failed for {$serviceClass}: " . $e->getMessage());
            return config('app.debug') ? "[shortcode error: {$shortcodeName}]" : '';
        }
    }
    
    /**
     * Render service directly from Blade directive  
     * Usage: @service('ExampleApp\\Services\\HelloService', ['title' => 'Custom Title'])
     */
    public function renderServiceDirective(string $serviceClass, array $attributes = []): string {
        try {
            if (!class_exists($serviceClass)) {
                return config('app.debug') ? "[service class '{$serviceClass}' not found]" : '';
            }
            
            $service = new $serviceClass($attributes);
            return $service->render($attributes);
        } catch (\Exception $e) {
            error_log("ERROR: Blade service rendering failed for {$serviceClass}: " . $e->getMessage());
            return config('app.debug') ? "[service error: {$serviceClass}]" : '';
        }
    }
}
