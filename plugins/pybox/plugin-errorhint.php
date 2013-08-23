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

global $hintage;
$hintage = array(
                 // global means it was within a function body
                 "NameError: (global )?name '(.*)' is not defined" => '
This error indicates that Python tried to look up a variable or function
named <code>$2</code> but it was not defined (or not in the current scope).
If you 
      want to access a variable or function, check spelling, capitalization, and that you defined the 
variable properly. Here is one example:
<pre>
print(Max(3, 4)) # Max should be max
</pre>
The visualizer can help you determine what variables are defined when the error occurs.<br>
      If you did not mean to access a variable, you might have meant a string, which needs to be 
surrounded by quotation marks.
<pre>
print(Hello) # should be print("Hello")
</pre>
',
                 "SyntaxError: invalid syntax" => '
This error indicates Python was unable to interpret your code since
a grammar rule was violated. Common issues include: strings must be enclosed within quotation 
                 marks, conditional expressions require a colon at the end, conditional expressions involving 
                 comparisons are <code>==</code> while assignments are <code>=</code>, and multiplication must always be done with <code>*</code>.
<pre>
if x > 0 # needs colon at end of statement
</pre>
Many issues can cause this error; be sure to also read the lines before 
and after the indicated one.',
"EOFError: EOF when reading a line" => "
This error indicates that Python ran out of input while reading. 
Either you called <code>input()</code> more often than intended,
or you supplied less test input than necessary. For example,
<pre>
print(input()+input()) # reads 2 lines
</pre>
Using the visualizer, you can determine how many times <code>input()</code> is called.
",
"TypeError: unsupported operand type\(s\) .*" =>
"
Python reached a step where it didn't know how to compute
what was asked. This often can mean using a non-numerical
value in the wrong place, for example:
<pre>
print('5'+1) # adding or concatenation?
</pre>
If this is the case, 
check that all 
values used in the arithmetic expressions have been converted
into the intended 
<a href='http://cscircles.cemc.uwaterloo.ca/4-types/'>type</a>.
Using the visualizer, you can inspect exactly what happened
just before the error occurred.
",
"SyntaxError: unexpected EOF while parsing" =>
"
This error indicates that Python ran into the end of code unexpectedly. 
Check that each open 
parenthesis has a corresponding closed 
parenthesis before the end of the line.
<pre>
print(min(5,max(x,y)) # lacks a ')' at end
</pre>
Or you may have ended by forgetting a body:
<pre>
if x > y: # needs a body
</pre>
",
"IndentationError: unindent does not match any outer indentation level"
=>
"
This error indicates that Python ran into an indentation level that 
does not match earlier ones. 
Make sure that you indent properly after conditional expressions, 
and that you unindent accordingly afterwards.
<pre>
if x > y:
  print('ok')
 print('?') # needs 0 or 2 indents
</pre>
Count indentation carefully if you have nested blocks.
",
"SyntaxError: EOL while scanning string literal"
=>
"
This error indicates that Python reached the end of the line while reading a string. Check that you 
have terminated your string properly.
<pre>
print('hello) # should be print('hello')
</pre>
When using special characters such as a quote or backslash 
within a string, place a single backslash before the special character.
",
"IndentationError: unexpected indent"
=>
"This error indicates that Python ran into an indentation with no grammatical basis. Check that 
you have set your indentations correctly. Ensure that there are no stray spaces in front of any line 
of code.
<pre>
print('ok')
    print('Bad spaces at start of line')
</pre>",
"TabError: inconsistent use of tabs and spaces in indentation"
=>
"This error indicates that Python encountered mixed tabs and spaces. Do not intermix tabs and 
spaces in code indentation. Use either only tabs or only spaces (we recommend spaces). If using multiple text editors, 
ensure that the type and length of tab is consistent.
",
"IndentationError: expected an indented block"
=>
"
This error indicates that Python expected indented code but not encounter it. Recall that after a <code>if</code>, <code>for</code>, <code>while</code> or <code>def</code> expression, an indentation is required for its enclosed statements.
<pre>
if name == 'Jim':
print('Hello') # should be indented
</pre>
",
"ValueError: invalid literal for (.*)\(\) with base .*"
=>
'This error indicates that Python tried to convert a
string to a <code>$1</code> number, but the string could not be interpreted
numerically.
<pre>
y = int("5.4") # ok: float("5.4") or int("5") 
</pre>
Using the visualizer, you can determine exactly what 
went on just before the error.',
"TypeError: '.*' object is not iterable"
=>
"
This error indicates that Python attempted to iterate over a
series of objects when only a single 
object was given. The most common problem is that functions
like <code>max()</code> and <code>min()</code>
require multiple objects in order to perform 
their calculations; only one object will not suffice.
<pre>
print(max(a)) # should be print(max(a,b))
</pre>
",
"TypeError: '.*' object is not callable"
=>
"
This error usually indicates a bad use of parentheses.
<pre>
5(4) # 5 isn't a function, did you mean 5*4?
</pre>
In complicated expressions, make sure that your
parentheses properly match, containing function arguments
and/or subexpressions.
<pre>
print(min(1,2)(3,4)) # what is (3,4)?
</pre>
",
"TypeError: can't multiply sequence by non-int of type '.*'"
=>
"
Did you mean to multiply a string or a list by an integer?
Python allows this, for example <code>'hi'*2</code> is <code>'hihi'</code>.
But you cannot multiply by a non-integer, and you cannot
multiply lists and/or strings by each other.
<pre>
[1, 2] * 1.5  # also bad: (1, 2) * 1.5 
</pre>
If you want to multiply the values inside of a list, 
you must use a loop to do each multiplication separately.
",
                 "IndexError: (string|list) index out of range"
=>
'
This error indicates that Python attempted to access the index of a $1 beyond its indices. 
                When accessing the elements of a string or list, keep in mind that the index count goes from 0 to its 
length <b>minus 1</b>. 
<pre>
c = mystr[i] # need i >= 0, i < len(mystr)
</pre>
You can
use the visualizer to inspect the $1 just before the
program crashed.',
                 "SyntaxError: unexpected character after line continuation character"
=>
"A <i>line continuation character</i> means a backslash that
was not used in string escaping properly."
. '
<pre>
print("a\\\\\\\\"\\\\") # letter a, escaped 
# backslash, end string, then bad backslash!
</pre>
Check 
that you do not have too many or too few backslashes, especially when attempting to use special 
characters. <br>
If you meant to use line continuation (we don\'t recommend it), check that it is at the end of the line:
<pre>
x = 3 \\\\
+ 4        # x is 7
</pre>
',
"TypeError: Can't convert '.*' object to str implicitly" =>
                 "
This error indicates that Python tried to 
<code>+</code> a
string with a non-string.
<ul>
<li>
If you meant addition, make sure all string values
are converted to numbers with <code>int()</code> or <code>float</code>
</li>
<li>
If you meant concatenation, make sure all values
are converted to strings with <code>str()</code>
</li>
</ul>
<pre>
'1234' + 5 # what is intended here?
</pre>
The visualizer can help you determine the values just before
the crash.",

"AttributeError: '(.*)' object has no attribute '(.*)'" =>

'
This error indicates that Python found no method or field <code>$2</code> when 
the code tried to call it on a <code>$1</code> object. Check that all object 
variable names and function calls are actually defined by that object, spelled and capitalized 
correctly.
<pre>
obj = [1, 2]
obj.replace(1, 0) #"replace" undefined on lists
</pre>
Use <code>print(dir(obj))</code> to see the methods defined for an object. The
visualizer may help you identify what caused this error exactly.
',
                 "TypeError: '.*' object is not subscriptable"
=>
"
This error indicates that Python attempted to apply a subscript index access,
using <code>[</code> and <code>]</code>, to an object that is
not a list or string. This may occur due to a number of 
grammatical errors:
<pre>
print(input[0]) # try print(input()[0])
</pre>
The visualizer can help you determine the steps just before the error
occurred.
",
                 "TypeError: string indices must be integers"
=>
"
This error indicates that Python attempted to apply a subscript index access,
using <code>[</code> and <code>]</code>, on an index
that is not an integer.
<pre>
mystring[5/2] # 5/2 is not an integer
</pre>
The visualizer can help you determine the steps just before the error
occurred.
",
                 "SyntaxError: can't assign to (function call|literal)"
=>
'
This error indicates that Python attempted to assign a value in an invalid way, such as
<pre>
sqrt(4) = x # we can\'t redefine sqrt(4)
</pre>
This probably means either that
<ul>
<li>you flipped the sides (the variable name in a <code>=</code>
assignment goes on the left) &mdash; try <code>x = sqrt(4)</code>
</li>
<li>you meant to do equality comparison <code>==</code> instead of
assignment <code>=</code> &mdash; try <code>sqrt(4) == x</code>
</li>
</ul>
',
                 "UnboundLocalError: local variable '(.*)' referenced before assignment"
=>
'
This error can only occur inside of a function that you defined. It means
that the variable <code>$1</code> will be defined further down but has not been yet.
<pre>
def f():
 print(x)
 x = 5
f()
</pre>
You can use the visualizer to examine the order in which your
program executes its lines of code.
For more information see 
<a href="http://docs.python.org/3/faq/programming.html#why-am-i-getting-an-unboundlocalerror-when-the-variable-has-a-value">here</a> and 
<a href="http://docs.python.org/3/faq/programming.html#what-are-the-rules-for-local-and-global-variables-in-python">here</a>.
',
"SyntaxError: can't assign to operator"
=>
"
This error indicates that Python encountered an illegal value assignment. If you want to assign a 
value to a variable, remember that on the left side of the assignment operator you may only have 
one term, and the right side can be any expression. Or you might have confused <code>=</code> with <code>==</code>.
<pre>
x + y = z # try z = x + y or x + y == z
</pre>",
                 "ValueError: could not convert string to float:.*"
=>
"This error indicates that Python attempted to convert a string to a float that is not a floating point 
                 number. If you are attempting to convert a string to a float, make sure that the string actually 
represents a floating point number. Ensure that there are no extraneous characters in the string: 
<pre>
x = float('314 159') # did you mean 314.159?
</pre>
The visualizer can help you debug what happened earlier in your code.",
                 "TypeError: unorderable types.*"
=>
"This error indicates that Python attempted to compare and put in order
two differing 
types of objects. You can compare numbers like 
<code>5 >= 4</code> and strings like <code>'food' > 'fish'</code>
but you can't mix the two or compare other types.
<pre>
z = max(5, '6')   # error when max calls >
booly = max > min # can't compare functions
</pre>
Using the visualizer can help you determine which values are being compared.",
                 "TypeError: not all arguments converted during string formatting" =>
"
The <code>%</code> symbol has two purposes in Python: modulo (remainder)
of numbers, and <i>string formatting</i> that we don't discuss. This error
probably means you accidentally applied <code>%</code> to a string.
<pre>
if '10' % 5 == 0: # '10' must be an int
</pre>
Using the visualizer can help you determine what happened just before
the indicated line.",
"TypeError: (object of type '.*' has no .*\(\)|bad operand type for .*|.* argument must be a .*)"
=>
"
This error indicates that Python attempted to call a function on 
the wrong kind of object. 
<pre>
print(len(input)) # try print(len(input()))
print(abs('-5'))  # convert to int first
</pre>
Using the visualizer can help you determine what happened just before
the indicated line.
",
                 "TypeError: '.*' object cannot be interpreted as an integer" =>
"This error indicates that Python encountered a non-integer value where an integer value was 
necessary. This often occurs in a <code>range()</code> statement.
<pre>
for i in range(0, '10'): # use str()
</pre>
Using the visualizer can help you determine what happened just before
the indicated line.
",
"TypeError: an integer is required"
=>
"
This error indicates that Python encountered a non-integer value for a function call that requires 
an integer. This error occurs often with <code>chr()</code> that converts
integers to ASCII characters:
<pre>
letter = chr('95') # convert to int() first
</pre>
Using the visualizer can help you determine what happened just before
the indicated line.
",
"RuntimeError: maximum recursion depth exceeded.*"
=>
'This error indicates that Python executed a function that utilized recursion more times than 
allowed by the interpreter. If you are using a recursive function, ensure that there is a way for the 
recursion to end, and that it is eventually reached by every input.
<pre>
def f(x):
 return x + f(x-1)
print(f(5)) # never stops!
</pre>
This is an excellent error for debugging with the visualizer.',
                 "IndexError: list assignment index out of range"
=>
'
This error indicates that Python attempted to access the index of a list beyond its indices. 
                When accessing the elements of a string or list, keep in mind that the index count goes from 0 to its 
length <b>minus 1</b>. 
<pre>
aList = ["f", "o"]
aList[2] = "x" # can only assign [0] or [1]
</pre>
If you want to append to the end of a list, use <code>list.append()</code>.
<br>You can
use the visualizer to inspect the list just before the
program crashed.',
"TypeError: slice indices must be integers or None or have an __index__ method"
=>
"A <i>slice</i> refers to a multi-part substring/sublist operator, like 
<code>x[b:e]</code> or <code>x[b:e:s]</code>.
Make sure that you use integers for all values inside of <code>[]</code>.
<pre>
s[1:s[len(s)-1]] # try s[1:len(s)-1]
</pre>
You can
use the visualizer to inspect the values just before the
program crashed.",
                 "TypeError: ord\(\) expected .*, but .* found"=>
                "
The <code>ord</code> function,
which converts characters to ASCII values, can only accept
single characters, which are strings of length 1.
<pre>
print(ord(3)) # try ord('3') or chr(3)?
</pre>
You can
use the visualizer to inspect the values just before the
program crashed."
                 ,
                 "SyntaxError: '(.*)' .*(outside|not.*in).*loop" =>
'
This error indicates that Python encountered a $1 statement outside of a loop,
but it only makes sense inside of a loop (<code>break</code> stops the loop,
<code>continue</code> skips to the next iteration of the same loop).
Check the scope and placement of your $1 statement and ensure it is inside of the loop.
',
                 "SyntaxError: 'return' outside function" =>
'
Double-check your indentation and line placement, so that <code>return</code>
only occurs inside of functions:
<pre>
def f(x):
  if x > 0: return True
return False           # outside!
</pre>
',
                 'TypeError: can only concatenate list \(not .*\) to list'
=>
'
Adding elements to lists can be done in two ways in Python,
<pre>
L = [1, 2]
print(L + [3]) # L stays [1, 2]
L.append(3)    # changes L
</pre>
This error indicates that 
you used <code>+</code> but didn\'t add two lists. 
You can
use the visualizer to inspect the values just before the
program crashed.
'
);
