<?php

require_once("include-me.php");

require_once(PWP_LOADER);
// the latter is ONLY needed for: 
//   if user is correct, then we see if logged in, and save completion
//   check if user is an admin, to show debug info
// is this a performance hit (from loading all of wordpress?) 


function safepython($files, $mainfile, $stdin, $cpulimit = 1) {
// execute the python $program using safeexec (here $program is a string or file reference)
// $stdin is the standard input for the program
//
// safepython will copy all relevant files to a new jail subdirectory and clean it up after
// only permanent side effect should be a log entry
//
// $files is an associative array of (filename => NULL | <string>)
// <string>: the file is created non-writeable and filled with <string>
// NULL: the file is create writeable and empty
//
// outputs in array format: see end of code
   if (!is_numeric($cpulimit) || !is_int($cpulimit+0) || $cpulimit <= 0 || $cpulimit > 10) 
     throw new PyboxException('invalid cpulimit ' . var_export($cpulimit, TRUE));
   
   $clocklimit = intval($cpulimit)*WALLFACTOR + WALLBUFFER;

   $prefix = PB_DEV?"D":"L";

   $id = $prefix.rand(100000000, 999999999);
   //pyboxlog("[safepython] job id " . $id . " starting", TRUE);
   $dir = PSCRATCHDIRMODJAIL . $id . "/";

   $loadLevel = explode(" ", trim(exec("ls -al " . PJAIL . PSCRATCHDIRMODJAIL . " | grep $prefix | wc")));
   $loadLevel = $loadLevel[0];

   mkdir(PJAIL . $dir);  
   chmod(PJAIL . $dir, 0710); //new scratch directory owned by user and group apache

   foreach ($files as $filename=>$contents) 
     if ($contents === NULL) { // file for output
       softDump ( "", PJAIL . $dir . $filename);
       chmod(PJAIL . $dir . $filename, 0777); 
     }
     else  // file for input
       softDump ( html_entity_decode($contents), PJAIL . $dir . $filename);

   $safeexecOutFile = PJAIL . $dir . "safeexec.out";
   $stderrFile = PJAIL . $dir . "stderr.out";

   $descriptorspec = array
     (0 => array("pipe", "r"), 
      1 => array("pipe", "w"),// stdout
      2 => array("file", $stderrFile, "w")
      );
   $cwd = PJAIL; // was $dir but jailed python doesn't like this  
   $env = array(); // array('some_option' => 'aeiou');

   // you need plenty of memory for Python. 50000k is not enough!
   $command = PSAFEEXEC . " --fsize 100 --env_vars PY --gid 1000 --uidplus 10000 " . 
     " --cpu $cpulimit --mem 100000 --clock $clocklimit --report_file $safeexecOutFile " .
     " --chroot_dir " . PJAIL . " --exec_dir /$dir --exec " . PPYTHON3MODJAIL . " -u -S $mainfile";

   global $mainProfilingID;
   $minorProfilingID = beginProfilingEntry(array("activity"=>"safeexec", "parent"=>$mainProfilingID));

   $process = proc_open($command, $descriptorspec, $pipes, $cwd, $env);
   
   if (!is_resource($process)) return FALSE;
   
   fwrite($pipes[0], $stdin);
   fclose($pipes[0]);
   
   $output = bounded_stream_get_contents($pipes[1], 10000); 
   //$errors = bounded_stream_get_contents($pipes[2], 10000);
   
   $errors = file_get_contents($stderrFile);
   $errors = array('data' => $errors, 'length' => strlen($errors));

   fclose($pipes[1]);
   //fclose($pipes[2]);
   
   proc_close($process);

   $safeexecOut = file_get_contents($safeexecOutFile);

   $cpuTime = preg_match('|(cpu.usage:.)([\d\.]*)(.seconds)|', $safeexecOut, $matches) === 1 ? $matches[2] : "unavailable"; // e.g., if killed

   endProfilingEntry($minorProfilingID, 
		     array("meta"=>array("loadLevel"=>$loadLevel,"safeexec reported cputime"=>$cpuTime)));

   $outdata = array();
   foreach ($files as $filename=>$data) 
     if ($data === NULL)
       $outdata[$filename] = file_get_contents(PJAIL . $dir . $filename);

   $keepTemp = FALSE;
   if (!$keepTemp) {
     // delete all temp things
     unlink($safeexecOutFile);
     unlink($stderrFile);
     foreach ($files as $filename=>$ignore) 
       unlink(PJAIL . $dir . $filename);
     rmdir(PJAIL . $dir);  
   }
   
   //pyboxlog("[safepython] job id $id ending. wall time: $wallTime; cpu time $cpuTime; load level $loadLevel", 
   //    ($loadLevel == 0 || PB_DEV) ? "suppress" : -1);
   //send an email if the load level is >0 and we're not on the debug site

// 'safeexecOut' is the output remarks of safeexec
// 'stdout' is the stdout of the actual program, 'stdoutlen' is its length since it might have been trimmed
// 'stderr' is the stderr of the actual program, 'stderrlen'
// 'outdata' is an associative array of what was written to the writable files
// 'ok' is a boolean: did the program run okay, or crash?
   return array('outdata'=>$outdata, 'safeexecOut'=>$safeexecOut, 'stdout'=>$output['data'], 'stdoutlen'=>$output['length'], 
		'stderr'=>$errors['data'], 'stderrlen'=>$errors['length'], 'ok'=>(substr($safeexecOut, 0, 2)=="OK"));
}

// the following are global defaults in absence of overwritten settings
function optionsAndDefaults() {
  // note: these should be all lower case, there was once a bug in wordpress' shortcode processing otherwise
  return array( 
	       "slug" => FALSE,                # important unless it's a pyExample or facultative

	       // stuff affecting what the pybox shows upon submission //
	       "showinput" => "Y",             
	       "showoutput" => "Y",
	       "showstderr" => "N",            
	       "showexpected" => "Y",
	       "showsafeexec" => "N",
	       "hideemptyoutput" => "N",
	       "hideemptyinput" => "Y",
	       "showonly" => FALSE,

	       // elementary things for constructing test cases //     
	       "input" => FALSE,
	       "answer" => FALSE,              # rarely used: solver is preferred
	       "grader" => "*diff*",
	       "solver" => FALSE,
	       "repeats" => "1",   
	       "generator" => FALSE,

	       // additional things which run in Python along with the user code //
	       "precode" => FALSE,
	       "autotests" => FALSE,
	       "rawtests" => FALSE,
	       "inplace" => FALSE,

	       // for safeexec //
	       "cpulimit" => "1",              # in seconds; maximum 10

	       // etc //
	       "haltonwrong" => "Y",           # halt after any incorrect sub-problem?
	       "nolog" => FALSE,               # don't generate a DB entry... used for pyExample, scramble
	       "taboo" => FALSE,               # forbidden substrings/regexes for user code
	       "allowinput" => "N",            # is input allowed?
	       "usertni" => FALSE,             # are user tests allowed in place of user input?
	       "facultative" => "N",           # does this exercise not have a checkmark?
	       "desirederror" => FALSE         # what error message must be produced?
	       );
}


function stderrNiceify($S) {
  $lines = explode("\n", $S);
  $r = $lines[0];
  for ($i=1; $i<count($lines); $i++) {
    $line = $lines[$i];
    if (preg_match('|(\s*)File "mainfile", line ([\d]*), in |', $line, $matches)) {
      $nextLine = $lines[$i+1]; //process two lines at a time
      $i++;
      if (preg_match('|\s*exec\(compile\(open\(\'usercode\'\).read\(\), \'usercode\', \'exec\'\)\)\s*|', $nextLine) || 
	  preg_match('|\s*exec\(compile\(open\(\'usertests\'\).read\(\), \'usertests\', \'exec\'\)\)\s*|', $nextLine) ||
	  preg_match('|\s*exec\(compile\(open\(\'testcode\'\).read\(\), \'testcode\', \'exec\'\)\)\s*|', $nextLine))
	{}
      else if (preg_match('|raise\(|', $nextLine))
        {}
      else 
	$r .= "\n" . $matches[1] . "In line:" . "\n" . $nextLine;
    }
    else if (preg_match('|(\s*)File "testcode"|', $line)) {
      $i++;
    }
    else if (preg_match('|(\s*)File "/scratch|', $line)) {
      $i++;
    }
    else if (preg_match('|(\s*)File "/static|', $line)) {
      $i++;
    }
    else if (preg_match('|(\s*)File "usercode", line ([\d]*), in <module>\s*|', $line, $matches)) {
      $r .= "\n" . $matches[1] . "In line $matches[2] of the code you submitted:";
    }
    else if (preg_match('|(\s*)File "usercode", line ([\d]*)\s*|', $line, $matches)) {
      $r .= "\n" . $matches[1] . "In line $matches[2] of the code you submitted:";
    }
    else if (preg_match('|(\s*)File "usertests", line ([\d]*), in <module>\s*|', $line, $matches)) {
      $r .= "\n" . $matches[1] . "In line $matches[2] of the tests you submitted:";
    }
    else if (preg_match('|(\s*)File "usertests", line ([\d]*)\s*|', $line, $matches)) {
      $r .= "\n" . $matches[1] . "In line $matches[2] of the tests you submitted:";
    }
    else {
      $r .= "\n" . $line;
    }
  }
  return $r;
}

// in next procedure, $testCaseDescription is an array with the same keys as optionsAndDefaults()
// returns an associative array with values:
// "result" => one of {"pass", "fail", "error"}, here "error" means something was wrong with the CEMC site or problem settings
// "message" => message to user in case of pass/fail
// "errmsg" => error messagef
// helper functions
  function tcpass($message) {return array("result" => "pass", "message" => $message, "errmsg" => FALSE);}
  function tcfail($message) {return array("result" => "fail", "message" => $message, "errmsg" => FALSE);}

function inputMaker($TC) {
  global $inputInUse, $userinput, $usertni;
  extract($TC);

  if ($inputInUse) {
    if ($usertni) 
      return FALSE;
    else
      return "_stdin=" . pythonEscape($userinput);
  }
  if ($input!==FALSE)
    return "_stdin=" . pythonEscape(softSafeDereference($input));

  if ($generator === FALSE)
    return FALSE;

  return "def _genstdin():\n" 
    . " _stdout = _sys.stdout\n _sys.stdout = mystdout = _StringIO()\n"
    . str_replace("\n", "\n ", "\n" . $generator) . "\n _sys.stdout = _stdout\n return mystdout.getvalue()\n"
    . "\n_stdin = _genstdin()";
}

function outputDescription($pass, $args) {
  extract($args);
  if ($pass === TRUE) {
    if (($showoutput=='Y' || $showexpected=='Y') && $stdout != "") {
      if ($grader == "*nograder*" || $grader == '*inplace*')
	return "Program gave the following output:" . preBox($stdout, $stdoutlen);
      else
	return "Program gave the following correct output:" . preBox($stdout, $stdoutlen);
    }
    else
      return "";
  }
  if ($pass === NULL) {
    if ($showoutput != "Y" || ($stdoutlen == 0 && (!$ok || $hideemptyoutput == 'Y')))
      return "";
    elseif ($stdoutlen == 0)
      return "Program printed no output.<br>";
    else
      return "Program gave the following output:" . preBox($stdout, $stdoutlen);
  }

  // pass == FAIL
  if ($showoutput != 'Y')
    $part1 = "";
  elseif ($stdoutlen == 0) {
    $part1 = "Program printed no output.";
    if ($requiredStdout != "") {
      $part1 .= " <i>(Did you forget to use </i><code>print</code><i>?)</i>";
    }
    $part1 .= "<br/>";
  }
  else
    $part1 = "Program output:" . preBox($stdout, $stdoutlen);

  if ($showexpected == 'Y' && $requiredStdout != "")
    $part2 = "Expected this correct output:".preBox($requiredStdout);
  else
    $part2 = "";
  
  return $part1 . $part2;
}

// main function for a test case
function doGrading($usercode, $TC) {
  $files = array();
  if ($TC['showonly']!==FALSE) {
    $desired = explode(" ", $TC['showonly']);
    foreach ($TC as $name=>$value) 
      if (substr($name, 0, 4)=="show") 
	$TC[$name]=(in_array(substr($name, 4), $desired))?"Y":"N";
  }

  if ($TC['answer']!==FALSE)
    $TC['answer']=ensureNewlineTerminated($TC['answer']);
  
  $TC["inplace"] = booleanize($TC["inplace"]);

  extract($TC); // same as $showinput = $TC["showinput"], etc  

  $mainFile = "";
  $er = FALSE;

  $mainFile .= "from _UTILITIES import *\n";

  $inputMaker = inputMaker($TC);
  $noInput = ($inputMaker === FALSE);
  $mainFile .= ($inputMaker === FALSE ? "_stdin=''" : $inputMaker) 
    . "
_stdincopy = open('stdincopy', 'w')
print(_stdin, file=_stdincopy, end='')
_stdincopy.close()
";

  if ($precode !== FALSE) 
    $mainFile .= softSafeDereference($precode) . "\n";

  $files['stdincopy'] = NULL;

  $mainFile .= "import _GRADER\n";
  $mainFile .= "_G = _GRADER\n";

  global $inputInUse, $facultative;

  if (!$inputInUse && ($inplace || $solver !== FALSE)) {
    if ($solver !== FALSE) 
      $files['solver'] = $solver;
    if ($solver !== FALSE) 
      $mainFile .= "_GRADER.globalsInitAndEcho(globals())\n";
    else
      $mainFile .= "_GRADER.globalsInitAndEcho(globals(), False)\n";
    $files['graderreply'] = NULL;
    $files['graderpre'] = NULL;
    $files['solverstdout'] = NULL;
    $mainFile .= "_GRADER.runSolverWithTests()\n"; // run the solver before usercode, lest they mess up our globals.
    
    $testcode = "";
    if ($rawtests !== FALSE)
      $testcode .= $rawtests . "\n";

    if ($autotests != FALSE) {
      $autotests = softSafeDereference($autotests);

      foreach (explode("\n", $autotests) as $autotestline) {
	if (preg_match('|^(\s*)(\S.*)$|', $autotestline, $matches)===0) continue; //skip blank lines
	$indentation = $matches[1];
	$command = trim($matches[2]);
	if (1 == preg_match('|^\w*$|', $command)) { // looks like just a variable name
	  $testcode .= $indentation . "_G.checkVar('$command')\n";
	}
	elseif (1 == preg_match('|^(\w*)\s*\((.*)\)|', $command, $pieces)) {
	  if ((strpos($pieces[2], $pieces[1]))===FALSE) // looks like a non-nested function call
	    $testcode .= $indentation . "_G.autotestCall('" . $pieces[1] . "',[" . $pieces[2] . "])\n";
	  else { // something more complex
	    $testcode .= $indentation . "_G.sayRunning(\"" . $command . "\")\n";
	    $testcode .= $indentation . "_G.autotestCompare(\"" . $command . "\", $command)\n";
	  }
	}
	else $testcode .= $autotestline . "\n"; // just leave it alone
      }
    }
    $files['testcode'] = $testcode === FALSE ? "" : softSafeDereference($testcode) . "\n";
  }

  $mainFile .= '
_orig_std = (_sys.stdin, _sys.stdout)
_user_stdout = _StringIO()
_sys.stdout = _TeeOut(_user_stdout, _orig_std[1])
_sys.stdin = _StringIO(_stdin)
exec(compile(open(\'usercode\').read(), \'usercode\', \'exec\'))
';
  if (!$inputInUse) { // lesson 18, part 2: may do this even if facultative
    if ($inplace) {
      $mainFile .= "exec(compile(open('testcode').read(), 'testcode', 'exec'))\n";
      $mainFile .= "_G.say('Y', 'noend')\n"; // success if none of the tests crash
    }
  }
  // we've got all the user stdout necessary for testing
$mainFile .= '
__user_stdout = _user_stdout.getvalue()
_user_stdout.close()
(_sys.stdin, _sys.stdout) = _orig_std
';
  if (!$facultative && !$inputInUse) {
    if ($answer !== FALSE) {
      $mainFile .= "_G._solver_stdout = " . pythonEscape(softSafeDereference($answer)) . "\n";
    }
    if ($grader !== '*nograder*' && ($answer !== FALSE || $solver !== FALSE)) {
      $mainFile .= "_G.stdoutGrading(_stdin, __user_stdout, _G._solver_stdout, " .pythonEscape(softSafeDereference($grader))." )\n";
      $files['stdoutgraderreply'] = NULL;
    }
  }

  $testDescription = FALSE;

  $files["usercode"] = $usercode;

  global $usertni;

  if ($inputInUse && $usertni) {
    $mainFile .= "\n" . "exec(compile(open('usertests').read(), 'usertests', 'exec'))\n";
    global $userinput;
    $files['usertests'] = $userinput;
  }

  $files["mainfile"] = $mainFile;

  $userResult = safepython($files, "mainfile", "" /* stdin is simulated */, $cpulimit);

  extract($userResult);

  // start printing stuff out now.
  $m = '';

  if ($testDescription != FALSE)
    $m .= $testDescription;

  if (!$inputInUse && $inplace && trim($outdata['graderpre']) != '')
    $m .= '<i>Before running your code:</i> ' . $outdata['graderpre'] . '<br/>';

  if ($showinput=="Y" && !$inputInUse && !$noInput &&
      ($hideemptyinput=="N" || $outdata['stdincopy']!=""))
    $m .= "Input:" . preBox($outdata['stdincopy']);

  $errnice = preBox(stderrNiceify($stderr), $stderrlen);
  if (userIsAdmin()) 
    $errnice .= JQpopUp("Debug: view unsanitized", preBox($stderr, $stderrlen));

  if ($stderr=='')
    $errnice = '';
  else
    $errnice = '<p>Error messages: ' . $errnice . '</p>';

  if ($ok) 
    $m .= "<p>Program executed without crashing.</p>";
  elseif (firstLine($safeexecOut) == 'Command exited with non-zero status (1)') 
    $m .= "<p>Program crashed.</p>";
  else
    $m .= "<p>Program crashed &mdash; " . firstLine($safeexecOut) . ".</p>";

  if ($showsafeexec=="Y")
    $m .= "Sandbox messages:" . preBox($safeexecOut);

  $simpleOutputDescription = outputDescription
    (NULL, array('showoutput'=>$showoutput, 'stdoutlen'=>$stdoutlen, 'hideemptyoutput'=>$hideemptyoutput,
		 'stdout'=>$stdout, 'ok'=>$ok));

  if ($desirederror !== FALSE) {
    $m .= $simpleOutputDescription;
    $lines = explode("\n", trim($userResult["stderr"]));
    $goodFail = (count($lines)>0) && ($lines[count($lines)-1]) == $desirederror;
    $m .= $errnice;
    return $goodFail ? tcpass($m) : tcfail($m);
  }

  if ((!$ok) || $facultative) { // we don't care what's in stdout
    $graderreply = trim(getSoft($outdata, 'graderreply', ''));
    if (!$ok && !$inputInUse && $graderreply != '') 
      $m .= "<i>The grader said:</i><div>" . $graderreply . "</div>";
    elseif ($inplace && $solver === FALSE & $graderreply != '')  
      $m .= "<i>Automatic tests:</i><div>" . substr($graderreply, 0, -1) . "</div>";
    $m .= $errnice . $simpleOutputDescription;
    return $ok ? tcpass($m) : tcfail($m);
  }

  if ($inplace) { // don't care what's in stdout, unless solverstdout != ''
    $GR = $outdata['graderreply'];
    $inplaceresult = substr($GR, -1);
    $inplacereply = substr($GR, 0, -1);

    if ($inplacereply != '')
      $inplacereply = "<i>The grader said:</i><div>$inplacereply</div>";

    if ($inplaceresult == 'Y') {
      if ($outdata['solverstdout'] == '')
	return tcpass($m . $inplacereply . $errnice . $simpleOutputDescription);
    }
    elseif ($inplaceresult == 'N')
      return tcfail($m . $inplacereply . $errnice . $simpleOutputDescription);

    $m .= $inplacereply; // carry on and let the stdout grader do its thing
  }

  // the user's code did not crash. what did the stdout grader say?
  $outGraderReply = $outdata['stdoutgraderreply'];
  if ($outGraderReply=="" || !( $outGraderReply[0] == "Y" || $outGraderReply[0] == "N") )
    throw new PyboxException("Grader error 2 [" . $outGraderReply .'|' . $outdata['graderreply'] . "|" . ord(substr($outdata['graderreply'], -1)) . "| $m ]");

  $outinfo = array('stdout'=>$stdout, 'stdoutlen'=>$stdoutlen,
		   'requiredStdout'=>getSoft($outdata, 'solverstdout', $answer),
		   'showoutput'=>$showoutput, 'showexpected'=>$showexpected, 'grader'=>$grader);
  $m .= outputDescription($outGraderReply[0] == "Y", $outinfo) . $errnice;

  if (strlen(trim($outGraderReply)) > 1)
    $m .= "<p>Result of grading: " . substr($outGraderReply, 1) . "</p>";

  return ($outGraderReply[0]=="Y") ? tcpass($m) : tcfail($m);
}
// end of doGrading


// this is only for saving completed coding exercises; short answer etc work differently.
function saveCompletion() {
  global $facultative, $slug;
  if ( !is_user_logged_in() || $facultative || $slug === NULL) 
    return; 

  global $wpdb;
  $uid = wp_get_current_user()->ID;
  $table_name = $wpdb->prefix . "pb_completed";
  $sqlcmd = "SELECT COUNT(1) FROM $table_name WHERE userid = %d AND problem = %s";
  $count = $wpdb->get_var($wpdb->prepare($sqlcmd, $uid, $slug));
  if ($count==0) {
    $wpdb->insert( $table_name, 
		   array( 'userid' => $uid,
			  'problem' => $slug) );
  }
}

//return string of submit.php: 1st character indicates status and the rest is html for the pybox. character codes:
//Y: correct/success! the user completed the exercise.
//y: correct/success! but read-only or user input, so it would be wrong to say exercise is complete.
//E: error
//N: incorrect/fail

// 3 helper functions for main
function mpass($message) {
  global $facultative; 
  if (!$facultative) saveCompletion(); 
  return ($facultative?"y":"Y<b>Success!</b><br/>") . $message;
} //helper
function mfail($message) {
  global $facultative;
  return ($facultative?"N":"N<b>Did not pass tests. Please check details below and try again.</b><br/>") . $message;
} //helper
function merror($message, $errmsg, $suppress = -1) {
  global $beginstamp;
  pyboxlog("[main error] " . $errmsg . " (partial message: " . $message . ")", $suppress);
  return "E"."<b>Internal error or HTTP error, details below. You can <a href=\"" 
    . UCONTACT . "\">contact us</a>.</b> Timestamp: " . date("y-m-d H:i:s", $beginstamp) . "<br/>" . preBox($errmsg);
} //helper
function msave() {
  global $userid;
  return "S".(($userid==-1)?"You must log in to save data.":"Program saved.");
}

function main() {
  /**************************************************
    part 0 : initialization and checking that a valid problem is selected 
  ************************************************/
  global $logRow, $beginstamp, $userid, $userinput, $meta, $wpdb,
    $inputInUse, $facultative, $usertni, $mainProfilingID, $slug;
  $beginstamp = time();
  $logRow = FALSE;
  $meta = array();

  $mainProfilingID = beginProfilingEntry(array("activity"=>"submit-code"));

  if ($_SERVER['REQUEST_METHOD'] != 'POST')
    return merror('', 'HTTP mangling: method "' . $_SERVER['REQUEST_METHOD'] .
		  '" was requested instead of "POST"', 'suppress');

  if (count($_POST)==0)
    return merror('', 'HTTP mangling: POST request contained no data', 'suppress');

  if (strlen(print_r($_POST, TRUE))>POSTLIMIT) {
    pyboxlog("submit.php got too many bytes of data:" . strlen(print_r($_POST, TRUE)));
    return mfail('Submitted data (program and/or test input) too large.
                  Reduce size or <a href = "' . URUNATHOME . '">run at home</a>.');
  }
  
  $id = getSoft($_POST, "pyId", "EMPTY");
  $usercode = getSoft($_POST, "usercode" . $id, -1);
  if (!is_string($usercode))
    return merror("", "No usercode" . $id . "!" . print_r($_POST, TRUE));
  $usercode = stripslashes($usercode);                         // php magic quotes!
  $usercode = preg_replace('|\xc2\xa0|', ' ', $usercode);      // non-breaking spaces introduced by editors

  $userinput = stripslashes(getSoft($_POST, "userinput", "")); // damn you, php magic quotes!
  $userinput = preg_replace('|\xc2\xa0|', ' ', $userinput);    // non-breaking spaces introduced by editors

  $hash = $_POST["hash"];
  
  //$graderArgsString = safeDereference("@file:" . $hash, 'hashes');
  //if (!is_string($graderArgsString)) 
  //  return merror("", "PyBox error: problem hash " . $hash . " not found.");

  /**************************************************
    part 1 : set global variables, build skeleton log row; quit upon justsave.
  ************************************************/
  //$problemArgs = multilineToAssociative($graderArgsString);
  //  foreach ($problemArgs as $key=>$value)
  //  $problemArgs[$key] = stripcslashes($value);

  $problemArgs = $wpdb->get_var($wpdb->prepare("
SELECT graderArgs from wp_pb_problems WHERE hash = %s", $hash));
  if ($problemArgs === NULL)
    return merror("", "Pybox error: problem hash " . $hash . " not found. Try reloading the page.");

  $problemArgs = json_decode($problemArgs, TRUE);

  //  if ($problemArgs != $problemArgsNew)
  //  pyboxlog("different: " . var_export($problemArgs, TRUE) . var_export($problemArgsNew, TRUE));
  //else
  //  pyboxlog("same", TRUE);

  $re = '/^('.implode("|",array_keys(optionsAndDefaults())).')([0-9]*)$/';
  $problemOptions = optionsAndDefaults();
  $subproblemOptions = array();
  foreach ($problemArgs as $key=>$value) {
    $match = preg_match($re, $key, $matches);
    if ($match == 0) 
      return merror("", "PyBox error: unknown option " . $key);
    if ($matches[2] == "") 
      $problemOptions[$matches[1]] = $value;
    else {
      if (!array_key_exists($matches[2], $subproblemOptions))
	$subproblemOptions[$matches[2]] = array();
      $subproblemOptions[$matches[2]][$matches[1]] = $value;
    }
  }
  foreach ($subproblemOptions as $index => $spo) {
    foreach ($problemOptions as $option => $value) {
      if (!array_key_exists($option, $spo))
	$subproblemOptions[$index][$option] = $value;
    }
  }

  $inputInUse = isSoft($_POST, "inputInUse", "Y");
  /*  var_dump( $_POST, TRUE);
  echo "eq: " .(($_POST["inputInUse"] === "Y") ? "T":"F");
  echo "inputinuse: " . ($inputInUse ? "T":"F");*/
  if ($inputInUse && !isSoft($problemOptions, "allowinput", "Y")) 
    return merror("", "Pybox error: input not actually allowed");

  $facultative = isSoft($problemOptions, "facultative", "Y") || $inputInUse;
  $usertni = isSoft($problemOptions, "usertni", "Y");

  $userid = is_user_logged_in() ? wp_get_current_user()->ID : -1;
  $meta['userid'] = $userid;
  $meta['problem'] = getSoft($problemArgs, 'slug', $hash);

  $slug = getSoft($problemArgs, 'slug', NULL);

  //most of the submit logging preparation. anything quitting earlier is not logged in DB
  if (!isSoft($problemOptions, "nolog", "Y")) {
    $postmisc = $_POST;
    unset($postmisc['usercode' . $id]);
    unset($postmisc['userinput']);
    unset($postmisc['hash']);
    $logRow = array(
		    'beginstamp' => date( 'Y-m-d H:i:s', $beginstamp ),
		    'usercode' => $usercode,
		    'hash' => $hash,
		    'postmisc' => print_r($postmisc, TRUE),
		    'problem' => $slug, 
// submissions for problems without a slug are saved but can't be retrieved
		    'ipaddress' => ($_SERVER['REMOTE_ADDR']),
		    'referer' => ($_SERVER['HTTP_REFERER']));
    if ($inputInUse) 
      $logRow['userinput'] = $userinput;
    $logRow['userid'] = $userid;
    
    //if ($logRow['problem']===NULL)
    //pyboxlog('nameless problem that is not read-only!', TRUE);
  }

  $justsave = array_key_exists('justsave', $_POST);
  if ($justsave)
    return msave();

  /**************************************************
    part 2 : grading
  ************************************************/
  if ($problemOptions['taboo'] != FALSE) {
    $taboo = explode(",", $problemOptions['taboo']);
    foreach ($taboo as $t) {
      $p = strpos($t, "|");
      if ($p === FALSE) {
	$regex = $t;
	$display = $t;
      }
      else {
	$display = substr($t, 0, $p);
	$regex = substr($t, $p+1);
      }
      $match = preg_match("#.*".trim($regex).".*#", $usercode);
      if ($match != 0) {
	return mfail("You cannot use <code>".trim($display)."</code> in this exercise.");
      }
    }
  }  

  if (count($subproblemOptions)==0) {
    //    if ($problemOptions['grader'] == '*nograder*')
      $subproblemOptions["1"] = $problemOptions;
      //else
      //return merror("", "No test cases found!");
  }

  // $spo: subproblemOptions for the current subproblem
  $tcTotal = 0;
  foreach ($subproblemOptions as $N=>$spo) 
    $tcTotal += $spo["repeats"];

  ksort($subproblemOptions); //test case 1, then 2, ...   
  $tcCurrent = 0;
  $allCorrect = TRUE; 
  $m = ''; //the result string, built a bit at a time.
  ///*********************************************** main grading loop ***/
  foreach ($subproblemOptions as $N=>$spo) { // spo: subproblemOptions for current subproblem
    for ($i=0; $i<$spo["repeats"]; $i++) {      
      $tcCurrent++;
      if (!($inputInUse) && $tcTotal > 1)
	$m .= "<b>Results for test case " . $tcCurrent . " out of " . $tcTotal . "</b><br/>";
      try {
	$tcOutcome = doGrading($usercode, $spo);
	$m .= $tcOutcome["message"];
	if ($tcOutcome["result"]=="error") 
	  return merror($m, $tcOutcome["errmsg"]);
	if ($tcOutcome["result"]=="fail" && ($spo["haltonwrong"]=="Y")) 
	  return mfail($m);
	if ($tcOutcome["result"]=="fail")
	  $allCorrect = FALSE;
      }
      catch (PyboxException $e) {
	return merror($m, $e->getMessage());
      }
      if ($inputInUse) break;
    }
    if ($inputInUse) break;
  }
  return $allCorrect ? mpass($m) : mfail($m);
}

$result = main();
echo $result;

//rest of the query logging
global $wpdb, $logRow, $mainProfilingID, $meta;
$crossref = NULL;
if ($logRow != FALSE) {
  $logRow['result'] = $result[0];
  $table_name = $wpdb->prefix . "pb_submissions";
  $wpdb->insert( $table_name, $logRow);
  $crossref = $wpdb->insert_id;
 }

$meta['ipaddress'] = $_SERVER['REMOTE_ADDR'];
endProfilingEntry($mainProfilingID, array("crossref"=>$crossref, "meta"=>$meta));


// end of file