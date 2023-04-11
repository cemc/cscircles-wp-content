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
 * since: 1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load module famne_settings_page
 *
 * @return void
 */
function load_mod_famne_settings_page() {
	if ( is_admin() ) :
		FAMNE::AddModule(
			'settings_page',
			array(
				'name'    => 'Settings page',
				'version' => '1.0.0',
			)
		);

		include_once FA_MNE_PLUGIN_DIR . '/modules/settings-page/class.FAMNESettingsPage.php';
		new FAMNESettingsPage();
	endif;
}

add_action( 'fa_mne_modules', 'load_mod_famne_settings_page' );
