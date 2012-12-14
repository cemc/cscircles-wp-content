<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * WP_Scoped_User PHP class for the WordPress plugin Role Scoper
 * role-scoper.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 * NOTE: this skeleton edition of WP_Scoped_User is loaded for anonymous users to reduce mem usage
 *
 */

if ( ! class_exists('WP_Scoped_User_Anon') ) {
class WP_Scoped_User_Anon extends WP_User { // Special skeleton class for ANONYMOUS USERS

	// note: these arrays are flipped (data stored in key) for better searching performance
	var $groups = array(); 				// 	$groups[group id] = 1
	var $has_user_roles = false;
	var $blog_roles = array(); 			//  $blog_roles[role_handle] = 1
	var $term_roles = array();			//	$term_roles[taxonomy][role_handle] = array of term ids 
	var $assigned_blog_roles = array(); //  $assigned_blog_roles[role_handle] = 1
	var $assigned_term_roles = array();	//	$assigned_term_roles[taxonomy][role_handle] = array of term ids 
	var $qualified_terms = array();		//  $qualified_terms[taxonomy][$capreqs_key] = previous result for qualify_terms call on this set of capreqs
	var $is_administrator;				//  cut down on unnecessary filtering by assuming that if a user can activate plugins, they can do anything
	var $is_module_administrator = array();
	
	function WP_Scoped_User_Anon() {
		if ( method_exists( $this, 'WP_User' ) ) {
			$this->WP_User(0, '');
		} else {
			parent::__construct(0, '');
		}

		// initialize blog_roles arrays
		$this->blog_roles[ANY_CONTENT_DATE_RS] = array();
	}

	// should not be used for anon user, but leave to maintain API
	function get_user_clause($table_alias) {
		$table_alias = ( $table_alias ) ? "$table_alias." : '';
		return " AND {$table_alias}user_id = '-1'";  // use -1 here to ignore accidental storage of other groups for zero user_id
	}
	
	function cache_get($cache_flag) {
		$cache_id = -1;
		$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER;

		return wpp_cache_get($cache_id, $cache_flag);
	}
	
	function cache_set($entry, $cache_flag) {
		$cache_id = -1;
		$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER;
		
		return wpp_cache_set($cache_id, $entry, $cache_flag);
	}
		
	function cache_force_set( $entry, $cache_flag, $append_blog_suffix = true ) {
		return $this->cache_set( $entry, $cache_flag, $append_blog_suffix, true );
	}
	
	function get_groups_for_user( $user_id, $args = array() ) {
		return array();
		
		if ( empty($args['no_cache']) ) {
			// use -1 here to ignore accidental storage of other groups for zero user_id
			$cache = wpp_cache_get( -1, 'group_membership_for_user' );
			if ( is_array($cache) )
				return $cache;
		}

		global $wpdb;
		
		if ( empty($wpdb->groups_rs) )
			return array();

		// include WP metagroup for anonymous user
		$user_groups = scoper_get_col( "SELECT $wpdb->groups_id_col FROM $wpdb->groups_rs WHERE {$wpdb->groups_rs}.{$wpdb->groups_meta_id_col} = 'wp_anon'" );

		if ( $user_groups && empty($args['no_cache']) ) {  // users should always be in at least a metagroup.  Problem with caching empty result on user creation beginning with WP 2.8
			$user_groups = array_fill_keys($user_groups, 1);
			
			wpp_cache_set( -1, $user_groups, 'group_membership_for_user' );
		}

		return $user_groups;
	}
	
	// return group_id as array keys
	function _get_usergroups($args = array()) {
		return WP_Scoped_User_Anon::get_groups_for_user( -1 );
	}
	
	function get_blog_roles( $role_type = 'rs' ) {
		return array();
	}
	
	function get_blog_roles_daterange( $role_type = 'rs', $include_role_duration_key = false ) {
		return array( '' => array() );
	}
	
	// returns array[role name] = array of term ids for which user has the role assigned (based on current role basis)
	function get_term_roles( $taxonomy = 'category', $role_type = 'rs' ) {
		return array();
	}
	
	function get_term_roles_daterange( $taxonomy = 'category', $role_type = 'rs', $include_role_duration_key = false ) {
		$this->term_roles[$taxonomy] = array( '' => array() );
		return array( '' => array() );
	}
	
	function merge_scoped_blogcaps() {	
	}
	
} // end class WP_Scoped_User
}
?>