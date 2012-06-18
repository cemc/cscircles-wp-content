<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// separated these functions into separate module for use by RS extension plugins

if ( ! function_exists('awp_ver') ) {
function awp_ver($wp_ver_requirement) {
	static $cache_wp_ver;
	
	if ( empty($cache_wp_ver) ) {
		global $wp_version;
		$cache_wp_ver = $wp_version;
	}
	
	if ( ! version_compare($cache_wp_ver, '0', '>') ) {
		// If global $wp_version has been wiped by WP Security Scan plugin, temporarily restore it by re-including version.php
		if ( file_exists (ABSPATH . WPINC . '/version.php') ) {
			include ( ABSPATH . WPINC . '/version.php' );
			$return = version_compare($wp_version, $wp_ver_requirement, '>=');
			$wp_version = $cache_wp_ver;	// restore previous wp_version setting, assuming it was cleared for security purposes
			return $return;
		} else
			// Must be running a future version of WP which doesn't use version.php
			return true;
	}

	// normal case - global $wp_version has not been tampered with
	return version_compare($cache_wp_ver, $wp_ver_requirement, '>=');
}
}

// TODO: move these function to core-admin_lib.php, update extensions accordingly
if ( ! function_exists('awp_plugin_info_url') ) {
function awp_plugin_info_url( $plugin_slug ) {
	$url = get_option('siteurl') . "/wp-admin/plugin-install.php?tab=plugin-information&plugin=$plugin_slug";
	return $url;
}
}

if ( ! function_exists('awp_plugin_update_url') ) {
function awp_plugin_update_url( $plugin_file ) {
	$url = wp_nonce_url("update.php?action=upgrade-plugin&amp;plugin=$plugin_file", "upgrade-plugin_$plugin_file");
	return $url;
}
}

if ( ! function_exists('awp_plugin_search_url') ) {
function awp_plugin_search_url( $search, $search_type = 'tag' ) {
	$wp_org_dir = 'tags';
	
	$url = get_option('siteurl') . "/wp-admin/plugin-install.php?tab=search&type=$search_type&s=$search";
	return $url;
}
}


if ( ! function_exists('awp_is_mu') ) {
function awp_is_mu() {
	global $wpdb, $wpmu_version;
	
	return ( ( defined('MULTISITE') && MULTISITE ) || function_exists('get_current_site_name') || ! empty($wpmu_version) || ( ! empty( $wpdb->base_prefix ) && ( $wpdb->base_prefix != $wpdb->prefix ) ) );
}
}

// returns true GMT timestamp
if ( ! function_exists('agp_time_gmt') ) {
function agp_time_gmt() {	
	return strtotime( gmdate("Y-m-d H:i:s") );
}
}

// date_i18n does not support pre-1970 dates, as of WP 2.8.4
if ( ! function_exists('agp_date_i18n') ) {
function agp_date_i18n( $datef, $timestamp ) {
	if ( $timestamp >= 0 )
		return date_i18n( $datef, $timestamp );
	else
		return date( $datef, $timestamp );
}
}

if ( ! function_exists('awp_post_type_from_uri') ) {
function awp_post_type_from_uri() {
	return cr_find_post_type();
}
}

// wrapper for __(), prevents WP strings from being forced into plugin .po
if ( ! function_exists( '__awp' ) ) {
function __awp( $string, $unused = '' ) {
	return __( $string );		
}
}

?>