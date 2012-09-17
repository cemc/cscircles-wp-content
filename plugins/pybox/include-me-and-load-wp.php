<?php

if (!defined('PYBOX_LOADED')) {
  define('PYBOX_LOADED', 'externally');
  require_once('plugin-constants.php');
  require_once(PWP_LOADER);
  if (!defined('DO_NOT_LOAD_POLYLANG_TEXTDOMAINS')) $GLOBALS['polylang']->load_textdomains();
  require_once('plugin-utilities.php'); 
 }

// end of file
