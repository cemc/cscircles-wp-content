<?php
/*
Plugin Name: Honeypot for Contact Form 7
Plugin URI: http://www.nocean.ca/plugins/honeypot-module-for-contact-form-7-wordpress-plugin/
Description: Add honeypot anti-spam functionality to the popular Contact Form 7 plugin.
Author: Nocean
Author URI: http://www.nocean.ca
Version: 1.14.1
Text Domain: contact-form-7-honeypot
Domain Path: /languages/
*/

/*  Copyright 2019  Ryan McLaughlin  (email : hello@nocean.ca)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
* Load textdomain
*
* Technically depreciated, all translations are handled via 
* https://translate.wordpress.org/projects/wp-plugins/contact-form-7-honeypot
* Leaving in the code for now.
*/
add_action( 'plugins_loaded', 'wpcf7_honeypot_load_textdomain' );
function wpcf7_honeypot_load_textdomain() {
	load_plugin_textdomain( 'contact-form-7-honeypot', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}


/**
 * 
 * Check if CF7 is installed and activated.
 * 		Deliver a message to install CF7 if not.
 * 
 */
add_action( 'admin_init', 'wpcf7_honeypot_has_parent_plugin' );
function wpcf7_honeypot_has_parent_plugin() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
		add_action( 'admin_notices', 'wpcf7_honeypot_nocf7_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) ); 

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

function wpcf7_honeypot_nocf7_notice() { ?>
	<div class="error">
		<p>
			<?php printf(
				__('%s must be installed and activated for the CF7 Honeypot plugin to work', 'contact-form-7-honeypot'),
				'<a href="'.admin_url('plugin-install.php?tab=search&s=contact+form+7').'">Contact Form 7</a>'
			); ?>
		</p>
	</div>
	<?php
}


/**
 *
 * Initialize the shortcode
 * 		This lets CF7 know about Mr. Honeypot.
 * 
 */
add_action('wpcf7_init', 'wpcf7_add_form_tag_honeypot', 10);
function wpcf7_add_form_tag_honeypot() {

	// Test if new 4.6+ functions exists
	if (function_exists('wpcf7_add_form_tag')) {
		wpcf7_add_form_tag( 
			'honeypot', 
			'wpcf7_honeypot_formtag_handler', 
			array( 
				'name-attr' => true, 
				'do-not-store' => true,
				'not-for-mail' => true
			)
		);
	} else {
		wpcf7_add_shortcode( 'honeypot', 'wpcf7_honeypot_formtag_handler', true );
	}
}


/**
 * 
 * Form Tag handler
 * 		This is where we generate the honeypot HTML from the shortcode options
 * 
 */
function wpcf7_honeypot_formtag_handler( $tag ) {

	// Test if new 4.6+ functions exists
	$tag = (class_exists('WPCF7_FormTag')) ? new WPCF7_FormTag( $tag ) : new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( 'text' );
	$atts = array();
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_option( 'id', 'id', true );
	
	$atts['wrapper_id'] = $tag->get_option('wrapper-id');
	$wrapper_id = (!empty($atts['wrapper_id'])) ? reset($atts['wrapper_id']) : uniqid('wpcf7-');

	$atts['message'] = apply_filters('wpcf7_honeypot_accessibility_message', __('Please leave this field empty.','contact-form-7-honeypot'));
	$atts['name'] = $tag->name;
	$atts['type'] = $tag->type;
	$atts['validautocomplete'] = $tag->get_option('validautocomplete');
	$atts['move_inline_css'] = $tag->get_option('move-inline-css');
	$atts['nomessage'] = $tag->get_option('nomessage');
	$atts['validation_error'] = $validation_error;
	$atts['css'] = apply_filters('wpcf7_honeypot_container_css', 'display:none !important; visibility:hidden !important;');
	$inputid = (!empty($atts['id'])) ? 'id="'.$atts['id'].'" ' : '';
	$inputid_for = ($inputid) ? 'for="'.$atts['id'].'" ' : '';
	$autocomplete_value = ($atts['validautocomplete']) ? 'off' : 'nope';

	// Check if we should move the CSS off the element and into the footer
	if (!empty($atts['move_inline_css']) && $atts['move_inline_css'][0] === 'true') {
		$hp_css = '#'.$wrapper_id.' {'.$atts['css'].'}';
		wp_register_style( 'wpcf7-'.$wrapper_id.'-inline', false);
		wp_enqueue_style( 'wpcf7-'.$wrapper_id.'-inline' );
		wp_add_inline_style( 'wpcf7-'.$wrapper_id.'-inline', $hp_css );
		$el_css = '';
	} else {
		$el_css = 'style="'.$atts['css'].'"';
	}

	$html = '<span id="'.$wrapper_id.'" class="wpcf7-form-control-wrap ' . $atts['name'] . '-wrap" '.$el_css.'>';
	if (!$atts['nomessage']) {
		$html .= '<label ' . $inputid_for . ' class="hp-message">'.$atts['message'].'</label>';
	}
	$html .= '<input ' . $inputid . 'class="' . $atts['class'] . '"  type="text" name="' . $atts['name'] . '" value="" size="40" tabindex="-1" autocomplete="'.$autocomplete_value.'" />';
	$html .= $validation_error . '</span>';

	// Hook for filtering finished Honeypot form element.
	return apply_filters('wpcf7_honeypot_html_output',$html, $atts);
}


/**
 * 
 * Honeypot Validation Filter
 * 		Bots beware!
 * 
 */
add_filter( 'wpcf7_validate_honeypot', 'wpcf7_honeypot_filter' ,10,2);

function wpcf7_honeypot_filter ( $result, $tag ) {
	
	// Test if new 4.6+ functions exists
	$tag = (class_exists('WPCF7_FormTag')) ? new WPCF7_FormTag( $tag ) : new WPCF7_Shortcode( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] ) ? $_POST[$name] : '';
	
	if ( $value != '' || !isset( $_POST[$name] ) ) {
		$result['valid'] = false;
		$result['reason'] = array( $name => wpcf7_get_message( 'spam' ) );
	}

	return $result;
}


/**
 * 
 * Tag generator
 * 		Adds Honeypot to the CF7 form editor
 * 
 */
add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_honeypot', 35 );

function wpcf7_add_tag_generator_honeypot() {
	if (class_exists('WPCF7_TagGenerator')) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'honeypot', __( 'Honeypot', 'contact-form-7-honeypot' ), 'wpcf7_tg_pane_honeypot' );
	} else if (function_exists('wpcf7_add_tag_generator')) {
		wpcf7_add_tag_generator( 'honeypot', __( 'Honeypot', 'contact-form-7-honeypot' ),	'wpcf7-tg-pane-honeypot', 'wpcf7_tg_pane_honeypot' );
	}
}

function wpcf7_tg_pane_honeypot($contact_form, $args = '') {
	if (class_exists('WPCF7_TagGenerator')) {
		$args = wp_parse_args( $args, array() );
		$description = __( "Generate a form-tag for a spam-stopping honeypot field. For more details, see %s.", 'contact-form-7-honeypot' );
		$desc_link = '<a href="https://wordpress.org/plugins/contact-form-7-honeypot/" target="_blank">'.__( 'CF7 Honeypot', 'contact-form-7-honeypot' ).'</a>';
		?>
		<div class="control-box">
			<fieldset>
				<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

				<table class="form-table"><tbody>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /><br>
							<em><?php echo esc_html( __( 'This can be anything, but should be changed from the default generated "honeypot". For better security, change "honeypot" to something more appealing to a bot, such as text including "email" or "website".', 'contact-form-7-honeypot' ) ); ?></em>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'ID (optional)', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class (optional)', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-wrapper-id' ); ?>"><?php echo esc_html( __( 'Wrapper ID (optional)', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="wrapper-id" class="wrapper-id-value oneline option" id="<?php echo esc_attr( $args['content'] . '-wrapper-id' ); ?>" /><br>
							<em><?php echo esc_html( __( 'By default the markup that wraps this form item has a random ID. You can customize it here. If you\'re unsure, leave blank.', 'contact-form-7-honeypot' ) ); ?></em>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-validautocomplete' ); ?>"><?php echo esc_html( __( 'Use W3C Valid Autocomplete (optional)', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="validautocomplete:true" id="<?php echo esc_attr( $args['content'] . '-validautocomplete' ); ?>" class="validautocompletevalue option" /><br />
							<em><?php echo __('See <a href="https://wordpress.org/support/topic/w3c-validation-in-1-11-explanation-and-work-arounds/" target="_blank" rel="noopener">here</a> for more details. If you\'re unsure, leave this unchecked.','contact-form-7-honeypot'); ?></em>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-move-inline-css' ); ?>"><?php echo esc_html( __( 'Move inline CSS (optional)', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="move-inline-css:true" id="<?php echo esc_attr( $args['content'] . '-move-inline-css' ); ?>" class="move-inline-css-value option" /><br />
							<em><?php echo __('Moves the CSS to hide the honeypot from the element to the footer of the page. May help confuse bots.','contact-form-7-honeypot'); ?></em>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-nomessage' ); ?>"><?php echo esc_html( __( 'Disable Accessibility Label (optional)', 'contact-form-7-honeypot' ) ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="nomessage:true" id="<?php echo esc_attr( $args['content'] . '-nomessage' ); ?>" class="messagekillvalue option" /><br />
							<em><?php echo __('If checked, the accessibility label will not be generated. This is not recommended, but may improve spam blocking. If you\'re unsure, leave this unchecked.','contact-form-7-honeypot'); ?></em>
						</td>
					</tr>

				</tbody></table>
			</fieldset>
		</div>

		<div class="insert-box">
			<input type="text" name="honeypot" class="tag code" readonly="readonly" onfocus="this.select()" />

			<div class="submitbox">
				<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7-honeypot' ) ); ?>" />
			</div>

			<br class="clear" />
		</div>
	<?php } else { ?>
		<div id="wpcf7-tg-pane-honeypot" class="hidden">
			<form action="">
				<table>
					<tr>
						<td>
							<?php echo esc_html( __( 'Name', 'contact-form-7-honeypot' ) ); ?><br />
							<input type="text" name="name" class="tg-name oneline" /><br />
							<em><small><?php echo esc_html( __( 'For better security, change "honeypot" to something less bot-recognizable.', 'contact-form-7-honeypot' ) ); ?></small></em>
						</td>
						<td></td>
					</tr>
					
					<tr>
						<td colspan="2"><hr></td>
					</tr>

					<tr>
						<td>
							<?php echo esc_html( __( 'ID (optional)', 'contact-form-7-honeypot' ) ); ?><br />
							<input type="text" name="id" class="idvalue oneline option" />
						</td>
						<td>
							<?php echo esc_html( __( 'Class (optional)', 'contact-form-7-honeypot' ) ); ?><br />
							<input type="text" name="class" class="classvalue oneline option" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="checkbox" name="nomessage:true" id="nomessage" class="messagekillvalue option" /> <label for="nomessage"><?php echo esc_html( __( 'Don\'t Use Accessibility Message (optional)', 'contact-form-7-honeypot' ) ); ?></label><br />
							<em><?php echo __('If checked, the accessibility message will not be generated. <strong>This is not recommended</strong>. If you\'re unsure, leave this unchecked.','contact-form-7-honeypot'); ?></em>
						</td>
					</tr>

					<tr>
						<td colspan="2"><hr></td>
					</tr>			
				</table>
				
				<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7-honeypot' ) ); ?><br /><input type="text" name="honeypot" class="tag" readonly="readonly" onfocus="this.select()" /></div>
			</form>
		</div>
	<?php }
}
