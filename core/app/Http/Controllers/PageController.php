<?php

namespace App\Http\Controllers;

use App\Services\PageService;
use Illuminate\Http\Request;

class PageController extends Controller {
    /**
     * Display a page based on its configuration
     */
    public function show(string $pageId) {
        $page = PageService::getPageConfig($pageId);
        
        // if (!$page || !($page['enabled'] ?? false)) {
        //     response(404);
        // }

        // Check authentication if required
        if (($page['auth_required'] ?? false) && !auth()->check()) {
            return redirect()->route('login');
        }

        $content = PageService::renderPageContent($pageId);
        
        return view('content', [
            'title' => $page['title'],
            'content' => $content,
        ]);
    }

    /**
     * Display the home page
     */
    public function home() {
        $homePageId = config('app.home_page', 'hello');
        return $this->show($homePageId);
    }
}
