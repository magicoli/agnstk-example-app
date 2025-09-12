<?php
/**
 * Plugin Name:     AGNSTK Example App
 * Plugin URI:      https://agnstk-example-app.org
 * Description:     A simple AGNSTK example app implemented as a WordPress plugin.
 * Author:          Olivier van Helden <olivier@van-helden.net>
 * Author URI:      https://magiiic.com
 * Text Domain:     exampleapp
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Agnstk\ExampleApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load core autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load WordPress adapter autoloader  
require_once __DIR__ . '/adapters/wordpress/vendor/autoload.php';

// Load and initialize WordPress adapter
require_once __DIR__ . '/adapters/wordpress/wordpress-plugin.php';
