<?php

namespace App\Services;

class ShortcodeService {
    /**
     * Process shortcodes in content based on source format detection
     */
    public function processShortcodes(string $content, ?string $sourceFormat = null): string {
        $shortcodes = config('app.registered_shortcodes', []);
        
        if (empty($shortcodes)) {
            error_log("DEBUG: No shortcodes registered");
            return $content;
        }
        
        error_log("DEBUG: Processing shortcodes with " . count($shortcodes) . " registered shortcodes");
        
        // Auto-detect source format if not provided
        if ($sourceFormat === null) {
            $sourceFormat = $this->detectSourceFormat($content);
            error_log("DEBUG: Detected source format: " . $sourceFormat);
        }
        
        // For markdown source, use alternative syntax {{shortcode}} instead of [shortcode]
        // This prevents conflict with markdown link syntax
        // Updated: Always use {{shortcode}} syntax as it's more distinctive
        $pattern = '/\{\{(\w+)(?:\s+(.*?))?\}\}/s';
            
        error_log("DEBUG: Using pattern: " . $pattern);
        
        $content = preg_replace_callback($pattern, function($matches) use ($shortcodes, $sourceFormat) {
            error_log("DEBUG: Found shortcode match: " . json_encode($matches));
            
            $shortcode = $matches[1];
            $attributeString = isset($matches[2]) ? trim($matches[2]) : '';
            
            if (!isset($shortcodes[$shortcode])) {
                error_log("DEBUG: Shortcode {$shortcode} not found in registered shortcodes");
                return $matches[0]; // Return original if shortcode not found
            }
            
            $serviceClass = $shortcodes[$shortcode];
            error_log("DEBUG: Processing shortcode {$shortcode} with class {$serviceClass}");
            
            try {
                // Parse attributes
                $attributes = $this->parseShortcodeAttributes($attributeString);
                error_log("DEBUG: Parsed attributes: " . json_encode($attributes));
                
                // Debug: check if class exists
                if (!class_exists($serviceClass)) {
                    error_log("ERROR: Service class {$serviceClass} does not exist");
                    return $sourceFormat === 'markdown' ? "**[shortcode error: class not found]**" : '[shortcode error: class not found]';
                }
                
                // Create service instance with attributes
                $service = new $serviceClass($attributes);
                $result = $service->render($attributes);
                error_log("DEBUG: Service render result length: " . strlen($result));
                return $result;
            } catch (\Exception $e) {
                error_log("ERROR: Shortcode processing failed for {$serviceClass}: " . $e->getMessage());
                error_log("ERROR: Exception trace: " . $e->getTraceAsString());
                return $sourceFormat === 'markdown' ? "**[shortcode error: {$shortcode}]**" : '[shortcode error]';
            }
        }, $content);
        
        return $content;
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
}
