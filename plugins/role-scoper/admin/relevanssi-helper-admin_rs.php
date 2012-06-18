<?php
class Relevanssi_Admin_Helper_RS {
	function rvi_reindex() {
		if ( function_exists( 'relevanssi_truncate_cache' ) )
			relevanssi_truncate_cache( true );

		/*  // too much overhead to do this on each save
		if ( function_exists( 'relevanssi_build_index' ) )
			add_action( 'shutdown', array( 'Relevanssi_Admin_Helper_RS', 'do_reindex' ) );
		*/
	}
	
	/*
	function do_reindex() {
		ob_start();  // as of version 2.9.12, no way to rebuild index w/o outputting message
		relevanssi_build_index();
		ob_end_clean();
	}
	*/
}
?>