<?php
// filter users list for edit-capable users as a convenience to administrator
add_filter('query', array('ScoperHardwayUsers', 'flt_editable_user_ids') );

// wrapper to users_where filter for "post author" / "page author" dropdown (limit to users who have appropriate caps)
// NOTE: As of 3.0, WP still contains this function but no longer calls it
add_filter('get_editable_authors', array('ScoperHardwayUsers', 'flt_get_editable_authors'), 50, 1);

if ( awp_ver( '3.1' ) )
	add_action('pre_user_query', array('ScoperHardwayUsers', 'act_wp_user_query'), 1 );
else
	require_once( dirname(__FILE__).'/hardway-users-legacy_rs.php' );

class ScoperHardwayUsers {
	// Filter the otherwise unfilterable get_editable_user_ids() result set, which affects the admin UI
	public static function flt_editable_user_ids($query) {
		// Only display users who can read / edit the object in question
		if ( strpos ($query, "user_id FROM") && strpos ($query, "meta_key =") ) {
			global $wpdb;
			
			if ( strpos ($query, "user_id FROM $wpdb->usermeta WHERE meta_key = '{$wpdb->prefix}user_level'") ) {
				//log_mem_usage_rs( 'start flt_editable_user_ids()' );

				if ( ! $post_type = cr_find_post_type() )
					return $query;

				if ( ! $post_type_obj = get_post_type_object( $post_type ) )
					return $query;

				$object_id = scoper_get_object_id();

				// only modify the default authors list if current user can edit_others for the current post/page
				if ( current_user_can( $post_type_obj->cap->edit_others_posts, $object_id ) ) {
					global $scoper, $current_user;

					$users = $scoper->users_who_can($post_type_obj->cap->edit_posts, COL_ID_RS, 'post', $object_id );

					if ( ! in_array($current_user->ID, $users) )
						$users []= $current_user->ID;
					
					$query = "SELECT $wpdb->users.ID FROM $wpdb->users WHERE ID IN ('" . implode("','", $users) . "')";
				}
				//log_mem_usage_rs( 'end flt_editable_user_ids()' );
			}
		}
		
		return $query;
	}
	
	public static function act_wp_user_query( &$query_obj ) {
		// filter Author dropdown on post edit form
		if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) && ( 'authors' == $query_obj->query_vars['who'] ) ) {
			global $current_user, $scoper, $wpdb;
			
			$object_type = cr_find_post_type();
			$object_id = scoper_get_object_id('post');
			$post_type_obj = get_post_type_object($object_type);
			
			if ( $object_id > 0 ) {
				if ( $current_author = $scoper->data_sources->get_from_db('owner', 'post', $object_id) )
					$force_user_id = $current_author;
			} else {
				global $current_user;
				$force_user_id = $current_user->ID;
			}

			if ( cr_user_can( $post_type_obj->cap->edit_others_posts, $object_id, 0, array( 'omit_owner_clause' => true ) ) ) {
				$args = array();
				if ( $force_user_id )
					$args['include_user_ids'] = $force_user_id;
				
				$args['preserve_or_clause'] = " uro.user_id = '$force_user_id'";
				$users = $scoper->users_who_can( $post_type_obj->cap->edit_posts, COL_ID_RS, 'post', $object_id, $args );
			} else {
				$display_name = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID = '$force_user_id'" );
				$users = array( (object) array( 'ID' => $force_user_id, 'display_name' => $display_name ) );
			}

			// for Author dropdown filtering, there should always be at least one user
			if ( $users ) {
				$query_obj->query_from = "FROM $wpdb->users";
				$query_obj->query_where = "WHERE 1=1 AND ID IN ('" . implode("','", $users ) . "')";
			}
		}
	}
	
	public static function flt_get_editable_authors($unfiltered_results) {
		global $wpdb, $scoper, $post;
		
		if ( ! $post_type = cr_find_post_type() )
			return $unfiltered_results;
		
		if ( ! $post_type_obj = get_post_type_object( $post_type ) )
			return $unfiltered_results;

		$have_cap = cr_user_can( $post_type_obj->cap->edit_others_posts, $post->ID, 0, array('require_full_object_role' => true ) );

		if ( $have_cap )
			return $scoper->users_who_can( $post_type_obj->cap->edit_posts, COLS_ALL_RS);
		else {
			if ( $post->ID ) {
				if ( $current_author = $scoper->data_sources->get_from_db('owner', 'post', $post->ID) )
					$force_user_id = $current_author;
			} else {
				global $current_user;
				$force_user_id = $current_user->ID;
			}
		
			if ( $force_user_id ) {
				$display_name = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID = '$force_user_id'" );
				$users = array( (object) array( 'ID' => $force_user_id, 'display_name' => $display_name ) );
				return $users;
			}
		}
		
		//log_mem_usage_rs( 'flt_get_editable_authors()' );


		return $unfiltered_results;
	}

}
?>