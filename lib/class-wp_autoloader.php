<?php
/**
 * WordPress-Autoloader
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage WP_Autoloader
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130103
 * @link       http://wordpress.com
 */

/**
 *
 * WP-Autoloader
 *
 * Easy to use autoloader
 *
 * To overwrite $prefix, an empty string have to be passed to the autoloader
 *
 * @author Ralf Albert
 * @version 0.3.3
 */

/*
 * Usage
 * =====
 *
 * WP_Autoloader::init( [$config] );
 *
 * $config is optional
 * $config could be array or object
 *
 * $config should look like this:
 *
 * $config = new stdClass();
 * $config->abspath				= __FILE__;
 * $config->include_pathes		= array( '/lib', '/classes' );
 * $config->extensions			= arry( '.php', '.html' );
 * $config->prefixes			= array( 'class-' );
 * $config->remove_namespacde	= 'MyVendorName'
 *
 *  OR
 *
 * $config = array(
 * 		'abspath'			=> __FILE__,
 * 		'include_pathes'	=> array( 'models', 'views' ),
 * 		'extensions'		=> '.php',
 * 		'prefixes' 			=> array( 'model-', 'view-' ),
 * 		'remove_namespace'	=> 'MyVendorName\\'
 * );
 *
 * Use 'remove_namespace' if you wish to remove a part or a whole namespace
 * Eg.:
 * file:
 * Namespace Abteilung8\SomePackage\SubPackage
 * class PackageClass {}
 *
 * Normally WP_Autoloader try to load ABSPATH\abteilung8\somepackage\subpackage\prefix_packageclass.php
 * With 'remove_namespace' = 'Abteilung8\\', WP_Autoloader will remove 'Abteilung8\' and try to load the
 * class from ABSPATH\somepackage\subpackage\prefix_packageclass.php
 *
 */

namespace RalfAlbert\lib\v3\Autoloader;

class WP_Autoloader
{
	/**
	 *
	 * Absolute path to include directory
	 * @var string
	 */
	public static $abspath = FALSE;

	/**
	 *
	 * Relative path(es) to include directory
	 * @var array
	 */
	public static $include_pathes = array();

	/**
	 *
	 * Extension for files
	 * @var string
	 */
	public static $extensions = array( '.php' );

	/**
	 *
	 * Prefix for files
	 * @var array
	 */
	public static $prefixes = array();

	/**
	 *
	 * Namespace or part of a namespace to be removed from namespaces
	 * @var string
	 */
	public static $remove_namespace = '';

	/**
	 *
	 * Initialize the autoloadding
	 *
	 * @param	array|object	$config	Array with configuration
	 */
	public static function init( $config = NULL ){

		if( NULL === $config )
			$config = array();

		// casting object to array for array_merge
		$config = (array) $config;

		$defaults = array(
			'include_pathes'	=> self::$include_pathes,
			'extensions'		=> self::$extensions,
			'prefixes'			=> self::$prefixes,
			'remove_namespace'	=> self::$remove_namespace,
		);

		extract( array_merge( $defaults, $config ) );

		if( isset( $abspath ) && is_string( $abspath ) );
			self::set_abspath( $abspath );

		if( ! empty( $remove_namespace ) )
			self::$remove_namespace = strtolower( rtrim( $remove_namespace, '\\' ) . '\\' );

		self::autoloader_init( $include_pathes, $prefixes, $extensions );

	}

	/**
	 *
	 * Initialize the spl_autoloader
	 * Overwrite class-vars, set&sanitize include-path, checks if include-path was already set
	 *
	 * @param	array	$include_path
	 * @param	array	$prefix
	 * @param	string	$extension
	 */
	public static function autoloader_init( $include_pathes = array(), $prefixes = array(), $extensions = array() ){

		if( FALSE === self::$abspath )
			self::$abspath = dirname( __FILE__ );

		$include_pathes	= (array) $include_pathes;
		$prefixes		= (array) $prefixes;
		$extensions		= (array) $extensions;

		if( ! empty( $include_pathes ) )
			self::set_includepathes( $include_pathes );

		if( ! empty( $prefixes ) )
			self::$prefixes = $prefixes;

		if( ! empty( $extensions ) )
			self::set_extensions( $extensions );

		spl_autoload_register( array( __CLASS__, 'autoload' ), TRUE );

	}

	/**
	 *
	 * Callback for spl_autoload_register
	 *
	 * @param	string	$class_name
	 */
	private static function autoload( $class_name ){

		self::add_namespace_to_includepath( $class_name );

		$class_name = strtolower( self::remove_namespace( $class_name ) );

		// if a class-prefix is set, add it to the class-name
		if( ! empty( self::$prefixes ) ){

			foreach( self::$prefixes as $prefix ){

				try {
					spl_autoload( $prefix . $class_name );
				} catch ( Exception $e ) {
					throw new WordPress_Exception( 'Class ' . $prefix . $class_name . ' not found' );
				}

			}

		} else {

				try {
					spl_autoload( $class_name );
				} catch ( Exception $e ) {
					throw new WordPress_Exception( 'Class ' . $class_name . ' not found' );
				}

		}

	}

	/**
	 *
	 * Removes a namespace with backslash as seperator from the classname
	 *
	 * @param	string	$class Classname to be cleared
	 * @return	string	Classname without namespace
	 */
	public static function remove_namespace( $nsclass = '' ){

		if( FALSE === strpos( $nsclass, '\\' ) )
			return $nsclass;

		return array_pop( explode( '\\', $nsclass ) );

	}

	/**
	 *
	 * Adding a namespace recursiv to includepath
	 *
	 * @param	string	$namespace Namespace to be added
	 * @return	array	The modified includepath
	 */
	public static function add_namespace_to_includepath( $namespace = '' ){

		if( FALSE === strpos( $namespace, '\\' ) )
			return $namespace;

		$namespace = strtolower( $namespace );

		if( ! empty( self::$remove_namespace ) )
			$namespace =  str_replace( self::$remove_namespace, '', $namespace );

		// remove filename if exists
		foreach( self::$extensions as $ext ){

			if( FALSE !== strpos( $namespace, $ext ) )
				$namespace = dirname( $namespace );

		}

		$namespace = explode( '\\', $namespace );

		while( 0 < count( $namespace ) ) {
			self::set_includepathes( implode( '\\', $namespace ) );
			array_pop( $namespace );
		}

		return get_include_path();

	}

	/**
	 *
	 * Set the absolute path to file
	 *
	 * @param	string	$abspath
	 * @return	bool	bool True on success (abspath is an file or directory), false on fail
	 */
	public static function set_abspath( $abspath = FALSE ){

		if( FALSE != $abspath && is_string( $abspath ) ){

			if( is_file( $abspath ) )
				self::$abspath = dirname( $abspath );

			elseif( is_dir( $abspath) )
				self::$abspath = strtolower( $abspath );

			else
				return FALSE;

		}

		return TRUE;

	}

	/**
	 *
	 * Sanitize and add the pathes to PHPs include-path
	 *
	 * @param	array	$pathes
	 * @return	array	array Registered include pathes
	 */
	public static function set_includepathes( $pathes){

		if( ! is_array( $pathes ) )
			$pathes = (array) $pathes;

		foreach( $pathes as $path ){

			if( is_string( $path ) ){

				// strip slashes and backslashes at the start and end of the string /classes/ -> classes; /lib/classes/ -> lib/classes
				$path = preg_replace( "#^[/|\\\]|[/|\\\]$#", '', $path );

				// replace slashes and backslashes with the OS specific directory seperator
				$path = preg_replace( "#[/|\\\]#", DIRECTORY_SEPARATOR, $path );

				if( is_dir( $path ) || is_dir( self::$abspath . '/' . $path ) ){
					array_push( self::$include_pathes, $path );

				}
			}

		}

		if( ! empty( self::$include_pathes ) )
			return self::register_includepathes( self::$include_pathes );
		else
			return NULL;

	}

	/**
	 *
	 * Registering the includ pathes
	 *
	 * @param	string|array	$includepathes
	 * @return	array			Array with registered pathes
	 */
	public static function register_includepathes( $includepathes ){

		if( ! is_array( $includepathes ) )
			$includepathes = (array) $includepathes;
		/*
		 * From php.net/spl_autoload (http://de3.php.net/manual/de/function.spl-autoload.php)
		 *
		 * 1. Add your class dir to include path
		 * 2. You can use this trick to make autoloader look for commonly used "My-class.php" type filenames
		 * 3. Use default autoload implementation or self defined autoloader
		 */

		foreach( self::$include_pathes as $includepath ){

			$path = self::$abspath . DIRECTORY_SEPARATOR . $includepath;

			// check if the path have already been added to include_path
			$pathes = explode( PATH_SEPARATOR, get_include_path() );

			if( ! in_array( $path, $pathes ) )
				// set our path at the first position. require, include, __autoload etc. start searching in the first path
				// with our custom path at the first, PHP does not have to search in all other pathes for our classes
				set_include_path( $path . PATH_SEPARATOR . get_include_path() );

		}

		return get_include_path();

	}

	/**
	 *
	 * Set the list with file-extensions for spl_autoload
	 *
	 * @param	string|array	$extensions
	 * @return	string|NULL		Comma separeted list with registered file-extensions or NULL on error
	 */
	public static function set_extensions( $extensions ){

		if( ! is_array( $extensions ) )
			$extensions = (array) $extensions;

		array_walk(
			$extensions,
			function( &$e ){ $e = strtolower( $e ); }
		);

		self::$extensions = $extensions;

		if( ! empty( self::$extensions ) )
			return self::register_extensions( self::$extensions );
		else
			return NULL;

	}

	/**
	 *
	 * Register one or more extension for autoloading
	 *
	 * @param	string|array	$extensions
	 * @return	string			Comma separated list with registered file-extensions
	 */
	public static function register_extensions( $extensions ){

		if( ! is_string( $extensions ) && ! is_array( $extensions ) )
			return FALSE;

		if( is_array( $extensions ) && ! empty( $extensions ))
			$extensions = implode( ',', $extensions );

		if( empty( $extensions ) )
			$extensions = &self::$extensions;

		spl_autoload_extensions( $extensions );

		return spl_autoload_extensions();

	}

	/**
	 *
	 * Removes a single path from PHPs include-path and from the internal
	 * list of include-pathes
	 *
	 * @param	string	$search_path Path to be removed from include-pathes
	 */
	public static function remove_includepath( $search_path ){

		$old_path = get_include_path();

		$pattern = sprintf(
			'/%s%s?/',
			str_replace( "\\", "\\\\", $search_path ),
			PATH_SEPARATOR
		);

		$new_path = preg_replace( $pattern, '', $old_path );

		set_include_path( $new_path );

		foreach( self::$include_pathes as $key => $path ){

			if( $path === $search_path )
				unset( self::$include_pathes[$key] );

		}

	}

	/**
	 *
	 * Unregister the registered autoload-function and removes the added pathes from PHPs
	 * include-path
	 *
	 */
	public static function reset(){

		spl_autoload_unregister( array( __CLASS__, 'autoload' ) );

		foreach( self::$include_pathes as $path )
			self::remove_includepath( $path );

	}

}
