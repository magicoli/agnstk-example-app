<?php

/**
 * AGNSTK Global Helper Functions
 */

if (!function_exists('base_url')) {
    /**
     * Generate a URL with the detected base URL
     */
    function base_url($path = '') {
        // Use Laravel's url() helper to generate base URLs
        return url($path);
    }
}

if (!function_exists('public_url')) {
    /**
     * Generate a URL for public assets with the detected public URL
     */
    function public_url($path = '') {
        // Use Laravel's asset() helper which generates URLs for public assets
        return asset($path);
    }
}

if (!function_exists('build_asset')) {
    /**
     * Generate a URL for built assets (CSS/JS) using Vite manifest
     */
    function build_asset($filename) {
        $manifestPath = public_path('build/manifest.json');
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            
            // Map logical names to actual manifest entries
            $assetMap = [
                'main-styles.css' => 'resources/sass/main-styles.scss',
                'main-scripts.js' => 'resources/js/main-scripts.js',
            ];
            
            $manifestKey = $assetMap[$filename] ?? $filename;
            
            if (isset($manifest[$manifestKey])) {
                return public_url('build/' . $manifest[$manifestKey]['file']);
            }
            
            // Also try to find by filename pattern
            foreach ($manifest as $entry) {
                if (isset($entry['file'])) {
                    $actualFile = basename($entry['file']);
                    // Match main-styles.css to any CSS file, main-scripts.js to any JS file
                    if ($filename === 'main-styles.css' && strpos($actualFile, '.css') !== false) {
                        return public_url('build/' . $entry['file']);
                    }
                    if ($filename === 'main-scripts.js' && strpos($actualFile, '.js') !== false) {
                        return public_url('build/' . $entry['file']);
                    }
                }
            }
        }
        
        // Fallback to direct filename
        return public_url("build/assets/{$filename}");
    }
}

if (!function_exists('do_shortcode')) {
    /**
     * Process shortcodes using service name and parameters
     * Usage: {{ do_shortcode('hello', ['title' => 'Custom Title']) }}
     */
    function do_shortcode($shortcodeName, $parameters = []) {
        try {
            $shortcodeService = app(\Agnstk\Services\ShortcodeService::class);
            return $shortcodeService->renderShortcodeDirective($shortcodeName, $parameters);
        } catch (\Exception $e) {
            return config('app.debug') ? "[shortcode error: {$e->getMessage()}]" : '';
        }
    }
}

if (!function_exists('resolve_file_path')) {
    /**
     * Resolve file path relative to application root
     * This is a global utility function that can be used by any class
     */
    function resolve_file_path(string $path): string {
        // If path is already absolute, return as-is
        if (str_starts_with($path, '/')) {
            return $path;
        }
        
        // Use the app_root from bundle configuration
        $appRoot = config('bundle.app_root');
        if ($appRoot) {
            $fullPath = $appRoot . '/' . $path;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        // Fallback to framework root for framework files
        $frameworkPath = base_path($path);
        if (file_exists($frameworkPath)) {
            return $frameworkPath;
        }
        
        // Return the app root path for logging (even if file doesn't exist)
        return $appRoot ? $appRoot . '/' . $path : $frameworkPath;
    }
}
