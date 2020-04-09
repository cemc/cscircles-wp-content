<?php

class ScoperUserEdit {

	// optional filter for WP role edit based on user level
	public static function editable_roles( $roles ) {
		global $current_user;

		$role_levels = ScoperUserEdit::get_role_levels();
		
		$current_user_level = ScoperUserEdit::get_user_level( $current_user->ID );
		
		foreach ( array_keys($roles) as $role_name )
			if ( isset($role_levels[$role_name]) && ( $role_levels[$role_name] > $current_user_level ) )
				unset( $roles[$role_name] );
		
		return $roles;
	}	
	

	public static function has_edit_user_cap($wp_blogcaps, $orig_reqd_caps, $args) {		
		// prevent anyone from editing or deleting a user whose level is higher than their own
		$levels = ScoperUserEdit::get_user_level( array( $args[1], $args[2] ) );
		
		// finally, compare would-be editor's level with target user's
		if ( $levels[ $args[2] ] > $levels[ $args[1] ] ) {
			$wp_blogcaps = array_diff_key( $wp_blogcaps, array_fill_keys( array( 'edit_users', 'delete_users' ), true ) );
		}
				
		return $wp_blogcaps;
	}
	
	
	public static function get_user_level( $user_ids ) {
		static $user_levels;
		
		$return_array = is_array( $user_ids );  // if an array was passed in, return results as an array
		
		if ( ! is_array($user_ids) ) {
			if ( IS_MU_RS && function_exists('is_super_admin') && is_super_admin() )	// mu site administrator may not be a user for the current blog
				return 10;
			
			$orig_user_id = $user_ids;	
			$user_ids = (array) $user_ids;
		}
	
		if ( ! isset($user_levels) )
			$user_levels = array();
			
		if ( array_diff( $user_ids, array_keys($user_levels) ) ) {
			// one or more of the users were not already logged	

			$role_levels = ScoperUserEdit::get_role_levels(); // local buffer for performance
				
			// If the listed user ids were logged following a search operation, save extra DB queries by getting the levels of all those users now
			global $wp_user_search;
			
			if ( ! empty( $wp_user_search->results ) ) {
				$query_users = $wp_user_search->results;
				$query_users = array_unique( array_merge( $query_users, $user_ids ) );
			} else
				$query_users = $user_ids;

			// get the WP roles for user
			global $wpdb;
			$results = scoper_get_results( "SELECT user_id, role_name FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = 'wp' AND user_id IN ('" . implode( "','", array_map( 'intval', $query_users ) ) . "')" );
	
			//echo("SELECT user_id, role_name FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = 'wp' AND user_id IN ('" . implode( "','", $query_users ) . "')");
			
			// credit each user for the highest role level they have
			foreach ( $results as $row ) {
				if ( ! isset( $role_levels[ $row->role_name ] ) )
					continue;
	
				if ( ! isset( $user_levels[$row->user_id] ) || ( $role_levels[ $row->role_name ] > $user_levels[$row->user_id] ) )
					$user_levels[$row->user_id] = $role_levels[ $row->role_name ];
			}
			
			// note any "No Role" users
			if ( $no_role_users = array_diff( $query_users, array_keys($user_levels) ) )
				$user_levels = $user_levels + array_fill_keys( $no_role_users, 0 );
		}
		
		
		if ( $return_array )
			$return = array_intersect_key( $user_levels, array_fill_keys( $user_ids, true ) );
		else 
			$return = ( isset($user_levels[$orig_user_id]) ) ? $user_levels[$orig_user_id] : 0;

		return $return;
	}
	

	// NOTE: user/role levels are used only for optional limiting of user edit - not for content filtering
	public static function get_role_levels() {
		static $role_levels;
		
		if ( isset($role_levels) )
			return $role_levels;

		$role_levels = array();
		
		global $wp_roles;
		foreach ( $wp_roles->roles as $role_name => $role ) {
			$level = 0;
			for ( $i=0; $i<=10; $i++ )
				if ( ! empty( $role['capabilities']["level_$i"] ) )
					$level = $i;
			
			$role_levels[$role_name] = $level;
		}	
		
		return $role_levels;
	}

} // end class


function awp_get_user_by_name( $name, $display_or_username = true ) {
	global $wpdb;
	
	if ( ! $user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_login = %s", $name) ) )
		if ( $display_or_username )
			$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE display_name = %s", $name ) );
	
	return $user;
}

function awp_get_user_by_id( $id ) {
	global $wpdb;
	
	if ( $user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE ID = %d", $id ) ) )

	return $user;
}

?>