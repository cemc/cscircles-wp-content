<?php
  /**
   *     Plugin Name:  Python in a Box
   *     Plugin URI:   
   *     Description:  Auto-judger for python via shortcodes
   *     Version:      20100613
   *     Author:       CEMC CS Circles
   *     Author URI:   
   */

  // this is the first file that WordPress calls to activate and use the plugin.
  // you should not include it yourself; use include-me.php instead.

require_once("include-me.php");

function pybox_database_install () {
  global $wpdb;
  pyboxlog("running pybox_database_install");
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  $table_name = $wpdb->prefix . "pb_completed";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {  
      $sql = "CREATE TABLE " . $table_name . " (
userid integer, 
problem text,
time timestamp
);";
      $result = dbDelta($sql);
      $result = dbDelta("create index pb_index on wp_pb_completed (userid, problem (16));");
      $result = dbDelta("create index pb_index_problem on wp_pb_completed (problem (16));");
  }

  $table_name = $wpdb->prefix . "pb_submissions";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {      
      $sql = "CREATE TABLE " . $table_name . " (
ID INT NOT NULL AUTO_INCREMENT,
beginstamp datetime,
endstamp timestamp, 
userid integer,
problem text,
hash text,
usercode text,
userinput text,
result text,
ipaddress text,
postmisc text,
referer text,
PRIMARY KEY (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
      $result = dbDelta($sql);
      $result = dbDelta("create index pb_index on wp_pb_submissions (userid, problem (16), beginstamp);");
      $result = dbDelta("create index pb_index_problem on wp_pb_submissions (problem (16));");
  }

  $table_name = $wpdb->prefix . "pb_lessons";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $table_name . " (
major integer,
minor text,
ordering integer,
title text,
id integer,
number text,
lang text,
PRIMARY KEY (id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
      $result = dbDelta("create index pb_index on wp_pb_lessons (lang (2), ordering);");
      $result = dbDelta($sql);
  }

  $table_name = $wpdb->prefix . "pb_problems";
  if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $table_name . " (
postid integer,
lesson integer,
boxid integer,
slug text,
type text,
facultative boolean,
shortcodeArgs text,
graderArgs text,
hash text,
url text,
publicname text,
content text,
lang text
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
      $result = dbDelta($sql);
      $result = dbDelta("create index pb_index on wp_pb_problems (hash (32));");
      $result = dbDelta("create index pb_index_named on wp_pb_problems (lang (2), slug (16));");
  }
  
  $table_name = $wpdb->prefix . "pb_profiling";
  if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    $sql = "CREATE TABLE " . $table_name . " (
ID integer NOT NULL AUTO_INCREMENT,
activity text,
start datetime,
preciseStart text,
preciseEnd text,
userid integer,
duration decimal(20, 10),
crossref integer,
parent integer,
meta text,
primary key (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
      $result = dbDelta($sql);
  }

  $table_name = $wpdb->prefix . "pb_mail";
  if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    $sql = "CREATE TABLE " . $table_name . " (
ID integer NOT NULL AUTO_INCREMENT,
ustudent integer,
ufrom integer,
uto integer,
problem text,
body text,
time timestamp CURRENT_TIMESTAMP,
unanswered boolean,
primary key (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
      $result = dbDelta($sql);
      $result = dbDelta("create index pb_index on wp_pb_mail (uto, unanswered);");
      $result = dbDelta("create index pb_index_subject on wp_pb_mail (ustudent, problem (16), ID);");
      $result = dbDelta("create index pb_index_problem on wp_pb_mail (problem (16));");
  }

}

register_activation_hook(__FILE__, 'pybox_database_install');
// for information about upgrading see
// http://codex.wordpress.org/Creating_Tables_with_Plugins



function enable_more_buttons($buttons) {
  $buttons[] = 'sub';
  $buttons[] = 'sup';
  return $buttons;
  }
add_filter("mce_buttons", "enable_more_buttons");

remove_action( 'wp_head', 'feed_links', 2 ); 
// Don't display the links to the general feeds: Post and Comment Feed
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

add_filter("robots_txt", "domo_arigato");

function domo_arigato($output) {
  $output .= 'Disallow: /wp-content/plugins/pybox/';
  return $output;
}


require_once("shortcodes.php");
require_once("shortcodes-layout.php");
require_once("shortcode-my-progress.php");
require_once("shortcode-make-databases.php");
require_once("shortcode-youtube.php");
require_once("shortcode-mailpage.php");
require_once("shortcode-style.php");
require_once("shortcode-db-profiling.php");
require_once("shortcode-admin-user-list.php");
require_once("plugin-footer-prevnext.php");
require_once("plugin-hooks.php");
require_once("plugin-profile-options.php");
require_once("js-translation.php");
require_once("newuseremail.php");
#require_once("dbf-subs.php"); not in use
#require_once("dbf-completed.php"); not in use
require_once("css-admin.php");

// end of file