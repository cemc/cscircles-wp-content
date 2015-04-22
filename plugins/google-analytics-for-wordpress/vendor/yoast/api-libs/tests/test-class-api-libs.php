<?php

class Yoast_Api_Libs_Test extends GA_UnitTestCase {

	private $loaded_api_libs = 0;

	/**
	 * Register API libs in the construct, so we don't need to do that every time
	 */
	public function __construct() {
		$load_libraries        = array( 'googleanalytics' );
		$this->loaded_api_libs = count( $load_libraries );

		Yoast_Api_Libs::load_api_libraries( $load_libraries );

		parent::__construct();
	}

	/**
	 * Count the result of activated API libs
	 */
	public function test_get_api_libs() {
		$total_active = count( Yoast_Api_Libs::get_api_libs() );

		$this->assertEquals( $this->loaded_api_libs, 1 );
	}

}