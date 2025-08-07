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
        'uri' => '/hello-service',        // Enable as a page at /hello-service
        'menu' => [
            'menu_id' => 'main', // Add to main menu
            'label' => 'HelloService',
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
     * Main render method - context-aware rendering
     */
    public function render(string $context = 'html'): string
    {
        $data = [
            'message' => 'Hello from AGNSTK!',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'platform' => $this->getCurrentPlatform(),
            'title' => 'AGNSTK Hello Service',
            'icon' => 'bi bi-rocket-takeoff'
        ];

        return match($context) {
            'text', 'markdown', 'cli' => $this->renderText($data),
            'card' => $this->renderCard($data), 
            default => $this->renderHtml($data)
        };
    }
    
    /**
     * Render as HTML card (for shortcodes, blocks)
     */
    private function renderCard(array $data): string
    {
        try {
            return view('sections.content-card', [
                'title' => $data['title'],
                'icon' => $data['icon'],
                'content' => $this->getHtmlContent($data)
            ])->render();
        } catch (\Exception $e) {
            return $this->renderFallback($data, $e);
        }
    }
    
    /**
     * Render as plain HTML (for pages)  
     */
    private function renderHtml(array $data): string
    {
        return $this->getHtmlContent($data);
    }
    
    /**
     * Render as text (for markdown, CLI)
     */
    private function renderText(array $data): string
    {
        return "**{$data['message']}** ({$data['platform']} - {$data['timestamp']})\n\n" .
               "Available as: Block, Shortcode [hello], Page /hello-service, Menu item, API endpoint\n" .
               "Deployment targets: Web, Desktop, Mobile, CLI\n" .
               "Edit src/Services/HelloService.php to customize.";
    }
    
    /**
     * Get HTML content body
     */
    private function getHtmlContent(array $data): string
    {
        return '<div class="alert alert-success">
            <h4>' . htmlspecialchars($data['message']) . '</h4>
            <p class="mb-2">
                <strong>Platform:</strong> ' . htmlspecialchars($data['platform']) . '<br>
                <strong>Generated:</strong> ' . htmlspecialchars($data['timestamp']) . '
            </p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h5>Available as:</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-layout-text-window"></i> Block (in page builders)</li>
                    <li><i class="bi bi-code-square"></i> Shortcode: <code>[hello]</code></li>
                    <li><i class="bi bi-file-earmark"></i> Page: <a href="' . base_url('/hello-service') . '">/hello-service</a></li>
                    <li><i class="bi bi-list"></i> Menu item</li>
                    <li><i class="bi bi-cloud"></i> API endpoint</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Deployment targets:</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-globe"></i> Web application</li>
                    <li><i class="bi bi-display"></i> Desktop app</li>
                    <li><i class="bi bi-phone"></i> Mobile app (PWA/Native)</li>
                    <li><i class="bi bi-terminal"></i> Command line</li>
                </ul>
            </div>
        </div>
        
        <div class="mt-3">
            <p class="text-muted mb-2">
                <i class="bi bi-info-circle"></i>
                Edit <code>src/Services/HelloService.php</code> to customize.
            </p>
        </div>';
    }
    
    /**
     * Fallback rendering when views fail
     */
    private function renderFallback(array $data, \Exception $e): string
    {
        $errorMessage = config('app.debug') 
            ? '<hr><details><summary>Debug Information</summary><pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre></details>'
            : '';

        return '<div class="alert alert-warning">
            <h4>' . htmlspecialchars($data['title']) . ' (Fallback)</h4>
            <p>Message: ' . htmlspecialchars($data['message']) . '</p>
            <p>Timestamp: ' . htmlspecialchars($data['timestamp']) . '</p>
            <p>Platform: ' . htmlspecialchars($data['platform']) . '</p>
            ' . $errorMessage . '
        </div>';
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
