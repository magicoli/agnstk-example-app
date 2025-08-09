<?php

namespace App\Services;

class BlockService {
    use \App\Traits\RenderableTrait;
    
    protected $content;
    protected $source;
    protected $sourceFormat;
    protected $options;
    public $title;
    public $id;

    
    public function __construct($args = []) {
    }
    
    public function render(): string {
        // $args = func_get_args();
        // error_log("DEBUG: Rendering block with args: " . json_encode($args));
        // PREPROCESSING: Convert markdown to HTML if needed
        $preprocessed = $this->preprocessContent();
        
        // Use the standard block component
        $view = view('components.block', [
        // $viewHtml = view('components.block', [
            'title' => $this->title,
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
     */
    public function create($source, array $options = []) {
        $this->source = $source ?? null;
        $this->options = $options ?? [];
        
        // If source is a service class name
        if (is_string($source) && class_exists($source)) {
            error_log("DEBUG source is service class: " . $source);
            return new $source($options);
        }
        
        // If source is a string, detect what kind of source it is
        if (is_string($source)) {
            error_log("DEBUG source is string: " . substr($source, 0, 50) . (strlen($source) > 50 ? '...' : ''));
            return $this->setBlockFromSource();
        }

        // If source is already a block instance
        if (is_object($source)) {
            error_log("DEBUG source is object: " . get_class($source));
            return $source;
        }
        
        throw new \InvalidArgumentException("Invalid block source provided");
    }

    /**
     * Create content block from different source types
     * This method handles all the content source logic that was previously in PageService
     */
    public function createFromContentSource(array $contentSource, array $options = []): ?self {
        switch ($contentSource['type']) {
            case 'content':
                // Direct HTML/text content
                return $this->create($contentSource['data'], $options);
                
            case 'source':
                // File source (markdown, etc.)
                $filePath = resolve_file_path($contentSource['data']);
                if (file_exists($filePath)) {
                    return $this->create($filePath, $options);
                }
                \Log::warning("Source file not found: {$contentSource['data']} (resolved to: {$filePath})");
                return null;
                
            case 'callback':
                // Service callback
                return $this->createFromCallback($contentSource['data'], $options);
                
            case 'view':
                // Laravel view
                return $this->createFromView($contentSource['data'], $options);
                
            default:
                return null;
        }
    }

    /**
     * Create a block from service callback
     */
    public function createFromCallback(string $callback, array $options = []): ?self {
        error_log("[DEBUG] Creating callback block for: {$callback}");
        
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
            
            error_log("[DEBUG] Resolved service class: {$serviceClass}");
            
            // Resolve service instance
            $service = app($serviceClass);
            
            if (!method_exists($service, $method)) {
                \Log::error("Method {$method} not found on {$serviceClass}");
                return null;
            }
            
            // Call the method and get content
            $content = $service->$method();
            error_log("[DEBUG] Service method output length: " . strlen($content));
            
            // Create block with the rendered content
            $resultBlock = $this->create($content, $options);
            error_log("[DEBUG] Created block: " . (is_object($resultBlock) ? get_class($resultBlock) : 'null'));
            
            return $resultBlock;
            
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
            $content = view($viewName, $viewData)->render();
            
            // Create block with the rendered content
            return $this->create($content, $options);
            
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

    /**
     * Get block configuration/properties
     */
    public function getConfig(): array {
        return [
            'title' => $this->title,
            'id' => $this->id,
            'source' => $this->source,
            'sourceFormat' => $this->sourceFormat,
            'options' => $this->options,
        ];
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

        // Set properties from options
        $this->title = $this->options['title'] ?? null;
        $this->id = $this->options['id'] ?? null;

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
        
        // Add other preprocessing steps here (future extensibility)
        // e.g., $content = $this->preprocessTemplateVariables($content);
        
        return $content;
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
}
