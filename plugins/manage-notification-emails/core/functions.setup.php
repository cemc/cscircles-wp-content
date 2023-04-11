<?php
/**
 * Manage notification emails add donation link
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

if ( class_exists( 'FAMNE' ) && ! function_exists( 'famne_meta_links' ) ) :

	/**
	 * Famne_meta_links
	 *
	 * @param array  $links
	 * @param string $file
	 * @return array
	 */
	function famne_meta_links( $links, $file ) {
		if ( 'manage-notification-emails/manage-notification-emails.php' === $file ) {
			$links[] = '<a href="https://paypal.me/virgial/5" target="_blank" title="' . __( 'Donate', 'manage-notification-emails' ) . '"><strong>' . __( 'Donate', 'manage-notification-emails' ) . '</strong> <span class="dashicons dashicons-coffee"></span></a>';
		}
		return $links;
	}

	add_filter( 'plugin_row_meta', 'famne_meta_links', 10, 2 );

endif;
