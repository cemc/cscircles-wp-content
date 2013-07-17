<?php

add_shortcode('export_submissions', 'export_submissions');

require_once('action-submit-code.php');

function export_submissions($content, $options) {
  $chunkSize = 500;
  $numChunks = 3250; // bigger is ok too i think
  global $wpdb;


  $problem_table = $wpdb->prefix . "pb_problems";
  $problems = $wpdb->get_results
    ("SELECT * FROM $problem_table WHERE ".//"facultative = 0 AND 
"lang = 'en' ORDER BY lesson ASC, boxid ASC", ARRAY_A);
  $problemsByNumber = array();
  foreach ($problems as $prow) 
    $problemsByNumber[$prow['slug']] = $prow;

  file_put_contents("/home/cscircles/export.txt", "");
  $outfile = fopen("/home/cscircles/export.txt", 'w');
  if ($outfile === FALSE) return "could not open file";
  echo strftime('%c');
  
  $last_id = 9999999999;
  
  for ($i=0; $i<$numChunks; $i++) {
  $results = 
    $wpdb->get_results
    ($wpdb->prepare
     ("SELECT problem, usercode, userinput, hash, ID FROM ".$wpdb->prefix."pb_submissions
       WHERE ID < %d 
       ORDER by ID DESC
       LIMIT %d", $last_id, $chunkSize),
     ARRAY_A);
  foreach ($results as $row) {
    set_time_limit(30); // keeps going as long as needed
    $output = array();
    $last_id = $row["ID"];
    $output["id"] = $row["ID"];
    $output["problem"] = $row["problem"];
    $problemInfo = getSoft($problemsByNumber, $row["problem"], NULL);
    if ($problemInfo === NULL) continue; // certainly can't get error msg
    $output["problem_version_mismatch"] = $problemInfo["hash"] != $row["hash"] ? "True":"False";
    $output["just_testing"] = ($row["userinput"] !== NULL) ? "True":"False";
    $output["user_input"] = $row["userinput"] === NULL ? "" : $row["userinput"];
    $output["user_code"] = $row["usercode"];

    $post = array("hash" => $problemInfo["hash"],
		  "pyId" => 0,
		  "usercode0" => $row["usercode"],
		  "userinput" => $row["userinput"],
		  "inputInUse" => $row["userinput"] !== NULL);

    //echo 
    submit_code_main($post, false);
    global $submit_code_stderr, $submit_code_errnice;
    if ($submit_code_stderr == NULL)
      continue; // not interested in it!
    
    $output["raw_errors"] = $submit_code_stderr === NULL ? "" : $submit_code_stderr;
    $output["nice_errors"] = $submit_code_errnice === NULL ? "" : $submit_code_errnice;

    foreach ($output as $k => $v) {
      $maxSize = 1000; // should be even
      $u = $v;
      if (strlen($u) > $maxSize) {
	$u = substr($u, 0, $maxSize/2)
	  ."...("
	  .(strlen($u)-$maxSize)
	  ." characters skipped)..."
	  .substr($u, strlen($u)-$maxSize/2, $maxSize/2);
      }
      fwrite($outfile, "$k: ".addcslashes($u, "\\\n\r")."\n");
      //      if ($k == 'id' and rand(1, 10000)==1) {
      //echo strftime('%c');
      //echo "<pre>";    
      //echo "$k: ".addcslashes($u, "\\\n\r")."\n";
      //echo "</pre>";
      //}
    }
    fwrite($outfile, "\n");
  }
  }
  echo strftime('%c');
  fclose($outfile);
}