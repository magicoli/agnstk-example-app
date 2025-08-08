<?php

namespace App\Http\Controllers;

use App\Services\PageService;
use Illuminate\Http\Request;

class PageController extends Controller {
    private PageService $pageService;
    private ?array $page;
    private array $pageConfig;
    private string $pageId = '';
    private string $title = '';
    private string $content = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(string $pageId = null) {
        $this->pageId = (string)($pageId ?? ''); // Allow empty pageId for normal Laravel instantiation

        $this->pageService = new PageService();
        
        // Only get page configuration if we have a valid ID
        if ($this->pageId) {
            $this->pageConfig = $this->pageService->getPageConfiguration($this->pageId);
            
            // Check if page exists and is enabled
            $pages = config('pages', []);
            $this->page = $pages[$this->pageId] ?? null;
            if (($this->page['auth_required'] ?? false) && !auth()->check()) {
                $this->middleware('auth');
            }
        } else {
            // Default empty values for when constructor is called without pageId
            $this->pageConfig = [];
            $this->page = null;
        }
    }

    private function getInstance(string $pageId = null): self {
        // Strategy 1: Use provided pageId parameter
        if ($pageId) {
            return $pageId === $this->pageId ? $this : new self($pageId);
        }
        
        // Strategy 2: Use this instance if it already has a pageId
        if ($this->pageId) {
            return $this;
        }
        
        // Strategy 3: Get pageId from route parameter
        $routePageId = request()->route()->parameter('pageId');
        if ($routePageId) {
            return new self($routePageId);
        }
        
        // No valid pageId found
        \Log::error('PageController::getInstance - No page ID available');
        abort(500, 'No page ID provided');
    }

    /**
     * Display a page based on its configuration
     */
    public function show(string $pageId = null) {
        // Use unified getInstance strategy to get the appropriate controller
        $pageCtrl = $this->getInstance($pageId);

        // Debug: Let's see exactly what Laravel is giving us
        \Log::info('PageController::show - Laravel route parameters debug', [
            'method_param_pageId' => $pageId,
            'resolved_pageId' => $pageCtrl->pageId,
            'route_parameters' => request()->route()->parameters(),
            'route_defaults' => request()->route()->defaults,
        ]);

        // Check authentication if required
        if (($pageCtrl->page['auth_required'] ?? false) && !auth()->check()) {
            return redirect()->route('login');
        }

        // Render content using the content block if available
        if ($pageCtrl->pageConfig['contentBlock']) {
            // Don't show content title in block if it matches page title
            if (!$pageCtrl->pageConfig['showContentTitle']) {
                $pageCtrl->pageConfig['contentBlock']->title = null;
            }
            $pageCtrl->content = $pageCtrl->pageConfig['contentBlock']->render();
        } elseif ($pageCtrl->pageConfig['content']) {
            // Fallback to direct content rendering
            $pageCtrl->content = $pageCtrl->pageService->renderPageContent($pageCtrl->pageConfig['content']);
        }
        
        return view('page', [
            'title' => $pageCtrl->pageConfig['title'],
            'content' => $pageCtrl->content,
        ]);
    }

}
