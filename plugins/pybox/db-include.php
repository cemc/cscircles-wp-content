<?php

require_once("include-me.php");
require_once(PWP_LOADER);

function dbFlexigrid($innerFunction) {

  $userid = getUserID();
  $profilingID = beginProfilingEntry(array("activity"=>"database", "userid"=>$userid));

  $page = getSoft($_POST, "page", -1);     //flexigrid required
  $rp = getSoft($_POST, "rp", -1);       //flexigrid required: results per page
  if (!is_numeric($page) || $page <= 0) $page = 1;  
  if (!is_numeric($rp) || $rp <= 0) $rp = 1;
  $sortname = trim(getSoft($_POST, "sortname", NULL));
  if ($sortname == "undefined") $sortname = NULL;
  $sortorder = trim(getSoft($_POST, "sortorder", ""));
  // not yet utilized: sortname, sortorder, qtype, query
  if (strtoupper($sortorder) != "ASC")
    $sortorder = "DESC";
  
  $info = array();
  $result = $innerFunction(" LIMIT " . (($page-1)*$rp) . ", " . $rp . " ", $sortname, $sortorder, $info);
  // $info is a pass-by-reference arg that should get 'type' and any other useful info like args

  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
  header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
  header("Cache-Control: no-cache, must-revalidate" ); 
  header("Pragma: no-cache" );
  header("Content-type: text/x-json");

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
