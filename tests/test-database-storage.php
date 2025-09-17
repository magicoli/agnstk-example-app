<?php
/**
 * Test database storage configuration and accessibility
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Database Storage Configuration Test ===" . PHP_EOL;

$test->assert_not_empty( $app, 'Application instance is available' );
$laravel = $app->getLaravel();
$test->assert_not_empty( $laravel, 'Laravel application is available');
// We need to handle a request to ensure all services are loaded
// Intercept output to reduce noise
ob_start();
$app->handleRequest();
ob_end_clean();

$test->assert_directory( $bundleConfig['app_root'] ?? null, 'Main app root path');
$test->assert_directory( base_path(), 'Core framework base path');
$test->assert_directory( config_path(), 'Config path');
$test->assert_directory( storage_path(), 'Storage path');
$test->assert_directory( database_path(), 'Database path');

$test->assert_not_empty( config('database'), 'Database default connection is set');

echo PHP_EOL;
echo "Testing database configuration..." . PHP_EOL;
// Access Laravel app through the Agnstk app

if ($laravel) {
    echo "Testing database connectivity..." . PHP_EOL;
    try {
        // Try to get database connection directly without config
        $db = $laravel->make('db');
        $test->assert_not_empty( $db, 'Database service is available');
        
        $pdo = $db->connection()->getPdo();
        $test->assert_not_empty( $pdo, 'Database connection established');
    } catch (Exception $e) {
        $test->assert_false( true, 'Database connection failed: ' . $e->getMessage());
    }
}

# Make sure to end with summary and proper exit code
exit( $test->summary() ? 0 : 1 );
