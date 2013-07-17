<?php
  // this file is used for debugging purposes, so site admins can quickly see a problem's description

include_once "../include-me.php";
require_once(PWP_LOADER);

global $wpdb;
$row = $wpdb->get_results($wpdb->prepare("
SELECT * FROM ".$wpdb->prefix."_pb_problems WHERE hash = %s", getSoft($_GET, "hash", "")), ARRAY_A);

foreach ($row[0] as $field => $value) {
  if ($field == 'shortcodeArgs' || $field == 'graderArgs') {
    echo "<b>$field</b><br/>";
    foreach (json_decode($value) as $sf=>$sv) 
      echo '-'.$sf.preBox($sv);
  }
  else echo $field . preBox($value);
}

?>
