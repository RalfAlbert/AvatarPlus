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
 * @version    0.1.20130112
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

	/**
	 * Metakey for storing cache data in the options db
	 * @var string
	 */
	public $cachekey = '';

	/**
	 * ID of the current post
	 * @var integer
	 */
	public $post_id = 0;

	/**
	 * Number of cache hits
	 * @var integer
	 */
	public static $chache_hits = 0;

	/**
	 * Number of cache missed
	 * @var integer
	 */
	public static $chache_miss = 0;

	/**
	 * Reading cache if internal cache is empty
	 */
	public function __construct( $post_id = 0 ) {

		$this->post_id = filter_var( $post_id, FILTER_SANITIZE_NUMBER_INT );

		$this->cachekey = Backend::get_option( 'cachingkey' );

		if( empty( self::$cache ) )
			self::$cache = $this->read_cache( $this->post_id );

	}

	/**
	 * Test if the url data of a specific url are already cached
	 * @param string $url URL to test
	 * @return boolean True if the url data rae cached, else false
	 */
	public function is_cached( $url = '' ) {

		if( isset( self::$cache[ md5( $url ) ] ) )
			self::$chache_hits++;

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

		// do not cache data if no avatar is available
		if( empty( $urldata->avatar_url ) )
			return false;

		self::$cache[ md5( $urldata->url ) ] = $urldata;

		self::$chache_miss++;

		$this->write_cache( $this->post_id );

		return true;

	}

	/**
	 * Read the external cache
	 * @return array Cached url data
	 */
	public function read_cache( $post_id ) {

		return get_post_meta( $post_id, $this->cachekey, true );

	}

	/**
	 * Writing the external cache
	 * @return boolean Always true
	 */
	public function write_cache( $post_id ) {

		update_post_meta( $post_id, $this->cachekey, self::$cache );

		return true;

	}

}
