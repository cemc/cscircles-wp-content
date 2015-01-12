<?php

if (defined('ABSPATH')) {
  echo "Error, WordPress is already loaded!";
  debug_print_backtrace();
  exit(0);
 }

// used for external/ajax access to wordpress functions
// http://codex.wordpress.org/Integrating_WordPress_with_Your_Website

// from this file (include-to-load-wp.php), look at the containing directory
// (pybox) and up 3 more (plugins, wp-content, wordpress) for wp-load.php 
// we can't just use ABSPATH because it's not defined (an ajax call is in progress)
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
// now wordpress is loaded. Huzzah!

add_filter( 'locale', 'set_my_locale' );
function set_my_locale( $lang ) {
  if (array_key_exists('lang', $_REQUEST))
    return $_REQUEST['lang'];
  return $lang;
}

// we only need this for ajax calls:
/*if (array_key_exists('polylang', $GLOBALS)
    // but not for the visualizer:
    && !defined("DO_NOT_LOAD_POLYLANG_TEXTDOMAINS"))
  $GLOBALS['polylang']->load_textdomains();
*/

// this is to avoid errors shown if WP_DEBUG is on
remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
add_action( 'shutdown', 'tweaked_wp_ob_end_flush_all', 1 );

function tweaked_wp_ob_end_flush_all() {
	$levels = ob_get_level();
	for ($i=0; $i<$levels; $i++)
		@ob_end_flush();
}

// end of file
