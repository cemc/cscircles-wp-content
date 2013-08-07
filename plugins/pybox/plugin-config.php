<?php

// CHANGE THESE: where are safeexec and python3jail installed? 
// (wordpress defines ABSPATH as absolute path to wordpress dir, ending in slash)
// these are the CEMC defaults, but yours can be anywhere
// you probably don't want them in a web-viewable directory
define('PJAIL', ABSPATH . '../../python3jail/'); // with trailing slash
define('PSAFEEXEC', ABSPATH . '../../safeexec/safeexec'); // binary executable

// CHANGE THIS to URL of visualizer (or http://cscircles.cemc.uwaterloo.ca/visualize if not installed)
define('UVISUALIZER', site_url( '/visualize/' ) );
// CHANGE THIS to URL of your search page
define('USEARCH', site_url ( '/search/') );

// misc constants
define('CSCIRCLES_EMAIL', 'cscircles@uwaterloo.ca');
define('CSCIRCLES_ASST_EMAIL', 'csc-assistant@uwaterloo.ca');
define('CSCIRCLES_ASST_ID_DE', 11351);
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

// 'cx' parameter for google custom search; replace with your own
// for the search box to search your own site
//define('GSOOGLE_SEARCH_CX', '007230231723983473694:r0-95non7ri');

define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept
define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER
