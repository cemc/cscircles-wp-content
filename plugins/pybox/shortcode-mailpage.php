<?php

add_shortcode('pbmailpage', 'pbmailpage');

function validate() {
  if (!array_key_exists('who', $_GET) || !array_key_exists('what', $_GET))
    return array("error", __t("Mandatory arguments are missing."));
  $s = $_GET['who'];
  $p = $_GET['what'];
  if (!is_numeric($s))
    return array("error", __t("Student ID must be a number."));
  $s = (int)$s;

  global $wpdb;
  
  $student = get_userdata($s);
  
  if ($student === False)
    return array("error", __t("No such student exists."));

  if (! (getUserID() == $s || in_array($s, getStudents()) || userIsAdmin() ) )
    return array("error", __t("Access denied. You may need to log in first."));

  $problem = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_pb_problems WHERE slug like %s AND lang like '"
 . pll_current_language() . "'", $p), ARRAY_A);

  if ($problem === null)
    return array("error", __t("No such problem exists."));

  $f = (int)getSoft($_GET, 'which', -1);

  return array("success", array("student"=>$student, "sid"=>$s, "problem"=>$problem, "focus"=>$f));
}

function name($uid) {
  if ($uid === 0 && userIsAdmin() || $uid === getUserID()) 
    return 'me';
  elseif ($uid == 0)
    return __t('CS Circles Assistant');
  else
    return get_userdata($uid)->user_login;
}

function pbmailpage($options, $content) {
  if ( !is_user_logged_in() ) 
    return __t("You must log in order to view the mail page.");

  $v = validate();

  if ($v[0] != 'success') 
    return $v[1]; // error message

  extract($v[1]); // $student, $problem, $focus, $sid

  $r = '';

  global $wpdb;
  
  $finished = $wpdb->get_var($wpdb->prepare("SELECT time FROM wp_pb_completed WHERE userid = %d AND problem = %s",
					    $sid, $problem['slug']));

  $r .= '<h1 id="m" style="margin-top:0">'.
    sprintf(__t('Messages about %1$s for user %2$s'),
	    '<a href="' . $problem['url'] . '">' . $problem['publicname'] .'</a>',
	    userString($sid));

  if ($finished !== NULL)
    $r .= "<img title='".$student->user_login.__t(" has completed this problem.")."' src='".UFILES."checked.png' class='pycheck'/>";
  else
    $r .= "<img title='".$student->user_login.__t(" has not completed this problem.")."' src='".UFILES."icon.png' class='pycheck'/>";

  $r .= '</h1>';

  if ($finished !== NULL)
    $r .= "<p style='font-weight: bold; color: red'>".sprintf(__t('Note: this student completed the problem at %s'), $finished)."</p>";

  $r .= '<i>'.__t('Click on a message title to toggle the message open or closed.').'</i>';

  $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_pb_mail WHERE ustudent = %d AND problem = %s ORDER BY ID desc",
						$sid, $problem['slug']), ARRAY_A);

  foreach ($messages as $i=>$message) {
    $c =  ($message['ID']==$focus) ?  " showing" : " hiding";
    $r .= "<div class='collapseContain$c' style='border-radius: 5px;'>";
    $title = __t("From")." ".name($message['ufrom']). ' '.__t('to').' '.name($message['uto']).', '.$message['time'];
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
      $to .= "<select class='recipient'><option value='0'>".__t("Select one...")."</option><option value='1'>".__t("My guru")." ($guru_login)</option><option value='-1'>".__t("CS Circles Assistant")."</option></select></div>";
    } 
    else {
      $to .= "<select class='recipient'><option value='0'>".__t("Select one...")."</option><option value='0' disabled='disabled'>".__t("My guru (you don't have one)")."</option><option value='-1'>".__t("CS Circles Assistant")."</option></select>";
      $url = get_edit_profile_url(get_current_user_id());
      $to .= "<i>".sprintf(__t("(name a guru on <a href='%s'>your Profile Page</a>)"), $url)."</i><br/>";
      $to .= '</div>';
    }
  }

  $r .= '<div class="pybox fixsresize mailform" id="mailform">
<div class="pyboxTextwrap">
<textarea name="body" class="resizy" placeholder="'.__t('Type here to send a reply about this problem').'" style="width:100%; white-space: pre-wrap; font-size: 11px; line-height:13px" rows=12></textarea>
</div>
'.$to;

  if (getUserID() != $sid) 
    $r .= '<input style="position:relative; top:2px" type="checkbox" id="noreply">'.
      ' <label style="font-size:75%" for="noreply">Just mark as read without replying (reply text is ignored)</label><br>';

  $r .= '<button onclick="mailReply('.$sid.',\''.$problem['slug'].'\');">'.__t('Send this message!').'</button>
</div>';

  $problemname = $problem['publicname'];

  $name = name($sid);
  $r .= "<h2>Tools</h2>";
  $r .= "<a href='".cscurl('progress').'?user='.$sid."'>".sprintf(__t("%s's progress page (new window)"), $name)."</a>";
  $r .= "<br><a href=\"".$problem['url'].'">'.sprintf(__t("Original lesson page containing %s (new window)"), $problemname).'</a>'."
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

  if (getUserID()!=$sid)
    $r .= niceFlex('unread', __t("All unanswered messages by my students"),
		   'mail', 'dbMail', array('unans'=>1));
  

  return $r;
}

function niceFlex($id, $title, $fileSuffix, $functionName, $dbparams) {
  
  include_once("db-$fileSuffix.php");
  $url = UDBPREFIX . $fileSuffix . ".php";
  $bar = array();
  $foo = call_user_func($functionName," limit 0,0", '', '', &$bar, $dbparams);
  $rows = $foo['total'];

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