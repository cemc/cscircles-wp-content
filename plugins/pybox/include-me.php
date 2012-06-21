<?php

require_once('plugin-path-definitions.php');
require_once(PWP_LOADER);

class PyboxException extends Exception {}

define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept

define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER

// string constants
define('CSCIRCLES_EMAIL', 'cscircles@uwaterloo.ca');

require_once('plugin-utilities.php');

// end of file
