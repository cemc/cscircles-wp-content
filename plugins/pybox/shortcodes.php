<?php
add_shortcode('pyBox', 'pyBoxHandler');
add_shortcode('pyLink', 'pyLinkHandler');
add_shortcode('pyHint', 'pyHintHandler');
add_shortcode('pyShort', 'pyShortHandler');
add_shortcode('pyWarn', 'pyWarnHandler');
add_shortcode('pyMulti', 'pyMultiHandler');
add_shortcode('pyExample', 'pyExampleHandler');
add_shortcode('pyProtect', 'pyProtectHandler');
add_shortcode('pyMultiScramble', 'pyMultiScrambleHandler');
add_shortcode('newuserwelcome', 'newuserwelcome');
add_shortcode('br', 'printbr');

add_shortcode('pyRecall', 'pyRecallHandler');

function loadMostRecent($slug) {
  if ( !is_user_logged_in() ) 
    return NULL;

  global $wpdb;
  $uid = wp_get_current_user()->ID;
  $table_name = $wpdb->prefix . "pb_submissions";
  $sqlcmd = "SELECT usercode FROM $table_name WHERE userid = %d AND problem = %s ORDER BY beginstamp DESC LIMIT 1";
  return $wpdb->get_var( $wpdb->prepare($sqlcmd, $uid, $slug));
}

function printbr($o ,$c){return '<br/>';}

add_shortcode('pyDebug', 'pyDebug');
function pyDebug($o, $c){
  if (PB_DEV)
    return do_shortcode($c);
}

add_shortcode( 'list-pybox-pages', 'list_pybox_pages' );

function pberror( $errmsg) {
  pyboxlog("[pyBoxHandler] " . $errmsg);
  return "<b>".sprintf(__t("Internal error, details below; please <a href='%s'>contact staff</a>."), cscurl('contact'))."</b> ".__t("Timestamp:")." " . date("y-m-d H:i:s", time()) . "<br/>" . preBox($errmsg);}

function pyLinkHandler($options, $content) {
  $code = softSafeDereference($options["code"]);
  $r = '';
  $r .= '<a href="' . cscurl('console') . "?consolecode=" . rawurlencode( $code ) . '" target="_blank">';
  $r .= $content;
  $r .= '</a>';
  return $r;
}

function pyHintHandler($options, $content) {
  return popUp($content, do_shortcode($options["hint"]),
	       getSoft($options, "class", ""));
}

function checkbox($slug) {
  if ($slug != 'NULL' && getCompleted($slug)) 
    return "<img title='".__t("You have completed this problem at least once.")."' src='".UFILES."checked.png' class='pycheck'/>";
  else
    return "<img title='".__t("You have not yet completed this problem.")."' src='".UFILES."icon.png' class='pycheck'/>";
}

function generateId() {
  global $pyRenderCount;
  if (!isset($pyRenderCount)) $pyRenderCount = 0;
  $id = $pyRenderCount++;
  return $id;
}

function registerPybox($id, $slug, $type, $facultative, $title, $content, $args = NULL, $hash = NULL, $graderOptions = NULL) {
  if (is_array($args))
    $args = json_encode($args);
  global $wpdb, $post;
  if (userIsAdmin() && array_key_exists("makeproblemdb", $_GET)) {
     $table_name = $wpdb->prefix . "pb_problems";
    $row = array();
    $row['postid'] = $post->ID;
    if ($_GET["makeproblemdb"] >= 0) {
      $row['lesson'] = $_GET["makeproblemdb"];
    }
    $row['boxid'] = $id;
    if ($slug != 'NULL')
      $row['slug'] = $slug;
    $row['type'] = $type;
    $row['content'] = $content;
    $row['facultative'] = $facultative;
    $row['url'] = get_page_link($post->ID) . '#pybox' . $id;
    if ($title != NULL) {
      if ($_GET["makeproblemdb"] >= 0) 
	$row['publicname'] = $_GET["lessonnumber"] . ': ' . $title;
      else
	$row['publicname'] = $title; //e.g., for the console, which is not part of any lesson
    }
    if ($args != NULL) {
      $row['shortcodeArgs'] = $args;
    }
    if ($hash != NULL) {
      $row['hash'] = $hash;
    }      
    if ($graderOptions != NULL) {
      $row['graderArgs'] = $graderOptions;
    }
    $row['lang'] = $_GET['lessonlang'];
    $wpdb->insert($table_name, $row);
  }
  else if ($hash != NULL) {
    if ($wpdb->get_var("SELECT COUNT(1) from wp_pb_problems WHERE hash = '$hash'") == 0) {
      // hash is important, but not yet registered!
      // typically this would occur if we're editing a file
      // if the hash doesn't exist, add it so the grader knows what do to with submissions
      $row = array(
		   'type'=>$type,
		   'postid'=>$post->ID,
		   'boxid'=>$id,
		   'facultative'=>$facultative,
		   'url' => get_page_link($post->ID) . '#pybox' . $id,
		   'shortcodeArgs' => $args,
		   'graderArgs' => $graderOptions,
		   'hash' => $hash,
		   'lang' => pll_current_language());
      if ($slug != 'NULL')  $row['slug'] = $slug;
      $wpdb->insert("wp_pb_problems", $row);
    }
  }
}

function heading($type, &$options) {
  $r = '';
  $r .= '<div class="heading">';
  if (array_key_exists('title', $options))
    $r .= "<span class='type'>$type: </span><span class='title'>" . $options['title'] . '</span>';
  else
    $r .= '<span class="title">' . $type . '</span>';
  $r .= '</div>';
  return $r;
}

function pyShortHandler($options, $content) {
  $id = generateId();
  
  $answer = $options["answer"];

  $type = getSoft($options, 'type', 'trimmableString');

  $r = '';
  $slug = getSoft($options, 'slug', 'NULL');
  $r .= "<div class='pybox modeNeutral' id='pybox$id'>\n";
  registerPybox($id, $slug, "short answer", FALSE, getSoft($options, 'title', NULL), $content, $options);
  $r .= checkbox($slug);
  if (!array_key_exists('slug', $options))
    $r .= "<b style='color:red;'>WARNING: this problem needs a permanent slug to save user data</b></br>";

  $r .= heading(__t('Short Answer Exercise'), $options);

  $r .= do_shortcode($content);
  $r .= '<br><label for="pyShortAnswer'.$id.'">'.__t('Your answer');
  if ($type=="number") $r .= ' ('.__t('enter a number').')';
  $r .= ': </label><input type="text" onkeypress="{if (event.keyCode==13) pbShortCheck('.$id.')}" id="pyShortAnswer'.$id.'">';
  //  $r .= '<hr>';
  $r .= '<div class="pyboxbuttons">';
  $r .= '<input type="hidden" name="type" value="'. $type . '"/>';
  $r .= '<input type="hidden" name="correct" value="'. $answer . '"/>';
  $r .= '<input type="hidden" name="slug" value="'. $slug . '"/>';
  $r .= "<input type='submit' style='margin:5px;' value='".__t('Check answer')."' onClick = 'pbShortCheck($id)'/>";
  $r .= '</div>';
  $r .= '<div class="pbresults" id="pyShortResults'.$id.'"></div>';
  $r .= '<div class="epilogue">'. getSoft($options, "epilogue", __t("Correct!")) . '</div>';
  $r .= '</div>';
  
  return $r; 
} 

function pyWarnHandler($options, $content) {
  $scontent = do_shortcode($content);
  return "<table class='pywarn'><tr><td class='pywarnleft'><img src='".UWARN."'/></td>".
    "<td class='pywarnright'><span> $scontent </span></td></table>";
}

function pyMultiHandler($options, $content) {
  $id = generateId();

  $right = softSafeDereference($options["right"]);
  $wrong = explode("\n", trim(softSafeDereference($options["wrong"])));
  $shuff = range(-1, count($wrong)-1);
  shuffle($shuff);

  $r = '';
  $slug = getSoft($options, 'slug', 'NULL');
  $r .= "<div class='pybox modeNeutral' id='pybox$id'>\n";
  registerPybox($id, $slug, "multiple choice", FALSE, getSoft($options, 'title', NULL), $content, $options);
  $r .= checkbox($slug);
  if (!array_key_exists('slug', $options))
    $r .= "<b style='color:red;'>WARNING: this problem needs a permanent slug to save user data</b></br>";

  $r .= heading(__t('Multiple Choice Exercise'), $options);

  $r .= '<div>';
  $r .= do_shortcode($content);
  $r .= '</div><label>'.__t('Your choice:').' </label><select id="pyselect' . $id . '"><option value="d" selected>'.__t('Select one').'</option>';
  foreach ($shuff as $s) {
    if ($s==-1)
      $r .= '<option value="r">' . $right . '</option>';
    else
      $r .= '<option value="w">' . $wrong[$s] . '</option>';
  }
  $r .= '</select>';
  //$r .= '<hr>';
  $r .= "<div class='pyboxbuttons'>";
  $r .= '<input type="hidden" name="slug" value="'. $slug . '"/>';
  $r .= "<input type='submit' style='margin:5px;' value='".__t("Check answer")."' onClick='pbMultiCheck($id)'/>";
  $r .= '</div>'; //pyboxbuttons
  $r .= '<div class="pbresults" id="pyMultiResults'.$id.'"></div>';
  $r .= '<div class="epilogue">'. getSoft($options, "epilogue", __t("Correct!")) . '</div>';
  $r .= '</div>'; //pybox

  return $r;
}

function pyExampleHandler($options, $content) {
  if (array_key_exists('translate', $options)) {
    $GLOBALS['pb_translation'] = $options['translate'];
  }
  if ($options == FALSE) $options = array();
  $options['pyexample'] = 'Y';
  $result = pyBoxHandler($options, $content);
  $GLOBALS['pb_translation'] = NULL;
  return $result;
}

function pyMultiScrambleHandler($options, $content) {
  $id = generateId(); 

  $answer = $options['answer'];
  if (substr($answer, 0, 6)=='@file:')
    $answer = trim(softSafeDereference($answer));
  $answer = explode("\n", $answer);
  for ($i=0; $i<count($answer); $i++) 
    $answer[$i] = array($i, $answer[$i]);
  shuffle($answer);

  $r = '';
  $slug = getSoft($options, 'slug', 'NULL');
  $r .= "<div class='pybox modeNeutral multiscramble' id='pybox$id'>\n";
  registerPybox($id, $slug, "multichoice scramble", FALSE, getSoft($options, 'title', NULL), $content, $options);
  $r .= checkbox($slug);
  if (!array_key_exists('slug', $options))
    $r .= "<b style='color:red;'>WARNING: this problem needs a permanent slug to save user data</b></br>";

  $r .= heading(__t('Scramble Exercise'), $options);

  $r .= $content;
  $r .= '<ul class="pyscramble" name="pyscramble" id="pyscramble' . $id . '">' . "\n";
  foreach ($answer as $a) 
    $r .= '<li id="pyli' . $id . '_' . $a[0] . '" class="pyscramble" >' . $a[1] . '</li>' . "\n"; 
  $r .= '</ul>' . "\n";
  $r .= '<div class="pyboxbuttons">';
  $r .= "<input type='button' value='".__t("Check answer")."' onclick='pbMultiscrambleCheck($id)'/>\n";
  $r .= '<input type="hidden" name="slug" value="'.$slug.'"/>' . "\n";
  $r .= '</div>'; 
  $r .= '<div id="pbresults' . $id . '" class="pbresults"></div>';
  $r .= '<div class="epilogue">'. getSoft($options, "epilogue", __t("Correct!")) . '</div>';
  $r .= '</div>';

  return $r;
}

function pyProtectHandler($options, $content) {
  return $options['protect'];
}

function pyRecallHandler($options, $content) {

  if (!array_key_exists('slug', $options))
    return "[pyRecall error: no slug given]";
  
  global $wpdb;
  $problem = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_pb_problems WHERE slug like %s", $options['slug']), ARRAY_A);

  if ($problem == NULL) 
    return "[pyRecall error: slug " . $options['slug'] . " not found]";

  if (trim($content)=="")
    $content = $problem['content'];

  $mergedOptions = json_decode($problem['shortcodeArgs'], TRUE);
  if (array_key_exists('translate', $options)) {
    $GLOBALS['pb_translation'] = $options['translate'];
    foreach ($mergedOptions as $key => $value)
      $mergedOptions[$key] = translateOf($mergedOptions[$key], $options['translate']);
  }

  foreach ($options as $o=>$v) {$mergedOptions[$o] = $v;}
  
  $result = NULL;

  if ($problem['type'] == "code") $result = pyBoxHandler($mergedOptions, $content);
  if ($problem['type'] == "scramble") $result = pyBoxHandler($mergedOptions, $content);
  if ($problem['type'] == "short answer") $result = pyShortHandler($mergedOptions, $content);
  if ($problem['type'] == "multiple choice") $result = pyMultiHandler($mergedOptions, $content);
  if ($problem['type'] == "multichoice scramble") $result = pyMultiScrambleHandler($mergedOptions, $content);
  
  $GLOBALS['pb_translation'] = NULL;

  if ($result == NULL) 
    return "[pyRecall error: unknown type " . $problem['type'] . "]";

  return $result;
}

function newuserwelcome($options, $content) {
  if ( ! is_user_logged_in() ) {
    $ulog = wp_login_url( home_url() );
    $uuse = cscurl('usage');

    return "<em>".sprintf(__t('New around here? Read <a %1$s>Using this Website</a>. You can also <a %2$s>click here to create an account or log in</a>.'), 
			  "class='open-same-window' href='$uuse'", 
			  "class='open-same-window' href='$ulog'")."</em><br/>";
  }
  return "";
}

function sanitize_helper($matches) {
  $attr = shortcode_parse_atts( $matches[3] );

  // anything which is not a slug is not an exercise and passes through unchanged.
  if (!array_key_exists('slug', $attr)) 
    return $matches[0]; 
    
  if (!array_key_exists('title', $attr)) {
    pyboxlog('[in sanitize_helper] Warning: ' . $attr['slug'] . ' has no title!');
    $attr['title'] = "";
  }

  $r = '[pyRecall slug="' . $attr['slug'] . '"';

  $to_translate = array('title', 'epilogue', 'right', 'wrong', 'defaultcode');
  foreach ($to_translate as $key) 
    if (array_key_exists($key, $attr)) {
      $r .= ' ' . $key .'="';
      $value = $attr[$key];
      $value = str_replace('"', '""', $value);
      $value = str_replace("\n", '\n', $value);
      $r .= $value . '"';
    }

  $r .= ']' . $matches[5] . '[/pyRecall]';

  return $r;
}

function sanitize($page) {
  $page = str_replace("\r", "", $page);
  $regex = '(\[?)\[(pyExample|pyShort|pyMulti|pyMultiScramble|pyBox)\b((?:[^\'"\\]]|' . "'[^']*'|" . '"[^"]*")*)(?:(\/))?\](?:(.+?)\[\/\2\])?(\]?)';
  return preg_replace_callback( "_$regex"."_s", 'sanitize_helper', $page);
}

// for the navigation page
function list_pybox_pages($options, $content) {
  $out = get_pages(array('parent'=>0, 'status'=>'publish'));
  $links = array();
  global $polylang;
  foreach ($out as $page) {

    if ($polylang->get_translation('post', $page->ID, 'en') == $page->ID) { //english only

      $links[] = array("url"=>get_page_link( $page->ID), "title"=>$page->post_title);
      if (isSoft($_GET, 'export', 'Y')) {
	$p = get_page($page->ID);
	$slug = $p->post_name;
	$f = fopen(PEXPORT . $slug . ".txt", 'w');
	fwrite($f, 'Title: ' . $p->post_title . "\n\nContent:\n\n");
	fwrite($f, sanitize($p->post_content));
	fclose($f);
      }
      elseif (isSoft($_GET, 'export', 'raw')) {
	$p = get_page($page->ID);
	$slug = $p->post_name;
	$f = fopen(PEXPORT . $slug . ".txt", 'w');
	fwrite($f, 'Title: ' . $p->post_title . "\n\nContent:\n\n");
	fwrite($f, $p->post_content);
	fclose($f);
      }
    }
  }

  $user_id      = get_current_user_id();
  $current_user = wp_get_current_user();
  $profile_url  = get_edit_profile_url( $user_id );

  if ( $user_id ) {
    $links[] = array("url"=>$profile_url, "title"=>__t('Edit my profile'));
    $links[] = array("url"=>wp_logout_url(), 'title'=>__t('Log out now'));
  } 
  else
    $links[] = array("url"=>wp_logout_url(), 'title'=>__t('Log in or create a new account'));


  foreach ($links as $i=>$page) {
    $s = $page['title'];
    $m = preg_match('/^([0-9]+)([A-Z])?\: (.*)$/', $s, $matches);
    if ($m <= 0)
      $k = '9999---' . $s;
    else {
      $sub = $matches[2];
      if ($sub=="") $sub="-";
      $k = sprintf("%04d", $matches[1]) . '-' . $sub . '-'.$matches[3];
    }
    $links[$i]['key']=$k;
  }
  //  print_r($links);
  function cmp($p1, $p2) {
    return strcmp($p1['key'], $p2['key']);
  }
  usort($links, 'cmp');
  echo '<ul>';
  foreach ($links as $page) {
    echo "<li><a class='open-same-window' href='".$page['url'] ."'>".$page['title']."</a></li>";
  }
  echo '</ul>';
}


  // helper functions for pyBoxHandler
  function button($name, $atts) { $res = "<td><input type='button' name='$name' ";
    foreach ($atts as $att => $val) $res .= $att.'="'.$val.'" ';
    return $res . "></td>\n"; }
  function option($name, $atts) {
    $res = "<option name='$name' ";
    $atts['data-pbonclick']=$atts['onclick'];
    foreach ($atts as $att => $val) if ($att != 'value' && $att != 'onclick') $res .= $att.'="'.$val.'" ';
    return $res . ">" . $atts['value'] . "</option>\n"; }

function pyBoxHandler($options, $content) {

  // given a shortcode, print out the html for the user, 
  // and save the relevant grader options in a hash file.
  $id = generateId();

  if ($options == FALSE) $options = array();           // wordpress does a weird thing where valueless
  for ($i=0; array_key_exists($i, $options); $i++) {   // attributes map like [0]=>'attname'.
    $options[$options[$i]] = "Y";                      // these lines change it to
    unset($options[$i]);                               // 'attname'=>'Y'
  }
  $shortcodeOptions = json_encode($options);           // this will be put into DB.  

  /// do some cleaning-up and preprocessing of options, and create the problem info for grader

  if (array_key_exists('pyexample', $options)) {
    setSoft($options, 'grader', '*nograder*');
    setSoft($options, 'readonly', 'Y');
    setSoft($options, 'hideemptyinput', 'Y');
    unset($options['pyexample']);
  }
  
  if (array_key_exists('code', $options)) {            // sugar: code is an alias for defaultcode
    $options["defaultcode"] = $options["code"];
    unset($options['code']);
  }

  $richreadonly = array_key_exists('richreadonly', $options);    // sugar
  if ($richreadonly) {
    $options['readonly'] = "Y";
    unset($options['richreadonly']);
  }
  
  if (array_key_exists('nograder', $options)) {        // syntactic sugar for nograder option
    if (array_key_exists('grader', $options))
      pyboxlog('Warning: grader overwritten with *nograder*');
    $options["grader"] = "*nograder*";
    unset($options['nograder']);
  }

  foreach ($options as $optname => $optvalue)          // syntactic sugar for inplace grader 
    if (preg_match('|tests|', $optname)>0)
      $options["inplace"] = "Y"; 

  global $post;                                        // $lessonNumber is numeric (major) part of lesson number
  if (preg_match('|^(\\d+).*|', 
		 $post->post_name, $matches)==0)      
    $lessonNumber = -1; 
  else
    $lessonNumber = $matches[1];

  $inplace = booleanize(getSoft($options, 'inplace', 'N'));    
  $scramble = booleanize(getSoft($options, 'scramble', 'N'));      // important booleans used to determine
  $readonly = booleanize(getSoft($options, 'readonly', 'N'));      // other options... get them first
  $showEditorToggle = booleanize(getSoft($options, 'showeditortoggle', 'N'));
  unset($options['scramble']);
  unset($options['readonly']);                                     // don't extract() these!
  unset($options['inplace']);

  if ($inplace) setSoft($options, 'hideemptyoutput', 'Y');

  $defaultValues = array
    ('defaultcode' => FALSE,        // default values in case not explicitly selected
     'autocommentline' => ($lessonNumber > 3) && !($scramble || $readonly),
     'console' => 'N',
     'rows' => 10,
     'allowinput' => $lessonNumber > 5 && !$scramble && !$readonly,
     'disablericheditor' => (($lessonNumber > -1 && $lessonNumber < 7) || $scramble || $readonly) && !$richreadonly,
     'usertni' => $inplace
    );

  foreach ($defaultValues as $key => $value) 
    if (!array_key_exists($key, $options))
      $options[$key] = $defaultValues[$key];

  extract($options);

  $allowinput = booleanize($allowinput);
  $disablericheditor = booleanize($disablericheditor);
  $console = booleanize($console);
  $autocommentline = booleanize($autocommentline);
  $usertni = booleanize($usertni);

  $facultative = ((isset($grader) && ($grader == '*nograder*')) || $console || $readonly);
  if ($scramble || $readonly) $options['nolog'] = 'Y';

  // for grader. note that if they are absent, their default values are 'N'
  if ($facultative) $options['facultative'] = 'Y'; else unset($options['facultative']);
  if ($allowinput) $options['allowinput'] = 'Y'; else unset($options['allowinput']);
  if ($scramble) $options['scramble'] = 'Y'; // already unset
  if ($readonly) $options['readonly'] = 'Y';
  if ($inplace) $options['inplace'] = 'Y';
  if ($usertni) $options['usertni'] = 'Y'; else unset($options['usertni']);

  $cosmeticOptions = array('defaultcode', 'autocommentline', 'console', 
			   'rows', 'disablericheditor', 'scramble', 'readonly',
			   'showeditortoggle', 'title', 'placeholder');
  $copyForGrader = array();
  foreach ($options as $optname => $optvalue) {
    if (!in_array($optname, $cosmeticOptions))
	$copyForGrader[$optname] = $optvalue;
  }
  if (array_key_exists('maxeditdistance', $options))
    $copyForGrader['originalcode'] = $defaultcode;
  $optionsJson = json_encode($copyForGrader);

  $hash = md5($shortcodeOptions . $optionsJson);

  $slug = getSoft($options, 'slug', 'NULL');

  registerPybox($id, $slug, $scramble?"scramble":"code", $facultative, getSoft($options, 'title', NULL), $content, $shortcodeOptions, $hash, $optionsJson);

  /// we've delivered options to the grader. get on with producing html

  if (($defaultcode === FALSE) && $scramble && ($solver !== FALSE)) {
    $lines = explode("\n", trim(softSafeDereference($solver)));
    shuffle($lines);
    $defaultcode = implode("\n", $lines);
  }
  if ($defaultcode !== FALSE) {
    try 
      { $defaultcode = softSafeDereference($defaultcode); }
    catch (PyboxException $e) 
      { return pberror("PyBox error: defaultcode file " . $defaultcode . " not found."); }
    $defaultcode = ensureNewlineTerminated($defaultcode);
  }

  $enableDebugFeatures = array_key_exists("d", $_GET); # || userIsAdmin();

  /// actually start outputting here. part 1: headers and description

  $r = '';

  $readyScripts = '';

  $r .= '<form class="pbform" action="#" id="pbform' . $id . '" method="POST">'."\n";

  if ($scramble)
    $c = "scramble";
  else if ($enableDebugFeatures) {
    $c = "debug";
  }
  else $c = "";
  if ($facultative)
    $c .= " facultative";

  $r .= "<div class='pybox modeNeutral $c' id='pybox$id'>\n";

  if (!$facultative && !array_key_exists("slug", $options)) {
    pyboxlog("Hash " . $hash . " not read-only, but needs a slug", TRUE);
    $r .= "<b style='color:red;'>WARNING: this problem needs a permanent slug to save user data</b></br>";    
  }

  if ($facultative) 
    $r .= heading(__t('Example'), $options);
  else {
    $r .= checkbox($slug);
    $r .= heading($scramble ? __t('Scramble Exercise') : __t('Coding Exercise'), $options);
  }

  
  $r .= do_shortcode($content); //instructions, problem description. process any shortcodes inside.


  // part 1.5: help box

if (!$facultative && !$scramble) {
  $r .= '<div class="helpOuter" style="display: none;"><div class="helpInner">';
  if (!is_user_logged_in()) {
    $r .= '<div style="text-align: center">'.__t('You need to create an account and log in to ask a question.').'</div>';
  }
  else {
    global $wpdb;
    $guru_login = get_the_author_meta('pbguru', get_current_user_id());
    if ($guru_login != '')
      $guruid = $wpdb->get_var($wpdb->prepare('SELECT ID from wp_users WHERE user_login = %s', $guru_login));
    $r .= '<div style="text-align: center">';
    if ($guru_login != '' and $guruid !== NULL) {
      $r .= __t('Send a question by e-mail to: ');
      $r .= "<select class='recipient'><option value='0'>".__t("Select one...")."</option><option value='1'>".__t("My guru")." ($guru_login)</option><option value='-1'>".__t("CS Circles Assistant")."</option></select></div>";
    } 
    else {
      $r .= __t('Send a question by e-mail to: ');
      $r .= "<select class='recipient'><option value='0'>".__t("Select one...")."</option><option value='0' disabled='disabled'>".__t("My guru (you don't have one)")."</option><option value='-1'>".__t("CS Circles Assistant")."</option></select>";
      $r .= "<i>".
	sprintf(__t("(name a guru on <a %s>your Profile Page</a>)"), "href='".get_edit_profile_url(get_current_user_id())."'")
	."</i><br/>";
      $r .= '</div>';
    }
    $r .= __t("Enter text for the message below. <i>Be sure to explain where you're stuck and what you've tried so far. Your partial solution code will be automatically included with the message.</i>");
    $r .= "<textarea style='font-family: serif'></textarea>";
    $r .= "<table class='helpControls'><tr><td style='width: 50%'><a onclick='sendMessage($id,\"$slug\")'>".__t("Send this message")."</a></td><td style='width: 50%'>
           <a onclick='helpClick($id)'>".__t("Cancel")."</a></td></tr></table>";
  }

  $r .= '</div></div>';
 }

  /// part 2: code input

  if ($readonly) {
    $thecode = trim($defaultcode);
    $rows = count(explode("\n", $thecode));
  }
  elseif (($console=="Y") && array_key_exists("consolecode", $_GET)) {
    $thecode = htmlspecialchars(html_entity_decode(stripslashes($_GET["consolecode"])));
    $rows = count(explode("\n", $thecode))+1;
  }
  else {
    $thecode = $defaultcode;
    if ($autocommentline)
      $thecode .= __t('# delete this comment and enter your code here')."\n";
    
    if (array_key_exists('slug', $options) && $scramble === FALSE) {
      $savedCode = loadMostRecent($options['slug']);
      if ($savedCode !== NULL)
	$thecode = $savedCode; 
    }

  }

  if ($scramble) {
    $r .= '<ul class="pyscramble" name="pyscramble" id="pyscramble' . $id . '">'."\n";
    foreach (explode("\n", rtrim($thecode)) as $s) {
      if (strpos( $s, 'delete this comment' ) === FALSE) // fix an old bug -- got stuck in database
	$r .= ' <li class="pyscramble">' . 
	  (trim($s)=='' ? '&nbsp;' :
	   htmlspecialchars(html_entity_decode($s))) . 
	  "</li>\n";
    }
    $r .= "</ul>\n";
    $r .= "<input type='hidden' id='usercode$id' name='usercode$id'/>\n";
  } else {
    //    $r .= "<div class='acecontain ace_hide' id='acecontain$id' ><div class='aceinner' id='ace$id'></div></div>";
    
    $px = $rows*26+6; //+6 for border, padding in weird box model
    $h = $px;
    if (!$readonly) $h = max(50, $h);
    $h = " style='height: {$h}px;'";
    $ro = $readonly?"readonly='readonly'":"";
    $c = $readonly?"RO":"RW";
    $p = $readonly?" style = 'height : {$px}px;' ":"";
    $s = $readonly?"":"resizy";

    //cols=... required for valid html but width actually set by css
    //height is set explicitly since it's the only way for IE to render the textarea at the correct height
    $pl = (array_key_exists("placeholder", $options)) ? ("placeholder='" . $options['placeholder'] . "'") : "";

    $r .= "<div class='pyboxTextwrap pyboxCodewrap $c $s' $h><textarea wrap='off' name='usercode$id' id='usercode$id' $pl cols=10 rows=$rows $ro $p class='pyboxCode $c'>\n";
    $r .= $thecode;
    $r .= '</textarea></div>'."\n";
  }


  // part 2.5 history container

  $r .= "<div id='pbhistory$id' class='flexcontain' style='display:none;'></div>\n";
  
  /// part 3: stdin
  
  if ($allowinput) {
    if ($usertni === TRUE)
      $description = 
	__t('Enter testing statements like <tt>print(myfunction("test argument"))</tt> below.');
    else
      $description =
	__t("You may enter input for the program in the box below.");

    $r .= '<div name="pyinput" id="pyinput'.$id.'">';
    $r .= $description;
    $r .= '<div class="pyboxTextwrap resizy" style="height: 102px;" ><textarea wrap="off" name="userinput" class="pyboxInput" cols=10 rows=4></textarea></div>';
    $r .= '</div>'."\n"; 
    //cols=10 required for valid html but width actually set by css
  }

  /// part 4: controls

  $tni = $usertni?'Y':'N';
  $actions = array();
  if ($allowinput) {
    $actions['switch'] = array('id'=>"switch$id", 'value'=>'Input Switch', 'onclick'=>"pbInputSwitch($id,'$tni')");
  }
  if (!$disablericheditor) {
    //    $userLikesRich = (!is_user_logged_in()) || ("true"!==get_the_author_meta( 'pbplain', get_current_user_id()));
    $userLikesRich = TRUE;
    if ($showEditorToggle || $richreadonly)
      $actions['CMtoggle'] = array('value'=>__t('Rich editor'), 
				   'id'=>"toggleCM$id", 'onclick'=>"pbToggleCodeMirror($id)");
    if ($userLikesRich)
      $readyScripts .= "jQuery(function(){pbToggleCodeMirror($id);});";
  }
  if (!$scramble && ($lessonNumber >= 4 || $lessonNumber < 0)) {
    $actions['consolecopy'] = array('value'=>__t('Open in console'), 'onclick'=>"pbConsoleCopy($id)");
  }
  if (!$scramble && ($lessonNumber >= 4 || $lessonNumber < 0)) {
    $actions['visualize'] = array('value'=>__t('Visualize'), 'onclick'=>"pbVisualize($id,'$tni')");
  }
  if (!$readonly && !$scramble) {
    //$actions['save'] = array('value'=>'Save without running', 'onclick'=>"pbSave($id)");
    if (array_key_exists("slug", $options)) {
      $historyAction = "historyClick($id,'$slug')";
      $actions['history'] = array('value'=>__t('History'), 'onclick'=>$historyAction);
    }
    if( !($readonly === "Y") && ($defaultcode != '') && ($defaultcode !== FALSE)) {
      // prepare the string for javaScript enclosure
      // we put in single-quotes, rather than json's default double quotes, for compatibility with
      // our $button usage
      $dc = substr(json_encode(htmlspecialchars_decode($defaultcode, ENT_QUOTES), JSON_HEX_APOS), 1, -1);
      $r .= "<input type='hidden' id='defaultCode$id' value='$dc'></input>\n";
      $actions['default'] = array('value'=>__t('Reset code to default'), 'onclick'=>"pbSetText($id,descape($('#defaultCode$id').val()))", );
    }
  }

  if (!$facultative && !$scramble) {
    $actions['help'] = array('value'=>__t('Help'), 'onclick'=>"helpClick($id);");
  }


  if ( $richreadonly )
    $actions = array('CMtoggle' => $actions['CMtoggle']); // get rid of all other options

  if ( $enableDebugFeatures ) 
    $actions['hashview'] = array('value'=>'Hash (debug)', 'onclick'=>"window.open('".UDEBUGPHP."?hash=$hash')");
  $r .= "<div class='pyboxbuttons'><table><tr>\n";
  if (!$richreadonly)
    $r .= "<td><input type='submit' name='submit' id='submit$id' value=' '/></td>\n";
  $mb = 3; //maximum number of buttons, not counting 'submit'
  $i=0;
  foreach ($actions as $name => $atts) {
    $i++;
    if ($i<=$mb) {
      $r .= button($name, $atts);
      continue;
    }
    if ($i==1+$mb) {
      $r .= "</tr></table><select id='pbSelect$id' class='selectmore'><option name='more'>".__t("More actions...")."</option>\n";
    } 
    $r .= option($name, $atts);
  }
  if (count($actions)>$mb) {
    $r .= "</select></div>\n";
  }
  else
    $r .= "</tr></table></div>\n";

  if (isset($cpulimit) && $cpulimit != 1) {
    $timeout = (WALLFACTOR*$cpulimit+WALLBUFFER+2)*1000; // + 2 seconds for network latency each way
    $r .= "<input type='hidden' name='timeout' value='$timeout'/>\n";
  }

  $r .= '<input type="hidden" id="inputInUse'.$id.'" name="inputInUse" value="Y"/>'."\n";
  $r .= '<input type="hidden" name="pyId" value="'. $id . '"/>'."\n";
  $r .= '<input type="hidden" name="hash" value="'. $hash . '"/>'."\n";
  // although inputInUse starts as Y, the next script sets it to N and fixes the button labels
  $readyScripts .= $allowinput?
    ('pbInputSwitch(' . $id . ',"' . ($usertni?'Y':'N') . '");'):
    'document.getElementById("submit' . $id . '").value = "'.__t('Run program').'";' .
    'document.getElementById("inputInUse' . $id . '").value = "N";';

  /// part 5 : results area, and footers

  $c = (count($actions)>$mb)?' avoidline':'';
  $r .= "<div id='pbresults$id' class='pbresults$c'></div>\n";

  $r .= '</div>' . "\n";
  $r .= '</form>'."\n";	
  if ($readyScripts != '')
    $r .= "<script type='text/javascript'>$readyScripts</script>\n";

  return $r;
  
}


// end of file