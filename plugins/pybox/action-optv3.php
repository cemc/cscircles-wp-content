<?php

require_once("include-to-load-wp.php");
foreach ($_REQUEST as $k => $v)
  $_REQUEST[$k] = stripslashes($v);

  // read http request variables, from cgi, then pass them on 
  // as a json dict to python's maketrace in jail

$descriptorspec = array
    (0 => array("pipe", "r"), 
     1 => array("pipe", "w"),// stdout
     2 => array("pipe", "w")
     );

$command = PSAFEEXEC . " --env_vars PY --gid 1000 --uidplus 10000 --cpu 5 --mem 100000 --clock 7 --chroot_dir " . PJAIL . " --exec_dir /static/OnlinePythonTutor/v3/ --exec /bin/python3 -S -u csc_exec.py -";

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