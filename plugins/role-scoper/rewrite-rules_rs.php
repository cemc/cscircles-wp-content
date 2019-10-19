<?php

/**
 * ScoperRewrite PHP class for the WordPress plugin Role Scoper
 * rewrite-rules_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */
 
class ScoperRewrite {
	
	public static function insert_with_markers( $file_path, $marker_text, $insertion ) {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/misc.php' );
			else
				return;
		}

		if ( $insertion || file_exists($file_path) ) {
			if ( ! $insertion ) { // if no insertion and no existing entry, don't mess
				$htcontent = file_get_contents($file_path);
				if ( false === strpos( $htcontent, $marker_text ) )
					return;
			}
		
			insert_with_markers( $file_path, $marker_text, explode( "\n", $insertion ) );
		}
	}
	
	
	public static function update_site_rules( $include_rs_rules = true ) {
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return;
		
		$const_name = ( $include_rs_rules ) ? 'FLUSHING_RULES_RS' : 'CLEARING_RULES_RS';
			
		if ( defined( $const_name ) )
			return;
	
		define( $const_name, true );

		// Avoid file corruption by skipping if another flush was initiated < 5 seconds ago
		// Currently, this could leave .htaccess out of sync with settings (following an unusual option updating sequence), but that's the lesser failure
		if ( $last_regen = get_site_option( 'scoper_main_htaccess_date' ) ) {
			if ( intval($last_regen) > agp_time_gmt() - 5 ) {
				return;
			}
		}
		update_site_option( 'scoper_main_htaccess_date', agp_time_gmt() );  // stores to site_meta table for network installs.  Note: scoper_update_site_option is NOT equivalent
		
		$include_rs_rules = $include_rs_rules && scoper_get_option( 'file_filtering' );
		
		// sleep() time is necessary to avoid .htaccess file i/o race conditions since other plugins (W3 Total Cache) may also perform or trigger .htaccess update, and those file operations don't all use flock
		// This update only occurs on plugin activation, the first time a MS site has an attachment to a private/restricted page, and on various plugin option changes.
		if ( IS_MU_RS && ( ! awp_ver('3.5') || get_site_option( 'ms_files_rewriting' ) ) ) {
			add_action( 'shutdown', create_function( '', "sleep(2); require_once( dirname(__FILE__).'/rewrite-mu_rs.php' ); ScoperRewriteMU::update_mu_htaccess( '$include_rs_rules' );" ) );
		} else {
			if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/misc.php' );
			
			if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
				include_once( ABSPATH . '/wp-admin/includes/file.php' );

			add_action( 'shutdown', create_function( '', 'global $wp_rewrite; if ( ! did_action("delete_option_rewrite_rules") && ! empty($wp_rewrite) ) { sleep(2); $wp_rewrite->flush_rules(true); }' ), 999 );
		}
	}
	
	public static function build_site_rules( $ifmodule_wrapper = true ) {
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return;
	
		$http_auth = scoper_get_option( 'feed_link_http_auth' );
		$filtering = IS_MU_RS && ( ! awp_ver('3.5') || get_site_option( 'ms_files_rewriting' ) ) && get_site_option( 'scoper_file_filtering' );	// scoper_get_option is not reliable for initial execution following plugin activation
		
		$new_rules = '';
		
		if ( $http_auth || $filtering ) {
			require_once( dirname(__FILE__).'/uploads_rs.php' );
	
			$new_rules = "\n# BEGIN Role Scoper\n";
			
			if ( $ifmodule_wrapper )
				$new_rules .= "<IfModule mod_rewrite.c>\n";
	
			$new_rules .= "RewriteEngine On\n\n";

			if ( $http_auth ) {
				// workaround for HTTP Authentication with PHP running as CGI
				$new_rules .= "RewriteCond %{HTTP:Authorization} ^(.*)\n";
				$new_rules .= "RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]\n";
			}
		
			if ( $filtering ) {
				$new_rules .= ScoperRewriteMU::build_blog_file_redirects();
			}
			
			if ( $ifmodule_wrapper )
				$new_rules .= "</IfModule>\n";
			
			$new_rules .= "\n# END Role Scoper\n\n";
		}
	
		return $new_rules;
	}

	
	public static function site_config_supports_rewrite() {
		require_once( dirname(__FILE__).'/uploads_rs.php' );
		$uploads = scoper_get_upload_info();
		
		if ( false === strpos( $uploads['baseurl'], untrailingslashit( get_option('siteurl') ) ) )
			return false;
		
		// don't risk leaving custom .htaccess files in content folder at deactivation due to difficulty of reconstructing custom path for each blog
		if ( IS_MU_RS && ( ! awp_ver('3.5') || get_site_option( 'ms_files_rewriting' ) ) ) {
			global $blog_id;
			
			if ( 'site-new.php' == $GLOBALS['pagenow'] )
				return true;

			if ( UPLOADS != UPLOADBLOGSDIR . "/$blog_id/files/" )
				return false;
				
			if ( BLOGUPLOADDIR != WP_CONTENT_DIR . "/blogs.dir/$blog_id/files/" )
				return false;
		}
		
		return true;
	}
	
	public static function update_blog_file_rules( $include_rs_rules = true ) {
		global $blog_id;
		
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return;
		
		// avoid file collision by skipping if another flush was initiated < 5 seconds ago
		if ( $last_regen = scoper_get_option( 'file_htaccess_date' ) ) {
			if ( intval($last_regen) > agp_time_gmt() - 5  ) {
				return;
			}
		}
		
		scoper_update_option( 'file_htaccess_date', agp_time_gmt() );
		
		$include_rs_rules = $include_rs_rules && scoper_get_option( 'file_filtering' );
		
		if ( ! self::site_config_supports_rewrite() ) {
			return;
		} elseif ( ! $include_rs_rules )
			$rules = '';
		else {
			$rules = self::build_blog_file_rules();
		}
			
		require_once( dirname(__FILE__).'/uploads_rs.php' );
		$uploads = scoper_get_upload_info();
		
		// If a filter has changed MU basedir, don't filter file attachments for this blog because we might not be able to regenerate the basedir for rule removal at RS deactivation
		if ( ! IS_MU_RS || ( awp_ver('3.5') && ! get_site_option( 'ms_files_rewriting' ) ) || strpos( $uploads['basedir'], "/blogs.dir/$blog_id/files" ) || ( false !== strpos( $uploads['basedir'], trailingslashit(WP_CONTENT_DIR) . 'uploads' ) ) ) {
			$htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';
			ScoperRewrite::insert_with_markers( $htaccess_path, 'Role Scoper', $rules );
		}
	}
	
	static function &build_blog_file_rules() {
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return '';
		
		$new_rules = '';

		require_once( dirname(__FILE__).'/analyst_rs.php' );
		if ( ! $attachment_results = ScoperAnalyst::identify_protected_attachments() )
			return $new_rules;
			
		global $wpdb;

		require_once( dirname(__FILE__).'/uploads_rs.php' );
		
		$home_root = parse_url(get_option('home'));
		$home_root = trailingslashit( $home_root['path'] );
		
		$uploads = scoper_get_upload_info();
		
		$baseurl = trailingslashit( $uploads['baseurl'] );
		
		$arr_url = parse_url( $baseurl );
		$rewrite_base = $arr_url['path'];
		
		$file_keys = array();
		$has_postmeta = array();
		
		if ( $key_results = scoper_get_results( "SELECT pm.meta_value, p.guid, p.ID FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON p.ID = pm.post_id WHERE pm.meta_key = '_rs_file_key'" ) ) {
			foreach ( $key_results as $row ) {
				$file_keys[$row->guid] = $row->meta_value;
				$has_postmeta[$row->ID] = $row->meta_value;
			}
		}
	
		$new_rules = "<IfModule mod_rewrite.c>\n";
		$new_rules .= "RewriteEngine On\n";
		$new_rules .= "RewriteBase $rewrite_base\n\n";
	
		$main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&rs_rewrite=1 [NC,L]\n";
	
		$htaccess_urls = array();
	
		foreach ( $attachment_results as $row ) {
			if ( false !== strpos( $row->guid, $baseurl ) ) {	// no need to include any attachments which are not in the uploads folder
				if ( ! empty($file_keys[ $row->guid ] ) ) {
					$key = $file_keys[ $row->guid ];
				} else {
					$key = urlencode( str_replace( '.', '', uniqid( strval( rand() ), true ) ) );
					$file_keys[ $row->guid ] = $key;
				}

				if ( ! isset( $has_postmeta[$row->ID] ) || ( $key != $has_postmeta[$row->ID] ) )
					update_post_meta( $row->ID, "_rs_file_key", $key );

				if ( isset( $htaccess_urls[$row->guid] ) )  // if a file is attached to multiple protected posts, use a single rewrite rule for it
					continue;

				$htaccess_urls[$row->guid] = true;
				
				$rel_path =  str_replace( $baseurl, '', $row->guid );
				
				// escape spaces
				$file_path =  str_replace( ' ', '\s', $rel_path );
				
				// escape horiz tabs (yes, at least one user has them in filenames)
				$file_path =  str_replace( chr(9), '\t', $file_path );

				// strip out all other nonprintable characters.  Affected files will not be filtered, but we avoid 500 error.  Possible TODO: advisory in file attachment utility
				$file_path =  preg_replace( '/[\x00-\x1f\x7f]/', '', $file_path );

				// escape all other regular expression operator characters
				$file_path =  preg_replace( '/[\^\$\.\+\[\]\(\)\{\}]/', '\\\$0', $file_path );

				$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$file_path" . "$ [NC]\n";
				$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
				$new_rules .= $main_rewrite_rule;

				if ( $pos_ext = strrpos( $file_path, '\.' ) ) {
					$thumb_path = substr( $file_path, 0, $pos_ext );
					$ext = substr( $file_path, $pos_ext + 2 );	

					$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '-[0-9]{2,4}x[0-9]{2,4}\.' . $ext . "$ [NC]\n";
					$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
					$new_rules .= $main_rewrite_rule;
					
					// if resized image file(s) exist, include rules for them
					$guid_pos_ext = strrpos( $rel_path, '.' );
					$pattern = $uploads['path'] . '/' . substr( $rel_path, 0, $guid_pos_ext ) . '-??????????????' . substr( $rel_path, $guid_pos_ext );
					if ( glob( $pattern ) ) {
						$new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '-[0-9,a-f]{14}\.' . $ext . "$ [NC]\n";
						$new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
						$new_rules .= $main_rewrite_rule;
					}
				}
			}
		} // end foreach protected attachment

		if ( IS_MU_RS && ( ! awp_ver('3.5') || get_site_option( 'ms_files_rewriting' ) ) ) {
			global $blog_id;
			$file_filtered_sites = (array) get_site_option( 'scoper_file_filtered_sites' );
			if ( ! in_array( $blog_id, $file_filtered_sites ) ) {
				// this site needs a file redirect rule in root .htaccess
				scoper_flush_site_rules();
			}
		
			if ( defined('SCOPER_MU_FILE_PROCESSING') ) { // unless SCOPER_MU_FILE_PROCESSING is defined (indicating blogs.php has been modified for compatibility), blogs.php processing will be bypassed for all files
				$content_path = trailingslashit( str_replace( $strip_path, '', str_replace( '\\', '/', WP_CONTENT_DIR ) ) );

				$new_rules .= "\n# Default WordPress cache handling\n";
				$new_rules .= "RewriteRule ^(.*) {$content_path}blogs.php?file=$1 [L]\n";
			}
		}
		
		$new_rules .= "</IfModule>\n";
		
		return $new_rules;
	}
	
	// called by agp_return_file() in abnormal cases where file access is approved, but key for protected file is lost/corrupted in postmeta record or .htaccess file
	public static function resync_file_rules() {
		if ( defined( 'SCOPER_NO_HTACCESS' ) )
			return;
	
		// Don't allow this to execute too frequently, to prevent abuse or accidental recursion
		if ( agp_time_gmt() - get_option( 'last_htaccess_resync_rs' ) > 30 ) {
			update_option( 'last_htaccess_resync_rs', agp_time_gmt() );
			
			// Only the files / uploads .htaccess for current blog is regenerated
			scoper_flush_file_rules();
			
			usleep(10000); // Allow 10 milliseconds for server to regather itself following .htaccess update
		} 
	}
} // end class ScoperRewrite
?>