<?php

namespace App\Services;

use Illuminate\Support\Str;

class BlockService {
    use \App\Traits\RenderableTrait;
    
    protected $content;
    protected $source;
    protected $sourceFormat;
    protected $options;
    protected $view; // View name for rendering
    protected $callback; // Callback for dynamic content rendering
    // Instance properties
    protected $viewHtml; // Rendered HTML from view
    protected $attributes = []; // For CSS classes and other HTML attributes
    protected $show_title = true; // Whether to show the title in the block
    public $title;
    public $id;        // HTML id attribute (instance-specific, like "main-content", "sidebar-1", "footer")
    public $slug;      // Block type slug (semantic, like "hello", "example-block")

    /**
     * Create a block from various content sources
     * This is the main public method for creating blocks from different sources
     * 
     * @param array $options properties for the block (source, content, view, id, title, etc.)
     */
    public function __construct($options = []) {
        $this->id = $this->options['id'] ?? null;
        $this->options = $options ?? [];
        $this->title = $this->options['title'] ?? null;
        $this->content = $this->options['content'] ?? null;
        $this->view = $this->options['view'] ?? null;
        $this->source = $this->options['source'] ?? null;
        $this->callback = $this->options['callback'] ?? null;
        $this->show_title = $this->options['show_title'] ?? true; // Whether to show the title in the block
        $this->attributes = $this->options['attributes'] ?? []; // Will be completed later
    }
    
    public function render(): string {
        // PREPROCESSING: Convert markdown to HTML if needed
        $preprocessed = $this->preprocessContent();
        
        $debug = [
            '$this->show_title' => $this->show_title ? 'true' : 'false',
            '$this->options[\'show_title\']' => ($this->options['show_title'] ?? true) ? 'true' : 'false',
        ];
        $preprocessed .= "\n<pre>DEBUG: " . __METHOD__ . print_r($debug, true) . "</pre>";
        // Use the standard block component
        $view = view('components.block', [
        // $viewHtml = view('components.block', [
            'title' => $this->show_title ? $this->title : '',
            'content' => $preprocessed,
            'attributes' => $this->options['attributes'] ?? [],
        ])->render();

        // POST-PROCESSING: Apply shortcodes and other HTML adjustments
        // Pass the original source format so shortcodes use the right syntax
        $postprocessed = $this->postProcessContent($view);
        // $postprocessed = $this->postProcessContent($viewHtml);

        return $postprocessed;
    }
    
    /**
     * Create a block from various content sources
     * This is the main public method for creating blocks from different sources
     * 
     * @param string $slug Block slug (type identifier)
     * @param array $options Additional properties for the block (source, content, view, id, title, etc.)
     */
    /**
     * Create a block with comprehensive configuration
     * Accepts semantic parameters: content, source, callback, view, slug, id, title
     * BlockService handles all logic including slug determination
     * 
     * @param array $options All block configuration including semantic content parameters
     * @return self
     */
    public function create($options = []) {
        // Determine block slug from various sources
        $this->id = ($options['id'] ?? $this->slug) . '-block';
        $this->title = $options['title'] ?? null;
        $this->options = $options;
        
        $this->slug = $options['slug'] ?? null;

        // Handle semantic content parameters
        if (isset($options['content'])) {
            // Direct HTML/text content
            $this->slug = ($this->slug ?: $options['container-id'] ?? 'content') . '-block';
            $this->content = $options['content'];
                        // Extract title from HTML content if not already set
            $this->preprocessContent();

            return $this;
        }
        
        if (isset($options['source'])) {
            // File source (markdown, etc.)
            $filePath = resolve_file_path($options['source']);
            if (file_exists($filePath)) {
                $this->slug = Str::slug(pathinfo($options['source'], PATHINFO_FILENAME)) . '-source';
                $this->content = file_get_contents($filePath);
                
                $this->preprocessContent();
                
                return $this;
            }
            \Log::warning("Source file not found: {$options['source']} (resolved to: {$filePath})");
            return $this;
        }
        
        if (isset($options['callback'])) {
            [$serviceClass] = explode('@', $options['callback'], 2);
            $options['slug'] = $this->slug ?: Str::slug(str_replace('Service', '-callback', class_basename($serviceClass)));
            // Service callback
            return $this->createFromCallback($options['callback'], $options);
        }
        
        if (isset($options['view'])) {
            $this->slug = $this->slug ?: $options['view'] . '-view';
            // Laravel view
            return $this->createFromView($options['view'], $options, $options['viewData'] ?? []);
        }
        
        // If no content source specified, create empty block
        $this->content = '';
        return $this;
    }
    
    /**
     * Determine block slug from configuration
     * Moves slug determination logic from PageService to BlockService
     */
    protected function getSlug() {
        return $this->slug;
    }

    /**
     * Create content block from different source types
     * This method handles all the content source logic that was previously in PageService
     */
    public function createFromContentSource(array $contentSource, array $options = [], array $pageConfig = []): ?self {
        // Build comprehensive options by combining content source with existing options
        $allOptions = array_merge($options, [
            $contentSource['type'] => $contentSource['data'],
            'pageConfig' => $pageConfig,
        ]);
        
        // Let the main create() method handle everything
        return $this->create($allOptions);
    }

    /**
     * Create a block from service callback
     */
    public function createFromCallback(string $callback, array $options = []): ?self {
        
        // Parse callback string like "HelloService@render"
        [$serviceClass, $method] = explode('@', $callback, 2);
        
        try {
            // Try to resolve with full namespace first
            if (!class_exists($serviceClass)) {
                // Try with YourApp namespace (our service namespace)
                $namespacedClass = "YourApp\\Services\\{$serviceClass}";
                if (class_exists($namespacedClass)) {
                    $serviceClass = $namespacedClass;
                }
            }
            
            // Resolve service instance
            $service = app($serviceClass);
            
            if (!method_exists($service, $method)) {
                \Log::error("Method {$method} not found on {$serviceClass}");
                return null;
            }
            
            // Call the method and get content
            $content = $service->$method();
            
            // Get title from service if available and pass it in options
            if (method_exists($service, 'getTitle')) {
                $options['title'] = $service->getTitle();
            }
            $this->preprocessContent();
            $options['content'] = $content; // Set content in options
            // Create block with the rendered content
            $options = array_merge($this->options, $options); // Merge with existing options
            if($options['title'] === $options['container-title']) {
                $options['show_title'] = false; // Hide title if it matches container title
            // } else {
            //     $options['hide_title'] = false; // Show title if it doesn't match
            }
            // DEBUG
            $debug = $options; unset($debug['content']);
            $debug['show_title'] = $options['show_title'] ? 'true' : 'false';
            error_log("[DEBUG] BlockService::createFromCallback - options: " . print_r($debug, true));
            $options['content'] .= "\n<pre>DEBUG: " . __METHOD__ . print_r($debug, true) . "</pre>"; // Add debug info to content
            return $this->create($options);
            
        } catch (\Exception $e) {
            \Log::error("Error calling {$callback}: " . $e->getMessage());
            error_log("[ERROR] Exception in createFromCallback: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a block from Laravel view
     */
    public function createFromView(string $viewName, array $options = [], array $viewData = []): ?self {
        try {
            // Check if view exists
            if (!view()->exists($viewName)) {
                \Log::warning("View {$viewName} not found, falling back to default");
                return null;
            }
            
            // Render view with provided data
            $this->content = view($viewName, $viewData)->render();
            $options['content'] = $this->content;
            $options['title'] = 'createFromView ' . $this->extractTitle($content); // Extract title from content if available
            
            // Create block with the rendered content
            $options = array_merge($this->options, $options); // Merge with existing options
            return $this->create($options);
            
        } catch (\Exception $e) {
            \Log::error("Error rendering view {$viewName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the block title
     */
    public function getTitle(): ?string {
        return $this->title;
    }

    public function hideTitle() {
        $this->show_title = false;
    }

    /**
     * Get block configuration/properties
     */
    public function getConfig(): array {
        return [
            'title' => $this->title,
            'id' => $this->id,
            'slug' => $this->slug,
            'source' => $this->source,
            'sourceFormat' => $this->sourceFormat,
            'options' => $this->options,
        ];
    }

    /**
     * Determine if content title should be shown in the block
     */
    public function shouldShowContentTitle(?string $pageTitle): bool {
        if (!$this->title || !$pageTitle) {
            return true; // Show content title if we have one and no page title
        }
        
        // Hide content title if it's the same as page title
        return $pageTitle !== $this->title;
    }
    
    /**
     * Create block from various source formats (content, file, etc.)
     */
    protected function setBlockFromSource() {
        // Detect if it's a file path
        if (file_exists($this->source)) {
            $this->content = file_get_contents($this->source);
        } else {
            // Treat source as direct content
            $this->content = $this->source;
        }


        return $this;
    }

    /**
     * Detect source format from content or file extension
     */
    protected function detectSourceFormat(): string {
        if($this->sourceFormat) {
            return $this->sourceFormat; // Use already set format if available
        }

        // Check file extension first if available
        if ($this->source) {
            $extension = pathinfo($this->source, PATHINFO_EXTENSION);
            if ($extension === 'md') return 'markdown';
            if (in_array($extension, ['html', 'htm'])) return 'html';
        }
        
        $content = $this->content ?? '';
        // Fallback to content analysis
        if (preg_match('/^#\s+/m', $content) || // Headers
            preg_match('/\*\*[^*]+\*\*/', $content) || // Bold
            preg_match('/\*[^*]+\*/', $content) || // Italic
            preg_match('/```/', $content) || // Code blocks
            preg_match('/\n\s*\*\s+/', $content)) { // Lists
            $this->sourceFormat = 'markdown';
        } else {
            $this->sourceFormat = 'html';
        }
        
        return $this->sourceFormat;
    }
    
    /**
     * Preprocessing method for content conversion (markdown to HTML, etc.)
     * This runs before content is rendered and converts source formats to HTML
     */
    public function preprocessContent(string $content = null, string $sourceFormat = null): string {
        $sourceFormat = $sourceFormat ?: $this->detectSourceFormat();
        $content = $content ?? $this->content;

        if(empty($content)) {
            return ''; // Nothing to process
        }

        // Convert markdown to HTML if needed
        if ($sourceFormat === 'markdown') {
            $content = $this->convertMarkdownToHtml($content);
        }

        $this->content = $content; // Update content after conversion

        $this->title = $this->extractTitle();

        // Add other preprocessing steps here (future extensibility)
        // e.g., $content = $this->preprocessTemplateVariables($content);
        
        return $this->content;
    }
    
    /**
     * Post-processing method for HTML content adjustments
     * This method should ONLY be called on already-rendered HTML content
     * and applies final adjustments like shortcodes, dynamic properties, etc.
     */
    public function postProcessContent(string $htmlContent, string $sourceFormat = null): string {
        $sourceFormat = $sourceFormat ?: $this->detectSourceFormat();

        // Apply shortcode processing to HTML content
        $htmlContent = $this->processShortcodes($htmlContent, $sourceFormat);

        // Apply any other post-processing here (future extensibility)
        // e.g., $htmlContent = $this->processInternalLinks($htmlContent);
        // e.g., $htmlContent = $this->processImageOptimization($htmlContent);
        // e.g., $htmlContent = $this->applyDynamicProperties($htmlContent);
        
        return $htmlContent;
    }
    
    /**
     * Convert markdown to HTML
     */
    protected function convertMarkdownToHtml(string $markdown): string {
        if ($this->detectSourceFormat() !== 'markdown') {
            return (string)$markdown; // No conversion needed
        }

        $converter = new \League\CommonMark\CommonMarkConverter();
        return $converter->convert($markdown)->getContent();
    }
    
    /**
     * Process shortcodes in content
     * Delegates to ShortcodeService for actual processing
     */
    protected function processShortcodes(string $content = null, string $sourceFormat = null): string {
        $content = $content ?? $this->content ?: '';
        $sourceFormat = $sourceFormat ?: $this->detectSourceFormat($content);

        $shortcodeService = app(\App\Services\ShortcodeService::class);
        return $shortcodeService->processShortcodes($content, $sourceFormat);
    }
    
    // /**
    //  * Extract title from markdown content (first # header)
    //  */
    // protected function extractTitleFromMarkdown(string $markdown): ?string {
    //     if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
    //         return trim($matches[1]);
    //     }
    //     return null;
    // }
    
    // /**
    //  * Extract title from HTML content (first h1 tag)
    //  */
    // protected function extractTitleFromHtml(string $html): ?string {
    //     if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
    //         return strip_tags($matches[1]);
    //     }
    //     return null;
    // }

    protected function extractTitle($content = null): ?string {
        $content = $content ?? $this->content;
        $title = $this->title ?? $this->getTitle() ?: null;

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $this->content, $matches)) {
            $title = strip_tags($matches[1]);
            $content = str_replace($matches[1], '', $this->content); // Remove title from content
        }

        // Update the title and content properties
        $this->content = $content;
        $this->title = $title;
        return $this->title;
    }



}
