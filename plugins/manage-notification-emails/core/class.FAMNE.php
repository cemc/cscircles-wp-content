<?php
/**
 * Manage notification emails settings page class
 *
 * Initializes the plugin.
 *
 * This file is part of the Manage Notification Emails plugin
 * You can find out more about this plugin at https://www.freeamigos.nl
 * Copyright (c) 2006-2015  Virgial Berveling
 *
 * @package WordPress
 * @author Virgial Berveling
 * @copyright 2006-2015
 *
 * since: 1.6.0
 */

if ( ! class_exists( 'FAMNE' ) ) :
	/**
	 * FAMNE
	 */
	class FAMNE {

		private static $modules         = array();
		private static $notices         = array();
		private static $network_managed = false;

		/**
		 * __construct
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'do_modules' ) );
			add_action( 'admin_footer', array( $this, 'admin_notices' ) );
			self::$network_managed = self::get_option( 'famne_network_managed' ) ? true : false;
		}

		/**
		 * Network managed
		 *
		 * @return boolean
		 */
		public static function network_managed() {
			return self::get_option( 'famne_network_managed' ) ? true : false;
		}

		/**
		 * AddModule
		 *
		 * @param  mixed $name
		 * @param  mixed $args
		 * @return void
		 */
		public static function AddModule( $name, $args ) {
			self::$modules[ $name ] = (object) array(
				'name'      => isset( $args['name'] ) ? $args['name'] : $name,
				'version'   => isset( $args['version'] ) ? $args['version'] : FA_MNE_VERSION,
				'licensed'  => isset( $args['licensed'] ) ? true : false,
				'slug'      => isset( $args['slug'] ) ? $args['slug'] : $name,
				'card'      => isset( $args['card'] ) ? $args['card'] : null,
				'option_id' => isset( $args['option_id'] ) ? $args['option_id'] : null,
			);
		}

		/**
		 * Get modules
		 *
		 * @return array
		 */
		public static function getModules() {
			return self::$modules;
		}

		/**
		 * Add Notice
		 *
		 * @param  string  $message
		 * @param  string  $priority
		 * @param  boolean $dismissible
		 * @return void
		 */
		public static function AddNotice( $message = 'missing notification message', $priority = 'error', $dismissible = false ) {
			self::$notices[] = array(
				'message'     => $message,
				'priority'    => $priority,
				'dismissible' => $dismissible,
			);
		}

		/**
		 * Admin notices
		 *
		 * @return void
		 */
		public function admin_notices() {
			$count = count( self::$notices );
			while ( is_array( self::$notices ) && $count > 0 ) :
				$notice      = array_pop( self::$notices );
				$dismissible = $notice['dismissible'] ? ' is-dismissible' : '';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( 'notice notice-' . $notice['priority'] . $dismissible ), esc_html( $notice['message'] ) );
			endwhile;
		}



		/**
		 * Added folder namespacing
		 *
		 * @since 2.0.1
		 */
		public function do_modules() {
			do_action( 'fa_mne_modules' );
		}



		/**
		 * Get_option
		 *
		 * @param  mixed $name
		 * @return mixed $option
		 */
		public static function get_option( $name ) {
			if ( 'fa_mne_version' === $name ) {
				return get_site_option( $name );
			}
			if ( 'famne_network_managed' === $name ) {
				return get_site_option( $name );
			}

			return self::network_managed() ? get_site_option( $name ) : get_option( $name );
		}

		/**
		 * Update_option
		 *
		 * @param  string $name
		 * @param  mixed  $options
		 * @return boolean
		 */
		public static function update_option( $name, $options ) {

			if ( 'fa_mne_version' === $name ) {
				return update_site_option( $name, $options );
			}
			if ( 'famne_network_managed' === $name ) {
				self::$network_managed = '1' === $options ? true : false;
				return update_site_option( $name, $options );
			}
			return self::network_managed() ? update_site_option( $name, $options ) : update_option( $name, $options );
		}

		/**
		 * Default_options
		 *
		 * @return array
		 */
		public static function default_options() {
			return array(
				'wp_new_user_notification_to_user'    => '1',
				'wp_new_user_notification_to_admin'   => '1',
				'wp_notify_postauthor'                => '1',
				'wp_notify_moderator'                 => '1',
				'wp_password_change_notification'     => '1',
				'send_password_change_email'          => '1',
				'send_email_change_email'             => '1',
				'send_password_forgotten_email'       => '1',
				'send_password_admin_forgotten_email' => '1',
				'auto_core_update_send_email'         => '1',
				'auto_plugin_update_send_email'       => '1',
				'auto_theme_update_send_email'        => '1',
			);
		}

		/**
		 * Update_check
		 *
		 * @return void
		 */
		public static function update_check() {
			if ( self::get_option( 'fa_mne_version' ) !== FA_MNE_VERSION ) {
				$options         = self::get_option( 'famne_options' );
				$current_version = self::get_option( 'fa_mne_version' );

				/* Is this the first install, then set all defaults to active */
				if ( false === $options ) {
					self::install();
					return;
				}

				if ( version_compare( $current_version, '1.8.0' ) <= 0 && self::network_managed() ) {
					/** Update to 1.8.1
					 * setting the registrationnotification
					 */
					update_site_option( 'registrationnotification', ! empty( $options['wp_new_user_notification_to_admin'] ) ? 'yes' : 'no' );
				}

				if ( version_compare( $current_version, '1.5.1' ) <= 0 ) {
					/** Update to 1.6.0
					 * setting the newly added options to checked as default
					 */
					$options['auto_plugin_update_send_email'] = '1';
					$options['auto_theme_update_send_email']  = '1';
					self::update_option( 'famne_options', $options );
				}

				if ( '1.1.0' === $current_version ) {
					/** Update 1.1.0 to 1.2.0
					 * setting the newly added options to checked as default
					 */

					$options['send_password_forgotten_email']       = '1';
					$options['send_password_admin_forgotten_email'] = '1';

					self::update_option( 'famne_options', $options );
				}

				/**
				 * Update to 1.4.1
				 * setting the newly added options to checked as default
				 */

				if ( version_compare( $current_version, '1.4.0' ) <= 0 ) {
					$options['auto_core_update_send_email'] = '1';
					self::update_option( 'famne_options', $options );
				}

				/** Update 1.0 to 1.1 fix:
				 * update general wp_new_user_notification option into splitted options
				 */
				if ( version_compare( $current_version, '1.0.0' ) <= 0 ) {
					unset( $options['wp_new_user_notification'] );
					$options['wp_new_user_notification_to_user']  = '1';
					$options['wp_new_user_notification_to_admin'] = '1';
					self::update_option( 'famne_options', $options );
				}

				/* UPDATE DONE! */
				self::update_option( 'fa_mne_version', FA_MNE_VERSION );
			}
		}



		/**
		 * Get the IDs of all sites on the network.
		 *
		 * @since 1.8.0
		 *
		 * @return array The IDs of all sites on the network.
		 */
		protected static function get_all_site_ids() {

			global $wpdb;
			$site_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
						SELECT `blog_id`
						FROM `{$wpdb->blogs}`
						WHERE `site_id` = %d
					",
					$wpdb->siteid
				)
			);
			return $site_ids;
		}


		/**
		 * Install
		 *
		 * @return void
		 */
		public static function install() {
			$famne_options = self::default_options();
			self::update_option( 'famne_options', $famne_options );
			self::update_option( 'fa_mne_version', FA_MNE_VERSION );
		}

		/**
		 * Uninstall
		 *
		 * @return void
		 */
		public static function uninstall() {
			delete_site_option( 'fa_mne_version' );
			delete_site_option( 'famne_options' );
			delete_site_option( 'famne_network_managed' );
			delete_option( 'fa_mne_version' );
			delete_option( 'famne_options' );

			if ( is_multisite() ) {
				foreach ( self::get_all_site_ids() as $id ) {
					delete_blog_option( intval( $id ), 'famne_options' );
				}
			}
		}
	}
endif;
