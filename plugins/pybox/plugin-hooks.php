<?php

  /******************************/
  // stuff for main functionality. see also functions.php in the pybox2011childTheme

add_action('init', 'pyBoxInit');
function pyBoxInit() {
 wp_enqueue_script("jquery"); 
 wp_enqueue_script("jquery-ui-core"); 
 wp_enqueue_script("jquery-ui-dialog"); 
 wp_enqueue_script("jquery-ui-draggable");
 wp_enqueue_script("jquery-ui-sortable"); 
 wp_enqueue_script("jquery-ui-resizable");
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

add_action('wp_footer', 'footsy');
function footsy() { 
  global $popupBoxen;
  echo $popupBoxen;

  if (class_exists('Polylang_Base')) {
    echo '<span id="pylangswitcher">';
    //  echo '<li><a id="notice-trans" href="#">notice! (08-30)</a></li>';
    
    // these are the publicly-available languages
    foreach (array('en', 'fr', 'de') as $lang) {
      if ($lang != pll_current_language()) 
        echo '<li><a href="'.get_permalink(pll_get_post(get_the_ID(), $lang)).'">'.$lang.'</a></li>';
    }
    
    // these are the ones in development
    if (userIsAdmin() || 
        userIsTranslator() || userIsAssistant())
      foreach (array('nl') as $lang) {
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

  /******************************/
// important changes to what wordpress does by default

// texturize is too annoying once we have translations and code all mixed together
foreach ( array( 'comment_author', 'term_name', 'link_name', 'link_description', 'link_notes', 'bloginfo', 'wp_title', 'widget_title', 'the_title', 'the_content', 'the_excerpt', 'comment_text', 'list_cats' ) as $filter ) {
  remove_filter($filter, 'wptexturize');
}

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

function postprocesscallback($m) {
  return softSafeDereference($m[1]);
}
function postprocess($text) {
  $text = preg_replace_callback("|{pyRaw ([^}]*)}|", "postprocesscallback", $text);
  return $text;
}

// undo overly agressive paragraphing
add_filter ('the_content',  'fixwpautop', 20);
// now allow raw material we don't want touched
add_filter ('the_content',  'postprocess', 100);

  /******************************/
// tinyMCE editor stuff

// adapted from http://www.ilovecolors.com.ar/tinymce-plugin-wordpress/
add_filter('mce_buttons_3', 'pb_mce_buttons');
function pb_mce_buttons($buttons) {
  array_push($buttons, 
	     "pbbPre", "pbbCode", "pbbAQuo", "|", 
	     "pbbBox", "pbbShort", "pbbMulti", "pbbMultiScramble", "|", 
	     "pbbHint", "pbbWarn", "pbbLink", "|", 
	     "pbbPlain"); 
  return $buttons;
}

add_filter("mce_external_plugins", "pb_mce_external_plugins");
function pb_mce_external_plugins($plugin_array) {
  $plugin_array['pybuttons']  =  plugins_url('/pybox/customizations-wordpress-rich-editor/pybuttons.js');
  return $plugin_array;
}

add_action('admin_head', 'tinymce_wsfix');
function tinymce_wsfix() {
  echo "<script type='text/javascript' src='".UPYBOX."customizations-wordpress-rich-editor/editor-ws.js'></script>\n";
}

function enable_more_buttons($buttons) {
  $buttons[] = 'sub';
  $buttons[] = 'sup';
  return $buttons;
  }
add_filter("mce_buttons", "enable_more_buttons");


  /******************************/
  // other important setup actions

add_action( 'init', 'setup_translation' );
function setup_translation() {
  load_plugin_textdomain('cscircles', FALSE, dirname( plugin_basename( __FILE__ ) ));
}

// help prevent sender warnings   
add_action('phpmailer_init', 'set_mail_sender');
function set_mail_sender(&$phpmailer) {
  $phpmailer->Sender = CSCIRCLES_BOUNCE_EMAIL;
}

// we also used to do robots_txt, but not working, so deleted

/************************************/
// this was a magical invocation against IE at some point in time
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




// end of file