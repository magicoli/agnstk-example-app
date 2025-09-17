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

$test->assert_not_empty( config('database'), 'Database config is set');
$dbType = config('database.default');
$test->assert_not_empty( $dbType, "Database default connection type ($dbType)" );

echo PHP_EOL;
echo "Testing database configuration..." . PHP_EOL;
// Access Laravel app through the Agnstk app

echo PHP_EOL;
echo "Testing web accessibility of storage paths..." . PHP_EOL;

$pages = array(
    'storage/' => 403,
    'storage/logs/laravel.log' => 403,
    'storage/database/database.sqlite' => 403,
  );

foreach ($pages as $page => $expected_code) {
    $url = rtrim( getenv('HOME_URL'), '/' ) . '/' . ltrim( $page, '/' );
    $headers = testing_get_headers( $url );
    $response = $headers[0] ?? '';
    $response_code = preg_match( '#HTTP/\d+\.\d+\s+(\d{3})#', $response, $matches ) ? (int)$matches[1] : 0;
    $test->assert_equals($expected_code, $response_code, "Response code for $url ... " );
}    

if(! $test->assert_true( $laravel instanceof \Illuminate\Foundation\Application, 'Valid Laravel application instance')) {
    exit( $test->summary() ? 0 : 1 );
}

echo "Testing database connectivity..." . PHP_EOL;
try {
    // Try to get database connection directly without config
    $db = $laravel->make('db');
    $test->assert_not_empty( $db, 'Database service is available');
    
    $pdo = $db->connection()->getPdo();
    $test->assert_not_empty( $pdo, 'Database connection established');
} catch (Exception $e) {
    $test->assert_false( true, 'Database connection failed: ' . $e->getMessage());
    exit($test->summary() ? 0 : 1);
}

echo "Testing DB object functionality..." . PHP_EOL;
$test->assert_true( class_exists('Illuminate\Support\Facades\DB'), 'DB facade class exists');
$test->assert_not_empty( DB::getFacadeRoot(), 'DB facade root is available');
$test->assert_not_empty( DB::connection(), 'DB connection is available');
$test->assert_not_empty( DB::connection()->getPdo(), 'DB PDO instance is available');

echo "Testing main tables..." . PHP_EOL;
$tables = array(
    'migrations',
    'users',
    'cache',
);
foreach( $tables as $table ) {
    try {
        $count = DB::table($table)->count();
        $test->assert_true( is_numeric($count), "Table '$table' exists (count: $count)");
    } catch (Exception $e) {
        $test->assert_false( true, "Table '$table' check failed: " . $e->getMessage());
    }
}

# Make sure to end with summary and proper exit code
exit( $test->summary() ? 0 : 1 );
