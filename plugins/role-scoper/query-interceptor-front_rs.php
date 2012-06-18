<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

add_filter( 'get_previous_post_where', array('QueryInterceptorFront_RS', 'flt_adjacent_post_where') );
add_filter( 'get_next_post_where', array('QueryInterceptorFront_RS', 'flt_adjacent_post_where') );

add_filter( 'getarchives_where', array('QueryInterceptorFront_RS', 'flt_getarchives_where') );
	
add_filter( 'option_sticky_posts', array('QueryInterceptorFront_RS', 'flt_sticky_posts') );

add_filter( 'bp_has_activities', array('QueryInterceptorFront_RS', 'bp_has_activities'), 10, 2 );

class QueryInterceptorFront_RS {
	function bp_has_activities( $bp_activities, $bp_activities_template ) {
		require_once( dirname(__FILE__). '/bp-helper_rs.php' );
		return _scoper_bp_has_activities( $bp_activities, $bp_activities_template );
	}

	// custom wrapper to clean up after get_previous_post_where, get_next_post_where nonstandard arg syntax 
	// (uses alias p for post table, passes "WHERE post_type=...)
	function flt_adjacent_post_where( $where ) {
		global $wpdb, $query_interceptor, $current_user;

		if ( ! empty($current_user->ID) )
			$where = str_replace( " AND p.post_status = 'publish'", '', $where);
	
		// get_adjacent_post() function includes 'WHERE ' at beginning of $where
		$where = str_replace( 'WHERE ', 'AND ', $where );

		$post_type = cr_find_post_type();
		
		$args = array( 'source_alias' => 'p', 'skip_teaser' => true );	// skip_teaser arg ensures unreadable posts will not be linked
		$where = 'WHERE 1=1 ' . $query_interceptor->flt_objects_where( $where, 'post', $post_type, $args );

		return $where;
	}
	
	// custom wrapper to clean up after get_archives() nonstandard arg syntax (passes "WHERE post_type=...)
	function flt_getarchives_where ( $where ) {
		global $current_user, $wpdb;
		
		$where = str_replace( "WHERE ", "WHERE $wpdb->posts.post_date > 0 AND ", $where );
		
		if ( ! empty($current_user->ID) )
			$where = str_replace( "AND post_status = 'publish'", "AND post_status IN ('publish', 'private')", $where );

		$where = str_replace( "WHERE ", "AND ", $where );
	
		// pass force arg to ignore teaser setting
		$where = apply_filters('objects_where_rs', $where, 'post', '', array('skip_teaser' => true) );

		$where = 'WHERE 1=1 ' . $where;
			
		return $where;
	}
	
	function flt_sticky_posts( $post_ids ) {
		if ( $post_ids && ! is_content_administrator_rs() ) {
			global $wpdb;
			$post_ids = scoper_get_col( apply_filters( 'objects_request_rs', "SELECT ID FROM $wpdb->posts WHERE ID IN ('" . implode( "','", $post_ids ) . "')", 'post' ) );
		}

		return $post_ids;
	}
}
?>