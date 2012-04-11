<?php

require_once("../include-me.php");
require_once(PWP_LOADER);

function main() {

  echo <<<EOT
<!DOCTYPE HTML>
<head>
<style type="text/css">
<!--
    td {border-top: 1px solid black; border-left: 1px solid black;}
-->
</style>
</head>
<body>
EOT;

  $resultdesc = array('y'=> 'Executed without crashing.', 
		      'Y'=> 'Correct!', 
		      'N'=> 'Incorrect.', 
		      'E'=> 'Internal error logged.', 
		      'S'=> '{not executed, just saved}',
		      's'=> '{not executed; save imported from old save system}');

  $problemname = $_GET["p"];

  global $current_user;
  get_currentuserinfo();
  global $wpdb;

  //  echo "This page lets you view all of your past submissions (since 2012/01/20) for any problem. It is currently under development, please <a href='".UCONTACT."'>contact us</a> if anything strange seems to be going on.<br>";

  if ( !is_user_logged_in() ) 
    return "You must log in to view past submissions.";

  $uid = $current_user->ID;
  $uname = $current_user->display_name;
  $table_name = $wpdb->prefix . "pb_submissions";

  $prep = $wpdb->prepare("SELECT beginstamp, usercode, userinput, result from $table_name where userid = %d AND problem = %s ORDER BY beginstamp DESC", $uid, $problemname);

  $rows = $wpdb->get_results( $prep, ARRAY_A );

  if (count($rows)==0) return "We do not have record of any submissions from you, $uname, for problem ".$problemname;

  echo "Found " . count($rows) . " of your submissions for problem <tt>$problemname</tt>:";

  echo "<table><tr><th>Submission time</th><th>Submitted code</th><th>Submitted test input, if any</th><th>Result</th></tr>";
  foreach ($rows as $r) {
    echo "\n<tr>";
    echo "<td style='border-left: none;'>".$r['beginstamp']."</td>";
    echo "<td>".preBox($r['usercode'])."</td>";
    echo "<td>".($r['userinput']==NULL?"<i>n/a</i>":preBox($r['userinput']))."</td>";
    echo "<td>".$resultdesc[$r['result']]."</td></tr>";
  }
  echo "\n</table>\n</body>";

  return;

}

echo main();
?>
