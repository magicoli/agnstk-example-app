<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Services\PageService;

// Home page
Route::get('/', [PageController::class, 'home'])->name('home');

// Authentication routes
Auth::routes();

// Dynamic page routes based on configuration
$pages = PageService::getEnabledPages();
foreach ($pages as $pageId => $page) {
    $uri = $page['uri'];
    $routeName = $pageId;
    
    // Handle authentication requirement
    if ($page['auth_required'] ?? false) {
        Route::get($uri, [PageController::class, 'show'])
            ->name($routeName)
            ->middleware('auth')
            ->defaults('pageId', $pageId);
    } else {
        Route::get($uri, [PageController::class, 'show'])
            ->name($routeName)
            ->defaults('pageId', $pageId);
    }
} 
