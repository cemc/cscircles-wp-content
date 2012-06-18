<?php
class Relevanssi_Search_Filter_RS {
	var $valid_stati = array();
	var $relevanssi_results = array();
	
	function Relevanssi_Search_Filter_RS() {
		$this->valid_stati = array_merge( get_post_stati( array( 'public' => true ) ), get_post_stati( array( 'private' => true ) ) );

		remove_filter( 'relevanssi_post_ok', 'relevanssi_default_post_ok' );
		add_filter( 'relevanssi_post_ok', array( &$this, 'relevanssi_post_ok' ) );
		add_filter( 'relevanssi_results', array( &$this, 'relevanssi_log_results' ) );
	}

	function relevanssi_log_results( $arr ) {
		$this->relevanssi_results = $arr;
		
		global $wpdb;
		$id_clause = "AND ID IN( '" . implode( "','", array_keys($arr) ) . "')";
		$results = $wpdb->get_results( "SELECT ID, post_name, post_type, post_status, post_author, post_parent FROM $wpdb->posts WHERE 1=1 $id_clause" );
		
		foreach( $results as $row ) {
			wp_cache_add( $row->ID, $row, 'posts' );
		}
		
		return $arr;
	}
	
	function relevanssi_post_ok($doc) {
		static $set_listed_ids = false;
	
		if ( ! $set_listed_ids ) {
			$set_listed_ids = true;
			$GLOBALS['scoper']->listed_ids['post'] = array_fill_keys( array_keys($this->relevanssi_results), true );
		}

		if ( function_exists('relevanssi_s2member_level') ) {
			if ( relevanssi_s2member_level($doc) == 0 ) return false; // back compat with relevanssi_default_post_ok, in case somebody is also running s2member
		}
		
		$status = relevanssi_get_post_status($doc);

		if ( in_array( $status, $this->valid_stati ) )
			$post_ok = current_user_can( 'read_post', $doc );
		else
			$post_ok = false;

		return $post_ok;
	}
}
?>