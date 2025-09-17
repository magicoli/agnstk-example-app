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
        // Override Laravel's paths to use main app directories for storage and database
        $this->configureAppPaths();
        
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
     * Configure Laravel application paths to use main app directories
     */
    protected function configureAppPaths(): void
    {
        $appRoot = $this->bundleConfig['app_root'];
        
        // Override Laravel paths to point to main app directories
        $this->laravel->useStoragePath($appRoot . '/storage');
        $this->laravel->useDatabasePath($appRoot . '/storage/database');
        
        // Ensure all required storage directories exist
        $this->ensureStorageDirectories($appRoot);
        
        // Initialize database if needed (after Laravel is fully booted)
        $this->laravel->booted(function () {
            $this->initializeDatabase();
        });
    }

    /**
     * Ensure all required storage directories exist
     */
    protected function ensureStorageDirectories(string $appRoot): void
    {
        $requiredDirs = [
            '/storage',
            '/storage/app',
            '/storage/app/public',
            '/storage/database',
            '/storage/framework',
            '/storage/framework/cache',
            '/storage/framework/sessions',
            '/storage/framework/views',
            '/storage/logs'
        ];
        
        foreach ($requiredDirs as $dir) {
            $fullPath = $appRoot . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }

    /**
     * Initialize database using Laravel's proper methods
     */
    protected function initializeDatabase(): void
    {
        try {
            $config = $this->laravel->make('config');
            $defaultConnection = $config->get('database.default');
            $connectionConfig = $config->get("database.connections.$defaultConnection");
            
            // Only initialize for SQLite databases that don't exist
            if ($connectionConfig['driver'] === 'sqlite') {
                $databasePath = $connectionConfig['database'];
                
                // Ensure database file exists for SQLite
                if (!file_exists($databasePath)) {
                    // Create empty database file
                    touch($databasePath);
                    chmod($databasePath, 0644);
                    
                    // Run migrations if available
                    if ($this->laravel->runningInConsole() === false) {
                        // We're in web context, can't run migrations directly
                        // Database will be created but migrations need to be run separately
                    }
                }
            }
            
            // Test database connection
            $this->laravel->make('db')->connection()->getPdo();
            
        } catch (\Exception $e) {
            // Log database initialization error but don't break the application
            if ($this->laravel->bound('log')) {
                $this->laravel->make('log')->warning('Database initialization failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the Laravel application instance (for testing purposes)
     */
    public function getLaravel(): Application
    {
        return $this->laravel;
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

    /**
     * Handle the incoming HTTP request
     */
    public function loadRequest(): void
    {
        // Check for maintenance mode again (framework level)
        $agnstk_path = $this->corePath;
        $maintenance = $agnstk_path . '/storage/framework/maintenance.php';
        if (file_exists($maintenance)) {
            require $maintenance;
        }

        $request = Request::capture();
        // $this->laravel->handleRequest($request);
    }
}
