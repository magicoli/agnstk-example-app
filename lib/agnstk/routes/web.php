<?php

use Illuminate\Support\Facades\Route;
use Agnstk\Http\Controllers\PageController;
use Agnstk\Services\PageService;

// Home page
Route::get('/', [PageController::class, 'home'])->name('home');

// Authentication routes
Auth::routes();

// Dynamic page routes based on configuration
$pages = PageService::getEnabledPages();
foreach ($pages as $slug => $page) {
    $uri = $page['uri'];
    $routeName = $slug;
    
    // Handle authentication requirement
    if ($page['auth_required'] ?? false) {
        Route::get($uri, [PageController::class, 'show'])
            ->name($routeName)
            ->middleware('auth')
            ->defaults('slug', $slug);
    } else {
        Route::get($uri, [PageController::class, 'show'])
            ->name($routeName)
            ->defaults('slug', $slug);
    }
}
