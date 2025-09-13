<?php
/**
 * ExampleApp - Example Application using AGNSTK framework
 * 
 * This is the standalone entry point for the AGNSTK Laravel application.
 * It allows the app to be accessed directly from the project root.
 */

// Define framework root
$agnstkPath = __DIR__ . '/lib/agnstk';

// Load framework autoloader first
require_once $agnstkPath . '/vendor/autoload.php';

// Load app autoloader if it exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load bundle configuration from config file
$bundleConfigPath = __DIR__ . '/config/bundle.php';
if (!file_exists($bundleConfigPath)) {
    throw new RuntimeException('Bundle configuration file not found: ' . $bundleConfigPath);
}
$bundleConfig = require $bundleConfigPath;

$app = new Agnstk\App($bundleConfig);

// $app->handleRequest();
