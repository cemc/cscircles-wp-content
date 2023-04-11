<?php
/**
 * Manage notification emails version switch
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

if ( ! defined( 'FA_MNE_PLUGIN_DIR' ) ) {
	die();
}

global $wp_version;
if ( version_compare( $wp_version, '5.2.0' ) >= 0 ) {
	include_once FA_MNE_PLUGIN_DIR . '/modules/pluggable/pluggable-functions-1.5.php';
} elseif ( version_compare( $wp_version, '4.7.0' ) >= 0 ) {
	include_once FA_MNE_PLUGIN_DIR . '/modules/pluggable/pluggable-functions-1.3.php';
} else {
	include_once FA_MNE_PLUGIN_DIR . '/modules/pluggable/pluggable-functions-1.2.php';
}
