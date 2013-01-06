<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    AvatarPlus
 * @subpackage AvatarPlus\Cache
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130103
 * @link       http://wordpress.com
 */

namespace AvatarPlus\Cache;

use AvatarPlus as Base;
use AvatarPlus\Backend\Backend as Backend;


/**
 * AvatarPlus caching class
 * Simple caching for urls to avatar images
 *
 * @author Ralf Albert
 *
 */
class Cache
{
	/**
	 * Internal cache
	 * @var array
	 */
	public static $cache = array();

	public static $chache_hits = 0;
	public static $chache_miss = 0;

	/**
	 * Reading cache if internal cache is empty
	 */
	public function __construct() {

		if( empty( self::$cache ) )
			self::$cache = $this->read_cache();

	}

	/**
	 * Test if the url data of a specific url are already cached
	 * @param string $url URL to test
	 * @return boolean True if the url data rae cached, else false
	 */
	public function is_cached( $url = '' ) {

		if( isset( self::$cache[ md5( $url ) ] ) )
			self::$chache_hits++;
		else
			self::$chache_miss++;

		return isset( self::$cache[ md5( $url ) ] );

	}

	/**
	 * Returns the cached url data
	 * @param string $url URL
	 * @return object URL data
	 */
	public function get_cached_url( $url = '' ) {

		return self::$cache[ md5( $url ) ];

	}

	/**
	 * Caching the url data
	 * @param AvatarPlus_Profile_To_Avatar $urldata Object with the url data
	 * @return boolean Always true
	 */
	public function cache_url( \stdClass $urldata ) {

		self::$cache[ md5( $urldata->url ) ] = $urldata;

		$this->write_cache();

		return true;

	}

	/**
	 * Read the external cache
	 * @return array Cached url data
	 */
	public function read_cache() {

		$transientkey = Backend::get_option( 'transientkey' );

		return get_site_transient( $transientkey );

	}

	/**
	 * Writing the external cache
	 * @return boolean Always true
	 */
	public function write_cache() {

		$transientkey = Backend::get_option( 'transientkey' );
		$expiration   = Backend::get_option( 'cache_expiration' );

		set_site_transient( $transientkey, self::$cache, $expiration );

		return true;

	}

}
