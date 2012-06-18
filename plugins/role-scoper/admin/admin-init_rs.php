<?php

function scoper_admin_init() {
	if ( ! empty($_POST['rs_submit']) || ! empty($_POST['rs_defaults']) || ! empty($_POST['rs_flush_cache']) ) {
		// For 'options' and 'realm' admin panels, handle updated options right after current_user load (and before scoper init).
		// By then, check_admin_referer is available, but Scoper config and WP admin menu has not been loaded yet.
		require_once( SCOPER_ABSPATH . '/submittee_rs.php');	
		$handler = new Scoper_Submittee();
	
		if ( isset($_POST['rs_submit']) ) {
			$sitewide = isset($_POST['rs_options_doing_sitewide']);
			$customize_defaults = isset($_POST['rs_options_customize_defaults']);
			$handler->handle_submission( 'update', $sitewide, $customize_defaults );
			
		} elseif ( isset($_POST['rs_defaults']) ) {
			$sitewide = isset($_POST['rs_options_doing_sitewide']);
			$customize_defaults = isset($_POST['rs_options_customize_defaults']);
			$handler->handle_submission( 'default', $sitewide, $customize_defaults );
			
		} elseif ( isset($_POST['rs_flush_cache']) )
			$handler->handle_submission( 'flush' );
	} 
	
	// work around conflict with Simple Fields plugin uploader
	if ( defined( 'EASY_FIELDS_URL' ) ) {
		if ( strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/media-upload.php' ) || strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/async-upload.php' ) )
			define( 'DISABLE_QUERYFILTERS_RS', true );
	}
}


function scoper_use_posted_init_options() {
	if ( 0 !== strpos( $GLOBALS['plugin_page_cr'], 'rs-' ) || defined('GROUP_ROLES_RS') )
		return;
	
	if ( isset( $_POST['rs_defaults'] ) ) {
		$arr = scoper_default_options();
	} else {
		$arr = $_POST;
	}
	
	define ( 'SCOPER_CUSTOM_USER_BLOGCAPS', ! empty( $arr['custom_user_blogcaps'] ) );
	
	define ( 'DEFINE_GROUPS_RS', ! empty($arr['define_usergroups']) );
	define ( 'GROUP_ROLES_RS', ! empty($arr['define_usergroups']) && ! empty($arr['enable_group_roles']) );
	define ( 'USER_ROLES_RS', ! empty($arr['enable_user_roles']) );

	wpp_cache_init( IS_MU_RS && scoper_establish_group_scope() );
}

function get_posted_object_terms_cr( $taxonomy ) {
	if ( 'category' == $taxonomy ) {
		if ( ! empty($_POST['post_category']) )
			return $_POST['post_category'];

	} elseif( 'post_tag' == $taxonomy ) {
		if ( ! empty($_POST['tags_input']) )
			return $_POST['tags_input'];

	} elseif( ! empty($_POST['tax_input'][$taxonomy]) ) {
		return $_POST['tax_input'][$taxonomy];
	}
		
	return array();
}

function agp_strtolower( $str ) {
	if ( defined( 'SCOPER_MB_STRINGS' ) )
		return mb_strtolower( $str );
	else
		return strtolower( $str );
}
	
?>