<?php

namespace Agnstk\Providers;

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
        $this->app->singleton(\Agnstk\Services\ShortcodeService::class);
        
        // Discover and register application services
        $this->discoverAndRegisterServices();
    }
    
    /**
     * Discover services in src/Services/ and register them with Laravel
     */
    private function discoverAndRegisterServices(): void {
        $appRoot = config('bundle.app_root');
        $appNamespace = config('bundle.namespace');
        $servicesPath = $appRoot . '/src/Services';
        
        if (!is_dir($servicesPath) || !$appNamespace) {
            return;
        }
        
        // Find all PHP files in Services directory
        $serviceFiles = glob($servicesPath . '/*.php');
        
        foreach ($serviceFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = "{$appNamespace}\\Services\\{$className}";
            
            // Check if class exists and register it
            if (class_exists($fullClassName)) {
                $this->app->singleton($fullClassName);
            }
        }
    }

    /**
     * Configure bundle config by using the config already provided by App
     */
    private function configureAppRoot(): void {
        // Bundle config should already be set by App class
        // No need to load from file - just use what's already configured
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Configure global URL handling for root-based serving
        $this->configureGlobalUrls();
        
        // Service features registration moved to App.php booted() callback
        // to ensure bundle config is loaded first
        
        // Register Blade directives for shortcodes
        $this->registerBladeDirectives();
    }
    
    /**
     * Register features declared by services in their $provides arrays
     */
    public function registerServiceFeatures(): void {
        $appRoot = config('bundle.app_root') ?: config('app.app_root');
        if(empty($appRoot)) {
            throw new \RuntimeException("App root is not set in bundle configuration. Cannot register service features.");
            return;
        }
        $appNamespace = config('bundle.namespace');
        $servicesPath = $appRoot . '/src/Services';
        
        if (!is_dir($servicesPath) || !$appNamespace) {
            throw new \RuntimeException("Services path $servicesPath not found or app namespace $appNamespace not set. Cannot register service features.");
            return;
        }
        
        // Find all PHP files in Services directory
        $serviceFiles = glob($servicesPath . '/*.php');
        
        foreach ($serviceFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = "{$appNamespace}\\Services\\{$className}";

            if (class_exists($fullClassName)) {
                $this->registerServiceProvides($fullClassName);
            }
        }
    }
    
    /**
     * Register features from a service's $provides array
     */
    private function registerServiceProvides(string $serviceClass): void {
        // Get $provides array using provides() method
        try {
            // This might be enough:
            // if (!method_exists($serviceClass, 'provides')) {
            //     return;
            // }
            // $provides = $serviceClass::provides();

            $provides = [];
            
            // First try to use provides() method
            if (method_exists($serviceClass, 'provides')) {
                $provides = $serviceClass::provides();
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
        $shortcodes = config('shortcodes', []);
        $shortcodes[$shortcode] = $serviceClass;
        config(['shortcodes' => $shortcodes]);
    }
    
    /**
     * Register a page route
     */
    private function registerPageRoute(string $uri, string $serviceClass): void {
        // Generate a slug from the service class name for consistent identification
        $slug = $this->generateSlugFromServiceClass($serviceClass);
        
        // Store service mapping for PageController to access
        $servicePages = config('app.registered_service_pages', []);
        $servicePages[$slug] = [
            'uri' => $uri,
            'service_class' => $serviceClass,
            'type' => 'service',
        ];
        config(['app.registered_service_pages' => $servicePages]);
        
        // Register Laravel route using PageController
        \Illuminate\Support\Facades\Route::get($uri, [\Agnstk\Http\Controllers\PageController::class, 'show'])
            ->defaults('slug', $slug);
    }
    
    /**
     * Generate a consistent slug from service class name
     */
    private function generateSlugFromServiceClass(string $serviceClass): string {
        // Extract class name without namespace
        $className = class_basename($serviceClass);
        
        // Remove 'Service' suffix if present
        $className = preg_replace('/Service$/', '', $className);
        
        // Convert to slug format
        return \Illuminate\Support\Str::slug($className);
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
