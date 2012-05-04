<?php

require_once("include-me.php");
require_once(PWP_LOADER);

$message = $_REQUEST["message"];
$millis = $_REQUEST["millis"];
$user_stdin = $_REQUEST["user_stdin"];
$user_script = $_REQUEST["user_script"];

retroProfilingEntry($millis*0.001, array('activity'=>'visualize', 'meta'=>array('message'=>$message, 'user_stdin'=>$user_stdin, 'user_script'=>$user_script)));