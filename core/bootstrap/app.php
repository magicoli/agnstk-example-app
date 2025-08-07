<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$corePath = dirname(__DIR__);

// Ensure bundle.php config exists (copy from example if needed)
$bundleConfig = $corePath . '/config/bundle.php';
$bundleExample = $corePath . '/config/bundle.example.php';
if (!file_exists($bundleConfig) && file_exists($bundleExample)) {
    copy($bundleExample, $bundleConfig);
}

// Load application autoloader if it exists (for all deployment targets)
// Read bundle config directly since Laravel config system isn't available yet
$appRoot = dirname(__DIR__, 2); // Default fallback
if (file_exists($bundleConfig)) {
    $bundleSettings = require $bundleConfig;
    $appRoot = $bundleSettings['app_root'] ?? $appRoot;
}

// Only load application autoloader if app root is different from core root
if (realpath($appRoot) !== realpath($corePath)) {
    $appAutoloader = $appRoot . '/vendor/autoload.php';
    if (file_exists($appAutoloader)) {
        require $appAutoloader;
    }
}

return Application::configure(basePath: $corePath)
    ->withRouting(
        web: $corePath . '/routes/web.php',
        commands: $corePath . '/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
