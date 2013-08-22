<?php

  // read http request variables, from cgi, then pass them on 
  // as a json dict to python's maketrace in jail

require_once("include-to-load-wp.php");
foreach ($_REQUEST as $k => $v)
  $_REQUEST[$k] = stripslashes($v);

if (!array_key_exists('user_script', $_REQUEST) || !array_key_exists('raw_input_json', $_REQUEST))
  {
    echo "Error, missing inputs";
    return;
  }

$logRow = array(
                'beginstamp' => date( 'Y-m-d H:i:s', time() ),
                'usercode' => $_REQUEST['user_script'],
                'userinput' => $_REQUEST['raw_input_json'],
                'hash' => 'visualizer',
                'postmisc' => '',
                'problem' => 'visualizer', 
                'ipaddress' => ($_SERVER['REMOTE_ADDR']),
                'referer' => ($_SERVER['HTTP_REFERER']),
                'userid' => is_user_logged_in() ? wp_get_current_user()->ID : -1);

global $wpdb;
$table_name = $wpdb->prefix . "pb_submissions";
$wpdb->insert( $table_name, $logRow);

$descriptorspec = array
    (0 => array("pipe", "r"), 
     1 => array("pipe", "w"),// stdout
     2 => array("pipe", "w")
     );

$command = PSAFEEXEC . " --env_vars PY --gid 1000 --uidplus 10000 --cpu 5 --mem 100000 --clock 7 --chroot_dir " . PJAIL . " --exec_dir /static/OnlinePythonTutor3-cemc/ --exec /bin/python3 -S -u csc_exec.py -";

$process = proc_open($command, $descriptorspec, $pipes);

header("Content-type: text/plain; charset=iso-8859-1");
if (is_resource($process)) {

  fwrite($pipes[0], json_encode($_REQUEST));
  fclose($pipes[0]);

  $results = stream_get_contents($pipes[1]);
  fclose($pipes[1]);

  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);

  $return_value = proc_close($process);
  echo $results;
  
  if ($results == '') echo $stderr;
}
else
  echo "Error";