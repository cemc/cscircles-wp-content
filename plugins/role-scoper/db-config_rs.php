<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/role-scoper_init.php');
	
global $sitewide_groups;

if ( IS_MU_RS ) {
	if ( ! isset($sitewide_groups) )
		$sitewide_groups = scoper_establish_group_scope();
} else
	$sitewide_groups = false;
	
global $wpdb;

//table names for scoper-specific data; usually no reason to alter these
$wpdb->user2role2object_rs = $wpdb->prefix . 'user2role2object_rs'; 
$wpdb->role_scope_rs = $wpdb->prefix . 'role_scope_rs';

//default names for tables which might otherwise be replaced by existing groups & user2group tables from an external forum app
// (must be stored within the Wordpress DB)
$prefix = ( ! empty($wpdb->base_prefix) && $sitewide_groups ) ? $wpdb->base_prefix : $wpdb->prefix;

$wpdb->groups_basename =	'groups_rs';
$wpdb->groups_rs = 			$prefix . $wpdb->groups_basename; 

$wpdb->user2group_rs = 	$prefix . 'user2group_rs';


//default column names for groups table; may need to change if using another app's table
// (note, if no equivalent column exists in the existing table, scoper column can usually be created without bothering the other app)
$wpdb->groups_id_col = 		 'ID';
$wpdb->groups_name_col = 	 'group_name';
$wpdb->groups_descript_col = 'group_description';
$wpdb->groups_homepage_col = 'group_homepage';
$wpdb->groups_meta_id_col =  'group_meta_id';

//default column names for user2group table; may need to change if using another app's table
// (note, if no equivalent column exists in the existing table, scoper column can usually be created without bothering the other app)
$wpdb->user2group_gid_col = 		'group_id';
$wpdb->user2group_uid_col = 		'user_id';
$wpdb->user2group_assigner_id_col = 'assigner_id';
$wpdb->user2group_status_col 	  = 'status';

?>