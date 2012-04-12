<?php

require_once('plugin-path-definitions.php');
require_once(PWP_LOADER);

class PyboxException extends Exception {}

define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept

define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER

// string constants
define('NASTYCHARACTERS', "\\\n");
define('GENERATORWRAPPER', "from random import randint\n*code*");
define('CSCIRCLES_EMAIL', 'cscircles@gmail.com');

require_once('plugin-utilities.php');

define('GRADERPREAMBLE', softSafeDereference("@file:graderPreamble.py"));  



// end of file
