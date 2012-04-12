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
	if (!(current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP) 
		|| 	current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)
		||	current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP))) {		
		wp_die(__("You are not allowed to send emails.", MAILUSERS_I18N_DOMAIN));
	} 
?>

<div class="wrap">

	<h2><?php _e('Send an email', MAILUSERS_I18N_DOMAIN); ?></h2>
	<br/>

	<?php if (current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP)
		|| 	current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)) { ?>
	<div style="float:left"><a href="admin.php?page=email-users/email_users_user_mail_form.php">
		<img src="<?php echo WP_CONTENT_URL . '/plugins/email-users/images/user.png'; ?>" alt="<?php _e('Send an email to one or more individual users', MAILUSERS_I18N_DOMAIN); ?>" title="<?php _e('Send an email to one or more individual users', MAILUSERS_I18N_DOMAIN); ?>" /></a>
	</div>
	<p><?php _e('Send an email to one or more individual users', MAILUSERS_I18N_DOMAIN); ?></p>
	<div class="clear"></div>
	<br/>
	<?php } ?>

	<?php if (current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP)) { ?>
	<div style="float:left"><a href="admin.php?page=email-users/email_users_group_mail_form.php">
		<img src="<?php echo WP_CONTENT_URL . '/plugins/email-users/images/group.png'; ?>" alt="<?php _e('Send an email to one or more user groups', MAILUSERS_I18N_DOMAIN); ?>" title="<?php _e('Send an email to one or more user groups', MAILUSERS_I18N_DOMAIN); ?>" /></a>
	</div>
	<p><?php _e('Send an email to one or more user groups', MAILUSERS_I18N_DOMAIN); ?></p>
	<div class="clear"></div>
	<?php } ?>
</div>
		
	
