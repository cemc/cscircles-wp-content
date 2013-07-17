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

register_activation_hook(__FILE__, 'pybox_database_install');
// for information about upgrading see
// http://codex.wordpress.org/Creating_Tables_with_Plugins

function pybox_database_install () {
  pyboxlog("running pybox_database_install");
  global $wpdb;

  // we use dbDelta since it is more compatible with upgrades
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
  // we create indexes with direct queries since "Duplicate key name" works in our favour

  $table_name = $wpdb->prefix . "pb_completed";

  $sql = "CREATE TABLE " . $table_name . " (
userid integer, 
problem text,
time timestamp
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdp->prefix."pb_completed (userid, problem (16));");
  $wpdb->query("create index pb_index_problem on ".$wpdb->prefix."pb_completed (problem (16));");

  $table_name = $wpdb->prefix . "pb_submissions";
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
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_submissions (userid, problem (16), beginstamp);");
  $wpdb->query("create index pb_index_problem on ".$wpdb->prefix."pb_submissions (problem (16));");

  $table_name = $wpdb->prefix . "pb_lessons";
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
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_lessons (lang (2), ordering);");
  dbDelta($sql);

  $table_name = $wpdb->prefix . "pb_problems";
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
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_problems (hash (32));");
  $wpdb->query("create index pb_index_named on ".$wpdb->prefix."pb_problems (lang (2), slug (16));");
  
  $table_name = $wpdb->prefix . "pb_profiling";
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
  dbDelta($sql);

  $table_name = $wpdb->prefix . "pb_mail";
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
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_mail (uto, unanswered);");
  $wpdb->query("create index pb_index_subject on ".$wpdb->prefix."pb_mail (ustudent, problem (16), ID);");
  $wpdb->query("create index pb_index_problem on ".$wpdb->prefix."pb_mail (problem (16));");

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
require_once("shortcode-export-submissions.php");
require_once("shortcode-vis.php");
require_once("plugin-footer-prevnext.php");
require_once("plugin-hooks.php");
require_once("plugin-profile-options.php");
require_once("js-translation.php");
require_once("newuseremail.php");
require_once("css-admin.php");

// end of file