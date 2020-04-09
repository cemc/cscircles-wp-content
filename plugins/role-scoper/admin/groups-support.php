<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) ) 
	die('This page cannot be called directly.');

/* this file adapted from:
 Group Restriction plugin
 http://code.google.com/p/wp-group-restriction/
 Tiago Pocinho, Siemens Networks, S.A.
 
 some group-related functions also moved to ScoperAdminLib with slight adaptation
 */

class UserGroups_tp {
	public static function getUsersWithGroup($group_id) {
		return ScoperAdminLib::get_group_members($group_id);
	}
	
	public static function addGroupMembers ($group_id, $user_ids){
		ScoperAdminLib::add_group_user($group_id, $user_ids);
	}
	
	public static function deleteGroupMembers ($group_id, $user_ids) {
		ScoperAdminLib::remove_group_user($group_id, $user_ids);
	}
		
	
	public static function GetGroup($group_id) {
		return ScoperAdminLib::get_group($group_id);
	}
	
	public static function getGroupByName($name) {
		return ScoperAdminLib::get_group_by_name($name);
	}
	
	/**
	 * Creates a new Group
	 *
	 * @param string $name - Name of the group
	 * @param string $description - Group description (optional)
	 * @return group ID on successful creation
	 **/
	public static function createGroup ($name, $description = ''){
		global $wpdb;

		if( ! UserGroups_tp::isValidName($name) )
			return false;

		$insert = "INSERT INTO $wpdb->groups_rs ($wpdb->groups_name_col, $wpdb->groups_descript_col) VALUES ('$name','$description')";
		scoper_query( $insert );

		do_action('created_group_rs', (int) $wpdb->insert_id);
		
		return (int) $wpdb->insert_id;
	}

	
	/**
	 * Removes a given group
	 *
	 * @param int $id - Identifier of the group to delete
	 * @param boolean True if the deletion is successful
	 **/
	public static function deleteGroup ($group_id){
		global $wpdb;

		if( ! $group_id || ! UserGroups_tp::getGroup($group_id) )
			return false;

		do_action('delete_group_rs', $group_id);
		
		// first delete all cache entries related to this group
		if ( $group_members = ScoperAdminLib::get_group_members( $group_id, COL_ID_RS ) ) {
			$id_in = "'" . implode("', '", $group_members) . "'";
			$any_user_roles = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE role_type = 'rs' AND user_id IN ($id_in) LIMIT 1");
			
			foreach ($group_members as $user_id )
				wpp_cache_delete( $user_id, 'group_membership_for_user' );
		}
		
		//if ( $got_blogrole = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = 'rs' AND group_id = '$group_id' LIMIT 1") ) {
			scoper_query("DELETE FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = 'rs' AND group_id = '$group_id'");
		
		//}
		
		//if ( $got_taxonomyrole = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'term' AND role_type = 'rs' AND group_id = '$group_id' LIMIT 1") ) {
			scoper_query("DELETE FROM $wpdb->user2role2object_rs WHERE scope = 'term' AND role_type = 'rs' AND group_id = '$group_id'");
		
		//}
		
		//if ( $got_objectrole = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND role_type = 'rs' AND group_id = '$group_id' LIMIT 1") ) {
			scoper_query("DELETE FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND role_type = 'rs' AND group_id = '$group_id'");
		//}
		
		$delete = "DELETE FROM $wpdb->groups_rs WHERE $wpdb->groups_id_col='$group_id'";
		scoper_query( $delete );

		$delete = "DELETE FROM $wpdb->user2group_rs WHERE $wpdb->user2group_gid_col='$group_id'";
		scoper_query( $delete );
		
		return true;
	}

	/**
	 * Checks if a group with a given name exists
	 *
	 * @param string $name - Name of the group to test
	 * @return boolean True if the group exists, false otherwise.
	 **/
	public static function groupExists($name) {
		global $wpdb;

		$query = "SELECT COUNT(*) FROM $wpdb->groups_rs WHERE $wpdb->groups_name_col = '$name'";
		$results = scoper_get_var( $query );
		
		return $results != 0;
	}
	
	/**
	 * Verifies if a group name is valid (for a new group)
	 *
	 * @param string $string - Name of the group
	 * @return boolean True if the name is valid, false otherwise.
	 **/
	public static function isValidName($string){
		if($string == "" || UserGroups_tp::groupExists($string)){
			return false;
		}
		return true;
	}

	/**
	 * Updates an existing Group
	 *
	 * @param int $groupID - Group identifier
	 * @param string $name - Name of the group
	 * @param string $description - Group description (optional)
	 * @return boolean True on successful update
	 **/
	public static function updateGroup ($group_id, $name, $description = ''){
		global $wpdb;

		$description = strip_tags($description);

		if ( $prev = scoper_get_row("SELECT * FROM $wpdb->groups_rs WHERE $wpdb->groups_id_col='$group_id';") ) {
		
			if( ($prev->{$wpdb->groups_name_col} != $name) && ! UserGroups_tp::isValidName($name))
				return false;
				
			// don't allow updating of metagroup name / descript
			if( ! empty($prev->meta_id) )
				return false;
		}
			
		do_action('update_group_rs', $group_id);
			
		$query = "UPDATE $wpdb->groups_rs SET $wpdb->groups_name_col = '$name', $wpdb->groups_descript_col='$description' WHERE $wpdb->groups_id_col='$group_id';";
		scoper_query( $query );

		return true;
	}
	
	public static function update_group_members_multi_status( $group_id, $current_members ) {
		$posted_members = array();
		
		$is_administrator = is_user_administrator_rs();

		$can_manage = $is_administrator || current_user_can( 'manage_groups' );
		$can_moderate = $can_manage || current_user_can( 'recommend_group_membership' );
		
		if ( ! $can_moderate && ! current_user_can( 'request_group_membership' ) )
			return;
		
		if ( $can_manage )
			$posted_members['active'] = explode( ',', trim($_POST['current_agents_rs_csv'], ',') );
		else
			$current_members = array_diff_key( $current_members, array( 'active' => true ) );

		if ( $can_moderate ) {
			$current_members['recommended'] = ScoperAdminLib::get_group_members($group_id, COL_ID_RS, false, array( 'status' => 'recommended' ) );
			
			if ( ! empty($_POST['recommended_agents_rs_csv']) )
				$posted_members['recommended'] = explode( ',', trim($_POST['recommended_agents_rs_csv'], ',') );
		}

		$current_members['requested'] = ScoperAdminLib::get_group_members($group_id, COL_ID_RS, false, array( 'status' => 'requested' ) );
		
		if ( ! empty($_POST['requested_agents_rs_csv']) )
			$posted_members['requested'] = explode( ',', trim($_POST['requested_agents_rs_csv'], ',') );

		$all_current_members = agp_array_flatten ( $current_members );
		$all_posted_members = agp_array_flatten ( $posted_members );
		
		foreach ( $current_members as $status => $stored ) {
			// remove group memberships which were not posted for any status			
			foreach ( $stored as $user_id ) {
				if ( $user_id )
					if ( ! in_array( $user_id, $all_posted_members ) )
						ScoperAdminLib::remove_group_user($group_id, $user_id);
			}
		}
		
		foreach ( $posted_members as $status => $posted ) {
			// insert or update group memberships as specified
			foreach ( $posted as $user_id ) {
				if ( $user_id )
					if ( ! in_array( $user_id, $all_current_members ) )
						ScoperAdminLib::add_group_user($group_id, $user_id, $status);
					elseif ( ! in_array( $user_id, $current_members[$status] ) )
						ScoperAdminLib::update_group_user($group_id, $user_id, $status);
			}
		}
	}
	
	// Called once each for members checklist, managers checklist in admin UI.
	// In either case, current (checked) members are at the top of the list.
	public static function group_members_checklist( $group_id, $user_class = 'member', $all_users = '' ) {
		global $scoper;
		
		if ( ! $all_users )
			$all_users = $scoper->users_who_can('', COLS_ID_NAME_RS);

		if ( $group_id )
			$group = ScoperAdminLib::get_group($group_id);
			
		if ( 'member' == $user_class ) {
			$current_ids = ($group_id) ? array_flip(ScoperAdminLib::get_group_members($group_id, COL_ID_RS)) : array();

			if ( ! empty($group) && in_array( $group->meta_id, array( 'rv_pending_rev_notice_ed_nr_', 'rv_scheduled_rev_notice_ed_nr_' ) ) ) {
				$args = array( 'any_object' => true );
				
				$eligible_ids = array();
				foreach( get_post_types( array( 'public' => true ), 'object' ) as $_type => $_type_obj ) {
					$args['object_type'] = $_type;
					$type_eligible_ids = $scoper->users_who_can( array( $_type_obj->cap->edit_published_posts, $_type_obj->cap->edit_others_posts ), COL_ID_RS, 'post', 0, $args );
					$eligible_ids = array_merge( $eligible_ids, $type_eligible_ids );	
				}
				$eligible_ids = array_unique( $eligible_ids );
			} else {
				// force_all_users arg is a temporary measure to ensure that any user can be viewed / added to a sitewide MU group regardless of what blog backend it's edited through 
				$_args = ( IS_MU_RS && scoper_get_option( 'mu_sitewide_groups', true ) ) ? array( 'force_all_users' => true ) : array();

				$eligible_ids = $scoper->users_who_can( '', COL_ID_RS, '', '', $_args );
			}
			
			$admin_ids = array();
		} else {
			$group_role_defs = ( 'moderator' == $user_class ) ? array( 'rs_group_moderator' ) : array( 'rs_group_manager' );
			
			if ( $group_id ) {
				require_once( dirname(__FILE__).'/role_assignment_lib_rs.php');
				$current_roles = ScoperRoleAssignments::organize_assigned_roles(OBJECT_SCOPE_RS, 'group', $group_id, $group_role_defs, ROLE_BASIS_USER);

				$current_roles = agp_array_flatten($current_roles, false);

				$current_ids = ( isset($current_roles['assigned']) ) ? $current_roles['assigned'] : array();
			} else
				$current_ids = array();
			
			$cap_name = ( defined( 'SCOPER_USER_ADMIN_CAP' ) ) ? constant( 'SCOPER_USER_ADMIN_CAP' ) : 'edit_users';
			$admin_ids = $scoper->users_who_can( $cap_name, COL_ID_RS );
			
			// optionally, limit available group managers according to role_admin_blogwide_editor_only option 
			if ( 'manager' == $user_class ) {	
				$require_blogwide_editor = false;
					
				if ( ! empty($group) ) {
					if ( ! strpos( $group->meta_id, '_nr_' ) ) {	// don't limit manager selection for groups that don't have role assignments
						$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');
					}
				}
				
				if ( 'admin' == $require_blogwide_editor ) {
					$eligible_ids = $admin_ids;
					
				} elseif ( 'admin_content' == $require_blogwide_editor ) {
					$cap_name = ( defined( 'SCOPER_CONTENT_ADMIN_CAP' ) ) ? constant( 'SCOPER_CONTENT_ADMIN_CAP' ) : 'activate_plugins';
					$eligible_ids = array_unique( array_merge( $admin_ids, $scoper->users_who_can( $cap_name, COL_ID_RS ) ) );
					
				} elseif ( $require_blogwide_editor ) {
					$post_editors = $scoper->users_who_can('edit_others_posts', COL_ID_RS);
					$page_editors = $scoper->users_who_can('edit_others_pages', COL_ID_RS);
					
					$eligible_ids = array_unique( array_merge($post_editors, $page_editors, $admin_ids) );
				
				} else
					$eligible_ids = '';
			} else
				$eligible_ids = '';
				
		} // endif user class is not "member" 
		
		$css_id = $user_class;
		$args = array( 'eligible_ids' => $eligible_ids, 'via_other_scope_ids' => $admin_ids, 'suppress_extra_prefix' => true );
 		require_once( dirname(__FILE__).'/agents_checklist_rs.php');
		ScoperAgentsChecklist::agents_checklist( ROLE_BASIS_USER, $all_users, $css_id, $current_ids, $args);
	}
	
	/**
	 * Writes the success/error messages
	 * @param string $string - message to be displayed
	 * @param boolean $success - boolean that defines if is a success(true) or error(false) message
	 **/
	public static function write($string, $success=true, $id="message"){
		if($success){
			echo '<div id="'.$id.'" class="updated fade"><p>'.$string.'</p></div>';
		}else{
			echo '<div id="'.$id.'" class="error fade"><p>'.$string.'</p></div>';
		}
	}
}

?>
