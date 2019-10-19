<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
require_once( dirname(__FILE__).'/db-config_rs.php');
	
/**
 * WP_Scoped_User PHP class for the WordPress plugin Role Scoper
 * role-scoper.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */
if ( ! class_exists('WP_Scoped_User') ) {
class WP_Scoped_User extends WP_User {
	// note: these arrays are flipped (data stored in key) for better searching performance
	var $groups = array(); 				// 	$groups[group id] = 1
	var $blog_roles = array(); 			//  $blog_roles[date_key][role_handle] = 1
	var $term_roles = array();			//	$term_roles[taxonomy][date_key][role_handle] = array of term ids 
	var $assigned_blog_roles = array(); //  $assigned_blog_roles[role_handle] = 1
	var $assigned_term_roles = array();	//	$assigned_term_roles[taxonomy][role_handle] = array of term ids 
	var $qualified_terms = array();		//  $qualified_terms[taxonomy][$capreqs_key] = previous result for qualify_terms call on this set of capreqs
	
	function __construct($id = 0, $name = '', $args = array()) {
		//log_mem_usage_rs( 'begin WP_Scoped_User' );
		
		if ( method_exists( $this, 'WP_User' ) ) {
			$this->WP_User( $id, $name );
		} else {
			parent::__construct( $id, $name );
		}

		// without this, logged users have no read access to blogs they're not registered for
		if ( IS_MU_RS && $id && ! is_admin() && empty( $this->allcaps ) )
			$this->caps['subscriber'] = true;

		// initialize blog_roles arrays
		$this->assigned_blog_roles[ANY_CONTENT_DATE_RS] = array();
		$this->blog_roles[ANY_CONTENT_DATE_RS] = array();

		//log_mem_usage_rs( 'called this->WP_User' );
		
		$defaults = array( 'disable_user_roles' => false, 'disable_group_roles' => false, 'disable_wp_roles' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		if ( $this->ID || defined( 'SCOPER_ANON_METAGROUP' ) ) {
			if ( ! $disable_wp_roles ) {
				// include both WP roles and custom caps, which are treated as a hidden single-cap role capable of satisfying single-cap current_user_can calls
				$this->assigned_blog_roles[ANY_CONTENT_DATE_RS] = $this->caps;
			
				// prepend role_type prefix to wp rolenames
				global $wp_roles;
				foreach ( array_keys($this->assigned_blog_roles[ANY_CONTENT_DATE_RS]) as $name) {
					if ( isset($wp_roles->role_objects[$name]) ) {
						$this->assigned_blog_roles[ANY_CONTENT_DATE_RS]['wp_' . $name] = $this->assigned_blog_roles[ANY_CONTENT_DATE_RS][$name];
						unset($this->assigned_blog_roles[ANY_CONTENT_DATE_RS][$name]);
					}
				}
			}
			
			if ( defined('DEFINE_GROUPS_RS') && ! $disable_group_roles ) {
				$this->groups = $this->_get_usergroups();

				if ( ! empty($args['filter_usergroups']) )  // assist group admin
					$this->groups = array_intersect_key($this->groups, $args['filter_usergroups']);
			}
			
			// if ( RS_BLOG_ROLES ) {  // rs_blog_roles option has never been active in any RS release; leave commented here in case need arises
				if ( $rs_blogroles = $this->get_blog_roles_daterange( 'rs' ) ) {
					foreach ( array_keys($rs_blogroles) as $date_key ) {
						if ( isset($this->assigned_blog_roles[$date_key]) )
							$this->assigned_blog_roles[$date_key] = array_merge($this->assigned_blog_roles[$date_key], $rs_blogroles[$date_key]);
						else
							$this->assigned_blog_roles[$date_key] = $rs_blogroles[$date_key];
					}
				}
			//}

			// note: The allcaps property still governs current_user_can calls when the cap requirements do not pertain to a specific object.
			// If WP roles fail to provide all required caps, the Role Scoper has_cap filter validates the current_user_can check if any RS blogrole has all the required caps.
			//
			// The blog_roles array also comes into play for object permission checks such as page or post listing / edit.  
			// In such cases, roles in the Scoped_User->blog_roles array supplement any pertinent taxonomy or role assignments,
			// as long as the object or its terms are not configured to require that role to be term-assigned or object-assigned.

			//log_mem_usage_rs( 'new Scoped User done' );
			
			add_filter('user_has_cap', array(&$this, 'reinstate_caps'), 99, 3);
		}
	}
	
	function check_for_user_roles() {
		if ( IS_MU_RS )
			return true;	// this function is only for performance; not currently dealing with multiple uro tables
		
		global $wpdb;
		
		return scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE role_type = 'rs' AND user_id = '$this->ID' LIMIT 1");
	}
	
	function get_user_clause($table_alias) {
		$table_alias = ( $table_alias ) ? "$table_alias." : '';
		
		$arr = array();
		
		if ( GROUP_ROLES_RS && $this->groups )
			$arr []= "{$table_alias}group_id IN ('" . implode("', '", array_keys($this->groups) ) . "')";
		
		if ( USER_ROLES_RS || empty($arr) ) // too risky to allow query with no user or group clause
			$arr []= "{$table_alias}user_id = '$this->ID'";
			
		$clause = implode( ' OR ', $arr );
		
		if ( count($arr) > 1 )
			$clause = "( $clause )";
		
		if ( $clause )
			return " AND $clause";
	}
	
	function cache_get($cache_flag, $append_blog_suffix = true ) {
		if ( GROUP_ROLES_RS && $this->groups ) {
			$cache_id = $this->ID;	
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER_AND_GROUPS;
		} else {
			$cache_id = $this->ID;
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER;
		}
		
		return wpp_cache_get($cache_id, $cache_flag, $append_blog_suffix);
	}
	
	function cache_set($entry, $cache_flag, $append_blog_suffix = true, $force_update = false ) {
		if ( GROUP_ROLES_RS && $this->groups ) {
			$cache_id = $this->ID;	
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER_AND_GROUPS;
		} else {
			$cache_id = $this->ID;
			$cache_flag = $cache_flag . '_for_' . ROLE_BASIS_USER;
		}

		return wpp_cache_set($cache_id, $entry, $cache_flag, 0, $append_blog_suffix, $force_update );
	}
		
	function cache_force_set( $entry, $cache_flag, $append_blog_suffix = true ) {
		return $this->cache_set( $entry, $cache_flag, $append_blog_suffix, true );
	}

	public static function _get_groups_for_user( $user_id, $args = array() ) {
		if ( empty($args['status']) )
			$status = 'active';
		elseif ( 'any' == $args['status'] ) {
			$args['no_cache'] = true;
			$status = '';
		} else {
			$args['no_cache'] = true;
			$status = $args['status'];
		}
	
		if ( empty($args['no_cache']) ) {
			$cache = wpp_cache_get($user_id, 'group_membership_for_user');
			
			if ( is_array($cache) )
				return $cache;
		}

		global $wpdb;
		
		if ( ! $wpdb->user2group_rs )
			return array();

		if ( ! $user_id ) {
			// include WP metagroup for anonymous user
			return array_fill_keys( scoper_get_col( "SELECT $wpdb->groups_id_col FROM $wpdb->groups_rs WHERE {$wpdb->groups_rs}.{$wpdb->groups_meta_id_col} = 'wp_anon'" ), 'true' );
		}
			
		$status_clause = ( $status ) ? "AND status = '$status'" : '';	

		$query = "SELECT $wpdb->user2group_gid_col FROM $wpdb->user2group_rs WHERE $wpdb->user2group_uid_col = '$user_id' $status_clause ORDER BY $wpdb->user2group_gid_col";
		if ( ! $user_groups = scoper_get_col($query) )
			$user_groups = array();

		// include WP metagroup(s) for WP blogrole(s)
		$metagroup_ids = array();
		if ( ! empty($args['metagroup_roles']) ) {
			foreach ( array_keys($args['metagroup_roles']) as $role_handle )
				$metagroup_ids []= 'wp_role_' . str_replace( 'wp_', '', $role_handle );
		}

		if ( $metagroup_ids ) {
			$meta_id_in = "'" . implode("', '", $metagroup_ids) . "'";

			$query = "SELECT $wpdb->groups_id_col FROM $wpdb->groups_rs"
			. " WHERE {$wpdb->groups_rs}.{$wpdb->groups_meta_id_col} IN ($meta_id_in)"
			. " ORDER BY $wpdb->groups_id_col";
		
			if ( $meta_groups = scoper_get_col($query) )
				$user_groups = array_merge( $user_groups, $meta_groups );
		}
	
		if ( $user_groups && empty($args['no_cache']) ) {  // users should always be in at least a metagroup.  Problem with caching empty result on user creation beginning with WP 2.8
			$user_groups = array_fill_keys($user_groups, 1);

			wpp_cache_set($user_id, $user_groups, 'group_membership_for_user');
		}

		return $user_groups;
	}
	
	// can be called statically by external modules
	function get_groups_for_user( $user_id, $args = array() ) {
		return self::_get_groups_for_user( $user_id, $args );
	}
	
	// return group_id as array keys
	function _get_usergroups($args = array()) {
		if ( ! $this->ID && ! defined( 'SCOPER_ANON_METAGROUP' ) )
			return array();

		$args = (array) $args;
		
		if ( ! empty($this->assigned_blog_roles) )
			$args['metagroup_roles'] = $this->assigned_blog_roles[ANY_CONTENT_DATE_RS];
			
		$user_groups = self::_get_groups_for_user( $this->ID, $args );
		
		return $user_groups;
	}
	
	// wrapper for back compat with callin code that does not expect date_key dimension
	function get_blog_roles( $role_type = 'rs' ) {
		$blog_roles = $this->get_blog_roles_daterange( $role_type );

		if ( isset($blog_roles[ANY_CONTENT_DATE_RS]) && is_array($blog_roles[ANY_CONTENT_DATE_RS]) )
			return $blog_roles[ANY_CONTENT_DATE_RS];
		else
			return array();
	}
	
	function get_blog_roles_daterange( $role_type = 'rs', $args = array() ) {
		$defaults = array( 'enforce_duration_limits' => true, 'retrieve_content_date_limits' => true, 'include_role_duration_key' => false, 'no_cache' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		if ( $enforce_duration_limits = $enforce_duration_limits && scoper_get_option( 'role_duration_limits' ) ) {
			$duration_clause = ( $enforce_duration_limits ) ? scoper_get_duration_clause() : '';
			$no_cache = $no_cache || strpos( $duration_clause, 'start_date_gmt' ) || strpos( $duration_clause, 'end_date_gmt' );
		} else {
			$duration_clause = '';
		}
		
		$no_cache = $no_cache || $include_role_duration_key || ! $retrieve_content_date_limits;

		if ( ! $no_cache ) {
			$cache_flag = "{$role_type}_blog-roles";		// changed cache key from "blog_roles" to "blog-roles" to prevent retrieval of arrays stored without date_key dimension
			$cache = $this->cache_get( $cache_flag );
			if ( is_array($cache) ) {
				return $cache;
			}
		}

		global $wpdb;
		
		$u_g_clause = $this->get_user_clause('uro');
		
		$extra_cols = ( $include_role_duration_key ) ? ", uro.date_limited, uro.start_date_gmt, uro.end_date_gmt" : '';
		
		$qry = "SELECT uro.role_name, uro.content_date_limited, uro.content_min_date_gmt, uro.content_max_date_gmt $extra_cols FROM $wpdb->user2role2object_rs AS uro WHERE uro.scope = 'blog' AND uro.role_type = %s $duration_clause $u_g_clause";
		$results =  scoper_get_results( $wpdb->prepare( $qry, $role_type ) );

		$role_handles = array( '' => array() );
		
		foreach ( $results as $row ) {
			$date_key = ( $retrieve_content_date_limits && $row->content_date_limited ) ? serialize( (object) array( 'content_min_date_gmt' => $row->content_min_date_gmt, 'content_max_date_gmt' => $row->content_max_date_gmt ) ) : '';	
			
			if ( $include_role_duration_key ) {
				$role_duration_key = ( $row->date_limited ) ? serialize( (object) array( 'start_date_gmt' => $row->start_date_gmt, 'end_date_gmt' => $row->end_date_gmt ) ) : '';
				$role_handles[$role_duration_key][$date_key] [ scoper_get_role_handle( $row->role_name, $role_type ) ] = true;
			} else
				$role_handles[$date_key] [ scoper_get_role_handle( $row->role_name, $role_type ) ] = true;
		}

		if ( ! $no_cache )
			$this->cache_set($role_handles, $cache_flag);
		
		return $role_handles;
	}
	
	// wrapper for back compat with calling code that does not expect date_key dimension
	function get_term_roles( $taxonomy = 'category', $role_type = 'rs' ) {
		$term_roles = $this->get_term_roles_daterange( $taxonomy, $role_type );
		
		if ( isset($term_roles[ANY_CONTENT_DATE_RS]) && is_array($term_roles[ANY_CONTENT_DATE_RS]) )
			return $term_roles[ANY_CONTENT_DATE_RS];
		else
			return array();
	}

	// returns array[role name] = array of term ids for which user has the role assigned (based on current role basis)
	function get_term_roles_daterange( $taxonomy = 'category', $role_type = 'rs', $args = array() ) {
		$defaults = array( 'enforce_duration_limits' => true, 'retrieve_content_date_limits' => true, 'include_role_duration_key' => false, 'no_cache' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
			
		$taxonomy = sanitize_key( $taxonomy );
		
		global $wpdb;
		
		if ( $enforce_duration_limits = $enforce_duration_limits && scoper_get_option( 'role_duration_limits' ) ) {
			$duration_clause = ( $enforce_duration_limits ) ? scoper_get_duration_clause() : '';
			$no_cache = $no_cache || strpos( $duration_clause, 'start_date_gmt' ) || strpos( $duration_clause, 'end_date_gmt' );
		} else {
			$duration_clause = '';
		}
		
		$no_cache = $no_cache || $include_role_duration_key || ! $retrieve_content_date_limits;

		if ( ! $no_cache ) {
			$cache_flag = "{$role_type}_term-roles_{$taxonomy}";	// changed cache key from "term_roles" to "term-roles" to prevent retrieval of arrays stored without date_key dimension
		
			$tx_term_roles = $this->cache_get($cache_flag);
		} else 
			$tx_term_roles = '';
			
		if ( ! is_array($tx_term_roles) ) {
			// no need to check for this on cache retrieval, since a role_type change results in a rol_defs change, which triggers a full scoper cache flush
			$tx_term_roles = array( '' => array() );
			
			$u_g_clause = $this->get_user_clause('uro');

			$extra_cols = ( $include_role_duration_key ) ? ", uro.date_limited, uro.start_date_gmt, uro.end_date_gmt" : '';
			
			$qry = "SELECT uro.obj_or_term_id, uro.role_name, uro.assignment_id, uro.content_date_limited, uro.content_min_date_gmt, uro.content_max_date_gmt $extra_cols FROM $wpdb->user2role2object_rs AS uro ";
			$qry .= "WHERE uro.scope = 'term' AND uro.assign_for IN ('entity', 'both') AND uro.role_type = 'rs' AND uro.src_or_tx_name = '$taxonomy' $duration_clause $u_g_clause";
			
			if ( $results = scoper_get_results($qry) ) {
				foreach($results as $termrole) {
					$date_key = ( $retrieve_content_date_limits && $termrole->content_date_limited ) ? serialize( (object) array( 'content_min_date_gmt' => $termrole->content_min_date_gmt, 'content_max_date_gmt' => $termrole->content_max_date_gmt ) ) : '';
					
					$role_handle = 'rs_' . $termrole->role_name;
					
					if ( $include_role_duration_key ) {
						$role_duration_key = ( $termrole->date_limited ) ? serialize( (object) array( 'start_date_gmt' => $termrole->start_date_gmt, 'end_date_gmt' => $termrole->end_date_gmt ) ) : '';
						$tx_term_roles[$role_duration_key][$date_key][$role_handle][] = $termrole->obj_or_term_id;
					} else
						$tx_term_roles[$date_key][$role_handle][] = $termrole->obj_or_term_id;
				}
			}
			
			if ( ! $no_cache )
				$this->cache_set($tx_term_roles, $cache_flag);
		}
		
		if ( $retrieve_content_date_limits && ! $include_role_duration_key ) {  // normal usage (only internal call to skip this block is for user profile)
			$this->assigned_term_roles[$taxonomy] = $tx_term_roles;

			global $scoper;
			if ( ! empty($scoper) ) { // this method is only called after Scoper is initialized, but include this sanity check
				foreach( array_keys($this->assigned_term_roles[$taxonomy]) as $date_key ) {
					// strip out any assignments for roles which are no longer defined (such as Revisionary roles after Revisionary is deactivated)
					$this->assigned_term_roles[$taxonomy][$date_key] = array_intersect_key( $this->assigned_term_roles[$taxonomy][$date_key], $scoper->role_defs->role_caps );
					
					$this->term_roles[$taxonomy][$date_key] = $scoper->role_defs->add_contained_term_roles( $this->assigned_term_roles[$taxonomy][$date_key] );
				}
				
				// support legacy template code using $current_user->term_roles or $current_user->assigned_term_roles
				if ( ! awp_ver( '3.3-dev' ) ) {
					if ( $this->ID == $GLOBALS['current_user']->ID ) {
						$GLOBALS['current_user']->assigned_term_roles[$taxonomy] = $this->assigned_term_roles[$taxonomy];
						$GLOBALS['current_user']->term_roles[$taxonomy] = $this->term_roles[$taxonomy];
					}
				}
			}
		}
		
		return $tx_term_roles;
	}
	
	
	function merge_scoped_blogcaps() {	
		global $scoper;

		// strip out any assignments for roles which are no longer defined (such as Revisionary roles after Revisionary is deactivated)
		foreach( array_keys($this->assigned_blog_roles) as $date_key ) 
			$this->assigned_blog_roles[$date_key] = array_intersect_key( $this->assigned_blog_roles[$date_key], $scoper->role_defs->role_caps );

		foreach( array_keys($this->assigned_blog_roles[ANY_CONTENT_DATE_RS]) as $role_handle ) {
			if ( ! is_array($scoper->role_defs->role_caps[$role_handle]) )
				continue;
			
			$role_spec = scoper_explode_role_handle($role_handle);

			if ( ! empty($role_spec->role_type) && ( 'rs' == $role_spec->role_type ) && ! empty($scoper->role_defs->role_caps[$role_handle]) )
				$this->allcaps = ( is_array($this->allcaps) ) ? array_merge($this->allcaps, $scoper->role_defs->role_caps[$role_handle]) : $scoper->role_defs->role_caps[$role_handle];	
		}
		
		$this->allcaps['is_scoped_user'] = true; // use this to detect when something tampers with scoped allcaps array
	}
	
	function reinstate_caps( $wp_blogcaps, $orig_reqd_caps, $args ) {
		global $current_user, $current_rs_user;
	
		if ( ( $args[1] == $current_rs_user->ID ) && array_diff_key( $current_rs_user->allcaps, $current_user->allcaps ) ) {
			$current_user->allcaps = array_intersect( array_merge( $current_user->allcaps, $current_rs_user->allcaps ), array(true,1,'1') );
		}
		
		return $wp_blogcaps;
	}

} // end class WP_Scoped_User
}

?>