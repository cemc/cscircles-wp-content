<?php

  /*****

Sweetcodes are a modification of WordPress shortcodes. 

What's broke that needs fixing?
===============================
Shortcodes have a limited argument system that makes certain character
combinations impossible to enter. For example, consider one common shortcode
syntax,
 
 [shortcode-name kwarg1=v1 kwarg2=v2 ...] body [/shortcode-name]

{Here shortcode-name is made out of [a-zA-Z0-9_-] (PCRE \w plus hyphen),
each kwarg[i] is made out of a-z0-9_ (PCRE \w minus uppercase),
and each v[i] is a string that is slash-escaped, and optionally quoted.
Extra whitespace is allowed before or after each kwarg[i] or v[i].
The body may also contain one or more nested shortcodes.}

Problem 1. WordPress will not allow any of the values (v1, v2) to contain
a closing square bracket "]" under any circumstances, even if it is quoted.

Problem 2. Each v[i] may either be (a) unquoted but contain no spaces,
(b) single-quoted but contain no single quotes, or (c) double-quoted
but contain no double quotes. This is a pretty weird restriction especially
given that the v[i] strings are slash-escaped (they will be run through 
stripcslashes).

Sweetcodes fix exactly these two problems. 

As a bonus, they also accept Pascal-style "" or '' quote-escapes.

A fix for another issue
=======================
Problem 3. Literal newlines inside of nested shortcodes cause problems with
autop/shortcode_unautop. We accept a flag that will preemtively (before autop)
cause:
- literal newlines in sweetcode bodies are ignored (turned to a space)
 - except:
   - if they are in <pre> they are preserved via conversion to <br/>
   - if they are in a shortcode argument instead of a body, they are preserved

If you find yourself definitely wanting a newline inside of a sweetcode 
body, use instead the CS Circles [br] shortcode that turns into <br>.

Writing sweetcodes in <pre>
===========================
Sweetcodes will also do their level best to ignore <pre> and </pre> located
within the arguments part. This means that you can write code in a code-like
environment. The <pre> must appear after the shortcode name, such as

[pyExample <pre>code="..."</pre>]body[/pyExample]
or
[pyExample code=<pre>"..."</pre>]body[/pyExample]
or
[pyExample code="<pre>...</pre>"]body[/pyExample]

However, be aware that multiple newlines in a row won't work
consistently (see below).

What else is broke? (a rant of warning)
===================
The Wordpress editor, if you switch between visual and text mode a lot,
is not consistent in how it handles spaces. Multiple spaces entered in 
the HTML source code editor will be collapsed when you switch to Visual.
Multiple literal newlines entered inside of a <pre> will be lost
if you switch back and forth enough. Multiple newlines outside of a <pre>
will be lost if entered in HTML and switched, but will cause &nbsp;
to be added if entered in Visual. 

This is worth pointing out only to warn that, even with the sweetcode
modifications, writing long stretches of precise text in a shortcode
can only be done within limits unless you are willing to do some work
on the editor. Note that the cscircles plugin fixes the multiple-space
issue, but not the multiple-literal-newline one.

 *****/

$sweetcode_tags = array();
$sweetcode_flags = array(); //delete all literal newlines from body?

function add_sweetcode($tag, $func, $stripnewlines = false) {
  global $sweetcode_tags;
  global $sweetcode_flags;
  
  if ( is_callable($func) ) {
    $sweetcode_tags[$tag] = $func;
    if ($stripnewlines)
      $sweetcode_flags[] = $tag;
  }
}

function do_sweetcode($content) {
  global $sweetcode_tags;
  
  if (empty($sweetcode_tags) || !is_array($sweetcode_tags))
    return $content;
  
  $pattern = get_sweetcode_regex();
  return preg_replace_callback( "/$pattern/s", 'do_sweetcode_tag', $content );
}

function get_sweetcode_regex($flagged_only = false) {
  global $sweetcode_tags;
  global $sweetcode_flags;
  if ($flagged_only)
    $tagnames = $sweetcode_flags;
  else
    $tagnames = array_keys($sweetcode_tags);
  $tagregexp = join( '|', array_map('preg_quote', $tagnames) );

  return
    '\\['                              // Opening bracket
    . '(\\[?)'                           // 1: Optional second opening bracket for escaping sweetcodes: [[tag]]
    . "($tagregexp)"                     // 2: Sweetcode name
    . '(?![\\w-])'                       // Not followed by word character or hyphen
    . '('                                // 3: Unroll the loop: Inside the opening sweetcode tag
    .   '(?:'
    // the next 4 lines replace 2 original lines
    .        '"(?:\\.|""|[^\\"])*"(?!")' // inside of double-quotes, \. or "" or non-escaped; can't end followed by "
    .       "|'(?:\\.|''|[^\\'])*'(?!')" // inside of single-quotes, \. or '' or non-escaped; can't end followed by '
    .       '|\\/(?!\\])'                // A forward slash not followed by a closing bracket
    .       '|[^\\]\\/\'"]'              // Not a closing bracket or forward slash or quotes
    // end changes!
    .   ')*?'
    . ')'
    . '(?:'
    .     '(\\/)'                        // 4: Self closing tag ...
    .     '\\]'                          // ... and closing bracket
    . '|'
    .     '\\]'                          // Closing bracket
    .     '(?:'
    .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing sweetcode tags
    .             '[^\\[]*+'             // Not an opening bracket
    .             '(?:'
    .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing sweetcode tag
    .                 '[^\\[]*+'         // Not an opening bracket
    .             ')*+'
    .         ')'
    .         '\\[\\/\\2\\]'             // Closing sweetcode tag
    .     ')?'
    . ')'
    . '(\\]?)';                          // 6: Optional second closing brocket for escaping sweetcodes: [[tag]]
}

function do_sweetcode_tag( $m ) {

  global $sweetcode_tags;

  // allow [[foo]] syntax for escaping a tag
  if ( $m[1] == '[' && $m[6] == ']' ) {
    return substr($m[0], 1, -1);
  }

  $tag = $m[2];

  $attr = sweetcode_parse_atts( $m[3] );

  if ( isset( $m[5] ) ) {
    // enclosing tag - extra parameter
    return $m[1] . call_user_func( $sweetcode_tags[$tag], $attr, $m[5], $tag ) . $m[6];
  } else {
    // self-closing tag
    return $m[1] . call_user_func( $sweetcode_tags[$tag], $attr, null,  $tag ) . $m[6];
  }
}

function sweetcode_parse_atts($text) {
  $atts = array();

  $pattern = 
    '/(\w+)\s*=\s*"'.'((?:\\.|""|[^\\"])*)'.'"(?!")(?:\s|$)'  // old:  '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)'
    ."|(\w+)\s*=\s*'"."((?:\\.|''|[^\\'])*)"."'(?!')(?:\s|$)" // old: .'|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)'
    .'|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)'
    .'|"([^"]*)"(?:\s|$)'
    .'|([^ ="\'\s]+)(?:\s|$)/'; // modified

  // we want to allow the user to use these
  $text = str_replace("<pre>", "", $text);
  $text = str_replace("</pre>", "", $text);

  // deal with our methodology for keeping newlines in args only
  $text = str_replace("<!--argOnlyNewline-->", "\n", $text);
  $text = str_replace("<br/>", "\n", $text);
  
  // remove stuff autop might have added
  $text = str_replace("<p>", "", $text);
  $text = str_replace("</p>", "", $text);

  // non-breaking spaces
  $text = str_replace("&nbsp;", " ", $text);
  $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

  if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
    foreach ($match as $m) {
      ///echo '{';
      //foreach ($m as $i=>$v) echo '[' . $i . '=>' . '(' . $v .')],';
      //echo '}';
      if (!empty($m[1]))
        $atts[strtolower($m[1])] = strip_and_unduplicate($m[2], '"');
      elseif (!empty($m[3]))
        $atts[strtolower($m[3])] = strip_and_unduplicate($m[4], "'");
      elseif (!empty($m[5]))
        $atts[strtolower($m[5])] = stripcslashes($m[6]);
      elseif (isset($m[7]) and strlen($m[7]))
        $atts[] = stripcslashes($m[7]);
      elseif (isset($m[8]))
        $atts[] = stripcslashes($m[8]);
    }
  } else {
    $atts = ltrim($text);
  }
  return $atts;
}

function strip_and_unduplicate($text, $q) {
  $r = '';
  $n = strlen($text);
  $a = str_split($text);
  for ($i = 0; $i < $n; $i++) {
    if ($a[$i]=='\\') {
      $a[$i] = '';
      $a[$i+1] = stripcslashes('\\' . $a[$i+1]);
      $i++;
    }
    else if ($a[$i]==$q) { // must be a duplicated $q quote
      $i++;
      $a[$i] = '';
    }
  }
  return implode($a);
}

add_filter('the_content', 'do_sweetcode', 11);

function preprocesscallback1($m) {
  return str_replace("\n", "<br/>", $m[0]);
}
function preprocesscallback($m) {
  $res = $m[0];
  $res = preg_replace_callback("|<pre>.*</pre>|sU", "preprocesscallback1", $res);
  $res = str_replace("\r", "", $res);
  $res = str_replace("\n", "<!--argOnlyNewline-->", $res);
  return $res;
}
function preprocess($text) {
  // delete all newlines within sweetcode bodies as they don't play well with wpautop
  $f = get_sweetcode_regex(true);
  $f = '@'.$f.'@s';
  $text = preg_replace_callback($f, "preprocesscallback", $text);
  return $text;
}

add_filter ('the_content',  'preprocess', 1);

add_filter ('the_content',  'removeAON', 50);

function removeAON($content) {
  return str_replace("<!--argOnlyNewline-->", " ", $content);
}
