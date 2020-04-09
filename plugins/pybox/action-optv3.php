<?php

  // read http request variables, from cgi, then pass them on 
  // as a json dict to python's maketrace in jail

define('MAX_VIS_CACHED_LEN', 30000);

  /************* preliminary stuff *************/
header("Content-type: text/plain; charset=utf8");
require_once("include-to-load-wp.php");
foreach ($_REQUEST as $k => $v)
  $_REQUEST[$k] = stripslashes($v);

if (!array_key_exists('user_script', $_REQUEST))
  {
    echo "Error, missing inputs";
    return;
  }

if (!array_key_exists('raw_input_json', $_REQUEST))
  $_REQUEST['raw_input_json'] = '';

if (strlen(print_r($_REQUEST, TRUE))>POSTLIMIT) {
  pyboxlog("action-optv3.php got too many bytes of data:" 
           . strlen(print_r($_REQUEST, TRUE)));
  return sprintf(__t('Submitted data (program and/or test input) '
             .'too large. Reduce size or <a href = "%s">'
             .'run at home</a>.'), 
                 cscurl('install'));
  }

global $wpdb;

  /************* check for a cached version *************/

$cached_result = NULL;

$basehash = md5($_REQUEST['user_script'] . "\t\t\t" . $_REQUEST['raw_input_json']) . '-viz';
$versionedhash = $basehash . VIZ_VERSION; 

$dontCache = strstr($_REQUEST['user_script'], 'random') !== FALSE;

if (!$dontCache) { // don't cache if randomized
  $the_count = $wpdb->get_var
    ($wpdb->prepare
     ("SELECT count(1) FROM {$wpdb->prefix}pb_submissions
WHERE hash LIKE %s LIMIT 4", $basehash . '%'));
  
  $cached_result = $wpdb->get_var
    ($wpdb->prepare
     ("SELECT result FROM {$wpdb->prefix}pb_submissions
WHERE hash = %s AND result is NOT NULL LIMIT 1", $versionedhash));
  
  // if cached_result is NULL, we still have to compute it anyway

 }

  /************* do logging *************/
// save things to build up the cache count
// save things sent to the real visualizer (not the iframe)
// but once something is iframed 5 times we don't need to log it any more
if ($cached_result === NULL || !isSoft($_REQUEST, "iframe_mode", "Y")) {

  $logRow = array(
                  'beginstamp' => date( 'Y-m-d H:i:s', time() ),
                  'usercode' => $_REQUEST['user_script'],
                  'userinput' => $_REQUEST['raw_input_json'],
                  'hash' => $versionedhash,
                  'problem' => isSoft($_REQUEST, "iframe_mode", "Y") ? 'visualizer-iframe' : 'visualizer', 
                  'ipaddress' => ($_SERVER['REMOTE_ADDR']),
                  'referer' => ($_SERVER['HTTP_REFERER']),
                  'userid' => is_user_logged_in() ? wp_get_current_user()->ID : -1);
  
  $table_name = $wpdb->prefix . "pb_submissions";
  $wpdb->insert( $table_name, $logRow);
  
  $logid = $wpdb->insert_id;
 }

  /************* actually execute the visualizer if necessary *************/

if ($cached_result !== NULL) {
  echo $cached_result;
  exit;
 }

$descriptorspec = array
    (0 => array("pipe", "r"), 
     1 => array("pipe", "w"),// stdout
     2 => array("pipe", "w")
     );

$command = PSAFEEXEC . " --env_vars PY --gid 1000 --uidplus 10000 --cpu 5 --mem 100000 --clock 7 --chroot_dir " . PJAIL . " --exec_dir /static/OnlinePythonTutor3-cemc/ --exec /bin/python3 -S -u csc_exec.py -";

$process = proc_open($command, $descriptorspec, $pipes);

if (is_resource($process)) {

  fwrite($pipes[0], json_encode($_REQUEST));
  fclose($pipes[0]);

  $results = stream_get_contents($pipes[1]);
  fclose($pipes[1]);

  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);

  $return_value = proc_close($process);

  echo $results;
  
  // typical for internal errors
  if ($results == '') echo $stderr;
  else {
    
    if (!$dontCache &&
        $the_count >= 4 && //cache things on the 5th time
        $cached_result == NULL &&
        strlen($results) < MAX_VIS_CACHED_LEN)
      
      $wpdb->update("{$wpdb->prefix}pb_submissions", 
                    array("result" => $results),
                    array("ID" => $logid));

  }
}
else
  echo "Error, could not create the process";