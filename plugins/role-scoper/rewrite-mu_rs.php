<?php

require_once( dirname(__FILE__).'/rewrite-rules_rs.php' );

/**
 * ScoperRewriteMU PHP class for the WordPress plugin Role Scoper
 * rewrite-mu_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */
class ScoperRewriteMU {
	function default_file_rule_pos($rules) {
		$default_file_redirect_rule = array();
		$default_file_redirect_rule []= 'RewriteRule ^([_0-9a-zA-Z-]+/)?files/(.+) wp-includes/ms-files.php?file=$2 [L]';  // WP 3.0 - subdomain
		$default_file_redirect_rule []= 'RewriteRule ^files/(.+) wp-includes/ms-files.php?file=$1 [L]'; // WP 3.0 - subdirectory
		
		foreach ( $default_file_redirect_rule as $default_rule ) {
			if ( $pos_def = strpos( $rules, $default_rule ) ) {
				return $pos_def;
			}
		}
		
		return false;
	}

	// directly inserts essential RS rules into the main wp-mu .htaccess file
	function update_mu_htaccess( $include_rs_rules = true ) {
		//rs_errlog( "update_mu_htaccess: arg = $include_rs_rules" );
		
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return;
		
		$include_rs_rules = $include_rs_rules && get_site_option( 'scoper_file_filtering' );	// scoper_get_option is not reliable for initial execution following plugin activation
		
		if ( ! $include_rs_rules )
			delete_site_option( 'scoper_file_filtered_sites' );
		
		//rs_errlog( "update_mu_htaccess: $include_rs_rules" );
		
		if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
			include_once( ABSPATH . '/wp-admin/includes/file.php' );
		
		$home_path = get_home_path();
		$htaccess_path = $home_path .'.htaccess';
		
		if ( ! file_exists($htaccess_path) )
			return;
			
		$contents = file_get_contents( $htaccess_path );

		if ( $pos_def = ScoperRewriteMU::default_file_rule_pos($contents) ) {	
			$fp = fopen($htaccess_path, 'w');
			
			if ( $pos_rs_start = strpos( $contents, "\n# BEGIN Role Scoper" ) )
				fwrite($fp, substr( $contents, 0, $pos_rs_start ) );
			else
				fwrite($fp, substr( $contents, 0, $pos_def ) );
			
			if ( $include_rs_rules )
				fwrite($fp, ScoperRewrite::build_site_rules(false) );
				
			fwrite($fp, substr( $contents, $pos_def ) );
	
			fclose($fp);
		}
	}
	
	// Note: this filter is never applied by WP Multisite as of WP 3.1.3
	// In case a modified or future MU regenerates the site .htaccess, filter contents to include RS rules
	function insert_site_rules( $rules = '' ) {
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return $rules;
	
		if ( get_site_option( 'scoper_file_filtering' ) ) {
			if ( $pos_def = ScoperRewriteMU::default_file_rule_pos($rules) ) {	
				$rules = substr( $rules, 0, $pos_def ) . ScoperRewrite::build_site_rules(false) . substr( $rules, $pos_def );
			}
		}

		return $rules;
	}
	
	function build_blog_file_redirects() {
		global $wpdb, $blog_id, $base;
		
		if ( ! ScoperRewrite::site_config_supports_rewrite() || defined( 'SCOPER_NO_HTACCESS' ) )
			return '';

		$new_rules = '';
		$orig_blog_id = $blog_id;	
		
		$strip_path = str_replace( '\\', '/', trailingslashit(ABSPATH) );

		require_once( dirname(__FILE__).'/analyst_rs.php' );
		
		$new_rules .= "\n#Run file requests through blog-specific .htaccess to support filtering.  Files that pass through filtering will be redirected by default WP rules.\n";
		
		$file_filtered_sites = array();
		
		$results = scoper_get_results( "SELECT blog_id, path FROM $wpdb->blogs ORDER BY blog_id" );
		
		foreach ( $results as $row ) {
			switch_to_blog( $row->blog_id );
			
			if ( $results = ScoperAnalyst::identify_protected_attachments() ) {
				$file_filtered_sites []= $row->blog_id;
			
				// WP-mu content rules are only inserted if defined uploads path matches this default structure
				$dir = ABSPATH . UPLOADBLOGSDIR . "/{$row->blog_id}/files/";
				$url = trailingslashit( $siteurl ) . UPLOADBLOGSDIR . "/{$row->blog_id}/files/";
				
				$uploads = apply_filters( 'upload_dir', array( 'path' => $dir, 'url' => $url, 'subdir' => '', 'basedir' => $dir, 'baseurl' => $url, 'error' => false ) );
				
				$content_base = str_replace( $strip_path, '', str_replace( '\\', '/', $uploads['basedir'] ) );

				$path = trailingslashit($row->path);
						
				if ( $base && ( '/' != $base ) )
					if ( 0 === strpos( $path, $base ) )
						$path = substr( $path, strlen($base) );

				// If a filter has changed basedir, don't filter file attachments for this blog
				if ( strpos( $content_base, "/blogs.dir/{$row->blog_id}/files/" ) )
					$new_rules .= "RewriteRule ^{$path}files/(.*) {$content_base}$1 [L]\n";			//RewriteRule ^blog1/files/(.*) wp-content/blogs.dir/2/files/$1 [L]
			}
		}
		
		update_site_option( 'scoper_file_filtered_sites', $file_filtered_sites );
		
		switch_to_blog( $orig_blog_id );
		
		return $new_rules;
	}
	
	
	// remove RS rules from every .htaccess file in the wp-MU "files" folders
	function clear_all_file_rules() {
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return;
		
		global $wpdb, $blog_id;
		$blog_ids = scoper_get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id" );
		$orig_blog_id = $blog_id;
	
		foreach ( $blog_ids as $id ) {
			switch_to_blog( $id );

			require_once( dirname(__FILE__).'/uploads_rs.php' );
			$uploads = scoper_get_upload_info();
			$htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';
			if ( file_exists( $htaccess_path ) )
				ScoperRewrite::insert_with_markers( $htaccess_path, 'Role Scoper', '' );
		}
		
		switch_to_blog( $orig_blog_id );
	}

}

?>