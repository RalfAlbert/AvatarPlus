<?php
/**
 * WordPress-Plugin Simple Facebook Avatar
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage Simple Facebook Avatar
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.2.20130101-01
 * @link       http://wordpress.com
 */

/**
 * Plugin Name:	Simple Facebook Avatar
 * Plugin URI:	http://yoda.neun12.de
 * Description:	Replacing the standard avatar in comments with a facebook avatar if a user's facebook profile url is provided
 * Version: 	0.2.20130101-01
 * Author: 	Ralf Albert
 * Author URI: 	http://yoda.neun12.de
 * Text Domain:
 * Domain Path:
 * Network:
 * License:	GPLv3
 */

/*
 This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/*
 * You can configure some parts of the plugin e.g. in your functions.php
 *
 *  - Use an extra field
 *  	add_filter( 'simple_facebook_avatar_use_extrafield', '__return_false' );
 *		  Use '__return_false' if no extra field should be used.
 *		  Default is: true; use an extra field
 *
 * - Label text
 * 		apply_filters( 'simple_facebook_avatar_labeltext', __( 'Your translated label text', YourTextDomain );
 * 		  If an extrafield is used, overwrite the defualt label text
 * 		  Default is: Facebook Profile URL
 *
 *  - Alternate text for images
 *  	apply_filters( 'simple_facebook_avatar_alttext', $default_alternate_text )
 *  	  Setup an default alternate text for images
 *  	  Default is: Facebook Avatar
 */



/**
 * Main namespace for Simple Facebook Avatar
 * @author Ralf Albert
 *
 */
namespace SimpleFacebookAvatar;

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

register_uninstall_hook(
	__FILE__,
	__NAMESPACE__ . '\uninstall'
);

/**
 * Initialize the plugin, hook up all actions and filters
 * @uses apply_filters() 'simple_facebook_avatar_use_extrafield' filter var to TRUE to use an extra field.
 */
function plugin_init() {

	$use_extra_field = apply_filters( 'simple_facebook_avatar_use_extrafield', true );

	/*
	 * Add comment field
	 * @see http://wpengineer.com/2214/adding-input-fields-to-the-comment-form/
	 *
	 * Get Facebook Avatar
	 * @see http://stackoverflow.com/questions/2821061/facebook-api-how-do-i-get-a-facebook-users-profile-image-through-the-fb-api
	 */

	if( false !== $use_extra_field ){

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
		__NAMESPACE__ . '\get_facebook_avatar',
		10,
		5
	);

}

/**
 * Remove all comment-meta (Facebook profile URLs)
 */
function uninstall() {

	global $wpdb;

	$sql = "DELETE FROM {$wpdb->commentmeta} WHERE meta_key = %s;";

	$result = $wpdb->query( $wpdb->prepare( $sql, get_metakey() ) );

}

/**
 * Get the metakey
 *
 * @return string The used metakey
 */
function get_metakey(){

	return 'SFA_facebook_profile_url';

}

/**
 * Add an extra field to the comment form
 *
 * @uses apply_filters() 'simple_facebook_avatar_labeltext' Filter the label text for the extra field
 * @param array $default_fields The default comment fields
 * @return arary $default_fields Modified array with extra comment field
 */
function add_comment_field( $default_fields ) {

	$metakey = get_metakey();
	$label_text = apply_filters( 'simple_facebook_avatar_labeltext', 'Facebook Profile URL' );

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

	$metakey = get_metakey();

	add_comment_meta(
		$comment_id,
		$metakey,
		filter_input( INPUT_POST, $metakey, FILTER_SANITIZE_URL ),
		false
	);

}

/**
 * Get the Facebook avatar if an url to the user's profile is set. Else return the avatar created by WordPress
 *
 * @param string $avatar HTML of the avatar image
 * @param int|string|object $id_or_email User ID or user email
 * @param int $size Size of the avatar
 * @param string $default URL to a default image
 * @param string $alt Alternative text to use in image tag. Defaults to 'Facebook Avatar'
 * @return string $fb_avatar <img> tag with avatar
 */
function get_facebook_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

	global $comment;

	$fb_avatar = '';

	// prevent error message on dashboard if the comment ID is not set
	// do NOT use get_comment_ID(), this will raise the error messages again!
	$comment_id = ( isset( $comment->comment_ID ) ) ? $comment->comment_ID : 0;

	$fb_profile_url = get_comment_meta( $comment_id, get_metakey(), true );

	$fb_avatar = ( ! empty( $fb_profile_url ) ) ?
		convert_url_to_fb_avatar( $fb_profile_url, $alt, $size ) :
		convert_url_to_fb_avatar( $comment->comment_author_url, $alt, $size );

	// reset to default avatar if faild getting avatar from facebook
	return ( empty( $fb_avatar ) ) ?
	    $avatar : $fb_avatar;

}

/**
 * Try to convert an url into an Facebook avatar image
 *
 * @uses apply_filters() 'simple_facebook_avatar_alttext' Filter the alternate text for the <img>-tag
 * @param string $url URL to be converted
 * @param string $alt Alternate text for <img>-tag
 * @param integer $size Width & height of the avatar image
 * @return string HTML <img>-tag if succed, else an empty string
 */
function convert_url_to_fb_avatar( $url, $alt, $size ) {

	// predefine some vars
	$fb_avatar_template = 'http://graph.facebook.com/%username%/picture';
	$fb_user            = '';
	$fb_avatar          = '';
	$fb_avatar_url      = '';

	$size = ( is_numeric( $size ) ) ?
	    $size : 96;

	$safe_alt = ( false === $alt) ?
	    esc_attr( apply_filters( 'simple_facebook_avatar_alttext', 'Facebook Avatar' ) ) :
	    esc_attr( $alt );

	$parsed_url = parse_url( $url );

	if( isset( $parsed_url['path'] ) ) {

		$fb_user = trim( $parsed_url['path'], '/' );

		if( ! empty( $fb_user ) ){

			$fb_avatar_url = str_replace( '%username%', $fb_user, $fb_avatar_template );
			$fb_avatar = "<img alt='{$safe_alt}' src='{$fb_avatar_url}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

		}
	}

	return $fb_avatar;

}
