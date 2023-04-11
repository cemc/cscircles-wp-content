<?php
/**
 
 STOP SENDING NOTIFICATION MAILS TO THE USERS 
 version: 1.2.0
 */

if (!defined('ABSPATH')) die();

$famne_options = get_option( 'famne_options' );

if (!function_exists('dont_send_password_change_email') ) :
/**
 * Email password change notification to registered user.
 *
*/
//echo "dont_send_password_change_email";
function dont_send_password_change_email( $send=false, $user='', $userdata='')
{
    
    global $famne_options;
    
    if (is_array($user)) $user = (object) $user;

    if (!empty($famne_options['wp_password_change_notification']) ) :

        // send a copy of password change notification to the admin
        // but check to see if it's the admin whose password we're changing, and skip this
        if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
            $message = sprintf(__('Password Lost and Changed for user: %s'), $user->user_login) . "\r\n";
            // The blogname option is escaped with esc_html on the way into the database in sanitize_option
            // we want to reverse this for the plain text arena of emails.
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            wp_mail(get_option('admin_email'), sprintf(__('[%s] Password Lost/Changed'), $blogname), $message);
        }    
    
    endif;
    
    if (empty($famne_options['send_password_change_email']) ) :   
        return false;
    else :
        return true;
    endif;
}
add_filter('send_password_change_email', 'dont_send_password_change_email',1,3);
endif;


if (empty($famne_options['send_email_change_email']) && !function_exists('dont_send_email_change_email') ) :
/**
 * Email users e-mail change notification to registered user.
 *
*/
//echo "dont_send_email_change_email off";
function dont_send_email_change_email( $send=false, $user='', $userdata='')
{
    return false;
}
add_filter('send_email_change_email', 'dont_send_email_change_email',1,3);
endif;

    

if (!function_exists('wp_new_user_notification') ) :
/**
 * Email login credentials to a newly-registered user.
 *
 * A new user registration notification is also sent to admin email.
*/
//echo "wp_new_user_notification off";
function wp_new_user_notification( $user_id, $notify = '' ) {

global $famne_options;

    if (!empty($famne_options['wp_new_user_notification_to_admin'])) 
    {
        fa_new_user_notification_to_admin($user_id);
    }
        
    if (!empty($famne_options['wp_new_user_notification_to_user'])) 
    {
        fa_new_user_notification_to_user($user_id);
    }
}
endif;

if (empty($famne_options['wp_notify_postauthor']) && !function_exists('wp_notify_postauthor') ) :
/**
 * Notify an author (and/or others) of a comment/trackback/pingback on a post.
*/
//echo "wp_notify_postauthor off";
function wp_notify_postauthor( $comment_id, $deprecated = null ) {}
endif;

if (empty($famne_options['wp_notify_moderator']) && !function_exists('wp_notify_moderator') ) :
/**
 * Notifies the moderator of the blog about a new comment that is awaiting approval.
*/
//echo "wp_notify_moderator off";
function wp_notify_moderator($comment_id) {}
endif;




if (empty($famne_options['wp_password_change_notification']) && !function_exists('wp_password_change_notification') ) :
/**
 * Notify the blog admin of a user changing password, normally via email.
 */
function wp_password_change_notification($user) {}


endif;



if ((empty($famne_options['send_password_forgotten_email']) || empty($famne_options['send_password_admin_forgotten_email'])) && !function_exists('dont_send_password_forgotten_email') ) :
/**
 * Email forgotten password notification to registered user.
 *
*/
//echo "dont_send_password_forgotten_email off";exit;
function dont_send_password_forgotten_email( $send=true, $user_id=0 )
{
    global $famne_options;
    
    $is_administrator = fa_user_is_administrator($user_id);
    
    if ($is_administrator && empty($famne_options['send_password_admin_forgotten_email']))
    {
        // stop sending admin forgot email     
		return false;
    }
    if (!$is_administrator && empty($famne_options['send_password_forgotten_email']))
    {
        // stop sending user forgot email 
		return false;
    }
    // none of the above so give the default status back
    return $send;
}
add_filter('allow_password_reset', 'dont_send_password_forgotten_email',1,3);
endif;






function fa_new_user_notification_to_admin ($user_id)
{
    
    //Most parts of this function are copied form pluggable.php
    
	global $wpdb;
	$user = get_userdata( $user_id );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";


    @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);
}


function fa_new_user_notification_to_user($user_id)
{
	global $wpdb;
	$user = get_userdata( $user_id );

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);


	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	/** This action is documented in wp-login.php */
	do_action( 'retrieve_password_key', $user->user_login, $key );

	// Now insert the key, hashed, into the DB.
	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	$message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
	$message .= __('To set your password, visit the following address:') . "\r\n\r\n";
	$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

	$message .= wp_login_url() . "\r\n";

	wp_mail($user->user_email, sprintf(__('[%s] Your username and password info'), $blogname), $message);
}

function fa_user_is_administrator($user_id=0)
{
    $user = new WP_User( intval($user_id) );
    $is_administrator = false;
    if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
        foreach ( $user->roles as $role )
            if ( strtolower($role) == 'administrator') $is_administrator = true;
    }
    return $is_administrator;
}
