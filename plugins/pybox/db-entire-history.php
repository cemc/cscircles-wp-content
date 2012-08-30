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

   $resultdesc = array('y'=> __t('Did not crash.'), 
		       'Y'=> __t('Correct!'), 
		       'N'=> __t('Incorrect.'), 
		       'E'=> __t('Internal error.'), 
		       'S'=> __t('Saved.'),
		       's'=> __t('Saved.'));
   
   global $current_user;
   get_currentuserinfo();
   global $wpdb;
   
   if ( !is_user_logged_in() ) 
     return __t("You must log in to view past submissions.");

   if ($user == "all") {
     $u = "all";
   }
   elseif ($user == "") {
     $u = $current_user;
   }
   elseif ( userIsAdmin() ) {
     $u = get_userdata($user);
     if ($u === false) 
       return __t("User number not found.");
   }
   else {
     $u = get_userdata($user);
     if ($u === false)
       return __t("User number not found.");
     if (strcasecmp(get_user_meta($user, 'pbguru', true) , $current_user->user_login)!=0) {
       return sprintf(__t("User %s does not have you as their guru."), $user);
     }
   }

   if ($user != "")
     $info['viewuser'] = $user;

   // make an associative array indexed by slug
   $problemTable = $wpdb->get_results("SELECT slug, publicname, url FROM wp_pb_problems WHERE slug IS NOT NULL", OBJECT_K);

   $whereProblem = "1";
   if ($problem != '') {
     if (!array_key_exists($problem, $problemTable))
       return sprintf(__t("Problem %s is unknown."), $problem);
     $whereProblem = $wpdb->prepare("problem = %s", $problem);
   }

   $info['problem'] = $problem;

   $knownFields = array(__t("userid")=>"userid", __t("time &amp; ID")=>"beginstamp", __t("problem")=>"problem",
			__t("user code")=>"usercode", __t("user input")=>"userinput", __t("result")=>"result");
   
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
     return __t("We do not have record of any submissions.");

   $prep = $wpdb->prepare("
SELECT userid, ID, beginstamp, usercode, userinput, result, problem
FROM wp_pb_submissions 
WHERE $whereStudent AND $whereProblem
ORDER BY $sortString ID DESC " . $limit);
 
   $flexirows = array();
   foreach ($wpdb->get_results( $prep, ARRAY_A ) as $r) {
     $cell = array();
     if ($u == "all")
       $cell[__t('userid')] = $r['userid'];
     $p = $r['problem'];
     if (array_key_exists($p, $problemTable)) 
       $cell[__t('problem')] = '<a class="open-same-window" href="' . $problemTable[$p]->url . '">'
	 . $problemTable[$p]->publicname . '</a>';
     else
       $cell[__t('problem')] = $p;
     $cell[__t('user code')] = preBox($r['usercode'], -1, -1);
     $cell[__t('user input')] = $r['userinput'] == NULL ? '<i>'.__t('n/a').'</i>' : preBox($r['userinput'], -1, 100000);
     $cell[__t('result')] = $resultdesc[$r['result']];
     $cell[__t('time &amp; ID')] = str_replace(' ', '<br/>', $r['beginstamp']) . '<br/>#' . $r['ID'];
     $flexirows[] = array('id'=>$r['ID'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
 }
 );

// paranoid against newline error
