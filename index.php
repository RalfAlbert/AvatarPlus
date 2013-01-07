<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage AvatarPlus
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130106
 * @link       http://wordpress.com
 */

/**
 * Plugin Name:	AvatarPlus
 * Plugin URI:	http://yoda.neun12.de
 * Description:	Replacing the standard avatar in comments with a GooglePlus, Facebook or Twitter avatar if a user enter a profile url
 * Version: 	0.1.20130106
 * Author: 		Ralf Albert
 * Author URI: 	http://yoda.neun12.de
 * Text Domain:
 * Domain Path:
 * Network:
 * License:		GPLv3
 */

namespace AvatarPlus;
use RalfAlbert\lib\v3\Autoloader as Autoloader;
use RalfAlbert\lib\v3\EnviromentCheck as EnvCheck;
use AvatarPlus\Url as Url;
use AvatarPlus\Cache as Cache;
use AvatarPlus\Backend as Backend;

/**
 * Initialize plugin on theme setup.
 * This is a theme specific functionality, but the code store some data
 * in the comment meta. This data can be better removed on plugin uninstall.
 *
 */
add_action(
	'plugins_loaded',
	__NAMESPACE__ . '\plugin_init',
	10,
	0
);

register_activation_hook(
	__FILE__,
	__NAMESPACE__ . '\activate'
);

register_deactivation_hook(
	__FILE__,
	__NAMESPACE__ . '\deactivate'
);

register_uninstall_hook(
	__FILE__,
	__NAMESPACE__ . '\uninstall'
);

/**
 * On activation:
 * - Initialize autoloader
 * - Check if the PHP- and WP versions are correct
 * - Add default options
 */
function activate() {

	init_autoloader();

	new EnvCheck\WP_Environment_Check(
		array(
			'php' => '5.3',
			'wp'  => '3.5'
		)
	);

	// default options
	$options = array(
		'metakey'          => 'avatarplus_profile_url',
		'cachingkey'       => 'avatarplus_caching',
		'use_extra_field'  => false,
	);

	add_option( Backend\Backend::OPTION_KEY, $options );

}

/**
 * On deactivation:
 *  - Remove cached urls
 *  - Remove options
 */
function deactivate() {

	global $wpdb;

	// delete caching data
	$sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s;";
	$wpdb->query( $wpdb->prepare( $sql, Backend\Backend::get_option( 'cachingkey' ) ) );

// 	init_autoloader();
// 	delete_option( Backend\Backend::OPTION_KEY );

}

/**
 * On uninstall:
 *  - Remove all comment-meta (profile URLs)
 *  - Remove all post-meta (cached URLs)
 *  - Remove options
 */
function uninstall() {

	global $wpdb;

	// delete extra field data
	$sql = "DELETE FROM {$wpdb->commentmeta} WHERE meta_key = %s;";
	$wpdb->query( $wpdb->prepare( $sql, Backend\Backend::get_option( 'metakey' ) ) );

	// delete caching data
	$sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s;";
	$wpdb->query( $wpdb->prepare( $sql, Backend\Backend::get_option( 'cachingkey' ) ) );

	// remove options
	delete_option( Backend\Backend::OPTION_KEY );

}

function init_autoloader() {

	require_once dirname( __FILE__ ) . '/lib/class-wp_autoloader.php';

	$config = new \stdClass();
	$config->abspath			= __FILE__;
	$config->include_pathes		= array( '/lib', '/classes' );
	$config->extensions			= array( '.php' );
	$config->prefixes			= array( 'class-' );
	$config->remove_namespace	= __NAMESPACE__;

	Autoloader\WP_Autoloader::init( $config );


	return true;
}

function plugin_init() {

	init_autoloader();

	$use_extra_field = Backend\Backend::get_option( 'use_extra_field' );

	if( false !== $use_extra_field ) {

		// add the field to comment form
		add_filter(
			'comment_form_defaults',
			__NAMESPACE__ . '\add_comment_field'
		);

		// save data from new comment field on posting a comment
		add_action(
			'comment_post',
			__NAMESPACE__ . '\save_comment_meta_data',
			10,
			1
		);

	}

	// get avatar
	add_filter(
		'get_avatar',
		__NAMESPACE__ . '\get_aplus_avatar',
		10,
		5
	);

	// create menupage
	if( is_admin() )
		$backend = new Backend\Backend();


	// debugging
	add_action(
		'wp_footer',
		__NAMESPACE__ . '\get_cache_usage',
		10,
		0
	);

}

/**
 * Add an extra field to the comment form
 *
 * @uses apply_filters() 'avatarplus_labeltext' Filter the label text for the extra field
 * @param array $default_fields The default comment fields
 * @return arary $default_fields Modified array with extra comment field
 */
function add_comment_field( $default_fields ) {

	if( ! is_array( $default_fields ) || empty( $default_fields ) )
		return $default_fields;

	$metakey    = Backend\Backend::get_option( 'metakey' );
	$label_text = apply_filters( 'avatarplus_labeltext', 'Profile URL' );

	$comment_field_template =
	'<p class="comment-form-author">
		<label for="%label%">%label_text%</label>
		<input id="%label%" name="%label%" size="30" type="text" />
	</p>';

	$comment_field_template = str_replace( '%label%', $metakey, $comment_field_template );
	$comment_field_template = str_replace( '%label_text%', $label_text, $comment_field_template );

	$default_fields['fields'][$metakey] = $comment_field_template;

	return $default_fields;

}

/**
 * Save the data from extra comment field
 *
 * @param integer $comment_id ID of the current comment
 */
function save_comment_meta_data( $comment_id ) {

	if( empty( $comment_id ) )
		return $comment_id;
	else
		$comment_id = (int) $comment_id;

	$metakey = Backend\Backend::get_option( 'metakey' );

	add_comment_meta(
		$comment_id,
		$metakey,
		filter_input( INPUT_POST, $metakey, FILTER_SANITIZE_URL ),
		false
	);

}

/**
 * Get the avatar if an url to the user's profile is set. Else return the avatar created by WordPress
 *
 * @uses apply_filters 'avatarplus_apikey' !!!IMPORTANT!!! Setup the api-key for G+ with apply_filters
 * @param string $avatar HTML of the avatar image
 * @param int|string|object $id_or_email User ID or user email
 * @param int $size Size of the avatar
 * @param string $default URL to a default image
 * @param string $alt Alternative text to use in image tag. Defaults to 'AvatarPlus'
 * @return string $aplus_avatar <img>-tag with avatar
 */
function get_aplus_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

	global $comment, $post;

	if( empty( $comment ) )
		return $comment;
	else
		$comment = (object) $comment;

	$aplus_avatar      = null;
	$aplus_avatar_html = null;
	$profile_url       = '';
	$metakey           = Backend\Backend::get_option( 'metakey' );

	// prevent error message on dashboard if the comment ID is not set
	// do NOT use get_comment_ID(), this will raise the error messages again!
	$comment_id = ( isset( $comment->comment_ID ) ) ? $comment->comment_ID : 0;

	// Try to get an url from comment-meta
	// Do not test on empty url or something else. If we do test it, we have to
	// test if it is a valid url. If the url is not empty, we just try to
	// get the avatar url. If it fails, get_avatar_url() returns
	// an empty string and trigger the fallback to the WP avatar
	$profile_url = get_comment_meta( $comment_id, $metakey, true );

	$aplus_avatar = ( ! empty( $profile_url ) ) ?
		new Url\Profile_To_Avatar( $profile_url, $size, $post->ID ) :
		new Url\Profile_To_Avatar( $comment->comment_author_url, $size, $post->ID );

	// reset to default avatar if faild getting avatar from profile url
	if( false === $aplus_avatar->is_url_reachable() )
		return $avatar;

	$aplus_avatar_html = replace_avatar_html( $avatar, $aplus_avatar->get_avatar_url( $size ), $size, $alt );

	return $aplus_avatar_html;

}

/**
 * Replacing the attributes in the WP avatar <img>-tag
 * @param string $html The html to modify
 * @param string $url URL replacement
 * @param number $size Size replacement
 * @param string $alt Alternate text replacement
 * @return string Modified <img>-tag
 */
function replace_avatar_html( $html = '', $url = '', $size = 0, $alt = '' ) {

	if( empty( $html ) )
		return '';

	$search_and_replace = array(
			'src'    => 'url',
			'alt'    => 'alt',
			'width'  => 'size',
			'height' => 'size'
	);

	foreach( $search_and_replace as $attrib => $var ) {

		if( ! empty( $$var ) )
			$html = preg_replace(
					sprintf( '#%s=(["|\'])(.*)(["|\'])#Uuis', $attrib ),
					sprintf( '%s=${1}%s${3}', $attrib, $$var ),
					$html
			);


	}

	return $html;

}

function get_cache_usage() {

	$cache = new Cache\Cache();

	printf( '<p style="text-align:center">Cache hits: %d / Chache missed: %d</p>', $cache::$chache_hits, $cache::$chache_miss );

}
