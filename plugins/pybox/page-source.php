<?php

require_once("include-to-load-wp.php");

$page = NULL;
if (array_key_exists("page", $_GET)) {
  $page = get_post($_GET["page"]);
 }
 else if (array_key_exists("slug", $_GET)) {
   $page = get_page_by_path($_GET["slug"]);
 }

if ($page == NULL)
  { echo "Page not found"; return; }

$content = $page->post_content;

$content = 
  preg_replace("_(solver|answer|right|wrong)\\s*=\\s*" 
               ."(" . '"'.'(\\\\.|""|[^\\\\"])*'.'"(?!")' 
               ."|" . "'"."(\\\\.|''|[^\\\\'])*"."'(?!')"
               .")_s",
               "$1=REDACTED",
               $content);

?>
<html>
The following is the source code for the page <b><a href="<?php 
echo get_permalink($page->ID); 
?>"><?php echo $page->post_title; ?></a></b> that you clicked on.
<br>
<?php echo open_source_preamble(); ?>
<hr>
<pre style="white-space:pre-wrap"><?php
echo embed_atfile_links(htmlspecialchars($content));
?></pre>
</html>
