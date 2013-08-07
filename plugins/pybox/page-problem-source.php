<?php

require_once("include-to-load-wp.php");

global $wpdb;

$row = NULL;
if (getSoft($_GET, "hash", "")!="") {  
  $row = $wpdb->get_row($wpdb->prepare("SELECT type, shortcodeArgs, content FROM "
                                       .$wpdb->prefix."pb_problems WHERE hash = %s", 
                                       $_GET["hash"])
                        , ARRAY_A);
 }
 else if (getSoft($_GET, "slug", "")!="") {
  $row = $wpdb->get_row($wpdb->prepare("SELECT type, shortcodeArgs, content FROM "
                                       .$wpdb->prefix."pb_problems WHERE slug = %s AND lang = %s", 
                                       $_GET["slug"],
                                       getSoft($_GET, "lang", "en"))
                        , ARRAY_A);
 }

if ($row == NULL)
  {echo "Invalid problem"; return; }

$r = "";
$r .= '[';

if ($row['type'] == 'short answer') $codename = 'pyShort';
 else if ($row['type'] == 'multiple choice') $codename = 'pyMulti';
 else if ($row['type'] == 'multichoice scramble') $codename = 'pyMultiScramble';
 else if ($row['type'] == 'scramble') $codename = 'pyBox';
 else if ($row['type'] == 'code') $codename = 'pyBox';
 else $codename = 'py??'. $row['type'] . '??';

$r .= $codename;

foreach (json_decode($row['shortcodeArgs']) as $field => $value) {
  $r .= ' ';
  $r .= $field;
  $r .= '="';
  if (substr($field, 0, 6) == 'solver' || substr($field, 0, 6) == 'answer') 
    $r .= "REDACTED";
  else
    $r .= addcslashes($value, '"' . "'\n");  
  $r .= '"';
}
$r .= "]\n";
$r .= $row['content'];
$r .= "\n[/$codename]";

header("Content-Type: text/plain");
echo $r;

?>
