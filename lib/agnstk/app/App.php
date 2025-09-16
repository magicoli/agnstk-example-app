<?php

namespace Agnstk;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * AGNSTK Application Bootstrap Class
 * 
 * This class handles all the framework initialization using only
 * the configuration provided by the main application.
 */
class App
{
    protected array $bundleConfig;
    protected string $corePath;
    protected Application $laravel;

    public function __construct(array $bundleConfig)
    {
        $this->bundleConfig = $bundleConfig;
        $this->corePath = dirname(__DIR__);
        
        define('LARAVEL_START', microtime(true));
        
        $this->bootstrap();
    }

    /**
     * Bootstrap the Laravel application with proper configuration
     */
    protected function bootstrap(): void
    {
        // Framework autoloader should already be loaded by main app
        
        // Check for maintenance mode using app root from config
        $maintenance = $this->bundleConfig['app_root'] . '/storage/framework/maintenance.php';
        if (file_exists($maintenance)) {
            require $maintenance;
        }

        // Capture the request for URL detection
        $request = Request::capture();
        
        // Auto-detect URLs based on the request
        $this->configureUrls($request);
        
        // Bootstrap Laravel using auto-detected AGNSTK path
        $this->laravel = require_once $this->corePath . '/bootstrap/app.php';
        
        // Configure Laravel with bundle config
        $this->configureLaravel();
    }

    /**
     * Auto-detect and configure URLs based on the current request
     */
    protected function configureUrls(Request $request): void
    {
        $scheme = $request->getScheme();
        $host = $request->getHttpHost();
        $scriptName = $request->getScriptName();

        // Extract the base path (handles subdirectory installations)
        $urlBasePath = dirname($scriptName);
        if ($urlBasePath === '/' || $urlBasePath === '\\') {
            $urlBasePath = '';
        }

        // Build the complete base URL
        $baseUrl = $scheme . '://' . $host . $urlBasePath;
        
        // Calculate relative path from app root to agnstk framework
        $agnstk_relpath = str_replace($this->bundleConfig['app_root'] . '/', '', $this->corePath);
        $assetUrl = $baseUrl . '/' . $agnstk_relpath . '/public';

        // Set environment variables for Laravel configuration
        putenv("APP_URL=" . $baseUrl);
        putenv("ASSET_URL=" . $assetUrl);
    }

    /**
     * Configure Laravel after bootstrapping
     */
    protected function configureLaravel(): void
    {
        $this->laravel->booted(function ($app) {
            // Read all config files from app's config directory
            $appConfigPath = $this->bundleConfig['app_root'] . '/config';
            if (is_dir($appConfigPath)) {
                $configFiles = glob($appConfigPath . '/*.php');
                
                foreach ($configFiles as $configFile) {
                    if(preg_match('/\.example\.php$/', $configFile)) {
                        // Skip example config files
                        continue;
                    }
                    $configName = basename($configFile, '.php');
                    $configData = require $configFile;
                    // Completely override the library config with app config
                    $app['config']->set($configName, $configData);
                }
            }

            // Ensure bundle config values are set in Laravel app config namespace
            foreach(config('bundle') as $key => $value) {
                config(["app.$key" => $value]);
            }
            
            // Now register service features with the loaded config
            $appServiceProvider = new \Agnstk\Providers\AppServiceProvider($app);
            $appServiceProvider->registerServiceFeatures();
        });
    }

    /**
     * Handle the incoming HTTP request
     */
    public function handleRequest(): void
    {
        // Check for maintenance mode again (framework level)
        $agnstk_path = $this->corePath;
        $maintenance = $agnstk_path . '/storage/framework/maintenance.php';
        if (file_exists($maintenance)) {
            require $maintenance;
        }

        $request = Request::capture();
        $this->laravel->handleRequest($request);
    }
}
