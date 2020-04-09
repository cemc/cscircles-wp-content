<?php

require_once("db-include.php");

/* 
inner function returns either a string in case of error,
or an array-pair (total, array of (id, cell) array-pairs),
where each cell is an array representing a row.
*/

echo dbFlexigrid
(
 function ($limit, $sortname, $sortorder) {
  global $db_query_info;
  $db_query_info = array();
   $db_query_info['type'] = 'profiling';

   if ( !userIsAdmin() )
     return "Error";
   global $wpdb;

   $L = getSoft($_POST, 'frequency', '');
   if (!is_numeric($L) || $L <= 0) 
     $L = 10;

   $A = getSoft($_POST, 'activity', '');
   if ($A=='all'||$A=='') $where = "";
   else if ($A=='noDatabases') $where = "WHERE activity NOT LIKE 'database%'";
   else if ($A=='databases') $where = "WHERE activity LIKE 'database%'";
   else $where = $wpdb->prepare("WHERE activity LIKE %s", $A);
 
   $sortString = (trim($sortname) == "")? "":" $sortname $sortorder ";

   $count = $wpdb->get_var("
SELECT count(distinct activity, substring(start, 1, $L))
FROM ".$wpdb->prefix."pb_profiling
$where");

   $query = "
SELECT substring(start, 1, $L) as period, count(1), sum(duration), activity
FROM ".$wpdb->prefix."pb_profiling
$where
GROUP BY activity, period
ORDER BY $sortString period DESC
$limit";

   $id = 0;
   $flexirows = array();
   foreach ($wpdb->get_results( $query, ARRAY_A ) as $r) {
     $cell = array();
     foreach ($r as $i => $v) {
       $cell[$i] = $v;
     }
     $flexirows[] = array('id'=>$r['period'].$r['activity'], 'cell'=>$cell);
   }
   return array('total' => $count, 'rows' => $flexirows);
 }
 );

// paranoid against newline error

