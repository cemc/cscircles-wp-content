<?php

function scoper_version_updated_from_legacy( $prev_version ) {
	// single-pass do loop to easily skip unnecessary version checks
	do {
		if ( version_compare( $prev_version, '1.0.0-rc6', '<') && version_compare( $prev_version, '1.0.0-rc2', '>=') ) {
			// In rc2 through rc4, we forced invalid img src attribute for image attachments on servers deemed non-apache
			// note: false === stripos( php_sapi_name(), 'apache' ) was the criteria used by the offending code
			// Need to update all affected post_content to convert attachment_id URL to file URL
			if ( false === stripos( php_sapi_name(), 'apache' ) && ! get_site_option('scoper_fixed_img_urls') ) {
				global $wpdb, $wp_rewrite;
	
				if ( ! empty($wp_rewrite) ) {
					$blog_url = get_bloginfo('url');
					if ( $results = $wpdb->get_results( "SELECT ID, guid, post_parent FROM $wpdb->posts WHERE post_type = 'attachment' && post_date > '2008-12-7'" ) ) {
						foreach ( $results as $row ) {
							$data = array();
							$data['post_content'] = $wpdb->get_var( "SELECT post_content FROM $wpdb->posts WHERE ID = '$row->post_parent'" );
							
							if ( $row->guid ) {
								$attachment_link_raw = $blog_url . "/?attachment_id={$row->ID}";
								$data['post_content'] = str_replace('src="' . $attachment_link_raw, 'src="' . $row->guid, $data['post_content']);
								
								$attachment_link = get_attachment_link($row->ID);
								$data['post_content'] = str_replace('src="' . $attachment_link, 'src="' . $row->guid, $data['post_content']);
							}
	
							if ( ! empty($data['post_content']) ) {
								$wpdb->update($wpdb->posts, $data, array("ID" => $row->post_parent) );
							}
						}
					}
				
					update_option('scoper_fixed_img_urls', true);
				}
			}
		} else break;
	
		// changed default teaser_hide_private otype option to separate entries for posts, pages in v1.0.0-rc4
		if ( version_compare( $prev_version, '1.0.0-rc4', '<') ) {
			$teaser_hide_private = get_option('scoper_teaser_hide_private');
	
			if ( isset($teaser_hide_private['post']) && ! is_array($teaser_hide_private['post']) ) {
				if ( $teaser_hide_private['post'] )
					// despite "for posts and pages" caption, previously this option caused pages to be hidden but posts still teased
					update_option( 'scoper_teaser_hide_private', array( 'post:post' => 0, 'post:page' => 1 ) );
				else
					update_option( 'scoper_teaser_hide_private', array( 'post:post' => 0, 'post:page' => 0 ) );
			}
		} else break;
		
		// 0.9.15 eliminated ability to set recursive page parents
		if ( version_compare( $prev_version, '0.9.15', '<') ) { 
			scoper_fix_page_parent_recursion();
		} else break;
		
		
		// added WP role metagroups in v0.9.9
		if ( ( ! empty($prev_version) && version_compare( $prev_version, '0.9.9', '<') ) ) {
			global $wp_roles;
			
			if ( ! empty($wp_roles) )
				scoper_sync_wproles();
				
		} else break;
	} while ( 0 ); // end single-pass version check loop
} // end function
	

// legacy function called when upgrading from versions older than 0.9.15
function scoper_fix_page_parent_recursion() {
	global $wpdb;
	$arr_parent = array();
	$arr_children = array();
	
	if ( $results = scoper_get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page'") ) {
		foreach ( $results as $row ) {
			$arr_parent[$row->ID] = $row->post_parent;
			
			if ( ! isset($arr_children[$row->post_parent]) )
				$arr_children[$row->post_parent] = array();
				
			$arr_children[$row->post_parent] []= $row->ID;
		}
		
		// if a page's parent is also one of its children, set parent to Main
		foreach ( $arr_parent as $page_id => $parent_id )
			if ( isset($arr_children[$page_id]) && in_array($parent_id, $arr_children[$page_id]) )
				scoper_query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_parent = '0' WHERE ID = %d", $page_id ) );
	}
}

?>