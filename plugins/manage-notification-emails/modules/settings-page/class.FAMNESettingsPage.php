<?php
/**
 * Manage notification emails settings page class
 *
 * Displays the settings page.
 *
 * This file is part of the Manage Notification Emails plugin
 * You can find out more about this plugin at https://www.freeamigos.nl
 * Copyright (c) 2006-2015  Virgial Berveling
 *
 * @package WordPress
 * @author Virgial Berveling
 * @copyright 2006-2015
 *
 * version: 1.3.1
 */

if ( ! class_exists( 'FAMNESettingsPage' ) ) :

	/**
	 * FAMNESettingsPage
	 */
	class FAMNESettingsPage {

		private $options;
		const MENU_SLUG          = 'famne-admin';
		const MENU_NETWORK_SLUG  = 'famne-network-admin';
		private $tabs            = array();
		private static $sections = array();

		/**
		 * Start up
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'page_init' ) );
			if ( current_user_can( 'manage_network_options' ) ) :
				add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
				add_action( 'network_admin_edit_famnesavenetwork', array( $this, 'save_networksettings' ) );
				add_action( 'network_admin_notices', array( $this, 'custom_notices' ) );
			endif;
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			add_filter( 'plugin_action_links_' . FA_MNE_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
			add_action( 'init', array( $this, 'famne_load_textdomain' ) );

		}

		/**
		 * Add options page
		 */
		public function network_admin_menu() {
			// This page will be under "Settings".
			add_submenu_page(
				'settings.php', // Parent element.
				'Notification e-mails', // Text in browser title bar.
				'Notification e-mails', // Text to be displayed in the menu.
				'manage_options', // Capability.
				self::MENU_NETWORK_SLUG, // Page slug, will be displayed in URL.
				array( $this, 'print_admin_page' )
			);

			$this->addTabs();
			add_action( 'admin_init', array( $this, 'load_scripts' ) );
		}

		/**
		 * Add options page
		 */
		public function admin_menu() {
			// This page will be under "Settings".
			add_options_page(
				'Settings Admin',
				'Notification e-mails',
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'print_admin_page' )
			);

			$this->addTabs();
			add_action( 'admin_init', array( $this, 'load_scripts' ) );
		}


		public function custom_notices() {

			if ( self::is_famne_network_settings_page() && isset( $_GET['updated'] ) ) {
				echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__( 'Settings updated.', 'manage-notification-emails' ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'manage-notification-emails' ) . '</span></button></div>';
			}
		}


		/**
		 * Add Tabs
		 *
		 * @return void
		 */
		private function addTabs() {
			if ( self::is_famne_network_settings_page() ) :
				$this->tabs[5] = array(
					'title'     => __( 'Network options', 'manage-notification-emails' ),
					'slug'      => 'network',
					'view_file' => 'network.php',
					'icon'      => 'dashicons-admin-multisite',
				);
			endif;

			if ( ! self::is_famne_network_settings_page() || FAMNE::get_option( 'famne_network_managed' ) ) :

				$this->tabs[10] = array(
					'title'     => __( 'Core options', 'manage-notification-emails' ),
					'slug'      => 'settings',
					'view_file' => 'settings.php',
					'icon'      => 'dashicons-admin-settings',
				);
				$this->tabs[30] = array(
					'title'     => __( 'Modules', 'manage-notification-emails' ),
					'slug'      => 'modules',
					'view_file' => 'modules.php',
					'icon'      => 'dashicons-admin-plugins',
				);
				$this->tabs[90] = array(
					'title'     => __( 'Information', 'manage-notification-emails' ),
					'slug'      => 'extra',
					'view_file' => 'extra.php',
					'icon'      => 'dashicons-editor-help',
				);
			endif;
		}


		/**
		 * Register and add settings
		 */
		public function page_init() {

			register_setting(
				'famne_option_group', // Option group.
				'famne_options', // Option name.
				array( $this, 'sanitize' ) // Sanitize.
			);

			add_settings_section(
				'setting_section_id', // ID.
				'', // Title.
				array( $this, 'print_section_info' ), // Callback.
				self::MENU_SLUG // Page.
			);

			if ( isset( $_GET['famne_reset'] ) && is_string( $_GET['famne_reset'] ) && '1' === $_GET['famne_reset'] ) {
				$this->reset_settings();
			}
		}

		/**
		 * Check if is_famne_settings_page
		 *
		 * @return boolean
		 */
		public static function is_famne_settings_page() {
			global $pagenow;
			$getpage = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
			$is_admin_page = 'options-general.php' === $pagenow && self::MENU_SLUG === $getpage;
			return $is_admin_page;
		}

		/**
		 * Check if is_famne_network_settings_page
		 *
		 * @return boolean
		 */
		public static function is_famne_network_settings_page() {
			global $pagenow;
			$getpage = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
			$is_network_admin_page = 'settings.php' === $pagenow && self::MENU_NETWORK_SLUG === $getpage;
			return $is_network_admin_page;
		}

		/**
		 * Load_scripts
		 *
		 * @return void
		 */
		public function load_scripts() {
			if ( ! self::is_famne_settings_page() && ! self::is_famne_network_settings_page() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}


		/**
		 * Sanitize each setting field as needed
		 *
		 * @param array $input Contains all settings fields as array keys.
		 */
		public function sanitize( $input ) {
			if ( empty( $input ) ) {
				$input = array();
			}
			$new_input = array();

			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = '1' === strval( $val ) ? '1' : '';
			}

			foreach ( FAMNE::getModules() as $mod ) :
				if ( ! empty( $mod->option_id ) && is_array( $mod->option_id ) ) :
					foreach ( $mod->option_id as $m ) :
						if ( isset( $input[ $m ] ) ) {
							$new_input[ $m ] = $input[ $m ];
						}
						endforeach;
				endif;
			endforeach;

			$new_input = apply_filters( 'famne_sanitize_settings_page', $new_input );

			return $new_input;
		}


		public function save_networksettings() {

			if ( ! current_user_can( 'manage_network_options' ) ) {
				check_admin_referer( 'fake_info_action', 'fake_info' );
				exit;
			}

			if ( ! empty( $_POST ) && check_admin_referer( 'famnenetwork_save', 'famnenetwork' ) ) {

				$network_activated = FAMNE::get_option( 'famne_network_managed' );
				$network_managed   = ! empty( $_POST['famne_options'] ) && '1' === $_POST['famne_options']['network_managed'] ? '1' : null;
				FAMNE::update_option( 'famne_network_managed', $network_managed );

				if ( $network_managed ) :
					$options = $_POST['famne_options'];

					if ( empty( $network_activated ) ) :
						$options = FAMNE::default_options();
					endif;

					$options = $this->sanitize( $options );

					/* Since 1.8.1 */
					if ( ! empty( $options['wp_new_user_notification_to_admin'] ) ) {
						update_site_option( 'registrationnotification', 'yes' );
					} else {
						update_site_option( 'registrationnotification', 'no' );
					}

					FAMNE::update_option( 'famne_options', $options );
				endif;

				wp_safe_redirect(
					add_query_arg(
						array(
							'page'    => self::MENU_NETWORK_SLUG,
							'updated' => true,
						),
						network_admin_url( 'settings.php' )
					)
				);
				exit;
			}
		}

		private function reset_settings() {
			if ( ! isset( $_GET['famne_reset'] ) || ! is_string( $_GET['famne_reset'] ) || '1' !== $_GET['famne_reset'] || ! isset( $_GET['nonce'] ) ) :
				return;
			endif;

			if ( ! wp_verify_nonce( $_GET['nonce'], 'famne_reset' ) ) :
				wp_nonce_ays( '' );
				die();
			endif;

			if ( self::is_famne_settings_page() ) :
				delete_option( 'fa_mne_version' );
				delete_option( 'famne_options' );
				FAMNE::install();
				echo "<script>document.location='" . admin_url( 'options-general.php?page=famne-admin&updated=1' ) . "'</script>";
			elseif ( self::is_famne_network_settings_page() ) :
				delete_site_option( 'fa_mne_version' );
				delete_site_option( 'famne_options' );
				FAMNE::install();
				echo "<script>document.location='" . admin_url( 'network/settings.php?page=famne-network-admin&updated=1' ) . "'</script>";
			endif;
			exit;
		}

		/**
		 * Print admin_page
		 *
		 * @return void
		 */
		public function print_admin_page() {
			global $famne_options;
			global $pagenow;

			$famne_options = FAMNE::get_option( 'famne_options' );

			foreach ( $this->tabs as $key => $tab ) :
				self::register_section( $tab, $key );
			endforeach;
			do_action( 'famne_register_settings_section' );

			if ( self::is_famne_network_settings_page() ) :
				include_once dirname( __FILE__ ) . '/views/container-network.php';
			else :
				include_once dirname( __FILE__ ) . '/views/container.php';
			endif;
		}

		/**
		 * Register section
		 *
		 * @param  mixed $section
		 * @param  mixed $ord
		 * @return void
		 */
		public static function register_section( $section, $ord = 99 ) {
			self::$sections[ $ord ] = $section;
		}

		/**
		 * Print registered sections
		 *
		 * @param  mixed $output
		 * @return void
		 */
		public static function print_sections( $output = true ) {

			require_once dirname( __FILE__ ) . '/formfields.php';

			ksort( self::$sections );
			foreach ( self::$sections as &$section ) {
				$icon = ! empty( $section['icon'] ) ? 'data-icon="' . esc_attr( $section['icon'] ) . '"' : '';
				echo PHP_EOL;
				echo '<div class="sections-container" id="' . esc_attr( 'sections-' . $section['slug'] ) . '" title="' . esc_attr( ucfirst( __( $section['title'] ) ) ) . '" ' . $icon . '>';

				if ( ! empty( $section['view_file'] ) ) :
					$view_file = FA_MNE_PLUGIN_DIR . '/modules/settings-page/views/' . basename( $section['view_file'] );
					if ( file_exists( $view_file ) ) {
						include_once $view_file;
					}
				endif;
				if ( ! empty( $section['html'] ) ) {
					echo esc_html( $section['html'] );
				}
				echo '</div>';
				echo PHP_EOL;
			}
		}

		/**
		 * Enqueue scripts
		 *
		 * @param  mixed $hook
		 * @return void
		 */
		public function enqueue_scripts( $hook ) {

			wp_enqueue_style(
				'famne-settings-page',
				plugins_url( '/modules/settings-page/assets/main.min.css', FA_MNE_PLUGIN_BASENAME ),
				null,
				time()
			);

			wp_enqueue_script(
				'famne-settings-page',
				plugins_url( '/modules/settings-page/assets/main.min.js', FA_MNE_PLUGIN_BASENAME ),
				array( 'jquery' ),
				time(),
				true
			);
		}

		/**
		 * Add_ plugin settings link
		 *
		 * @param  mixed $links
		 * @return string
		 */
		public function add_action_links( $links ) {
			$mylinks = array(
				'<a href="' . admin_url( 'options-general.php?page=famne-admin' ) . '">' . __( 'Settings' ) . '</a>',
			);
			return array_merge( $links, $mylinks );
		}

		/**
		 * Translations.
		 *
		 * @since 1.4.2
		 */
		public function famne_load_textdomain() {
			load_plugin_textdomain( FA_MNE_SLUG, false, basename( FA_MNE_PLUGIN_DIR ) . '/languages' );
		}
	}

endif;
