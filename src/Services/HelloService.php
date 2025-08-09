<?php

namespace YourApp\Services;

use Illuminate\Contracts\Support\Renderable;
use App\Traits\RenderableTrait;

class HelloService implements Renderable {
    use RenderableTrait;
    /**
     * Service configuration for editors
     */

    protected static $initialized = false; // Track if service is initialized

    protected static $label;
    protected static $description;
    protected static $category;
    protected static $icon;
    protected static $keywords;
    protected static $uri;

    /**
     * What this service provides
     * This tells AGNSTK what features to enable automatically
     */
    protected static array $provides = []; // DO NOT SET VALUE HERE but when initializing the service
    
    // Default values for dynamic content
    protected static $defaultTitle;
    protected static $defaultContent;

    protected string $title;
    protected array $data = [];
    protected string $content;
    protected array $attributes = []; // For CSS classes and other HTML attributes

    public function __construct(array $options = []) {
        // Initialize static properties
        self::init();
        
        // Set instance properties from options
        $this->title = $options['title'] ?? static::$defaultTitle;
        $this->content = $options['content'] ?? static::$defaultContent;
        $this->attributes = $options['attributes'] ?? [];

        self::provides();
    }

    /**
     * Set service instance properties
     * This is useful for dynamic configuration or when properties need to be set after instantiation
     */
    public static function init() {
        if(self::$initialized) {
            return; // Already initialized
        }

        // Only lable is set here (for admin and editors).
        // Title is dynamic so it can not be set here.
        // It should be set with arguments passed to the instance or by the calling method
        // including shortcode, block or page instances.

        self::$label = _('Hello World');
        self::$description = _('A simple Hello World service for demonstration purposes');
        self::$category = _('example');
        self::$icon = 'smiley';
        self::$keywords = ['hello', 'example', 'demo'];
        self::$uri = '/hello';

        // Set default values for dynamic content
        self::$defaultTitle = _('Hello World');
        self::$defaultContent = _('This is a demonstration of the service system.');

        self::$initialized = true; // Mark as initialized
    }

    /**
     * Return sevice provides configuration
     */
    public static function provides(): array {
        if(!empty(self::$provides)) {
            return self::$provides;
        }
        self::init();

        // Service provides configuration - dynamic values set here
        self::$provides = [
            'block' => true,
            'shortcode' => 'hello',
            'uri' => self::$uri,
            'menu' => [
                'label' => _('Hello World'),
                'uri' => self::$uri,
                'icon' => self::$icon,
                'order' => 10,
                'enabled' => true,
            ],
            'api' => true,
            'page' => [
                'title' => _('Hello World'),
                'uri' => self::$uri,
                'template' => 'page', // Use generic page template
                'meta_description' => _('Hello World example page')
            ],
        ];

        return self::$provides;
    }

    /**
     * Main render method - single source of truth
     * Always returns HTML via view() - no hardcoded HTML!
     */
    public function render(array $options = []): string {
        $show_title = $options['show_title'] ?? $this->show_title ?? true;
        // Merge instance options with render-time options (render options take precedence)
        $title = $show_title ? ( $options['title'] ?? $this->title ?? '') : '';
        $content = $options['content'] ?? $this->content ?? _('This is a demonstration of the AGNSTK service system.');
        $attributes = array_merge($this->attributes, $options['attributes'] ?? []);

        $debug = $options; unset($debug['content']);
        $debug['show_title'] = ($this->show_title ?? $options['show_title'] ?? true) ? 'true' : 'false';
        $debug_msg = "[DEBUG] HelloService::render options: " . print_r($debug, true);
        error_log($debug_msg);
        $content .= "\n<pre>" . $debug_msg . "</pre>"; // Add debug info to content
        
        $data = array_filter([
            'title' => $title,
            'content' => $content,
            'platform' => $this->getCurrentPlatform(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'attributes' => $attributes, // Pass attributes to view
        ]);
        
        return view('components.block', $data)->render();
    }

    /**
     * Get the service title for pages
     */
    public function getTitle(): ?string {
        return $this->title;
    }
    
    /**
     * Set the service title
     */
    public function setTitle(?string $title): self {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Set content
     */
    public function setContent(?string $content): self {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Set attributes (CSS classes, etc.)
     */
    public function setAttributes(array $attributes): self {
        $this->attributes = $attributes;
        return $this;
    }
    
    /**
     * Get raw data for API endpoints
     */
    public function toArray(): array {
        return [
            'title' => $this->title,
            'content' => $this->content ?? _('This is a demonstration of the AGNSTK service system.'),
            'features' => array_keys(self::$provides),
        ];
    }

    /**
     * Page configuration for routing from object properties
     */
    public function getPageConfig(): array {
        return self::$provides['page'] ?? [];
    }

    /**
     * Menu configuration from object properties
     */
    public function getMenuConfig(): array {
        return self::$provides['menu'] ?? [];
    }

    /**
     * API endpoint data  from object properties
     */
    public function getApiData(): array {
        return $this->toArray();
    }

    /**
     * WRONG!
     * This is a generic feature, it has to be in helpers or boostrap
     * or anywhere else than in a specific service.
     */
    private function getCurrentPlatform(): string {
        // Ultimately, platform will be set by the adapter, this is temporary
        if (defined('WP_VERSION')) return 'WordPress';
        if (defined('DRUPAL_VERSION')) return 'Drupal';  
        if (class_exists('October\Rain\Foundation\Application')) return 'October CMS';
        return config('app.platform', 'Standalone'); // Default to Standalone
    }
}
