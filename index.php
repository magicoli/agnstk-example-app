<?php

/**
 * AGNSTK - Agnostic Glue for Non-Specific ToolKits
 * 
 * This is the standalone entry point for the AGNSTK Laravel application.
 * It allows the app to be accessed directly from the project root.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/core/vendor/autoload.php';

// Also load application autoloader if it exists
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
}

$request = Request::capture();

// Detect base URL from the current request for proper asset handling

// Auto-detect the base URL and path
$scheme = $request->getScheme();
$host = $request->getHttpHost();
$scriptName = $request->getScriptName();

// Extract the base path (handles subdirectory installations like /agnstk/)
$basePath = dirname($scriptName);
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

// Build the complete base URL
$baseUrl = $scheme . '://' . $host . $basePath;
$assetUrl = $baseUrl . '/core/public';

// Set environment variables for Laravel configuration
putenv("APP_URL=" . $baseUrl);
putenv("ASSET_URL=" . $assetUrl);
putenv("APP_ROOT=" . __DIR__);

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/core/bootstrap/app.php';

// Configure Laravel after bootstrapping
$app->booted(function ($app) use ($baseUrl, $assetUrl) {
    // Set the application URL
    $app['config']->set('app.url', $baseUrl);
    // Set the asset URL for proper asset() helper behavior
    $app['config']->set('app.asset_url', $assetUrl);
});

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/core/storage/framework/maintenance.php')) {
    require $maintenance;
}

$app->handleRequest($request);
