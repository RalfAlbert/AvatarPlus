<?php
// Load WordPress test environment
// https://github.com/nb/wordpress-tests
//

// define path to plugin folder
$plugin_path = dirname( dirname( __FILE__ ) );
define( 'PLUGIN_BASE_PATH', $plugin_path );

// The path to wordpress-tests
$path = '/wp_phpunit/wordpress-tests/bootstrap.php';

try {
    require_once $path;
} catch (Exception $e) {
    exit( "Couldn't find path to wordpress-tests/bootstrap.php\n" );
}