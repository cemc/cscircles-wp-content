<?php
define( 'HEADER_IMAGE_WIDTH', apply_filters( 'twentyeleven_header_image_width', 1000 ) );
define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'twentyeleven_header_image_height', 150 ) );
define( 'HEADER_IMAGE', content_url('/themes/pybox2011childTheme/images/header.jpg') );

function pybox_on() {
  return function_exists('pyboxlog');
}

function _cscurl($slug) {
  return pybox_on() ? cscurl($slug) : $slug;
}

function ___t($text) {
  return pybox_on() ? __t($text) : $text;
}

// don't show avatars
add_filter('pre_option_show_avatars', 'do_not_show_avatars');
function do_not_show_avatars() { return 0; }

// transparent latex backgrounds
add_filter('option_wp_latex', 'transparent_latex_bg');
function transparent_latex_bg($opts) {
  $opts['bg'] = 'transparent';
  return $opts; 
}

// removes the profile.php admin color scheme options
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

// remove unnecessary parts of profile page, add "return" button
add_action( 'admin_head-profile.php', 'do_tweak_profile_page');
function do_tweak_profile_page() {ob_start('tweak_profile_page');}
function tweak_profile_page( $subject ) {
  // add return link, and hide unnecessary profile bits
    $subject = preg_replace ( '#id="profile-page">#', 'id="profile-page">
<style>h3, .show-admin-bar, .user-display-name-wrap, .user-url-wrap, .user-description-wrap  {display: none} </style>' . (pybox_on() ? returnfromprofile() : ''), $subject);
  
  return $subject;
}

add_action( 'admin_head-index.php', 'do_tweak_dashboard_page');
function do_tweak_dashboard_page() {ob_start('tweak_dashboard_page');}
function tweak_dashboard_page( $subject ) {
  return preg_replace ( '/<div class="wrap">/', (pybox_on() ? returnfromprofile() : '') . '<div class="wrap">', $subject);
}

function my_function_admin_bar(){ return true; }
add_filter( 'show_admin_bar' , 'my_function_admin_bar');


add_filter( 'twentyeleven_default_theme_options' , 'set_layout');
function set_layout($options) {
  $options['theme_layout'] = 'content';
  return $options;
}


add_action('get_header', 'my_filter_head');

function my_filter_head() {
  remove_action('wp_head', '_admin_bar_bump_cb');
}

/*************************************************/
// stuff that used to be in a plugin, but now is in the theme
/*************************************************/
add_action('admin_head', 'pybox_admin_css');
function pybox_admin_css() {
  echo '<style type="text/css">
.wp-admin .button-primary{
/*        margin-top: 10px;
        font-size: 14px !important;
        font-weight: bold;
        padding: 7px;
        height: auto;*/
}

.wp-admin #wpadminbar #wp-toolbar #wp-admin-bar-user-actions .ab-item
{padding: 0px;}

#wp-admin-bar-my-account div.ab-sub-wrapper {
    padding-top: 10px !important;
    padding-bottom: 10px !important;
    margin-left: 0px !important;
}
</style>
';
}

add_action('login_head', 'pybox_login_css');
function pybox_login_css() {
  echo '<style type="text/css">
#login h1, #login h2 {text-align: center; margin-bottom: 10px;}
</style>';
}


/*******************************/
// remove some things that are TMI
add_action('wp_head', 'faviconlinks');
add_action('login_head', 'faviconlinks');
add_action('admin_head', 'faviconlinks');
function faviconlinks() {
  echo '<link rel="shortcut icon" href="'.(pybox_on() ? UFAVICON : '#').'"/>';
}

// don't show/generate links to feeds
remove_action( 'wp_head', 'feed_links', 2 ); 
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

add_filter( 'previous_post_rel_link', '__return_false' );
add_filter( 'next_post_rel_link', '__return_false' );

function justemail_contactmethod( $contactmethods ) {
  unset($contactmethods['aim']);
  unset($contactmethods['jabber']);
  unset($contactmethods['yim']);
  return $contactmethods;
}
add_filter('user_contactmethods','justemail_contactmethod',10,1);

/*************************************/
// changes to the login page

// Don't like the default header
add_action( 'login_enqueue_scripts', 'login_style' );
function login_style() {
  echo "<style type='text/css'>#login h1 a {display:none;}</style>";
}
function login_subtitle( $message ) {
  //this is a good time to print a new header
  echo "<h1>".___t("Computer Science Circles")."</h1>";
  echo "<h2 style='text-align:center;margin:0.5em'>".___t("Log In or Create Account")."</h2>";

  return $message; //no-op
}
add_filter( 'login_message', 'login_subtitle' );

// We found "register" was confusing for users
add_action( 'login_footer', 'change_login_text' );
function change_login_text() {
  $msg = ___t('Create a new account');
  $htmltext = htmlspecialchars($msg, ENT_QUOTES);
  $attrtext = addslashes($msg);
  echo "<script type='text/javascript'>
jQuery('a').each(function(){if(this.href.indexOf('action=register')!=-1)this.innerHTML='$htmltext'})
jQuery('#registerform #wp-submit.button').each(function(){this.value = '$attrtext'});
</script>";
}

// fix a faulty wordpress translation
add_action('login_message', 'change_login_message');
function change_login_message($message) 
{
  $message = str_replace("Für dieser Seite registrieren", "Für diese Seite registrieren", $message);
  return $message;
}



/*************************************/
// significant changes in functionality to the admin menu

// always show the admin bar
add_filter('show_admin_bar', '__return_true');

add_action('admin_bar_menu', 'pb_menu_items', 5);
function pb_menu_items($wp_admin_bar) {
  $wp_admin_bar->add_menu( array( 'parent' => 'user-actions', 'href' => _cscurl('progress'), 'title'=>___t('My Progress'), 'id'=>'up'));
  if (!get_option('cscircles_hide_help'))
    $wp_admin_bar->add_menu( array( 'parent' => 'user-actions', 'href' => _cscurl('mail'), 'title'=>___t('Mail'), 'id'=>'uppity'));
  $wp_admin_bar->add_menu( array( 'id'=>'snappy', 'parent' => 'user-actions', 'title' => ___t('Console (new window)'), 'href' => _cscurl('console'), "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'snappie', 'parent' => 'user-actions', 'title' => ___t('Visualizer (new window)'), 'href' => _cscurl('visualize'), "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'crackle', 'parent' => 'user-actions', 'title' => ___t('Resources (new window)'), 'href' => _cscurl('resources'), "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'pop', 'parent' => 'user-actions', 'title' => ___t('Contact Us (new window)'), 'href' => _cscurl('contact'), "meta" => array("target" => "_blank")));

  if (!is_admin())
    $wp_admin_bar->add_menu( array( 'parent' => 'top-secondary', 'id' => 'totop', 
				    'title' => '<img onclick="scrollToTop()" title="'.___t('scroll to top').'"'.
				    ' class="icon" src="'.UFILES . 'up.png"/>' ));
  
  global $wpdb;

  $mailtable = $wpdb->prefix."pb_mail";
  // check first, since upon initial installation it might be a problem due to being in header
  if ($wpdb->get_var("SHOW TABLES LIKE '$mailtable'") == $mailtable) { 
    $students = pybox_on() ? getStudents() : array();
    if (pybox_on() && (count($students)>0 || userIsAdmin() || userIsAssistant())) {
      if (userIsAdmin())
        $where = "(uto = ".getUserID() . " OR uto = 0)";
      else {
        $where = "(uto = " .getUserID() . ")";//"AND ustudent IN (".implode(',', $students)."))";
      }
      $where = $where . "AND unanswered = 1";
      $count = $wpdb->get_var("SELECT COUNT(1) FROM ".$wpdb->prefix."pb_mail WHERE $where");
      if ($count > 0) {
        echo "<!--" . (user_can(11351, 'level_10') ? 'y':'n') . " SELECT ustudent, problem, ID FROM ".$wpdb->prefix."pb_mail WHERE $where ORDER BY ID ASC LIMIT 1 -->";
        $msg = $wpdb->get_row("SELECT ustudent, problem, ID FROM ".$wpdb->prefix."pb_mail 
                             WHERE $where ORDER BY ID ASC LIMIT 1", ARRAY_A);
        
        $url = _cscurl('mail') . "?who=".$msg['ustudent']."&what=".$msg['problem']."&which=".$msg['ID'].'#m';
        
        $wp_admin_bar->add_menu( array( 'parent' => 'top-secondary', 'id' => 'mail', 'href' => $url,
                                        'title' => '<img title="'.___t('goto oldest unanswered mail').'"'.
                                        'class="icon" src="'.UFILES . "mail-icon.png\"/>($count)" ));
      }
    }
  }
  
  if (current_user_can('level_10')) {	  

    $wp_admin_bar->add_node( array(
				   'id'        => 'admin-menu',
				   'parent'    => 'top-secondary',
				   'title'     => 'su',
				   'meta'      => array(
							'class'     => '',
							'title'     => 'Admin Menu',
							),
				   ) );

    $ap = get_page_by_title('Admin Pages');

    $more_links = array(
			'Wordpress Dashboard' => get_bloginfo('wpurl') .'/wp-admin/index.php',
			'Edit THIS Page' => get_edit_post_link());
    if ($_SERVER['SERVER_NAME'] == 'cscircles.cemc.uwaterloo.ca') {
      $more_links['MySQL Frontend'] = "/~atkong/pma/"; 
      $more_links['[rebuild /export directory]'] = '/nav/?export=Y';
    }
    if ($ap != null) {
      //      $more_links['Daily submit-code usage'] = get_permalink($ap).'/profiling/?frequency=10&activity=submit-code';
      $more_links['CS Circles Options'] = admin_url('admin.php?page=cscircles-options');
      $more_links['Rebuild Databases'] = admin_url('admin.php?page=cscircles-makedb');
      $more_links['[listing of admin-manual follows]'] = get_permalink($ap);
      
      $pages = get_pages( array('child_of' => $ap->ID, 'post_status'=>'publish,private'));
      foreach ($pages as $page) 
        $more_links[$page->post_title] = get_permalink($page);

    }

    $i = 0;
    foreach ($more_links as $title => $link) {
      $wp_admin_bar->add_node(array('parent'=>'admin-menu', 'id'=>"morelinks" . $i++, 'href' => $link, 'title' => $title));
    }
  }      
}

add_action( 'wp_before_admin_bar_render', 'tweak_polylang_menu' );
function tweak_polylang_menu() {
  global $wp_admin_bar;
  if (class_exists('PLL_Base') && is_admin()) {
    if (pybox_on() && !(userIsTranslator() || userIsAdmin() || userIsAssistant())) 
      $wp_admin_bar->remove_node('languages');
    else {
      $node = $wp_admin_bar->get_node('languages');
      $node->title = ___t('Filter Listed Pages'); // 'Languages' is confusing
      $wp_admin_bar->add_node($node); // update   
      /*      $node = $wp_admin_bar->get_node('all'); doesn't exist any more?
      $node->title = str_replace(__('Show all languages', 'polylang'), ___t('Show all visible'), $node->title); // similar
      $wp_admin_bar->add_node($node); // update   */
    }
  }
}

add_action( 'init', 'tweak_admin_bar_actions' );
function tweak_admin_bar_actions() {
  // remove completely
  remove_action( 'admin_bar_menu', 'wp_admin_bar_wp_menu', 10 );
  remove_action( 'admin_bar_menu', 'wp_admin_bar_my_sites_menu', 20 );
  remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
  remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
  remove_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );

  // change ordering
  remove_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 0 ); 
  remove_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 4 ); 
  remove_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 7 ); 

  add_action( 'admin_bar_menu', 'wp_admin_bar_create_account_item_tweak', 0 ); // new 
  add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 0 ); 
  add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 4 ); 
  add_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 7 ); 
  add_action( 'admin_bar_menu', 'wp_admin_bar_sitename_tweak', 31 ); // new
}

// create this for non-logged-in users; change title/href for logged-in
function wp_admin_bar_sitename_tweak( $wp_admin_bar) {
  $wp_admin_bar->add_menu( array(
                                 'id'    => 'site-name',
                                 'title' => get_bloginfo('name'),
                                 'href' => _cscurl('homepage')
                                 ) );
}

// call to action in top right
function wp_admin_bar_create_account_item_tweak( $wp_admin_bar ) {
  if ( ! get_current_user_id() ) // if not logged in 
    {
      $wp_admin_bar->add_menu( array( 
                                     'id' => 'new-or-login', 
                                     'parent' => 'top-secondary', 
                                     'title' => ___t('<p>Create free account / login</p><p>to save your progress</p>'), 
                                     'href' => wp_login_url( $_SERVER['REQUEST_URI'] ), //
                                     'meta' => array( 'class' => 'new-or-login'))); 
    }
}

// bunch of small changes
add_action( 'wp_before_admin_bar_render', 'tweak_admin_bar' ); 
function tweak_admin_bar() {
  $user_id      = get_current_user_id();
  $current_user = wp_get_current_user();
  $profile_url  = get_edit_profile_url( $user_id );
  
  global $wp_admin_bar;
  
  $wp_admin_bar->remove_node('wp-logo');
  
  if ( $user_id ) { // if logged in

    $howdy  = sprintf( ___t("%s's menu"), $current_user->display_name );

    $wp_admin_bar->remove_node('user-info');
    $wp_admin_bar->remove_node('dashboard');
    $wp_admin_bar->remove_node('appearance');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->remove_node('edit');

    // note! add_node can be used to update information
    $wp_admin_bar->add_node(array('id'=>'logout', 'href' => wp_logout_url( $_SERVER['REQUEST_URI'] )));
    $wp_admin_bar->add_node(array('id'=>'my-account', 'href' => null, 'title' => $howdy ));
    $wp_admin_bar->add_node(array('id'=>'edit-profile', 'href' => $profile_url . '?wp_http_referer=' . urlencode($_SERVER['REQUEST_URI'])));

    if ( is_admin() ) { // proper language redirect on admin pages
      $wp_admin_bar->add_node(array('id'=>'view-site', 'href' => _cscurl('homepage')));
    }

  }  
}



// end of file
