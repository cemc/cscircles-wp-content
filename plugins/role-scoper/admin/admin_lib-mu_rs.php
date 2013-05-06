<?php

function scoper_mu_site_menu() {	
	if ( ! is_option_administrator_rs() )
		return;

	$path = SCOPER_ABSPATH;

	$name = ( awp_ver( '3.1' ) ) ? 'sites' : 'ms-admin';
	
	// RS Site Options
	add_submenu_page("{$name}.php", __('Role Scoper Options', 'scoper'), __('Role Options', 'scoper'), 'read', 'rs-site_options' );
	
	$func = "include_once('$path' . '/admin/options.php');scoper_options( true );";
	add_action("{$name}_page_rs-site_options", create_function( '', $func ) );	

	global $scoper_default_options, $scoper_options_sitewide;
			
	// omit Option Defaults menu item if all options are controlled sitewide
	if ( empty($scoper_default_options) )
		scoper_refresh_default_options();
	
	if ( count($scoper_options_sitewide) != count($scoper_default_options) ) {
		// RS Default Options (for per-blog settings)
		add_submenu_page("{$name}.php", __('Role Scoper Option Defaults', 'scoper'), __('Role Defaults', 'scoper'), 'read', 'rs-default_options' );
	
		$func = "include_once('$path' . '/admin/options.php');scoper_options( false, true );";
		add_action("{$name}_page_rs-default_options", create_function( '', $func ) );
	}
	
	// satisfy WordPress' demand that all admin links be properly defined in menu
	if ( 'rs-attachments_utility' == $GLOBALS['plugin_page_cr'] )
		add_submenu_page("{$name}.php", __('Attachment Utility', 'scoper'), __('Attachment Utility', 'scoper'), 'read', 'rs-attachments_utility', array( $GLOBALS['scoper_admin'], 'menu_handler' ) );
}

function scoper_mu_users_menu() {
	if ( ! defined('DEFINE_GROUPS_RS') || ! scoper_get_site_option( 'mu_sitewide_groups' ) )
		return;

	$cap_req = ( is_user_administrator_rs() || current_user_can('recommend_group_membership') ) ? 'read' : 'manage_groups';

	$groups_caption = ( defined( 'GROUPS_CAPTION_RS' ) ) ? GROUPS_CAPTION_RS : __('Role Groups', 'scoper');

	global $scoper_admin;
	$menu_name = ( awp_ver( '3.1' ) ) ? 'users.php' : 'ms-admin.php';
	add_submenu_page( $menu_name, $groups_caption, $groups_caption, $cap_req, 'rs-groups', array( &$scoper_admin, 'menu_handler' ) );
	
	if ( scoper_get_option( 'mu_sitewide_groups' ) ) {
		global $plugin_page_cr;
		// satisfy WordPress' demand that all admin links be properly defined in menu
		if ( 'rs-default_groups' == $plugin_page_cr )
			add_submenu_page($menu_name, __('User Groups', 'scoper'), __('Default Groups', 'scoper'), $cap_req, 'rs-default_groups', array( &$scoper_admin, 'menu_handler' ) );

		if ( 'rs-group_members' == $plugin_page_cr )
			add_submenu_page($menu_name, __('User Groups', 'scoper'), __('Group Members', 'scoper'), $cap_req, 'rs-group_members', array( &$scoper_admin, 'menu_handler' ) );
	}
}

function scoper_get_blog_list( $start = 0, $num = 10 ) {
	global $wpdb;

	$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );
	$blog_list = array();
	
	foreach ( (array) $blogs as $details ) {
		$table_name = ( $details['blog_id'] > 1 ) ? $wpdb->base_prefix . $details['blog_id'] . "_posts" : $wpdb->base_prefix . 'posts';
	
		if ( ! $wpdb->get_results( "SHOW TABLES LIKE '$table_name'" ) )
			continue;
	
		$blog_list[ $details['blog_id'] ] = $details;
		$blog_list[ $details['blog_id'] ]['postcount'] = $wpdb->get_var( "SELECT COUNT(ID) FROM $table_name WHERE post_status='publish' AND post_type='post'" );
	}
	unset( $blogs );
	$blogs = $blog_list;

	if( false == is_array( $blogs ) )
		return array();

	if( $num == 'all' )
		return array_slice( $blogs, $start, count( $blogs ) );
	else
		return array_slice( $blogs, $start, $num );
}

?>