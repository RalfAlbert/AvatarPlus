<?php
/**
 *
 * WP_Autoloader
 *
 * Easy to use autoloader
 *
 * @author Ralf Albert (neun12@googlemail.com)
 * @version 1.1
 */

/*
 * Usage
 * =====
 *
 * WP_Autoloader( [config] );
 *
 * $config could be array or object
 *
 * $config should look like this:
 *
 * $config = new stdClass();
 * $config->abspath  = __FILE__;
 * $config->pathes   = array( '/lib' );
 * $config->prefixes = array();
 *
 *  OR
 *
 * $config = array(
 * 		'abspath'  => __FILE__,
 * 		'pathes'   => array( 'models', 'views' ),
 * 		'prefixes' => array( 'model-', 'view-' ),
 * );
 *
 * 'abspath' is the base directory where the plugin is inside
 * 'pathes' are the directories relative to 'abspath' where to search for classes
 * 'prefixes' are file-prefixes for class files
 */

namespace WordPress\Autoloader;

if ( ! class_exists( 'WordPress\Autoloader\Autoloader' ) ) {
	class Autoloader
	{
		/**
		 * Absolute path to plugin
		 * @var string
		 */
		public $abspath  = '';

		/**
		 * Directories realtive to absolute path to search for classes
		 * @var array
		 */
		public $pathes   = array();

		/**
		 * File-prefixes for class filenames
		 * @var array
		 */
		public $prefixes = array();

		/**
		 * Initialize the autoloading
		 *
		 * The autoloader will be initialized with default values if no configuration is set.
		 * These values are:
		 *  - the directory where the autoloader file is in as 'abspath'
		 *  - no pathes (empty array)
		 *  - no prefixes (empty array)
		 *
		 *  If a configuration is set, then it will be merged with the default configuration.
		 *  The 'abspath' will be sanitized (right trim slashes and backslashes, add directory seperator at end)
		 *  After this, the pathes will be sanitized to valid pathes. All unreachable pathes will be removed!
		 *  As all pathes are relative to the 'abspath', all pathes will be removed if the 'abspath' is wrong.
		 *  In the last step the autoload function is registered with spl_autoload_register()
		 *
		 * @param array|object $args
		 */
		public function __construct( $args = null ) {

			$defaults = array(
				'abspath'  => dirname( __FILE__ ) . DIRECTORY_SEPARATOR,
				'pathes'   => $this->pathes,
				'prefixes' => $this->prefixes,
			);

			$args = array_merge( $defaults, $args );

			$this->abspath = (string) $args['abspath'];
			$this->sanitize_abspath();

			$this->pathes   = array_merge( $this->pathes, (array) $args['pathes'] );
			$this->sanitize_pathes();

			$this->prefixes = array_merge( $this->prefixes, (array) $args['prefixes'] );

			$this->register_autoloader();

		}

		/**
		 * Sanitizing the absolute path
		 *
		 * Removes slashes and backslashes at the end, add a directory seperator at the end
		 */
		public function sanitize_abspath() {

			if( is_file( $this->abspath ) )
				$this->abspath = dirname( $this->abspath );

			$this->abspath = rtrim( $this->abspath, '/' );
			$this->abspath = rtrim( $this->abspath, '\\' );
			$this->abspath .= DIRECTORY_SEPARATOR;

		}

		/**
		 * Sanitizing the pathes
		 *
		 * Remove slashes and backslashes on the left and right. Add a directory seperator
		 * at the end. Check if the directory exists, use 'abspath' as base. If the directory
		 * does not exists, removes it from the list.
		 */
		public function sanitize_pathes() {

			foreach ( $this->pathes as $key => &$path ) {
				$path = trim( $path, '/' );
				$path = trim( $path, '\\' );
				$path = sprintf( '%s%s%s', $this->abspath, $path, DIRECTORY_SEPARATOR );

				if ( ! is_dir( $path ) )
					unset( $this->pathes[$key] );

			}

		}

		/**
		 * Registering the autoload function
		 *
		 * Use spl_autoload_register() to register the autoload function.
		 */
		public function register_autoloader() {

			spl_autoload_register( array( $this, 'autoload' ), true, true );

		}

		/**
		 * The autoload function itself
		 *
		 * Convert all classnames into lower characters. Try to find a namespace.
		 * If a namespace was found, use the namespace as path. Else use the setup pathes
		 * to search for the class.
		 * Both, namespaced and not namespaced classes, can be prefixed.
		 *
		 * @param string $class Class to be loaded
		 */
		public function autoload( $class ) {

			$classname = strtolower( $class );

			switch ( $this->maybe_namespaced( $classname ) ) {

				case 'namespaced':
					$this->load_namespaced( $classname );
					break;

				case 'not_namespaced':
				default:
					$this->load_not_namespaced( $classname );
					break;

			}

		}

		/**
		 * Load not namespaced classes
		 *
		 * Tries all pathes with classname plus .php and if the class is not found,
		 * tries all pathes plus prefixes with classname plus .php
		 *
		 * @param string $class Class to be loaded
		 */
		public function load_not_namespaced( $class ) {

			foreach ( $this->pathes as $path ) {

				$file = sprintf( '%s%s.php', $path, $class );

				if ( file_exists( $file ) ) {

					require_once $file;
					break 1;

				} else {

					foreach ( $this->prefixes as $prefix ) {

						$file = sprintf( '%s%s%s.php', $path, $prefix, $class );

						if ( file_exists( $file ) ) {

							require_once $file;
							break 2;

						} // end if

					} // end foreach 2

				} // end if-else

			} // end foreach 1

		}

		/**
		 * Load namespaced classes
		 *
		 * Try to use the namespace as path, if the class is not found, try to
		 * add registered prefixes to the class
		 *
		 * @param string $class Class to be loaded
		 */
		public function load_namespaced( $class ) {

			$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );

			$file = sprintf( '%s%s.php', $this->abspath, $class );

			if ( file_exists( $file ) ) {

				require_once $file;

			} else {

				foreach ( $this->prefixes as $prefix ) {

					$replace = sprintf( '\\%s$1.php', $prefix );
					$file = preg_replace( '/\\\(.+)$/is', $replace, $class );

					if ( file_exists( $file ) ) {

						require_once $file;
						break 1;

					} // end if

				} // end foreach

			} // end if-else

		}

		/**
		 * Whether the class is namespaced or not
		 *
		 * @param string $class Class to be tested
		 * @return string 'namespaced' or 'not_namespaced', depending on the class
		 */
		public function maybe_namespaced( $class ) {

			return ( false != strpos( $class, '\\' ) ) ?
				'namespaced' : 'not_namespaced';

		}

	}
}