<?php

namespace YourApp\Services;

use App\Services\BaseService;

/**
 * HelloService - Simple example service
 * 
 * This demonstrates the minimal code needed to create an AGNSTK service.
 * New developers should use this as a template for their own services.
 */
class HelloService extends BaseService {
    /**
     * Initialize service configuration
     */
    public static function init(): void {
        if (static::$initialized) {
            return;
        }

        static::$label = _('Hello World');
        static::$description = _('A simple Hello World service for demonstration purposes');
        static::$category = _('example');
        static::$icon = 'smiley';
        static::$keywords = ['hello', 'example', 'demo'];
        static::$uri = '/hello';

        static::$defaultTitle = _('Hello World');
        static::$defaultContent = _('This is a demonstration of the AGNSTK service system.');

        parent::init();
    }

    /**
     * Define what this service provides
     */
    public static function provides(): array {
        if (!empty(static::$provides)) {
            return static::$provides;
        }

        static::init();

        static::$provides = [
            'block' => true,
            'shortcode' => 'hello',
            'uri' => static::$uri,
            'menu' => [
                'label' => _('Hello World'),
                'uri' => static::$uri,
                'icon' => static::$icon,
                'order' => 10,
                'enabled' => true,
            ],
            'api' => true,
            'page' => [
                'title' => _('Hello World'),
                'uri' => static::$uri,
                'template' => 'page',
                'meta_description' => _('Hello World example page')
            ],
        ];

        return static::$provides;
    }

    /**
     * Main render method - the core service functionality
     * This is all that's required for a basic service
     */
    public function render(array $options = []): array {
        $content = $options['content'] ?? $this->content ?? _('This is a demonstration of the AGNSTK service system.');
        $attributes = array_merge($this->attributes, $options['attributes'] ?? []);
        
        return [
            'title' => $this->title,
            'content' => $content,
            'platform' => $this->getCurrentPlatform(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'attributes' => $attributes,
        ];
    }
}
