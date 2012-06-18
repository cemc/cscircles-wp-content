<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	_e( 'This file cannot be executed directly.', 'scoper' );

function scoper_requested_file_rule_expire() {
	if ( scoper_get_option( 'file_filtering' ) ) {
		if ( $key = scoper_get_option( 'file_filtering_regen_key' ) ) {
			if ( ! empty($_GET['key']) && ( $key == $_GET['key'] ) ) {  // user must store their own non-null key before this will work
				global $wpdb;
				
				if ( IS_MU_RS ) {
					$blog_ids = scoper_get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id" );
					$orig_blog_id = $GLOBALS['blog_id'];
				
					foreach ( $blog_ids as $id ) {
						switch_to_blog( $id );
						scoper_query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_rs_file_key'" );
					}
				} else {
					scoper_query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_rs_file_key'" );
				}

				scoper_expire_file_rules();

				if ( IS_MU_RS )
					_e( "File attachment access keys and rewrite rules will be regenerated for each site at next access.", 'scoper' );
				else
					_e( "File attachment access keys and rewrite rules were regenerated.", 'scoper' );
			} else
				_e( 'Invalid argument.', 'scoper' );
		} else
			_e( 'Please configure File Filtering options!', 'scoper' );
	} else {
		_e( 'The function is disabled.', 'scoper' );
	}
	exit(0);
}
?>