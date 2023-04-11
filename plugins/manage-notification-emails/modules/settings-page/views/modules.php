<?php
/**
 * Custom view
 *
 * This file is part of the Manage Notification Emails plugin
 * You can find out more about this plugin at https://www.freeamigos.nl
 * Copyright (c) 2006-2015  Virgial Berveling
 *
 * @package WordPress
 * @author Virgial Berveling
 * @copyright 2006-2015
 *
 * since: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'FAMNESettingsPage', false ) ) {
	exit;
}
global $famne_options;

?>

<div class="wrap">
<h3><?php _e( 'Extra modules', 'manage-notification-emails' ); ?></h3>
<p><?php _e( 'The modules below are options which are not available in WordPress by default.', 'manage-notification-emails' ); ?></p>
<div class="spacer"></div>
<div class="modules">
<?php
foreach ( FAMNE::getModules() as $mod ) :
	if ( ! in_array( $mod->slug, array( 'pluggable', 'settings_page' ), true ) && ! empty( $mod->card ) ) {
		call_user_func( $mod->card, $famne_options );
	}
endforeach;

if ( current_user_can( 'manage_network_options' ) ) :
	?>
	<div class="card" style="text-align:center">
		<div style="text-align:center;height:60px;padding-top:20px;padding-right:50px"><span class="dashicons dashicons-admin-multisite" style="font-size:50px"></span></div>
		<h3 class="title" style="color:#76d450"><?php _e( 'Multisite managed settings available', 'manage-notification-emails' ); ?></h3>
		<?php _e( 'For this environment, it is possible for you to manage all settings globally in the network settings menu.', 'manage-notification-emails' ); ?>
		<div class="spacer"></div>
	</div>
<?php endif; ?>
</div>
</div>
