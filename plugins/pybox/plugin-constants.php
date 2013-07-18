<?php

// P: path constant; U: url constant

// CHANGE THIS to URL of wordpress root (server part optional, with trailing slash)
define('UWPHOME',  preg_match('_^/dev(/|$)_i', $_SERVER['REQUEST_URI']) ? '/dev/' : '/');

// CHANGE THIS to URL of visualizer (or http://cscircles.cemc.uwaterloo.ca/visualize if not installed)
define('UVISUALIZER', UWPHOME . 'visualize/');
// CHANGE THIS to URL of your search page
define('USEARCH', UWPHOME . 'search/');

// path to wordpress (with trailing slash): up 3 directories from directory containing this file
define('PWP', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

// CHANGE THESE: where are safeexec and python3jail installed? 
define('PJAIL', PWP . '../../python3jail/'); // with trailing slash
define('PSAFEEXEC', PWP . '../../safeexec/safeexec'); // binary executable

// misc constants
define('CSCIRCLES_EMAIL', 'cscircles@uwaterloo.ca');
define('CSCIRCLES_ASST_EMAIL', 'csc-assistant@uwaterloo.ca');
define('CSCIRCLES_ASST_ID_DE', 11351);
define('CSCIRCLES_BOUNCE_EMAIL', 'bounces@cscircles.cemc.uwaterloo.ca');
define('CSCIRCLES_DEVELOPER_EMAIL', 'daveagp@gmail.com');
define('PCEMCCSC', 'http://www.cemc.uwaterloo.ca/resources/cscircles');

// optional reporting and exporting
define('PPYBOXLOG', PWP . '../../pybox_log.txt');
define('PEXPORT', PWP . '../../export/');

// normally the rest of this file should work without modifying below this line

// if UWPHOME is right, this should be ok too
define('UWPCONTENT', UWPHOME . 'wp-content/');

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


class PyboxException extends Exception {}
define('POSTLIMIT', 20000); //maximum size 'POST' that submit.php will accept
define('WALLFACTOR', 2); 
define('WALLBUFFER', 4); // if the cpu limit for a problem is X, walltime limit is FACTOR*X + BUFFER


global $pb_translation;

// end of file