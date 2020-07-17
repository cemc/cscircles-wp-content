<?php

function formatCode($code_or_null) {
  if ($code_or_null) return prebox($code_or_null);
  return '<i>n/a</i>';
}

function formatTime($code_or_null) {
  return $code_or_null ?: '<i>n/a</i>';
}

/* 
inner function returns either a string in case of error,
or an array-pair (total, array of (id, cell) array-pairs),
where each cell is an array representing a row.
*/

function dbProblemSummary($limit, $sortname, $sortorder, $req = NULL) {
  global $db_query_info;
  $db_query_info = array();
  if ($req == NULL) $req = $_REQUEST;
   $db_query_info['type'] = 'problem-summary';

   if ( !is_user_logged_in() )
     return __t("You must log in to view past submissions.");

   $problemslug = getSoft($req, "p", ""); //which problem?

   if ($problemslug=="")
     return __t("You must enter a non-empty problem name.");

   global $wpdb;   
   $problem_table = $wpdb->prefix . "pb_problems";
   $problemname = $wpdb->get_var
     ($wpdb->prepare
      ("SELECT publicname FROM $problem_table WHERE lang = '%s' AND slug = '%s'",
       pll_current_language(), $problemslug));
   
   if ($problemname == null) 
     return sprintf(__t("Problem %s not found (at least in current language)"), $problemslug);

   $db_query_info['problem'] = $problemslug;
   
   $u = wp_get_current_user();
   $uid = $u->ID;
   $db_query_info['viewuser'] = $uid;   
   $ulogin = $u->user_login;

   $submit_table = $wpdb->prefix . "pb_submissions";
   $usermeta_table = $wpdb->prefix . "usermeta";
   $user_table = $wpdb->prefix . "users";
   $complete_table = $wpdb->prefix . "pb_completed";

   $count = count(getStudents());
   $student_list = getStudentList();

   // for testing
   // $count = 10;
   // $student_list = '(1000,1001,1002,1003,1004,1005,1006,1007,1008,1009)';
   
   $knownFields = array(
     "ID"=>"ID",
     "info"=>"ID",
     __t("last correct")=>"lastCode",
     __t("last time")=>"lastTime",
     __t("first correct")=>"firstCode",
     __t("first time")=>"firstTime");

   if (array_key_exists($sortname, $knownFields)) {
     $sortString = $knownFields[$sortname] . " " . $sortorder . ", ";
   } else $sortString = "";

   $prep = $wpdb->prepare("
SELECT users.ID,
       firstCorrect.beginstamp firstTime, firstCorrect.usercode firstCode,
       lastCorrect.beginstamp lastTime, lastCorrect.usercode lastCode
FROM
  (select `ID` from wp_users where `ID` in $student_list) AS users
LEFT JOIN
  (select min(ID) minID, max(ID) maxID, userid
   FROM wp_pb_submissions where userid in $student_list
   AND problem=%s
   AND result='Y'
   GROUP BY userid) AS minmax
ON (minmax.userid=users.ID)
LEFT JOIN
  (select beginstamp, usercode, ID from wp_pb_submissions) as firstCorrect
ON (firstCorrect.ID = minmax.minID)
LEFT JOIN
  (select beginstamp, usercode, ID from wp_pb_submissions) as lastCorrect
ON (lastCorrect.ID = minmax.maxID)
ORDER BY $sortString ID ASC
$limit
", $problemslug);
   $results = $wpdb->get_results($prep, ARRAY_A);

   $flexirows = array();
   foreach ($results as $r) {
     $cell = array();
     $sid = $r['ID'];
     $cell['ID'] = $sid;
     $cell['info'] = userString($sid);
     $na = '<i>n/a</i>';
     $cell[__t('last correct')] = formatCode($r['lastCode']);
     $cell[__t('last time')] = formatTime($r['lastTime']);
     $cell[__t('first correct')] = formatCode($r['firstCode']);
     $cell[__t('first time')] = formatTime($r['firstTime']);
     $flexirows[] = array('id'=>$sid, 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
}

// only do this if called directly
if(strpos($_SERVER["SCRIPT_FILENAME"], '/db-problem-summary.php')!=FALSE) {
  require_once("db-include.php");
  echo dbFlexigrid('dbProblemSummary');
 }

// paranoid against newline error
