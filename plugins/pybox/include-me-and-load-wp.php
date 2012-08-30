<?php

if (!defined('PYBOX_LOADED')) {
  define('PYBOX_LOADED', 'externally');
  require_once('plugin-constants.php');
  require_once(PWP_LOADER);
  $GLOBALS['polylang']->load_textdomains();
  require_once('plugin-utilities.php'); 
 }

// end of file
