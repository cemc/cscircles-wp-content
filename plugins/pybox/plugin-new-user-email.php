<?php
  // override a pluggable function.
  // based on http://www.epicalex.com/new-user-email-set-up/
 if ( !function_exists('wp_new_user_notification') ) {
  function wp_new_user_notification($user_id, $plaintext_pass = '') {
    //pyboxlog('called wp_n_u_n', 1);
    $user = new WP_User($user_id);

    $body = 
      __t('An account has been created for you on the Computer Science Circles website, with a randomly generated password.

User name: %username%
Current random password: %password%

Please visit
%loginurl%
and change your password to something you will remember. 
Press "%upprof%" once you are done.

Thanks from the Computer Science Circles team!
%siteurl%');
    //pyboxlog('locale:' . pll_current_language('locale'), 1);
    if (class_exists('Polylang_Base')) 
      update_user_meta( $user_id, 'user_lang', pll_current_language('locale') );
    $subject = __t('Computer Science Circles: New account');

    $user_login = stripslashes($user->user_login);
    $user_email = stripslashes($user->user_email);

    $find = array('/%username%/i', '/%password%/i', '/%blogname%/i', '/%siteurl%/i', '/%loginurl%/i', '/%useremail%/i', '/%upprof%/i');
    $replace = array($user_login, $plaintext_pass, get_option('blogname'), get_option('siteurl'), get_option('siteurl').'/wp-login.php', $user_email, __t('Update Profile'));

    $body = preg_replace($find, $replace, $body);
    $body = preg_replace("/%.*%/", "", $body);
    
    pb_mail('"'.get_option('blogname').'" <'.CSCIRCLES_BOUNCE_EMAIL.'>', '<'.$user_email.'>', $subject, $body);
  }
 }

