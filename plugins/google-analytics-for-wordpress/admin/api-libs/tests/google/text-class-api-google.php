<?php

class Yoast_Api_Google_Test extends GA_UnitTestCase {

	/**
	 * Test the autoload functionality
	 */
	public function test_autoload(){
		$classes = array(
			'Yoast_Google_Client',
			'Yoast_Google_Model',
		);

		foreach( $classes as $class_name ) {
			$this->assertTrue( class_exists( $class_name ) );
		}
	}

}