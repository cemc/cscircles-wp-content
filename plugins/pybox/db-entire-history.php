<?php

/* 
inner function returns either a string in case of error,
or an array-pair (total, array of (id, cell) array-pairs),
where each cell is an array representing a row.
*/

function dbEntireHistory($limit, $sortname, $sortorder, $req=NULL) {
  global $db_query_info;
  $db_query_info = array();
   if ($req == NULL) $req = $_REQUEST;
   $db_query_info['type'] = 'entire-history';

   $user = getSoft($req, "user", "");   
   $problem = getSoft($req, "problemhash", "");   

   $resultdesc = array('y'=> __t('Did not crash.'), 
		       'Y'=> __t('Correct!'), 
		       'N'=> __t('Incorrect.'), 
		       'E'=> __t('Internal error.'), 
		       'S'=> __t('Saved.'),
		       's'=> __t('Saved.'));
   
   global $current_user;
   wp_get_current_user();
   global $wpdb;
   
   if ( !is_user_logged_in() ) 
     return __t("You must log in to view past submissions.");

   if ($user == "all") {
     $u = "all";
   }
   elseif ($user == "") {
     $u = $current_user;
   }
   elseif ( userIsAdmin() || userIsAssistant() ) {
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
     $db_query_info['viewuser'] = $user;

   // make an associative array indexed by slug
   
   $problemTable = $wpdb->get_results("SELECT slug, publicname, url FROM ".$wpdb->prefix."pb_problems WHERE slug IS NOT NULL AND lang = '".currLang2() ."'", 
				      OBJECT_K);

   $whereProblem = "1";
   if ($problem != '') {
     if (!array_key_exists($problem, $problemTable))
       return sprintf(__t("Problem %s is unknown."), $problem);
     $whereProblem = $wpdb->prepare("problem = %s", $problem);
   }

   $db_query_info['problem'] = $problem;

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
     $whereStudent = $wpdb->prepare("userid = %d", $uid);
   }     

   $count = 
     $wpdb->get_var("
SELECT COUNT(1)
FROM ".$wpdb->prefix."pb_submissions 
WHERE $whereStudent AND $whereProblem");

   if ($count==0) 
     return __t("We do not have record of any submissions.");

   $prep = "
SELECT userid, ID, beginstamp, usercode, userinput, result, problem
FROM ".$wpdb->prefix."pb_submissions 
WHERE $whereStudent AND $whereProblem
ORDER BY $sortString ID DESC " . $limit;
 
   $flexirows = array();
   foreach ($wpdb->get_results( $prep, ARRAY_A ) as $r) {
     $cell = array();
     if ($u == "all") {
       $cell[__t('userid')] = str_replace(' ', "<br>", userString($r['userid'], true));
     }
     $p = $r['problem'];
     if (array_key_exists($p, $problemTable)) 
       $cell[__t('problem')] = '<a class="open-same-window" href="' . $problemTable[$p]->url . '">'
	 . $problemTable[$p]->publicname . '</a>';
     else
       $cell[__t('problem')] = $p;
     $cell[__t('user code')] = preBox($r['usercode'], -1, -1);
     $cell[__t('user input')] = $r['userinput'] == NULL ? '<i>'.__t('n/a').'</i>' : preBox($r['userinput'], -1, 100000);
     if ($p != 'visualizer' && $p != 'visualizer-iframe')
       $cell[__t('result')] = getSoft($resultdesc, $r['result'], '???');
     else
       $cell[__t('result')] = '<i>n/a</i>';       
     $cell[__t('time &amp; ID')] = str_replace(' ', '<br/>', $r['beginstamp']) . '<br/>#' . $r['ID'];
     $flexirows[] = array('id'=>$r['ID'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
 };

// only do this if calld directly
if(strpos($_SERVER["SCRIPT_FILENAME"], '/db-entire-history.php')!=FALSE) {
  require_once("db-include.php");
  echo dbFlexigrid('dbEntireHistory');
 }

// paranoid against newline error
