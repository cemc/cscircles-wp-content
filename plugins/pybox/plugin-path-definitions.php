<?php

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
else {
  if (strpos($currenturi, 'swordpress') != false) {
    define('UWPHOME', '/~cscircles/swordpress/');
    define('PB_DEV', TRUE);
  }
  else { //default
    define('UWPHOME', '/~cscircles/wordpress/');
    define('PB_DEV', FALSE); 
  }
 }

if (PB_DEV) 
  define('PLOCALHOME', '/home/cscircles/dev/');
 else
   define('PLOCALHOME', '/home/cscircles/live/');

define('PWP', PLOCALHOME . 'www/wordpress/');

define('PCEMCCSC', 'http://www.cemc.uwaterloo.ca/resources/cscircles');

// path constants :: should exist, and in many cases be writable
define('PJAIL', PLOCALHOME . 'python3jail/');
define('PPYTHON3MODJAIL', '/python3');
define('PSCRATCHDIRMODJAIL', 'scratch/');

define('PDATADIR', PLOCALHOME . 'py_includes/');
define('PPYBOXLOG', PLOCALHOME . 'pybox_log.txt');

define('PPYBOXDIR', PWP . 'wp-content/plugins/pybox/');
define('PSAFEEXEC', PLOCALHOME . 'safeexec/safeexec');

define('PWP_LOADER', PWP . 'wp-load.php');
// PWP_LOADER is used for external access to wordpress functions
// http://codex.wordpress.org/Integrating_WordPress_with_Your_Website

// URL constants
define('UFILES', UWPHOME . 'static/');

define('UFAVICON', UFILES . 'favicon.ico');
define('UWARN', UFILES . 'warning.png');

define('UPYBOX', UWPHOME . 'wp-content/plugins/pybox/');

define('UPYBOXJS', UPYBOX . 'pybox.js?8');
define('UPYBOXCSS', UPYBOX . 'pybox.css?3');
define('UDEBUGPHP', UPYBOX . 'pages/problem-options.php');
define('UOLDHISTORY', UPYBOX . 'pages/problem-history.php?p=');

define('USUBMIT', UPYBOX . 'action-submit-code.php');
define('USETCOMPLETED', UPYBOX . 'action-notify-complete.php');
define('UMESSAGE', UPYBOX . 'action-send-message.php');

define('UFULLHISTORY', UPYBOX . 'db-entire-history.php');
define('UHISTORY', UPYBOX . 'db-problem-history.php');
define('UFLEXIGRID', UPYBOX . 'db-flexigrid/');

define('USERVER' , 'http://cscircles.cemc.uwaterloo.ca');

define('USEARCH', UWPHOME . 'search/');
define('UMAIL', UWPHOME . 'mail/');
define('URESOURCES', UWPHOME . 'resources/');
define('UCONSOLE', UWPHOME . 'console/'); /*last / is Important to get newlines right in GET */
define('UVISUALIZE', UWPHOME . 'visualize/'); 
define('UUSAGE', UWPHOME . 'using-this-website/');
define('UCONTACT', UWPHOME . 'contact/');
define('URUNATHOME', UWPHOME . 'run-at-home/');

// end of file