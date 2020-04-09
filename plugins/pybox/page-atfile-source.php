<?php

require_once("include-to-load-wp.php");

$get = softSafeDereference("@file:" . $_GET["file"]);

if (!array_key_exists("file", $_GET) || $get == "@file:" . $_GET["file"]) {
  echo '<html>';
  echo 'Not a valid <code>@file</code> reference, or does not exist: <code>' . htmlspecialchars($_GET["file"]) . '</code>';
  echo '</html>';
  return;
 }

?>
<html>
Here are the contents of <code>wp-content/lesson_files/<?php echo $_GET["file"]; ?></code>
<br>
<?php echo open_source_preamble(); ?>
<hr>
<pre style="white-space:pre-wrap"><?php
echo htmlspecialchars(softSafeDereference("@file:" . $_GET["file"]));
?></pre>
</html>
