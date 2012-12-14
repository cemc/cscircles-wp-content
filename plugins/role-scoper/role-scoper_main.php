<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/**
 * Scoper PHP class for the WordPress plugin Role Scoper
 * role-scoper_main.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2012
 * 
 */
class Scoper
{
	var $definitions;
	var $access_types;
	var $data_sources;
	var $taxonomies;
	var $cap_defs;
	var $role_defs;
	
	var $cap_interceptor;		// legacy API
	
	// === Temporary status variables ===
	var $direct_file_access;
	var $listed_ids = array();  // $listed_ids[src_name][object_id] = true : general purpose memory cache for non-post data sources; primary use is with has_cap filter to avoid a separate db query for each listed item 

	var $default_restrictions = array();

	// minimal config retrieval to support pre-init usage by WP_Scoped_User before text domain is loaded
	function Scoper() {
		$this->definitions = array( 'data_sources' => 'Data_Sources', 'taxonomies' => 'Taxonomies', 'cap_defs' => 'Capabilities', 'role_defs' => 'Roles' );	
		require_once( dirname(__FILE__).'/definitions_cr.php' );
		
		if ( defined( 'RVY_VERSION' ) )
			$this->cap_interceptor = (object) array();	// legacy support for Revisionary < 1.1 which set flags on this object property
	}
	
	function load_config() {
		require_once( dirname(__FILE__).'/lib/agapetry_config_items.php');
		$this->access_types = new AGP_Config_Items();
		$this->access_types->init( cr_access_types() );  // 'front' and 'admin' are the hardcoded access types
		
		// establish access type for this http request
		$access_name = ( is_admin() || defined('XMLRPC_REQUEST') ) ? 'admin' : 'front';
		$access_name = apply_filters( 'scoper_access_name', $access_name );		// others plugins can apply additional criteria for treating a particular URL with wp-admin or front-end filtering
		if ( ! defined('CURRENT_ACCESS_NAME_RS') )
			define('CURRENT_ACCESS_NAME_RS', $access_name);

		// disable RS filtering of access type(s) if specified in realm options 
		if ( ! is_admin() || ! defined('SCOPER_REALM_ADMIN_RS') ) {		// don't remove items if the option is being editied
			if ( $disabled_access_types = scoper_get_option('disabled_access_types') )
				$this->access_types->remove_members_by_key($disabled_access_types, true);
				
			// If the detected access type (admin, front or custom) was "disabled", it is still detected, but we note that query filters should not be applied
			if ( ! $this->access_types->is_member($access_name) )
				define('DISABLE_QUERYFILTERS_RS', true);
		}
		
		// populate data_sources, taxonomies, cap_defs, role_defs arrays
		foreach( array_keys($this->definitions) as $topic )
			$this->load_definition( $topic );	
			
		foreach( array_keys($this->definitions) as $topic )
			$this->$topic->lock();

		// clean up after 3rd party plugins (such as Role Scoping for NGG) which don't set object type and src_name properties for roles
		if ( has_filter( 'define_roles_rs' ) ) {
			require_once( dirname(__FILE__).'/extension-helper_rs.php' );
			scoper_adjust_legacy_extension_cfg( $this->role_defs, $this->cap_defs );
		}
		
		add_action( 'set_current_user', array( &$this, 'credit_blogroles' ) );
		
		$this->credit_blogroles();

		do_action('config_loaded_rs');
	}
	
	function credit_blogroles() {
		// credit non-logged and "no role" users for any anonymous roles
		global $current_rs_user;
		
		if ( $current_rs_user ) {
			if ( empty($current_rs_user->ID) ) {
				foreach ( $this->role_defs->filter_keys( -1, array( 'anon_user_blogrole' => true ) ) as $role_handle) {
					$current_rs_user->assigned_blog_roles[ANY_CONTENT_DATE_RS][$role_handle] = true;
					$current_rs_user->blog_roles[ANY_CONTENT_DATE_RS][$role_handle] = true;
				}
			}
	
			if ( isset($current_rs_user->assigned_blog_roles) )
				$this->refresh_blogroles();
		}
	}
	
	function refresh_blogroles() {
		global $current_rs_user;
		
		if ( empty($current_rs_user) )
			return;
		
		$current_rs_user->merge_scoped_blogcaps();
		$GLOBALS['current_user']->allcaps = array_merge( $GLOBALS['current_user']->allcaps, $current_rs_user->allcaps );
		
		if ( empty($GLOBALS['current_user']->data) )
			$GLOBALS['current_user']->data = (object) array();

		foreach( array( 'groups', 'blog_roles', 'assigned_blog_roles' ) as $var ) {
			if ( isset($current_rs_user->$var) )
				$GLOBALS['current_user']->$var = $current_rs_user->$var;
		}

		if ( $current_rs_user->ID || defined( 'SCOPER_ANON_METAGROUP' ) ) {
			foreach ( array_keys($current_rs_user->assigned_blog_roles) as $date_key )
				$current_rs_user->blog_roles[$date_key] = $this->role_defs->add_contained_roles( $current_rs_user->assigned_blog_roles[$date_key] );
		}
	}
	
	function load_definition( $topic ) {
		$class_name = "CR_" . $this->definitions[$topic];
		require_once( strtolower($this->definitions[$topic]) . '_rs.php' );

		$filter_name = "define_" . strtolower($this->definitions[$topic]) . "_rs";
		$this->$topic = apply_filters( $filter_name, new $class_name( call_user_func("cr_{$topic}") ) );

		if ( 'role_defs' == $topic ) {
			$this->role_defs->role_caps = apply_filters('define_role_caps_rs', cr_role_caps() );
			
			if ( $user_role_caps = scoper_get_option( 'user_role_caps' ) )
				$this->role_defs->add_role_caps( $user_role_caps );

			$this->log_cap_usage( $this->role_defs, $this->cap_defs );  // add any otype associations from new user_role_caps, but don't remove an otype association due to disabled_role_caps

			if ( $disabled_role_caps = scoper_get_option( 'disabled_role_caps' ) )
				$this->role_defs->remove_role_caps( $disabled_role_caps );

			$this->role_defs->remove_invalid(); // currently don't allow additional custom-defined post, page or link roles

			$this->customize_role_objscope();
			
			// To support merging in of WP role assignments, always note actual WP-defined roles 
			// regardless of which role type we are scoping with.
			$this->log_wp_roles( $this->role_defs );
		}
	}
	
	function log_cap_usage( &$role_defs, &$cap_defs ) {
		foreach( $role_defs->members as $role_handle => $role_def ) {
			
			foreach( array_keys( $role_defs->role_caps[$role_handle] ) as $cap_name ) {
				if ( empty( $cap_defs->members[$cap_name]->object_types ) || ! in_array( $role_def->object_type, $cap_defs->members[$cap_name]->object_types ) ) {
					if ( 'post' == $role_def->src_name )
						$cap_defs->members[$cap_name]->object_types[] = $role_def->object_type;
						
					elseif ( in_array( $role_def->src_name, array( 'link', 'group' ) ) )	// TODO: other data sources?
						$cap_defs->members[$cap_name]->object_types[] = $role_def->src_name;
				}
			}
		}
	}
	
	function customize_role_objscope() {
		foreach ( $this->role_defs->get_all_keys() as $role_handle ) {
			if ( ! empty($this->role_defs->members[$role_handle]->objscope_equivalents) ) {
				foreach( $this->role_defs->members[$role_handle]->objscope_equivalents as $equiv_key => $equiv_role_handle ) {
					
					if ( scoper_get_option( "{$equiv_role_handle}_role_objscope" ) ) {	// If "Additional Object Role" option is set for this role, treat it as a regular direct-assigned Object Role

						if ( isset($this->role_defs->members[$equiv_role_handle]->valid_scopes) )
							$this->role_defs->members[$equiv_role_handle]->valid_scopes = array('blog' => 1, 'term' => 1, 'object' => 1);

						unset( $this->role_defs->members[$role_handle]->objscope_equivalents[$equiv_key] );
				
						if ( ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) )
							define( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle, true );	// prevent Role Caption / Abbrev from being substituted from equivalent role
					}
				}
			}
		}	
	}
	
	function log_wp_roles( &$role_defs ) {
		global $wp_roles;
		if ( ! isset($wp_roles) )
			$wp_roles = new WP_Roles();
			
		// populate WP roles least-role-first to match RS roles
		$keys = array_keys($wp_roles->role_objects);
		$keys = array_reverse($keys);

		$cr_cap_names = $this->cap_defs->get_all_keys();
		
		$last_lock = $role_defs->locked;
		$role_defs->locked = false;

		foreach ( $keys as $role_name ) {
			if ( ! empty( $wp_roles->role_objects[$role_name]->capabilities ) ) {
				// remove any WP caps which are in array, but have value = false
				if ( $caps = array_intersect( $wp_roles->role_objects[$role_name]->capabilities, array(true) ) )
					$caps = array_intersect_key( $caps, array_flip($cr_cap_names) );  // we only care about WP caps that are RS-defined
			} else
				$caps = array();

			$role_defs->add( $role_name, 'wordpress', '', '', 'wp' );

			// temp hardcode for site-wide Nav Menu cap
			if ( ! empty( $caps['edit_theme_options'] ) )
				$caps['manage_nav_menus'] = true;

			$role_defs->role_caps['wp_' . $role_name] = $caps;
		}
		
		$role_defs->locked = $last_lock;
	}
	
	
	function init() {
		scoper_version_check();
		
		if ( ! isset($this->data_sources) )
			$this->load_config();
		
		$is_administrator = is_content_administrator_rs();
		
		if ( $doing_cron = defined('DOING_CRON') )
			if ( ! defined('DISABLE_QUERYFILTERS_RS') )
				define('DISABLE_QUERYFILTERS_RS', true);
				
		if ( ! $this->direct_file_access = strpos($_SERVER['QUERY_STRING'], 'rs_rewrite') )
			$this->add_main_filters();
			
		// ===== Special early exit if this is a plugin install script
		if ( is_admin() ) {
			if ( in_array( $GLOBALS['pagenow'], array( 'plugin-install.php', 'plugin-editor.php' ) ) ) {
				// flush RS cache on activation of any plugin, in case we cached results based on its presence / absence
				if ( ( ! empty($_POST) ) || ( ! empty($_REQUEST['action']) ) ) {
					if ( ! empty($_POST['networkwide']) || ( 'plugin-editor.php' == $GLOBALS['pagenow'] ) )
						wpp_cache_flush_all_sites();
					else
						wpp_cache_flush();
				}

				do_action( 'scoper_init' );
				return; // no further filtering on WP plugin maintenance scripts
			}
		}
		// =====

		require_once( dirname(__FILE__).'/attachment-interceptor_rs.php');
		$GLOBALS['attachment_interceptor'] = new AttachmentInterceptor_RS(); // .htaccess file is always there, so we always need to handle its rewrites
				
		// ===== Content Filters to limit/enable the current user
		$disable_queryfilters = defined('DISABLE_QUERYFILTERS_RS');
		
		if ( $disable_queryfilters ) {
			// Some wp-admin pages need to list pages or categories based on front-end access.  Classic example is Subscribe2 categories checklist, included in Subscriber profile
			// In that case, filtering will be applied even if wp-admin filtering is disabled.  API hook enables other plugins to defined their own "always filter" URIs.
			$always_filter_uris = apply_filters( 'scoper_always_filter_uris', array( 'p-admin/profile.php' ) );

			if ( in_array( $GLOBALS['pagenow'], $always_filter_uris ) || in_array( $GLOBALS['plugin_page_cr'], $always_filter_uris ) ) {
				$disable_queryfilters = false;
				break;
			}
		}
		
		// register a map_meta_cap filter to handle the type-specific meta caps we are forcing
		require_once( dirname(__FILE__).'/meta_caps_rs.php' );	

		if ( ! $disable_queryfilters ) {
			 if ( ! $is_administrator ) {
				if ( $this->direct_file_access ) {
					require_once( dirname(__FILE__).'/cap-interceptor-basic_rs.php');  // only need to support basic read_post / read_page check for direct file access
					$GLOBALS['cap_interceptor_basic'] = new CapInterceptorBasic_RS();
				} else {
					require_once( dirname(__FILE__).'/cap-interceptor_rs.php');
					$GLOBALS['cap_interceptor'] = new CapInterceptor_RS();
				}
			}

			// (also use content filters on front end to FILTER IN private content which WP inappropriately hides from administrators)
			if ( ( ! $is_administrator ) || $this->is_front() ) {
				require_once( dirname(__FILE__).'/query-interceptor_rs.php');
				$GLOBALS['query_interceptor'] = new QueryInterceptor_RS();
			}

			if ( ( ! $this->direct_file_access ) && ( ! $is_administrator || ! defined('XMLRPC_REQUEST') ) ) { // don't tempt trouble by adding hardway filters on XMLRPC for logged administrator
				$this->add_hardway_filters();
				
				if ( $this->is_front() || ! $is_administrator ) {
					require_once( dirname(__FILE__).'/terms-query-lib_rs.php');
				
					if ( awp_ver( '3.1' ) && ! defined( 'SCOPER_LEGACY_TERMS_FILTER' ) ) {
						require_once( dirname(__FILE__).'/terms-interceptor_rs.php');
						$GLOBALS['terms_interceptor'] = new TermsInterceptor_RS();
					} else
						require_once( dirname(__FILE__).'/hardway/hardway-taxonomy-legacy_rs.php');
				}
			}

		} // endif query filtering not disabled for this access type

		if ( $is_administrator ) {
			if ( $this->is_front() )
				require_once( 'comments-int-administrator_rs.php' );
		} else
			require_once( 'comments-interceptor_rs.php' );
		
		if ( is_admin() )
			$this->add_admin_ui_filters( $is_administrator );
		
		do_action( 'scoper_init' );
		
		// ===== end Content Filters
		
	} // end function init
	
	
	// filters which are only needed for the wp-admin UI
	function add_admin_ui_filters( $is_administrator ) {
		global $pagenow;
		
		// ===== Admin filters (menu and other basics) which are (almost) always loaded 
		require_once( dirname(__FILE__).'/admin/admin_rs.php');
		$GLOBALS['scoper_admin'] = new ScoperAdmin();
		
		if ( 'async-upload.php' != $pagenow ) {
			if ( ! defined('DISABLE_QUERYFILTERS_RS') || $is_administrator ) {
				require_once( dirname(__FILE__).'/admin/filters-admin-ui_rs.php' );
				$GLOBALS['scoper_admin_filters_ui'] = new ScoperAdminFiltersUI();
			}
		}
		// =====

		// ===== Script-specific Admin filters 
		if ( 'users.php' == $pagenow ) {
			require_once( dirname(__FILE__).'/admin/filters-admin-users_rs.php' );
			
		} elseif ( 'edit.php' == $pagenow ) {
			if ( ! defined('DISABLE_QUERYFILTERS_RS') || $is_administrator )
				require_once( dirname(__FILE__).'/admin/filters-admin-ui-listing_rs.php' );

		} elseif ( in_array( $pagenow, array( 'edit-tags.php', 'edit-link-categories.php' ) ) ) {
			if ( ! defined('DISABLE_QUERYFILTERS_RS') )
				require_once( dirname(__FILE__).'/admin/filters-admin-terms_rs.php' );
		}
		// =====
		
		if ( scoper_get_option( 'group_ajax' ) && ( isset( $_GET['rs_user_search'] ) || isset( $_GET['rs_group_search'] ) ) ) {
			require_once( dirname(__FILE__).'/admin/user_query_rs.php' );
			exit;	
		} 
	}
	
	
	function add_hardway_filters() {
		// port or low-level query filters to work around limitations in WP core API
		require_once( dirname(__FILE__).'/hardway/hardway_rs.php'); // need get_pages() filtering to include private pages for some 3rd party plugin config UI (Simple Section Nav)
		
		// buffering of taxonomy children is disabled with non-admin user logged in
		// But that non-admin user may add cats.  Don't allow unfiltered admin to rely on an old copy of children
		global $wp_taxonomies;
		if ( ! empty($wp_taxonomies) ) {
			foreach ( array_keys($wp_taxonomies) as $taxonomy )
				add_filter ( "option_{$taxonomy}_children", create_function( '$option_value', "return rs_get_terms_children('$taxonomy', " . '$option_value );') );
				//add_filter("option_{$taxonomy}_children", create_function( '', "return rs_get_terms_children('$taxonomy');") );
		}

		if ( is_admin() || defined('XMLRPC_REQUEST') ) {
            global $pagenow;
			
			if ( ! in_array( $pagenow, array( 'plugin-editor.php', 'plugins.php' ) ) ) {
	            global $plugin_page_cr;

				// low-level filtering for miscellaneous admin operations which are not well supported by the WP API
				$hardway_uris = array(
				'index.php',		'revision.php',			'admin.php?page=rvy-revisions',
				'post.php', 		'post-new.php', 		'edit.php', 
				'upload.php', 		'edit-comments.php', 	'edit-tags.php',
				'profile.php',		'admin-ajax.php',
				'link-manager.php', 'link-add.php',			'link.php',		 
				'edit-link-category.php', 	'edit-link-categories.php',
				'media-upload.php',	'nav-menus.php'  
				);

				$hardway_uris = apply_filters( 'scoper_admin_hardway_uris', $hardway_uris );
																															// support for rs-config-ngg <= 1.0
				if ( defined('XMLRPC_REQUEST') || in_array( $pagenow, $hardway_uris ) || in_array( $plugin_page_cr, $hardway_uris ) || in_array( "p-admin/admin.php?page=$plugin_page_cr", $hardway_uris ) )
					require_once( dirname(__FILE__).'/hardway/hardway-admin_rs.php' );
        	}
		} // endif is_admin or xmlrpc
	}
	
	
	// add filters which were skipped due to direct file access, but are now needed for the error page display
	function add_main_filters() {
		$is_admin = is_admin();
		$is_administrator = is_content_administrator_rs();
		$disable_queryfilters = defined('DISABLE_QUERYFILTERS_RS');
		$frontend_admin = false;
		
		if ( ! defined('DOING_CRON') ) {
			if ( $this->is_front() ) {
				if ( ! $disable_queryfilters )
					require_once( dirname(__FILE__).'/query-interceptor-front_rs.php');
	
				if ( ! $is_administrator ) {
					require_once( dirname(__FILE__).'/qry-front_non-administrator_rs.php');
					$GLOBALS['feed_interceptor'] = new FeedInterceptor_RS(); // file already required in role-scoper.php
				}
	
				require_once( dirname(__FILE__).'/template-interceptor_rs.php');
				$GLOBALS['template_interceptor'] = new TemplateInterceptor_RS();
	
				$frontend_admin = ! scoper_get_option('no_frontend_admin'); // potential performance enhancement	

				if ( ! empty($_REQUEST['s']) && function_exists('relevanssi_query') ) {
					require_once( dirname(__FILE__).'/relevanssi-helper-front_rs.php' );
					$rel_helper_rs = new Relevanssi_Search_Filter_RS();
				}
			}

			// ===== Filters which are always loaded (except on plugin scripts), for any access type
			include_once( dirname(__FILE__).'/hardway/wp-patches_agp.php' ); // simple patches for WP
			
			if ( $this->is_front() || ( 'edit.php' == $GLOBALS['pagenow'] ) ) {
				require_once( dirname(__FILE__).'/query-interceptor-base_rs.php');
				$GLOBALS['query_interceptor_base'] = new QueryInterceptorBase_RS();  // listing filter used for role status indication in edit posts/pages and on front end by template functions
			}
		}
		
		// ===== Filters which support automated role maintenance following content creation/update
		// Require an explicitly set option to skip these for front end access, just in case other plugins modify content from the front end.
		if ( ( $is_admin || defined('XMLRPC_REQUEST') || $frontend_admin || defined('DOING_CRON') ) ) {
			require_once( dirname(__FILE__).'/admin/cache_flush_rs.php' );
			require_once( dirname(__FILE__).'/admin/filters-admin_rs.php' );
			$GLOBALS['scoper_admin_filters'] = new ScoperAdminFilters();
			
			if ( defined( 'RVY_VERSION' ) ) // Support Revisionary references to $scoper->filters_admin (TODO: eventually phase this out)
				$this->filters_admin =& $GLOBALS['scoper_admin_filters'];
		}
		// =====
	}
	

	function init_users_interceptor() {
		if ( ! isset($GLOBALS['users_interceptor']) ) {
			require_once( dirname(__FILE__).'/users-interceptor_rs.php');
			$GLOBALS['users_interceptor'] = new UsersInterceptor_RS();

			//log_mem_usage_rs( 'init Users Interceptor' );
		}
		
		return $GLOBALS['users_interceptor'];
	}
	
	
	// Primarily for internal use. Drops some features of WP core get_terms while adding the following versatility:
	// - supports any RS-defined taxonomy, with or without WP taxonomy schema
	// - optionally return term_id OR term_taxonomy_id as single column
	// - specify filtered or unfiltered via argument
	// - optionally get terms for a specific object
	// - option to order by term hierarchy (but structure as flat array)
	function get_terms($taxonomy, $filtering = true, $cols = COLS_ALL_RS, $object_id = 0, $args = array()) {
		if ( ! $tx = $this->taxonomies->get($taxonomy) )
			return array();

		global $wpdb;

		$defaults = array( 'order_by' => '', 'use_object_roles' => false, 'operation' => '' ); // IMPORTANT to default operation to nullstring
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		if (  is_administrator_rs( $this->taxonomies->member_property( $taxonomy, 'object_source' ) ) )
			$filtering = false;

		// try to pull it out of wpcache
		$ckey = md5( $taxonomy . $cols . $object_id . serialize($args) . $order_by );
		
		if ( $filtering ) {
			$src_name = $this->taxonomies->member_property($taxonomy, 'object_source', 'name');

			$args['reqd_caps_by_otype'] = $this->get_terms_reqd_caps( $taxonomy, $operation, ADMIN_TERMS_FILTER_RS === $filtering );

			$ckey = md5( $ckey . serialize($args['reqd_caps_by_otype']) ); ; // can vary based on request URI
		
			global $current_rs_user;
			$cache_flag = 'rs_scoper_get_terms';
			$cache = $current_rs_user->cache_get($cache_flag);
		} else {			
			$cache_flag = "all_terms";
			$cache_id = 'all';
			$cache = wpp_cache_get( $cache_id, $cache_flag );
		}

		if ( isset( $cache[ $ckey ] ) ) {
			return $cache[ $ckey ];
		}
			
		// call base class method to build query
		$terms_only = ( ! $filtering || empty($use_object_roles) );
	
		$query_base = $this->taxonomies->get_terms_query($taxonomy, $cols, $object_id, $terms_only );

		if ( ! $query_base )
			return array();

		$query = ( $filtering ) ? apply_filters('terms_request_rs', $query_base, $taxonomy, $args) : $query_base;

		// avoid sending alarms to SQL purists if this query was not modified by RS filter
		if ( $query_base == $query )
			$query = str_replace( 'WHERE 1=1 AND', 'WHERE', $query );
		
		if ( COL_ID_RS == $cols )
			$results = scoper_get_col($query);
		elseif ( COL_COUNT_RS == $cols )
			$results = intval( scoper_get_var($query) );
		else {
			// TODO: why is this still causing an extra (and costly) scoped query?
			/*
			// for COLS_ALL query, need to call core get_terms call in case another plugin is translating term names
			if ( has_filter( 'get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms') ) ) {
				remove_filter( 'get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3 );
				$all_terms = get_terms($taxonomy);
				add_filter( 'get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 1, 3 );

				$term_names = scoper_get_property_array( $all_terms, 'term_id', 'name' );
			}
			*/
			
			$results = scoper_get_results($query);

			//scoper_restore_property_array( $results, $term_names, 'term_id', 'name' );
				
			if ( ORDERBY_HIERARCHY_RS == $order_by ) {
				require_once( dirname(__FILE__).'/admin/admin_lib_rs.php');
				
				if ( $src = $this->data_sources->get( $tx->source ) ) {
					if ( ! empty($src->cols->id) && ! empty($src->cols->parent) ) {
						require_once( dirname(__FILE__).'/admin/admin_lib-bulk-parent_rs.php');
						$results = ScoperAdminBulkParent::order_by_hierarchy($results, $src->cols->id, $src->cols->parent);
					}
				}
			}
		}
		
		$cache[ $ckey ] = $results;

		if ( $results || empty( $_POST ) ) { // todo: why do we get an empty array for unfiltered request for object terms early in POST processing? (on submission of a new post by a contributor)
			if ( $filtering )
				$current_rs_user->cache_force_set( $cache, $cache_flag );
			else
				wpp_cache_force_set( $cache_id, $cache, $cache_flag );	
		}
		
		return $results;
	}
	
	function get_default_restrictions($scope, $args = array()) {
		$defaults = array( 'force_refresh' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
	
		if ( isset($this->default_restrictions[$scope]) && ! $force_refresh )
			return $this->default_restrictions[$scope];
		
		if ( empty($force_refresh) ) {
			$cache_flag = "rs_{$scope}_def_restrictions";
			$cache_id = md5('');	// maintain default id generation from previous versions

			$default_strict = wpp_cache_get($cache_id, $cache_flag);
		}
		
		if ( $force_refresh || ! is_array($default_strict) ) {
			global $wpdb;
			
			$qry = "SELECT src_or_tx_name, role_name FROM $wpdb->role_scope_rs WHERE role_type = 'rs' AND topic = '$scope' AND max_scope = '$scope' AND obj_or_term_id = '0'";

			$default_strict = array();
			if ( $results = scoper_get_results($qry) ) {
				foreach ( $results as $row ) {
					$role_handle = scoper_get_role_handle($row->role_name, 'rs');
					$default_strict[$row->src_or_tx_name][$role_handle] = true;
					
					if (OBJECT_SCOPE_RS == $scope) {
						if ( $objscope_equivalents = $this->role_defs->member_property($role_handle, 'objscope_equivalents') )
							foreach ( $objscope_equivalents as $equiv_role_handle )
								$default_strict[$row->src_or_tx_name][$equiv_role_handle] = true;
					}
					
				}
			}
		}
		
		$this->default_restrictions[$scope] = $default_strict;

		wpp_cache_set($cache_id, $default_strict, $cache_flag);
		
		return $default_strict;
	}
	
	// for any given role requirement, a strict term is one which won't blend in blog role assignments
	// (i.e. a term which requires the specified role to be assigned as a term role or object role)
	//
	// returns $arr['restrictions'][role_handle][obj_or_term_id] = array( 'assign_for' => $row->assign_for, 'inherited_from' => $row->inherited_from ),
	//				['unrestrictions'][role_handle][obj_or_term_id] = array( 'assign_for' => $row->assign_for, 'inherited_from' => $row->inherited_from )
	function get_restrictions($scope, $src_or_tx_name, $args = array()) {
		$def_cols = COL_ID_RS;

		// Note: propogating child restrictions are always directly assigned to the child term(s).
		// Use include_child_restrictions to force inclusion of restrictions that are set for child items only,
		// for direct admin of these restrictions and for propagation on term/object creation.
		$defaults = array( 	'id' => 0,					'include_child_restrictions' => false,
						 	'force_refresh' => false, 
						 	'cols' => $def_cols,		'return_array' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		$cache_flag = "rs_{$scope}_restrictions_{$src_or_tx_name}";
		$cache_id = md5($src_or_tx_name . $cols . strval($return_array) . strval($include_child_restrictions) );

		if ( ! $force_refresh ) {
			$items = wpp_cache_get($cache_id, $cache_flag);

			if ( is_array($items) ) {
				if ( $id ) {
					foreach ( $items as $setting_type => $roles )
						foreach ( array_keys($roles) as $role_handle )
							$items[$setting_type][$role_handle] = array_intersect_key( $items[$setting_type][$role_handle], array( $id => true ) );
				}

				return $items;
			}
		}
		
		if ( ! isset($this->default_restrictions[$scope]) )
			$this->default_restrictions[$scope] = $this->get_default_restrictions($scope);

		global $wpdb;

		if ( ! empty($this->default_restrictions[$scope][$src_or_tx_name]) ) {
			if ( $strict_roles = array_keys($this->default_restrictions[$scope][$src_or_tx_name]) ) {
				if ( OBJECT_SCOPE_RS == $scope ) {
					// apply default_strict handling to objscope equivalents of each strict role
					foreach ( $strict_roles as $role_handle )
						if ( $objscope_equivalents = $this->role_defs->member_property($role_handle, 'objscope_equivalents') )
							$strict_roles = array_merge($strict_roles, $objscope_equivalents);
							
					$strict_roles = array_unique($strict_roles);
				}
			}
			
			$strict_role_in = "'" . implode("', '", scoper_role_handles_to_names($strict_roles) ) . "'";
		} else
			$strict_role_in = '';
		
		$items = array();				
		if ( ! empty($strict_roles) ) {
			foreach ( $strict_roles as $role_handle )
				$items['unrestrictions'][$role_handle] = array();  // calling code will use this as an indication that the role is default strict
		}
		
		$default_strict_modes = array( false );
		
		if ( $strict_role_in )
			$default_strict_modes []= true;

		foreach ( $default_strict_modes as $default_strict ) {
			$setting_type = ( $default_strict ) ? 'unrestrictions' : 'restrictions';

			if ( TERM_SCOPE_RS == $scope )
				$max_scope = ( $default_strict ) ? 'blog' : 'term';  // note: max_scope='object' entries are treated as separate, overriding requirements
			else
				$max_scope = ( $default_strict ) ? 'blog' : 'object'; // Storage of 'blog' max_scope as object restriction does not eliminate any term restrictions.  It merely indicates, for data sources that are default strict, that this object does not restrict roles
				
			if ( $default_strict )
				$role_clause = "AND role_name IN ($strict_role_in)";
			elseif ($strict_role_in)
				$role_clause = "AND role_name NOT IN ($strict_role_in)";
			else
				$role_clause = '';

			$for_clause = ( $include_child_restrictions ) ? '' : "AND require_for IN ('entity', 'both')";
			
			$qry_base = "FROM $wpdb->role_scope_rs WHERE role_type = 'rs' AND topic = '$scope' AND max_scope = '$max_scope' AND src_or_tx_name = '$src_or_tx_name' $for_clause $role_clause";
			
			if ( COL_COUNT_RS == $cols )
				$qry = "SELECT role_name, count(obj_or_term_id) AS item_count, require_for $qry_base GROUP BY role_name";
			else
				$qry = "SELECT role_name, obj_or_term_id, require_for AS assign_for, inherited_from $qry_base";

			if ( $results = scoper_get_results($qry) ) {
				foreach( $results as $row) {
					$role_handle = scoper_get_role_handle($row->role_name, 'rs');
					
					if ( COL_COUNT_RS == $cols )
						$items[$setting_type][$role_handle] = $row->item_count;
					elseif ( $return_array )
						$items[$setting_type][$role_handle][$row->obj_or_term_id] = array( 'assign_for' => $row->assign_for, 'inherited_from' => $row->inherited_from );
					else
						$items[$setting_type][$role_handle][$row->obj_or_term_id] = $row->assign_for;
				}
			}
			
		} // end foreach default_strict_mode

		wpp_cache_force_set($cache_id, $items, $cache_flag);

		if ( $id ) {
			foreach ( $items as $setting_type => $roles )
				foreach ( array_keys($roles) as $role_handle )
					$items[$setting_type][$role_handle] = array_intersect_key( $items[$setting_type][$role_handle], array( $id => true ) );
		}

		return $items;
	}
	
	
	// wrapper for back-compat with calling code expecting array without date limit dimension
	function qualify_terms($reqd_caps, $taxonomy = 'category', $qualifying_roles = '', $args = array()) {
		$terms = $this->qualify_terms_daterange( $reqd_caps, $taxonomy, $qualifying_roles, $args );
		
		if ( isset($terms['']) && is_array($terms['']) )
			return $terms[''];
		else
			return array();
	}

	// $qualifying_roles = array[role_handle] = 1 : qualifying roles
	// returns array of term_ids (terms which have at least one of the qualifying roles assigned)
	function qualify_terms_daterange($reqd_caps, $taxonomy = 'category', $qualifying_roles = '', $args = array()) {
		$defaults = array( 'user' => '', 'return_id_type' => COL_ID_RS, 'use_blog_roles' => true, 'ignore_restrictions' => false );

		if ( isset($args['qualifying_roles']) )
			unset($args['qualifying_roles']);
			
		if ( isset($args['reqd_caps']) )
			unset($args['reqd_caps']);

		$args = array_merge( $defaults, (array) $args );
		extract($args);

		if ( ! $qualifying_roles )  // calling function might save a little work or limit to a subset of qualifying roles
			$qualifying_roles = $this->role_defs->qualify_roles( $reqd_caps );

		if ( ! $this->taxonomies->is_member($taxonomy) )
			return array( '' => array() );
		
		if ( ! is_object($user) ) {
			$user = $GLOBALS['current_rs_user'];
		}
		
		// If the taxonomy does not require objects to have at least one term, there are no strict terms.
		if ( ! $this->taxonomies->member_property($taxonomy, 'requires_term') )
			$ignore_restrictions = true;
			
		if ( ! is_array($qualifying_roles) )
			$qualifying_roles = array($qualifying_roles => 1);	

		// no need to serialize and md5 the whole user object
		if ( ! empty($user) )
			$args['user'] = $user->ID;

		// try to pull previous result out of memcache
		ksort($qualifying_roles);
		$rolereq_key = md5( serialize($reqd_caps) . serialize( array_keys($qualifying_roles) ) . serialize($args) );
		
		if ( isset($user->qualified_terms[$taxonomy][$rolereq_key]) )
			return $user->qualified_terms[$taxonomy][$rolereq_key];
			
		if ( ! $qualifying_roles )
			return array( '' => array() );

		$all_terms = $this->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS); // returns term_id, even for WP > 2.3

		if ( ! isset($user->term_roles[$taxonomy]) )
			$user->get_term_roles_daterange($taxonomy);  // returns term_id for categories

		$good_terms = array( '' => array() );
		
		if ( $user->term_roles[$taxonomy] ) {
			foreach ( array_keys($user->term_roles[$taxonomy]) as $date_key ) {
				//narrow down to roles which satisfy this call AND are owned by current user
				if ( $good_terms[$date_key] = array_intersect_key( $user->term_roles[$taxonomy][$date_key], $qualifying_roles ) )
					// flatten from term_roles_terms[role_handle] = array of term_ids
					// to term_roles_terms = array of term_ids
					$good_terms[$date_key] = agp_array_flatten( $good_terms[$date_key] );
			}
		}

		if ( $use_blog_roles ) {
			foreach ( array_keys($user->blog_roles) as $date_key ) {	
				$user_blog_roles = array_intersect_key( $user->blog_roles[$date_key], $qualifying_roles );

				// Also include user's WP blogrole(s) which correspond to the qualifying RS role(s)
				if ( $wp_qualifying_roles = $this->role_defs->qualify_roles($reqd_caps, 'wp') ) {
					
					if ( $user_blog_roles_wp = array_intersect_key( $user->blog_roles[$date_key], $wp_qualifying_roles ) ) {
					
						// Credit user's qualifying WP blogrole via equivalent RS role(s)
						// so we can also enforce "term restrictions", which are based on RS roles
						$user_blog_roles_via_wp = $this->role_defs->get_contained_roles( array_keys($user_blog_roles_wp), false, 'rs' );
						$user_blog_roles_via_wp = array_intersect_key( $user_blog_roles_via_wp, $qualifying_roles );
						$user_blog_roles = array_merge( $user_blog_roles, $user_blog_roles_via_wp );
					}
				}
				
				if ( $user_blog_roles ) {
					if ( empty($ignore_restrictions) ) {
						// array of term_ids that require the specified role to be assigned via taxonomy or object role (user blog caps ignored)
						$strict_terms = $this->get_restrictions(TERM_SCOPE_RS, $taxonomy);
					} else
						$strict_terms = array();

					foreach ( array_keys($user_blog_roles) as $role_handle ) {
						if ( isset($strict_terms['restrictions'][$role_handle]) && is_array($strict_terms['restrictions'][$role_handle]) )
							$terms_via_this_role = array_diff( $all_terms, array_keys($strict_terms['restrictions'][$role_handle]) );
						
						elseif ( isset($strict_terms['unrestrictions'][$role_handle]) && is_array($strict_terms['unrestrictions'][$role_handle]) )
							$terms_via_this_role = array_intersect( $all_terms, array_keys( $strict_terms['unrestrictions'][$role_handle] ) );
						
						else
							$terms_via_this_role = $all_terms;
	
						if( $good_terms[$date_key] )
							$good_terms[$date_key] = array_merge( $good_terms[$date_key], $terms_via_this_role );
						else
							$good_terms[$date_key] = $terms_via_this_role;
					}
				}
			}
		}

		foreach ( array_keys($good_terms) as $date_key ) {
			if ( $good_terms[$date_key] = array_intersect( $good_terms[$date_key], $all_terms ) )  // prevent orphaned category roles from skewing access
				$good_terms[$date_key] = array_unique( $good_terms[$date_key] );
		
			// if COL_TAXONOMY_ID_RS, return a term_taxonomy_id instead of term_id
			if ( $good_terms[$date_key] && (COL_TAXONOMY_ID_RS == $return_id_type) && taxonomy_exists($taxonomy) ) {
				$all_terms_cols = $this->get_terms( $taxonomy, UNFILTERED_RS );
				$good_tt_ids = array();
				foreach ( $good_terms[$date_key] as $term_id )
					foreach ( array_keys($all_terms_cols) as $termkey )
						if ( $all_terms_cols[$termkey]->term_id == $term_id ) {
							$good_tt_ids []= $all_terms_cols[$termkey]->term_taxonomy_id;
							break;
						}
						
				$good_terms[$date_key] = $good_tt_ids;
			}
		}
		
		$user->qualified_terms[$taxonomy][$rolereq_key] = $good_terms;

		return $good_terms;
	}
	
	// account for different contexts of get_terms calls 
	// (Scoped roles can dictate different results for front end, edit page/post, manage categories)
	function get_terms_reqd_caps( $taxonomy, $operation = '', $is_term_admin = false ) {
		global $pagenow;

		if ( ! $src_name = $this->taxonomies->member_property( $taxonomy, 'object_source' ) ) {
			if ( taxonomy_exists( $taxonomy ) )
				$src_name = 'post';
		}

		$return_caps = array();

		$is_term_admin = $is_term_admin 
		|| in_array( $pagenow, array( 'edit-tags.php' ) ) 
		|| ( 'nav_menu' == $taxonomy && ( 'nav-menus.php' == $pagenow ) 
		|| ( ( 'admin-ajax.php' == $pagenow ) && ( ! empty($_REQUEST['action']) && in_array( $_REQUEST['action'], array( 'add-menu-item', 'menu-locations-save' ) ) ) )
		);	// possible TODO: abstract for non-WP taxonomies

		if ( $is_term_admin ) {
			// query pertains to the management of terms
			if ( 'post' == $src_name ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				$return_caps[$taxonomy] = array( $taxonomy_obj->cap->manage_terms );
			} elseif ( 'link_category' == $taxonomy ) { 
				$return_caps[$taxonomy] = array( 'manage_categories' );
			} else {
				global $scoper;
				$cap_defs = $scoper->cap_defs->get_matching( $src_name, $taxonomy, OP_ADMIN_RS );
				$return_caps[$taxonomy] = $cap_defs ? array_keys( $cap_defs ) : array();
			}

		} else {
			// query pertains to reading or editing content within certain terms, or adding terms to content
			
			$base_caps_only = true;
			
			if ( 'post' == $src_name ) {
				if ( ! $operation )
					$operation = ( $this->is_front() || ( 'profile.php' == $pagenow ) || ( is_admin() && array_key_exists('plugin_page', $GLOBALS) && ( 's2' == $GLOBALS['plugin_page'] ) ) ) ? 'read' : 'edit';  // hack to support subscribe2 categories checklist

				$status = ( 'read' == $operation ) ? 'publish' : 'draft';
				
				// terms query should be limited to a single object type for post.php, post-new.php, so only return caps for that object type (TODO: do this in wp-admin regardless of URI ?)
				if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) )
					$object_type = cr_find_post_type();
			} else {
				if ( ! $operation )
					$operation = ( $this->is_front() ) ? 'read' : 'edit';

				$status = '';
			}
				
			// The return array will indicate term role enable / disable, as well as associated capabilities
			if ( ! empty($object_type) )
				$check_object_types = array( $object_type );
			else {
				if ( $check_object_types = (array) $this->data_sources->member_property( $src_name, 'object_types' ) )
					$check_object_types = array_keys( $check_object_types );
			}
				
			if ( 'post' == $src_name )
				$use_post_types = scoper_get_option( 'use_post_types' );	
			
			$enabled_object_types = array();
			foreach ( $check_object_types as $_object_type ) {
				if ( $use_term_roles = scoper_get_otype_option( 'use_term_roles', $src_name, $_object_type ) )
					if ( ! empty( $use_term_roles[$taxonomy] ) ) {
						if ( ( 'post' != $src_name ) || ! empty( $use_post_types[$_object_type] ) )
							$enabled_object_types []= $_object_type;
					}
			}

			foreach( $enabled_object_types as $object_type )
				$return_caps[$object_type] = cr_get_reqd_caps( $src_name, $operation, $object_type, $status, $base_caps_only );	
		}
		
		return $return_caps;
	}
	
	function users_who_can($reqd_caps, $cols = COLS_ALL_RS, $object_src_name = '', $object_id = 0, $args = array() ) {
		// if there are not capability requirements, no need to load Users_Interceptor filtering class
		if ( ! $reqd_caps ) {
			if ( COL_ID_RS == $cols )
				$qcols = 'ID';
			elseif ( COLS_ID_NAME_RS == $cols )
				$qcols = "ID, user_login AS display_name";	// calling code assumes display_name property for user or group object
			elseif ( COLS_ID_DISPLAYNAME_RS == $cols )
				$qcols = "ID, display_name";
			elseif ( COLS_ALL_RS == $cols )
				$qcols = "*";
			else
				$qcols = $cols;
				
			global $wpdb;
				
			$orderby = ( $cols == COL_ID_RS ) ? '' : 'ORDER BY display_name';

			if ( IS_MU_RS && ! scoper_get_option( 'mu_sitewide_groups' ) && ! defined( 'FORCE_ALL_SITE_USERS_RS' ) )
				$qry = "SELECT $qcols FROM $wpdb->users INNER JOIN $wpdb->usermeta AS um ON $wpdb->users.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities' $orderby";
			else
				$qry = "SELECT $qcols FROM $wpdb->users $orderby";

			if ( COL_ID_RS == $cols )
				return scoper_get_col( $qry );
			else
				return scoper_get_results( $qry );	
			
		} else {
			$defaults = array( 'where' => '', 'orderby' => '', 'disable_memcache' => false, 'group_ids' => '', 'force_refresh' => false, 'force_all_users' => false );
			$args = array_merge( $defaults, (array) $args );
			extract($args);
	
			$cache_flag = "rs_users_who_can";
			$cache_id = md5(serialize($reqd_caps) . $cols . 'src' . $object_src_name . 'id' . $object_id . serialize($args) );
		
			if ( ! $force_refresh ) {
				// if we already have the results cached, no need to load Users_Interceptor filtering class
				$users = wpp_cache_get($cache_id, $cache_flag);
	
				if ( is_array($users) )
					return $users;
			}
			
			$this->init_users_interceptor();
			$users = $GLOBALS['users_interceptor']->users_who_can($reqd_caps, $cols, $object_src_name, $object_id, $args );

			wpp_cache_set($cache_id, $users, $cache_flag);
			return $users;
		}
	}
	
	function groups_who_can($reqd_caps, $cols = COLS_ALL_RS, $object_src_name = '', $object_id = 0, $args = array() ) {
		$this->init_users_interceptor();
		return $GLOBALS['users_interceptor']->groups_who_can($reqd_caps, $cols, $object_src_name, $object_id, $args );
	}
	
	function is_front() {
		return ( defined('CURRENT_ACCESS_NAME_RS') && ( 'front' == CURRENT_ACCESS_NAME_RS ) );
	}
	
	
	// returns array of role names which have the required caps (or their basecap equivalent)
	// AND have been applied to at least one object, for any user or group
	function qualify_object_roles( $reqd_caps, $object_type = '', $user = '', $base_caps_only = false ) {
		$roles = array();

		if ( $base_caps_only )
			$reqd_caps = $this->cap_defs->get_base_caps($reqd_caps);

		$roles = $this->role_defs->qualify_roles($reqd_caps, 'rs', $object_type);

		return $this->confirm_object_scope( $roles, $user );
	}

	// $roles[$role_handle] = array
	// returns arr[$role_handle] 
	function confirm_object_scope( $roles, $user = '' ) {
		foreach ( array_keys($roles) as $role_handle ) {
			if ( empty( $this->role_defs->members[$role_handle]->valid_scopes['object'] ) )
				unset( $roles[$role_handle] );
		}

		if ( ! $roles )
			return array();
		
		if ( is_object($user) )
			$applied_obj_roles = $this->get_applied_object_roles( $user );
		elseif ( empty($user) ) {
			$applied_obj_roles = $this->get_applied_object_roles( $GLOBALS['current_rs_user'] );
		} else // -1 value passed to indicate check for all users
			$applied_obj_roles = $this->get_applied_object_roles();
			
		return array_intersect_key( $roles, $applied_obj_roles );	
	}
	
	
	// returns array of role_handles which have been applied to any object
	// if $user arg is supplied, returns only roles applied for that user (or that user's groups) 
	function get_applied_object_roles( $user = '' ) {
		if ( is_object( $user ) ) {
			$cache_flag = 'rs_object-roles';			// v 1.1: changed cache key from "object_roles" to "object-roles" to match new key format for blog, term roles
			$cache = $user->cache_get($cache_flag);
			
			$limit = '';
			$u_g_clause = $user->get_user_clause('');
		} else {
			$cache_flag = 'rs_applied_object-roles';	// v 1.1: changed cache key from "object_roles" to "object-roles" to match new key format for blog, term roles
			$cache_id = 'all';
			$cache = wpp_cache_get($cache_id, $cache_flag);
			
			$u_g_clause = '';
		}
		
		if ( is_array($cache) )
			return $cache;
		
		$role_handles = array();
			
		global $wpdb;
		
		// object roles support date limits, but content date limits (would be redundant and a needless performance hit)
		$duration_clause = scoper_get_duration_clause( '', $wpdb->user2role2object_rs );

		if ( $role_names = scoper_get_col("SELECT DISTINCT role_name FROM $wpdb->user2role2object_rs WHERE role_type='rs' AND scope='object' $duration_clause $u_g_clause") )
			$role_handles = scoper_role_names_to_handles($role_names, 'rs', true); //arg: return role keys as array key
		
		if ( is_object($user) ) {
			$user->cache_force_set($role_handles, $cache_flag);
		} else
			wpp_cache_force_set($cache_id, $role_handles, $cache_flag);
		
		return $role_handles;
	}
	
	function user_can_edit_blogwide( $src_name = '', $object_type = '', $args = '' ) {
		if ( is_administrator_rs($src_name) )
			return true;
	
		require_once( dirname(__FILE__).'/admin/permission_lib_rs.php' );
		return user_can_edit_blogwide_rs($src_name, $object_type, $args);
	}
	
} // end Scoper class


// (needed to stop using shared core library function with Revisionary due to changes in meta_flag handling)
if ( ! function_exists('awp_user_can') ) {
function awp_user_can( $reqd_caps, $object_id = 0, $user_id = 0, $meta_flags = array() ) {	
	return cr_user_can( $reqd_caps, $object_id, $user_id, $meta_flags );
}
}

// equivalent to current_user_can, 
// except it supports array of reqd_caps, supports non-current user, and does not support numeric reqd_caps
function cr_user_can( $reqd_caps, $object_id = 0, $user_id = 0, $meta_flags = array() ) {	
	if ( ! $user_id ) {
		if ( function_exists('is_super_admin') && is_super_admin() ) 
			return true;
			
		if ( is_content_administrator_rs() || ! function_exists( '_cr_user_can' ) )
			return current_user_can( $reqd_caps );
	}

	if ( function_exists( '_cr_user_can' ) )
		return _cr_user_can( $reqd_caps, $object_id, $user_id, $meta_flags );
}

?>