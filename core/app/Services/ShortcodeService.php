<?php

namespace App\Services;

class ShortcodeService {
    /**
     * Process shortcodes in content based on source format detection
     */
    public function processShortcodes(string $content, ?string $sourceFormat = null): string {
        $shortcodes = config('app.registered_shortcodes', []);
        
        if (empty($shortcodes)) {
            return $content;
        }
       
        // Auto-detect source format if not provided
        if ($sourceFormat === null) {
            $sourceFormat = $this->detectSourceFormat($content);
        }
        
        // Process both {{shortcode}} and [shortcode] patterns
        // Exception: exclude [shortcode] for markdown sources to avoid conflicts
        $processedContent = $content;
        
        // Always process {{shortcode}} syntax
        $processedContent = $this->processPattern($processedContent, '/\{\{(\w+)(?:\s+(.*?))?\}\}/s', $shortcodes, $sourceFormat);
        
        // Process [shortcode] syntax only for non-markdown sources  
        if ($sourceFormat !== 'markdown') {
            $processedContent = $this->processPattern($processedContent, '/\[(\w+)(?:\s+(.*?))?\]/s', $shortcodes, $sourceFormat);
        }
        
        return $processedContent;
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
        $shortcodes = config('app.registered_shortcodes', []);
        
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
     * Usage: @service('YourApp\\Services\\HelloService', ['title' => 'Custom Title'])
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
    
    /**
     * Process a single shortcode pattern
     */
    protected function processPattern(string $content, string $pattern, array $shortcodes, string $sourceFormat): string {
        return preg_replace_callback($pattern, function($matches) use ($shortcodes, $sourceFormat) {
            
            $shortcode = $matches[1];
            $attributeString = isset($matches[2]) ? trim($matches[2]) : '';
            
            if (!isset($shortcodes[$shortcode])) {
                return $matches[0]; // Return original if shortcode not found
            }
            
            $serviceClass = $shortcodes[$shortcode];
            
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
                return $service->render($attributes);
                
            } catch (\Exception $e) {
                error_log("ERROR: Failed to process shortcode {$shortcode}: " . $e->getMessage());
                return $matches[0]; // Return original on error
            }
        }, $content);
    }
}
