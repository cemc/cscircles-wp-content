<?php

require_once("db-include.php");

add_shortcode('pbmailpage', 'pbmailpage');

function validate() {
  if (!array_key_exists('who', $_GET) || !array_key_exists('what', $_GET))
    return array("error", "Mandatory arguments are missing.");
  $s = $_GET['who'];
  $p = $_GET['what'];
  if (!is_numeric($s))
    return array("error", "Student ID must be a number.");
  $s = (int)$s;

  global $wpdb;
  
  $student = get_userdata($s);
  
  if ($student === False)
    return array("error", "No such student exists.");

  if (! (getUserID() == $s || in_array($s, getStudents()) || userIsAdmin() ) )
    return array("error", "Access denied. You may need to log in first.");

  $problem = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_pb_problems WHERE slug like %s", $p), ARRAY_A);

  if ($problem === null)
    return array("error", "No such problem exists.");

  $f = (int)getSoft($_GET, 'which', -1);

  return array("success", array("student"=>$student, "sid"=>$s, "problem"=>$problem, "focus"=>$f));
}

function name($uid) {
  if ($uid === 0 && userIsAdmin() || $uid === getUserID()) 
    return 'me';
  elseif ($uid == 0)
    return 'CS Circles Assistant';
  else
    return get_userdata($uid)->user_login;
}

function pbmailpage($options, $content) {
  if ( !is_user_logged_in() ) 
    return "You must log in order to view the mail page.";

  $v = validate();

  if ($v[0] != 'success') 
    return $v[1]; // error message

  extract($v[1]); // $student, $problem, $focus, $sid

  $r = '';

  global $wpdb;
  
  $finished = $wpdb->get_var($wpdb->prepare("SELECT time FROM wp_pb_completed WHERE userid = %d AND problem = %s",
					    $sid, $problem['slug']));

  $r .= '<h1 style="margin-top:0">Messages about <a href="' . $problem['url'] . '">' . $problem['publicname'] .
    '</a> for ' . userString($sid);

  if ($finished !== NULL)
    $r .= "<img title='".$student->user_login." has completed this problem.' src='".UFILES."checked.png' class='pycheck'/>";
  else
    $r .= "<img title='".$student->user_login." has not completed this problem.' src='".UFILES."icon.png' class='pycheck'/>";

  $r .= '</h1>';

  if ($finished !== NULL)
    $r .= "<p style='font-weight: bold; color: red'>Note: this student completed the problem at $finished</p>";

  $r .= '<i>Click on a message title to toggle the message open or closed.</i>';

  $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_pb_mail WHERE ustudent = %d AND problem = %s ORDER BY ID desc",
						$sid, $problem['slug']), ARRAY_A);

  foreach ($messages as $i=>$message) {
    $c =  ($message['ID']==$focus) ?  " showing" : " hiding";
    $r .= "<div class='collapseContain$c' style='border-radius: 5px;'>";
    $title = "From ".name($message['ufrom']). ' to '.name($message['uto']).' on '.$message['time'];
    if (count($messages)>1 && $i==0) $title = "(newest) " . $title;
    if (count($messages)>1 && $i==count($messages)-1) $title = "(oldest) " . $title;
    $r .= "<div class='collapseHead'><span class='icon'></span>$title</div>";
    $r .= "<div class='collapseBody'><span class='quoth'>Quote and Reply</span>".preBox($message['body']).'</div>';
    $r .= '</div>';
  }

  $to = "";
  if (getUserID() == $sid) {
    $guru_login = get_the_author_meta('pbguru', get_current_user_id());
    $guru = get_user_by('login', $guru_login);                          // FALSE if does not exist
    $to .= '<div style="text-align: center">';
    $to .= 'Send a question by e-mail to: ';
    if ($guru !== FALSE) {
      $to .= "<select class='recipient'><option value='0'>Select one...</option><option value='1'>My guru ($guru_login)</option><option value='-1'>CS Circles Assistant</option></select></div>";
    } 
    else {
      $to .= "<select class='recipient'><option value='0'>Select one...</option><option value='0' disabled='disabled'>My guru (you don't have one)</option><option value='-1'>CS Circles Assistant</option></select>";
      $to .= "<i>(name a guru on <a href=".get_edit_profile_url(get_current_user_id()).">your Profile Page</a>)</i><br/>";
      $to .= '</div>';
    }
  }

  $r .= '<div class="pybox fixsresize mailform" id="mailform">
<div class="pyboxTextwrap">
<textarea name="body" class="resizy" placeholder="Type here to send a reply about this problem" style="width:100%" rows=5></textarea>
</div>
'.$to.'
<button onclick="mailReply('.$sid.',\''.$problem['slug'].'\');">Send this message!</button>
</div>';

  $r .= "<h2>Tools</h2>
<a href='".$problem['url'].'">Original lesson page containing '.$problem['publicname'].'</a> (in new window)'."
<div class='collapseContain hiding'>
<div class='collapseHead'><span class='icon'></span>Problem description for ".$problem['publicname']."</div>
<div class='collapseBody'>".pyBoxHandler(json_decode($problem['shortcodeArgs'], TRUE), $problem['content'])."</div>
</div>";

  if (getUserID()!=$sid)
    $r .= niceFlex('us', name($sid)."'s submissions for ".$problem['publicname'], 
		   UHISTORY, array('user'=>$sid, 'p'=>$problem['slug']));
  $r .= niceFlex('ms', "My previous submissions for ".$problem['publicname'], 
		 UHISTORY, array('p'=>$problem['slug']));

  if (getUserID()!=$sid)
    $r .= niceFlex('omp', "My other messages about ".$problem['publicname'], 
		   UDBMAIL, array('what'=>$problem['slug'], 'xwho'=>$sid));
  
  $r .= niceFlex('oms',   (getUserID()==$sid)?"My messages for other programs":"Messages to/from ".name($sid)." for other problems", 
		 UDBMAIL, array('who'=>$sid, 'xwhat'=>$problem['slug']));
  
  $r .= "<a href='".UPROGRESS.'?user='.$sid."'>".name($sid)."'s progress page</a> (in new window)";

  $r .= '</ul>';

  return $r;
}

function niceFlex($id, $title, $url, $dbparams) {
  return "<div class='collapseContain hiding' id='cc$id'>
<div class='collapseHead' id='ch$id'><span class='icon'></span>$title</div>
<div class='collapseBody' id='cb$id'></div></div>
<script type='text/javascript'>
jQuery('#ch$id').click(function(e) {
  if (0==jQuery('#cb$id .flexigrid').size()) pyflex({'id':'cb$id', 'url':'$url', 'dbparams':".json_encode($dbparams)."});
});
</script>";
}

// end of file