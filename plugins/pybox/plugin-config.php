<?php

// CHANGE THESE: where are safeexec and python3jail installed? 
// (wordpress defines ABSPATH as absolute path to wordpress dir, ending in slash)
// these are the CEMC defaults, but yours can be anywhere
// you probably don't want them in a web-viewable directory
define('PJAIL', ABSPATH . '../../python3jail/'); // with trailing slash
define('PSAFEEXEC', ABSPATH . '../../safeexec/safeexec'); // binary executable
// you don't need to use ABSPATH at all: /home/myaccount/python3jail/ is fine for example
// if for some reason you want to use a relative path, it should be relative to the "pybox" directory (see action-submit-code.php)

// CHANGE THIS to URL of visualizer (or http://cscircles.cemc.uwaterloo.ca/visualize if not installed)
define('UVISUALIZER', site_url( '/visualize/' ) );
// CHANGE THIS to URL of your search page
define('USEARCH', site_url ( '/search/') );

// misc constants
define('CSCIRCLES_EMAIL', 'cscircles@uwaterloo.ca');
define('CSCIRCLES_ASST_EMAIL', 'csc-assistant@uwaterloo.ca');
define('CSCIRCLES_ASST_ID_DE', 11351);
// you can leave CSCIRCLES_BOUNCE_EMAIL as-is, although users will
// get bounces directing them to our site. However, to get a
// bounce specific to your server requires extra steps, see 
// bounce_email.py and configgure your mail server accordingly.
define('CSCIRCLES_BOUNCE_EMAIL', 'bounces@cscircles.cemc.uwaterloo.ca'); 
define('PCEMCCSC', 'http://www.cemc.uwaterloo.ca/resources/cscircles');

// optional reporting and exporting.
// if you want logging to work, you must define one of the two
// PYBOXLOG constants
if ($_SERVER['SERVER_NAME'] == 'cscircles.cemc.uwaterloo.ca') {
  // if you want some of these, remove them from the 'if' block
  define('PYBOXLOG_EMAIL', 'daveagp@gmail.com');        // e-mail notifications for logging
  define('PPYBOXLOG', ABSPATH . '../../pybox_log.txt'); // file, writeable by apache, for logging
  define('PEXPORT', ABSPATH . '../../export/');         // export directory
}

// according to default python3jail setup
define('PPYTHON3MODJAIL', '/bin/python3');
define('PSCRATCHDIRMODJAIL', 'scratch/');

define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept
define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER
