<?php
  // this file is used for debugging purposes, so site admins can quickly see a problem's description

require_once("include-to-load-wp.php");

global $wpdb;
$row = $wpdb->get_results($wpdb->prepare("
SELECT * FROM ".$wpdb->prefix."pb_problems WHERE hash = %s", getSoft($_GET, "hash", "")), ARRAY_A);

foreach ($row[0] as $field => $value) {
  if ($field == 'shortcodeArgs' || $field == 'graderArgs') {
    echo "<b>$field</b><br/>";
    foreach (json_decode($value) as $sf=>$sv) 
      echo '---'.$sf.preBox($sv);
  }
  else echo "<b>$field</b><br/>" . preBox($value);
}

?>
