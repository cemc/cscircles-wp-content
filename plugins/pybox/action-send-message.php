<?php

require_once("include-me-and-load-wp.php");

function send($problem_info, $from, $to, $student, $slug, $body) {

  global $wpdb, $current_user;

  $unanswered = (getUserID() == $student) ? 1 : 0;

  if (getUserID() != $student) 
    $wpdb->update('wp_pb_mail',
		  array('unanswered' => 0),
		  array('unanswered' => 1, 'ustudent' => $student, 'problem' => $slug));
  
  $wpdb->insert('wp_pb_mail', 
		array('ufrom' => $from, 'uto' => $to, 'ustudent' => $student, 'problem' => $slug, 'body' => $body, 
		      'unanswered' => $unanswered), 
		array('%d','%d','%d','%s','%s', '%d'));
  $mailref = $wpdb->insert_id;

  if (userIsAdmin())
    $header_from = __t('From:').' '. __t("CS Circles Assistant") . '<'.CSCIRCLES_ASST_EMAIL.'>';
  else 
    $header_from = __t('From:'). '"' . $current_user->user_nicename . '" <' . $current_user->user_email . '>';

  $subject = __t('CS Circles') .' - '. __t('message about') . ' ' . $problem_info['publicname'];
  
  $contents = $body."\n===\n";
  $contents .= __t("To send a reply message, please visit")."\n";
  $contents .= cscurl('mail') . "?who=$student&what=$slug&which=$mailref#m\n";
  $contents .= __t("Problem URL:")." " . $problem_info['url'] . "\n";
  $contents .= "[".__t("Sent by CS Circles")." ".cscurl("homepage")."]";
  
  if ($to == 0) {
    wp_mail(CSCIRCLES_ASST_EMAIL, $subject, $contents, $header_from);
    if (get_the_author_meta('pbnocc', getUserID())!='true')
      wp_mail($current_user->user_email, __t("SENT:")." " . $subject, __t("THIS IS A COPY of a message you sent to the CS Circles Assistant.") ."\n\n" . $contents, $header_from);
  }
  else {
    wp_mail(get_user_by('id', $to)->user_email, $subject, $contents, $header_from);
    if (get_the_author_meta('pbnocc', getUserID())!='true')
      wp_mail($current_user->user_email, "SENT: " . $subject, __t("THIS IS A COPY of a message you sent to ").get_user_by('id',$to)->user_login.".\n\n" . $contents, $header_from);
  }
  return $mailref;
}


$slug = $_POST["slug"];
$source = $_POST["source"];

$user = getUserID();

if ($user < 0) {
  header('HTTP/1.1 401 Unauthorized');
  return;
 }

global $current_user;
get_currentuserinfo();
$user_email = $current_user->user_email;

global $wpdb;
$problem_info = $wpdb->get_row($wpdb->prepare('SELECT * from wp_pb_problems where slug = %s', $slug), ARRAY_A);

if ($problem_info === NULL) {
  header('HTTP/1.1 404 Not Found');
  return;  
 }

$message = stripcslashes($_POST["message"]);

if ($source == 1) {
  $guru_login = get_the_author_meta('pbguru', get_current_user_id()); // '' if does not exist
  $guru = get_user_by('login', $guru_login);                          // FALSE if does not exist

  $code = stripcslashes($_POST["code"]);
  
  $message .= "\n===\n".__t("The user sent this code with the message:")."\n===\n" . $code;

  echo send($problem_info, getUserID(), isSoft($_POST, 'recipient', '1') ? $guru->ID : 0, getUserID(), $slug, $message);
 }
elseif ($source == 2) {
  $id = $_POST['id'];  
  $guru_login = get_the_author_meta('pbguru', $id); // '' if does not exist
  $guru = get_user_by('login', $guru_login);        // FALSE if does not exist
  if (userIsAdmin() || getUserID() == $guru->ID) {
    // from {guru or CSC Asst.} to student
    echo send($problem_info, userIsAdmin()?0:getUserID(), $id, $id, $slug, $message);
  }
  elseif ($id == getUserID()) {
    // from student to {guru or CSC Asst.}
    echo send($problem_info, $id, isSoft($_POST, 'recipient', '1') ? $guru->ID : 0, $id, $slug, $message);
  }
  
}
// end of file!
