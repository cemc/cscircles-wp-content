<?php

// utility functions available within the plugin

  // process raw content (used for example in nested shortcodes)
function do_short_and_sweetcode($x) {
  return do_shortcode(do_sweetcode($x));
}

// useful 

// translation
function __t($en_string) {
  return __($en_string, 'cscircles');// . get_locale(); // for debugging
}

function translateOf($string, $translations) {
  $translations = explode("\n", $translations);
  for ($i=0; $i<count($translations)/2; $i++) {
    $en = $translations[2*$i];
    $trans = $translations[2*$i+1];
    $string = str_replace($en, $trans, $string);
  }
  return $string;
}

function tabs_to_spaces($width, $text) {
  if (strpos($text, "\t") === FALSE) return $text;
  $lines = explode("\n", $text); // thankfully this works for \r\n too
  $result = "";
  foreach ($lines as $line) {
    $i = 0;
    $indent = 0;
    while ($i < strlen($line) and ($line[$i] == ' ' or $line[$i] == "\t")) {
      if ($line[$i] == " ") $indent++;
      else {$indent += $width; $indent -= $indent % $width;}
      $i++;
    }
    $result .= str_repeat(' ', $indent);
    $result .= substr($line, $i);
    $result .= "\n";
  }
  return $result;
}

// given a term describing a link to a special page,
// get the URL to the (properly translated) real location
function cscurl($desc) {
  if ($desc == 'visualize') return UVISUALIZER;

  //  if ($desc == 'homepage') // due to a bug, we can't translate during 'is_admin'
  //return is_admin() ? "/" : pll_home_url();
  
  $cscslugmap = array(
		     ('progress') => 'user-page',
		     ('mail') => 'mail',
		     ('resources') => 'resources',
		     ('console') => 'console',
		     ('usage') => 'using-this-website',
		     ('contact') => 'contact',
		     ('install') => 'run-at-home',
		     ('allusers') => 'admin-user-list',
		     ('homepage') => NULL // see below
		     );

  $s = $cscslugmap[$desc];
  global $wpdb;

  // compute $res, the native-language page id
  if ($desc == 'homepage') {
    
    // normally for cscircles, show_on_front is page
    // (front page displays static page in Settings/Reading)
    if (get_option('show_on_front') != 'page')
      return str_replace('/dev/', '/', get_option('siteurl'));

    $res = get_option('page_on_front');
  }
  else {
    $res = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name like %s", $s));
  }

  if (class_exists('PLL_Base')) {
    if (!is_admin()) {
      $old = $res;
      $res = pll_get_post($res);
      if ($res=="") $res = $old; // there was no translation
    }
    else {
      $lang = substr(get_user_meta( wp_get_current_user()->ID, "user_lang", true), 0, 2);
      if ($lang=='') $lang=substr(get_bloginfo("language"), 0, 2);
      if ($lang=='0') $lang=substr(get_bloginfo("language"), 0, 2);
      $res = pll_get_post($res, $lang);
    }
  }

  return get_permalink($res);
}

function pb_mail($from, $to, $subject, $body) {
  // $from should just be a string like me@domain.com or "Jim" <me@domain.com>
  // this function takes care of using this as reply-to and setting
  // an appropriate real sender.

  // we use Python if possible (better unicode support) and fallback to WP/PHP

  $ensemble = "$from\n$to\n$subject\n$body";
  //pyboxlog('[pb_mail]'.$ensemble, 1);
  $cmd = PPYBOXDIR . "send_email.py";
  $descriptorspec = array(
			  0 => array("pipe", "r"), 
			  1 => array("pipe", "w"), 
			  2 => array("pipe", "w")
			  );
  $process = proc_open($cmd, $descriptorspec, $pipes, '.', array('PYTHONIOENCODING'=>"utf_8"));
  $sent = FALSE;
  if (is_resource($process)) {
    fwrite($pipes[0], $ensemble);
    fclose($pipes[0]);
    //pyboxlog("message sent [$from|$to|$subject|" . stream_get_contents($pipes[1]) .'|'.stream_get_contents($pipes[2]).']', 1);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) == 0) $sent = TRUE;
  } 
  if (!$sent) {
    pyboxlog( "Used fallback for pb_mail: $ensemble", TRUE );
    wp_mail( $to, $subject, $body, "From: " . $from . '\n');
  }
}

function userString($n, $short = false) {
  if ($n < 0) return "unregistered";
  $user = get_userdata($n);
  if ($user === FALSE) return FALSE;

  $nicks = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentnicks', true), true);
  if (is_array($nicks) && array_key_exists($n, $nicks)) {
    return $nicks[$n];
  }

  if (!$short)
    return $user->user_login . " (" 
      . $user->user_email . " #" . $n . ")";
  return $user->user_login . " #" . $n ;
}

function userIsAdmin() {
  return is_user_logged_in() && current_user_can('level_10');
}

function userIsAssistant() {
  foreach (unserialize(CSCIRCLES_ASST_ID_MAP) as $lang => $id)
    if (getUserID() == $id) return true;
  return userIsAdmin();
}

function userIsTranslator() {
  if (!class_exists('PLL_Base')) return false;
  if (!is_user_logged_in()) return false;
  if (!ON_CEMC_SERVER) return false;
  global $wpdb;
  return 1==$wpdb->get_var("select count(1) from ".$wpdb->prefix."user2group_rs where group_id=10 and user_id=" . wp_get_current_user()->ID);
}

function nicefiedUsername($uid, $short = TRUE) {
  if (($uid == 0 && userIsAdmin()) || ($uid == getUserID())) 
    return __t('me');
  elseif ($uid == 0 || in_array($uid, unserialize(CSCIRCLES_ASST_ID_MAP)))
    return $short ? __t('Asst.') : __t('CS Circles Assistant');
  else
    return get_userdata($uid)->user_login;
}

function guruIDID($id) {
  $tmp = get_user_by('login', get_the_author_meta('pbguru', $id));  
  return ($tmp === FALSE) ? -1 : $tmp->ID;
}

function rightNowString() { // in format beloved by SQL
  date_default_timezone_set('America/New_York');
  return date("y-m-d H:i:s", time());
}

// see also: newuserwelcome
//           pybox.css reference to header background

function getUserID() {
  $u = wp_get_current_user();
  return empty($u->ID) ? -1 : $u->ID;
}

// 2-character code for current language
// doesn't work in Dashboard/Admin pages
function currLang2() {
  return class_exists('PLL_Base') ? pll_current_language() : substr(get_bloginfo("language"), 0, 2);
}

// same, but like en_US or fr_FR
function currLang4() {
  return class_exists('PLL_Base') ? pll_current_language("locale") : get_bloginfo("language");
}

// write to log, and send an e-mail
// if second arg is on, suppress e-mail when possible
function pyboxlog($message, $suppressemail = -1) {
  $userid = getUserID();

  if (($suppressemail === -1 || !defined('PPYBOXLOG'))
      && defined('PYBOXLOG_EMAIL')) {
    $to = PYBOXLOG_EMAIL;
    $subject = 'pyboxlog';
    $emailm  = $message . "\n" . "\n" 
      . "\n" . "REQUEST: " . print_r($_REQUEST, TRUE) . "\n" 
      . "\n" . "SERVER: " . print_r($_SERVER, TRUE) . "\n" 
      . "\n" . "USERID: " . $userid . "\n"
      . "\n" . "GET: " . print_r($_GET, TRUE) . "\n" 
      . "\n" . "POST: " . print_r($_POST, TRUE) . "\n" 
      //. "\n" . "SESSION: " . print_r($_SESSION, TRUE) . "\n" 
      //. "\n" . "ENV: " . print_r($_ENV, TRUE) . "\n" 
      ;
    mail($to, $subject, $emailm);
  }
  if (defined('PPYBOXLOG')) {
    $file = fopen(PPYBOXLOG, "a");
    date_default_timezone_set('America/New_York');
    fwrite($file, date("y-m-d H:i:s", time()) . " " . $message . "\n");
    fclose($file);
  }
}

function softSafeDereference( $orig ) {
// read contents of a file from the data directory
// sanitized to avoid trickery: only alphanumeric and .-_ and / allowed in filenames
// if it contains any .. then it fails
  // pyboxlog("sd".$s);

  if (substr($orig, 0, 6)!="@file:") return $orig;
  $s = substr($orig, 6);

  // exclude .. and force only alphanumerics plus /._-
  if (strstr($s, "..") != FALSE) return $orig;
  if (preg_match('@^[a-zA-Z0-9/_.-]+$@', $s)==0) return $orig;

  $fn = PDATADIR  . trim($s);
  $co = @file_get_contents($fn);
  
  if ($co === FALSE) 
    return $orig;
  //throw new PyboxException("Cannot find file " . $fn);

  if (getSoft($GLOBALS, 'pb_translation', NULL) != NULL)
    $co = translateOf($co, $GLOBALS['pb_translation']);

  return $co;
}

function softDump( $s, $target ) {
// writes/copies $s to $target, where $s is a string or a @file:filename
  //pyboxlog($s .';' . $target, FALSE);
  if (substr($s, 0, 6)=="@file:")
    $s = softSafeDereference($s);
  //pyboxlog($s .';' . $target, FALSE);
  $file = fopen($target, "w");
  if ($file === FALSE) return FALSE;
  if (fwrite($file, $s) === FALSE) return FALSE;
  return fclose($file);
}

function ensureNewlineTerminated( $s ) {
// returns the string, plus a newline if it didn't already end with one
  if ( ($s == '') || (substr( $s, -1, 1) != "\n"))
    return $s . "\n";
  return $s;
}


function rowSummary($arr) {
  foreach ($arr as $i => $v) {
    if (!isset($r)) $r = '['; else $r .= ', ';
    $r .= htmlspecialchars($i.':'.substr(json_encode($v), 0, 20));
  }
  return $r.']';
}

function preBox( $s, $len = -1, $lenlimit = 2000, $style = '', $hinted = false ) {
// takes any long string, trims if needed, converts special html chars, and wraps in pre tag
// the result can be directly inserted in html safely
// $len: if this is a smaller version of an original string, what was the original length?
  if ($s === NULL)
    return '<div><i>NULL</i></div>';

  if (strlen($s) > 1) {
    $lastline = strrpos($s, "\n", -2);
    if ($lastline === FALSE) $lastline = "";
    else $lastline = substr($s, $lastline);
    if (strlen($lastline) > $lenlimit)
      $lastline = substr($lastline, strlen($lastline) - $lenlimit);
  }
  else $lastline = '';

  if ($len == -1)
    $len = strlen($s);
  if ($lenlimit >= 0 && strlen($s) - strlen($lastline) > $lenlimit) {
    $s = substr($s, 0, $lenlimit) . "\n" . 
      sprintf(__t('[Too long; only first %1$s out of %2$s characters shown]'), $lenlimit, $len) . $lastline;
  }
  $style = ($style=="")?"":" style='$style'";

  $s = htmlspecialchars($s);

  if ($hinted) {
    $regex = "|Internal_Flag:(.*):galF|";
    
    preg_match($regex, $s, $match);

    $hint = determineHint($match[1]);

    if ($hint !== NULL) {
      $replacement = popUp($match[1], $hint, $style, 
                           "Click for more information about this error");
    }
    else
      $replacement = $match[1];
    
    $s = preg_replace($regex, $replacement, $s);
  }
  global $popupBoxen;
  return "<pre class='prebox'$style>"."\n".$s.'</pre>' . $popupBoxen;
}

function preBoxHinted( $s, $len = -1, $lenlimit = 2000, $style = '') {
  return preBox($s, $len, $lenlimit, $style . " widepophint hintedError", true);
}

function multilineToAssociative( $s ) {
// create associative array from a multiline string, treating it as alternating between symbols and strings
// e.g. "a\nsome words\nb\n100\n gives array("a" => "some words", "b" => "100")
  $s = trim($s);
  if ($s=="") return array();
  $list = explode("\n", $s);
  $result = array();
  for ($i=0; 2*$i+1 < count($list); $i++) {
    $result[$list[2*$i]] = $list[2*$i+1];
  }
  if ((count($list) % 2) == 1)
    $result[$list[count($list)-1]] = "";
  return $result;
}

function firstLine( $s ) { //get first line of a string
  $list = explode("\n", $s);
  return $list[0];
}

function popUp($linkText, $popup, $class = "", $title = "") {
  //pyboxlog('popup' . $linkText . ';' . $popup . '.');
  global $pyRenderCount;
  if (!isset($pyRenderCount)) { // only used if called dynamically, e.g. "unsanitized output" in admin's submit.php
    $pyRenderCount = rand(1000, 9999);
  }
  $id = $pyRenderCount++;

  global $popupBoxen;

  if ($title != '') $title=' title="'.$title.'" ';

  $r = '';
  $r .= '<a class="hintlink" '.$title.' id="hintlink' . $id . '">';
  $r .= $linkText;
  $r .= '</a>';

  $popupBoxen .= '<div id="hintbox' . $id . '" class="hintbox '.$class.'">';
  $popupBoxen .= '<div id="hintboxlink' . $id . '" class="hintboxlink"></div>';
  $popupBoxen .= $popup;
  $popupBoxen .= '</div>';

  return $r;
}

function JQpopUp($linkText, $popup) {
  return
    "<div class='jqpHintOuter'>"
    . "<div class='jqpHintLink'><a>$linkText</a></div>"
    . "<div class='jqpHintContent' style='display: none;'>$popup</div>"
    . "</div>";
}

function getCompleted($problem) {
  global $current_user;
  get_currentuserinfo();
  global $wpdb;

  if ( ! is_user_logged_in() )
    return FALSE;

  $uid = $current_user->ID;

  //pyboxlog($uid.' '.$problem); 
  $table_name = $wpdb->prefix . "pb_completed";
  $uname = $current_user->user_login;
  $sqlcmd = "SELECT COUNT(*) FROM $table_name WHERE userid = '$uid' AND problem = %s";
  $count = $wpdb->get_var($wpdb->prepare($sqlcmd, $problem));
  //pyboxlog("getcompleted " . $problem . $uid . $count);
  return ($count > 0)?TRUE:FALSE;
}

function getStudents($with_hidden = false) {
  global $current_user;
  get_currentuserinfo();

  if ( ! is_user_logged_in() )
    return array();

  global $wpdb;

  $ulogin = $current_user->user_login;

  $rows = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM ".$wpdb->prefix."usermeta WHERE meta_key=%s AND meta_value=%s ORDER BY user_id DESC", 'pbguru', $ulogin));

  $result = array();
  if ($with_hidden)
    $hidden = array();
  else
    $hidden = explode(",", get_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', true));
  foreach ($rows as $row) if (!in_array($row->user_id, $hidden)) $result[] = $row->user_id;
  return $result;
}

function getStudentList($with_hidden = false) {
  if ( ! is_user_logged_in() )
    return FALSE;

  $students = getStudents();

  return '('.implode(',', $students).')';
}

function bounded_stream_get_contents($strm, $maxlen) {
  $res = stream_get_contents($strm, $maxlen);
  $outputsize = strlen($res);
  do {
    $more = stream_get_contents($strm, $maxlen);
    $outputsize += strlen($more);
  }
  while (strlen($more) == $maxlen);
  return array('data' => $res, 'length' => $outputsize);
}

function returnfromprofile() {
  $u = getSoft($_REQUEST, 'wp_http_referer', site_url());
  
  return "<div><a class='button button-primary' href='$u'>".__t("Return to Computer Science Circles")."</a></div>";
}

function getSoft($array, $key, $default) {
  if (array_key_exists($key, $array))
    return $array[$key];
  return $default;
}
function setSoft(&$array, $key, $value) {
  if (!array_key_exists($key, $array))
    $array[$key] = $value;
}
function isSoft(&$array, $key, $value) {
  return array_key_exists($key, $array) && ($array[$key] === $value);
}
function unsetSoft(&$array, $key) {
  if (array_key_exists($key, $array))
    unset($array[$key]);
}

function booleanize($x) {
  return !(($x === FALSE) || ($x === 'N'));
}

function timeAndMicro() {
  $m = explode(" ", substr(microtime(), 2));
  return array($m[1], $m[0]);
}

function simpleProfilingEntry($data) {
  return;
  global $wpdb;
  date_default_timezone_set('America/New_York');
  $start = date( 'Y-m-d H:i:s', time() );
  $preciseStart = timeAndMicro();
  $table_name = $wpdb->prefix . "pb_profiling";
  $data['start'] = $start;
  $data['preciseStart'] = implode(".", $preciseStart);
  $data['preciseEnd'] = implode(".", $preciseStart);
  $data['duration'] = 0;
  $data['userid'] = getUserID();
  if (array_key_exists('meta', $data))
    $data['meta'] = json_encode($data['meta']);
  $wpdb->insert( $table_name, $data );
}

function beginProfilingEntry($data) {
  return;
  // $data must be a subset of the column names in wp_pb_profiling
  // if $data['meta'] exists it will get json_encoded
  global $wpdb;
  date_default_timezone_set('America/New_York');
  $start = date( 'Y-m-d H:i:s', time() );
  $preciseStart = timeAndMicro();
  $table_name = $wpdb->prefix . "pb_profiling";
  $data['start'] = $start;
  $data['preciseStart'] = implode(".", $preciseStart);
  $data['userid'] = getUserID();
  if (array_key_exists('meta', $data))
    $data['meta'] = json_encode($data['meta']);
  $wpdb->insert( $table_name, $data );
  return $wpdb->insert_id;
}

function endProfilingEntry($id, $data=array()) {  
  return;
  // $data must be a subset of the column names in wp_pb_profiling
  // any common values with beginProfilingEntry's $data will be overwritten
  // if $data['meta'] exists it will get json_encoded
  $preciseEnd = timeAndMicro();
  global $wpdb;
  $table_name = $wpdb->prefix . "pb_profiling";
  $preciseStart = explode(".", $wpdb->get_var("SELECT preciseStart from $table_name where ID = " . $id));
  $duration = (($preciseEnd[0]-$preciseStart[0])*100000000 + ($preciseEnd[1]-$preciseStart[1]))/100; // there are 8 digits of precision on this server, but the last two are 0
  $data['preciseEnd'] = implode('.', $preciseEnd);
  $data['duration'] = $duration/1000000;
  if (array_key_exists('meta', $data))
    $data['meta'] = json_encode($data['meta']);
  $wpdb->update($table_name, $data, array('ID' => $id));
}

// don't actually do separate start and end calls; just a single thing
function retroProfilingEntry($seconds, $data=array()) {
  return;
  date_default_timezone_set('America/New_York');
  $data['start'] = date( 'Y-m-d H:i:s', time() );
  $data['preciseEnd'] = implode(".", timeAndMicro());
  $data['preciseStart'] = $data['preciseEnd'] - $seconds;
  $data['duration'] = $seconds;
  $data['userid'] = getUserID();
  if (array_key_exists('meta', $data))
    $data['meta'] = json_encode($data['meta']);
  global $wpdb;
  $wpdb->insert( $wpdb->prefix."pb_profiling", $data );
}

function optionsHelper($options, $argname) {
  // $options is an associative array mapping value=>text for html <options> elements
  $select = getSoft($_GET, $argname, NULL);
  $r = "<select name='$argname'>";
  foreach ($options as $key => $text) 
    if ($key == $select)
      $r .= "<option value='$key' selected='selected'>$text</option>";
    else
      $r .= "<option value='$key'>$text</option>";
  $r .= '</select>';
  return $r;
}

function pythonEscape($string) {
  return "'''" . addslashes($string) . "'''";
}


function allSolvedCount() {
  global $wpdb;
  return $wpdb->get_var("select count(1) from ".$wpdb->prefix."pb_completed;");
}

function resendEmails() {
  global $wpdb;
  $return;
  foreach ($wpdb->get_results("SELECT * FROM ".$wpdb->prefix."pb_mail WHERE ID >= 3818 and ID <= 3835 and uto != 0", ARRAY_A) as $r) {
    $problem = $r['problem'];
    $pname = $wpdb->get_var("SELECT publicname FROM ".$wpdb->prefix."pb_problems WHERE slug like '$problem'");
    $purl = $wpdb->get_var("SELECT url FROM ".$wpdb->prefix."pb_problems WHERE slug like '$problem'");
    
    $subject = "CS Circles - Message about $pname";
    $to = get_user_by('id', $r['uto'])->user_email;

    if ($r['ufrom'] == 0)
      $mFrom = '"'. __t("CS Circles Assistant") . '" <'.CSCIRCLES_BOUNCE_EMAIL.'>';
    else {
      $user = get_user_by('id', $r['ufrom']);
      $mFrom = '"' . $user->user_login . '" <' . $user->user_email . '>';
    }


    $student = $r['ustudent'];
    $slug = $problem;
    $mailref = $r['ID'];

    $contents = "[Please accept our apologies for the delay in this message, which was caused by a mail daemon problem.]\n\n";
    
    $contents .= $r['body']."\n===\n";
    $contents .= __t("To send a reply message, please visit")."\n";
    $contents .= cscurl('mail') . "?who=$student&what=$slug&which=$mailref#m";
    $contents .= __t("Problem URL:")." " . $purl . "\n";
    $contents .= "[".__t("Sent by CS Circles")." ".cscurl("homepage")."]";

    //    $contents .= "\n\n" . $to;

    pyboxlog("Trying to resend message $mailref:$mFrom|$to|$subject|$contents", TRUE);
    pb_mail($mFrom, $to, $subject, $contents);
    pyboxlog("Resent message $mailref", TRUE);
  }
}  

function embed_atfile_link($name) {
  return '<a href="page-atfile-source.php?'.http_build_query(array('file'=>$name[1])).'">'.$name[0].'</a>';
}

function embed_atfile_links($s) {
  return preg_replace_callback("~@file:([a-zA-Z0-9./_-]+)~", "embed_atfile_link", $s);
}

function problemSourceWidget($parms, $bump = false) {
  if ( is_user_logged_in() ) {
    $classes = "get-problem-source";
    if ($bump) $classes .= " bumpit";
    
    return '<a class="'.$classes.'" target="_blank" title="'.__t('View definition')
      .'" href="'.UPROBLEMSOURCE.'?'.http_build_query($parms).'">'
      .'&lt;/&gt;</a>';
  }
  else return "";
}

function pageSourceWidget() {
  global $post;
  if ( is_user_logged_in() && isset($post) ) {
    $classes = "get-page-source";
    
    return '<a class="'.$classes.'" target="_blank" title="'.__t('View page source')
      .'" href="'.UPAGESOURCE.'?'.http_build_query(array("page"=>$post->ID)).'">'
      ."<img src='".UFILES."/cc.png'></a>";
      //      .'&lt;/&gt;</a>';
  }
  else return "";
}

function open_source_preamble() {
  if (!is_user_logged_in()) {
    echo "<i>Sorry, you need to be logged in to view this page.</i>";
    die(0);
  }

  // keep track in case anything weird happens
  $log = array();
  $log['page'] = $_SERVER['PHP_SELF'];
  $log['page'] = substr($log['page'], strrpos($log['page'], '/')+1);
  $log['query'] = $_GET;
  $log['ip'] = $_SERVER['REMOTE_ADDR'];

  simpleProfilingEntry(array("activity" => "view-source",
                             "meta" => $log));

  return '
We provide it under a <img src="files/cc.png" style="height: 1em"/>
<a href="http://creativecommons.org/licenses/by-nc-sa/3.0/">
Creative Commons Non-Commerical Share-Alike 3.0 License</a> 
<br>
Use it as a model in <a href="/authoring">creating your own</a> CS Circles lessons and exercises.
<br>
We encourage you to <a href="/contact">contact us</a> if you have questions or want to write, share or remix our content.
<br>
Visit <a href="https://github.com/cemc/cscircles-wp-content">https://github.com/cemc/cscircles-wp-content</a>
for the CS Circles backend source code.';
}

// end of file