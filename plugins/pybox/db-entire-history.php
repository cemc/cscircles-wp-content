<?php

require_once("db-include.php");

/* 
inner function returns either a string in case of error,
or an array-pair (total, array of (id, cell) array-pairs),
where each cell is an array representing a row.
*/

echo dbFlexigrid
(
 function ($limit, $sortname, $sortorder, &$info) {
   $info['type'] = 'entire-history';

   $user = getSoft($_POST, "user", "");   
   $problem = getSoft($_POST, "problemhash", "");   

   $resultdesc = array('y'=> 'Did not crash.', 
		       'Y'=> 'Correct!', 
		       'N'=> 'Incorrect.', 
		       'E'=> 'Internal error.', 
		       'S'=> 'Saved.',
		       's'=> 'Saved.');
   
   global $current_user;
   get_currentuserinfo();
   global $wpdb;
   
   if ( !is_user_logged_in() ) 
     return "You must log in to view past submissions.";

   if ($user == "all") {
     $u = "all";
   }
   elseif ($user == "") {
     $u = $current_user;
   }
   elseif ( userIsAdmin() ) {
     $u = get_userdata($user);
     if ($u === false) 
       return "User number not found.";
   }
   else {
     $u = get_userdata($user);
     if ($u === false)
       return "User number not found.";
     if (get_user_meta($user, 'pbguru', true) != $current_user->user_login) {
       return "User $user does not have you as their guru.";
     }
   }

   if ($user != "")
     $info['viewuser'] = $user;

   // make an associative array indexed by slug
   $problemTable = $wpdb->get_results("SELECT slug, publicname, url FROM wp_pb_problems WHERE slug IS NOT NULL", OBJECT_K);

   $whereProblem = "1";
   if ($problem != '') {
     if (!array_key_exists($problem, $problemTable))
       return "Problem $problem is unknown.";
     $whereProblem = $wpdb->prepare("problem = %s", $problem);
   }

   $info['problem'] = $problem;

   $knownFields = array("userid"=>"userid", "time &amp; ID"=>"beginstamp", 
			"user code"=>"usercode", "user input"=>"userinput", "result"=>"result");
   
   if (array_key_exists($sortname, $knownFields)) {
     $sortString = $knownFields[$sortname] . " " . $sortorder . ", ";
   }
   else $sortString = "";

   $whereStudent = NULL;

   if ($u == "all") {
     $whereStudent = userIsAdmin() ? "1" : ("userid in " . getStudentList());
   }
   else {
     $uid = $u->ID;
     $uname = $u->display_name;
     $whereStudent = $wpdb->prepare("userid = %d", $uid);
   }     

   $count = 
     $wpdb->get_var($wpdb->prepare("
SELECT COUNT(1)
FROM wp_pb_submissions 
WHERE $whereStudent AND $whereProblem"));

   if ($count==0) 
     return "We do not have record of any submissions.";

   $prep = $wpdb->prepare("
SELECT userid, ID, beginstamp, usercode, userinput, result, problem
FROM wp_pb_submissions 
WHERE $whereStudent AND $whereProblem
ORDER BY $sortString ID DESC " . $limit);
 
   $flexirows = array();
   foreach ($wpdb->get_results( $prep, ARRAY_A ) as $r) {
     $cell = array();
     if ($u == "all")
       $cell['userid'] = $r['userid'];
     $p = $r['problem'];
     if (array_key_exists($p, $problemTable)) 
       $cell['problem'] = '<a class="open-same-window" href="' . $problemTable[$p]->url . '">'
	 . $problemTable[$p]->publicname . '</a>';
     else
       $cell['problem'] = $p;
     $cell['user code'] = preBox($r['usercode'], -1, -1);
     $cell['user input'] = $r['userinput'] == NULL ? '<i>n/a</i>' : preBox($r['userinput'], -1, 100000);
     $cell['result'] = $resultdesc[$r['result']];
     $cell['time &amp; ID'] = str_replace(' ', '<br/>', $r['beginstamp']) . '<br/>#' . $r['ID'];
     $flexirows[] = array('id'=>$r['ID'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
 }
 );

// paranoid against newline error
