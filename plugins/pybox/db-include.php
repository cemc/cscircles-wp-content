<?php

  // work with ajax and normally too
if (!defined('ABSPATH')) {
  require_once("include-me-if-ajax.php");
 }

/* 
The generic template for exposing a database query to flexigrid.

dbFlexigrid takes a single function argument with signature

 function ($limit, $sortname, $sortorder, &$info) 

where $limit is a LIMIT XX, YY string for SWL,
$sortname, $sortorder is a column name and order to sort on,
$info['type'] must be defined and is stored for profiling
$info[anything else] can also be defined and will be stored for profiling.

the function returns either a string in case of error,
or an array-pair (total, (array of pairs (id, cell)),
where each cell is an array representing a row... this is the format flexigrid uses.

see db-mail for a reasonably-understandable example of using this framework

*/

function dbFlexigrid($innerFunction, $headers = TRUE) {

  $userid = getUserID();
  $profilingID = beginProfilingEntry(array("activity"=>"database", "userid"=>$userid));

  $page = getSoft($_REQUEST, "page", -1);     //flexigrid required
  $rp = getSoft($_REQUEST, "rp", -1);       //flexigrid required: results per page
  if (!is_numeric($page) || $page <= 0) $page = 1;  
  if (!is_numeric($rp) || $rp < 0) $rp = 1; // $rp == 0 means you just want the count, it is not a real interface with flexigrid
  $sortname = trim(getSoft($_REQUEST, "sortname", NULL));
  if ($sortname == "undefined") $sortname = NULL;
  $sortorder = trim(getSoft($_REQUEST, "sortorder", ""));
  // not yet utilized: sortname, sortorder, qtype, query
  if (strtoupper($sortorder) != "ASC")
    $sortorder = "DESC";
  
  $info = array();
  $result = $innerFunction(" LIMIT " . (($page-1)*$rp) . ", " . $rp . " ", $sortname, $sortorder, $info);

  if ($headers) {
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
    header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
    header("Cache-Control: no-cache, must-revalidate" ); 
    header("Pragma: no-cache" );
    header("Content-type: text/x-json");
  }

  $activity = "database-";
  if (array_key_exists('type', $info)) {
    $activity .= $info['type'];
    unset($info['type']);
  }

  if (is_array($result)) { // success case 
    $result['page'] = $page;
    $info['result'] = 'success';
    endProfilingEntry($profilingID, array("activity"=>$activity, "meta"=>$info));
    return json_encode($result);
  }
  else {
    $info['result'] = 'error';
    endProfilingEntry($profilingID, array("activity"=>$activity, "meta"=>$info));
    return json_encode("Error: ".$result); // failure case; just sending a string.
  }
}

// paranoid against newline error
