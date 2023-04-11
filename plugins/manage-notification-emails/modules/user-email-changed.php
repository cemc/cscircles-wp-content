<?php
/**
 * Manage notification emails settings page class
 * *
 * This file is part of the Manage Notification Emails plugin
 * You can find out more about this plugin at https://www.freeamigos.nl
 * Copyright (c) 2006-2015  Virgial Berveling
 *
 * @package WordPress
 * @author Virgial Berveling
 * @copyright 2006-2020
 *
 * since: 1.7.0
 */

// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load module famne_user_email_changed
 *
 * @return void
 */
function load_mod_famne_user_email_changed() {
	$famne_options = FAMNE::get_option( 'famne_options' );

	FAMNE::AddModule(
		'email_changed_email',
		array(
			'name'      => 'Add user e-mailaddress change notifier',
			'version'   => '1.0.0',
			'option_id' => array( 'send_email_change_request_email_to_admin', 'send_email_changed_email_to_admin' ),
			'card'      => 'card_famne_user_email_changed',
		)
	);

	/**
	 * Card_famne_user_email_changed
	 *
	 * @param  mixed $famne_options
	 * @return void
	 */
	function card_famne_user_email_changed( $famne_options ) {
		$active = ! empty( $famne_options['send_email_change_request_email_to_admin'] ) || ! empty( $famne_options['send_email_changed_email_to_admin'] );
		?>
	<div class="card">
		<label class="switch"><div class="slider round <?php echo ( $active ? 'active' : '' ); ?>"><span class="on">ON</span><span class="off">OFF</span></div></label>
		<h2 class="title"><?php _e( 'E-mail address changed by user', 'manage-notification-emails' ); ?></h2>
		<?php print_checkbox( $famne_options, 'send_email_change_request_email_to_admin', __( 'Request to change e-mailadress notification to admin', 'manage-notification-emails' ), __( 'Sends an e-mail to administrators after a user requested to update his or her e-mailaddress.', 'manage-notification-emails' ) ); ?>
		<?php print_checkbox( $famne_options, 'send_email_changed_email_to_admin', __( 'User changed (confirmed) e-mail notification to admin', 'manage-notification-emails' ), __( 'Sends an e-mail to administrators after a user successfully updated his or her e-mailaddress.', 'manage-notification-emails' ) ); ?>
	</div>
		<?php
	}

	if ( ! empty( $famne_options['send_email_change_request_email_to_admin'] ) ) :

		/**
		 * Famne_user_email_change_request_to_admin
		 *
		 * @return void
		 */
		function famne_user_email_change_request_to_admin() {
			// stop if email is not right.
			$email = sanitize_email( $_POST['email'] );
			if ( ! is_email( $email ) || email_exists( $email ) ) {
				return;
			}

			$blog_name   = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$admin_email = get_option( 'admin_email' );

			/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
			$email_text = __(
				'Howdy admin,
        
        Recently a user requested to have his email address on his account changed.

        An email change request has been sent to ###EMAIL###
        
        Regards,
        All at ###SITENAME###
        ###SITEURL###'
			);

			$email_text = str_replace( '###EMAIL###', $email, $email_text );
			$email_text = str_replace( '###SITENAME###', $blog_name, $email_text );
			$email_text = str_replace( '###SITEURL###', home_url(), $email_text );

			wp_mail( $admin_email, sprintf( __( '[%s] User requested Email Change' ), $blog_name ), $email_text );
		}
		add_action( 'personal_options_update', 'famne_user_email_change_request_to_admin' );

	endif;

	if ( ! empty( $famne_options['send_email_changed_email_to_admin'] ) ) :
		/**
		 * Famne_user_email_changed_email_to_admin
		 *
		 * @param  array $email_change_email
		 * @param  mixed $user
		 * @param  mixed $userdata
		 * @return array $email_change_email
		 */
		function famne_user_email_changed_email_to_admin( $email_change_email, $user, $userdata ) {
			$blog_name   = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$admin_email = get_option( 'admin_email' );

			/* translators: Do not translate USERNAME, ADMIN_EMAIL, NEW_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
			$email_text = __(
				'Hi admin,

    This notice confirms that the user ###USERNAME### changed his email address on ###SITENAME### from ###EMAIL### to ###NEW_EMAIL###.

    Regards,
    All at ###SITENAME###
    ###SITEURL###'
			);

			$email_text = str_replace( '###USERNAME###', $user['user_login'], $email_text );
			$email_text = str_replace( '###NEW_EMAIL###', $userdata['user_email'], $email_text );
			$email_text = str_replace( '###EMAIL###', $user['user_email'], $email_text );
			$email_text = str_replace( '###SITENAME###', $blog_name, $email_text );
			$email_text = str_replace( '###SITEURL###', home_url(), $email_text );

			wp_mail( $admin_email, sprintf( __( '[%s] User Email Changed' ), $blog_name ), $email_text, $email_change_email['headers'] );

			return $email_change_email;
		}

		add_filter( 'email_change_email', 'famne_user_email_changed_email_to_admin', 10, 3 );

	endif;
}

add_action( 'fa_mne_modules', 'load_mod_famne_user_email_changed' );
