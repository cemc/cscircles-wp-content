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
 * version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'FAMNESettingsPage', false ) ) {
	exit;
}
global $famne_options;
?>
<div class="wrap page-width">
<h3><?php _e( 'Options for e-mails to users', 'manage-notification-emails' ); ?></h3>
<div class="section-part">
<?php
	print_checkbox( $famne_options, 'wp_new_user_notification_to_user', __( 'New user notification to user', 'manage-notification-emails' ), __( 'Send e-mail with login credentials to a newly-registered user.', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'wp_notify_postauthor', __( 'Notify post author', 'manage-notification-emails' ), __( 'Send e-mail to an author (and/or others) of a comment/trackback/pingback on a post.', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'wp_notify_moderator', __( 'Notify moderator', 'manage-notification-emails' ), __( 'Send e-mail to the moderator of the blog about a new comment that is awaiting approval.', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'send_password_change_email', __( 'Password change notification to user', 'manage-notification-emails' ), __( 'Send e-mail to registered user about changing his or her password. Be careful with this option, because when unchecked, the forgotten password request e-mails will be blocked too.', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'send_email_change_email', __( 'E-mail address change notification to user', 'manage-notification-emails' ), __( 'Send e-mail to registered user about changing his or her E-mail address.', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'send_password_forgotten_email', __( 'Password forgotten e-mail to user', 'manage-notification-emails' ), __( 'Send the forgotten password e-mail to registered user.<br/>(To prevent locking yourself out, sending of the forgotten password e-mail for administrators will still work)', 'manage-notification-emails' ) );
?>
</div>
<br/>
<h2><?php _e( 'Options for e-mails to administrators', 'manage-notification-emails' ); ?></h2>
<div class="section-part">
<?php
	print_start_table_form();
	$user_noti_title = __( 'New user notification to site admin', 'manage-notification-emails' );
	$user_noti_label = __( 'Sends an e-mail to the site admin after a new user is registered.', 'manage-notification-emails' );
if ( is_multisite() && ! FAMNE::network_managed() ) {
	$user_noti = get_site_option( 'registrationnotification' ) === 'yes' ? true : false;
	print '<div class="option-container">';
	print '<label><input type="checkbox" disabled ' . ( $user_noti ? 'checked="checked"' : '' ) . ' />';

	// translators: Adding network settings link.
	print '<strong>' . $user_noti_title . '</strong><br/><em>' . sprintf( __( 'Globally managed in the multisite %1$snetwork settings menu%2$s.', 'manage-notification-emails' ), '<a href="' . network_admin_url( 'settings.php' ) . '">', '</a>' ) . '</em><br/>' . $user_noti_label;
	print '</label>';
	print '</div>';
} else {
	print_checkbox( $famne_options, 'wp_new_user_notification_to_admin', $user_noti_title, $user_noti_label );
}
	print_checkbox( $famne_options, 'wp_password_change_notification', __( 'Password change notification to admin', 'manage-notification-emails' ), __( 'Send e-mail to the blog admin of a user changing his or her password.', 'manage-notification-emails' ) );

	print_checkbox( $famne_options, 'auto_core_update_send_email', __( 'Automatic WordPress core update e-mail', 'manage-notification-emails' ), __( 'Sends an e-mail after a successful automatic WordPress core update to administrators. E-mails about failed updates will always be sent to the administrators and cannot be disabled.', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'auto_plugin_update_send_email', __( 'Automatic WordPress plugin update e-mail', 'manage-notification-emails' ), __( 'Sends an e-mail after a successful automatic plugin update to administrators. E-mails about failed plugin updates will always be sent to the administrators', 'manage-notification-emails' ) );
	print_checkbox( $famne_options, 'auto_theme_update_send_email', __( 'Automatic WordPress theme update e-mail', 'manage-notification-emails' ), __( 'Sends an e-mail after a successful automatic theme update to administrators. E-mails about failed theme updates will always be sent to the administrators', 'manage-notification-emails' ) );
	print_end_table_form();
?>
</div>
<div class="section-part border-warning">
<?php
	print_start_table_form();
	print_checkbox( $famne_options, 'send_password_admin_forgotten_email', __( 'Password forgotten e-mail to administrator', 'manage-notification-emails' ), __( 'Send the forgotten password e-mail to administrators.', 'manage-notification-emails' ), __( 'Okay, this is a <strong style="color:#900">DANGEROUS OPTION !</strong><br/> So be warned, because unchecking this option prevents sending out the forgotten password e-mail to all administrators. So hold on to your own password and uncheck this one at your own risk ;-)', 'manage-notification-emails' ) );
	print_end_table_form();
?>
</div>
<p style="float:right"><a href="#" onclick="resetToDefaults();return false" class="button"><?php _e( 'Reset settings', 'manage-notification-emails' ); ?></a></p>
</div>
<?php
$admin_page = FAMNESettingsPage::is_famne_network_settings_page() ? 'network/settings.php?page=famne-network-admin&famne_reset=1' : 'options-general.php?page=famne-admin&famne_reset=1';
$reset_url  = str_replace( '&amp;', '&', wp_nonce_url( admin_url( $admin_page ), 'famne_reset', 'nonce' ) ) . '#settings';
?>
<script>
	function resetToDefaults(){
		var r = confirm('reset to default settings?');
		if (r == true) document.location='<?php echo $reset_url; ?>';
	}
</script>
