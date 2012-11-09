<?php

  //require_once("db-include.php");

add_shortcode('pyUser', 'pyUser');

function pyUser($options, $content) {
  if ( !is_user_logged_in() ) 
    return __t("You must log in order to view your user page.");
  
  global $wpdb;
  
  $user = wp_get_current_user();
  $uid = $user->ID;

  $students = getStudents();
  $cstudents = count($students);

  $problem_table = $wpdb->prefix . "pb_problems";
  $problems = $wpdb->get_results
    ("SELECT * FROM $problem_table WHERE facultative = 0 AND lang LIKE '".pll_current_language()."' AND lesson IS NOT NULL ORDER BY lesson ASC, boxid ASC", ARRAY_A);
  $problemsByNumber = array();
  foreach ($problems as $prow) 
    $problemsByNumber[$prow['slug']] = $prow;

  $gp = getSoft($_GET, "problem", "");
  if ($gp != "" && $gp != "console" && !array_key_exists($gp, $problemsByNumber)) {
    echo sprintf(__t("Problem %s not found (at least in current language)"), $gp);
    return;
  }

  if (userIsAdmin() || $cstudents>0) {
    $preamble = 
      "<div style='background-color:#EEF; border: 1px solid blue; border-radius: 5px; padding: 5px;'>
       <h1 style='margin-top: 0px;'>".sprintf(__t("Reload with a different view? (you have %s students)"), $cstudents)."</h1>
       <form method='get'>".__t("Select different user?")."<br/>";
    $options = array();
    $options[''] = __t('Show only me');
    $options['all'] = __t('Summary of all my students');
    
    //$preamble .= <option value=''>Show only me</option>
    //     <option value='all'>Summary of all my students</option>";
    if (userIsAdmin()) {
      foreach ($wpdb->get_results("SELECT user_nicename, user_email, ID, display_name FROM wp_users") as $row) 
	$options[$row->ID] = $row->display_name . " (" . $row->user_nicename . " " . $row->user_email . " #" . $row->ID . ")";
    }
    else foreach ($students as $student) {
      $info = get_userdata($student);
      $options[$info->ID] = $info->display_name . " (" . $info->user_nicename . " " . $info->user_email . " #" . $info->ID . ")";
    }
    
    $preamble .= optionsHelper($options, 'user')."<br/>";
    $preamble .= __t("Just show submissions for one problem?")."<br/>";
    $options = array();
    $options[''] = __t('Show all');
    $options['console'] = __t('Console');
    foreach ($problems as $problem) {
      if ($problem['type'] == 'code')
	$options[$problem['slug']] = $problem['publicname'];
    }
    $preamble .= optionsHelper($options, 'problem');

    $preamble .= "<br/><input type='submit' value='".__t('Submit')."'/></form></div>";
    echo $preamble;
  }
  
  $getall = isSoft($_GET, 'user', 'all');

  if (!$getall && array_key_exists('user', $_GET) && $_GET['user'] != '') {
    if (!is_numeric($_GET['user']))
      return __t("User id must be numeric.");
    $getuid = (int)$_GET['user'];
    if (userIsAdmin()) {
      if (get_userdata($getuid) === FALSE)
	return __t("Invalid user id.");
    }
    else {
      if (!in_array($getuid, $students))
	return __t("Invalid user id.");
    }
    $uid = $getuid;
    $user = get_userdata($uid);
    echo "<h1 style='color: red;'>".__t('Viewing as ') . $user->display_name . " (" . $user->user_nicename . " " . $user->user_email . " #" . $uid . ")</h1>";
  }
  if ($getall) {
    echo "<h1 style='color: red;'>".__t("Viewing summary of all your students")."</h1>";
  }

  if ($getall && $gp != "" && $gp != "console") {
    echo niceFlex('perstudent',  sprintf(__t("Solutions by my students for %s"), 
					 $problemsByNumber[$_GET['problem']]['publicname']),
		  'problem-summary', 'dbProblemSummary', array('p'=>$_GET['problem']));
  }

  $completed_table = $wpdb->prefix . "pb_completed";

  if (!$getall) {
    $recent = "";
    $completed = $wpdb->get_results
      ("SELECT * FROM $completed_table WHERE userid = $uid ORDER BY time DESC", ARRAY_A);
    $recent .= '<h2>'.__t("Latest Problems Completed").'</h2>';
    for ($i=0; $i<6 && $i<count($completed); $i++) {
      $p = getSoft($problemsByNumber, $completed[$i]['problem'], FALSE);
      if ($p !== FALSE)
	$recent .= '<a class="open-same-window problem-completed" href="' . $p['url'] 
	  . '" title="'. $completed[$i]['time'] .'">' 
	  . $p['publicname'] . '</a>';
      else
	$recent .= '['.$completed[$i]['problem'].']';
    }
    echo $recent;
  }

  $subs = "";
  $subs = '<h2>'.__t("Submitted Code").'</h2>';
  $subs .= '<div id="recentsubs"></div>';
  $U = UFULLHISTORY;
  $dbparams = array();
  if (getSoft($_GET, 'user', '')!='')
    $dbparams['user'] = $_GET['user'];
  if (getSoft($_GET, 'problem', '')!='')
    $dbparams['problemhash'] = $_GET['problem'];

  $subs .= "<script type='text/javascript'>\npyflex({'id':'recentsubs','url':'$U','dbparams':".json_encode($dbparams)."})\n</script>\n";

  $lessons_table = $wpdb->prefix . "pb_lessons";
  $lessons = $wpdb->get_results
    ("SELECT * FROM $lessons_table WHERE lang LIKE '" . pll_current_language() . "'", ARRAY_A);

  $lessonsByNumber = array();
  foreach ($lessons as $lrow) 
    $lessonsByNumber[$lrow['ordering']] = $lrow;

  $overview = '<h2>'.__t('Overview').'</h2>';

  $didIt = array();
  if ($getall) {
    foreach ($didIt as $index) 
      $didIt[$index] = 0;
    if (userIsAdmin())
      $completed = $wpdb->get_results
	("SELECT count(userid), problem from $completed_table GROUP BY problem", ARRAY_A);
    else {
      $studentList = getStudentList();
      $completed = $wpdb->get_results
	("SELECT count(userid), problem from $completed_table WHERE userid in $studentList GROUP BY problem", ARRAY_A);
    }
    foreach ($completed as $crow) 
      $didIt[$crow['problem']] = $crow['count(userid)'];
  }
  else {
    foreach ($completed as $crow) 
      $didIt[$crow['problem']] = TRUE;
  }

  $overview .= '<table style="width:auto;border:none;margin:0px auto;">';

  $lesson = -1;
  $lrow = NULL;
  $llink = "";
  $firstloop = true;
  foreach ($problems as $prow) {
    if ($prow['lesson'] != $lesson) {
      if (!$firstloop)
	$overview .= "</td></tr>\n";
      $firstloop = false;
      $overview .= "<tr><td class='lessoninfo'>";
      $lesson = $prow['lesson'];
      $lrow = $lessonsByNumber[$lesson];
      $overview .= '<a class="open-same-window" href="';
      $llink = get_page_link($lrow['id']);
      $overview .= $llink;
      $overview .= '">';
      $overview .= $lrow['number'] . ": " . $lrow['title'];
      $overview .= '</a></td><td>';
    }
    $overview .= '<a class="open-same-window" href="' . $llink . '#pybox' . $prow['boxid'] . '">';

    if ($getall) 
      $overview .= '<img title="' . $prow['publicname'] . '" src="' . UFILES . 'icon.png"/>'.
	getSoft($didIt, $prow['slug'], 0).'</a>';
    else
      $overview .= '<img title="' . $prow['publicname'] . '" src="' . UFILES .
	(isSoft($didIt, $prow['slug'], TRUE) ? 'checked' : 'icon') . '.png"/></a>';
  }
  
  $overview .= '</table>';

  return "<div class='userpage'>$subs $overview</div>";

}

// end of file