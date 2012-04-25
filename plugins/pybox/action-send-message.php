<?php

require_once("include-me.php");
require_once(PWP_LOADER);

$slug = $_POST["slug"];
$recipient = $_POST["recipient"];
$message = stripcslashes($_POST["message"]);
$code = stripcslashes($_POST["code"]);

$user = getUserID();

if ($user < 0) {
  header('HTTP/1.1 401 Unauthorized');
  return;
 }

global $current_user;
$user_email = $current_user->user_email;

global $wpdb;
$problem_info = $wpdb->get_row($wpdb->prepare('SELECT * from wp_pb_problems where slug = %s', $slug), ARRAY_A);

if ($problem_info === NULL) {
  header('HTTP/1.1 404 Not Found');
  return;  
 }

$guruid = 0;
if ($recipient == 1) {
  global $wpdb;
  $guru_login = get_the_author_meta('pbguru', get_current_user_id());
  if ($guru_login != '') {
    $guruid = $wpdb->get_var($wpdb->prepare('SELECT ID from wp_users WHERE user_login = %s', $guru_login));
    $guru_email = $wpdb->get_var($wpdb->prepare('SELECT user_email from wp_users WHERE user_login = %s', $guru_login));
  }
  if ($guru_login == '' || $guruid === NULL) 
    $guruid = -1;
 }
// $guruid = -1: error looking up guru... should not happen unless something is broken
// $guruid = 0: email should only be sent to cscircles TA
// $guruid > 0: guru is recipient and this is their id, guru_email is their email

$header_from = 'From: "' . $current_user->user_nicename . '" <' . $current_user->user_email . '>';
$subject = 'CS Circles - question about ' . $problem_info['publicname'];

$contents = $current_user->display_name . " (user #" . $current_user->ID . ") sent you this question about problem '" . $problem_info['publicname'] . "' in Computer Science Circles.\n" .
"Problem description: " . $problem_info['url'] . "\nMessage:\n===\n" . $message . "\n===\nThe user sent this code with the message:\n===\n" . $code . "\n===\n";
$contents .= "Previous submissions (login required):\nhttp://cscircles.cemc.uwaterloo.ca/user-page/?user=$user&problem=" . $problem_info['slug'] . "\n";
$contents .= "[Sent by CS Circles http://cscircles.cemc.uwaterloo.ca]";

if ($guruid > 0) {
  wp_mail($guru_email, $subject, $contents, $header_from);
  wp_mail("cscircles@gmail.com", "COPY: " . $subject, "CARBON COPY ONLY - WAS SENT TO GURU ($guru_email)\n" . $contents, $header_from);
  wp_mail($current_user->user_email, "SENT: " . $subject, "THIS IS A COPY OF A MESSAGE YOU SENT. The real message was sent to your guru ($guru_login).\n\n" . $contents, $header_from);
 }
 else {
  wp_mail("cscircles@gmail.com", $subject, $contents, $header_from);
  wp_mail($current_user->user_email, "SENT: " . $subject, "THIS IS A COPY OF A MESSAGE YOU SENT. The real message was sent to the CS Circles Assistant.\n\n" . $contents, $header_from);
 }

// end of file!
