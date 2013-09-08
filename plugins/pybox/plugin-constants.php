<?php

// P: path constant

define('PPYBOXDIR', PWP . 'wp-content/plugins/pybox/');
define('PDATADIR', PWP . 'wp-content/lesson_files/');

// U: URL constant

define('UVISUALIZER', UWPHOME . 'visualize/');

define('UWPCONTENT', UWPHOME . 'wp-content/');
define('UPYBOX', UWPCONTENT . 'plugins/pybox/');
define('UCODEMIRROR3', UPYBOX . 'CodeMirror3/');
define('UFILES', UPYBOX . 'files/');
define('UFAVICON', UFILES . 'favicon.ico');
define('UWARN', UFILES . 'warning.png');

// +1 to ? number forces clients to reload instead of using cached
define('UPYBOXJS', UPYBOX . 'pybox.js?33');
define('UPYBOXCSS', UPYBOX . 'pybox.css?21');

define('UPROBLEMSOURCE', UPYBOX . 'page-problem-source.php');
define('UPAGESOURCE', UPYBOX . 'page-source.php');

define('USUBMIT', UPYBOX . 'action-submit-code.php');
define('USETCOMPLETED', UPYBOX . 'action-notify-complete.php');
define('UMESSAGE', UPYBOX . 'action-send-message.php');

define('UFULLHISTORY', UPYBOX . 'db-entire-history.php');
define('UHISTORY', UPYBOX . 'db-problem-history.php');
define('UDBMAIL', UPYBOX . 'db-mail.php');
define('UDBPREFIX', UPYBOX . 'db-');
define('UFLEXIGRID', UPYBOX . 'flexigrid/');

// other basic stuff

class PyboxException extends Exception {}
global $pb_translation;

// end of file