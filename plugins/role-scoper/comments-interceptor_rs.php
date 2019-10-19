<?php
add_filter( 'comments_clauses', array( 'CommentsInterceptor_RS', 'flt_comments_clauses' ), 10, 2 );
add_filter( 'wp_count_comments', array( 'CommentsInterceptor_RS', 'wp_count_comments_override'), 99, 2 );

class CommentsInterceptor_RS {
	public static function flt_comments_clauses( $clauses, $qry_obj ) {
		global $wpdb;
		
		if ( did_action( 'comment_post' ) )  // don't filter comment retrieval for email notification
			return $clauses;
		
		if ( is_admin() && defined( 'SCOPER_NO_COMMENT_FILTERING' ) && empty( $GLOBALS['current_user']->allcaps['moderate_comments'] ) )
			return $clauses;

		if ( empty( $clauses['join'] ) || ! strpos( $clauses['join'], $wpdb->posts ) )
			$clauses['join'] .= "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
		
		// for WP 3.1 and any manual 3rd-party join construction (subsequent filter will expand to additional statuses as appropriate)
		$clauses['where'] = preg_replace( "/ post_status\s*=\s*[']?publish[']?/", " $wpdb->posts.post_status = 'publish'", $clauses['where'] );

		// performance enhancement: simplify comments query if there are no attachment comments to filter (TODO: cache this result)
		$qry_any_attachment_comments = "SELECT ID FROM $wpdb->posts AS p INNER JOIN $wpdb->comments AS c ON p.ID = c.comment_post_ID WHERE p.post_type = 'attachment' LIMIT 1";

		$post_type_arg = ( isset( $qry_obj->query_vars['post_type'] ) ) ? $qry_obj->query_vars['post_type'] : '';
		
		$post_id = ( ! empty( $qry_obj->query_vars['post_id'] ) ) ? $qry_obj->query_vars['post_id'] : 0;

		$attachment_query = ( 'attachment' == $post_type_arg ) || ( $post_id && ( 'attachment' == get_post_field( 'post_type', $post_id ) ) );

		$args = array( 'skip_teaser' => true );

		if ( is_admin() ) {
			require_once( 'admin/comments-interceptor-admin_rs.php' );
			$args['force_reqd_caps'] = CommentsInterceptorAdmin_RS::get_reqd_caps();
		}
		
		// $attachment_query: current query is for attachment post type, or for a specific post which is an attachment
		// $post_id: current query is for a specific post
		// 	 NOTE: even if not $attachment_query, attachment comments are included by default along with comments on other post types (i.e. for Recent Comments sidebar)
		if ( defined('SCOPER_NO_ATTACHMENT_COMMENTS') || ( ! $attachment_query && ( $post_id || ( ! defined('SCOPER_ATTACHMENT_COMMENTS') && ! scoper_get_var($qry_any_attachment_comments) ) ) ) ) {
			$clauses['where'] = " AND " . $clauses['where'];
			$clauses['where'] = "1=1" . apply_filters('objects_where_rs', $clauses['where'], 'post', $post_type_arg, $args );
		} else {
			if ( false === strpos( $clauses['fields'], 'DISTINCT ' ) )
				$clauses['fields'] = 'DISTINCT ' . $clauses['fields'];
			
			if ( $post_type_arg )
				$post_types = (array) $post_type_arg;
			else
				$post_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
			
			$post_type_in = "'" . implode( "','", $post_types ) . "'";

			$clauses['join'] .= " LEFT JOIN $wpdb->posts as parent ON parent.ID = {$wpdb->posts}.post_parent AND parent.post_type IN ($post_type_in) AND $wpdb->posts.post_type = 'attachment'";

			$use_post_types = scoper_get_option( 'use_post_types' );
			
			$where = array();
			foreach( $post_types as $type ) {
				if ( ! empty( $use_post_types[$type] ) )
					$where_post = apply_filters('objects_where_rs', '', 'post', $type, $args );
				else
					$where_post = "AND 1=1";
				
				$where[]= "$wpdb->posts.post_type = '$type' $where_post";
				
				if ( ! defined( 'SCOPER_PUBLIC_ATTACHMENT_COMMENTS' ) )
					$where[]= "$wpdb->posts.post_type = 'attachment' AND parent.post_type = '$type' " . str_replace( "$wpdb->posts.", "parent.", $where_post );
			}

			if ( defined( 'SCOPER_PUBLIC_ATTACHMENT_COMMENTS' ) )
				$where[]= "$wpdb->posts.post_type = 'attachment' AND parent.post_status = 'publish'";

			$clauses['where'] = preg_replace( "/\s*AND\s*{$wpdb->posts}.post_status\s*=\s*[']?publish[']?/", "", $clauses['where'] );
			$clauses['where'] .= ' AND ( ' . agp_implode( ' ) OR ( ', $where, ' ( ', ' ) ' ) . ' )';
		}
		
		return $clauses;
	}
	
	public static function wp_count_comments_clauses( $clauses, $qry_obj ) {
		if ( ! strpos( $clauses['where'], 'GROUP BY' ) ) {
			$clauses['fields'] = 'comment_approved, COUNT( * ) AS num_comments';
			$clauses['where'] .= ' GROUP BY comment_approved';
		}
		return $clauses;
	}
	
	// force wp_count_comments() through WP_Comment_Query filtering
	public static function wp_count_comments_override( $comments, $post_id = 0 ) {
		add_filter( 'comments_clauses', array( 'CommentsInterceptor_RS', 'wp_count_comments_clauses' ), 99, 2 );
		$count = get_comments( array( 'post_id' => $post_id ) );
		remove_filter( 'comments_clauses', array( 'CommentsInterceptor_RS', 'wp_count_comments_clauses' ), 99, 2 );
		
		// remainder of this function ported from WP 3.2 function wp_count_comments()
	
		$total = 0;
		$approved = array('0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed');
		foreach ( (array) $count as $row ) {
			$row = (array) $row;  // RS modification
		
			if ( isset( $row['num_comments'] ) ) {
				// Don't count post-trashed toward totals
				if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )
					$total += $row['num_comments'];
				if ( isset( $approved[$row['comment_approved']] ) )
					$stats[$approved[$row['comment_approved']]] = $row['num_comments'];
			}
		}

		$stats['total_comments'] = $total;
		foreach ( $approved as $key ) {
			if ( empty($stats[$key]) )
				$stats[$key] = 0;
		}

		$stats = (object) $stats;

		return $stats;
	}
}
?>