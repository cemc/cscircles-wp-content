<?php

/*

This file exposes part of the mail database to the user via flexigrid.

The present file accepts POST queries with the following arguments:

- who : a user id number, and we seek all messages about this user.
- xwho : we seek all messages not about this user. cannot be used with who
- what : a problem slug/name, and we seek all messages about this problem.
- xwhat : we seek all messages not about this problem. cannot be used with what
- unans : find only unanswered messages (1) or answered messages (0)

Additionally, we filter by security: the presently logged-in user can
only view messages that are to/from themselves and their students
(admins can view everything).

*/

function xname($uid) {
  if ($uid === 0 && userIsAdmin() || $uid === getUserID()) 
    return 'me';
  elseif ($uid == 0)
    return 'TA';
  else
    return get_userdata($uid)->user_login;
}

function dbMail($limit, $sortname, $sortorder, &$info, $req = NULL) {
  $who = getSoft(($req==NULL?$_REQUEST:$req), "who", "");
  $xwho = getSoft(($req==NULL?$_REQUEST:$req), "xwho", "");
  $what = getSoft(($req==NULL?$_REQUEST:$req), "what", "");
  $xwhat = getSoft(($req==NULL?$_REQUEST:$req), "xwhat", "");
  $unans = getSoft(($req==NULL?$_REQUEST:$req), "unans", "");

   $info['type'] = 'mail-history';
   $info['who'] = $who;
   $info['xwho'] = $xwho;
   $info['what'] = $what;
   $info['xwhat'] = $xwhat;
   $info['unans'] = $unans;

   if ( !is_user_logged_in() )
     return "You must log in to view past mail.";

   $where = 'WHERE 1';

   if (!userIsAdmin()) {
     $students = getStudents();
     $students[] = getUserID();
     $where .= ' AND ustudent IN ('.implode(',', $students).')';
   }

   if ($who != '') {
     if (!is_numeric($who))
       return "'who' must be numeric.";
     $who = (int)$who;
     if (userIsAdmin() || getUserID() == $who || getUserID() == guruIDID(getUserID()))
       $where .= ' AND ustudent = '.$who;
     else
       return "Access denied.";
   }
   else if ($xwho != '') {
     if (!is_numeric($xwho))
       return "'xwho' must be numeric.";
     $xwho = (int)$xwho;
     $where .= ' AND ustudent != '.$xwho;
   }

   if ($unans != '') {
     if (!is_numeric($unans))
       return "'unans' must be numeric.";
     $unans = (int)$unans;
     $where .= ' AND unanswered = '.$unans;
   }

   global $wpdb;   

   if ($what != '') 
     $where .= $wpdb->prepare(' AND problem = %s', $what);
   if ($xwhat != '') 
     $where .= $wpdb->prepare(' AND problem != %s', $xwhat);
   
   $table_name = $wpdb->prefix . "pb_mail";

   $knownFields = array("from"=>"ufrom", "to"=>"uto", 
			"when"=>"time", "message"=>"body",
			"problem"=>"problem");

   $sortString = (array_key_exists($sortname, $knownFields)) ?
     ($knownFields[$sortname] . " " . $sortorder . ", ") : "";

   $count = $wpdb->get_var("SELECT COUNT(1) from $table_name $where");
   $prep = $wpdb->prepare("SELECT * from $table_name $where ORDER BY $sortString ID DESC" . $limit);

   $flexirows = array();
   foreach ($wpdb->get_results( $prep, ARRAY_A ) as $r) {
     $cell = array();
     $cell['from'] = xname($r['ufrom']);
     $cell['to'] = xname($r['uto']);
     $url =  UMAIL . "?who=".$r['ustudent']."&what=".$r['problem']."&which=".$r['ID']."#m\n";
     $cell['message'] = "<a href='$url'>".preBox($r['body'])."</a>";
     $cell['when'] = $r['time'];
     if ($what=='')
       $cell['problem'] = $r['problem'];
     $flexirows[] = array('id'=>$r['ID'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
}


// only do this if calld directly
if(strpos($_SERVER["SCRIPT_FILENAME"], '/db-mail.php')!=FALSE) {
  require_once("db-include.php");
  echo dbFlexigrid('dbMail');
 }

// paranoid against newline error
