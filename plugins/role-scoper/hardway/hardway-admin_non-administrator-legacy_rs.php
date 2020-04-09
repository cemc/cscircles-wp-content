<?php
if ( 'nav-menus.php' != $GLOBALS['pagenow'] )	// nav-menus.php only needs admin_referer check.
	ScoperAdminHardway_Ltd_Legacy::add_filters();

class ScoperAdminHardway_Ltd_Legacy {
	public static function add_filters() {
		// URIs ending in specified filename will not be subjected to low-level query filtering
		$nomess_uris = apply_filters( 'scoper_skip_lastresort_filter_uris', array( 'categories.php', 'themes.php', 'plugins.php', 'profile.php', 'link.php' ) );
		
		if ( empty( $_POST['ps'] ) )	// need to filter Find Posts query in Media Library
			$nomess_uris = array_merge($nomess_uris, array('admin-ajax.php'));
		
		if ( ! in_array( $GLOBALS['pagenow'], $nomess_uris ) && ! in_array( $GLOBALS['plugin_page_cr'], $nomess_uris ) )
			add_filter('query', array('ScoperAdminHardway_Ltd_Legacy', 'flt_last_resort_query') );	
			
		// limit these links on post/page edit listing to drafts which current user can edit
		add_filter('get_others_drafts', array('ScoperAdminHardway_Ltd_Legacy', 'flt_get_others_drafts'), 50, 1);
	}

	public static function flt_last_resort_query($query) {
		static $in_process = false;
		
		if ( $in_process )
			return $query;
			
		$in_process = true;
		$query = self::_flt_last_resort_query($query);
		$in_process = false;
		return $query;
	}
	
	static function _flt_last_resort_query($query) {
		global $wpdb, $pagenow, $scoper;
		
		$posts = $wpdb->posts;
		$comments = $wpdb->comments;
		$links = $wpdb->links;
		$term_taxonomy = $wpdb->term_taxonomy;
	
		// WP 3.0:  SELECT * FROM wp_comments c LEFT JOIN wp_posts p ON c.comment_post_ID = p.ID WHERE p.post_status != 'trash' AND ( c.comment_approved = '0' OR c.comment_approved = '1' ) ORDER BY c.comment_date_gmt
		// 
		if ( strpos($query, "ELECT ") && preg_match ("/FROM\s*{$GLOBALS['wpdb']->comments}/", $query)
		&& ( ! strpos($query, "ELECT COUNT") || empty( $_POST ) )
		&& ( ! strpos($_SERVER['SCRIPT_FILENAME'], 'p-admin/upload.php') )
		 )  // don't filter the comment count query prior to DB storage of comment_count to post record
		{
			//define( 'SCOPER_NO_COMMENT_FILTERING', true );
			if ( defined( 'SCOPER_NO_COMMENT_FILTERING' ) && empty( $GLOBALS['current_user']->allcaps['moderate_comments'] ) ) {
				return $query;			
			}
			
			// cache the filtered results for pending comment count query, which (as of WP 3.0.1) is executed once per-post in the edit listing
			$post_id = 0;
			if ( $doing_pending_comment_count = strpos( $query, 'COUNT(comment_ID)' ) && strpos( $query, 'comment_post_ID' ) && strpos( $query, "comment_approved = '0'" ) ) {
				if ( 'index.php' != $pagenow ) {	// there's too much happening on the dashboard (and too much low-level query filtering) to buffer listed IDs reliably.
					if ( preg_match( "/comment_post_ID IN \( '([0-9]+)' \)/", $query, $matches ) ) {
						if ( $matches[1] )
							$post_id = $matches[1];
					}			
				}
				
				if ( $post_id ) {
					static $cache_pending_comment_count;
						
					if ( ! isset($cache_pending_comment_count) ) {
						$cache_pending_comment_count = array();
					
					} elseif ( isset( $cache_pending_comment_count[$post_id] ) ) {
						return "SELECT $post_id AS comment_post_ID, {$cache_pending_comment_count[$post_id]} AS num_comments";
					}
				}
			}
			
			$comment_alias = ( strpos( $query, "$comments c" ) || strpos( $query, "$comments AS c" ) ) ? 'c' : $comments;
			
			// apply DISTINCT clause so JOINs don't cause redundant comment count
			$query = str_replace( "SELECT *", "SELECT DISTINCT $comment_alias.*", $query);
			$query = str_replace( "SELECT SQL_CALC_FOUND_ROWS *", "SELECT SQL_CALC_FOUND_ROWS DISTINCT $comment_alias.*", $query);
		
			if ( ! strpos( $query, ' DISTINCT ' ) )
				$query = str_replace( "SELECT ", "SELECT DISTINCT ", $query);

			//$query = str_replace( "COUNT(*)", " COUNT(DISTINCT $comments.comment_ID)", $query);				// TODO: confirm preg_replace works and str_replace is not needed
			//$query = str_replace( "COUNT(comment_ID)", " COUNT(DISTINCT $comments.comment_ID)", $query);
			$query = preg_replace( "/COUNT(\s*\*\s*)/", " COUNT(DISTINCT $comments.comment_ID)", $query);
			$query = preg_replace( "/COUNT(\s*comment_ID\s*)/", " COUNT(DISTINCT $comments.comment_ID)", $query);

			$query = str_replace( " user_id ", " $comment_alias.user_id ", $query);
			
			if ( ! strpos( $query, "JOIN $posts" ) ) {
				if ( strpos( $query, "$comments c" ) )
					$query = preg_replace( "/FROM\s*{$comments} c\s*WHERE /", "FROM $comments c INNER JOIN $posts ON $posts.ID = $comment_alias.comment_post_ID WHERE ", $query);
				else
					$query = preg_replace( "/FROM\s*{$comments}\s*WHERE /", "FROM $comments INNER JOIN $posts ON $posts.ID = $comment_alias.comment_post_ID WHERE ", $query);
				
				if ( strpos( $query, "GROUP BY" ) )
					$query = preg_replace( "/FROM\s*{$comments}\s*GROUP BY /", "FROM $comments INNER JOIN $posts ON $posts.ID = $comment_alias.comment_post_ID GROUP BY ", $query);
			}

			$generic_uri = in_array( $pagenow, array( 'index.php', 'comments.php' ) );

			if ( ! $generic_uri && ( $_post_type = cr_find_post_type( '', false ) ) )  // arg: don't return 'post' as default if detection fails
				$post_types = array( $_post_type => get_post_type_object( $_post_type ) );
			else
				$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );

			$post_statuses = get_post_stati( array( 'internal' => null ), 'object' );
				
			$reqd_caps = array();
			
			$use_post_types = scoper_get_option( 'use_post_types' );
			
			foreach( $post_types as $_post_type => $type_obj ) {
				if ( empty( $use_post_types[$_post_type] ) )
					continue;
					
				foreach ( $post_statuses as $status => $status_obj ) {
					$reqd_caps[$_post_type][$status] = array( $type_obj->cap->edit_others_posts, 'moderate_comments' );
					
					if ( $status_obj->private )
						$reqd_caps[$_post_type][$status] []= $type_obj->cap->edit_private_posts;
						
					$status_name = ( ( 'publish' == $status ) || ( 'future' == $status ) ) ? 'published' : $status;

					$property = "edit_{$status_name}_posts";
					if ( ! empty( $type_obj->cap->$property ) && ! in_array( $type_obj->cap->$property, $reqd_caps[$_post_type][$status] ) )
						$reqd_caps[$_post_type][$status] []= $type_obj->cap->$property;
				}
			}

			$args = array( 'force_reqd_caps' => $reqd_caps );
			
			if ( strpos( $query, "$posts p" ) || strpos( $query, "$posts AS p" ) )
				$args['source_alias'] = 'p';
	
			$object_type = ( 'edit.php' == $pagenow ) ? cr_find_post_type() : '';
			$query = apply_filters( 'objects_request_rs', $query, 'post', $object_type, $args );
			
			// pre-execute the comments listing query and buffer the listed IDs for more efficient user_has_cap calls
			if ( strpos( $query, "* FROM $comments") && empty($scoper->listed_ids['post']) ) {
				if ( $results = scoper_get_results($query) ) {
					$scoper->listed_ids['post'] = array();
					
					foreach ( $results as $row ) {
						if ( ! empty($row->comment_post_ID) )
							$scoper->listed_ids['post'][$row->comment_post_ID] = true;
					}
				}
			} elseif ( $doing_pending_comment_count && $post_id ) {	
				if ( isset($scoper->listed_ids['post']) )
					$listed_ids = array_keys($scoper->listed_ids['post']);
				elseif ( ! empty($GLOBALS['wp_object_cache']->cache['posts']) && is_array($GLOBALS['wp_object_cache']->cache['posts']) )
					$listed_ids = array_keys($GLOBALS['wp_object_cache']->cache['posts']);
				else
					$listed_ids = array();
					
				// make sure our current post_id is in the list
				$listed_ids[] = $post_id;

				if ( count( $listed_ids ) > 1 ) {
					// cache the pending comment count for all listed posts
					$query = str_replace( "comment_post_ID IN ( '$post_id' )", "comment_post_ID IN ( '" . implode( "','", $listed_ids ) . "' )", $query );
					$results = scoper_get_results( $query );

					$cache_pending_comment_count = array_fill_keys( $listed_ids, 0 );

					foreach( $results as $row )
						$cache_pending_comment_count[ $row->comment_post_ID ] = $row->comment_count;
				}
			}

			//d_echo( "<br />replaced: $query<br />" );
			
			//rs_errlog ("<br /><br />replaced with $query<br /><br />");

		} // endif matched query substring
		
		// num cats: "SELECT COUNT(*) FROM wp_term_taxonomy"
		// SELECT DISTINCT COUNT(tt.term_id) FROM wp_term_taxonomy AS tt WHERE 1=1 AND tt.taxonomy = 'category' 
		// SELECT DISTINCT tt.term_id FROM wp_term_taxonomy AS tt WHERE
		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php') ) && ! defined('XMLRPC_REQUEST') ) {
			if ( strpos($query, " FROM $term_taxonomy") || strpos($query, " FROM $wpdb->terms") ) 
			{
				//rs_errlog ("<br />caught $query <br />");

				// don't mess with parent category selection/availability for single term edit
				$is_term_admin = ( in_array( $pagenow, array( 'edit-tags.php', 'edit-link-categories.php' ) ) );
				if ( $is_term_admin ) {
					if ( ! empty( $_REQUEST['tag_ID'] ) )
						return $query;
				}

				$matches = array();
				if ( $return = preg_match( "/taxonomy IN \('(.*)'/", $query, $matches ) )
					$taxonomy = explode( "','", str_replace( ' ', '', $matches[1] ) );
				elseif ( $return = preg_match( "/taxonomy\s*=\s*'(.*)'/", $query, $matches ) )
					$taxonomy = $matches[1];
				
				if ( ! empty($taxonomy) ) {
					if ( 'profile.php' == $pagenow )
						return $query;	
					else
						$query = apply_filters( 'terms_request_rs', $query, $taxonomy, array( 'is_term_admin' => $is_term_admin ) );
				}

				//rs_errlog ("<br /><br /> returning $query <br />");
				return $query;
			}
		} 
		
		// get_users_drafts() and get_others_unpublished_posts()
		//
		// Recent posts: SELECT ID, post_title FROM wp_posts WHERE post_type = 'post' AND (post_status = 'publish' OR post_status = 'private') AND post_date_gmt < '2008-04-30 05:04:04' ORDER BY post_date DESC LIMIT 5 
		// Scheduled entries: SELECT ID, post_title, post_date_gmt FROM wp_posts WHERE post_type = 'post' AND post_status = 'future' ORDER BY post_date ASC"
		if ( 
		   ( strpos($query, "post_date_gmt <") && strpos ($query, "ELECT ID, post_title") && strpos($query, " FROM $posts WHERE ") )
		|| ( strpos ($query, "ELECT ID, post_title, post_date_gmt") && strpos($query, " FROM $posts WHERE ") ) 
		) {
			if ( $_post_type = cr_find_post_type() )
				$query = apply_filters('objects_request_rs', $query, 'post', $_post_type, '');
		}
		
		// links
		//SELECT * , IF (DATE_ADD(link_updated, INTERVAL 120 MINUTE) >= NOW(), 1,0) as recently_updated FROM wp_links WHERE 1=1 ORDER BY link_name ASC
		if ( ( strpos($query, "FROM $links WHERE") || strpos($query, "FROM $links  WHERE") ) && strpos($query, "ELECT ") ) {
			$query = apply_filters('objects_request_rs', $query, 'link', 'link');
			return $query;
		}
		
		return $query;
	} // end function
	
	// Note: this filter is never invoked by WP core as of WP 2.7
	public static function flt_get_others_drafts($results) {
		global $wpdb, $current_user, $scoper;
		
		// buffer titles in case they were filtered previously
		$titles = scoper_get_property_array( $results, 'ID', 'post_title' );
		
		// WP 2.3 added pending status, but no new hook or hook argument
		$draft_query = strpos($wpdb->last_query, 'draft');
		$pending_query = strpos($wpdb->last_query, 'pending');
		
		if ( $draft_query && $pending_query )
			$status_clause = "AND ( post_status = 'draft' OR post_status = 'pending' )";
		elseif ( $draft_query )
			$status_clause = "AND post_status = 'draft'";
		else
			$status_clause = "AND post_status = 'pending'";
		
		$object_type = cr_find_post_type();
		if ( ! $object_type )
			$object_type = 'post';
			
		if ( ! $otype_val = $scoper->data_sources->member_property('post', 'object_types', $object_type, 'val') )
			$otype_val = $object_type;
			
		$qry = "SELECT ID, post_title, post_author FROM $wpdb->posts WHERE post_type = '$otype_val' AND post_author != '$current_user->ID' $status_clause";
		$qry = apply_filters('objects_request_rs', $qry, 'post', '', '');
		
		$items = scoper_get_results($qry);
		
		// restore buffered titles in case they were filtered previously
		scoper_restore_property_array( $items, $titles, 'ID', 'post_title' );

		return $items;
	}
} // end class

?>