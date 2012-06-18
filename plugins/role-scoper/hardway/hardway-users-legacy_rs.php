<?php

add_filter('wp_dropdown_users', array('ScoperHardwayUsersLegacy', 'flt_wp_dropdown_users'), 50, 1);	

class ScoperHardwayUsersLegacy {
	//horrible reverse engineering of dropdown_users execution because until recently, only available filter was on html output
	function flt_wp_dropdown_users($wp_output) {
		// just filter Post Author dropdown
		if ( ! strpos( $wp_output, "name='post_author_override'" ) )
			return $wp_output;
		
		// this is meant to filter the post author selection
		if ( ! is_admin() )
			return $wp_output;
		
		// if (even after our blogcap tinkering) the author list is already locked due to insufficient blog-wide caps, don't mess
		if ( ! $pos = strpos ($wp_output, '<option') )
			return $wp_output;
		
		if ( ! strpos ($wp_output, '<option', $pos + 1) )
			return $wp_output;

		global $wpdb, $scoper;
			
		$last_query = $wpdb->last_query;
		
		if ( ! $object_type = cr_find_post_type() )
			return $wp_output;
		
		if( ! $post_type_obj = get_post_type_object($object_type) )
			return $wp_output;
			
		global $current_user;
			
		$is_ngg_edit = strpos( $_SERVER['REQUEST_URI'], 'nggallery-manage-gallery' );
			
		if ( ! $is_ngg_edit ) {
			$src_name = 'post';
			$object_id = scoper_get_object_id( 'post', $object_type);
			
			// only modify the default authors list if current user has Editor role for the current post/page
			$have_cap = cr_user_can( $post_type_obj->cap->edit_others_posts, $object_id, 0, array( 'require_full_object_role' => true ) );
		} else {
			$src_name = 'ngg_gallery';
			$object_id = $_REQUEST['gid'];
			$have_cap = ! empty( $current_user->allcaps[$post_type_obj->cap->edit_others_posts] );  // $post_type_obj defaults to 'post' type on NGG Manage Gallery page
		}

		//if ( ! $have_cap )
		 //	return $wp_output;
		
		$orderpos = strpos($last_query, 'ORDER BY');
		$orderby = ( $orderpos ) ? substr($last_query, $orderpos) : '';
		if ( ! strpos( $orderby, 'display_name' ) )	// sanity check in case the last_query buffer gets messed up
			$orderby = '';

		$id_in = $id_not_in = $show_option_all = $show_option_none = '';
		
		$pos = strpos($last_query, 'AND ID IN(');
		if ( $pos ) {
			$pos_close = strpos($last_query, ')', $pos);
			if ( $pos_close)
				$id_in = substr($last_query, $pos, $pos_close - $pos + 1); 
		}
		
		$pos = strpos($last_query, 'AND ID NOT IN(');
		if ( $pos ) {
			$pos_close = strpos($last_query, ')', $pos);
			if ( $pos_close)
				$id_not_in = substr($last_query, $pos, $pos_close - $pos + 1); 
		}
		
		$search = "<option value='0'>";
		$pos = strpos($wp_output, $search . __awp('Any'));
		if ( $pos ) {
			$pos_close = strpos($wp_output, '</option>', $pos);
			if ( $pos_close)
				$show_option_all = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = "<option value='-1'>";
		$pos = strpos($wp_output, $search . __awp('None'));
		if ( $pos ) {
			$pos_close = strpos($wp_output, '</option>', $pos);
			if ( $pos_close)
				$show_option_none = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = "<select name='";
		$pos = strpos($wp_output, $search);
		if ( false !== $pos ) {
			$pos_close = strpos($wp_output, "'", $pos + strlen($search));
			if ( $pos_close)
				$name = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = " id='";
		$multi = ! strpos($wp_output, $search);  // beginning with WP 2.7, some users dropdowns lack id attribute
		
		$search = " class='";
		$pos = strpos($wp_output, $search);
		if ( $pos ) {
			$pos_close = strpos($wp_output, "'", $pos + strlen($search));
			if ( $pos_close)
				$class = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = " selected='selected'";
		$pos = strpos($wp_output, $search);
		if ( $pos ) {
			$search = "<option value='";
	
			$str_left = substr($wp_output, 0, $pos);
			$pos = strrpos($str_left, $search); //back up to previous option tag

			$pos_close = strpos($wp_output, "'", $pos + strlen($search));
			if ( $pos_close)
				$selected = substr($wp_output, $pos + strlen($search), $pos_close - ($pos + strlen($search)) ); 
		}
		
		if ( empty($selected) )
			$selected = $current_user->ID;	// precaution prevents default-selection of non-current user
		
		// Role Scoper filter application
		$where = "$id_in $id_not_in";

		$args = array();
		$args['where'] = $where;
		$args['orderby'] = $orderby;
		
		if ( $object_id > 0 ) {
			if ( $current_author = $scoper->data_sources->get_from_db('owner', $src_name, $object_id) )
				$force_user_id = $current_author;
		} else {
			$force_user_id = $current_user->ID;
		}

		if ( $have_cap ) {
			if ( $force_user_id )
				$args['preserve_or_clause'] = " uro.user_id = '$force_user_id'";

			$users = $scoper->users_who_can($post_type_obj->cap->edit_posts, COLS_ID_DISPLAYNAME_RS, 'post', $object_id, $args);
		} else {
			$display_name = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID = '$force_user_id'" );
			$users = array( (object) array( 'ID' => $force_user_id, 'display_name' => $display_name ) );
		}
		
		if ( empty($users) ) { // sanity check: users_who_can should always return Administrators
			if ( $admin_roles = awp_administrator_roles() )
				$users = scoper_get_results( "SELECT DISTINCT ID, display_name FROM $wpdb->users INNER JOIN $wpdb->user2role2object_rs AS uro ON uro.user_id = $wpdb->users.ID AND uro.scope = 'blog' AND uro.role_type = 'wp' AND uro.role_name IN ('" . implode( "','", $admin_roles ) . "')" );
			else
				return $wp_output; // the user data must be messed up
		}
			
		$show = 'display_name'; // no way to back this out

		
		// ----------- begin wp_dropdown_users code copy (from WP 2.7) -------------
		$id = $multi ? "" : "id='$name'";

		$output = "<select name='$name' $id class='$class'>\n";

		if ( $show_option_all )
			$output .= "\t<option value='0'>$show_option_all</option>\n";

		if ( $show_option_none )
			$output .= "\t<option value='-1'>$show_option_none</option>\n";
			
		foreach ( (array) $users as $user ) {
			$user->ID = (int) $user->ID;
			$_selected = $user->ID == $selected ? " selected='selected'" : '';
			$display = !empty($user->$show) ? $user->$show : '('. $user->user_login . ')';
			$output .= "\t<option value='$user->ID'$_selected>" . esc_html($display) . "</option>\n";
		}

		$output .= "</select>";
		// ----------- end wp_dropdown_users code copy (from WP 2.7) -------------
		
		return $output;
	}
} // end class
?>