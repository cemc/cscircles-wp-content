<?php

add_shortcode('pyVis', 'pyVisHandler');

function pyVisHandler($options, $content) {
  $res = ' ';
  $content = html_entity_decode(str_replace("\xC2\xA0", ' ', str_replace('<br/>', "\n", $content)));
  $content = trim($content);
  if (substr($content, -3)=='<p>') {
    $content = substr($content, 0, strlen($content)-3);
  }
  $content = trim($content);
  //  for ($i =0; $i<strlen($content); $i++)
  //  $res .= ' ' . ord($content[$i]);
  //return $res;
  if (strlen($content) < 11 || substr($content, 0, 5) != '<pre>' || substr($content, -6) != "</pre>")
    return "Error: interior of pyVis must be <pre><pre>...</pre></pre>Found <pre>".substr($content, 0, 5).' '.substr($content, strlen($content)-6, 6)."</pre>";
  $content = trim(substr($content, 5, strlen($content)-11));
  $tag = 'iframe'; $urlname = 'src';
  //$tag = 'object'; $urlname = 'data'; // doesn't seem to be different
  $url = '../static/OnlinePythonTutor/v3/iframe-embed.html#'
    .'code='.urlencode($content)
    .'&cumulative=false&heapPrimitives=false&drawParentPointers=false&textReferences=false&showOnlyOutputs=false&py=3&curInstr=0&resizeContainer=true&highlightLines';
  return 
    "<$tag width='100%' height='480' frameborder='0' $urlname='$url'></$tag>";
}

