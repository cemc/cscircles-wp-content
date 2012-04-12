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
	if (!current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP)) {
		wp_die(__("You are not allowed to send emails to user groups.", MAILUSERS_I18N_DOMAIN));
	}
?>

<?php
	$err_msg = '';

	get_currentuserinfo();
	// Use current user info only if from name and address has not been set by the form
	if (!isset($_POST['fromName']) || !isset($_POST['fromAddress']) || empty($_POST['fromName']) || empty($_POST['fromAddress'])) {
		$from_name = $user_identity;
		$from_address = $user_email;
	} else {
		$from_name = $_POST['fromName'];
		$from_address = $_POST['fromAddress'];
	}

	// Analyse form input, check for blank fields
	if ( !isset( $_POST['mail_format'] ) || trim($_POST['mail_format'])=='' ) {
		$err_msg = $err_msg . __('You must specify the mail format.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	} else {
		$mail_format = $_POST['mail_format'];
	}

	if ( !isset($_POST['send_roles']) || !is_array($_POST['send_roles']) || empty($_POST['send_roles']) ) {
		$err_msg = $err_msg . __('You must select at least a role.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	} else {
		$send_roles = $_POST['send_roles'];
	}

	if ( !isset( $_POST['subject'] ) || trim($_POST['subject'])=='' ) {
		$err_msg = $err_msg . __('You must enter a subject.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	} else {
		$subject = $_POST['subject'];
	}

	if ( !isset( $_POST['mailContent'] ) || trim($_POST['mailContent'])=='' ) {
		$err_msg = $err_msg . __('You must enter some content.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	} else {
		$mail_content = $_POST['mailContent'];
	}
	
	// If error, we simply show the form again
	if ( $err_msg!='' ) {
		// Redirect to the form page
		include 'email_users_group_mail_form.php';
	} else {
		// No error, send the mail
		
		// Do some HTML homework if needed
		//--
		if ($mail_format=='html') {
			$mail_content = wpautop($mail_content);
		}		
?>

	<div class="wrap">
	<?php
		// Fetch users
		// --
		$recipients = mailusers_get_recipients_from_roles($send_roles, $user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

		if (empty($recipients)) {
	?>
			<p><strong><?php _e('No recipients were found.', MAILUSERS_I18N_DOMAIN); ?></strong></p>
	<?php
		} else {
			$num_sent = mailusers_send_mail($recipients, $subject, $mail_content, $mail_format, $from_name, $from_address);
			if (false === $num_sent) {
				echo "<p class=\"error\">There was a problem trying to send email to users.</p>";
			} else if (0 === $num_sent) {
				echo "<p class=\"error\">No email has been sent to other users. This may be because no valid email addresses were found.</p>";
			} else if ($num_sent > 0 && $num_sent == count($recipients)){
	?>
			<div class="updated fade">
				<p><?php echo sprintf(__("Notification sent to %s user(s).", MAILUSERS_I18N_DOMAIN), $num_sent); ?></p>
			</div>
	<?php
			} else if ($num_sent > count($recipients)) {
				echo "<div class=\"error\"><p>WARNING: More email has been sent than the number of recipients found.</p></div>";
			} else {
				echo "<p class=\"updated\">Email has been sent to $num_sent users, but ".count($recipients)." recipients were originally found. Perhaps some users don't have valid email addresses?</p>";
			}
			include 'email_users_group_mail_form.php';
		}
	?>
	</div>

<?php
	}
?>
