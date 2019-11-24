<?php 

/*
 * Plugin Name: CB Change Mail Sender
 * Description: Easy to change mail sender name and email from wordpress default name and email.
 * Version: 1.2.2
 * Author: Md Abul Bashar
 * Author URI: http://www.codingbank.com
 */

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function cb_mail_load_textdomain() {
  load_plugin_textdomain( 'cb-mail', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}

add_action( 'init', 'cb_mail_load_textdomain' );

function cb_mail_sender_register() {
	add_settings_section('cb_mail_sender_section', __('CB Mail Sender Options', 'cb-mail'), 'cb_mail_sender_text', 'cb_mail_sender');

	add_settings_field('cb_mail_sender_id', __('CB Mail Sender Name','cb-mail'), 'cb_mail_sender_function', 'cb_mail_sender',  'cb_mail_sender_section');

	register_setting('cb_mail_sender_section', 'cb_mail_sender_id');

	add_settings_field('cb_mail_sender_email_id', __('CB Mail Sender Email', 'cb-mail'), 'cb_mail_sender_email', 'cb_mail_sender',  'cb_mail_sender_section');

	register_setting('cb_mail_sender_section', 'cb_mail_sender_email_id');

}
add_action('admin_init', 'cb_mail_sender_register');



function cb_mail_sender_function(){

	printf('<input name="cb_mail_sender_id" type="text" class="regular-text" value="%s" placeholder="CB Mail Name"/>', get_option('cb_mail_sender_id'));

}
function cb_mail_sender_email() {
	printf('<input name="cb_mail_sender_email_id" type="email" class="regular-text" value="%s" placeholder="no_reply@yourdomain.com"/>', get_option('cb_mail_sender_email_id'));


}

function cb_mail_sender_text() {

	printf('%s You may change your WordPress Default mail sender name and email %s', '<p>', '</p>');

}



function cb_mail_sender_menu() {
	add_menu_page(__('CB Mail Sender Options', 'cb-mail'), __('CB Mail Sender', 'cb-mail'), 'manage_options', 'cb_mail_sender', 'cb_mail_sender_output', 'dashicons-email');


}
add_action('admin_menu', 'cb_mail_sender_menu');



function cb_mail_sender_output(){
?>	
	<?php settings_errors();?>
	<form action="options.php" method="POST">
		<?php do_settings_sections('cb_mail_sender');?>
		<?php settings_fields('cb_mail_sender_section');?>
		<?php submit_button();?>
	</form>
<?php }








// Change the default wordpress@ email address
add_filter('wp_mail_from', 'cb_new_mail_from');
add_filter('wp_mail_from_name', 'cb_new_mail_from_name');
 
function cb_new_mail_from($old) {
	return get_option('cb_mail_sender_email_id');
}
function cb_new_mail_from_name($old) {
	return get_option('cb_mail_sender_id');
}