<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    AvatarPlus
 * @subpackage AvatarPlus\Profile_To_Avatar
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130112
 * @link       http://wordpress.com
 */

namespace AvatarPlus\Url;
use AvatarPlus\Backend\Backend;
use AvatarPlus\Cache\Cache;

/**
 * Convert a profile url to an avatar url for GooglePlus, Facebook and Twitter
 *
 * @author Ralf Albert
 *
 */
class Profile_To_Avatar
{

	/**
	 * Object containing informations about the url
	 * - is_reachable: is the url reachable
	 * - is_redirection: redirect the url to another url
	 * - location: target location if the url redirect to another url
	 * - etc
	 * @var object
	 */
	public $url = null;

	/**
	 * Maximum number of redirections
	 * Set the inital value to 0 to detect redirections
	 * @var integer
	 */
	public $redirection_count = 0;

	/**
	 * Caching for original url in case of recursive calls
	 * @var string
	 */
	public $original_url = '';

	/**
	 * Constructor setup the avatar url
	 * - test if the url is already cached. if it is cached, get the cached url data
	 * - else setup the url data and cache them
	 * @param string $url URL to the user profile (GooglePlus or Facebook )
	 * @param integer $size Size of the avatar image (needed for Google Plus)
	 * @param integer $post_id Post ID for caching
	 */
	public function __construct( $url, $size, $post_id = 0 ) {

		$cache = new Cache( $post_id );

		if( true === $cache->is_cached( $url ) ) {

			$this->url = $cache->get_cached_url( $url );

		} else {

			$this->url = $this->setup_url( $url );

			if( isset( $this->url->location ) && ! empty( $this->url->location ) )
				$this->url->avatar_url = $this->get_avatar_url( $size );

			$cache->cache_url( $this->url );

		}


	}

	/**
	 * Setting up the url data.
	 * - Validate and sanitize the profile url
	 * - Test if the profile url redirects to another url
	 * - resolve all redirects (maximum deepth: 5 redirects)
	 *
	 * @param unknown $url URL to the user profile
	 * @return object $data Object with all collected data
	 */
	public function setup_url( $url ) {

		// get data from $data if this method is called recursive
		if( is_object( $url ) ) {

			$data = $url;
			$data->url = $data->location;
			$data->is_redirected = false;

			// reset maximum number of redirections to the default value of wp_remote_get()
			$this->redirection_count = 5;

		} else {

			$data = new \stdClass();
			$data->url = (string) $url;

		}

		// url starts with http(s):// ?
		preg_match( '#^https?://#Uuis', $data->url, $match );

		$data->url = ( ! empty( $match ) ) ? $data->url : '';

		// sanitize & validate url
		// if validation of url fails, the filter returns false.
		$data->url = filter_var( $data->url, FILTER_SANITIZE_URL ) & filter_var( $data->url, FILTER_VALIDATE_URL );

		// the url is not a valid url, stop setup here
		if( empty( $data->url ) )
			return null;

		// detect redirection and  test if the url is reachable
		// on the first run, $this->redirecttion_count is 0.
		// This will return the http headers of the url and we
		// can detect if it is a redirection or not
		$remote = wp_remote_get( $data->url, array( 'sslverify' => false, 'redirection' => $this->redirection_count ) );

		// if the url is valid, but is not reachable, stop setup here
		if( is_wp_error( $remote ) ) {

			$data->is_reachable = false;

			return $data;

		}

		$location    = ( isset( $remote['headers']['location'] ) ) ? $remote['headers']['location'] : '';
		$status_code = ( isset( $remote['response']['code'] ) )    ? $remote['response']['code'] : 0;

		switch( $status_code ) {

			// url redirect to another url
			case 301:
			case 302:

				$data->location       = ( isset( $location ) && ! empty( $location ) ) ? $location : '';
				$data->is_redirected  = true;
				$data->is_reachable   = true;

				break;

			// found & ok
			case 200:

				$data->location       = $data->url;
				$data->is_redirected  = false;
				$data->is_reachable   = true;

				break;

			// all other status codes
			case 404:
			default:

				// Facebook return a 404 status code if the user is not logged in.
				// We have to fix that. Assuming all Facebook urls are reachable,
				// we simply set 'is_reachable' to true if it is a Facebook url.
				// Maybe I fix it in a future version
				$data->location       = $data->url;
				$data->is_redirected  = false;
				$data->is_reachable   = $this->is_facebook( $data->url );

				break;

		}

		// if the url is a redirection, call setup_url() recursive to resolve the redirection
		// but only on the first run!
		if( true === $data->is_redirected && 0 === $this->redirection_count ) {

			// save the original url
			if( empty( $this->original_url ) )
				$this->original_url = $data->url;

			$this->setup_url( $data );

		}

		// copy back the original url
		if( ! empty( $this->original_url ) )
			$data->url = $this->original_url;


		return $data;

	}

	/**
	 * Returns the service name if the url is supported, else false
	 * @return string|boolean $service The service name if the url is supported, else false
	 */
	public function is_url_supported() {

		return ( isset( $this->url->service ) || 'unknown' !== $this->url->service ) ?
			$this->url->service : false;

	}

	/**
	 * Whether the url is reachable (http status code 200, 301 or 302) or not
	 * @return boolean True if the url is reachable, else false
	 */
	public function is_url_reachable() {

		return ( isset( $this->url->is_reachable ) && ! empty( $this->url->is_reachable ) ) ?
			$this->url->is_reachable : false;

	}

	/**
	 * Whether the profile url redirects to another url
	 * @return boolean True if the url redirect, else false
	 */
	public function is_url_redirected() {

		return ( isset( $this->url->is_redirected ) && ! empty( $this->url->is_redirected ) ) ?
			$this->url->is_redirected : false;

	}

	/**
	 * Returns the location where the redirection points to if the url redirect
	 * @return string Last location in a possible row of redirects
	 */
	public function get_redirection_location() {

		return ( isset( $this->url->location ) && ! empty( $this->url->location ) ) ?
			$this->url->location : '';

	}

	/**
	 * Returns the url to the avatar image
	 * @param integer $size Size of the avatar image (needed for GooglePlus)
	 * @return string URL of the avatar image
	 */
	public function get_avatar_url( $size ) {

		if( isset( $this->url->avatar_url ) && ! empty( $this->url->avatar_url ) )
			return $this->url->avatar_url;

		$url        = isset( $this->url->location ) ? $this->url->location : $this->url->url; // prefer the resolved url, not the original url!!
		$avatar_url = '';

		preg_match( '#https?://(.+)/#Uuis', $url, $service );

		if( isset( $service[1] ) && ! empty( $service[1] ) ) {

			switch( $service[1] ) {

				case 'plus.google.com':
					$avatar_url = $this->get_gplus_avatar_url( $url, $size );
					$this->url->service = 'googleplus';
					break;

				case 'www.facebook.com':
					$avatar_url = $this->get_facebook_avatar_url( $url );
					$this->url->service = 'facebook';
					break;

				case 'twitter.com':
					$avatar_url = $this->get_twitter_avatar_url( $url );
					$this->url->service = 'twitter';
					break;

				default:
					$avatar_url = ''; // unknown service
					$this->url->service = 'unknown';
					break;

			}
		}

		if( ! empty( $avatar_url ) ) {
			$this->url->avatar_url = $avatar_url;
			$this->url->is_reachable = true;
		}

		return $avatar_url;

	}

	/**
	 * Convert a GooglePlus profile url into the GooglePlus avatar url
	 * @param string $url G+ profile url
	 * @param string $apikey G+ api-key
	 * @param integer $size Size of the avatar image
	 * @return string URL to the avatar image. If the profile url could not be converted, an empty string will be returned
	 */
	public function get_gplus_avatar_url( $url = '', $size = 96 ) {

		$apikey = Backend::get_option( 'gplus_apikey' );

		// no key, no data!
		if( empty( $apikey ) || empty( $url ) )
			return '';

		// predefine some vars
		$apiurl_template = 'https://www.googleapis.com/plus/v1/people/%s/';
		$apiurl          = '';
		$user			 = '';
		$avatar_url		 = '';
		$size            = ( is_integer( $size ) ) ? $size : 96;


		// grab a number with minimum length of 5 digits from given url
		preg_match( '#/(\d{5,})/?$#Uuis', $url, $matches );

		if( isset( $matches[1] ) && ! empty( $matches[1] ) )
			$userid = $matches[1];

		if( ! empty( $userid ) ){

			// insert user-id into G+ api-url
			$apiurl = sprintf( $apiurl_template , $userid );

			// add the api key as url-parameter
			$apiurl = add_query_arg( array( 'key' => $apikey ), $apiurl );

		}

		if( ! empty( $apiurl ) ) {

			$remote = wp_remote_get( $apiurl, array( 'sslverify' => false ) );

			if( ! is_wp_error( $remote ) ) {

				$data = json_decode( $remote['body'] );

				if( isset( $data->image->url ) )
					$avatar_url = $data->image->url;

				// add optional size to avatar url
				if( ! empty( $avatar_url ) )
					$avatar_url = preg_replace( '#(sz)=(\d+)$#Uuis', "$1={$size}", $avatar_url );

			}

		}


		return $avatar_url;

	}

	/**
	 * Convert a Facebook profile url into the Facebook avatar url
	 * @param string $url Facebook profile url
	 * @return string URL to the avatar image. If the profile url could not be converted, an empty string will be returned
	 */
	public function get_facebook_avatar_url( $url ='' ) {

		if( empty( $url ) )
			return '';

		// predefine some vars
		$avatar_template = 'http://graph.facebook.com/%username%/picture';
		$user            = '';
		$avatar_url      = '';

		$parsed_url = parse_url( $url );

		if( isset( $parsed_url['path'] ) ) {

			$user = trim( $parsed_url['path'], '/' );

			if( ! empty( $user ) )
				$avatar_url = str_replace( '%username%', $user, $avatar_template );

		}

		return $avatar_url;

	}

	/**
	 * Convert a Twitter profile url into the Twitter avatar url
	 * @param string $url Twitter profile url
	 * @return string URL to the avatar image. If the profile url could not be converted, an empty string will be returned
	 */
	public function get_twitter_avatar_url( $url = '' ) {

		if( empty( $url ) )
			return '';

		$avatar_template = 'http://api.twitter.com/1/users/profile_image/%username%.original';
		$user            = '';
		$avatar_url      = '';

		preg_match( '#/[^/]+/?$#Uuis', $url, $match );

		if( ! empty( $match ) ) {

			$user = trim( $match[0], '/' );

			if( ! empty( $user ) )
				$avatar_url = str_replace( '%username%', $user, $avatar_template );

		}

		return $avatar_url;

	}

	/**
	 * Helper function
	 * Simply check if the host of an url is 'facebook'
	 *
	 * @param string $url URL to test
	 * @return boolean True if the url contains the word 'facebook', else false
	 */
	protected function is_facebook( $url = '' ) {

		$parts = parse_url( $url );

		return ( isset( $parts['host'] ) ) ?
		(bool) stripos( $parts['host'], 'facebook' ) : false;

	}

}