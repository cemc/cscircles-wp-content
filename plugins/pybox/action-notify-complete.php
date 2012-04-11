<?php

require_once("include-me.php");
require_once(PWP_LOADER);

$problem = $_POST["problem"];
  
global $current_user;
get_currentuserinfo();
global $wpdb;

if ( is_user_logged_in() ) {

  $uid = $current_user->ID;
  $table_name = $wpdb->prefix . "pb_completed";
  $uname = $current_user->user_login;

  $count = $wpdb->get_var($wpdb->prepare("
SELECT COUNT(*) FROM $table_name WHERE userid = %d AND problem = %s",
					 $uid, $problem));

  if ($count == 0) //not previously completed 
    $rows_affected = $wpdb->insert( $table_name, 
				  array( 'userid' => $uid,
					 'problem' => $problem) );
}

// end of file!
