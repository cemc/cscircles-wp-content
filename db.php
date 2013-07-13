<?php

  /*
This is a pseudo-hack to enable both "development" and "live" sites
with a single database.

The issue: we would like to use the "pre_option_home" and 
"pre_option_siteurl" filters in order to make sure that get_option
correctly returns the right home/site urls. However, filters defined
in the plugin are too late, because the flow of wp-settings is:

- (line 66) require functions.php
- (line 155) call wp_plugin_directory_constants( ), which
 in turn calls get_option
- (line 194) load plugins

Since we only have control in wp-config.php (it loads wp-settings.php)
we need to define the filter between lines 66 and 155. This db.php 
drop-in happens to do just that.
  */

add_filter ( 'pre_option_home', 'test_localhosts' );
add_filter ( 'pre_option_siteurl', 'test_localhosts' );

function test_localhosts( ) {
  if (strcasecmp($_SERVER['REQUEST_URI'], '/dev') == 0
      || strcasecmp(substr($_SERVER['REQUEST_URI'], 0, 5), '/dev/') == 0)
    $ret = 'http://cscircles.cemc.uwaterloo.ca/dev';
  else
    $ret = 'http://cscircles.cemc.uwaterloo.ca';
  return $ret;
}

// end of file