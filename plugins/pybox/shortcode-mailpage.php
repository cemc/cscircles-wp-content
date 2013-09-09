<?php

add_shortcode('pbmailpage', 'pbmailpage');

function validate() {
  /*  if (!array_key_exists('who', $_GET) || !array_key_exists('what', $_GET))
   return array("error", __t("Mandatory arguments are missing."));*/
  $s = getSoft($_GET, 'who', getUserID());
  if ($s === '') $s = getUserID();
  $p = getSoft($_GET, 'what', '');
  if (!is_numeric($s))
    return array("error", __t("Student ID must be a number."));
  $s = (int)$s;
  
  global $wpdb, $mailcond;
  
  $student = get_userdata($s);
  
  if ($student === False)
    return array("error", __t("No such student exists."));
  
  if (! (getUserID() == $s || in_array($s, getStudents()) || userIsAdmin() || userIsAssistant()) )
    return array("error", __t("Access denied. You may need to log in first."));

  if (! (getUserID() == $s || in_array($s, getStudents()) || userIsAdmin()))
    $mailcond = "(uto = ".getUserID()." OR ufrom = ".getUserID().")";
  else // if viewing as foreign-language assistant, only can view problems to/from self
    $mailcond = "1";

  if ($p != '') {
    $problem = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."pb_problems WHERE slug = %s AND lang = %s",
					     $p, pll_current_language()), ARRAY_A);
    
    if ($problem === null)
      return array("error", __t("No such problem exists."));
  }
  
  $f = (int)getSoft($_GET, 'which', -1);
  
  return array("success", array("student"=>$student, "sid"=>$s, "problem"=>($p==''?NULL:$problem), "focus"=>$f));
}

function pbmailpage($options, $content) {
  if ( !is_user_logged_in() ) 
    return __t("You must log in order to view the mail page.");
  
  $v = validate();
  
  if ($v[0] != 'success') 
    return $v[1]; // error message
  
  extract($v[1]); // $student, $problem, $focus, $sid
  
  $name = nicefiedUsername($sid, FALSE);
  
  $r = '';
  
  global $wpdb;
  
  $students = getStudents();
  $cstudents = count($students);
  
  $r .= reselector($students, $cstudents);

  $r .= '<hr style="width:80%;align:center;">';
  
  if ($problem !== NULL) {
    
    $finished = $wpdb->get_var($wpdb->prepare("SELECT time FROM ".$wpdb->prefix."pb_completed WHERE userid = %d AND problem = %s",
					      $sid, $problem['slug']));
    
    $r .= '<div class="history-note">'.
      sprintf(__t('Messages about %1$s for user %2$s'),
	      '<a href="' . $problem['url'] . '">' . $problem['publicname'] .'</a>',
	      userString($sid));
    $r .= '</div>';
    
    if ($finished !== NULL)
      $r .= "<img title='".$student->user_login.__t(" has completed this problem.")."' src='".UFILES."checked.png' class='pycheck'/>";
    else
      $r .= "<img title='".$student->user_login.__t(" has not completed this problem.")."' src='".UFILES."icon.png' class='pycheck'/>";
    
    $r .= '</h1>';
    
    if ($finished !== NULL)
      $r .= "<p style='font-weight: bold; color: red'>".sprintf(__t('Note: this student completed the problem at %s'), $finished)."</p>";
    
    $r .= '<i>'.__t('Click on a message title to toggle the message open or closed.').'</i>';
    
    global $mailcond;
    $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."pb_mail WHERE ustudent = %d AND problem = %s AND $mailcond ORDER BY ID desc",
						  $sid, $problem['slug']), ARRAY_A);
    
    foreach ($messages as $i=>$message) {
      $c =  ($message['ID']==$focus) ?  " showing" : " hiding";
      $idp = ($message['ID']==$focus) ?  " id='m' " : '';
      $r .= "<div $idp class='collapseContain$c' style='border-radius: 5px;'>";
      $title = __t("From")." ".nicefiedUsername($message['ufrom'], FALSE). ' '.__t('to').' '.nicefiedUsername($message['uto'], FALSE).', '.$message['time'];
      if (count($messages)>1 && $i==0) $title .= " ".__t("(newest)");
      if (count($messages)>1 && $i==count($messages)-1) $title .= " " .__t("(oldest)");
      $r .= "<div class='collapseHead'><span class='icon'></span>$title</div>";
      $r .= "<div class='collapseBody'><span class='quoth'>".__t("Quote/<br/>Reply")."</span>".preBox($message['body'], -1,10000,"font-size:12px; line-height:14px; white-space: pre-wrap;").'</div>';
      $r .= '</div>';
    }
    
    $to = "";
    if (getUserID() == $sid) {
      $guru_login = get_the_author_meta('pbguru', get_current_user_id());
      $guru = get_user_by('login', $guru_login);                          // FALSE if does not exist
      $to .= '<div style="text-align: center">';
      $to .= __t('Send a question by e-mail to: ');
      if ($guru !== FALSE) {
	$to .= "<select class='recipient'>
<option value='1'>".__t("My guru")." ($guru_login)</option>
<option value='-1'>".__t("CS Circles Assistant")."</option>
</select></div>";
      } 
      else {
	$to .= "<select class='recipient'>
<option value='-1'>".__t("CS Circles Assistant")."</option>
<option value='0'>".__t("(No guru is specified)")."</option>
</select>";
	$to .= '<br/></div>';
      }
    }
    
    $r .= '<div class="pybox fixsresize mailform" id="mailform">
<div id="bodyarea" class="pyboxTextwrap">
<textarea name="body" class="resizy" placeholder="'.__t('Type here to send a reply about this problem').'" style="width:100%; white-space: pre-wrap; font-size: 11px; line-height:13px" rows=12></textarea>
</div>
'.$to;
    
    if (getUserID() != $sid) 
      $r .= '<input style="position:relative; top:2px" type="checkbox" id="noreply" onclick="toggleVisibility(\'bodyarea\')">'.
	' <label style="font-size:75%" for="noreply">'.__t('Just mark as read without replying').'</label><br>';
    
    $r .= '<button onclick="mailReply('.$sid.',\''.$problem['slug'].'\');">'.__t('Send this message!').'</button>
</div>';
    
    $problemname = $problem['publicname'];

    $r .= '<hr style="width:80%;align:center;">';
    
    if (getUserID() != $sid) 
      $r .= "<div class='history-note'><a href='".cscurl('progress').'?user='.$sid."'>".sprintf(__t("%s's progress page (new window)"), $name)."</a></div>";

$r .= "
<div class='collapseContain hiding'>
<div class='collapseHead'><span class='icon'></span>".__t("Problem description for")." ".$problem['publicname']."</div>
<div class='collapseBody'>".pyBoxHandler(json_decode($problem['shortcodeArgs'], TRUE), $problem['content'])."</div>
</div>";
    
    
    if (getUserID()!=$sid)
      $r .= niceFlex('us', sprintf(__t('%1$s\'s submissions for %2$s'), $name, $problemname),
		     'problem-history', 'dbProblemHistory', array('user'=>$sid, 'p'=>$problem['slug']));
    $r .= niceFlex('ms', sprintf(__t("My previous submissions for %s"), $problemname),
		   'problem-history', 'dbProblemHistory', array('p'=>$problem['slug']));
    
    if (getUserID()!=$sid)
      $r .= niceFlex('omp', sprintf(__t("My other messages about %s"), $problemname),
		     'mail', 'dbMail', array('what'=>$problem['slug'], 'xwho'=>$sid));
    
    $r .= niceFlex('oms',   (getUserID()==$sid)?__t("My messages for other programs"):
		   sprintf(__t("Messages to/from %s for other problems"), $name), 
		   'mail', 'dbMail', array('who'=>$sid, 'xwhat'=>$problem['slug']));
    
  }
  
  if ($cstudents > 0 || userIsAssistant())
    $r .= niceFlex('allstu', sprintf(__t("All messages ever about %s's work"), $name),
		   'mail', 'dbMail', array('who'=>$sid));
  
  $r .= niceFlex('allme', __t("All messages ever to or from me"),
		 'mail', 'dbMail', array());
  
  return $r;
}

function reselector(&$students, $cstudents) {
  
  global $wpdb;
  
  $problem_table = $wpdb->prefix . "pb_problems";
  $problems = $wpdb->get_results
    ("SELECT * FROM $problem_table WHERE facultative = 0 AND lang = '".pll_current_language()."' AND lesson IS NOT NULL ORDER BY lesson ASC, boxid ASC", ARRAY_A);
  $problemsByNumber = array();
  foreach ($problems as $prow) 
    $problemsByNumber[$prow['slug']] = $prow;
  
  $gp = getSoft($_GET, "what", "");
  if ($gp != "" && $gp != "console" && !array_key_exists($gp, $problemsByNumber)) {
    echo sprintf(__t("Problem %s not found (at least in current language)"), $gp);
    return;
  }  
  
  $preamble = 
    "<div class='progress-selector'>
       <form method='get'><table style='border:none'>";
  if ($cstudents > 0 || userIsAssistant()) { // slightly leaky but assistants will want to see progress
    $preamble .= "<tr><td>".sprintf(__t("View mail with one of your students? (you have %s)"), $cstudents).'</td><td>';
    $options = array();
    $options[''] = __t('Me');
    
    if (!userIsAdmin()) {
      foreach ($students as $student) {
        $info = get_userdata($student);
        $options[$info->ID] = userString($info->ID);
      }
    }
    
    if (userIsAdmin()) {
      $preamble .= 'blank: you; "all": all; id#: user (<a href="'.cscurl('allusers').'">list</a>) <input style = "padding:0px;width:60px" type="text" name="user" value="'.getSoft($_REQUEST, 'user', '').'">';
    }
    else {
      $preamble .= optionsHelper($options, 'who');
    }
    $preamble .= "</td></tr>";
  }
  
  $preamble .= "<tr><td>".__t("View mail for another problem?")."</td><td>";
  $options = array();
  $options[''] = 'all problems';
  foreach ($problems as $problem) {
    if ($problem['type'] == 'code')
      $options[$problem['slug']] = $problem['publicname'];
  }
  $preamble .= optionsHelper($options, 'what') . "</td></tr>";;
  
    $preamble .= "</td></tr><tr><td colspan='2' style='text-align:center'><input style='width: 25%' type='submit' value='".__t('Submit')."'/></tr></td></table></form></div>";
  return $preamble;
}

function niceFlex($id, $title, $fileSuffix, $functionName, $dbparams) {
  
  include_once("db-$fileSuffix.php");
  $url = UDBPREFIX . $fileSuffix . ".php";
  $query_result = call_user_func($functionName," limit 0,0", '', '', $dbparams);
  if (is_string($query_result))
    $rows = __t("n/a");
  else
    $rows = $query_result['total'];

  return "<div class='collapseContain hiding' id='cc$id'>
<div class='collapseHead' id='ch$id'><span class='icon'></span>$title ($rows)</div>
<div class='collapseBody' id='cb$id'></div></div>
<script type='text/javascript'>
jQuery('#ch$id').click(function(e) {
  if (0==jQuery('#cb$id .flexigrid').size()) pyflex({'id':'cb$id', 'url':'$url', 'dbparams':".json_encode($dbparams)."});
});
</script>";
}

// end of file