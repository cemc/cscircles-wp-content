<?php
/**
 
 STOP SENDING NOTIFICATION MAILS TO THE USERS 
 version 1.5.3
 fixed: Email automatic plugin update notification to admin option
 version 1.5.2
 added: Email automatic plugin update notification to admin option
 added: Email automatic theme update notification to admin option
 since 1.5.1
 updated: the core pluggable function wp_new_user_notification
 added: passing through the $deprecated and $notify
 fixed notice of $deprecated
 */

if (!defined('ABSPATH')) die();

$famne_options = FAMNE::get_option( 'famne_options' );

FAMNE::AddModule('pluggable',array(
    'name' => 'Pluggable',
    'version'=>'1.5.1'
));


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
function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {

    global $famne_options;

    if (!empty($famne_options['wp_new_user_notification_to_admin'])) 
    {
        fa_new_user_notification_to_admin($user_id,$deprecated,$notify);
    }
    if (!empty($famne_options['wp_new_user_notification_to_user'])) 
    {
        fa_new_user_notification_to_user($user_id,$deprecated,$notify);
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




if (empty($famne_options['auto_core_update_send_email']) && !function_exists('fa_dont_sent_auto_core_update_emails') ) :
    /**
     * Send email when WordPress automatic updated.
     *
    */
    //echo "auto_core_update_send_email off";exit;
    
    
    function fa_dont_sent_auto_core_update_emails( $send, $type, $core_update, $result ) {
        if ( ! empty( $type ) && $type == 'success' ) {
            return false;
        }
        return true;
    }
    add_filter( 'auto_core_update_send_email', 'fa_dont_sent_auto_core_update_emails', 10, 4 );
endif;



function fa_new_user_notification_to_admin ($user_id,$deprecated,$notify='')
{
    
    //Most parts of this function are copied form pluggable.php
    
    if ( $deprecated !== null ) {
        _deprecated_argument( __FUNCTION__, '4.3.1' );
    }

    global $wpdb, $wp_hasher;
    $user = get_userdata( $user_id );

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

    if ( 'user' !== $notify ) {
        $switched_locale = switch_to_locale( get_locale() );

        /* translators: %s: site title */
        $message = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
        /* translators: %s: user login */
        $message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
        /* translators: %s: user email address */
        $message .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n";

        $wp_new_user_notification_email_admin = array(
            'to'      => get_option( 'admin_email' ),
            /* translators: New user registration notification email subject. %s: Site title */
            'subject' => __( '[%s] New User Registration' ),
            'message' => $message,
            'headers' => '',
        );

        /**
         * Filters the contents of the new user notification email sent to the site admin.
         *
         * @since 4.9.0
         *
         * @param array   $wp_new_user_notification_email {
         *     Used to build wp_mail().
         *
         *     @type string $to      The intended recipient - site admin email address.
         *     @type string $subject The subject of the email.
         *     @type string $message The body of the email.
         *     @type string $headers The headers of the email.
         * }
         * @param WP_User $user     User object for new user.
         * @param string  $blogname The site title.
         */
        $wp_new_user_notification_email_admin = apply_filters( 'wp_new_user_notification_email_admin', $wp_new_user_notification_email_admin, $user, $blogname );

        @wp_mail(
            $wp_new_user_notification_email_admin['to'],
            wp_specialchars_decode( sprintf( $wp_new_user_notification_email_admin['subject'], $blogname ) ),
            $wp_new_user_notification_email_admin['message'],
            $wp_new_user_notification_email_admin['headers']
        );

        if ( $switched_locale ) {
            restore_previous_locale();
        }
    }
}


function fa_new_user_notification_to_user($user_id,$deprecated=null,$notify='')
{
    if ( $deprecated !== null ) {
        _deprecated_argument( __FUNCTION__, '4.3.1' );
    }

    // Accepts only 'user', 'admin' , 'both' or default '' as $notify
    if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), true ) ) {
        return;
    }

    global $wpdb, $wp_hasher;
    $user = get_userdata( $user_id );

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

    // `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
    if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
        return;
    }

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

    $switched_locale = switch_to_locale( get_user_locale( $user ) );

    /* translators: %s: user login */
    $message  = sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
    $message .= __( 'To set your password, visit the following address:' ) . "\r\n\r\n";
    $message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . ">\r\n\r\n";

    $message .= wp_login_url() . "\r\n";

    $wp_new_user_notification_email = array(
        'to'      => $user->user_email,
        /* translators: Login details notification email subject. %s: Site title */
        'subject' => __( '[%s] Login Details' ),
        'message' => $message,
        'headers' => '',
    );

    /**
     * Filters the contents of the new user notification email sent to the new user.
     *
     * @since 4.9.0
     *
     * @param array   $wp_new_user_notification_email {
     *     Used to build wp_mail().
     *
     *     @type string $to      The intended recipient - New user email address.
     *     @type string $subject The subject of the email.
     *     @type string $message The body of the email.
     *     @type string $headers The headers of the email.
     * }
     * @param WP_User $user     User object for new user.
     * @param string  $blogname The site title.
     */
    $wp_new_user_notification_email = apply_filters( 'wp_new_user_notification_email', $wp_new_user_notification_email, $user, $blogname );

    wp_mail(
        $wp_new_user_notification_email['to'],
        wp_specialchars_decode( sprintf( $wp_new_user_notification_email['subject'], $blogname ) ),
        $wp_new_user_notification_email['message'],
        $wp_new_user_notification_email['headers']
    );

    if ( $switched_locale ) {
        restore_previous_locale();
    }
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



if (empty($famne_options['auto_plugin_update_send_email']) ) :
    /**
     * Email automatic plugin update notification to admin.
     *
    */
    //echo "auto_plugin_update_send_email off";
    function fa_auto_plugin_update_send_email($notifications_enabled,$update_results_plugins)
    {
        $notifications_enabled = false;
        foreach ( $update_results_plugins as $update_result ) {
            // do we have a failed update?
            if ( true !== $update_result->result ) $notifications_enabled = true;
        }
        return $notifications_enabled;
    }

    add_filter( 'auto_plugin_update_send_email', 'fa_auto_plugin_update_send_email',10,2 );
endif;


if (empty($famne_options['auto_theme_update_send_email']) ) :
    /**
     * Email automatic theme update notification to admin.
     *
    */
    //echo "auto_theme_update_send_email off";
    function fa_auto_theme_update_send_email($notifications_enabled,$update_results_theme)
    {
        $notifications_enabled = false;

        foreach ( $update_results_theme as $update_result ) {
            // do we have a failed update?
            if ( true !== $update_result->result ) $notifications_enabled = true;
        }
        return $notifications_enabled;
    }

    add_filter( 'auto_theme_update_send_email', 'fa_auto_theme_update_send_email',10,2 );
endif;