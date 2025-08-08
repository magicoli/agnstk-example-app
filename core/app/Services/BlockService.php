<?php

namespace App\Services;

class BlockService {
    
    /**
     * Create a block from various sources
     * This method detects source type and initializes the appropriate block
     */
    public function createBlock($source, array $options = []) {
        // If source is a string, detect what kind of source it is
        if (is_string($source)) {
            return $this->createBlockFromSource($source, $options);
        }
        
        // If source is a service class name
        if (is_string($source) && class_exists($source)) {
            return new $source($options);
        }
        
        // If source is already a block instance
        if (is_object($source)) {
            return $source;
        }
        
        throw new \InvalidArgumentException("Invalid block source provided");
    }
    
    /**
     * Create block from various source formats (content, file, etc.)
     */
    protected function createBlockFromSource(string $source, array $options = []) {
        // Detect if it's a file path
        if (file_exists($source)) {
            return $this->createBlockFromFile($source, $options);
        }
        
        // Detect if it's direct content
        return $this->createBlockFromContent($source, $options);
    }
    
    /**
     * Create block from file
     */
    protected function createBlockFromFile(string $filePath, array $options = []) {
        $content = file_get_contents($filePath);
        $sourceFormat = $this->detectSourceFormat($content, $filePath);
        
        return $this->createContentBlock($content, $sourceFormat, $options);
    }
    
    /**
     * Create block from direct content
     */
    protected function createBlockFromContent(string $content, array $options = []) {
        $sourceFormat = $this->detectSourceFormat($content);
        return $this->createContentBlock($content, $sourceFormat, $options);
    }
    
    /**
     * Create a generic content block
     */
    protected function createContentBlock(string $content, string $sourceFormat, array $options = []) {
        return new class($content, $sourceFormat, $options) {
            use \App\Traits\RenderableTrait;
            
            protected $content;
            protected $sourceFormat;
            protected $options;
            public $title;
            public $id;
            
            public function __construct($content, $sourceFormat, $options = []) {
                $this->content = $content;
                $this->sourceFormat = $sourceFormat;
                $this->options = $options;
                $this->title = $options['title'] ?? null;
                $this->id = $options['id'] ?? null;
            }
            
            public function render(): string {
                $blockService = app(\App\Services\BlockService::class);
                $processedContent = $blockService->postProcessContent($this->content, $this->sourceFormat);
                
                // Use the standard block component
                return view('components.block', [
                    'title' => $this->title,
                    'content' => $processedContent,
                    'attributes' => $this->options['attributes'] ?? [],
                ])->render();
            }
        };
    }
    
    /**
     * Detect source format from content or file extension
     */
    protected function detectSourceFormat(string $content, string $filePath = null): string {
        // Check file extension first if available
        if ($filePath) {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($extension === 'md') return 'markdown';
            if (in_array($extension, ['html', 'htm'])) return 'html';
        }
        
        // Fallback to content analysis
        if (preg_match('/^#\s+/m', $content) || // Headers
            preg_match('/\*\*[^*]+\*\*/', $content) || // Bold
            preg_match('/\*[^*]+\*/', $content) || // Italic
            preg_match('/```/', $content) || // Code blocks
            preg_match('/\n\s*\*\s+/', $content)) { // Lists
            return 'markdown';
        }
        
        return 'html';
    }
    
    /**
     * General post-processing method for all block content
     * This method should be called on all rendered content before final output
     */
    public function postProcessContent(string $content, string $sourceFormat = 'html'): string {
        // Convert markdown to HTML if needed
        if ($sourceFormat === 'markdown') {
            $content = $this->convertMarkdownToHtml($content);
        }
        
        // Apply shortcode processing
        $content = $this->processShortcodes($content, $sourceFormat);
        
        // Apply any other post-processing here (future extensibility)
        // e.g., $content = $this->processInternalLinks($content);
        // e.g., $content = $this->processImageOptimization($content);
        
        return $content;
    }
    
    /**
     * Convert markdown to HTML
     */
    protected function convertMarkdownToHtml(string $markdown): string {
        $converter = new \League\CommonMark\CommonMarkConverter();
        return $converter->convert($markdown)->getContent();
    }
    
    /**
     * Process shortcodes in content
     * Delegates to ShortcodeService for actual processing
     */
    protected function processShortcodes(string $content, string $sourceFormat = 'html'): string {
        $shortcodeService = app(\App\Services\ShortcodeService::class);
        return $shortcodeService->processShortcodes($content, $sourceFormat);
    }
}