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
        
        // Register core services
        $this->app->singleton(\App\Services\ShortcodeService::class);
        
        // Discover and register application services
        $this->discoverAndRegisterServices();
    }
    
    /**
     * Discover services in src/Services/ and register them with Laravel
     */
    private function discoverAndRegisterServices(): void {
        $appRoot = config('app.app_root');
        $servicesPath = $appRoot . '/src/Services';
        
        if (!is_dir($servicesPath)) {
            return;
        }
        
        // Find all PHP files in Services directory
        $serviceFiles = glob($servicesPath . '/*.php');
        
        foreach ($serviceFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = "YourApp\\Services\\{$className}";
            
            // Check if class exists and register it
            if (class_exists($fullClassName)) {
                $this->app->singleton($fullClassName);
            }
        }
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
        
        // Register service features (shortcodes, pages, menus, etc.)
        $this->registerServiceFeatures();
        
        // Register Blade directives for shortcodes
        $this->registerBladeDirectives();
    }
    
    /**
     * Register features declared by services in their $provides arrays
     */
    private function registerServiceFeatures(): void {
        $appRoot = config('app.app_root');
        $servicesPath = $appRoot . '/src/Services';
        
        if (!is_dir($servicesPath)) {
            return;
        }
        
        // Find all PHP files in Services directory
        $serviceFiles = glob($servicesPath . '/*.php');
        
        foreach ($serviceFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = "YourApp\\Services\\{$className}";
            
            if (class_exists($fullClassName)) {
                $this->registerServiceProvides($fullClassName);
            }
        }
    }
    
    /**
     * Register features from a service's $provides array
     */
    private function registerServiceProvides(string $serviceClass): void {
        // Get $provides array using provides() method if available, otherwise try reflection
        try {
            $provides = [];
            
            // First try to use provides() method
            if (method_exists($serviceClass, 'provides')) {
                $provides = $serviceClass::provides();
            } elseif (method_exists($serviceClass, 'getProvides')) {
                $provides = $serviceClass::getProvides();
            } else {
                // Fallback to reflection for static property
                $reflection = new \ReflectionClass($serviceClass);
                if ($reflection->hasProperty('provides')) {
                    $providesProperty = $reflection->getProperty('provides');
                    $providesProperty->setAccessible(true);
                    $provides = $providesProperty->getValue();
                }
            }
            
            if (!is_array($provides)) {
                return;
            }
            
            // Register shortcode
            if (!empty($provides['shortcode'])) {
                $this->registerShortcode($provides['shortcode'], $serviceClass);
            }
            
            // Register page route
            if (!empty($provides['uri'])) {
                $this->registerPageRoute($provides['uri'], $serviceClass);
            }
            
            // Register menu item  
            if (!empty($provides['menu'])) {
                $this->registerMenuItem($provides['menu'], $serviceClass);
            }
            
        } catch (\Exception $e) {
            error_log("ERROR: Failed to process service {$serviceClass}: " . $e->getMessage());
        }
    }
    
    /**
     * Register a shortcode handler
     */
    private function registerShortcode(string $shortcode, string $serviceClass): void {
        // For now, we'll store shortcode registrations in config
        // Later this can be processed by CMS adapters
        $shortcodes = config('app.registered_shortcodes', []);
        $shortcodes[$shortcode] = $serviceClass;
        config(['app.registered_shortcodes' => $shortcodes]);
        
    }
    
    /**
     * Register a page route
     */
    private function registerPageRoute(string $uri, string $serviceClass): void {
        // Register Laravel route
        \Illuminate\Support\Facades\Route::get($uri, function() use ($serviceClass) {
            $service = app($serviceClass);
            
            // Get page config to determine page title
            $pageConfig = $service->getPageConfig();
            $pageTitle = $pageConfig['title'] ?? null;
            
            // Get content title (fallback)
            $contentTitle = $service->getTitle();
            
            // Determine final titles
            $finalPageTitle = $pageTitle ?: $contentTitle; // Page title falls back to content title
            
            // If page title and content title are the same, don't show title in content block
            $shouldShowContentTitle = ($finalPageTitle !== $contentTitle);
            
            // Render content with title control
            $renderOptions = [];
            if (!$shouldShowContentTitle) {
                $renderOptions['title'] = null; // Don't show title in block
            }
            
            $content = $service->render($renderOptions);
            
            return view('page', [
                'title' => $finalPageTitle,
                'content' => $content
            ]);
        });
    }
    
    /**
     * Register a menu item
     */
    private function registerMenuItem(array $menuConfig, string $serviceClass): void {
        // Store menu registrations in config for processing
        $menus = config('app.registered_menus', []);
        $menuConfig['service_class'] = $serviceClass;
        $menus[] = $menuConfig;
        config(['app.registered_menus' => $menus]);
        
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
    
    /**
     * Register Blade directives for shortcode and service rendering
     */
    private function registerBladeDirectives(): void {
        // Blade directives temporarily disabled to prevent infinite loops
        // TODO: Implement proper Blade directive syntax
    }
}
