<?php

// plugin-utilities: this file is included in include-me.php

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

function cscurl($slug) {

  if ($slug == 'visualize' || $slug == 'search') 
    return UWPHOME . $slug . '/';
  if ($slug == 'homepage') 
    return is_admin() ? "/" : pll_home_url();
  
  $cscslugmap = array(
		     ('progress') => 'user-page',
		     ('mail') => 'mail',
		     ('resources') => 'resources',
		     ('console') => 'console',
		     ('usage') => 'using-this-website',
		     ('contact') => 'contact',
		     ('install') => 'run-at-home',
		     );
  
  $s = $cscslugmap[$slug];
  $res = get_page_by_path($s)->ID;
  if (!is_admin())
    $res = pll_get_post($res);
  return get_permalink($res);
}

function userString($n, $short = false) {
  if ($n < 0) return "unregistered";
  $user = get_userdata($n);
  if ($user === FALSE) return FALSE;
  if (!$short)
    return $user->display_name . " (" 
      . $user->user_nicename . " " 
      . $user->user_email . " #" . $n . ")";
  return $user->user_nicename . " #" . $n ;
}

function userIsAdmin() {
  return is_user_logged_in() && current_user_can('level_10');
}

function userIsTranslator() {
  if (!is_user_logged_in()) return false;
  global $wpdb;
  return 1==$wpdb->get_var("select count(1) from wp_user2group_rs where group_id=10 and user_id=" . wp_get_current_user()->ID);
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

function pyboxlog($message, $suppressemail = -1) {
  $userid = getUserID();

  if ($suppressemail === -1) {
    $to = CSCIRCLES_EMAIL;
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
  $file = fopen(PPYBOXLOG, "a");
  date_default_timezone_set('America/New_York');
  fwrite($file, date("y-m-d H:i:s", time()) . " " . $message . "\n");
  fclose($file);
}

function softSafeDereference( $s, $which="", &$errtgt=NULL ) {
  //ppyboxlog("ssd".$s);
  $errtgt = FALSE;
  $r = "";
  $lines = explode("\n", $s);
  for ($i = 0; $i < count($lines); $i++) {
    if ($i > 0) $r .= "\n";
    if (substr($lines[$i], 0, 6)=="@file:") {
      $tmp = oneLineSoftSafeDereference( $lines[$i], $which, $errtgt );
      if ($tmp === FALSE) return FALSE;
      $r .= $tmp;
    }
    else
      $r .= $lines[$i];
  }
  return $r;
}

function oneLineSoftSafeDereference( $s, $which="", &$errtgt=NULL ) {
  //  pyboxlog("olssd".$s);
  $errtgt = FALSE;
  if (substr($s, 0, 6)=="@file:") {
    $comma = strpos($s, ",");
    if ($comma === FALSE)
      return safeDereference( $s, $which, $errtgt );
    else
      return safeDereference( substr($s, 0, $comma), $which, $errtgt) . "\n" .  
	oneLineSoftSafeDereference( substr($s, $comma+1), $which, $errtgt);
  }
  return $s;
}

function safeDereference( $s, $which="", &$errtgt=NULL ) {
// read contents of a file from the data directory
// sanitized to avoid trickery: only alphanumeric and .-_ and / allowed in filenames
// if it contains any .. then it fails
  // pyboxlog("sd".$s);
  $errtgt = FALSE;
  if (substr($s, 0, 6)!="@file:") return FALSE;
  $s = substr($s, 6);

  // exclude .. and force only alphanumerics plus /._-
  if (strstr($s, "..") != FALSE) return FALSE;
  if (preg_match('@[^a-zA-Z0-9/_\-.]@', $s)>0) return FALSE;

  $fn = PDATADIR  . trim($s);
  $co = file_get_contents($fn);

  
  if ($co === FALSE) 
    throw new PyboxException("Cannot find file " . $fn);

  if (getSoft($GLOBALS, 'pb_translation', NULL) != NULL)
    $co = translateOf($co, $GLOBALS['pb_translation']);

  return $co;
}

function softDump( $s, $target ) {
// writes/copies $s to $target, where $s is a string or a @file:filename
  pyboxlog($s .';' . $target, FALSE);
  if (substr($s, 0, 6)=="@file:")
    $s = softSafeDereference($s);
  pyboxlog($s .';' . $target, FALSE);
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


function preBox( $s, $len = -1, $lenlimit = 1000, $style = '' ) {
// takes any long string, trims if needed, converts special html chars, and wraps in pre tag
// the result can be directly inserted in html safely
// $len: if this is a smaller version of an original string, what was the original length?
  if ($s === NULL)
    return '<div><i>NULL</i></div>';
  if ($len == -1)
    $len = strlen($s);
  if ($lenlimit >= 0 && strlen($s) > $lenlimit) {
    $s = substr($s, 0, $lenlimit) . "\n" . 
      sprintf(__t('[Too long; only first %1$s out of %2$s characters shown]'), $lenlimit, $len) . "\n";
  }
  $style = ($style=="")?"":" style='$style'";
  return "<pre class='prebox'$style>"."\n".htmlspecialchars($s).'</pre>';
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

function popUp($linkText, $popup, $class = "") {
  //pyboxlog('popup' . $linkText . ';' . $popup . '.');
  global $pyRenderCount;
  if (!isset($pyRenderCount)) { // only used if called dynamically, e.g. "unsanitized output" in admin's submit.php
    $pyRenderCount = rand(1000, 9999);
  }
  $id = $pyRenderCount++;

  global $popupBoxen;

  $r = '';
  $r .= '<a class="hintlink" id="hintlink' . $id . '">';
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
    "<div class='hintOuter'>"
    . "<div class='hintLink'><a>$linkText</a></div>"
    . "<div class='hintContent' style='display: none;'>$popup</div>"
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
  $sqlcmd = "SELECT COUNT(*) FROM $table_name WHERE userid = '$uid' AND problem = '$problem'";
  $count = $wpdb->get_var($wpdb->prepare($sqlcmd));
  //pyboxlog("getcompleted " . $problem . $uid . $count);
  return ($count > 0)?TRUE:FALSE;
}

function getStudents() {
  global $current_user;
  get_currentuserinfo();

  if ( ! is_user_logged_in() )
    return array();

  global $wpdb;

  $ulogin = $current_user->user_login;

  $rows = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM wp_usermeta WHERE meta_key=%s AND meta_value=%s", 'pbguru', $ulogin));

  $result = array();
  foreach ($rows as $row) $result[] = $row->user_id;
  return $result;
}

function getStudentList() {
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
  $u = site_url();
  
  if (array_key_exists("redirect", $_GET)) {
    $u = $_GET['redirect'];
  }
  if (array_key_exists("redirect", $_POST)) {
    $u = $_POST['redirect'];
  }

  return "<div class='returnfromprofile'><a href='$u'>".__t("Return to Computer Science Circles")."</a></div>";
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

function beginProfilingEntry($data) {
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
  date_default_timezone_set('America/New_York');
  $data['start'] = date( 'Y-m-d H:i:s', time() );
  $data['preciseEnd'] = implode(".", timeAndMicro());
  $data['preciseStart'] = $data['preciseEnd'] - $seconds;
  $data['duration'] = $seconds;
  $data['userid'] = getUserID();
  if (array_key_exists('meta', $data))
    $data['meta'] = json_encode($data['meta']);
  global $wpdb;
  $wpdb->insert( "wp_pb_profiling", $data );
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


// end of file