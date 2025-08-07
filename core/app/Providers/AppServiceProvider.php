<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        // Set app.app_root from bundle config as soon as config is available
        // This allows us to use config('app.app_root') everywhere without fallbacks
        $this->configureAppRoot();
        
        // Register application services
        $this->app->singleton(\YourApp\Services\HelloService::class);
    }

    /**
     * Configure app_root from bundle config with proper fallback
     */
    private function configureAppRoot(): void {
        $appRoot = config('bundle.app_root', config('app.app_root', dirname(base_path())));
        config(['app.app_root' => env('APP_ROOT', $appRoot)]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Configure global URL handling for root-based serving
        $this->configureGlobalUrls();
    }

    /**
     * Configure global URL handling with base URL detection
     */
    private function configureGlobalUrls(): void {
        $request = request();
        if(!getenv('APP_URL') || !getenv('ASSET_URL')) {
            $scheme = $request->getScheme();
            $host = $request->getHttpHost();
        }
        $baseURL = getenv('APP_URL');
        $assetURL = getenv('ASSET_URL');

        $scriptName = $request->getScriptName();

        $basePath = dirname($scriptName);
        if ($basePath === '/' || $basePath === '\\' || $basePath === '.' || $scriptName === 'artisan') {
            $basePath = '';
        }
        
        // Configure Laravel's URL generation
        URL::forceRootUrl($baseURL);
        
        // Store URLs in config for global access
        config(['app.detected_base_url' => $baseURL]);
        config(['app.detected_public_url' => $assetURL]);
        
        // Create global helper macros
        URL::macro('baseUrl', function ($path = '') use ($baseURL) {
            // $baseURL = config('app.detected_base_url');
            return $baseURL . ($path ? '/' . ltrim($path, '/') : '');
        });
        
        URL::macro('publicUrl', function ($path = '') use ($assetURL) {
            // $assetURL = config('app.detected_public_url');
            return $assetURL . ($path ? '/' . ltrim($path, '/') : '');
        });
    }
}
