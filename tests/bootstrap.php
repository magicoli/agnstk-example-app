<?php
/**
 * Bootstrap file for OpenSimulator Helpers testing framework
 * Loads WordPress and sets up the testing environment
 */

// Start session FIRST to prevent warnings
if ( session_status() === PHP_SESSION_NONE ) {
	session_start();
}

try {
    // Load the helpers bootstrap (this defines OPENSIM_ENGINE_PATH)
    require_once dirname( __DIR__ ) . '/bootstrap.php';
} catch ( Exception $e ) {
    echo "ERROR: Exception loading bootstrap.php: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Load .env file if it exists for per-site configuration
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
	$env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($env_lines as $line) {
		if (strpos(trim($line), '#') === 0) {
			continue; // Skip comments
		}
		if (strpos($line, '=') !== false) {
			list($key, $value) = explode('=', $line, 2);
			$key = trim($key);
			$value = trim($value, '"\''); // Remove quotes
			putenv("$key=$value");
			$_ENV[$key] = $value;
		}
	}
	echo "Loaded environment configuration from .env" . PHP_EOL;
}

// Simple test framework
class SimpleTest {
	private $tests_run = 0;
	private $tests_passed = 0;
	private $tests_failed = 0;
	private $failed_tests = array();
	private $last_response_info = null;

	public function assert_true( $condition, $message = '' ) {
		$this->tests_run++;
		if ( $condition ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message;
			echo "✗ FAIL: {$message}" . PHP_EOL;
			return false;
		}
	}

	public function assert_false( $condition, $message = '' ) {
		return $this->assert_true( ! $condition, $message );
	}

	public function assert_equals( $expected, $actual, $message = '' ) {
		$this->tests_run++;
		if ( $expected === $actual ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message} (" . var_export($expected, true) . ")" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$error_details = "(expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")";
			$this->failed_tests[] = $message . " " . $error_details;
			echo "✗ FAIL: {$message} {$error_details}" . PHP_EOL;
			return false;
		}
	}

	public function assert_not_empty( $value, $message = '' ) {
		$this->tests_run++;
		if ( ! empty( $value ) ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message . " (value was empty)";
			echo "✗ FAIL: {$message} (value was empty)" . PHP_EOL;
			return false;
		}
	}

	public function assert_empty( $value, $message = '' ) {
		$this->tests_run++;
		if ( empty( $value ) ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message . " (value was not empty)";
			echo "✗ FAIL: {$message} (value was not empty)" . PHP_EOL;
			return false;
		}
	}

    /**
     * Check if text contains a specific string (case-insensitive)
     * @param string $haystack The text to search in
     * @param string $needle The text to search for
     * @return bool True if found, false otherwise
     */
    public function assert_contains($haystack, $needle, $message = '') {
        $this->tests_run++;
        if (stripos($haystack, $needle) !== false) {
            $this->tests_passed++;
            echo "✓ PASS: {$message}" . PHP_EOL;
            return true;
        } else {
            $this->tests_failed++;
            $this->failed_tests[] = $message . " (did not contain expected string)";
            echo "✗ FAIL: {$message} (did not contain expected string)" . PHP_EOL;
            return false;
        }
    }

    /**
     * Check if a string matches any of the given patterns
     * Supports both string and regex patterns (regex patterns start with '/')
     * 
     * @param string $text The text to search in
     * @param array $patterns Array of patterns (strings or regex patterns starting with '/')
     * @return bool True if any pattern matches
     */
    public function assert_matches_any_pattern($text, $patterns, $message = '') {
        $this->tests_run++;
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '/') === 0) {
                // Regex pattern
                if (preg_match($pattern, $text)) {
                    $this->tests_passed++;
                    echo "✓ PASS: {$message}" . PHP_EOL;
                    return true;
                }
            } else {
                // Simple substring match (case-insensitive)
                if (stripos($text, $pattern) !== false) {
                    $this->tests_passed++;
                    echo "✓ PASS: {$message}" . PHP_EOL;
                    return true;
                }
            }
        }
        $this->tests_failed++;
        $this->failed_tests[] = $message . " (did not match any expected patterns)";
        echo "✗ FAIL: {$message} (did not match any expected patterns)" . PHP_EOL;
        return false;
    }

    public function assert_valid_form( $content, $expected_form_id, $expected_fields, $expected_values) {
        // Parse the HTML to verify it's the initial config step
        $main_content = self::get_main_content($content, true) ?: $content;
        $parsed_html = self::parse_html($main_content);
        if (!$this->assert_true($parsed_html !== false, "  HTML parsed")) {
            return false;
        }

        $xpath = new DOMXPath($parsed_html);

        // Extract and display any error messages from the page
        $error_elements = $xpath->query('//div[contains(@class, "alert-danger")]');
        if (!$this->assert_equals(0, $error_elements->length, "  Error messages")) {
            $errors = array();
            foreach ($error_elements as $error_element) {
                $error_text = trim($error_element->textContent);
                if (!empty($error_text)) {
                    echo "    - " . $error_text . PHP_EOL;
                }
            }
            return false;
        }

        // Check for config_method field with ini_import option
        $form_id = $xpath->query('//form')->item(0)->id;

        if(!$this->assert_equals($expected_form_id, $form_id, "  Form ID")) {
            return false;
        }

        foreach($expected_values as $field_name => $expected_value) {
            $field = $xpath->query(sprintf('//input[@name="%s"] | //select[@name="%s"]', $field_name, $field_name))->item(0);
            if($field === null) {
                continue; // Already reported missing
            }
            $actual_value = '';
            if($field->tagName === 'input') {
                $actual_value = $field->getAttribute('value') ?? '';
            } elseif($field->tagName === 'select') {
                $selected_option = $xpath->query('.//option[@selected]', $field)->item(0);
                if($selected_option) {
                    $actual_value = $selected_option->getAttribute('value') ?? '';
                }
            }
            if(!$this->assert_equals($expected_value, $actual_value, "  Field '$field_name' value")) {
                $invalid[] = $field_name;
            }
        }
        if(!empty($invalid)) {
            echo "   ❌ Field value mismatches: " . implode(', ', $invalid) . ", aborting tests." . PHP_EOL;
            return false;
        }

        $missing = [];
        foreach ($expected_fields as $field_name) {
            $field = $xpath->query(sprintf('//input[@name="%s"] | //select[@name="%s"]', $field_name, $field_name))->item(0);
            if($field === null) {
                $missing[] = $field_name;
                continue;
            }
        }

        if(!$this->assert_true(empty($missing), "  Required fields" . (!empty($missing) ? ' (missing: ' . implode(', ', $missing) . ')' : ''))) {
            echo "   ❌ Missing fields: " . implode(', ', $missing) . ", aborting tests." . PHP_EOL;
            exit($this->summary() ? 0 : 1);
        }

        return true;
    }

    /**
     * Test if path is a valid directory
     */
    public function assert_directory($path, $message = '') {
        $this->tests_run++;
        if (is_dir($path)) {
            $this->tests_passed++;
            echo "✓ PASS: {$message} ({$path})" . PHP_EOL;
            return true;
        } else if (file_exists($path)) {
            $this->tests_failed++;
            $this->failed_tests[] = $message . " (not a directory: $path)";
            echo "✗ FAIL: {$message} (not a directory: $path, it's a file)" . PHP_EOL;
            return false;
        } else {
            $this->tests_failed++;
            $this->failed_tests[] = $message . " (not a directory: $path)";
            echo "✗ FAIL: {$message} (not a directory: $path)" . PHP_EOL;
            return false;
        }
    }

    public function get_stats() {
		return array(
			'run' => $this->tests_run,
			'passed' => $this->tests_passed,
			'failed' => $this->tests_failed,
			'failed_tests' => $this->failed_tests
		);
	}

	public function summary() {
		echo "\n" . str_repeat( '=', 50 ) . PHP_EOL;
		echo "Test Summary:" . PHP_EOL;
		echo "  Total tests: {$this->tests_run}" . PHP_EOL;
		echo "  Passed: {$this->tests_passed}" . PHP_EOL;
		echo "  Failed: {$this->tests_failed}" . PHP_EOL;
		
		if ( $this->tests_failed > 0 ) {
			echo "\nFailed Tests:" . PHP_EOL;
			foreach ( $this->failed_tests as $i => $failed_test ) {
				echo "  " . ($i + 1) . ". {$failed_test}" . PHP_EOL;
			}
			echo "\n  Status: FAILED" . PHP_EOL;
			return false;
		} else {
			echo "  Status: ALL PASSED" . PHP_EOL;
			return true;
		}
	}

    /**
     * Session-aware HTTP client properties
     */
    private $session_cookies = '';
    
    /**
     * Session-aware file_get_contents replacement that maintains cookies between requests
     * @param string $url The URL to fetch
     * @param bool $use_include_path Whether to search the include path (ignored for URLs)
     * @param resource|null $context Stream context for additional options
     * @param int $offset The offset where reading starts (ignored for URLs)
     * @param int|null $length Maximum length to read (ignored for URLs)
     * @return string|false The content or false on failure
     */
    public function get_content($url, $use_include_path = false, $context = null, $offset = 0, $length = null) {
        // For local files, use regular file_get_contents
        if (!filter_var($url, FILTER_VALIDATE_URL) || !php_has('curl')) {
            return file_get_contents($url, $use_include_path, $context, $offset, $length);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OpenSim-Helpers/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects automatically
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers to capture cookies
        
        // Send existing cookies
        if (!empty($this->session_cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->session_cookies);
        }
        
        // Parse context for POST data and headers
        if ($context !== null) {
            $context_options = stream_context_get_options($context);
            if (isset($context_options['http'])) {
                $http_opts = $context_options['http'];
                
                // Handle POST method
                if (isset($http_opts['method']) && strtoupper($http_opts['method']) === 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    if (isset($http_opts['content'])) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $http_opts['content']);
                    }
                }
                
                // Handle custom headers
                if (isset($http_opts['header'])) {
                    $headers = is_array($http_opts['header']) ? $http_opts['header'] : explode("\r\n", $http_opts['header']);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
            }
        }
        
        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return false;
        }
        
        // Split headers and content
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $content = substr($response, $header_size);
        
        // Extract and store cookies for future requests
        $this->extract_and_store_cookies($headers);
        
        // Store response info for debugging
        $this->last_response_info = curl_getinfo($ch);
        
        curl_close($ch);
        return $content;
    }
    
    /**
     * Extract cookies from response headers and store them for future requests
     * @param string $headers The response headers
     */
    private function extract_and_store_cookies($headers) {
        $header_lines = explode("\r\n", $headers);
        $new_cookies = [];
        
        foreach ($header_lines as $header) {
            if (stripos($header, 'Set-Cookie:') === 0) {
                $cookie_part = substr($header, 11); // Remove "Set-Cookie: "
                $cookie_name_value = explode(';', $cookie_part)[0]; // Get just name=value part
                $new_cookies[] = trim($cookie_name_value);
            }
        }
        
        if (!empty($new_cookies)) {
            // Update session cookies - prevent duplicates by using a proper merge strategy
            $existing_cookies_array = [];
            if (!empty($this->session_cookies)) {
                foreach (explode('; ', $this->session_cookies) as $cookie) {
                    if (trim($cookie)) {
                        $cookie_name = explode('=', $cookie)[0];
                        $existing_cookies_array[$cookie_name] = $cookie;
                    }
                }
            }
            
            // Add new cookies, replacing any existing ones with the same name
            foreach ($new_cookies as $cookie) {
                $cookie_name = explode('=', $cookie)[0];
                $existing_cookies_array[$cookie_name] = $cookie;
            }
            
            $this->session_cookies = implode('; ', array_values($existing_cookies_array));
        }
    }
    
    /**
     * Get information about the last HTTP response
     * @return array|null Response info from curl_getinfo
     */
    public function get_last_response_info() {
        return $this->last_response_info;
    }
    
    /**
     * Test form submission with automatic CSRF token handling
     * @param string $url The form URL
     * @param array $form_data Form data (without _token)
     * @param string $expected_success_indicator String that should appear in successful response
     * @param string $message Test description
     * @return bool True if form submission appears successful
     */
    public function assert_form_submission($url, $form_data, $expected_success_indicator, $message) {
        // Get form and extract CSRF token
        $form_html = $this->get_content($url);
        if (!$this->assert_not_empty($form_html, "Form loaded from $url")) {
            return false;
        }
        
        $csrf_token = self::get_csrf_token($form_html);
        if (!$this->assert_not_empty($csrf_token, "CSRF token extracted ($csrf_token)")) {
            return false;
        }
        
        // Extract original form ID to detect if we stay on same form
        $parsed_html = self::parse_html($form_html);
        $original_form_id = null;
        if ($parsed_html) {
            $xpath = new DOMXPath($parsed_html);
            $form_node = $xpath->query('//form')->item(0);
            if ($form_node) {
                $original_form_id = $form_node->getAttribute('id') ?: 'form';
            }
        }
        
        // Add CSRF token to form data
        $form_data['_token'] = $csrf_token;
        
        echo "  Submitting to $url" . PHP_EOL;
        echo "  Form data: " . json_encode($form_data, JSON_PRETTY_PRINT) . PHP_EOL;
        echo "  Session cookies: " . ($this->session_cookies ?: '(none)') . PHP_EOL;
        
        // Submit form
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Referer: https://dev.agnstk.com/agnstk-example-app/login'
                ],
                'content' => http_build_query($form_data)
            ]
        ]);
        
        $response = $this->get_content($url, false, $context);
        if (!$this->assert_not_empty($response, "Form submission response received")) {
            return false;
        }
        
        // Debug: Check if this is actually a successful POST or a redirect back to GET
        $response_info = $this->get_last_response_info();
        if ($response_info) {
            // Check for successful login redirect (302/301 status codes)
            $http_code = $response_info['http_code'] ?? 0;
            if ($http_code >= 300 && $http_code < 400) {
                return $this->assert_true(true, $message . " (redirect detected)");
            }
        }
        
        // Check for success indicator
        $html_content = $this->analyze_html_content($response);
        $main_content = $html_content['main_content'] ?? '';
        $page_title = $html_content['page_title'] ?? '';
        $head_title = $html_content['head_title'] ?? '';

        // Check for Laravel validation errors
        $parsed_response = self::parse_html($response);
        if ($parsed_response) {
            $response_xpath = new DOMXPath($parsed_response);
            // Look for validation errors in multiple formats (div, span, p, etc.)
            $error_elements = $response_xpath->query('//*[contains(@class, "alert-danger") or contains(@class, "invalid-feedback") or contains(@class, "error") or contains(@class, "text-danger")]');
            if ($error_elements->length > 0) {
                foreach ($error_elements as $error_element) {
                    $error_text = trim($error_element->textContent);
                    if (!empty($error_text)) {
                        echo "    - " . $error_text . PHP_EOL;
                    }
                }
            }
        }

        // Look for success indicator in any content
        $success = (stripos($main_content, $expected_success_indicator) !== false) || 
                   (stripos($page_title, $expected_success_indicator) !== false) ||
                   (stripos($head_title, $expected_success_indicator) !== false);
        
        // If success indicator not found, check if we're still on the same form (failure)
        if (!$success) {
            $response_parsed = self::parse_html($response);
            $still_on_form = false;
            
            if ($response_parsed && $original_form_id) {
                $response_xpath = new DOMXPath($response_parsed);
                $response_form = $response_xpath->query("//form[@id='$original_form_id']")->item(0);
                if ($response_form) {
                    $still_on_form = true;
                } else {
                    // Check for any form with similar action or common login form patterns
                    $login_forms = $response_xpath->query('//form[contains(@action, "login") or .//input[@name="email"] or .//input[@name="password"]]');
                    if ($login_forms->length > 0) {
                        $still_on_form = true;
                        echo "  DEBUG: Still on login-like form - submission failed" . PHP_EOL;
                    } else {
                        echo "  DEBUG: No original form found - might have succeeded but no success indicator" . PHP_EOL;
                        // Could be success but without the expected indicator
                        $success = true;
                    }
                }
            } else {
                echo "  DEBUG: Could not parse response or determine form status" . PHP_EOL;
            }
            
            if ($still_on_form) {
                $success = false;
            }
        }
        
        return $this->assert_true($success, $message);
    }
    
    /**
     * Test user login
     * @param string $email User email
     * @param string $password User password
     * @param string $message Test description
     * @return bool True if login appears successful
     */
    public function user_login($email, $password, $message = '') {
        if (empty($message)) {
            $message = "Login with $email";
        }
        
        // Check if login page has form (might already be logged in)
        $login_html = $this->get_content(home_url('login'));
        $csrf_token = self::get_csrf_token($login_html);
        
        if (empty($csrf_token)) {
            // No login form found - might already be logged in
            $html_content = $this->analyze_html_content($login_html);
            $main_content = $html_content['main_content'] ?? '';

            $page_title = $html_content['page_title'] ?? '';
            $head_title = $html_content['head_title'] ?? '';

            $is_dashboard = (stripos($main_content, 'Dashboard') !== false) || 
                            (stripos($page_title, 'Dashboard') !== false) || 
                            (stripos($head_title, 'Dashboard') !== false);
            return $this->assert_true($is_dashboard, "$message ('$head_title')");
        }
        
        return $this->assert_form_submission(
            home_url('login'),
            ['email' => $email, 'password' => $password],
            'Dashboard',
            $message
        );
    }
    
    /**
     * Test user logout
     * @param string $message Test description
     * @return bool True if logout appears successful
     */
    public function user_logout($message = 'User logout') {
        // Laravel logout might be GET /logout or POST /logout, let's try GET first
        $logout_response = $this->get_content(home_url('logout'));
        
        if (!empty($logout_response)) {
            $html_content = $this->analyze_html_content($logout_response);
            $main_content = $html_content['main_content'] ?? '';
            
            // Check if we see login form after logout
            if (stripos($main_content, 'Login') !== false || stripos($main_content, 'Email') !== false) {
                return $this->assert_true(true, $message);
            }
        }
        
        // If GET didn't work, try POST
        return $this->assert_form_submission(
            home_url('logout'),
            [], // Logout typically only needs CSRF token
            'Login',
            $message
        );
    }

    /**
     * Parse HTML content into a DOMDocument with error suppression
     * @param string $html_content The HTML content to parse
     * @return DOMDocument|false The parsed document or false on error
     */
    public static function parse_html($html_content) {
        if (empty($html_content)) {
            return false;
        }
        
        $doc = new DOMDocument();
        // Suppress warnings for malformed HTML
        $old_setting = libxml_use_internal_errors(true);
        $success = $doc->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors($old_setting);
        
        return $success ? $doc : false;
    }

    /**
     * Extract title content from HTML
     * @param string $html_content The HTML content
     * @return string|false The title content or false if not found
     */
    public static function get_html_title($html_content) {
        $doc = self::parse_html($html_content);
        if (!$doc) {
            return false;
        }
        
        $xpath = new DOMXPath($doc);
        $title_nodes = $xpath->query('//title');
        
        return $title_nodes->length > 0 ? trim($title_nodes->item(0)->textContent) : false;
    }

    /**
     * Extract main content from HTML, excluding navigation, header, footer, sidebar elements
     * Uses multiple strategies to find the actual main content container
     * @param string $html_content The HTML content to extract from
     * @return string The main content text
     */
    public static function get_main_content($html_content, $raw = false) {
        $parsed_html = self::parse_html($html_content);
        if (!$parsed_html) {
            return '';
        }

        $xpath = new DOMXPath($parsed_html);
        
        // Try multiple main content selectors in order of preference
        $main_selectors = array(
            '//main',                                    // Standard HTML5 main element
            '//div[@id="main-content"]',                // Divi theme
            '//div[@id="content"]',                     // Common theme pattern
            '//div[@class="content"]',                  // Common theme pattern
            '//div[contains(@class, "main-content")]',  // Flexible main-content class
            '//div[contains(@class, "site-content")]',  // WordPress theme pattern
            '//div[contains(@class, "entry-content")]', // Post/page content
            '//article',                                // Article elements
            '//div[@role="main"]'                       // ARIA main role
        );
        
        foreach ($main_selectors as $selector) {
            $main_nodes = $xpath->query($selector);
            if ($main_nodes->length > 0) {
                // Found main content container, extract text while excluding secondary elements
                $main_container = $main_nodes->item(0);
                
                // Remove navigation, header, footer, sidebar elements from the main container
                $exclude_selectors = array(
                    './/nav', './/header', './/footer', 
                    './/*[contains(@class, "sidebar")]', 
                    './/*[contains(@class, "navigation")]',
                    './/*[contains(@class, "nav")]',
                    './/*[contains(@class, "widget")]',
                    './/*[contains(@class, "menu")]'
                );
                
                foreach ($exclude_selectors as $exclude_selector) {
                    $exclude_nodes = $xpath->query($exclude_selector, $main_container);
                    foreach ($exclude_nodes as $exclude_node) {
                        if ($exclude_node->parentNode) {
                            $exclude_node->parentNode->removeChild($exclude_node);
                        }
                    }
                }
                if($raw) {
                    return $parsed_html->saveHTML($main_container);
                }
                return trim($main_container->textContent);
            }
        }
        
        // Fallback: get body content and exclude known secondary elements
        $body_nodes = $xpath->query('//body');
        if ($body_nodes->length === 0) {
            return strip_tags($html_content);
        }

        $body = $body_nodes->item(0);
        
        // Remove common secondary elements from body
        $exclude_selectors = array(
            './/nav', './/header', './/footer', 
            './/*[contains(@class, "sidebar")]', 
            './/*[contains(@class, "navigation")]',
            './/*[contains(@class, "nav")]',
            './/*[contains(@class, "widget")]',
            './/*[contains(@class, "menu")]',
            './/*[@role="navigation"]',
            './/*[@role="banner"]',
            './/*[@role="contentinfo"]'
        );
        
        foreach ($exclude_selectors as $exclude_selector) {
            $exclude_nodes = $xpath->query($exclude_selector, $body);
            foreach ($exclude_nodes as $exclude_node) {
                if ($exclude_node->parentNode) {
                    $exclude_node->parentNode->removeChild($exclude_node);
                }
            }
        }

        if($raw) {
            return $parsed_html->saveHTML($body);
        }
        return trim($body->textContent);
    }

    /**
     * Extract CSRF token from HTML form
     * @param string $html_content The HTML content containing the form
     * @return string|false The CSRF token value or false if not found
     */
    public static function get_csrf_token($html_content) {
        $parsed_html = self::parse_html($html_content);
        if (!$parsed_html) {
            return false;
        }
        
        $xpath = new DOMXPath($parsed_html);
        
        // Look for _token hidden input field
        $token_inputs = $xpath->query('//input[@name="_token"][@type="hidden"]');
        if ($token_inputs->length > 0) {
            return $token_inputs->item(0)->getAttribute('value');
        }
        
        // Also check for csrf-token meta tag (alternative Laravel method)
        $meta_tokens = $xpath->query('//meta[@name="csrf-token"]');
        if ($meta_tokens->length > 0) {
            return $meta_tokens->item(0)->getAttribute('content');
        }
        
        return false;
    }

    /**
     * Analyze HTML content for basic page elements
     * Generic function that extracts common HTML elements without any domain-specific logic
     * @param string $html_content The HTML content to analyze
     * @return array Analysis results with basic page elements
     */
    public static function analyze_html_content($html_content) {
        $parsed_html = self::parse_html($html_content);
        if (!$parsed_html) {
            return array(
                'success' => false,
                'error' => 'Failed to parse HTML'
            );
        }

        $head_title = self::get_html_title($html_content);
        $main_content = self::get_main_content($html_content);
        
        // Get page title and all headings
        $xpath = new DOMXPath($parsed_html);
        
        // Find page title (first h1 in the document)
        $page_title = null;
        $headings = $xpath->query('//h1');
        if ($headings->length > 0) {
            $page_title = trim($headings->item(0)->textContent);
        }
        
        // Get all headings for analysis
        $all_headings = array();
        $heading_nodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        foreach ($heading_nodes as $heading) {
            $all_headings[] = array(
                'tag' => $heading->tagName,
                'text' => trim($heading->textContent),
                'class' => $heading->getAttribute('class')
            );
        }

        return array(
            'success' => true,
            'head_title' => $head_title,
            'page_title' => $page_title,
            'all_headings' => $all_headings,
            'main_content' => $main_content,
            'content_preview' => substr($main_content, 0, 200),
            'html_length' => strlen($html_content)
        );
    }
}

/**
 * Test Server Management Functions
 */

// Global variables to track server state
$_test_server_pid = null;
$_test_server_port = null;
$_test_server_base_url = null;

/**
 * Find the first available port starting from a given port
 */
function find_available_port($start_port = 8080) {
    for ($port = $start_port; $port <= 65535; $port++) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if (!$socket) {
            return $port; // Port is available
        }
        fclose($socket);
    }
    return false; // No available port found
}

/**
 * Start a PHP test server for testing
 */
function start_test_server($document_root = null) {
    global $_test_server_pid, $_test_server_port, $_test_server_base_url;
    
    if ($_test_server_pid !== null) {
        echo "Test server already running on port $_test_server_port" . PHP_EOL;
        return $_test_server_base_url;
    }
    
    if ($document_root === null) {
        $document_root = dirname(__DIR__); // Default to helpers root
    }
    
    $_test_server_port = find_available_port(8080);
    if ($_test_server_port === false) {
        throw new Exception("No available port found for test server");
    }
    
    $_test_server_base_url = "http://127.0.0.1:$_test_server_port";
    
    echo "Starting test server on port $_test_server_port..." . PHP_EOL;
    
    // Start PHP built-in server in background
    $command = sprintf(
        'php -S 127.0.0.1:%d -t %s > /dev/null 2>&1 & echo $!',
        $_test_server_port,
        escapeshellarg($document_root)
    );
    
    $output = shell_exec($command);
    $_test_server_pid = (int)trim($output);
    
    if ($_test_server_pid <= 0) {
        throw new Exception("Failed to start test server");
    }
    
    // Wait a moment for server to start
    usleep(500000); // 0.5 seconds
    
    // Test if server is responding
    $test_url = $_test_server_base_url . '/index.php';
    $response = @file_get_contents($test_url);
    if ($response === false) {
        stop_test_server();
        throw new Exception("Test server started but not responding at $test_url");
    }
    
    echo "Test server started successfully at $_test_server_base_url" . PHP_EOL;
    return $_test_server_base_url;
}

/**
 * Stop the test server
 */
function stop_test_server() {
    global $_test_server_pid, $_test_server_port, $_test_server_base_url;
    
    if ($_test_server_pid !== null) {
        echo "Stopping test server (PID: $_test_server_pid)..." . PHP_EOL;
        
        // Kill the server process
        $kill_command = "kill $_test_server_pid 2>/dev/null";
        shell_exec($kill_command);
        
        // Wait a moment for graceful shutdown
        usleep(200000); // 0.2 seconds
        
        // Force kill if still running
        $force_kill_command = "kill -9 $_test_server_pid 2>/dev/null";
        shell_exec($force_kill_command);
        
        $_test_server_pid = null;
        $_test_server_port = null;
        $_test_server_base_url = null;
        
        echo "Test server stopped" . PHP_EOL;
    }
}

/**
 * Get the current test server base URL
 */
function get_test_server_url() {
    global $_test_server_base_url;
    return $_test_server_base_url;
}

/**
 * Cleanup function to ensure server is stopped
 */
function cleanup_test_server() {
    stop_test_server();
}

// Register cleanup handlers
register_shutdown_function('cleanup_test_server');

// Set up exception handler to ensure cleanup
set_exception_handler(function($exception) {
    echo "Uncaught exception: " . $exception->getMessage() . PHP_EOL;
    cleanup_test_server();
    exit(1);
});

// Handle SIGINT (Ctrl+C) and SIGTERM if available
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() {
        echo PHP_EOL . "Received interrupt signal, cleaning up..." . PHP_EOL;
        cleanup_test_server();
        exit(0);
    });
    
    pcntl_signal(SIGTERM, function() {
        echo PHP_EOL . "Received termination signal, cleaning up..." . PHP_EOL;
        cleanup_test_server();
        exit(0);
    });
}

/**
 * Drop-in replacement for get_headers(), use curl if available to allow more control
 * (including bypass allow_url_fopen off)
 *
 * @param string $url The URL to get headers from
 * @param bool $associative Whether to return an associative array (default: false)
 * @param resource|null $context Stream context (ignored when using cURL)
 * @return array|false Array of headers or false on failure
 */
function testing_get_headers( $url, $associative = false, $context = null ) {
	// Check if cURL is available, otherwise fallback to get_headers
	if ( ! php_has('curl') ) {
		return get_headers( $url, $associative, $context );
	}

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_NOBODY, true );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HEADER, true );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'OpenSim-Helpers/1.0' );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	
	$response = curl_exec( $ch );
	curl_close( $ch );

	if ( $response === false ) {
		return false;
	}

	// Parse headers into same format as get_headers()
	$headers = array();
	$header_lines = explode( "\r\n", $response );
	foreach ( $header_lines as $line ) {
		$line = trim( $line );
		if ( empty( $line ) ) {
			continue;
		}
		
		if ( strpos( $line, ':' ) !== false ) {
			// Header line with name: value
			list( $key, $value ) = explode( ':', $line, 2 );
			$key = trim( $key );
			$value = trim( $value );
			
			if ( $associative ) {
				// Associative format like get_headers($url, true)
				if ( isset( $headers[ $key ] ) ) {
					// Multiple headers with same name - convert to array
					if ( ! is_array( $headers[ $key ] ) ) {
						$headers[ $key ] = array( $headers[ $key ] );
					}
					$headers[ $key ][] = $value;
				} else {
					$headers[ $key ] = $value;
				}
			} else {
				// Numeric format like get_headers($url, false)
				$headers[] = $key . ': ' . $value;
			}
		} else {
			// Status line (HTTP/1.1 200 OK)
			if ( ! $associative ) {
				$headers[] = $line;
			} else {
				// In associative mode, status line is at index 0
				$headers[0] = $line;
			}
		}
	}
	return $headers;
}

function php_has( $extension ) {
	switch($extension) {
		case 'php':
			return true; // PHP is always loaded
		case 'php7':
			return version_compare( PHP_VERSION, '7.0', '>=' );	
		case 'php7.4':
			return version_compare( PHP_VERSION, '7.4', '>=' );
		case 'php8.0':
			return version_compare( PHP_VERSION, '8.0', '>=' );
		case 'curl':
			return function_exists( 'curl_init' );
		case 'intl':
			return function_exists( 'transliterator_transliterate' );
		case 'xmlrpc':
			return function_exists( 'xmlrpc_encode' );
			// return function_exists( 'xmlrpc_encode' ) || class_exists( '\\PhpXmlRpc\\Value' );
		case 'imagick':
			return class_exists( 'Imagick' ) || class_exists( 'ImagickDraw' ) || class_exists( 'ImagickPixel' );
		case 'json':
			return function_exists( 'json_encode' );
		case 'mbstring':
			return function_exists( 'mb_convert_encoding' );
		case 'simplexml':
			return function_exists( 'simplexml_load_string' );
		default:
			// For any other extension, we can use the extension_loaded function
			return extension_loaded( $extension );
	}
	return false;
}

// Global test instance
$test = new SimpleTest();
