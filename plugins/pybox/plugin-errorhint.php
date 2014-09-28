<?php

  /* 
Categorization, enumeration and explanation of errors 
done by Ayomikun (George) Okeowo
as part of a Junior Project at Princeton
University, Fall 2013 
*/

function determineHint($errmsg) {
  if (!hintable($errmsg)) return NULL;
  
  //$errnorm = normalize($errmsg);

  global $hintage;
  foreach ($hintage as $patt => $repl) {
    if (preg_match("\x10" . $patt . "\x10", $errmsg, $matches))
      return preg_replace("\x10" . $patt . "\x10", $repl, $errmsg);
  }

  return NULL;
}

function hintable($errmsg) {
  $hintables = array("RuntimeError", "ValueError",
                     "ZeroDivisionError", "AttributeError",
                     "TabError", "IndentationError", "IndexError",
                     "SyntaxError", "UnboundLocalError", "ImportError",
                     "OverflowError", "TypeError", "NameError",
                     "EOFError");

  $t = substr($errmsg, 0, strpos($errmsg, ":")); 

  return in_array($t, $hintables);
}

function normalize($errmsg) {
  $errmsg = preg_replace("_ '.*'( |$)_", " '' ", $errmsg);
  $errmsg = preg_replace("_\".*\"_", "\"\"", $errmsg);
  $errmsg = preg_replace("_\\d+_", "0", $errmsg);
  $errmsg = preg_replace("_\\b\\w+\\(\\)_", "func()", $errmsg);
  $errmsg = preg_replace("_^TypeError: unsupported operand type\\(s\\) .+\$_",
                         "TypeError: unsupported operand type(s)", $errmsg);
  return $errmsg;
}

// only called from ajax to action-submit, so has $REQUEST["lang"]
if (isSoft($_REQUEST, "lang", "lt_LT"))
  require_once("plugin-errorhint-lt_LT.php");
else
  require_once("plugin-errorhint-en_US.php");

