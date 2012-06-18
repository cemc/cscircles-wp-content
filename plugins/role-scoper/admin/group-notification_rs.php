<?php

class ScoperGroupNotification {

function membership_request_notify( $group_id, $user_id, $is_update = false ) {
	global $scoper;
	
	$group = ScoperAdminLib::get_group( $group_id );
	$user = new WP_User( $user_id );
		
	$title = sprintf(__('[%s] Group Membership Request'), get_option('blogname'));
	
	$approval_url = admin_url( "admin.php?page=rs-groups&mode=approve&id=$group_id&user=$user_id" );
	$edit_url = admin_url( "admin.php?page=rs-groups&mode=edit&id=$group_id" );
	
	$message = sprintf( __('%1$s has requested membership in the "%2$s" access group.'), $user->display_name, $group->display_name ) . "\r\n\r\n";
	
	$edit_msg = sprintf( __('You may also review the request for possible deletion: %1$s'), $edit_url ) . "\r\n\r\n";

	// If group has moderator(s), notify them.  Otherwise, notify managers / administrators
	$manager_ids = array();
	if ( $managers = $scoper->users_who_can( "manage_groups", COLS_ALL_RS, 'group', $group_id ) ) {
		foreach ( $managers as $row )
			$manager_ids[ $row->ID ] = true;	
	}
	
	if ( $moderators = $scoper->users_who_can( "recommend_group_membership", COLS_ALL_RS, 'group', $group_id ) ) {
		foreach ( $moderators as $row )
			$moderator_ids[ $row->ID ] = true;
	} else
		$moderator_ids = array();
	
	if ( array_diff_key( $moderator_ids, $manager_ids ) ) {
		$message .= sprintf( __('As a moderator of this group, you can approve the membership request by clicking the following link: %1$s'), $approval_url ) . "\r\n\r\n";
		$message .= $edit_msg;
		
		foreach ( $moderators as $_user )
			if ( ! isset( $manager_ids[ $_user->ID ] ) )
				awp_mail( $_user->user_email, $title, $message );
	} else {
		$message .= sprintf( __('As an administrator of this group, you can activate the membership request by clicking the following link: %1$s'), $approval_url ) . "\r\n\r\n";
		$message .= $edit_msg;
		
		foreach ( $managers as $_user )
			awp_mail( $_user->user_email, $title, $message );
	}
}


function membership_recommendation_notify( $group_id, $user_id, $is_update = false ) {
	global $scoper, $current_user;
	
	$group = ScoperAdminLib::get_group( $group_id );
	$user = new WP_User( $user_id );
		
	$title = sprintf(__('[%s] Group Membership Recommendation'), get_option('blogname'));
	
	$approval_url = admin_url( "admin.php?page=rs-groups&mode=approve&id=$group_id&user=$user_id" );
	
	$message = sprintf( __('A moderator (%1$s) has approved the requested group membership for %2$s in the "%3$s" access group.'), $current_user->display_name, $user->display_name, $group->display_name ) . "\r\n\r\n";
	
	//d_echo("sending email: $title<br />");
	
	// If group has moderator(s), notify them.  Otherwise, notify managers / administrators
	if ( $managers = $scoper->users_who_can( "manage_groups", COLS_ALL_RS, 'group', $group_id ) ) {

		$message .= sprintf( __('As an administrator of this group, you can activate the membership request by clicking the following link: %1$s'), $approval_url ) . "\r\n\r\n";
		
		foreach ( $managers as $_user ) {
			//d_echo("to: $_user->user_email<br />");
			awp_mail( $_user->user_email, $title, $message );
		}
	}
}


function membership_activation_notify( $group_id, $user_id, $is_update = false ) {
	global $scoper;
	
	$group = ScoperAdminLib::get_group( $group_id );
	$user = new WP_User( $user_id );
		
	$title = sprintf(__('[%s] Group Membership Activated'), get_option('blogname'));
	
	$site_url = site_url( '' );
	$admin_url = admin_url( '' );

	$message = sprintf( __('An administrator has activated the membership of %1$s in the "%2$s" access group.'), $user->display_name, $group->display_name ) . "\r\n\r\n";

	// If group has moderator(s), notify them.
	$manager_ids = array();
	if ( $managers = $scoper->users_who_can( "manage_groups", COLS_ALL_RS, 'group', $group_id ) ) {
		foreach ( $managers as $row )
			$manager_ids[ $row->ID ] = true;	
	}
	
	if ( $moderators = $scoper->users_who_can( "recommend_group_membership", COLS_ALL_RS, 'group', $group_id ) ) {
		foreach ( $moderators as $row )
			$moderator_ids[ $row->ID ] = true;
	} else
		$moderator_ids = array();
	
	if ( array_diff_key( $moderator_ids, $manager_ids ) ) {
		foreach ( $moderators as $_user )
			if ( ! isset( $manager_ids[ $_user->ID ] ) )
				awp_mail( $_user->user_email, $title, $message );
	}
	
	// Also notify the newly activated group member.
	$message .= sprintf( __('To view the site, follow this link: %1$s'), $site_url ) . "\r\n";
	$message .= sprintf( __('To log in with your new: %1$s'), $admin_url ) . "\r\n\r\n";
	
	awp_mail( $user->user_email, $title, $message );
}

} // end class

?>