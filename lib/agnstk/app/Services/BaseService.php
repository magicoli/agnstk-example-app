<?php

namespace App\Services;

use Illuminate\Contracts\Support\Renderable;
use App\Traits\RenderableTrait;

/**
 * Base service class providing common functionality for all AGNSTK services
 * 
 * This class handles all the boilerplate code so child services can focus
 * on their core functionality by just implementing render() and $provides.
 */
abstract class BaseService implements Renderable {
    use RenderableTrait;

    /**
     * Service configuration for editors and automation
     */
    protected static $initialized = false;
    protected static $label;
    protected static $description;
    protected static $category;
    protected static $icon;
    protected static $keywords;
    protected static $uri;
    protected static $defaultTitle;
    protected static $defaultContent;

    /**
     * What this service provides - must be defined in child classes
     * This tells AGNSTK what features to enable automatically
     */
    protected static array $provides = [];

    /**
     * Instance properties
     */
    protected string $title;
    protected string $content;
    protected array $data = [];
    protected array $attributes = [];

    public function __construct(array $options = []) {
        // Initialize static properties
        static::init();
        
        // Set instance properties from options
        $this->title = $options['title'] ?? static::$defaultTitle ?? '';
        $this->content = $options['content'] ?? static::$defaultContent ?? '';
        $this->attributes = $options['attributes'] ?? [];

        // Ensure provides configuration is available
        static::provides();
    }

    /**
     * Initialize service static properties - override in child classes
     */
    public static function init(): void {
        if (static::$initialized) {
            return;
        }
        
        // Child classes should override this method to set their specific values
        static::$initialized = true;
    }

    /**
     * Return service provides configuration - child classes should override
     */
    public static function provides(): array {
        if (!empty(static::$provides)) {
            return static::$provides;
        }

        // Child classes should override this to define their capabilities
        return static::$provides;
    }

    /**
     * Main render method - must be implemented by child classes
     * Should return array for BlockService compatibility
     */
    abstract public function render(array $options = []): array;

    /**
     * Get the service title
     */
    public function getTitle(): ?string {
        return $this->title;
    }
    
    /**
     * Set the service title
     */
    public function setTitle(?string $title): self {
        $this->title = $title ?? '';
        return $this;
    }
    
    /**
     * Set content
     */
    public function setContent(?string $content): self {
        $this->content = $content ?? '';
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
            'content' => $this->content,
            'features' => array_keys(static::$provides),
        ];
    }

    /**
     * Page configuration for routing from provides configuration
     */
    public function getPageConfig(): array {
        return static::$provides['page'] ?? [];
    }

    /**
     * Menu configuration from provides configuration  
     */
    public function getMenuConfig(): array {
        return static::$provides['menu'] ?? [];
    }

    /**
     * API endpoint data from provides configuration
     */
    public function getApiData(): array {
        return $this->toArray();
    }

    /**
     * Get current platform - utility method
     */
    protected function getCurrentPlatform(): string {
        // Platform detection - ultimately will be set by the adapter
        if (defined('WP_VERSION')) return 'WordPress';
        if (defined('DRUPAL_VERSION')) return 'Drupal';  
        if (class_exists('October\Rain\Foundation\Application')) return 'October CMS';
        return config('app.platform', 'Standalone');
    }
}
