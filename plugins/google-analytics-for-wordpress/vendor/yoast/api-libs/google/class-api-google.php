<?php

class Yoast_Api_Google {

	public $options;

	/**
	 * This class will be loaded when someone calls the API library with the Google analytics module
	 */
	public function __construct() {
		$this->load_api_google_files();
	}

	/**
	 * Register the Autoload the Google class
	 */
	private function load_api_google_files() {
		spl_autoload_register( array( $this, 'autoload_api_google_files' ) );
	}

	/**
	 * Autoload the API Google class
	 *
	 * @param string $class_name - The class that should be loaded
	 */
	private function autoload_api_google_files( $class_name ) {
		$path        = dirname( __FILE__ );
		$class_name  = strtolower( $class_name );
		$oauth_files = array(
			// Main requires
			'yoast_google_client'          => 'Google_Client',

			// Requires in classes
			'yoast_google_model'           => 'service/Google_Model',
			'yoast_google_service'         => 'service/Google_Service',
			'yoast_google_serviceresource' => 'service/Google_ServiceResource',
			'yoast_google_assertion'       => 'auth/Google_AssertionCredentials',
			'yoast_google_signer'          => 'auth/Google_Signer',
			'yoast_google_p12signer'       => 'auth/Google_P12Signer',
			'yoast_google_batchrequest'    => 'service/Google_BatchRequest',
			'yoast_google_uritemplate'     => 'external/URITemplateParser',
			'yoast_google_auth'            => 'auth/Google_Auth',
			'yoast_google_cache'           => 'cache/Google_Cache',
			'yoast_google_io'              => 'io/Google_IO',
			'yoast_google_mediafileupload' => 'service/Google_MediaFileUpload',
			'yoast_google_authnone'        => 'auth/Google_AuthNone',
			'yoast_google_oauth2'          => 'auth/Google_OAuth2',
			'yoast_google_verifier'        => 'auth/Google_Verifier',
			'yoast_google_loginticket'     => 'auth/Google_LoginTicket',
			'yoast_google_utils'           => 'service/Google_Utils',
			'yoast_google_pemverifier'     => 'auth/Google_PemVerifier',

			// Caching
			'yoast_google_filecache'       => 'cache/Google_FileCache',
			'yoast_google_memcachecache'   => 'cache/Google_MemcacheCache',
			'yoast_google_cacheparser'     => 'io/Google_CacheParser',

			// Requests
			'yoast_google_httprequest'     => 'io/Google_HttpRequest',
			'yoast_google_httpstream_io'   => 'io/Google_HttpStreamIO',
			'yoast_google_rest'            => 'io/Google_REST',

			// Wordpress
			'yoast_google_wpio'            => 'io/Google_WPIO',
			'yoast_google_wpcache'         => 'cache/Google_WPCache',

			// REPLACE ME!
			'yoast_google_curlio'          => 'io/Google_CurlIO',
		);

		if ( ! empty( $oauth_files[$class_name] ) ) {
			if ( file_exists( $path . '/' . $oauth_files[$class_name] . '.php' ) ) {
				require_once( $path . '/' . $oauth_files[$class_name] . '.php' );
			}

		}

	}

}