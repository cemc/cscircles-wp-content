<?php
function scoper_admin_init() {
	global $pagenow;

	if ( in_array( $pagenow, array( 'update.php', 'plugin-install.php', 'update-core.php', 'plugins.php' ) ) ) {
		require_once( dirname(__FILE__).'/plugin-update-watch_rs.php' );
		RS_UpdateWatch::update_watch();
	}
	
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
	
	if ( defined( 'SSEO_VERSION' ) )
		require_once( dirname(__FILE__).'/eyes-only-admin_rs.php' );
	
	global $pagenow;

	// prevent default_private option from forcing a draft/pending post into private publishing
	if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
		if ( empty($_POST['publish']) && isset($_POST['post_status']) && isset($_POST['post_type']) && scoper_get_otype_option( 'default_private', 'post', $_POST['post_type'] ) ) {
			$stati = get_post_stati( array( 'public' => true, 'private' => true ), 'names', 'or' );

			if ( 'private' == $_POST['visibility'] && ! in_array( $_POST['hidden_post_status'], $stati ) ) {
				$_POST['post_status'] = $_POST['hidden_post_status'];
				$_REQUEST['post_status'] = $_REQUEST['hidden_post_status'];
				
				$_POST['visibility'] = 'public';
				$_REQUEST['visibility'] = 'public';
			}
		}
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
	
	if ( IS_MU_RS ) {
		scoper_establish_group_scope();
	}
}

function agp_strtolower( $str ) {
	if ( defined( 'SCOPER_MB_STRINGS' ) )
		return mb_strtolower( $str );
	else
		return strtolower( $str );
}
	
?>