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
 * since: 1.8.0
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'FAMNESettingsPage', false ) ) {
	exit;
}
$famne_options = FAMNE::get_option( 'famne_options' );
if ( is_array( $famne_options ) ) :
	$famne_options = array();
endif;

$famne_options['network_managed'] = FAMNE::get_option( 'famne_network_managed' );


?>
<div class="wrap page-width">
<h3><?php _e( 'Manage multisite options', 'manage-notification-emails' ); ?></h3>
<p><?php _e( 'Here you can choose to manage all options for this plugin in one location. No hassle with toggling options on and off for each subsite individually.', 'manage-notification-emails' ); ?></p>
<div class="section-part">
<?php
	print_checkbox( $famne_options, 'network_managed', __( 'Use the network settings.', 'manage-notification-emails' ), __( 'Checking this option will disable per site management. All sites will use these global network settings.', 'manage-notification-emails' ) );
?>
</div>
</div>
