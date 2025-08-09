<?php

namespace App\Http\Controllers;

use App\Services\PageService;
use Illuminate\Http\Request;

class PageController extends Controller {
    private PageService $pageService;
    private ?array $page;
    private array $pageConfig;
    private string $slug = '';
    private string $title = '';
    private string $content = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(string $slug = null) {
        $this->slug = (string)($slug ?? ''); // Allow empty slug for normal Laravel instantiation

        $this->pageService = new PageService();
        
        // Only get page configuration if we have a valid slug
        if ($this->slug) {
            $this->pageConfig = $this->pageService->getConfig($this->slug);
            
            // Check if page exists and is enabled
            $pages = config('pages', []);
            $this->page = $pages[$this->slug] ?? null;
            if (($this->page['auth_required'] ?? false) && !auth()->check()) {
                $this->middleware('auth');
            }
        } else {
            // Default empty values for when constructor is called without slug
            $this->pageConfig = [];
            $this->page = null;
        }
    }

    private function getInstance(string $slug = null): self {
        // Strategy 1: Use provided slug parameter
        if ($slug) {
            return $slug === $this->slug ? $this : new self($slug);
        }
        
        // Strategy 2: Use this instance if it already has a slug
        if ($this->slug) {
            return $this;
        }
        
        // Strategy 3: Get slug from route parameter
        $routeSlug = request()->route()->parameter('slug');
        if ($routeSlug) {
            return new self($routeSlug);
        }
        
        // No valid slug found
        \Log::error('PageController::getInstance - No page slug available');
        abort(500, 'No page slug provided');
    }

    /**
     * Display a page based on its configuration
     */
    public function show(string $slug = null) {
        // Use unified getInstance strategy to get the appropriate controller
        $pageCtrl = $this->getInstance($slug);

        // Debug: Let's see exactly what Laravel is giving us
        \Log::info('PageController::show - Laravel route parameters debug', [
            'method_param_slug' => $slug,
            'resolved_slug' => $pageCtrl->slug,
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
    
    /**
     * Display the home page
     */
    public function home() {
        // Home page typically maps to the 'about' page or first enabled page
        $pages = config('pages', []);
        $homeSlug = 'about'; // Default home page slug
        
        // Find first page with uri '/' or use 'about'  
        foreach ($pages as $slug => $page) {
            if (($page['uri'] ?? '') === '/') {
                $homeSlug = $slug;
                break;
            }
        }
        
        return $this->show($homeSlug);
    }

}
