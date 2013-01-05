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
 * @version    0.1.20130103
 * @link       http://wordpress.com
 */

/**
 * Plugin Name:	AvatarPlus
 * Plugin URI:	http://yoda.neun12.de
 * Description:	Replacing the standard avatar in comments with a GooglePlus or Facebook avatar if a user enter his G+/FB profile url in comments
 * Version: 	0.1.20130103
 * Author: 		Ralf Albert
 * Author URI: 	http://yoda.neun12.de
 * Text Domain:
 * Domain Path:
 * Network:
 * License:		GPLv3
 */

namespace AvatarPlus;

/**
 * Initialize plugin on theme setup.
 * This is a theme specific functionality, but the code store some data
 * in the comment meta. This data can be better removed on plugin uninstall.
 *
 */
add_action(
	'after_setup_theme',
	__NAMESPACE__ . '\plugin_init',
	10,
	0
);

register_activation_hook(
	__FILE__,
	__NAMESPACE__ . '\activate'
);

register_uninstall_hook(
	__FILE__,
	__NAMESPACE__ . '\uninstall'
);

function activate() {}

/**
 * Remove all comment-meta (profile URLs)
 */
function uninstall() {

	global $wpdb;

	$sql = "DELETE FROM {$wpdb->commentmeta} WHERE meta_key = %s;";

	$result = $wpdb->query( $wpdb->prepare( $sql, get_metakey() ) );

}

/**
 * Get the metakey used by AvatarPlus
 *
 * @return string The used metakey
 */
function get_metakey(){

	return 'avatarplus_profile_url';

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

	$metakey    = get_metakey();
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

	$metakey = get_metakey();

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

	global $comment;

	if( empty( $comment ) )
		return $comment;
	else
		$comment = (object) $comment;

//TODO: move to converter
/*
	$apikey = apply_filters( 'avatarplus_apikey', false );

	// no key, no G+ avatar!!
	if( empty( $apikey ) && 'gplus' === get_services() )
		return $avatar;
*/
	$aplus_avatar   = '';
	$profile_url    = '';
	$avatar_url     = '';
	$url_converter  = new URL_Converter_Profile_To_Avatar();

	// prevent error message on dashboard if the comment ID is not set
	// do NOT use get_comment_ID(), this will raise the error messages again!
	$comment_id = ( isset( $comment->comment_ID ) ) ? $comment->comment_ID : 0;

	// Try to get an url from comment-meta
	// Do not test on empty url or something else. If we do test it, we have to
	// test if it is a valid url. If the url is not empty, we just try to
	// get the avatar url. If it fails, get_avatar_url() returns
	// an empty string and trigger the fallback to the WP avatar
	$profile_url = get_comment_meta( $comment_id, get_metakey(), true );

	$avatar_url = ( ! empty( $profile_url ) ) ?
		$url_converter->get_avatar_url( $profile_url, $size ) :
		$url_converter->get_avatar_url( $comment->comment_author_url, $size );

//TODO: Caching avatar url / profile_url

	$aplus_avatar = $url_converter->replace_avatar_source( $avatar, $avatar_url );

	// reset to default avatar if faild getting avatar from profile url
	return ( empty( $aplus_avatar ) ) ?
		$avatar : $aplus_avatar;

}

/**
 * Get avatar urls from different services (such like GooglePlus and Facebook)
 *
 * @author Ralf Albert
 *
 */
class URL_Converter_Profile_To_Avatar
{
	/**
	 * Size of the avatar image
	 * @var int
	 */
	public $size = 0;

	/**
	 * If the original url redirect to a profile url, this is the profile url
	 * @var string
	 */
	public $real_url = '';

	public function get_avatar_url( $url = '', $size = 96 ){

		$url = $this->vasa( $url, 'url' );
		if( empty( $url ) )
			return '';

		$this->size = ( 0 !== $this->vasa( $size, 'int' ) ) ?
			$this->vasa( $size, 'int' ) : 96;



	}

	public function replace_avatar_source( $old_avatrar, $source ){}

	protected function vasa( $what, $how ) {

		$how = strtolower( $how );

		if( ! key_exists( $how, $actions ) )
			return null;

		$actions = array(
			'url' => function ( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ) & filter_var( $url, FILTER_VALIDATE_URL ); },
			'str' => function ( $str ) { return  (bool) filter_var( $str, FILTER_SANITIZE_STRING ) & is_string( $str ); },
			'int' => function ( $int ) { return (bool) filter_var( $int, FILTER_SANITIZE_NUMBER_INT ) & is_integer( $int ); },
		);

		return $actions[$how] ( $what );

	}

	protected function get_real_url( $url = '' ) {

		$result = wp_remote_get( $url, array( 'sslverify' => false, 'rediretions' => 0 ) );

		if( '301' === $result['code'] )
			$this->is_redirect = true;

	}
}