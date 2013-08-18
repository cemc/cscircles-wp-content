<?php

/* 
inner function returns either a string in case of error,
or an array-pair (total, array of (id, cell) array-pairs),
where each cell is an array representing a row.
*/

function dbProblemHistory($limit, $sortname, $sortorder, $req = NULL) {
  global $db_query_info;
  $db_query_info = array();
  if ($req == NULL) $req = $_REQUEST;
   $db_query_info['type'] = 'problem-history';

   $problemname = getSoft($req, "p", ""); //which problem?
   $user = getSoft($req, "user", "");   
   if ($problemname=="")
     return __t("You must enter a non-empty problem name.");
   $db_query_info['problem'] = $problemname;

   $resultdesc = array('y'=> __t('Did not crash.'), 
		       'Y'=> __t('Correct!'), 
		       'N'=> __t('Incorrect.'), 
		       'E'=> __t('Internal error.'), 
		       'S'=> __t('Saved.'),
		       's'=> __t('Saved.'));

   if ( !is_user_logged_in() )
     return __t("You must log in to view past submissions.");
   
   if ( (userIsAdmin() || userIsAssistant()) && $user != "") {
     $u = get_userdata($user);
     if ($u === false) 
       return sprintf(__t("User number %s not found."), $u);
     $db_query_info['viewuser'] = $user;
   }
   else
     $u = wp_get_current_user();
   
   $uid = $u->ID;
   $uname = $u->display_name;
   
   global $wpdb;   
   $table_name = $wpdb->prefix . "pb_submissions";

   $counts = $wpdb->get_results
     ($wpdb->prepare("SELECT COUNT(1), COUNT(userinput) from $table_name
WHERE userid = %d AND problem = %s", $uid, $problemname), ARRAY_N);
   
   $count = $counts[0][0];
   $showInputColumn = $counts[0][1] > 0;
   
   if ($count==0) 
     return sprintf(__t('We do not have record of any submissions from user %1$s for problem %2$s.'),
		    $uname . ' (#'.$uid.')',
		    $problemname);
   

   $knownFields = array(__t("time &amp; ID")=>"beginstamp", __t("user code")=>"usercode", 
			__t("user input")=>"userinput", __t("result")=>"result");

   if (array_key_exists($sortname, $knownFields)) {
     $sortString = $knownFields[$sortname] . " " . $sortorder . ", ";
   }
   else $sortString = "";

   $prep = $wpdb->prepare("SELECT ID, beginstamp, usercode, userinput, result from $table_name
WHERE userid = %d AND problem = %s ORDER BY $sortString ID DESC" . $limit, $uid, $problemname);

   $flexirows = array();
   foreach ($wpdb->get_results( $prep, ARRAY_A ) as $r) {
     $cell = array();
     $cell[__t('user code')] = preBox($r['usercode'], -1, -1);
     if ($showInputColumn) 
       $cell[__t('user input')] = $r['userinput'] === NULL ? '<i>'.__t('n/a').'</i>' : preBox($r['userinput'], -1, 100000);
     $cell[__t('result')] = getSoft($resultdesc, $r['result'], $r['result']);
     $cell[__t('time &amp; ID')] = str_replace(' ', '<br/>', $r['beginstamp']) . '<br/>#' . $r['ID'];
     $flexirows[] = array('id'=>$r['ID'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
}

// only do this if calld directly
if(strpos($_SERVER["SCRIPT_FILENAME"], '/db-problem-history.php')!=FALSE) {
  require_once("db-include.php");
  echo dbFlexigrid('dbProblemHistory');
 }

// paranoid against newline error
