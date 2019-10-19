<?php
// note: This file was moved into admin/misc subdirectory to avoid detection as a plugin file by the WP plugin updater (due to Plugin Name search string)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

function scoper_pp_msg( $on_options_page = false ) {
	$more_url = ( $on_options_page ) ? '#pp-more' : admin_url( 'admin.php?page=rs-options&show_pp=1', SCOPER_BASENAME );

	$slug = 'role-scoper-migration-advisor';
	$use_network_admin = is_multisite() && ( is_network_admin() || agp_is_plugin_network_active(SCOPER_FILE) ) && is_super_admin();
	$_url = "update.php?action=$slug&amp;plugin=$slug&amp;pp_install=1&amp;TB_iframe=true";
	//$_url = "update.php?action=$slug&plugin=$slug&pp_install=1&TB_iframe=true";
	$install_url = ( $use_network_admin ) ? network_admin_url($_url) : admin_url($_url);
	
	$rs_migration_url = wp_nonce_url($install_url, "{$slug}_$slug");
	//$install_link =  "<span><a href='$url' class='thickbox' target='_blank'>" . __awp('install', 'pp') . '</a></span>';

	$msg = sprintf( __('Role Scoper development and support is winding down.&nbsp;&nbsp;For compatibility with the current WP version and a better permissions model, %1$slearn more%2$s, %3$sinstall the migration advisor%4$s, grab %5$s and buy %6$s.', 'scoper'), '<a href="' . $more_url . '">', '</a>', '<span><a href="' . $rs_migration_url . '" class="thickbox" title="install Role Scoper Migration Advisor">', '</a></span>', '<span class="plugins update-message"><a href="' . awp_plugin_info_url('press-permit-core') . '" class="thickbox" title=" Press Permit Core">Press&nbsp;Permit&nbsp;Core</a></span>', "<a href='http://presspermit.com'>Press&nbsp;Permit&nbsp;Pro</a>", '<span style="font-weight:bold;color:#c00">', '</span>' );

	return $msg;
}

?>