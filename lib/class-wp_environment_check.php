<?php
/**
 * WordPress-Environment_Check
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage WP_Environment_Check
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130103
 * @link       http://wordpress.com
 */

/**
 *
 * Class to check the environment (WordPress-, PHP and MySQL-version)
 * Test only on minimum or equal version
 *
 * @author Ralf Albert
 * @version 1.1
 * @link https://gist.github.com/1593410
 * @license GPL
 *
 * @var array|object $versions (optional) Array with key=>val or object $version->wp|php|mysql; what to test => minimum version
 *
 */

namespace RalfAlbert\lib\v3\EnviromentCheck;

class WP_Environment_Check
{
	/**
	 *
	 * WP, PHP & MySQL versions
	 * @access public static
	 * @var string minimum or equal version of WordPress
	 */
	public static $versions = array(

		'wp'	=> '3.2',
		'php'	=> '5.2',
		'mysql'	=> '5.0',

	);

	/**
	 *
	 * Exit message if WordPress test failed
	 * @access public static
	 * @var string
	 */
	public static $exit_msg_wp = '';

	/**
	 *
	 * Exit message if PHP test failed
	 * @access public static
	 * @var string
	 */
	public static $exit_msg_php = '';

	/**
	 *
	 * Exit message if MySQL test failed
	 * @access public static
	 * @var string
	 */
	public static $exit_msg_mysql = '';

	/**
	 *
	 * If set to true, the class will die with a message if a WP|PHP|MySQL test fail.
	 * Does not affect is_WP() or if forbidden_headers() is called without a message
	 * @access public static
	 * @var bool true (default)|false
	 */
	public static $die_on_fail = TRUE;

	/**
	 *
	 * Run all test that are defined in $version
	 * @access public
	 * @param array|object $versions
	 */

	public static function init( $versions = NULL ){

		if( ! empty( $versions ) || ( is_array( $versions ) || is_object( $versions ) ) )
			return self::run_all_tests( $versions );

		return FALSE;

	}

	/**
	 *
	 * Set $die_on_fail
	 * @param bool $status True exits the script with a message
	 */
	public static function set_die_on_fail( $status = TRUE ){

		if( ! is_bool( $status ) )
			$status = (bool) $status;

		self::$die_on_fail = $status;

	}

	/**
	 *
	 * Check if WordPress is active (if $wp is an object of class wp() )
	 * @access public static
	 * @return bool true|die with message and send forbidden-headers if WP is not active
	 */
	public static function is_WP(){
		/*
		 * ABSPATH is one of the first defined variables which are  global accessible.
		 * But this tells us only that a variable named 'ABSPATH' was defined.
		 * We don't know who has defined ABSPATH nor the database is connected to WordPress or not.
		 * Better we check if the database is connected with an instance of WordPress class wpdb.
		 */

		global $wpdb;

		return ! ( $wpdb instanceof wpdb ) ?
			self::forbidden_header() : TRUE;

	}

	/**
	 *
	 * Run all tests
	 * @access public static
	 * @param array|object $versions
	 * @return bool true if all tests passed successfully
	 */
	public static function run_all_tests( $versions = NULL ){

		if( empty( $versions ) || ( ! is_array( $versions ) && ! is_object( $versions ) ) )
			return FALSE;

		$tests = array( 'wp', 'php', 'mysql' );

		foreach( $versions as $test => $version ){

			$test = strtolower( $test );

			// check if the wanted test is available (means: is the test x a method 'check_x')
			if( in_array( $test, $tests ) ){

				$method = 'check_' . $test; // create the method (check_wp|check_php|check_mysql)
				self::$versions[$test] = $version;

				if( ! call_user_func( array( __CLASS__, $method ) ) )
					die( 'Test ' . __CLASS__ . '::' . $method . ' failed!' ); // this should never happen...

			}

		}

		return TRUE;

	}

	/**
	 *
	 * Check WordPress version
	 * @access public static
	 * @return bool true returns true if the test passed successfully. Die with a message if not.
	 */
	public static function check_wp(){

		if( empty( self::$versions['wp'] ) )
			return FALSE;

		if( empty( self::$exit_msg_wp ) )
			self::$exit_msg_wp = sprintf(
				'This plugin requires WordPress %s or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update WordPress</a> or delete the plugin.',
				self::$versions['wp']
			);

		global $wp_version;

		if( ! version_compare( $wp_version, self::$versions['wp'], '>=' ) )
			return self::forbidden_header( self::$exit_msg_wp );

		return TRUE;

	}

	/**
	 *
	 * Check PHP version
	 * @access public static
	 * @return bool true|die with message
	 */
	public static function check_php(){

		if( empty( self::$versions['php'] ) )
			return FALSE;

		if( empty( self::$exit_msg_php ) )
			self::$exit_msg_php = sprintf(
				'This plugin requires at least PHP version <strong>%s</strong>',
				self::$versions['php']
			);

		if( ! version_compare( PHP_VERSION, self::$versions['php'], '>=' ) ){
			return self::forbidden_header( self::$exit_msg_php );
		}

		return TRUE;

	}

	/**
	 *
	 * Check MYSQL version
	 * @access public static
	 * @return bool true|die with message
	 */
	public static function check_mysql(){

		if( empty( self::$versions['mysql'] ) )
			return FALSE;

		if( empty( self::$exit_msg_mysql ) )
			self::$exit_msg_mysql = sprintf(
				'This plugin requires at least MySQL version <strong>%s</strong>',
				self::$versions['mysql']
			);

		global $wpdb;
		if( ! version_compare( $wpdb->db_version(), self::$versions['mysql'], '>=' ) ){
			return self::forbidden_header( self::$exit_msg_mysql );
		}

		return TRUE;

	}

	/**
	 *
	 * Send forbidden-headers (403) if no message is set. Only dies if a message is set
	 * @access public static
	 * @param string (optional) $exit_msg
	 */
	public static function forbidden_header( $exit_msg = '' ){

		if( empty( $exit_msg ) ){

			header( 'Status: 403 Forbidden' );
			header( 'HTTP/1.1 403 Forbidden' );
			die( "I'm sorry Dave, I'm afraid I can't do that." );

		} else {

			if( FALSE === self::$die_on_fail )
				return FALSE;
			else
				die( $exit_msg );

		}

	}

}