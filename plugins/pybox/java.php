<?php

function java_parse($rawtext) {
  /*********************************************************************
   Procedure to check whether a snippet of Java 7 text represents
   a single line, a properly nested block structure, or none of the above.

   A single line is defined as having no \r\n; outside of comments/quotes.
   This is not the absolute best definition as you could write something like
       do {} while (true)   or   if (x == y++) {} else if (y == z++) {}
   but it does eliminate for-loops and therefore all comma-delimeted
   expression sequences. If you solve an exercise in such a crazy way,
   props to you!

   We also check for a single line with a terminator, meaning a single line
   followed by ; then whitespace and/or comments.

   A properly nested block is defined as having all { and } outside of
   comments/quotes as properly nested.

   Java allows you to write \uABCD where ABCD are hex digits as a
   replacement for any part of your source text, including but not limited
   to comments, quotes, and actual language structures. For example you
   can start a comment with \* and end it with \u002a\ since unicode code
   point 42 is an asterisk. This makes our job harder. The first thing is to 
   take care of this with preprocessing. We'll only deal with true ASCII (code
   points < 128) since all meaningful Java language characters lie in this
   range, and since dealing with higher ones is a pain in PHP.

   Reference for details:
   http://docs.oracle.com/javase/specs/jls/se7/html/index.html
  *******************/

  $regex = "(?<!\\\\)((\\\\\\\\)*)\\\\u+00([0-7][[:xdigit:]])";
  // not a backslash, followed by 2k+1 backslashes, u's, 00, two hex digits
  // nb: backslash duplication both for PHP escaping and regex escaping

  $text = preg_replace_callback("|$regex|", 
				function($match) {
				  return $match[1] . chr(hexdec($match[3]));
				},
				$rawtext);
  
  // states (single, multi-line comment; single, double quote)
  $java = 0; $scomment = 1; $mcomment = 2; $squote = 3; $dquote = 4;
  $state_name = array("java", "scomment", "mcomment", "squote", "dquote");
  // characters
  $bs = "\\"; $sq = "'"; $dq = "\"";
  // outputs
  $oneline = True; $oneline_with_terminator = False;
  $errmsg = "";

  // initialize
  $next = 0;
  $state = $java; 
  $depth = 0;
  $oldstate = -1; // for debugging
  while ($next < strlen($text)) {
    $ch = $text[$next];
    $is_newline = ($ch == "\n" || $ch == "\r");
    $nextch = ($next+1 == strlen($text)) ? "NA" : $text[$next+1];
    $digram = $ch . $nextch;

    /* //for debugging
    //if ($oldstate != $state && $oldstate != -1) 
    echo "[$next $digram $oneline ".$state_name[$state]."]";
    $oldstate = $state;
    // */
    $next++;
    
    if ($state === $java) {
      $is_inline_whitespace = ($ch == "\t") || ($ch == "\f") || ($ch == " ");
      $oneline_with_terminator = $oneline_with_terminator &&
	($is_inline_whitespace || ($digram == "//") || ($digram == "/*"));
      if (($is_newline || $ch == ";") && $oneline) {
	$oneline = False;
	if ($ch == ";")
	  $oneline_with_terminator = True;
      }
      if ($ch == '{') 
	$depth++;
      if ($ch == '}') {
	$depth--;
	if ($depth < 0 && $errmsg == "")
	  $errmsg = "Closing brace (}) not matching any previous ".
	    "opening brace ({).";
      }
      if ($ch == $dq) { 
	$state = $dquote;
      }
      else if ($ch == $sq) {
	$state = $squote;
      }
      else if ($digram == "//") {
	$state = $scomment;
	$next++;
      }
      else if ($digram == "/*") {
	$state = $mcomment;
	$next++;
      }
    }
    else if ($state === $dquote) {
      if ($is_newline && $errmsg == "")
	$errmsg = "String delimeter (\") followed by end of line.";
      if ($digram == $bs.$bs || $digram == $bs.$dq) {
	$next++;
      }
      else if ($ch == $dq) {
	$state = $java;
      }
    }
    else if ($state === $squote) {
      if ($is_newline && $errmsg == "")
	$errmsg = "Character delimeter (\') followed by end of line.";
      if ($digram == $bs.$bs || $digram == $bs.$sq) {
	$next++;
      }
      else if ($ch == $sq) {
	$state = $java;
      }
    }
    else if ($state === $scomment) {
      if ($is_newline) {
	$state = $java;
      }
    }
    else if ($state === $mcomment) {
      if ($digram == "*/") {
	$state = $java;
	$next++;
      }
    }
    // continue parsing the next iteration!
  }

  if ($errmsg == "") {
    if ($state === $squote)
      $errmsg = "Character delimeter (\') followed by end of input.";
    else if ($state === $dquote)
      $errmsg = "String delimeter (\") followed by end of input.";
    else if ($state === $mcomment)
      $errmsg = "Comment delimeter (/*) followed by end of input.";
    else if ($depth > 0)
      $errmsg = "Contains $depth too few close brace (}) characters."; 
  }
  
  $ends_with_scomment = (($errmsg == "") && ($state === $scomment));
  $valid = ($errmsg == "");
  return array("valid" => $valid, "text" => $text, "errmsg" => $errmsg, 
	       "oneline" => $oneline,
	       "oneline_with_terminator" => $oneline_with_terminator, 
	       "ends_with_scomment" => $ends_with_scomment); 
}

add_shortcode
('javaTest', function($options, $content) 
 {
   $r = "";
   foreach (array(
		  "\\u004eow testing. Gives 5 backslashes: \\\\\\u005c\\\\." .
		  " Won't convert: \\\\u0066, \\u0088. New\\uu000aline, " . 
		  "line\\uuu000dfeed. Ta d\u0061\u0021",
		  "a single line",
		  "a single line with newline at end\n",
		  "a single line with cr at end\r",
		  "a single line with tab at end\t",
		  "a single line with semicolon at end;",
		  "a single line with semicolon and tab at end;\t",
		  "a single line with semicolon and newline at end;\n",
		  "two semicolons;;",
		  "quoted semicolon \";\"",
		  "single-quoted semicolon ';'",
		  "commented semicolon /*;*/",
		  "inline-commented semicolon //;",
		  "semicolon followed by comments; /* blah; */ // ",
		  "semicolon followed by quotes; 'yeah'",
		  "good braces {}{}{{{}}}",
		  "too many {{{{s",
		  "balanced but unordered }{",
		  "too many }}}}s",
		  "/* multi-line comment \u000a ends ".
		  "here \\u002a/ while (stuff) {do things;}",
		  "/* this // is \n a valid comment \"'*/",
		  "a very short comment; /**/",
		  "a very short comment /\\u002a\\u002a/",
		  "a quote \"containing \n a newline\"",
		  "a quote '\n' with a newline",
		  "a quote \"\\\\\" with 2 bses",
		  "a quote \"\\\\\\\" with 3 bses",
		  "unmatched \"!",
		  "unmatched '!",
		  "empty quotes '' \"\" ... the next test is an empty string",
		  ""
		  ) 
	    as $test) {
     $result = java_parse($test);
     $r .= "<br/>Test<br/>$test<br/> yields flags ";
     foreach ($result as $key => $value) {
       if ($value === True) 
	 $r .= "[$key] ";
     }
     if ($result["errmsg"] != "")
       $r .= "and error message:<br/>".$result["errmsg"]."<br/>";
     if ($result["text"] == $test) $r .= "<br/>";
     else $r .= "and returns changed text:<br/>".$result["text"]."<br/>";
     //break; // for debugging
   }
   return $r;
 });



