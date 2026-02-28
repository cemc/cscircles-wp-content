<?php
/**
 * Plugin Name: Prevent Password Reset
 * Plugin URI: http://themehybrid.com/plugins/prevent-password-reset
 * Description: Prevents password reset for select users via the WordPress "lost password" form. This plugin adds a checkbox to each user's profile in the admin. If selected, it prevents the user's password from being reset.
 * Version: 0.2.0
 * Author: Justin Tadlock
 * Author URI: http://justintadlock.com
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License as published by the Free Software Foundation; either version 2 of the License, 
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write 
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package   PreventPasswordReset
 * @version   0.2.0
 * @author    Justin Tadlock <justin@justintadlock.com>
 * @copyright Copyright (c) 2012 - 2013, Justin Tadlock
 * @link      http://themehybrid.com/plugins/prevent-password-reset
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/* Set up the plugin on the 'plugins_loaded' hook. */
add_action( 'plugins_loaded', 'ppr_setup' );

/**
 * Plugin setup function.  Loads the translation files and adds each action and filter to their appropriate hook.
 *
 * @since  0.1.0
 * @access public
 * @return void
 */
function ppr_setup() {

	/* Loads the plugin translation files. */
	load_plugin_textdomain( 'prevent-password-reset', false, 'prevent-password-reset/languages' );

	/* Filters whether the user's password can be reset. */
	add_filter( 'allow_password_reset', 'ppr_allow_password_reset', 10, 2 );

	/* Hook into user's personal options to display checkbox. */
	add_action( 'personal_options', 'ppr_personal_options' );

	/* Save whether the user allows password resetting. */
	add_action( 'personal_options_update',  'ppr_save_user_meta' );
	add_action( 'edit_user_profile_update', 'ppr_save_user_meta' );

	/* Custom meta for plugin on the plugins admin screen. */
	add_filter( 'plugin_row_meta', 'ppr_plugin_row_meta', 10, 2 );
}

/**
 * Checks whether a user will allow their pasword to be reset via the "lost password" form.  This is 
 * saved as user meta ('_prevent_password_reset').
 *
 * @since  0.1.0
 * @param  bool   $allow    Whether the password can be reset.
 * @param  int    $user_id  The ID of the user.
 * @return bool
 */
function ppr_allow_password_reset( $allow, $user_id ) {

	$prevent = get_user_meta( $user_id, '_prevent_password_reset', true );

	/* If user has selected to prevent password resetting, set $allow to false. */
	if ( 1 === absint( $prevent ) )
		$allow = false;

	return $allow;
}

/**
 * Displays a checkbox in the "Personal Options" section of the user profile page for selecting whether their 
 * password can be reset.
 *
 * @since  0.1.0
 * @access public
 * @param  object  $user  The user object for the user currently being edited.
 * @return void
 */
function ppr_personal_options( $user ) { ?>
	<tr class="ppr-password-reset">
		<th scope="row"><?php _e( 'Password Reset', 'prevent-password-reset' ); ?></th>
		<td>
			<fieldset>
				<legend class="screen-reader-text"><span><?php _e( 'Password Reset', 'prevent-password-reset' ); ?></span></legend>
				<label for="prevent_password_reset">
					<input name="prevent_password_reset" type="checkbox" id="prevent_password_reset" value="1" <?php checked( absint( get_user_meta( $user->ID, '_prevent_password_reset', true ) ), 1 ); ?> />
					<?php _e( 'Prevent password from being reset via the "lost password" form.', 'prevent-password-reset' ); ?>
				</label>
			</fieldset>
		</td>
	</tr>
<?php }

/**
 * Saves whether the user allows their password to be reset from via the "lost password" form as user meta 
 * ('_prevent_password_reset').
 *
 * @since 0.1.0
 * @param int $user_id The ID of the user to save the metadata for.
 */
function ppr_save_user_meta( $user_id ) {

	$meta_value = isset( $_POST['prevent_password_reset'] ) ? 1 : 0;

	update_user_meta( $user_id, '_prevent_password_reset', $meta_value );
}

/**
 * Adds support, rating, and donation links to the plugin row meta on the plugins admin screen.
 *
 * @since  0.2.0
 * @access public
 * @param  array  $meta
 * @param  string $file
 * @return array
 */
function ppr_plugin_row_meta( $meta, $file ) {

	if ( preg_match( '/prevent-password-reset\.php/i', $file ) ) {
		$meta[] = '<a href="http://themehybrid.com/support">' . __( 'Plugin support', 'prevent-password-reset' ) . '</a>';
		$meta[] = '<a href="http://wordpress.org/support/view/plugin-reviews/prevent-password-reset#postform">' . __( 'Rate plugin', 'prevent-password-reset' ) . '</a>';
		$meta[] = '<a href="http://themehybrid.com/donate">' . __( 'Donate', 'prevent-password-reset' ) . '</a>';
	}

	return $meta;
}

?>