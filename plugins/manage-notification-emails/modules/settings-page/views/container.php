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
$managed_by_network = ! empty( FAMNE::get_option( 'famne_network_managed' ) );

?>
<div class="wrap">
	<h2><?php _e( 'Manage the notification e-mails', 'manage-notification-emails' ); ?> <small><small><?php _e( 'version', 'manage-notification-emails' ); ?> <?php echo FA_MNE_VERSION; ?></small></small></h2>
<?php
if ( $managed_by_network ) :
	?>
	<div class="spacer"></div>
	<div class="section-part network" style="text-align:center">
		<div style="text-align:center;height:60px;padding-top:20px;padding-right:50px"><span class="dashicons dashicons-admin-multisite" style="font-size:50px"></span></div>
		<h3 class="title" style="color:#76d450"><?php _e( 'Site managed by network manager', 'manage-notification-emails' ); ?></h3>
		<?php _e( 'Manage notification e-mails settings for this site are globally managed in the multisite network settings menu.', 'manage-notification-emails' ); ?>
		<div class="spacer"></div>
	</div>
	<div class="spacer"></div>
	<?php
else :
	_e( 'Manage your notification e-mail preferences below.', 'manage-notification-emails' );
	?>
	<form method="post" action="options.php?" enctype="multipart/form-data">

	<br/>
	<br/>
		<div class="nav-tab-wrapper"></div>
		<div class="spacer"></div>
		<div class="sections-wrapper">
			<?php
				// This prints out all hidden setting fields.
				settings_fields( 'famne_option_group' );
				FAMNESettingsPage::print_sections();
			?>
			<?php submit_button(); ?>
		</div>
		<div class="clear"></div>
	</form>
	<?php
endif;
?>
</div>
<?php
if ( ! $managed_by_network ) :
	require_once dirname( __FILE__ ) . '/donatebox.php';
endif;
