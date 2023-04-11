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

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'FAMNE', false ) ) {
	exit;
}
global $famne_options;
?>

	<div class="wrap page-width">
	<h3><?php _e( 'Information', 'manage-notification-emails' ); ?></h3>
	<div class="section-part extra">
	<h3><?php _e( 'Available Manage Notification E-mail Modules', 'manage-notification-emails' ); ?></h3>
	<ul>
<?php
$modules = FAMNE::getModules();
foreach ( $modules as $module ) :
	$icon = $module->licensed ? 'dashicons-products"' : 'dashicons-admin-plugins';
	?>
	<li><span class="dashicons-before <?php echo $icon; ?>"></span><?php echo esc_html( $module->name ); ?> (<?php echo esc_html( $module->version ); ?>)</li>
<?php endforeach; ?>
	</ul>
</div>
</div>
