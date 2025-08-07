<?php

namespace App\Services;

class ShortcodeService
{
    /**
     * Process shortcodes in content with context awareness
     */
    public function processShortcodes(string $content, string $context = 'html'): string
    {
        $shortcodes = config('app.registered_shortcodes', []);
        
        if (empty($shortcodes)) {
            return $content;
        }
        
        // For markdown context, use alternative syntax {{shortcode}} instead of [shortcode]
        $pattern = $context === 'markdown' 
            ? '/\{\{(\w+)\}\}/' 
            : '/\[(\w+)(?:\s+([^\]]*))?\]/';
        
        $content = preg_replace_callback($pattern, function($matches) use ($shortcodes, $context) {
            $shortcode = $matches[1];
            
            if (!isset($shortcodes[$shortcode])) {
                return $matches[0]; // Return original if shortcode not found
            }
            
            $serviceClass = $shortcodes[$shortcode];
            
            try {
                $service = app($serviceClass);
                
                // Use context-aware rendering  
                $renderContext = $context === 'markdown' ? 'text' : $context;
                return $service->render($renderContext);
            } catch (\Exception $e) {
                error_log("ERROR: Shortcode processing failed for {$serviceClass}: " . $e->getMessage());
                return $context === 'markdown' ? "**[shortcode error: {$shortcode}]**" : '[shortcode error]';
            }
        }, $content);
        
        return $content;
    }
}
