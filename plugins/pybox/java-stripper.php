<?php

include 'java.php';

$handle = fopen('php://stdin', 'r');
$count = 0;
$buffer = '';
while(!feof($handle)) {
  $buffer .= fgets($handle);
  //echo $count++, ": ", $buffer;
 }
fclose($handle);

$jp = java_parse($buffer);

echo $jp["text_nocomments"];