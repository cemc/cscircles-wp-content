<?php

class PyboxException extends Exception {}
define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept
define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER

$servername = $_SERVER['SERVER_NAME'];
$currenturi = $_SERVER['REQUEST_URI'];

if (strcasecmp($servername, 'cscircles.cemc.uwaterloo.ca') == 0) {
  if (strcasecmp($currenturi, '/dev') == 0 
      || strcasecmp(substr($currenturi, 0, 5), '/dev/') == 0) {
    define('UWPHOME', '/dev/');
    define('PB_DEV', TRUE);
  }
  else {
    define('UWPHOME', '/');
    define('PB_DEV', FALSE);
  }
}

if (PB_DEV) 
  define('PLOCALHOME', '/home/cscircles/dev/');
 else
   define('PLOCALHOME', '/home/cscircles/live/');

define('PWP', PLOCALHOME . 'www/wordpress/');

define('PCEMCCSC', 'http://www.cemc.uwaterloo.ca/resources/cscircles');


// string constants
define('CSCIRCLES_EMAIL', 'cscircles@uwaterloo.ca');
define('CSCIRCLES_ASST_EMAIL', 'csc-assistant@uwaterloo.ca');
define('CSCIRCLES_BOUNCE_EMAIL', 'bounces@cscircles.cemc.uwaterloo.ca');
define('CSCIRCLES_DEVELOPER_EMAIL', 'daveagp@gmail.com');


// path constants :: should exist, and in many cases be writable
define('PJAIL', PLOCALHOME . 'python3jail/');
define('PPYTHON3MODJAIL', '/python3');
define('PSCRATCHDIRMODJAIL', 'scratch/');

define('PDATADIR', PLOCALHOME . 'py_includes/');
define('PPYBOXLOG', PLOCALHOME . 'pybox_log.txt');

define('PPYBOXDIR', PWP . 'wp-content/plugins/pybox/');
define('PSAFEEXEC', PLOCALHOME . 'safeexec/safeexec');

define('PEXPORT', PLOCALHOME . 'export/');

define('PWP_LOADER', PWP . 'wp-load.php');
// PWP_LOADER is used for external access to wordpress functions
// http://codex.wordpress.org/Integrating_WordPress_with_Your_Website

// URL constants
define('UFILES', UWPHOME . 'static/');

define('UFAVICON', UFILES . 'favicon.ico');
define('UWARN', UFILES . 'warning.png');

define('UPYBOX', UWPHOME . 'wp-content/plugins/pybox/');

define('UPYBOXJS', UPYBOX . 'pybox.js?25');
define('UPYBOXCSS', UPYBOX . 'pybox.css?9');
define('UDEBUGPHP', UPYBOX . 'pages/problem-options.php');

define('USUBMIT', UPYBOX . 'action-submit-code.php');
define('USETCOMPLETED', UPYBOX . 'action-notify-complete.php');
define('UMESSAGE', UPYBOX . 'action-send-message.php');

define('UFULLHISTORY', UPYBOX . 'db-entire-history.php');
define('UHISTORY', UPYBOX . 'db-problem-history.php');
define('UDBMAIL', UPYBOX . 'db-mail.php');
define('UDBPREFIX', UPYBOX . 'db-');
define('UFLEXIGRID', UPYBOX . 'db-flexigrid/');

define('USERVER' , 'http://cscircles.cemc.uwaterloo.ca');

global $pb_translation;

// end of file