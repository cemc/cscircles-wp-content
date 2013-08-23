<?php

function determineHint($errmsg) {
  if (!hintable($errmsg)) return NULL;
  
  //$errnorm = normalize($errmsg);

  global $hintage;
  foreach ($hintage as $patt => $repl) {
    if (preg_match($patt, $errmsg, $matches))
      return preg_replace($patt, $repl, $errmsg);
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

global $hintage;
$hintage = array(
                 "_NameError: name '(.*)' is not defined_" => '
This error indicates that Python tried to look up a variable or function
named <code>$1</code> but it was not defined. If you 
      want to access a variable or function, check spelling, capitalization, and that you defined the 
variable properly. <br> Examples:
<pre>
print(Max(3, 4)) # Max should be max
</pre>
The visualizer can help you determine what variables are defined when the error occurs.<br>
      If you did not mean to access a variable, you might have meant a string, which needs to be 
surrounded by quotation marks.
<pre>
print(Hello) # should be print("Hello")
</pre>
');
