<?php
if ( ! function_exists( '_scoper_activate' ) ) {
function _scoper_activate() {
	// set_current_user may have triggered DB setup already
	if ( empty ($GLOBALS['scoper_db_setup_done']) ) {
		require_once( dirname(__FILE__).'/db-setup_rs.php');
		$ver = (array) get_option( 'scoper_version' );
		$db_ver = ( isset( $ver['db_version'] ) ) ? $ver['db_version'] : '';
		scoper_db_setup( $db_ver );
	}
	
	require_once( dirname(__FILE__).'/admin/admin_lib_rs.php');
	ScoperAdminLib::sync_wproles();

	scoper_flush_site_rules();
	scoper_expire_file_rules();
}
}

if ( ! function_exists( '_scoper_deactivate' ) ) {
function _scoper_deactivate() {
	delete_option('scoper_page_ancestors');
	
	global $wp_taxonomies;
	if ( ! empty($wp_taxonomies) ) {
		foreach ( array_keys($wp_taxonomies) as $taxonomy ) {
			delete_option("{$taxonomy}_children");
			delete_option("{$taxonomy}_children_rs");
			delete_option("{$taxonomy}_ancestors_rs");
		}
	}

	require_once( dirname(__FILE__).'/role-scoper_init.php');
	scoper_clear_site_rules();
	scoper_clear_all_file_rules();
}
}

?>