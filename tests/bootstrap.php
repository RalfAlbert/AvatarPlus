<?php
// Load WordPress test environment
// https://github.com/nb/wordpress-tests
//


$GLOBALS['wp_tests_options'] = array(
		'active_plugins' => array( 'avatar-plus/avatarplus.php' ),
		'template' => 'twentyeleven',
		'stylesheet' => 'twentyeleven',
);


// define path to plugin folder
$plugin_path = dirname( dirname( __FILE__ ) );
define( 'PLUGIN_BASE_PATH', $plugin_path );

! defined( 'WPTESTS_CONFIG_FILE' ) AND define( 'WPTESTS_CONFIG_FILE', dirname( __FILE__ ) . '/unittests-config.php' );

// include bootstrap
try {

    require_once '/WordPressTests/bootstrap.php';

} catch (Exception $e) {

    exit( "Couldn't find path to wordpress-tests/bootstrap.php\n" );

}
