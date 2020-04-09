<?php
header("Content-Type: text/plain"); 


require_once("include-to-load-wp.php");

global $pyRenderCount;
$pyRenderCount = rand(1000, 9999);

echo pyBoxHandler(
             json_decode('{"showonly":"output","grader":"*nograder*","console":"Y","slug":"console","title":"Console","showeditortoggle":"Y","allowinput":"Y"}', TRUE), "An altenative console.");
// end of file!
