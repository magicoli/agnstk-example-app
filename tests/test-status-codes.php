<?php
/**
 * Test HTTP status codes for various pages
 */

require_once __DIR__ . '/bootstrap.php';

$pages = array(
    '/' => 200,
    'callback-page' => 200,
    'developers' => 200,
    'static-page' => 200,
    'markdown-page' => 200,
    'non-existent' => 404,
    'non-defined' => 404,
    'user/dashboard' => 302, // Redirect to login
    'login' => 200,
    'register' => 200,
    'password/reset' => 200, // Password reset request form
    '.env' => 403,
    'config/' => 403,
  );

foreach ($pages as $page => $expected_code) {
    $url = rtrim( getenv('HOME_URL'), '/' ) . '/' . ltrim( $page, '/' );
    $headers = testing_get_headers( $url );
    $response = $headers[0] ?? '';
    $response_code = preg_match( '#HTTP/\d+\.\d+\s+(\d{3})#', $response, $matches ) ? (int)$matches[1] : 0;
    $test->assert_equals($expected_code, $response_code, "Testing $url ... " );
}    

exit( $test->summary() ? 0 : 1 );
