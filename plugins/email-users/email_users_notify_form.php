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
	if (!current_user_can(MAILUSERS_NOTIFY_USERS_CAP)) {
		wp_die(__("You are not allowed to notify users about posts and pages.", MAILUSERS_I18N_DOMAIN));
	} 
	
	if ( !isset($_GET['post_id']) && !isset($err_msg) ) {
		$err_msg .= 
			__('Trying to notify of a post without passing the post id !', 
				MAILUSERS_I18N_DOMAIN);
	}
		
	if (!isset($send_roles)) {
		$send_roles = array();
	}	
	if (!isset($send_users)) {
		$send_users = array();
	}

	$mail_format = mailusers_get_default_mail_format()=='html' ? 
						__('HTML', MAILUSERS_I18N_DOMAIN) 
					:	__('Plain text', MAILUSERS_I18N_DOMAIN);
					
	$subject = mailusers_get_default_subject();
	$mail_content = mailusers_get_default_body();

	// Replace the template variables concerning the blog details
	// --
	$subject = mailusers_replace_blog_templates($subject);
	$mail_content = mailusers_replace_blog_templates($mail_content);
		
	// Replace the template variables concerning the sender details
	// --	
	get_currentuserinfo();
	$from_name = $user_identity;
	$from_address = $user_email;
	$subject = mailusers_replace_sender_templates($subject, $from_name);
	$mail_content = mailusers_replace_sender_templates($mail_content, $from_name);
		
	// Replace the template variables concerning the post details
	// --
	if ( isset($_GET['post_id']) ) {
		$post_id = $_GET['post_id'];
	}
	$post = get_post( $post_id );
	$post_title = $post->post_title;
	$post_url = get_permalink( $post_id );			
	$post_content = explode( '<!--more-->', $post->post_content, 2 );
	$post_excerpt = $post_content[0];

        if (mailusers_get_default_mail_format()=='html') {
		$post_excerpt = wpautop($post_excerpt);
        }
	
	$subject = mailusers_replace_post_templates($subject, $post_title, $post_excerpt, $post_url);
	$mail_content = mailusers_replace_post_templates($mail_content, $post_title, $post_excerpt, $post_url);
?>

<div class="wrap">
	<h2><?php _e('Notify users of a post or page', MAILUSERS_I18N_DOMAIN); ?></h2>
		
	<?php 	if (isset($err_msg) && $err_msg!='') { ?>
			<p class="error"><?php echo $err_msg; ?></p>
			<p><?php _e('Please correct the errors displayed above and try again.', MAILUSERS_I18N_DOMAIN); ?></p>
	<?php	} ?>
	
	<?php	
	if (!isset($post_id)) { ?>
	<form name="SetPost" action="admin.php?page=email-users/email_users_notify_form.php" method="post">
	    <p>Please select the post that you wish to notify users about.</p>
	    <select name="post_id">
		<?php
		 $lastposts = get_posts('numberposts=0');
		 foreach($lastposts as $post) :
		    setup_postdata($post);
		 ?>
		<option value="<?php the_ID(); ?>"><?php the_title(); ?></option>
		 <?php endforeach; ?>
	    </select>

	    <p class="submit">
		    <input type="submit" name="Submit" value="<?php _e('Select post', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
	    </p>
	</form>
	<?php } else { ?>
		
	<form name="SendEmail" action="admin.php?page=email-users/email_users_send_notify_mail.php" method="post">		
		<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
		<input type="hidden" name="mail_format" value="<?php echo mailusers_get_default_mail_format(); ?>" />
		<input type="hidden" name="fromName" value="<?php echo $from_name;?>" />
		<input type="hidden" name="fromAddress" value="<?php echo $from_address;?>" />
		<input type="hidden" name="subject" value="<?php echo format_to_edit($subject);?>" />
		
		<table class="form-table" width="100%" cellspacing="2" cellpadding="5">
		<tr>
			<th scope="row" valign="top"></th>
			<td><strong><?php _e('Mail will be sent as:', MAILUSERS_I18N_DOMAIN); ?> <?php echo $mail_format; ?></strong></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="fromName"><?php _e('Sender', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><?php echo $from_name;?> &lt;<?php echo $from_address;?>&gt;</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="send_roles"><?php _e('Recipients', MAILUSERS_I18N_DOMAIN); ?>
			<br/><br/>
			<small><?php _e('Use CTRL key to select/deselect multiple items', MAILUSERS_I18N_DOMAIN); ?></small>
			<br/><br/>
			<small><?php _e('The users that did not agree to recieve notifications do not appear here.', MAILUSERS_I18N_DOMAIN); ?></small></label></th>
			<td>
				<select name="send_roles[]" multiple="yes" size="8" style="width: 250px; height: 250px;">
				<?php 
					$roles = mailusers_get_roles($user_ID, MAILUSERS_ACCEPT_NOTIFICATION_USER_META);
					foreach ($roles as $key => $value) { 
				?>
					<option value="<?php echo $key; ?>"	<?php 
						echo (in_array($key, $send_roles) ? ' selected="yes"' : '');?>>
						<?php echo __('Role', MAILUSERS_I18N_DOMAIN) . ' - ' . $value; ?>
					</option>
				<?php 
					}
				?>
				</select> 
				<select name="send_users[]" multiple="yes" size="8" style="width: 400px; height: 250px;">
				<?php 
					//  Display of users is based on plugin setting
					$na = __('N/A', MAILUSERS_I18N_DOMAIN);
					$sortby = mailusers_get_default_sort_users_by();
	

					error_log('%s::%s:  %s', basename(__FILE__), __LINE__, $sortby);
					$users = mailusers_get_users($user_ID, MAILUSERS_ACCEPT_NOTIFICATION_USER_META);
					foreach ($users as $user) {
						switch ($sortby) {
							case 'fl' :  //  First Last
								$name = sprintf('%s %s',
									is_null($user->first_name) ? $na : $user->first_name,
									is_null($user->last_name) ? $na : $user->last_name);
								break;

							case 'flul' :  //  First Last User Login
								$name = sprintf('%s %s (%s)',
									is_null($user->first_name) ? $na : $user->first_name,
									is_null($user->last_name) ? $na : $user->last_name,
									$user->user_login);
								break;

							case 'lf' :
								$name = sprintf('%s, %s',
									is_null($user->last_name) ? $na : $user->last_name,
									is_null($user->first_name) ? $na : $user->first_name);
								break;

							case 'lful' :
								$name = sprintf('%s, %s (%s)',
									is_null($user->last_name) ? $na : $user->last_name,
									is_null($user->first_name) ? $na : $user->first_name,
									$user->user_login);
								break;

							case 'ul' :
								$name = sprintf('%s', $user->user_login);
								break;

							case 'uldn' :
								$name = sprintf('%s (%s)',
									$user->user_login, $user->display_name);
								break;

							case 'ulfl' :
								$name = sprintf('%s (%s %s)', $user->user_login,
									is_null($user->first_name) ? $na : $user->first_name,
									is_null($user->last_name) ? $na : $user->last_name);
								break;

							case 'ullf' :
								$name = sprintf('%s (%s, %s)', $user->user_login,
									is_null($user->last_name) ? $na : $user->last_name,
									is_null($user->first_name) ? $na : $user->first_name);
								break;

							case 'dnul' :
								$name = sprintf('%s (%s)',
									$user->display_name, $user->user_login);
								break;

							case 'dn' :
							case 'none' :
							default:
								$name = $user->display_name;
								break;
						}
				?>
					<option value="<?php echo $user->ID; ?>" <?php 
						echo (in_array($user->ID, $send_users) ? ' selected="yes"' : '');?>>
						<?php echo __('User', MAILUSERS_I18N_DOMAIN) . ' - ' . $name; ?>
					</option>
				<?php 
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="subject"><?php _e('Subject', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><?php echo mailusers_get_default_mail_format()=='html' ? $subject : '<pre>' . format_to_edit($subject) . '</pre>';?></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="mailContent"><?php _e('Message', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><?php echo mailusers_get_default_mail_format()=='html' ? $mail_content : '<pre>' . wordwrap(strip_tags($mail_content), 80, "\n") . '</pre>';?>
				<textarea rows="10" cols="80" name="mailContent" id="mailContent" style="width: 647px; display: none;" readonly="yes"><?php echo $mail_content;?></textarea>
			</td>
		</tr>
		</table>
		
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Send Email', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
		</p>	
	</form>	
	
	<?php } ?>
</div>
