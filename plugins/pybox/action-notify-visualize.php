<?php

require_once("include-me.php");
require_once(PWP_LOADER);

$message = $_REQUEST["message"];
$millis = $_REQUEST["millis"];
$user_stdin = $_REQUEST["user_stdin"];
$user_script = $_REQUEST["user_script"];
$error = getSoft($_REQUEST, "error", "");

$meta = array('message'=>$message, 'user_stdin'=>$user_stdin, 'user_script'=>$user_script);

if ($error != "") {
  $meta['error'] = $error;
  pyboxlog("Notified of a visualizer error" . "\n" . $error . "\n" . 
	   $_SERVER['SERVER_PROTOCOL'] . " " . $_SERVER['HTTP_USER_AGENT'] . " " . $_SERVER["REMOTE_ADDR"] . " " . 
 	   getUserID() . "\n" . $user_stdin . "\n" . $user_script);
 }

retroProfilingEntry($millis*0.001, array('activity'=>'visualize', 'meta'=>$meta));