<?php
/*  Copyright 2006 Vincent Prat  

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
?>

<?php 
	if (!current_user_can('manage_options')) {
		wp_die(__("You are not allowed to change the options of this plugin.", MAILUSERS_I18N_DOMAIN));
	} 
?>

<?php
	$default_subject = '';
	$default_body = '';
	$default_mail_format = 'html';
	$default_sort_users_by = 'none';
	$max_bcc_recipients = '0';
	
	if ( isset( $_POST['default_subject'] ) ) {
		$default_subject = $_POST['default_subject'];
	}
	
	if ( isset( $_POST['default_body'] ) ) {
		$default_body = $_POST['default_body'];
	}
	
	if ( isset( $_POST['default_mail_format'] ) ) {
		$default_mail_format = $_POST['default_mail_format'];
	}
	
	if ( isset( $_POST['default_sort_users_by'] ) ) {
		$default_sort_users_by = $_POST['default_sort_users_by'];
	}
	
	if ( isset( $_POST['max_bcc_recipients'] ) ) {
		$max_bcc_recipients = $_POST['max_bcc_recipients'];
	}
	
	mailusers_update_default_subject( format_to_post($default_subject) );
	mailusers_update_default_body( $default_body );
	mailusers_update_default_mail_format( $default_mail_format );
	mailusers_update_default_sort_users_by( $default_sort_users_by );
	mailusers_update_max_bcc_recipients( $max_bcc_recipients );
?>

<div class="updated fade">
	<p><?php _e('Options set successfully', MAILUSERS_I18N_DOMAIN); ?></p>
</div>

<?php include 'email_users_options_form.php'; ?>
