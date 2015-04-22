<?php

/**
 * Include this class to use the Yoast_Api_Libs, you can include this as a submodule in your project
 * and you just have to autoload this class
 *
 *
 * NAMING CONVENTIONS
 * - Register 'oauth' by using $this->register_api_library()
 * - Create folder 'oauth'
 * - Create file 'class-api-oauth.php'
 * - Class name should be 'Yoast_Api_Oauth'
 */
class Yoast_Api_Libs {

	/**
	 * Store the available API libraries
	 *
	 * @var array
	 */
	private static $api_libs = array();

	/**
	 * Store the instances of the API class
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Call this method to init the libraries you need
	 *
	 * @param array $libraries
	 *
	 * @return bool True when at least 1 library is registered, False when there was 1 failure or when there is no class loaded at all
	 */
	public static function load_api_libraries( $libraries = array() ) {
		$succeeded = 0;
		$failed    = 0;

		if ( is_array( $libraries ) && count( $libraries ) >= 1 ) {
			foreach ( $libraries as $lib ) {
				if ( self::register_api_library( $lib ) ) {
					$succeeded ++;
				} else {
					$failed ++;
				}
			}
		}

		if ( $succeeded >= 1 && $failed == 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the registered API libraries
	 *
	 * @return array
	 */
	public static function get_api_libs() {
		return self::$api_libs;
	}

	/**
	 * Register a new API library to this class
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	private static function register_api_library( $name ) {
		$name            = strtolower( $name );
		$classname       = 'Yoast_Api_' . ucfirst( $name );
		$classpath       = 'class-api-' . $name . '.php';
		$path_to_require = dirname( __FILE__ ) . '/' . $name . '/' . $classpath;

		// Check if the API Libs was already registered
		if ( isset( self::$api_libs[$name] ) ) {
			return true;
		}

		self::$api_libs[$name] = array(
			'name'            => $name,
			'classname'       => $classname,
			'classpath'       => $classpath,
			'path_to_require' => $path_to_require,
		);

		if ( file_exists( $path_to_require ) ) {
			include( $path_to_require );

			if ( class_exists( $classname ) ) {
				$instance = new $classname;

				self::$instances[$name] = $instance;

				return true;
			}
		}

		return false;
	}

	/**
	 * Get instance
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	public function get_instance( $name ) {
		if ( isset( self::$instances[$name] ) ) {
			return self::$instances[$name];
		}

		return false;
	}

	/**
	 * Execute a call with this method
	 *
	 * @param       $instance
	 * @param       $method
	 * @param array $params
	 */
	public static function do_call( $instance, $method, $params = array() ) {
		$class = self::$instances[$instance];

		$class->$method( $params );
		// Call user func?
	}

}
