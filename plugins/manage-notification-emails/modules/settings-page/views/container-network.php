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
?>
<div class="wrap">
	<h2><?php _e( 'Manage the notification e-mails', 'manage-notification-emails' ); ?> <small><small><?php _e( 'version', 'manage-notification-emails' ); ?> <?php echo FA_MNE_VERSION; ?></small></small></h2>
	<form method="post" action="edit.php?action=famnesavenetwork" enctype="multipart/form-data">
<?php
	wp_nonce_field( 'famnenetwork_save', 'famnenetwork' );

_e( 'Manage your notification e-mail preferences below.', 'manage-notification-emails' );
?>
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
	</div>
<?php
require_once dirname( __FILE__ ) . '/donatebox.php';
