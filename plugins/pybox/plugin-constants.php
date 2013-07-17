<?php

class PyboxException extends Exception {}
define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept
define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER


// P: path constant; U: url constant

// UWPHOME, url to wordpress base (server part optional)
// PWP, local path to wordpress base
// PLOCALHOME, used just by cemc for our setup, optional

$dev = preg_match('_^/dev(/|$)_i', $_SERVER['REQUEST_URI']);

define('UWPHOME',  $dev ? '/dev/' : '/');
define('PLOCALHOME', '/home/cscircles/' . ($dev ? 'dev/' : 'live/'));
define('PWP', PLOCALHOME . 'www/wordpress/');

// if UWPHOME is right, this should be ok too
define('UWPCONTENT', UWPHOME . 'wp-content/');

define('UVISUALIZER', UWPHOME . 'visualize/');
define('USEARCH', UWPHOME . 'search/');

// where are safeexec and python3jail installed? 
define('PJAIL', PLOCALHOME . 'python3jail/');
define('PSAFEEXEC', PLOCALHOME . 'safeexec/safeexec');

// misc constants
define('CSCIRCLES_EMAIL', 'cscircles@uwaterloo.ca');
define('CSCIRCLES_ASST_EMAIL', 'csc-assistant@uwaterloo.ca');
define('CSCIRCLES_ASST_ID_DE', 11351);
define('CSCIRCLES_BOUNCE_EMAIL', 'bounces@cscircles.cemc.uwaterloo.ca');
define('CSCIRCLES_DEVELOPER_EMAIL', 'daveagp@gmail.com');
define('PCEMCCSC', 'http://www.cemc.uwaterloo.ca/resources/cscircles');

// normally the rest of this file should work without modifying below this line

// optional reporting
if (defined('PLOCALHOME')) {
  define('PPYBOXLOG', PLOCALHOME . 'pybox_log.txt');
  define('PEXPORT', PLOCALHOME . 'export/');
 }

// according to default python3jail setup
define('PPYTHON3MODJAIL', '/python3');
define('PSCRATCHDIRMODJAIL', 'scratch/');

define('PPYBOXDIR', PWP . 'wp-content/plugins/pybox/');
define('PDATADIR', PWP . 'wp-content/lesson_files/');

define('PWP_LOADER', PWP . 'wp-load.php');
// PWP_LOADER is used for external access to wordpress functions
// http://codex.wordpress.org/Integrating_WordPress_with_Your_Website

// URL constants
define('UCODEMIRROR2', UWPCONTENT . 'CodeMirror2/');

define('UPYBOX', UWPCONTENT . 'plugins/pybox/');
define('UFILES', UPYBOX . 'files/');
define('UFAVICON', UFILES . 'favicon.ico');
define('UWARN', UFILES . 'warning.png');

define('UPYBOXJS', UPYBOX . 'pybox.js?28');
define('UPYBOXCSS', UPYBOX . 'pybox.css?17');
define('UDEBUGPHP', UPYBOX . 'pages/problem-options.php');

define('USUBMIT', UPYBOX . 'action-submit-code.php');
define('USETCOMPLETED', UPYBOX . 'action-notify-complete.php');
define('UMESSAGE', UPYBOX . 'action-send-message.php');

define('UFULLHISTORY', UPYBOX . 'db-entire-history.php');
define('UHISTORY', UPYBOX . 'db-problem-history.php');
define('UDBMAIL', UPYBOX . 'db-mail.php');
define('UDBPREFIX', UPYBOX . 'db-');
define('UFLEXIGRID', UPYBOX . 'db-flexigrid/');

global $pb_translation;

// end of file