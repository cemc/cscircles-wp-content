<?php
/**
 * Form fields.
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



/**
 * Print start_table_form.
 *
 * @return void
 */
function print_start_table_form() {
	?>
	<table class="form-table"><tbody>
	<?php
}

/**
 * Print end_table_form.
 *
 * @return void
 */
function print_end_table_form() {
	?>
	</tbody></table>
	<?php
}

/**
 * Print checkbox.
 *
 * @param  mixed $options
 * @param  mixed $id
 * @param  mixed $title
 * @param  mixed $label
 * @param  mixed $message
 * @return void
 */
function print_checkbox( $options, $id, $title, $label = '', $message = '' ) {
	$checked = isset( $options[ $id ] ) && '1' === strval( $options[ $id ] ) ? true : false;

	print '<div class="option-container ' . ( $checked ? 'active' : '' ) . '">';
	print '<label><input type="checkbox" name="famne_options[' . $id . ']" value="1" ' . ( $checked ? 'checked="checked"' : '' ) . ' class="checkbox-change"/>';
	print '<strong>' . $title . '</strong><br/>' . $label;
	print '</label>';
	print '<p class="description">' . $message . '</p>';
	print '</div>';
}

/**
 * Print textbox.
 *
 * @param  mixed $options
 * @param  mixed $id
 * @param  mixed $title
 * @param  mixed $label
 * @param  mixed $message
 * @param  mixed $type
 * @param  mixed $placeholder
 * @return void
 */
function print_textbox( $options, $id, $title, $label = '', $message = '', $type = 'text', $placeholder = '' ) {
	$value = isset( $options[ $id ] ) && ! empty( $options[ $id ] ) ? $options[ $id ] : '';

	print '<div class="text-container ' . ( ! empty( $value ) ? 'active' : '' ) . '">';
	print '<br/><strong>' . $title . '</strong><br/>';
	print '<label><input type="' . $type . '" name="famne_options[' . $id . ']" placeholder="' . $placeholder . '" value="' . esc_attr( $value ) . '" />';
	print $label;
	print '</label>';
	print '<p class="description">' . $message . '</p>';
	print '</div>';
}
