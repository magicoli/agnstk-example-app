<?php

namespace Agnstk\Utils;

/**
 * Utility class for detecting application configuration
 */
class AppDetector
{
    /**
     * Get the application's root namespace from configuration
     */
    public static function getAppNamespace(?string $appRoot = null): ?string
    {
        // Use the app_root from config if provided, otherwise use the passed parameter
        $appRoot = $appRoot ?: config('app.app_root');
        
        if (!$appRoot) {
            return null; // No app root configured, cannot proceed
        }
        
        $composerPath = $appRoot . '/composer.json';
        
        if (!file_exists($composerPath)) {
            return null;
        }
        
        $composer = json_decode(file_get_contents($composerPath), true);
        if (!isset($composer['autoload']['psr-4'])) {
            return null;
        }
        
        // Find the namespace that maps to 'src/'
        foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
            if ($path === 'src/' || $path === 'src') {
                return rtrim($namespace, '\\');
            }
        }
        
        return null;
    }
    
    /**
     * Get a fully qualified class name for an app service
     */
    public static function getAppServiceClass(string $serviceClass): ?string
    {
        // If already fully qualified, return as-is
        if (class_exists($serviceClass)) {
            return $serviceClass;
        }
        
        // Try with app namespace
        $appNamespace = static::getAppNamespace();
        if ($appNamespace) {
            $namespacedClass = "{$appNamespace}\\Services\\{$serviceClass}";
            if (class_exists($namespacedClass)) {
                return $namespacedClass;
            }
        }
        
        return null;
    }
}
