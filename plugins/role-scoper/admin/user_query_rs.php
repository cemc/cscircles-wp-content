<?php
require_once( ABSPATH . '/wp-admin/includes/user.php' );

if ( isset( $_GET['rs_user_search'] )  ) {
	
	if ( empty( $_GET['rs_user_search'] ) ) {
		global $wpdb;
		$results = $wpdb->get_results("SELECT ID, user_login FROM $wpdb->users ORDER BY user_login");
		
	} else {
		if ( awp_ver( '3.1-beta' ) )
			$search = new WP_User_Query( 'search=*' . $_GET['rs_user_search'] . '*' );
		else
			$search = new WP_User_Search( $_GET['rs_user_search'] );

		if ( $search ) {
			global $wpdb;
			$results = $wpdb->get_results( "SELECT ID, user_login $search->query_from $search->query_where ORDER BY user_login" );
		}
	}

	if ( $results ) {	
		// determine all current users (of any status) for group in question
		if ( ! empty( $_GET['rs_agent_id'] ) )
			$users = ScoperAdminLib::get_group_members( $_GET['rs_agent_id'], COL_ID_RS, false, array( 'status' => 'any' ) );
		else
			$users = array();
		
		foreach( $results as $row )
			if ( ! in_array( $row->ID, $users ) ) {
				echo "<option value='$row->ID'>$row->user_login</option>";
			}
	}
	
} elseif ( isset( $_GET['rs_group_search'] ) ) {
	
	if ( ! empty( $_GET['rs_group_search'] ) ) {
		$searches = array();
		$where = 'AND (';
		foreach ( array('group_name', 'group_description') as $col )
			$searches[] = $col . " LIKE '%{$_GET['rs_group_search']}%'";
		$where .= implode(' OR ', $searches);
		$where .= ')';
	} else
		$where = '';

	if ( 'recommended' == $_GET['rs_target_status'] )
		$reqd_caps = 'recommend_group_membership';
	elseif ( 'requested' == $_GET['rs_target_status'] )
		$reqd_caps = 'request_group_membership';
	else
		$reqd_caps = 'manage_groups';

	// determine all currently stored groups (of any status) for user in question (not necessarily logged user)
	if ( ! empty( $_GET['rs_agent_id'] ) )
		$user_groups = $GLOBALS['current_rs_user']->get_groups_for_user( $_GET['rs_agent_id'], array( 'status' => 'any' ) );
	else
		$user_groups = array();
		
	if ( $groups = ScoperAdminLib::get_all_groups(FILTERED_RS, COLS_ALL_RS, array( 'include_norole_groups' => false, 'reqd_caps' => $reqd_caps, 'where' => $where ) ) ) {
		foreach( $groups as $row )
			if ( ( is_null($row->meta_id) || empty($row->meta_id) ) && ! in_array( $row->ID, $user_groups ) )
				echo "<option value='$row->ID'>$row->display_name</option>";
	}
}


?>