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
   $info['type'] = 'problem-history';

   $problemname = getSoft($_POST, "p", ""); //which problem?
   $user = getSoft($_POST, "user", "");   
   if ($problemname=="")
     return "You must enter a non-empty problem name.";
   $info['problem'] = $problemname;

   $resultdesc = array('y'=> 'Did not crash.', 
		       'Y'=> 'Correct!', 
		       'N'=> 'Incorrect.', 
		       'E'=> 'Internal error.', 
		       'S'=> 'Saved.',
		       's'=> 'Saved.');

   if ( !is_user_logged_in() )
     return "You must log in to view past submissions.";
   
   if ( userIsAdmin() && $user != "") {
     $u = get_userdata($user);
     if ($u === false) 
       return "User number $u not found.";
     $info['viewuser'] = $user;
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
     return "We do not have record of any submissions from user $uid '$uname' for problem $problemname.";
   

   $knownFields = array("time &amp; ID"=>"beginstamp", "user code"=>"usercode", 
			"user input"=>"userinput", "result"=>"result");

   if (array_key_exists($sortname, $knownFields)) {
     $sortString = $knownFields[$sortname] . " " . $sortorder . ", ";
   }
   else $sortString = "";

   $prep = $wpdb->prepare("SELECT ID, beginstamp, usercode, userinput, result from $table_name
WHERE userid = %d AND problem = %s ORDER BY $sortString ID DESC" . $limit, $uid, $problemname);

   $flexirows = array();
   foreach ($wpdb->get_results( $prep, ARRAY_A ) as $r) {
     $cell = array();
     $cell['user code'] = preBox($r['usercode'], -1, -1);
     if ($showInputColumn) 
       $cell['user input'] = $r['userinput'] == NULL ? '<i>n/a</i>' : preBox($r['userinput'], -1, 100000);
     $cell['result'] = $resultdesc[$r['result']];
     $cell['time &amp; ID'] = str_replace(' ', '<br/>', $r['beginstamp']) . '<br/>#' . $r['ID'];
     $flexirows[] = array('id'=>$r['ID'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
 }
 );


// paranoid against newline error
