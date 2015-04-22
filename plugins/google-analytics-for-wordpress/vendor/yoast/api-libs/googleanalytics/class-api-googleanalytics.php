<?php

class Yoast_Api_Googleanalytics {

	/**
	 * This class will be loaded when someone calls the API library with the Google analytics module
	 */
	public function __construct() {
		$this->load_api_oauth_files();
	}

	/**
	 * Register the Autoload the Oauth classes
	 */
	private function load_api_oauth_files() {
		spl_autoload_register( array( $this, 'autoload_api_oauth_files' ) );
	}

	/**
	 * Autoload the API Oauth classes
	 *
	 * @param string $class_name - The class that should be loaded
	 */
	private function autoload_api_oauth_files( $class_name ) {
		$path        = dirname( __FILE__ );
		$class_name  = strtolower( $class_name );
		$oauth_files = array(
			'yoast_googleanalytics_reporting' => 'class-googleanalytics-reporting',
			'yoast_google_analytics_client'   => 'class-google-analytics-client',
		);

		if ( ! empty( $oauth_files[$class_name] ) ) {
			if ( file_exists( $path . '/' . $oauth_files[$class_name] . '.php' ) ) {
				require_once( $path . '/' . $oauth_files[$class_name] . '.php' );
			}

		}

	}

}