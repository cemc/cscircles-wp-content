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
 * since: 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load module famne_custom_recipients
 *
 * @return array $args
 */
function load_mod_famne_custom_recipients() {
	FAMNE::AddModule(
		'custom_admin_recipients',
		array(
			'name'      => 'Choose extra recipients for admin notifications',
			'version'   => '1.0.0',
			'option_id' => array( 'custom_admin_recipients' ),
			'card'      => 'card_famne_custom_admin_recipients',
		)
	);

	/**
	 * Card_famne_custom_admin_recipients
	 *
	 * @param  mixed $famne_options
	 * @return void
	 */
	function card_famne_custom_admin_recipients( $famne_options ) {
		?>
	<div class="card">
		<label class="switch"><div class="slider round <?php echo ( ! empty( $famne_options['custom_admin_recipients'] ) ? 'active' : '' ); ?>"><span class="on">ON</span><span class="off">OFF</span></div></label>
		<h2 class="title"><?php esc_html_e( 'Extra admin recipients', 'manage-notification-emails' ); ?></h2>
		<?php _e( 'Here you can add extra admin recipients who will also receive all admin notifications.', 'manage-notification-emails' ); ?>
		<?php print_textbox( $famne_options, 'custom_admin_recipients', __( 'E-mail address(es)', 'manage-notification-emails' ), '', __( 'For multiple addresses separate by comma.', 'manage-notification-emails' ), 'text', 'email1@domain.com,email2@otherdomain.com' ); ?>
	</div>
		<?php
	}

	add_filter( 'wp_mail', 'famne_custom_to_admin_mailaddresses' );

	/**
	 * Filter custom admin email emailadresses.
	 *
	 * @param  mixed $args
	 * @return array $args
	 */
	function famne_custom_to_admin_mailaddresses( $args ) {

		// If to isn't set (who knows why it wouldn't) return args.
		if ( ! isset( $args['to'] ) || empty( $args['to'] ) ) {
			return $args;
		}

		// If TO is an array of emails, means it's probably not an admin email.
		if ( is_array( $args['to'] ) ) {
			return $args;
		}
		if ( ! empty( $args['cc'] ) ) {
			return $args;
		}

		$admin_email = get_option( 'admin_email' );

		// Check if admin email found in string, as TO could be formatted like 'Administrator <admin@domain.com>',
		// and if we specifically check if it's just the email, we may miss some admin emails.
		if ( strpos( $args['to'], $admin_email ) !== false ) {
			$famne_options = FAMNE::get_option( 'famne_options' );
			if ( empty( $famne_options['custom_admin_recipients'] ) ) {
				return $args;
			}

			$emails = array();
			if ( strpos( $famne_options['custom_admin_recipients'], ',' ) > 0 ) :
				$emails = explode( ',', $famne_options['custom_admin_recipients'] );
			else :
				$emails = array( $famne_options['custom_admin_recipients'] );
			endif;

			if ( ! empty( $args['headers'] ) && is_string( $args['headers'] ) ) {
				$args['headers'] = array( $args['headers'] );
			}
			if ( ! empty( $emails ) ) :
				if ( empty( $args['headers'] ) ) {
					$args['headers'] = array();
				}
				foreach ( $emails as $e ) :
					$e = trim( $e );
					if ( is_email( $e ) ) {
						$args['headers'][] = 'Cc: ' . $e;
					}
				endforeach;
			endif;
		}
		return $args;
	}
}

add_action( 'fa_mne_modules', 'load_mod_famne_custom_recipients' );
