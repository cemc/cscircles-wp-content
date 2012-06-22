<?php
// see also hacks.txt for documentation of changes to core wordpress code

remove_filter('the_content', 'do_shortcode', 11);
add_filter('the_content', 'do_shortcode', 9);

add_filter('no_texturize_tags', 'pyboxNTT'); function pyboxNTT($arg) {$arg[] = 'textarea'; $arg[] = 'input'; return $arg;}
//add_filter('no_texturize_tags', 'input'); old buggy version

function justemail_contactmethod( $contactmethods ) {
  unset($contactmethods['aim']);
  unset($contactmethods['jabber']);
  unset($contactmethods['yim']);
  return $contactmethods;
}
add_filter('user_contactmethods','justemail_contactmethod',10,1);

add_filter('login_headerurl','remove_wp_link');function remove_wp_link($var){return NULL;}
add_filter('login_headertitle','pb_title');function pb_title($var){return 'Computer Science Circles, powered by WordPress';}

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
  echo sprintf("var SUBMITURL = '%s';\n", USUBMIT);
  echo sprintf("var SETCOMPLETEDURL = '%s';\n", USETCOMPLETED);
  echo sprintf("var CONSOLEURL = '%s';\n", UCONSOLE);
  echo sprintf("var FILESURL = '%s';\n", UFILES);
  echo sprintf("var HISTORYURL = '%s';\n", UHISTORY);
  echo sprintf("var OLDHISTORYURL = '%s';\n", UOLDHISTORY);
  echo sprintf("var VISUALIZEURL = '%s';\n", UVISUALIZE);
  echo sprintf("var MESSAGEURL = '%s';\n", UMESSAGE);
  echo sprintf("var MAILURL = '%s';\n", UMAIL);
  echo sprintf("var DEFAULTTIMEOUTMS = '%s';\n", (WALLFACTOR*1 + WALLBUFFER)*1000);

  echo "</script>\n";

  echo '<link type="text/css" rel="stylesheet" href="'.UPYBOXCSS.'" />' . "\n";
  echo '<script type="text/javascript" src="'.UPYBOXJS.'"></script>'."\n";

  echo "<link rel='stylesheet' type='text/css' href='".UPYBOX."customizations-codemirror2/codemirrorwp.css'>\n";
  echo "<link rel='stylesheet' type='text/css' href='".UPYBOX."customizations-codemirror2/textmate.css'>\n";
  echo "<script type='text/javascript' src='".UFILES."codemirror2/lib/codemirror.js'></script>\n";
  echo "<script type='text/javascript' src='".UFILES."codemirror2/mode/python/python.js'></script>\n";

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

add_filter( 'previous_post_rel_link', 'disable_stuff' );
add_filter( 'next_post_rel_link', 'disable_stuff' );
function disable_stuff( $data ) {
  return false;
}


add_action('wp_footer', 'footsy');
function footsy() { 
  global $popupBoxen;
  echo $popupBoxen;

  if (userIsAdmin() || userIsTranslator() || pll_current_language() != 'en') {
    echo '<span id="pylangswitcher">'.pll_the_languages(array('echo'=>0,'display_names_as' => 'slug'));
    if (userIsAdmin() || userIsTranslator())
      echo '<li><a href="http://cscircles.cemc.uwaterloo.ca/wp-admin/edit.php?post_type=page">Editor</a></li>';
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
  $plugin_array['preelementfix']  =  plugins_url('/pybox/customizations-wordpress-rich-editor/pre-fix/editor_plugin.js');
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
	$text = preg_replace('|</pre>\s*<br\s*/>|s', "</pre>", $text);
	$text = preg_replace('|</pre>\s*<br>|s', "</pre>", $text);
	$text = preg_replace('|<br/>\s*<pre>|s', "<pre>", $text);
	$text = preg_replace('|tt>|', "code>", $text);

	return $text;
}

function preprocesscallback1($m) {
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
}
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


remove_filter ('the_content',  'wpautop');
add_filter ('the_content',  'wpautop', 6);
add_filter ('the_content',  'fixwpautop', 20);
add_filter ('the_content',  'preprocess', 1);
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
  $wp_admin_bar->add_menu( array( 'parent' => 'user-actions', 'href' => UWPHOME . "user-page/", 'title'=>'My Progress', 'id'=>'up'));
  $wp_admin_bar->add_menu( array( 'id'=>'snap', 'parent' => 'user-actions', 'title' => 'Console (new window)', 'href' => UCONSOLE, "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'crackle', 'parent' => 'user-actions', 'title' => 'Resources (new window)', 'href' => URESOURCES, "meta" => array("target" => "_blank")));
  $wp_admin_bar->add_menu( array( 'id'=>'pop', 'parent' => 'user-actions', 'title' => 'Contact Us (new window)', 'href' => UCONTACT, "meta" => array("target" => "_blank")));

  if (!is_admin())
    $wp_admin_bar->add_menu( array( 'parent' => 'top-secondary', 'id' => 'totop', 
				    'title' => '<img onclick="scrollToTop()" title="scroll to top"'.
				    ' class="icon" src="'.UFILES . 'up.png"/>' ));
  
  global $wpdb;

  $students = getStudents();
  if (count($students)>0 || userIsAdmin()) {
    $studentClause = userIsAdmin()?"1":("ustudent IN (".implode(',', $students).")");
    $count = $wpdb->get_var("SELECT COUNT(1) FROM wp_pb_mail WHERE unanswered = 1 AND $studentClause");
    if ($count > 0) {
      $msg = $wpdb->get_row("SELECT ustudent, problem, ID FROM wp_pb_mail 
                             WHERE unanswered = 1 AND $studentClause ORDER BY ID ASC LIMIT 1", ARRAY_A);

      $url = UMAIL . "?who=".$msg['ustudent']."&what=".$msg['problem']."&which=".$msg['ID'].'#m';
      
      $wp_admin_bar->add_menu( array( 'parent' => 'top-secondary', 'id' => 'mail', 'href' => $url,
				      'title' => '<img title="goto oldest unanswered mail"'.
				      'class="icon" src="'.UFILES . "mail-icon.png\"/>($count)" ));
    }
  }
  
  if (current_user_can('level_10')) {	  

    $wp_admin_bar->add_node( array(
				   'id'        => 'admin-menu',
				   'parent'    => 'top-secondary',
				   'title'     => 'ADMIN',
				   'meta'      => array(
							'class'     => '',
							'title'     => __('Admin Menu'),
							),
				   ) );

    $ap = get_page_by_title('Admin Pages');

    $more_links = array(
			'Wordpress Dashboard' => get_bloginfo('wpurl') .'/wp-admin/index.php',
			'Edit THIS Page' => get_edit_post_link(),	
			'MySQL Frontend' => "/~atkong/pma/", 
			'AWS EC2 visualizer log' => 'http://107.22.148.206/trace-log.txt',
			'AWS Management Console' => 'https://console.aws.amazon.com/s3/home',
			'Daily submit-code usage' => get_permalink($ap).'/profiling/?frequency=10&activity=submit-code',
			'[listing of admin-manual follows]' => get_permalink($ap)
			);

    $pages = get_pages( array('child_of' => $ap->ID, 'post_status'=>'publish,private'));
    foreach ($pages as $page) 
      $more_links[$page->post_title] = get_permalink($page);

    $i = 0;
    foreach ($more_links as $title => $link) {
      $wp_admin_bar->add_node(array('parent'=>'admin-menu', 'id'=>"morelinks" . $i++, 'href' => $link, 'title' => $title));
    }
  }      

}

// end of file