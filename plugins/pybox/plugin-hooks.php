<?php

// texturize is too annoying once we have translations and code all mixed together
foreach ( array( 'comment_author', 'term_name', 'link_name', 'link_description', 'link_notes', 'bloginfo', 'wp_title', 'widget_title', 'the_title', 'the_content', 'the_excerpt', 'comment_text', 'list_cats' ) as $filter ) {
  remove_filter($filter, 'wptexturize');
}

function justemail_contactmethod( $contactmethods ) {
  unset($contactmethods['aim']);
  unset($contactmethods['jabber']);
  unset($contactmethods['yim']);
  return $contactmethods;
}
add_filter('user_contactmethods','justemail_contactmethod',10,1);


function enable_more_buttons($buttons) {
  $buttons[] = 'sub';
  $buttons[] = 'sup';
  return $buttons;
  }
add_filter("mce_buttons", "enable_more_buttons");

remove_action( 'wp_head', 'feed_links', 2 ); 
// Don't display the links to the general feeds: Post and Comment Feed
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

add_filter("robots_txt", "domo_arigato");

function domo_arigato($output) {
  $output .= 'Disallow: /wp-content/plugins/pybox/';
  return $output;
}


add_filter('login_headerurl','remove_wp_link');function remove_wp_link($var){return NULL;}
add_filter('login_headertitle','pb_title');
function pb_title($var){return __t('Computer Science Circles, powered by WordPress');}

add_action('init', 'pyBoxInit');
function pyBoxInit() {
 wp_enqueue_script("jquery"); 
 wp_enqueue_script("jquery-ui-core"); 
 wp_enqueue_script("jquery-ui-dialog"); 
 wp_enqueue_script("jquery-ui-draggable");
 wp_enqueue_script("jquery-ui-sortable"); 
 wp_enqueue_script("jquery-ui-resizable");
 wp_enqueue_script("jquery-ui-tabs");
 wp_enqueue_style( 'wp-jquery-ui-dialog' );
}



add_action('wp_head', 'pyBoxHead');
function pyBoxHead() {
  echo "<script type='text/javascript'>\n";

  // if language is english, define __t as doing nothing
  if (get_locale() == 'fr_FR' || get_locale() == 'de_DE') {
    echo sprintf("var translationArray = %s;", jsonTranslationArray());
  }
  else {
    echo "var translationArray = null;";
  }
  echo sprintf("var SUBMITURL = '%s';\n", USUBMIT);
  echo sprintf("var SETCOMPLETEDURL = '%s';\n", USETCOMPLETED);
  echo sprintf("var CONSOLEURL = '%s';\n", cscurl('console'));
  echo sprintf("var FILESURL = '%s';\n", UFILES);
  echo sprintf("var HISTORYURL = '%s';\n", UHISTORY);
  echo sprintf("var VISUALIZEURL = '%s';\n", cscurl('visualize'));
  echo sprintf("var MESSAGEURL = '%s';\n", UMESSAGE);
  echo sprintf("var MAILURL = '%s';\n", cscurl('mail'));
  echo sprintf("var DEFAULTTIMEOUTMS = '%s';\n", (WALLFACTOR*1 + WALLBUFFER)*1000);

  echo "</script>\n";

  echo '<link type="text/css" rel="stylesheet" href="'.UPYBOXCSS.'" />' . "\n";
  echo '<script type="text/javascript" src="'.UPYBOXJS.'"></script>'."\n";

  echo "<link rel='stylesheet' type='text/css' href='".UPYBOX."customizations-codemirror/codemirrorwp.css'>\n";
  echo "<link rel='stylesheet' type='text/css' href='".UPYBOX."customizations-codemirror/textmate.css'>\n";
  echo "<script type='text/javascript' src='".UCODEMIRROR2."lib/codemirror.js'></script>\n";
  echo "<script type='text/javascript' src='".UCODEMIRROR2."mode/python/python.js'></script>\n";

  echo "<script type='text/javascript' src='".UFLEXIGRID."js/flexigrid.js'></script>\n";
  echo '<link type="text/css" rel="stylesheet" href="'.UFLEXIGRID.'css/flexigrid.css" />' . "\n";

  if (stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE')!==FALSE) 
    echo '<style type="text/css"> li.pyscrambleLI {height: 26px;} </style>'."\n";

  if (stripos($_SERVER["HTTP_USER_AGENT"], 'FIREFOX')!==FALSE) 
    echo '<style type="text/css"> .pybox textarea {white-space: nowrap;} </style>'."\n";

  global $pyRenderCount;
  $pyRenderCount= 0;
  global $popupBoxen;
  $popupBoxen = "";
}

add_action('wp_head', 'faviconlinks');
add_action('login_head', 'faviconlinks');
add_action('admin_head', 'faviconlinks');
function faviconlinks() {
  echo '<link rel="shortcut icon" href="'.UFAVICON.'"/>';
}

add_action('admin_head', 'tinymce_wsfix');
function tinymce_wsfix() {
  echo "<script type='text/javascript' src='".UPYBOX."customizations-wordpress-rich-editor/editor-ws.js'></script>\n";
}

add_filter( 'previous_post_rel_link', 'disable_stuff' );
add_filter( 'next_post_rel_link', 'disable_stuff' );
function disable_stuff( $data ) {
  return false;
}


add_action('wp_footer', 'footsy');
function footsy() { 
  global $popupBoxen;
  echo $popupBoxen;

  if (class_exists('Polylang_Base')) {
    echo '<span id="pylangswitcher">';
    //  echo '<li><a id="notice-trans" href="#">notice! (08-30)</a></li>';
    
    foreach (array('en', 'fr') as $lang) {
      if ($lang != pll_current_language()) 
        echo '<li><a href="'.get_permalink(pll_get_post(get_the_ID(), $lang)).'">'.$lang.'</a></li>';
    }
    
    if (userIsAdmin() || 
        userIsTranslator() || userIsAssistant())
      foreach (array('de', 'nl') as $lang) {
        if ($lang != pll_current_language()) {
          echo '<li><a href="'.get_permalink(pll_get_post(get_the_ID(), $lang)).'">'.$lang.'</a></li>';
        }
      }
    // old method:  echo pll_the_languages(array('echo'=>0,'display_names_as' => 'slug','hide_current' => 1));
    if (userIsAdmin() || userIsTranslator() || userIsAssistant())
      echo '<li><a href="'.admin_url('edit.php?post_type=page').'">'.__t('Editor').'</a></li>';
    echo '</span>';
  }
}

// adapted from http://www.ilovecolors.com.ar/tinymce-plugin-wordpress/
function pb_mce_buttons($buttons) {
  array_push($buttons, 
	     "pbbPre", "pbbCode", "pbbAQuo", "|", 
	     "pbbBox", "pbbShort", "pbbMulti", "pbbMultiScramble", "|", 
	     "pbbHint", "pbbWarn", "pbbLink", "|", 
	     "pbbPlain"); 
  return $buttons;
}
function pb_mce_external_plugins($plugin_array) {
  $plugin_array['pybuttons']  =  plugins_url('/pybox/customizations-wordpress-rich-editor/pybuttons.js');
  return $plugin_array;
}

add_filter("mce_external_plugins", "pb_mce_external_plugins");
add_filter('mce_buttons_3', 'pb_mce_buttons');

add_filter('show_admin_bar', '__return_true');
function fixwpautop($text) {
  // also makes somewhat more html5 compliant
	$text = preg_replace('|<p>\s*<div(.*)</div>\s*</p>|s', "<div$1</div>", $text);
	$text = preg_replace('|</div>\s*</p>|s', "</div>", $text);
	$text = preg_replace('|</pre>\s*<p>|s', "</pre>", $text);
	$text = preg_replace('|</pre>\s*<br( )(/)>|s', "</pre>", $text);
	$text = preg_replace('|<br( )(/)>\s*<pre>|s', "<pre>", $text);
        $text = preg_replace('|<br( )(/)>\s*<div|s', "<div", $text);
	$text = preg_replace('|tt>|', "code>", $text);

	return $text;
}

/*function preprocesscallback1($m) {
  return str_replace("\n", "<br/>", $m[0]);
}
function preprocesscallback($m) {
  $res = $m[0];
  $res = preg_replace_callback("|<pre>.*</pre>|s", "preprocesscallback1", $res);
  $res = str_replace(array("\n", "\r"), "", $res);
  return $res;
}
function preprocess($text) {
  // delete all newlines within shortcodes as they don't play well with wpautop
  $f = get_shortcode_regex();
  $f = '@'.$f.'@s';
  $text = preg_replace_callback($f, "preprocesscallback", $text);
  return $text;
  }*/
function postprocesscallback($m) {
  //pyboxlog("called pyraw callback on ".$m);
  //pyboxlog($m[1]);  pyboxlog($m[0]); pyboxlog($m[2]);
  //pyboxlog(softSafeDereference($m[1]));
  return softSafeDereference($m[1]);
}
function postprocess($text) {
  $text = preg_replace_callback("|{pyRaw ([^}]*)}|", "postprocesscallback", $text);
  return $text;
}

// this is needed to support complex nested shortcodes which may 
// sometimes include <pre> inside of shortcode arguments

// default: wpautop (10), shortcode_unautop (10), do_shortcode (11)

// us: 
// kill all literal newlines in shortcodes (except in <pre>)
//add_filter ('the_content',  'preprocess', 1);
// do wpautop, which will not affect interior of shortcodes
//remove_filter ('the_content',  'wpautop'); // by default, 10
//add_filter ('the_content',  'wpautop', 6); // now, 6
//remove_filter('the_content', 'do_shortcode', 11); // by default, 11
//add_filter('the_content', 'do_shortcode', 9); // now, 9
// shortcode_unautop is 10
add_filter ('the_content',  'fixwpautop', 20);
// now allow raw material we don't want touched
add_filter ('the_content',  'postprocess', 100);

add_shortcode('msie', 'msie');
function msie($options, $content) {
  $v = 0;
  if (stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE 6')!==FALSE) $v = 6;
  if (stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE 7')!==FALSE) $v = 7;
  if ($v > 0) {
    if (stripos($_SERVER["HTTP_USER_AGENT"], 'Trident')!==FALSE) {
      return 'It looks like you are running Internet Explorer in Compatibility View, please turn it off!!';
    }
    else {
      return "It looks like you are running Internet Explorer $v, please upgrade!!";
    }
  }
  if (stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE')!==FALSE) {
    return "It looks like you are running a modern version of Internet Explorer, not in compatibility view.";
  }
  return "It looks like you are running a browser other than Internet Explorer.";
}

add_action('admin_bar_menu', 'pb_menu_items', 5);
function pb_menu_items($wp_admin_bar) {
  $wp_admin_bar->add_menu( array( 'parent' => 'user-actions', 'href' => cscurl('progress'), 'title'=>__t('My Progress'), 'id'=>'up'));
  $wp_admin_bar->add_menu( array( 'parent' => 'user-actions', 'href' => cscurl('mail'), 'title'=>__t('Mail'), 'id'=>'uppity'));
  $wp_admin_bar->add_menu( array( 'id'=>'snappy', 'parent' => 'user-actions', 'title' => __t('Console (new window)'), 'href' => cscurl('console'), "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'crackle', 'parent' => 'user-actions', 'title' => __t('Resources (new window)'), 'href' => cscurl('resources'), "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'pop', 'parent' => 'user-actions', 'title' => __t('Contact Us (new window)'), 'href' => cscurl('contact'), "meta" => array("target" => "_blank")));

  if (!is_admin())
    $wp_admin_bar->add_menu( array( 'parent' => 'top-secondary', 'id' => 'totop', 
				    'title' => '<img onclick="scrollToTop()" title="'.__t('scroll to top').'"'.
				    ' class="icon" src="'.UFILES . 'up.png"/>' ));
  
  global $wpdb;

  $mailtable = $wpdb->prefix."pb_mail";
  // check first, since upon initial installation it might be a problem due to being in header
  if ($wpdb->get_var("SHOW TABLES LIKE '$mailtable'") == $mailtable) { 
    $students = getStudents();
    if (count($students)>0 || userIsAdmin() || userIsAssistant()) {
      if (userIsAdmin())
        $where = "(uto = ".getUserID() . " OR uto = 0)";
      else {
        $where = "(uto = " .getUserID() . ")";//"AND ustudent IN (".implode(',', $students)."))";
      }
      $where = $where . "AND unanswered = 1";
      $count = $wpdb->get_var("SELECT COUNT(1) FROM ".$wpdb->prefix."pb_mail WHERE $where");
      if ($count > 0) {
        $msg = $wpdb->get_row("SELECT ustudent, problem, ID FROM ".$wpdb->prefix."pb_mail 
                             WHERE $where ORDER BY ID ASC LIMIT 1", ARRAY_A);
        
        $url = cscurl('mail') . "?who=".$msg['ustudent']."&what=".$msg['problem']."&which=".$msg['ID'].'#m';
        
        $wp_admin_bar->add_menu( array( 'parent' => 'top-secondary', 'id' => 'mail', 'href' => $url,
                                        'title' => '<img title="'.__t('goto oldest unanswered mail').'"'.
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
    if ($_SERVER['SERVER_NAME'] = 'cscircles.cemc.uwaterloo.ca') {
      $more_links['MySQL Frontend'] = "/~atkong/pma/"; 
      $more_links['[rebuild /export directory]'] = '/nav/?export=Y';
    }
    if ($ap != null) {
      $more_links['Daily submit-code usage'] = get_permalink($ap).'/profiling/?frequency=10&activity=submit-code';
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

add_action( 'init', 'setup_translation' );

function setup_translation() {
  load_plugin_textdomain('cscircles', FALSE, dirname( plugin_basename( __FILE__ ) ));
}

add_action( 'wp_before_admin_bar_render', 'tweak_polylang_menu' );

function tweak_polylang_menu() {
  global $wp_admin_bar;
  if (class_exists('Polylang_Base') && is_admin()) {
    if (!(userIsTranslator() || userIsAdmin() || userIsAssistant()))
      $wp_admin_bar->remove_node('languages');
    else {
      $node = $wp_admin_bar->get_node('languages');
      $node->title = __t('Filter Listed Pages'); // 'Languages' is confusing
      $wp_admin_bar->add_node($node); // update   
      $node = $wp_admin_bar->get_node('all');
      $node->title = str_replace(__('Show all languages', 'polylang'), __t('Show all visible'), $node->title); // similar
      $wp_admin_bar->add_node($node); // update   
    }
  }
}

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
add_action( 'init', 'tweak_admin_bar_actions' );

// create this for non-logged-in users; change title/href for logged-in
function wp_admin_bar_sitename_tweak( $wp_admin_bar) {
  $wp_admin_bar->add_menu( array(
                                 'id'    => 'site-name',
                                 'title' => get_bloginfo('name'),
                                 'href' => cscurl('homepage')
                                 ) );
}

// call to action in top right
function wp_admin_bar_create_account_item_tweak( $wp_admin_bar ) {
  if ( ! get_current_user_id() ) // if not logged in 
    {
      $wp_admin_bar->add_menu( array( 
                                     'id' => 'new-or-login', 
                                     'parent' => 'top-secondary', 
                                     'title' => __t('<p>Create free account / login</p><p>to save your progress</p>'), 
                                     'href' => wp_login_url( $_SERVER['REQUEST_URI'] ), //
                                     'meta' => array( 'class' => 'new-or-login'))); 
    }
}

// bunch of small changes
function tweak_admin_bar() {
  $user_id      = get_current_user_id();
  $current_user = wp_get_current_user();
  $profile_url  = get_edit_profile_url( $user_id );
  
  if ( $user_id ) { // if logged in
    global $wp_admin_bar;

    $howdy  = sprintf( __t("%s's menu"), $current_user->display_name );

    $wp_admin_bar->remove_node('user-info');
    $wp_admin_bar->remove_node('dashboard');
    $wp_admin_bar->remove_node('appearance');

    // note! add_node can be used to update information
    $wp_admin_bar->add_node(array('id'=>'logout', 'href' => wp_logout_url( $_SERVER['REQUEST_URI'] )));
    $wp_admin_bar->add_node(array('id'=>'my-account', 'href' => null, 'title' => $howdy ));
    $wp_admin_bar->add_node(array('id'=>'edit-profile', 'href' => $profile_url . '?wp_http_referer=' . urlencode($_SERVER['REQUEST_URI'])));

    if ( is_admin() ) { // proper language redirect on admin pages
      $wp_admin_bar->add_node(array('id'=>'view-site', 'href' => cscurl('homepage')));
    }

    if ( !is_admin() ) { // search box
      $form = "<form id='adminbarsearch' action='".cscurl('search')."'>\n" 
        . '<input class="adminbar-input" name="q" id="adminbar-search" title="'.__t('click and type to search website').'" tabindex="10" type="text" value="" maxlength="150" />'
	. '<input name="sa" type="submit" class="adminbar-button" value="' . __('Search') . '"/>'
	. '<input type="hidden" name="cx" value="007230231723983473694:r0-95non7ri">'
        ."\n".'<input type="hidden" name="cof" value="FORID:9">'
        ."\n".'<input type="hidden" name="ie" value="UTF-8">'
        ."\n".'<input type="hidden" name="nojs" value="1">'
	. '</form>';

      $wp_admin_bar->add_node(array('id'=>'search', 'title'=>$form));
      
    }
    
  }  
}
add_action( 'wp_before_admin_bar_render', 'tweak_admin_bar' ); 

// help prevent sender warnings   
add_action('phpmailer_init', 'set_mail_sender');
function set_mail_sender(&$phpmailer) {
  $phpmailer->Sender = 'bounces@cscircles.cemc.uwaterloo.ca';
}

// Don't like the default header
add_action( 'login_enqueue_scripts', 'login_style' );
function login_style() {
  echo "<style type='text/css'>#login h1 a {display:none;}</style>";
}
function login_subtitle( $message ) {
  //this is a good time to print a new header
  echo "<h1>".__t("Computer Science Circles")."</h1>";
  echo "<h2 style='text-align:center;margin:0.5em'>".__t("Log In or Create Account")."</h2>";

  return $message; //no-op
}
add_filter( 'login_message', 'login_subtitle' );

// We found "register" was confusing for users
add_action( 'login_footer', 'change_login_text' );
function change_login_text() {
  $msg = __t('Create a new account');
  $htmltext = htmlspecialchars($msg, ENT_QUOTES);
  $attrtext = addslashes($msg);
  echo "<script type='text/javascript'>
jQuery('a').each(function(){if(this.href.indexOf('action=register')!=-1)this.innerHTML='$htmltext'})
jQuery('#registerform #wp-submit.button').each(function(){this.value = '$attrtext'});
</script>";
}

add_action('login_message', 'change_login_message');
function change_login_message($message) 
{
  $message = str_replace("Für dieser Seite registrieren", "Für diese Seite registrieren", $message);
  return $message;
}

// end of file