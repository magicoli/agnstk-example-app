<?php

namespace App\Traits;

trait RenderableTrait {
    
    /**
     * Post-process rendered content using BlockService
     * This should be called on all rendered content before final output
     */
    protected function postProcessRender(string $content, string $sourceFormat = 'html'): string {
        $blockService = app(\App\Services\BlockService::class);
        return $blockService->postProcessContent($content, $sourceFormat);
    }
    
    /**
     * Default render method that can be overridden by implementing classes
     * Automatically applies post-processing
     */
    public function renderWithPostProcessing(string $content, string $sourceFormat = 'html'): string {
        return $this->postProcessRender($content, $sourceFormat);
    }
}
