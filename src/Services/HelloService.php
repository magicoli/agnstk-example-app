<?php

namespace YourApp\Services;

class HelloService
{
    /**
     * What this service provides
     * This tells AGNSTK what features to enable automatically
     */
    protected static array $provides = [
        'block' => true,           // Enable as a block
        'shortcode' => 'hello',    // Enable as [hello] shortcode  
        'uri' => '/hello/app-conf',        // Enable as a page at /hello
        'menu' => [
            'menu_id' => 'main', // Add to main menu
            'label' => 'Hello',
            'order' => 20,
            'enabled' => true, // Ensure menu item is enabled
        ],
        'api' => true,             // Enable API endpoint
    ];

    public function __construct()
    {
        error_log('DEBUG HelloService initializing');
    }

    /**
     * Main render method - used by all deployment targets
     */
    public function render(): string
    {
        try {
            return view('hello', [
                'message' => 'Hello from AGNSTK!',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'platform' => $this->getCurrentPlatform()
            ])->render();
        } catch (\Exception $e) {
            // Fallback if view doesn't exist
            return '<div class="container">
                <h1>Hello from AGNSTK!</h1>
                <p>Message: Hello from AGNSTK!</p>
                <p>Timestamp: ' . now()->format('Y-m-d H:i:s') . '</p>
                <p>Platform: ' . $this->getCurrentPlatform() . '</p>
            </div>';
        }
    }

    /**
     * Block configuration for editors
     */
    public function getBlockConfig(): array
    {
        return [
            'title' => 'Hello World',
            'description' => 'A simple hello world example block',
            'category' => 'example',
            'icon' => 'smiley',
            'keywords' => ['hello', 'example', 'demo']
        ];
    }

    /**
     * Page configuration for routing
     */
    public function getPageConfig(): array
    {
        return [
            'title' => 'Hello Page',
            'uri' => '/hello',
            'template' => 'hello',
            'meta_description' => 'Hello World example page'
        ];
    }

    /**
     * Menu configuration
     */
    public function getMenuConfig(): array
    {
        return self::$provides['menu'] ?? [];
    }

    /**
     * API endpoint data
     */
    public function getApiData(): array
    {
        return [
            'message' => 'Hello from AGNSTK API!',
            'timestamp' => now()->toISOString(),
            'version' => config('api.version', config('app.version', '1.0.0')), // Use API version or app version
        ];
    }

    /**
     * Detect current platform (for demonstration)
     * Should be a global method
     */
    private function getCurrentPlatform(): string
    {
        // Ultimately, platform will be set by the adapter, this is temporary
        if (defined('WP_VERSION')) return 'WordPress';
        if (defined('DRUPAL_VERSION')) return 'Drupal';
        if (class_exists('October\Rain\Foundation\Application')) return 'October CMS';
        return config('app.platform', 'Standalone'); // Default to Standalone
    }
}
