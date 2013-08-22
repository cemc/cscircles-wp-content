<?php

add_sweetcode('pyVis', 'pyVisHandler');

function pyVisHandler($options, $the_content) {

  $width = getSoft($options, "width", 400);

  $res = ' ';
  if (!array_key_exists("code", $options)) return "pyVis error: code='...' not found";
  $content = $options["code"];
  $content = softSafeDereference($content);
  $content = html_entity_decode(str_replace("\xC2\xA0", ' ', str_replace('<br/>', "\n", $content)));
  $content = trim($content);
  $content = trim($content);
  //  for ($i =0; $i<strlen($content); $i++)
  //  $res .= ' ' . ord($content[$i]);
  //return $res;
  $tag = 'iframe'; $urlname = 'src';
  //$tag = 'object'; $urlname = 'data'; // doesn't seem to be different
  $url = content_url('plugins/pybox/') . 'OnlinePythonTutor3-cemc/iframe-embed.html#'
    .'code='.urlencode($content)
    .'&cumulative=false&heapPrimitives=false&drawParentPointers=false&textReferences=false&showOnlyOutputs=false&py=3&curInstr=0&resizeContainer=true&highlightLines&width='.$width;
  return 
    "<$tag width='100%' height='480' frameborder='0' $urlname='$url'></$tag>";
}

