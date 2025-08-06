<?php

/**
 * AGNSTK Global Helper Functions
 */

if (!function_exists('base_url')) {
    /**
     * Generate a URL with the detected base URL
     */
    function base_url($path = '') {
        return \Illuminate\Support\Facades\URL::baseUrl($path);
    }
}

if (!function_exists('public_url')) {
    /**
     * Generate a URL for public assets with the detected public URL
     */
    function public_url($path = '') {
        return \Illuminate\Support\Facades\URL::publicUrl($path);
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
