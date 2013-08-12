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
define('PCEMCCSC', 'http://www.cemc.uwaterloo.ca/resources/cscircles');

// optional reporting and exporting.
// if you want logging to work, you must define one of the two
// PYBOXLOG constants
define ('ON_CEMC_SERVER', UWPHOME == 'http://cscircles.cemc.uwaterloo.ca'
        || UWPHOME == 'http://cscircles.cemc.uwaterloo.ca/dev');
if (ON_CEMC_SERVER) {
  // if you want some of these, remove them from the 'if' block
  define('PYBOXLOG_EMAIL', 'daveagp@gmail.com');        // e-mail notifications for logging
  define('PPYBOXLOG', ABSPATH . '../../pybox_log.txt'); // file, writeable by apache, for logging
  define('PEXPORT', ABSPATH . '../../export/');         // export directory
}

// messages sent by CS Circles will have this return address:
define('CSCIRCLES_BOUNCE_EMAIL', 'bounces@cscircles.cemc.uwaterloo.ca'); 
/* this means they will bounce to the cemc.uwaterloo.ca site and not yours,
   and the links generated might not be correct.

   If you want to change this, you must also change the two constants at the top
   of send_email.py, you must configure your server as described at the top
   of bounce_email.py, and you must ensure /usr/bin/python3 works. */

// you probably don't need to change these
//maximum size 'POST' that submit.php will accept
define('POSTLIMIT', 20000); 
// if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER
define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); 

// wordpress "turns on" magic quotes even if they are off,
// so no matter what is your server setting, this probably should be true
// http://wordpress.org/support/topic/sql-injection-escaping-and-magic-quotes
define('MAGIC_QUOTES_USED', true);

// you probably don't need to change these if you install our python3jail and safeexec repositories
define('PPYTHON3MODJAIL', '/bin/python3');
define('PSCRATCHDIRMODJAIL', 'scratch/');

