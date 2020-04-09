<?php

class CapInterceptorBasic_RS
{	
	var $scoper;
	var $query_interceptor;
	
	function __construct() {
		$this->scoper =& $GLOBALS['scoper'];
		$this->query_interceptor =& $GLOBALS['query_interceptor'];
		
		add_filter('user_has_cap', array(&$this, 'flt_user_has_cap'), 99, 3);
	}
	
	// CapInterceptorBasic_RS::flt_user_has_cap
	//
	// Scaled down current_user_can filter, mainly for use with post/page access checks by attachment filter on direct file access 
	//
	// NOTE: This should not be added as a filter simultaneously with its full-featured counterpart. (On direct access, the cap-interceptor_rs.php file is not even loaded)
	//
	// Capability filter applied by WP_User->has_cap (usually via WP current_user_can function)
	// Pertains to logged user's capabilities blog-wide, or for a single item
	//
	// $wp_blogcaps = current user's blog-wide capabilities
	// $reqd_caps = primitive capabilities being tested / requested
	// $args = array with:
	// 		$args[0] = original capability requirement passed to current_user_can (possibly a meta cap)
	// 		$args[1] = user being tested
	// 		$args[2] = object id (could be a postID, linkID, catID or something else)
	//
	// The intent here is to add to (or take away from) $wp_blogcaps based on scoper role assignments
	// (only offer an opinion on scoper-defined caps, with others left in $allcaps array as blog-wide caps)
	//
	function flt_user_has_cap($wp_blogcaps, $orig_reqd_caps, $args)	{
		if ( empty( $args[2] ) )
			return $wp_blogcaps;

		// Disregard caps which are not defined in Role Scoper config
		if ( ! $rs_reqd_caps = array_intersect( $orig_reqd_caps, $this->scoper->cap_defs->get_all_keys() ) )
			return $wp_blogcaps;	

		$user_id = ( isset($args[1]) ) ? $args[1] : 0;

		global $current_rs_user;
		
		if ( $user_id && ( $user_id != $current_rs_user->ID ) )
			$user = rs_get_user( $user_id );
		else
			$user = $current_rs_user;

		$object_id = (int) $args[2];		
		
		if ( ! $post_type = get_post_field( 'post_type', $object_id ) )
			return $wp_blogcaps;

		global $wpdb;

		$use_term_roles = scoper_get_otype_option( 'use_term_roles', 'post', $post_type );	

		$use_object_roles = ( empty($src->no_object_roles) ) ? scoper_get_otype_option( 'use_object_roles', 'post', $post_type ) : false;
	
		$this_args = array('object_type' => $post_type, 'user' => $user, 'otype_use_term_roles' => $use_term_roles, 'otype_use_object_roles' => $use_object_roles, 'skip_teaser' => true );
		$where = $this->query_interceptor->objects_where_role_clauses( 'post', $rs_reqd_caps, $this_args );

		if ( $where )
			$where = "AND ( $where )";

		$id_ok = scoper_get_var( "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE 1=1 $where AND $wpdb->posts.ID = '$object_id' LIMIT 1" );

		$rs_reqd_caps = array_fill_keys( $rs_reqd_caps, true );

		if ( ! $id_ok ) {
			//d_echo("object_id $object_id not okay!" );
			//rs_errlog( "object_id $object_id not okay!" );
			
			return array_diff_key( $wp_blogcaps, $rs_reqd_caps);	// required caps we scrutinized are excluded from this array
		} else {
			if ( $restore_caps = array_diff($orig_reqd_caps, array_keys($rs_reqd_caps) ) )
				$rs_reqd_caps = $rs_reqd_caps + array_fill_keys($restore_caps, true);

			//rs_errlog( 'RETURNING:' );
			//rs_errlog( serialize(array_merge($wp_blogcaps, $rs_reqd_caps)) );

			return array_merge($wp_blogcaps, $rs_reqd_caps);
		}
	}

} // end class
?>