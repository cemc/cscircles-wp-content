<?php

  // this file contains things you might need to personalize

define('PRODUCTION_LANGUAGES', serialize(array('en', 'fr', 'de', 'nl', 'lt', 'zh')));
define('DEVELOPMENT_LANGUAGES', serialize(array('pl', 'tr')));

// user id of cscircles assistant for each language, defaults to dave/troy/sandy (0)
// 2 should change
define('CSCIRCLES_ASST_ID_MAP', 
       serialize(array('de' => 11351, 
                       'nl' => 25033, 
                       'lt' => 40943,
                       'fr' => 147,
                       //7648 79886
                       'zh' => 79886)));

// optional reporting and exporting.
// if you want logging to work, you must define one of the two
// PYBOXLOG constants
define ('ON_CEMC_SERVER', UWPHOME == 'https://cscircles.cemc.uwaterloo.ca/'
        || UWPHOME == 'https://cemclinux11.math.uwaterloo.ca/'
        || UWPHOME == 'https://cscircles.cemc.uwaterloo.ca/dev/');
if (ON_CEMC_SERVER) {
  // if you want some of these, remove them from the 'if' block
  define('PYBOXLOG_EMAIL', 'daveagp@gmail.com');        // e-mail notifications for logging
  define('PPYBOXLOG', ABSPATH . '../../pybox_log.txt'); // file, writeable by apache, for logging
  define('PEXPORT', ABSPATH . '../../export/');         // export directory
}

// messages sent by CS Circles will have this return address:
//define('CSCIRCLES_BOUNCE_EMAIL', 'bounces@cscircles.cemc.uwaterloo.ca'); 
define('CSCIRCLES_BOUNCE_EMAIL', 'cscircles.noreply@uwaterloo.ca'); 
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

