<?php

require_once("include-to-load-wp.php");

function quote_it($s) {
  if (substr_count($s, '"') > substr_count($s, "'"))
    return "'" . addcslashes($s, "'") . "'";
  else
    return '"' . addcslashes($s, '"') . '"';
}

global $wpdb;

$row = NULL;
if (getSoft($_GET, "hash", "")!="") {  
  $row = $wpdb->get_row($wpdb->prepare("SELECT type, shortcodeArgs, content, postid, url FROM "
                                       .$wpdb->prefix."pb_problems WHERE hash = %s", 
                                       $_GET["hash"])
                        , ARRAY_A);
 }
 else if (getSoft($_GET, "slug", "")!="") {
  $row = $wpdb->get_row($wpdb->prepare("SELECT type, shortcodeArgs, content, postid, url FROM "
                                       .$wpdb->prefix."pb_problems WHERE slug = %s AND lang = %s", 
                                       $_GET["slug"],
                                       getSoft($_GET, "lang", "en"))
                        , ARRAY_A);
 }

if ($row == NULL)
  {echo "Invalid problem"; return; }

$args = json_decode($row['shortcodeArgs'], true);

$r = "";
$r .= '[';

if ($row['type'] == 'short answer') $codename = 'pyShort';
 else if ($row['type'] == 'multiple choice') $codename = 'pyMulti';
 else if ($row['type'] == 'multichoice scramble') $codename = 'pyMultiScramble';
 else if ($row['type'] == 'scramble') {$codename = 'pyScramble'; unset($args['scramble']);}
 else if ($row['type'] == 'code') {
   if (isSoft($args, 'pyexample', 'Y'))
     {$codename = 'pyExample'; unset($args['pyexample']);}
   else
     $codename = 'pyBox';
 }
 else $codename = 'py??'. $row['type'] . '??';

$r .= $codename;

foreach ($args as $field => $value) {
  $r .= "\n";
  $r .= $field;
  $r .= '=';
  if (substr($field, 0, 6) == 'solver' || substr($field, 0, 6) == 'answer'
      || substr($field, 0, 5) == 'right' || substr($field, 0, 5) == 'wrong') 
    $r .= "REDACTED";
  else
    $r .= embed_atfile_links(quote_it(htmlspecialchars($value, ENT_NOQUOTES)));
}
$r .= "\n]\n";
$r .= htmlspecialchars($row['content'], ENT_NOQUOTES);
$r .= "\n[/$codename]";

$page_src_url = UPAGESOURCE.'?'.http_build_query(array("page"=>$row['postid']));
$content = get_post($row['postid'])->post_content;
$found = preg_match("_\[authorship.*info=\\s*(.*)/(authorship)?\]_s", $content, $match);
if ($found > 0)
  $authorshipmsg = 'The <a href="'.$page_src_url.'">containing page source</a> has authorship info: <i>'.$match[1].'</i>';
 else
   $authorshipmsg = 'See also the <a href="'.$page_src_url.'">containing page source</a>.';

?>
<html>
The following shortcode is a definition for the example or exercise that you clicked on.
<br>
<?php echo open_source_preamble(); ?>
<br>
<?php echo $authorshipmsg; ?>
<hr>
<pre style="white-space:pre-wrap"><?php echo $r ?></pre>
<hr>
Click <a href="<?php echo $row['url']; ?>">here</a> to view this shortcode in action.
</html>
