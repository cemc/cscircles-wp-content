<?php

if (!defined('PYBOX_LOADED')) {
  define('PYBOX_LOADED', 'externally');
  require_once('plugin-constants.php');
  require_once(PWP_LOADER);
  if (!defined('DO_NOT_LOAD_POLYLANG_TEXTDOMAINS') && array_key_exists('polylang', $GLOBALS)) 
    $GLOBALS['polylang']->load_textdomains();
  require_once('plugin-utilities.php'); 
  // next two lines prevent error described at http://core.trac.wordpress.org/ticket/22430
  remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
  add_action( 'shutdown', 'tweaked_wp_ob_end_flush_all', 1 );
 }

function tweaked_wp_ob_end_flush_all() {
	$levels = ob_get_level();
	for ($i=0; $i<$levels; $i++)
		@ob_end_flush();
}

// end of file
