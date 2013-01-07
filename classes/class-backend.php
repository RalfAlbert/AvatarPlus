<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    AvatarPlus
 * @subpackage AvatarPlus\Backend
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130103
 * @link       http://wordpress.com
 */

namespace AvatarPlus\Backend;

class Backend
{
	const OPTION_KEY = 'avatarplus_options';

	const MENU_SLUG = 'avatarplus';

	const TEXTDOMAIN = 'avatarplus';

	public $basename = '';

	public $html = array();

	public static $options = array();

	public function __construct() {

		$this->basename = plugin_dir_path( dirname( __FILE__ ) );

		$this->init_translation();

		add_action( 'admin_init', array( $this, 'settings_api_init' ), 1, 0 );
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 10, 0 );

	}

	public static function get_option( $option_name = '' ) {

		if( empty( self::$options ) )
			self::$options = get_option( self::OPTION_KEY );

		if( empty( $option_name ) )
			return self::$options;

		return ( isset( self::$options[$option_name] ) ) ?
			self::$options[$option_name] : null;

	}

	public function init_translation() {

		$lang_dir = $this->basename . 'languages';

		load_plugin_textdomain( self::TEXTDOMAIN, false, $lang_dir );

		$lang = ( defined( 'WPLANG') ) ?
		$lang = substr( WPLANG, 0, 2 ) : 'en';

		if( is_dir( $lang_dir . '/' . $lang ) )
			$lang_dir .= '/' . $lang . '/';
		else
			$lang_dir .= '/en/';


		$this->html = glob( $lang_dir . '*.{htm,html}', GLOB_BRACE );

	}

	/**
	 *
	 * Initialise the WordPress Settings-API
	 * Register the settings
	 * Register the sections
	 * Register the fields for each section
	 */
	public function settings_api_init() {

		// the sections
		$sections = array(
			// section-id => title, callback
			'aplus' => array( 'title' => __( 'AvatarPlus settings', self::TEXTDOMAIN), 'callback' => 'aplus_section' ),
			'gplus'  => array( 'title' => __( 'GooglePlus', self::TEXTDOMAIN ), 'callback' => 'gplus_section' ),
		);

		// fields for the sections
		$fields = array(
			// field-id => in-section, title, callback
			'field_1'	=> array( 'section' => 'aplus', 'title' => __( 'Extra field', self::TEXTDOMAIN ), 'callback' => 'comment_field' ),
			'field_3'	=> array( 'section' => 'gplus', 'title' => __( 'GooglePlus API key', self::TEXTDOMAIN ), 'callback' => 'gplus_field' ),
		);

		// register settings
		register_setting(
			self::OPTION_KEY,
			self::OPTION_KEY,
			array( $this, 'options_validate' )
		);

		// register each section
		foreach( $sections as $id => $args ){

			$title = $args['title'];
			$callback = array( $this, $args['callback'] );

			add_settings_section( $id, $title, $callback, self::MENU_SLUG );

		}

		// register each field in it's section
		foreach( $fields as $id => $args ){

			$title = $args['title'];
			$section = $args['section'];
			$callback = array( $this, $args['callback'] );

			add_settings_field( $id, $title, $callback,	self::MENU_SLUG, $section );

		}

	}

	/**
	 *
	 * Add a page to the dashboard-menu
	 */
	public function add_menu_page(){

		if( ! current_user_can( 'manage_options' ) )
			return false;

		add_options_page(
			'AvatarPlus',
			'AvatarPlus',
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'main_section' ),
			false,
			'bottom'
		);

	}

	public function options_validate( $input ) {

		$options = self::get_option();

		$input = array_merge( $options, $input );

		$input['use_extra_field'] = ( isset( $input['use_extra_field'] ) && 'on' === $input['use_extra_field'] ) ? true : false;

		$input['cache_expiration'] = filter_var( $input['cache_expiration'], FILTER_SANITIZE_NUMBER_INT );


		return $input;

	}

	public function get_text( $section = '' ) {

		if( empty( $section ) )
			return false;


	}

	public function main_section() {

		if( ! current_user_can( 'manage_options' ) )
			return;

		echo '<div class="wrap"><h1>AvatarPlus</h1>';

		echo '<p>Welcome to AvatarPlus, the flexible avatar plugin. This plugin allow you to use profile images from Google Plus, Facebook and Twitter as avatar images.</p>';

		echo '<form action="options.php" method="post">';

		settings_fields( self::OPTION_KEY );
		do_settings_sections( self::MENU_SLUG );

		submit_button( __( 'Save Changes', self::TEXTDOMAIN ), 'primary', 'submit_options', true );

		echo '</form>';
		echo '</div>';

	}

	public function aplus_section() {

		echo '<p>Some words about the AvatarPlus settings...</p>';

	}

	public function comment_field() {

		$use_extra_field = self::get_option( 'use_extra_field' );
		$checked = checked( $use_extra_field, true, false );

		printf( '<input type="checkbox" name="%s[use_extra_field]"%s> %s', self::OPTION_KEY, $checked, __( 'Use extra field in comment form', self::TEXTDOMAIN ) );

	}

	public function gplus_section() {

		echo '<p>Some words about the GooglePlus API key...</p>';

	}

	public function gplus_field() {

		$apikey = self::get_option( 'gplus_apikey' );

		printf( '<input type="text" size="50" name="%s[gplus_apikey]" value="%s">', self::OPTION_KEY, esc_attr( $apikey ) );

	}


}