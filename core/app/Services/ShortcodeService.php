<?php

namespace App\Services;

class ShortcodeService
{
    /**
     * Process shortcodes in content
     */
    public function processShortcodes(string $content): string
    {
        $shortcodes = config('app.registered_shortcodes', []);
        
        if (empty($shortcodes)) {
            return $content;
        }
        
        // Process each registered shortcode
        foreach ($shortcodes as $shortcode => $serviceClass) {
            $pattern = '/\[' . preg_quote($shortcode) . '(?:\s+([^\]]*))?\]/';
            
            $content = preg_replace_callback($pattern, function($matches) use ($serviceClass) {
                try {
                    $service = app($serviceClass);
                    return $service->render();
                } catch (\Exception $e) {
                    error_log("ERROR: Shortcode processing failed for {$serviceClass}: " . $e->getMessage());
                    return '[shortcode error]';
                }
            }, $content);
        }
        
        return $content;
    }
}
