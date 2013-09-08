<?php

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

   $count = $wpdb->get_var
     (userIsAdmin() ?
      ("SELECT count(1) FROM $user_table")
      : $wpdb->prepare
      ("SELECT count(1) FROM $usermeta_table WHERE meta_key=%s AND meta_value=%s", 'pbguru', $ulogin));

   $students = $wpdb->get_results
     (userIsAdmin() ?
      ("SELECT ID FROM $user_table $limit")
      : $wpdb->prepare
      ("SELECT user_id AS ID FROM $usermeta_table WHERE meta_key=%s AND meta_value=%s $limit", 'pbguru', $ulogin));
   
   // no sorting allowed due to weird nature of query

   $flexirows = array();
   foreach ($students as $r) {
     $sid = $r->ID;
     $sdata = $wpdb->get_row
       ($wpdb->prepare("SELECT usercode, beginstamp FROM $submit_table 
                        WHERE userid=$sid and problem='%s' and result='Y'
                        ORDER BY beginstamp DESC limit 1", $problemslug));
     $s = get_userdata($sid);
     $cell = array();
     $cell['ID'] = $sid;
     $cell['info'] = userString($sid);
     if ($sdata != null) {
       $cell[__t('latest correct')] = prebox($sdata->usercode);
       $cell[__t('last time')] = $sdata->endstamp;
       $cell[__t('first time')] = $wpdb->get_var
	 ($wpdb->prepare("SELECT time FROM $complete_table WHERE userid=$sid and problem='%s'", $problemslug));
     }
     else {
       $cell[__t('latest correct')] = '<i>n/a</i>';
       $cell[__t('last time')] = '<i>n/a</i>';
       $cell[__t('first time')] = '<i>n/a</i>';
     }
     $flexirows[] = array('id'=>$sid, 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
}

// only do this if calld directly
if(strpos($_SERVER["SCRIPT_FILENAME"], '/db-problem-summary.php')!=FALSE) {
  require_once("db-include.php");
  echo dbFlexigrid('dbProblemSummary');
 }

// paranoid against newline error
