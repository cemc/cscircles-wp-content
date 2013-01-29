<?php

add_shortcode('export_submissions', 'export_submissions');

require_once('action-submit-code.php');

function export_submissions($content, $options) {
  if ($options == '') $options = array();
  $lim = getSoft($options, "limit", 20);
  global $wpdb;


  $problem_table = $wpdb->prefix . "pb_problems";
  $problems = $wpdb->get_results
    ("SELECT * FROM $problem_table WHERE ".//"facultative = 0 AND 
"lang = 'en' ORDER BY lesson ASC, boxid ASC", ARRAY_A);
  $problemsByNumber = array();
  foreach ($problems as $prow) 
    $problemsByNumber[$prow['slug']] = $prow;


  $results = 
    $wpdb->get_results
    ($wpdb->prepare
     ("SELECT problem, usercode, userinput, hash, ID FROM wp_pb_submissions
       ORDER by ID DESC
       LIMIT %d", $lim),
     ARRAY_A);
  foreach ($results as $row) {
    $output = array();
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

    echo "<pre>";
    foreach ($output as $k => $v) {
      echo "$k: \"".addcslashes($v, "\\\n\r\'\"")."\"\n";
    }
    echo "</pre>";
  }
}