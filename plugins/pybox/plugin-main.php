<?php
  /**
   *     Plugin Name:  Python in a Box
   *     Plugin URI:   
   *     Description:  Auto-judger for python via shortcodes
   *     Version:      20100613
   *     Author:       CEMC CS Circles
   *     Author URI:   
   */

  // This is the first file that WordPress calls to activate and use the plugin.

  // Non-WordPress-called pages (ajax calls or direct .php views) must
  // include "include-to-load-wp.php" to get the plugin's functionality (and all of WP)

define('UWPHOME',  site_url('/') );
define('PWP',  ABSPATH );

if (FALSE === get_option('cscircles_pjail'))
  update_option('cscircles_pjail', '/path/to/python3jail/');
if (FALSE === get_option('cscircles_psafeexec'))
  update_option('cscircles_psafeexec', '/path/to/safeexec/safeexec');
if (!defined('PJAIL')) // allow overriding PJAIL in wp-config.php
  define('PJAIL', get_option('cscircles_pjail'));
if (!defined('PSAFEEXEC')) // allow overriding PSAFEEXEC in wp-config.php
  define('PSAFEEXEC', get_option('cscircles_psafeexec'));

require_once('plugin-config.php');
require_once('plugin-constants.php');
require_once('plugin-utilities.php'); 

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
  $wpdb->query("create index pb_index_problem on ".$wpdb->prefix."pb_completed (problem (16));");
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_completed (userid, problem (16));");

  $table_name = $wpdb->prefix . "pb_lessons";
  $sql = "CREATE TABLE " . $table_name . " (
major integer,
minor text,
ordering integer,
title text,
id integer,
number text,
lang text,
PRIMARY KEY  (id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_lessons (lang (2), ordering);");

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
primary key  (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_profiling (activity (32), start);");

  $table_name = $wpdb->prefix . "pb_mail";
  $sql = "CREATE TABLE " . $table_name . " (
ID integer NOT NULL AUTO_INCREMENT,
ustudent integer,
ufrom integer,
uto integer,
problem text,
body text,
time timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
unanswered boolean,
primary key  (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_mail (uto, unanswered);");
  $wpdb->query("create index pb_index_subject on ".$wpdb->prefix."pb_mail (ustudent, problem (16), ID);");
  $wpdb->query("create index pb_index_problem on ".$wpdb->prefix."pb_mail (problem (16));");
  $wpdb->query("create index pb_index_from on ".$wpdb->prefix."pb_mail (ufrom);");

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
result mediumtext,
ipaddress text,
postmisc text,
referer text,
PRIMARY KEY  (ID)
) CHARACTER SET utf8 COLLATE utf8_general_ci;";
  dbDelta($sql);
  $wpdb->query("create index pb_index on ".$wpdb->prefix."pb_submissions (userid, problem (16), beginstamp);");
  $wpdb->query("create index pb_index_problem on ".$wpdb->prefix."pb_submissions (problem (16));");
  $wpdb->query("create index pb_hash_result on ".$wpdb->prefix."pb_submissions (hash (40), result (2));");

}


require_once("sweetcodes.php");
require_once("shortcodes-exercises.php");
require_once("shortcodes-misc.php");
require_once("shortcodes-layout.php");
require_once("shortcode-my-progress.php");
require_once("shortcode-youtube.php");
require_once("shortcode-mailpage.php");
require_once("shortcode-style.php");
require_once("shortcode-db-profiling.php");
require_once("shortcode-admin-user-list.php");
require_once("shortcode-export-submissions.php");
require_once("shortcode-vis.php");
require_once("plugin-hooks.php");
require_once("plugin-errorhint.php");
require_once("plugin-new-user-email.php");
require_once("plugin-profile-options.php");
require_once("js-translation.php");
require_once("db-mail.php");
require_once("db-entire-history.php");
require_once("db-problem-history.php");
require_once("db-problem-summary.php");
require_once("admin-make-databases.php");
require_once("admin-students.php");
require_once("admin-options.php");
require_once("tool-visualizer.php");
//require_once("db-profiling.php"); kind of broken at the moment, but never used directly so ok

// end of file