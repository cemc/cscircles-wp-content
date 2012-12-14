<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/hardway/cache-persistent.php');

//if ( ! awp_ver( '3.0' ) )
//	require_once( dirname(__FILE__).'/wp-legacy_rs.php' );

// As of WP 3.0, this is not set until admin_header is loaded, and remains unset for non-admin urls.  To simplify subsequent checks, set it early and universally.
$GLOBALS['plugin_page_cr'] = ( is_admin() && isset( $_GET['page'] ) ) ? $_GET['page'] : '';

if ( is_admin() )
	require_once( dirname(__FILE__).'/admin/admin-init_rs.php' );
	
if ( IS_MU_RS )
	require_once( dirname(__FILE__).'/mu-init_rs.php' );

if ( IS_MU_RS || defined('SCOPER_FORCE_FILE_INCLUSIONS') ) {
	// workaround to avoid file error on get_home_path() call
	if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
		include_once( ABSPATH . '/wp-admin/includes/file.php' );	
}

// If an htaccess regeneration is triggered by somebody else, insert our rules (normal non-MU installations).
if ( ! defined( 'SCOPER_NO_HTACCESS' ) )
	add_filter( 'mod_rewrite_rules', 'scoper_mod_rewrite_rules' );

add_action( 'delete_option', 'scoper_maybe_rewrite_inclusions' );
add_action( 'delete_transient_rewrite_rules', 'scoper_rewrite_inclusions' );

// some options can be overridden by constant definition
add_filter( 'site_options_rs', 'scoper_apply_constants', 99 );
add_filter( 'options_rs', 'scoper_apply_constants', 99 );

function scoper_log_init_action() {
	define ( 'INIT_ACTION_DONE_RS', true );

	require_once( dirname(__FILE__).'/db-config_rs.php');
	
	$func = "require('" . dirname(__FILE__) . "/db-config_rs.php');";
	add_action( 'switch_blog', create_function( '', $func ) );
	
	if ( is_admin() )
		scoper_load_textdomain();

	elseif ( defined('XMLRPC_REQUEST') )
		require_once( dirname(__FILE__).'/xmlrpc_rs.php');
}

function scoper_act_set_current_user() {
	$id = ( ! empty($GLOBALS['current_user']) ) ? $GLOBALS['current_user']->ID : 0;

	if ( defined('MULTISITE') && MULTISITE ) {
		scoper_version_check();
	}

	if ( $id || defined( 'SCOPER_ANON_METAGROUP' ) ) {
		require_once( dirname(__FILE__).'/scoped-user.php');
		$GLOBALS['current_rs_user'] = new WP_Scoped_User($id);
		
		// other properties (blog_roles, assigned_term_roles, term_roles) will be set as populated
		foreach( array( 'groups', 'assigned_blog_roles' ) as $var ) {
			$GLOBALS['current_user']->$var = $GLOBALS['current_rs_user']->$var;
		}
	} else {
		require_once( dirname(__FILE__).'/scoped-user_anon.php');
		$GLOBALS['current_rs_user'] = new WP_Scoped_User_Anon();
	}

	// since sequence of set_current_user and init actions seems unreliable, make sure our current_user is loaded first
	if ( ! empty( $GLOBALS['scoper'] ) )
		return;
	elseif ( defined('INIT_ACTION_DONE_RS') )
		scoper_init();
	else {
		static $done = false;
		if ( $done ) { return; } else { $done = true; }
		$priority = ( defined( 'SCOPER_EARLY_INIT' ) ) ? 3 : 50;
		add_action('init', 'scoper_init', $priority);
	}
}

function scoper_init() {
	global $scoper;

	// Work around bug in More Taxonomies (and possibly other plugins) where category taxonomy is overriden without setting it public
	foreach( array( 'category', 'post_tag' ) as $taxonomy ) {
		if ( isset( $GLOBALS['wp_taxonomies'][$taxonomy] ) )
			$GLOBALS['wp_taxonomies'][$taxonomy]->public = true;
	}
	
	if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) ) {
		global $scoper_sitewide_options;
		$scoper_sitewide_options = apply_filters( 'sitewide_options_rs' , $scoper_sitewide_options );	
	}

	require_once( dirname(__FILE__).'/wp-cap-helper_cr.php' );
	WP_Cap_Helper_CR::establish_status_caps();
	WP_Cap_Helper_CR::force_distinct_post_caps();
	WP_Cap_Helper_CR::force_distinct_taxonomy_caps();
	
	if ( is_admin() ) {
		require_once( dirname(__FILE__).'/admin/admin-init_rs.php' );	// TODO: why is the require statement up top not sufficient for NGG 1.7.2 uploader?
		scoper_admin_init();	
	}

	//log_mem_usage_rs( 'scoper_admin_init done' );
		
	require_once( dirname(__FILE__).'/scoped-user.php');
	require_once( dirname(__FILE__).'/role-scoper_main.php');
	
	//log_mem_usage_rs( 'require role-scoper_main' );
	
	if ( empty($scoper) ) {		// set_current_user may have already triggered scoper creation and role_cap load
		$scoper = new Scoper();
		
		//log_mem_usage_rs( 'new Scoper done' );
		$scoper->init();
	}

	// ensure that content administrators (as defined by SCOPER_CONTENT_ADMIN_CAP) have all caps for custom types by default
	if ( is_content_administrator_rs() ) {
		global $current_rs_user;

		if ( ! empty($current_rs_user) ) { // user object not set when scoper_init() is manually invoked to support htaccess rule generation on plugin activation
			foreach ( get_post_types( array('public' => true, '_builtin' => false) ) as $name )
				$current_rs_user->assigned_blog_roles[ANY_CONTENT_DATE_RS]["rs_{$name}_editor"] = true;
			
			$taxonomies = get_taxonomies( array('public' => true, '_builtin' => false) );
			$taxonomies []= 'nav_menu';
			foreach ( $taxonomies as $name )
				$current_rs_user->assigned_blog_roles[ANY_CONTENT_DATE_RS]["rs_{$name}_manager"] = true;
			
			$current_rs_user->merge_scoped_blogcaps();
			$GLOBALS['current_user']->allcaps = array_merge( $GLOBALS['current_user']->allcaps, $current_rs_user->allcaps );
			$GLOBALS['current_user']->assigned_blog_roles = $current_rs_user->assigned_blog_roles;
		}
	}
	
	if ( ! empty($_GET['action']) && ( 'expire_file_rules' == $_GET['action'] ) ) {
		require_once( dirname(__FILE__).'/attachment-helper_rs.php' );
		scoper_requested_file_rule_expire();
	}

	//log_mem_usage_rs( 'scoper->init() done' );
}

function rs_get_user( $user_id, $name = '', $args = array() ) {
	if ( ! class_exists( 'WP_Scoped_User' ) )
		require_once( dirname(__FILE__).'/scoped-user.php');
	
	return new WP_Scoped_User( $user_id, $name, $args );
}

function scoper_load_textdomain() {
	if ( defined( 'SCOPER_TEXTDOMAIN_LOADED' ) )
		return;

	load_plugin_textdomain( 'scoper', false, SCOPER_FOLDER . '/languages' );

	define('SCOPER_TEXTDOMAIN_LOADED', true);
}

function scoper_get_init_options() {
	define ( 'SCOPER_CUSTOM_USER_BLOGCAPS', scoper_get_option('custom_user_blogcaps') );		// TODO: eliminate this?
	
	$define_groups = scoper_get_option('define_usergroups');
	define ( 'DEFINE_GROUPS_RS', $define_groups );
	define ( 'GROUP_ROLES_RS', $define_groups && scoper_get_option('enable_group_roles') );
	
	define ( 'USER_ROLES_RS', scoper_get_option('enable_user_roles') );
	
	if ( ! defined('DISABLE_PERSISTENT_CACHE') && ! scoper_get_option('persistent_cache') )
		define ( 'DISABLE_PERSISTENT_CACHE', true );

	wpp_cache_init( IS_MU_RS && scoper_establish_group_scope() );
}

function scoper_refresh_options() {
	if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) ) {
		scoper_retrieve_options(true);
		scoper_refresh_options_sitewide();
	}
		
	scoper_retrieve_options(false);
	
	scoper_refresh_default_options();
}

function scoper_set_conditional_defaults() {
	// if the WP installation has 100 or more users at initial Role Scoper installation, default to CSV input of username for role assignment	
	global $wpdb;
	$num_users = $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users" );
	if ( $num_users > 99 )
		update_option( 'scoper_user_role_assignment_csv', 1 );
}

function scoper_refresh_default_options() {
	global $scoper_default_options;

	require_once( dirname(__FILE__).'/defaults_rs.php');
	$scoper_default_options = apply_filters( 'default_options_rs', scoper_default_options() );
	
	if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) )
		scoper_apply_custom_default_options( 'scoper_default_options' );
}

function scoper_refresh_default_otype_options() {
	global $scoper_default_otype_options;
	
	require_once( dirname(__FILE__).'/defaults_rs.php');
	$scoper_default_otype_options = apply_filters( 'default_otype_options_rs', scoper_default_otype_options() );
	
	// compat workaround for old versions of Role Scoping for NGG which use old otype option key structure
	if ( isset( $scoper_default_otype_options['use_term_roles']['ngg_gallery:ngg_gallery'] ) && ( ! is_array($scoper_default_otype_options['use_term_roles']['ngg_gallery:ngg_gallery']) ) )
		$scoper_default_otype_options['use_term_roles']['ngg_gallery:ngg_gallery'] = array( 'ngg_album' => 1 );
		
	if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) )
		scoper_apply_custom_default_options( 'scoper_default_otype_options' );
}

function scoper_get_default_otype_options() {
	if ( did_action( 'scoper_init') ) {
		global $scoper_default_otype_options;
		
		if ( ! isset( $scoper_default_otype_options ) )
			scoper_refresh_default_otype_options();
			
		return $scoper_default_otype_options;
	} else
		return scoper_default_otype_options();	
}

function scoper_delete_option( $option_basename, $sitewide = -1 ) {
	// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
	if ( -1 === $sitewide ) {
		global $scoper_options_sitewide;
		$sitewide = isset( $scoper_options_sitewide ) && ! empty( $scoper_options_sitewide[$option_basename] );
	}

	if ( $sitewide ) {
		global $wpdb;
		scoper_query( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = '$wpdb->siteid' AND meta_key = 'scoper_$option_basename'" );
	} else 
		delete_option( "scoper_$option_basename" );
}

function scoper_update_option( $option_basename, $option_val, $sitewide = -1 ) {
	// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
	if ( -1 === $sitewide ) {
		global $scoper_options_sitewide;
		$sitewide = isset( $scoper_options_sitewide ) && ! empty( $scoper_options_sitewide[$option_basename] );
	}
	
	if ( $sitewide ) {
		global $scoper_site_options;
		$scoper_site_options[$option_basename] = $option_val;
		
		//d_echo("<br /><br />sitewide: $option_basename, value : " . maybe_serialize($option_val) );
		update_site_option( "scoper_$option_basename", $option_val );
	} else {
		//d_echo("<br />blogwide: $option_basename" );
		global $scoper_blog_options;
		$scoper_blog_options[$option_basename] = $option_val;

		update_option( "scoper_$option_basename", $option_val );
	}
}

function scoper_apply_constants($stored_options) {
	// If file filtering option is on but the DISABLE constant has been set, turn the option off and regenerate .htaccess
	if ( defined( 'DISABLE_ATTACHMENT_FILTERING' ) && DISABLE_ATTACHMENT_FILTERING ) {
		if ( ! empty( $stored_options['scoper_file_filtering'] ) ) {
			// in this case, we need to both convert the option value to constant value AND trigger .htaccess regeneration
			$stored_options['file_filtering'] = 0;
			update_option( 'scoper_file_filtering', 0 );
			scoper_flush_site_rules();
			scoper_expire_file_rules();	
		}
	}

	return $stored_options; 
}

function scoper_retrieve_options( $sitewide = false ) {
	global $wpdb;
	
	if ( $sitewide ) {
		global $scoper_site_options;
		
		$scoper_site_options = array();

		if ( $results = scoper_get_results( "SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE site_id = '$wpdb->siteid' AND meta_key LIKE 'scoper_%'" ) )
			foreach ( $results as $row )
				$scoper_site_options[$row->meta_key] = $row->meta_value;
				
		$scoper_site_options = apply_filters( 'site_options_rs', $scoper_site_options );
		return $scoper_site_options;

	} else {
		global $scoper_blog_options;
		
		$scoper_blog_options = array();
		
		if ( $results = scoper_get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'scoper_%'") )
			foreach ( $results as $row )
				$scoper_blog_options[$row->option_name] = $row->option_value;
				
		$scoper_blog_options = apply_filters( 'options_rs', $scoper_blog_options );
		return $scoper_blog_options;
	}
}


function scoper_get_site_option( $option_basename ) {
	return scoper_get_option( $option_basename, true );
}

function scoper_get_option($option_basename, $sitewide = -1, $get_default = false) {
	global $scoper_default_options;
	
	//if ( empty( $scoper_default_options ) && did_action( 'scoper_init' ) )	// Make sure other plugins have had a chance to apply any filters to default options
	if ( empty( $scoper_default_options ) )
		scoper_refresh_default_options();

	if ( ! $get_default ) {
		// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
		if ( -1 === $sitewide ) {
			global $scoper_options_sitewide;
			$sitewide = isset( $scoper_options_sitewide ) && ! empty( $scoper_options_sitewide[$option_basename] );
		}
	
		//dump($scoper_options_sitewide);
		
		if ( $sitewide ) {
			// this option is set site-wide
			global $scoper_site_options;
			
			if ( ! isset($scoper_site_options) || is_null($scoper_site_options) )
				$scoper_site_options = scoper_retrieve_options( true );	
				
			if ( isset($scoper_site_options["scoper_{$option_basename}"]) )
				$optval = $scoper_site_options["scoper_{$option_basename}"];
			
		} else {
			//dump($option_basename);
			global $scoper_blog_options;
			
			if ( ! isset($scoper_blog_options) || is_null($scoper_blog_options) )
				$scoper_blog_options = scoper_retrieve_options( false );	
				
			if ( isset($scoper_blog_options["scoper_$option_basename"]) )
				$optval = $scoper_blog_options["scoper_$option_basename"];
		}
	}
	
	//dump($get_default);
	//dump($scoper_blog_options);

	if ( ! isset( $optval ) ) {
		if ( ! empty($scoper_default_options) && ! empty( $scoper_default_options[$option_basename] ) )
			$optval = $scoper_default_options[$option_basename];
			
		if ( ! isset($optval) ) {
			global $scoper_default_otype_options;
			if ( isset( $scoper_default_otype_options[$option_basename] ) )
				return $scoper_default_otype_options[$option_basename];
			
			/*
			else {
				static $hardcode_option_defaults;
			
				if ( empty($hardcode_option_defaults) ) {
					require_once( dirname(__FILE__).'/defaults_rs.php');
					$hardcode_option_defaults = scoper_default_options();
				}
					
				if ( isset($hardcode_option_defaults[$option_basename]) )
					$optval = $hardcode_option_defaults[$option_basename];	
				else {
					static $hardcode_otype_option_defaults;
					
					if ( empty($hardcode_otype_option_defaults) )
						$hardcode_otype_option_defaults = scoper_default_otype_options();
					
					if ( isset($hardcode_otype_option_defaults[$option_basename]) )
						$optval = $hardcode_otype_option_defaults[$option_basename];	
				}	
			}
			*/
		}
	}

	if ( isset($optval) )
		$optval = maybe_unserialize($optval);
	else
		$optval = '';
		
	// merge defaults into stored option array
	//if ( ! empty( $GLOBALS['scoper_option_arrays'][$option_basename] ) ) {
		
	if ( 'use_post_types' == $option_basename ) {
		static $default_post_types;
		if ( empty($default_post_types) || ! did_action('init') ) {
			$default_post_types = array();
			
			foreach ( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) as $type )
				$default_post_types[$type] = 1;
		}
		
		$optval = array_merge( $default_post_types, (array) $optval );

	} elseif ( 'use_taxonomies' == $option_basename ) {	
		static $default_taxonomies;
		if ( empty($default_taxonomies) || ! did_action('init') ) {
			$default_taxonomies = array();
			
			$taxonomies = get_taxonomies( array( 'public' => true ) );
			$taxonomies[] = 'nav_menu';

			foreach ( $taxonomies as $taxonomy )
				$default_taxonomies[$taxonomy] = ( isset( $GLOBALS['rs_default_disable_taxonomies'][$taxonomy] ) ) ? 0 : 1;
		}
		
		$optval = array_diff_key( array_merge( $default_taxonomies, (array) $optval ), $GLOBALS['rs_forbidden_taxonomies'] );  // remove forbidden taxonomies, even if previously stored
	} elseif ( 'use_term_roles' == $option_basename ) {
		if ( $optval ) {
			foreach( array_keys($optval) as $key ) {
				$optval[$key] = array_diff_key( $optval[$key], $GLOBALS['rs_forbidden_taxonomies'] ); // remove forbidden taxonomies, even if previously stored
			}
		}
	}
	
	return $optval;
}

function scoper_get_otype_option( $option_main_key, $src_name, $object_type = '', $access_name = '')  {
	static $otype_options;

	// make sure we indicate object roles disabled if object type usage is completely disabled
	if ( 'use_object_roles' == $option_main_key ) {
		if ( ( 'post' == $src_name ) && $object_type ) {
			$use_object_types = scoper_get_option( 'use_post_types' );
			if ( ( ! empty($use_object_types) ) && empty( $use_object_types[$object_type] ) )	// since default is to enable all object types, don't interfere if no use_object_types option is stored
				return false;
		}
	}
	
	$key = "$option_main_key,$src_name,$object_type,$access_name";

	if ( empty($otype_options) )
		$otype_options = array();
	elseif ( isset($otype_options[$key]) )
		return $otype_options[$key];

	$stored_option = scoper_get_option($option_main_key);

	$default_otype_options = scoper_get_default_otype_options();
	
	// RS stores all portions of the otype option array together, but blending is needed because RS Extensions or other plugins can filter the default otype options array for specific taxonomies / object types
	$optval = awp_blend_option_array( 'scoper_', $option_main_key, $default_otype_options, 1, $stored_option );
	
	// note: access_name-specific entries are not valid for most otype options (but possibly for teaser text front vs. rss)
	if ( isset ( $optval[$src_name] ) )
		$retval = $optval[$src_name];
	
	if ( $object_type && isset( $optval["$src_name:$object_type"] ) )
		$retval = $optval["$src_name:$object_type"];
	
	if ( $object_type && $access_name && isset( $optval["$src_name:$object_type:$access_name"] ) )
		$retval = $optval["$src_name:$object_type:$access_name"];
	

	// if no match was found for a source request, accept any non-empty otype match
	if ( ! $object_type && ! isset($retval) )
		foreach ( $optval as $src_otype => $val )
			if ( $val && ( 0 === strpos( $src_otype, "$src_name:" ) ) )
				$retval = $val;

	if ( ! isset($retval) )
		$retval = array();
		
	$otype_options[$key] = $retval;
	
	return $retval;
}

function is_taxonomy_used_rs( $taxonomy ) {
	global $scoper_default_otype_options;

	$stored_option = (array) scoper_get_option( 'use_term_roles' );
	foreach( array_merge( array_keys($scoper_default_otype_options['use_term_roles']), array_keys($stored_option) ) as $key ) {
		$term_roles[$key] = ( isset($scoper_default_otype_options['use_term_roles'][$key]) ) ? $scoper_default_otype_options['use_term_roles'][$key] : array();
		
		if ( isset( $stored_option[$key] ) )
			$term_roles[$key] =	array_merge( $term_roles[$key], $stored_option[$key] );
	}

	foreach ( $term_roles as $taxonomies )  // keyed by src_otype
		if ( ! empty( $taxonomies[$taxonomy] ) )
			return true;
}

function scoper_maybe_rewrite_inclusions ( $option_name = '' ) {
	if ( $option_name == 'rewrite_rules' )
		scoper_rewrite_inclusions();
}

function scoper_rewrite_inclusions ( $option_name = '' ) {
	// force inclusion of required files in case flush_rules() is called from outside wp-admin, to prevent error when calling get_home_path() function
	if ( file_exists( ABSPATH . '/wp-admin/includes/misc.php' ) )
		include_once( ABSPATH . '/wp-admin/includes/misc.php' );
	
	if ( file_exists( ABSPATH . '/wp-admin/includes/file.php' ) )
		include_once( ABSPATH . '/wp-admin/includes/file.php' );	
}

// htaccess directive intercepts direct access to uploaded files, converts to WP call with custom args to be caught by subsequent parse_query filter
// parse_query filter will return content only if user can read a containing post/page
function scoper_mod_rewrite_rules ( $rules ) {
	if ( defined( 'SCOPER_NO_HTACCESS' ) )
		return $rules;
	
	$file_filtering = scoper_get_option( 'file_filtering' );

	global $scoper;
	if ( ! isset($scoper) || is_null($scoper) )
		scoper_init();
	
	require_once( dirname(__FILE__).'/rewrite-rules_rs.php' );

	if ( IS_MU_RS ) {
		if ( $file_filtering ) {
			require_once( dirname(__FILE__).'/rewrite-mu_rs.php' );
			$rules = ScoperRewriteMU::insert_site_rules( $rules );
		}
	} else {
		if ( ! strpos( $rules, 'BEGIN Role Scoper' ) ) {
			$rs_rules = ScoperRewrite::build_site_rules();
			$rules .= $rs_rules;
		}
	}

	return $rules;
}

function scoper_flush_site_rules() {
	require_once( dirname(__FILE__).'/rewrite-rules_rs.php' );
	ScoperRewrite::update_site_rules( true );
}

function scoper_clear_site_rules() {
	require_once( dirname(__FILE__).'/rewrite-rules_rs.php' );
	remove_filter('mod_rewrite_rules', 'scoper_mod_rewrite_rules');
	ScoperRewrite::update_site_rules( false );
}

function scoper_flush_file_rules() {
	require_once( dirname(__FILE__).'/rewrite-rules_rs.php' );
	ScoperRewrite::update_blog_file_rules();
}


function scoper_clear_all_file_rules() {
	if ( IS_MU_RS ) {
		require_once( dirname(__FILE__).'/rewrite-mu_rs.php' );
		ScoperRewriteMU::clear_all_file_rules();
	} else {
		require_once( dirname(__FILE__).'/rewrite-rules_rs.php' );
		ScoperRewrite::update_blog_file_rules( false );
	} 
}


// forces content rules to be regenerated in every MU blog at next access
function scoper_expire_file_rules() {
	if ( IS_MU_RS )
		scoper_update_option( 'file_htaccess_min_date', agp_time_gmt(), true );
	else {
		if ( did_action( 'scoper_init' ) )
			scoper_flush_file_rules();  // for non-MU, just regenerate the file rules (for uploads folder) now
		else
			add_action( 'scoper_init', 'scoper_flush_file_rules' );
	}
}


function scoper_version_check() {
	$ver_change = false;

	$ver = get_option('scoper_version');
	
	if ( empty($ver['db_version']) || version_compare( SCOPER_DB_VERSION, $ver['db_version'], '!=') ) {
		$ver_change = true;
		
		require_once( dirname(__FILE__).'/db-setup_rs.php');
		scoper_db_setup($ver['db_version']);
	}

	// These maintenance operations only apply when a previous version of RS was installed 
	if ( ! empty($ver['version']) ) {
		
		if ( version_compare( SCOPER_VERSION, $ver['version'], '!=') ) {
			$ver_change = true;
			
			require_once( dirname(__FILE__).'/admin/update_rs.php');
			scoper_version_updated( $ver['version'] );

			scoper_check_revision_settings();
		}
		
	} else {
		// first-time install (or previous install was totally wiped)
		require_once( dirname(__FILE__).'/admin/update_rs.php');
		scoper_set_default_rs_roledefs();
	}

	if ( $ver_change ) {
		$ver = array(
			'version' => SCOPER_VERSION, 
			'db_version' => SCOPER_DB_VERSION
		);
		
		update_option( 'scoper_version', $ver );
	}
}

function scoper_get_role_handle($role_name, $role_type) {
	return $role_type . '_' . str_replace(' ', '_', $role_name);
}

function scoper_role_names_to_handles($role_names, $role_type, $fill_keys = false) {
	$role_names = (array) $role_names;	

	$role_handles = array();
	foreach ( $role_names as $role_name )
		if ( $fill_keys )
			$role_handles[ $role_type . '_' . str_replace(' ', '_', $role_name) ] = 1;
		else
			$role_handles[]= $role_type . '_' . str_replace(' ', '_', $role_name);
			
	return $role_handles;
}

function scoper_explode_role_handle($role_handle) {
	global $scoper_role_types;
	$arr = (object) array();
	
	foreach ( $scoper_role_types as $role_type ) {
		if ( 0 === strpos($role_handle, $role_type . '_') ) {
			$arr->role_type = $role_type;
			$arr->role_name = substr($role_handle, strlen($role_type) + 1);
			break;
		}
	}
	
	return $arr;
}

function scoper_role_handles_to_names($role_handles) {
	global $scoper_role_types;

	$role_names = array();
	foreach ( $role_handles as $role_handle ) {
		foreach ( $scoper_role_types as $role_type )
			$role_handle = str_replace( $role_type . '_', '', $role_handle);
			
		$role_names[] = $role_handle;
	}
	
	return $role_names;
}

function rs_notice($message) {
	if ( defined( 'RS_DEBUG' ) ) {
		require_once( dirname(__FILE__).'/error_rs.php' );
		awp_notice( $message, 'Role Scoper' );
	}
}


// db wrapper methods allow us to easily avoid re-filtering our own query
function scoper_db_method($method_name, $query) {
	global $wpdb;
	//static $buffer;
	
	if ( is_admin() ) { // Low-level query filtering is necessary due to WP API limitations pertaining to admin GUI.
						// But make sure we don't chew our own cud (currently not an issue for front end)
		global $scoper_status;
	
		if ( empty($scoper_status) )
			$scoper_status = (object) array();
			
		/*
		$use_buffer = ('query' != $method_name ) && empty($_POST);
		
		if ( $use_buffer ) {
			$key = md5($query);
			if ( isset($buffer[$key]) )
				return $buffer[$key];
		}
		*/

		$scoper_status->querying_db = true;
		$results = call_user_func( array(&$wpdb, $method_name), $query );
		$scoper_status->querying_db = false;
		
		//if ( $use_buffer )
		//	$buffer[$key] = $results;
		
		return $results;
	} else
		return call_user_func( array(&$wpdb, $method_name), $query );
}

function scoper_get_results($query) {
	return scoper_db_method('get_results', $query);
}

function scoper_get_row($query) {
	return scoper_db_method('get_row', $query);
}

function scoper_get_col($query) {
	return scoper_db_method('get_col', $query);
}

function scoper_get_var($query) {
	return scoper_db_method('get_var', $query);
}

function scoper_query($query) {
	return scoper_db_method('query', $query);
}

function scoper_querying_db() {
	if ( isset($GLOBALS['scoper_status']) )
		return ! empty($GLOBALS['scoper_status']->querying_db);
}

function scoper_any_role_limits() {
	global $wpdb;
	
	$any_limits = (object) array( 'date_limited' => false, 'start_date_gmt' => false, 'end_date_gmt' => false, 'content_date_limited' => false, 'content_min_date_gmt' => false, 'content_max_date_gmt' => false );

	if ( scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE date_limited > 0 LIMIT 1" ) ) {
		$any_limits->date_limited = true;
		
		if ( scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE start_date_gmt > 0 LIMIT 1" ) )
			$any_limits->start_date_gmt = true;
			
		if ( scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE end_date_gmt != '" . SCOPER_MAX_DATE_STRING . "' LIMIT 1" ) )
			$any_limits->end_date_gmt = true;
	}
	
	if ( scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE content_date_limited > 0 LIMIT 1" ) ) {
		$any_limits->content_date_limited = true;
		
		if ( scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE content_min_date_gmt > 0 LIMIT 1" ) )
			$any_limits->content_min_date_gmt = true;
			
		if ( scoper_get_var( "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE content_max_date_gmt != '" . SCOPER_MAX_DATE_STRING . "' LIMIT 1" ) )
			$any_limits->content_max_date_gmt = true;
	}
	
	return $any_limits;
}

function scoper_get_duration_clause( $content_date_comparison = '', $table_prefix = 'uro', $enforce_duration_limits = true ) {
	static $any_role_limits;
	
	$clause = '';
	
	if ( $enforce_duration_limits && scoper_get_option( 'role_duration_limits' ) ) {
		if ( ! isset($any_role_limits) )
			$any_role_limits = scoper_any_role_limits();
		
		if ( $any_role_limits->date_limited ) {
			$current_time = current_time( 'mysql', 1 );
			
			$subclauses = array();
			
			if ( $any_role_limits->start_date_gmt )
				$subclauses []= "$table_prefix.start_date_gmt <= '$current_time'";
			
			if ( $any_role_limits->end_date_gmt )
				$subclauses []= "$table_prefix.end_date_gmt >= '$current_time'";
			
			$role_duration_clause = implode( " AND ", $subclauses );

			$clause = " AND ( $table_prefix.date_limited = '0' OR ( $role_duration_clause ) ) ";
		}
	}

	if ( $content_date_comparison && scoper_get_option( 'role_content_date_limits' ) ) {
		
		if ( ! isset($any_role_limits) )
			$any_role_limits = scoper_any_role_limits();
		
		if ( $any_role_limits->content_date_limited ) {
			$current_time = current_time( 'mysql', 1 );
			
			$subclauses = array();
			
			if ( $any_role_limits->content_min_date_gmt )
				$subclauses []= "$content_date_comparison >= $table_prefix.content_min_date_gmt";
			
			if ( $any_role_limits->content_max_date_gmt )
				$subclauses []= "$content_date_comparison <= $table_prefix.content_max_date_gmt";
			
			$content_date_clause = implode( " AND ", $subclauses );

			$clause .= " AND ( $table_prefix.content_date_limited = '0' OR ( $content_date_clause ) ) ";
		}
	}
	
	return $clause;
}

function scoper_get_property_array( &$arr, $id_prop, $buffer_prop ) {
	if ( ! is_array($arr) )
		return;

	$buffer = array();
		
	foreach ( array_keys($arr) as $key )
		$buffer[ $arr[$key]->$id_prop ] = ( isset($arr[$key]->$buffer_prop) ) ? $arr[$key]->$buffer_prop : '';

	return $buffer;
}

function scoper_restore_property_array( &$target_arr, $buffer_arr, $id_prop, $buffer_prop ) {
	if ( ! is_array($target_arr) || ! is_array($buffer_arr) )
		return;
		
	foreach ( array_keys($target_arr) as $key )
		if ( isset( $buffer_arr[ $target_arr[$key]->$id_prop ] ) )
			$target_arr[$key]->$buffer_prop = $buffer_arr[ $target_arr[$key]->$id_prop ];
}

function scoper_get_taxonomy_usage( $src_name, $object_types = '' ) {
	$taxonomies = array();
	$object_types = (array) $object_types;

	foreach( $object_types as $object_type ) {
		if ( taxonomy_exists( $object_type ) )
			$use_taxonomies = array( $object_type => 1 );	// taxonomy management roles are always applied
		else
			$use_taxonomies = scoper_get_otype_option( 'use_term_roles', $src_name, $object_type );

		$taxonomies = array_merge( $taxonomies, array_intersect( (array) $use_taxonomies, array( 1 ) ) );  // array cast prevents PHP warning on first-time execution following update to RS 1.2
	}
	
	if ( $taxonomies ) {
		// make sure we indicate non-usage of term roles for taxonomies that are completely disabled for RS
		if ( 'post' == $src_name ) {
			$use_taxonomies = scoper_get_option( 'use_taxonomies' );
			$taxonomies = array_intersect_key( $taxonomies, array_intersect( $use_taxonomies, array( 1 ) ) );
		}
		
		return array_keys($taxonomies);
	} else
		return array();
}

function cr_find_post_type( $post_arg = '', $return_default = true ) {
	// the post argument is already an object.  Just return its post_type property
	if ( is_object( $post_arg ) )
		return $post_arg->post_type;

	if ( $post_arg ) {
		// post_arg is not an object.  If its value matches global post, use that
		if ( ! empty($GLOBALS['post']) && is_object($GLOBALS['post']) && ( $post_arg == $GLOBALS['post']->ID ) && ( 'revision' != $GLOBALS['post']->post_type ) ) {
			$_type = $GLOBALS['post']->post_type;

			if ( 'revision' == $_type )
				$_type = get_post_field( 'post_type', $GLOBALS['post']->post_parent );
		}
			
		// we have a post id but it doesn't match global post, so retrieve it
		if ( $_post = get_post( $post_arg ) ) {
			$_type = $_post->post_type;
			
			if ( 'revision' == $_type )
				$_type = get_post_field( 'post_type', $_post->post_parent );
		}
		
		if ( ! empty($_type) )
			return $_type;
	}
	
	// no post id was passed in, or we couldn't retrieve it for some reason, so check $_REQUEST args
	global $pagenow;

	if ( ! empty( $GLOBALS['post'] ) && is_object($GLOBALS['post']) && ! empty( $GLOBALS['post']->post_type ) ) {
		$object_type = $GLOBALS['post']->post_type;

		if ( 'revision' == $object_type )
			$object_type = get_post_field( 'post_type', $GLOBALS['post']->post_parent );

	} elseif ( ! empty( $GLOBALS['wp_query']->queried_object ) && ! empty( $GLOBALS['wp_query']->queried_object->post_type ) ) {
		$object_type = $GLOBALS['wp_query']->queried_object->post_type;
		
	} elseif ( ! empty( $GLOBALS['wp_query']->queried_object ) && ! empty( $GLOBALS['wp_query']->queried_object->name ) ) {
		$object_type = $GLOBALS['wp_query']->queried_object->name;
		
	} elseif ( in_array( $pagenow, array( 'post-new.php', 'edit.php' ) ) ) {
		$object_type = ! empty( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
		
	} elseif ( in_array( $pagenow, array( 'edit-tags.php' ) ) ) {
		$object_type = ! empty( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : 'category';

	} elseif ( in_array( $pagenow, array( 'admin-ajax.php' ) ) && ! empty( $_REQUEST['taxonomy'] ) ) {
		$object_type = $_REQUEST['taxonomy'];
		
	} elseif ( ! empty( $_POST['post_ID'] ) ) {
		if ( $_post = get_post( $_POST['post_ID'] ) )
			$object_type = $_post->post_type;
				
	} elseif ( ! empty( $_GET['post'] ) ) {	 // post.php
		if ( $_post = get_post( $_GET['post'] ) )
			$object_type = $_post->post_type;
	
	} elseif( ! empty( $GLOBALS['scoper_object_type'] ) ) {
		$object_type = $GLOBALS['scoper_object_type'];

	} elseif( ! empty($_SERVER) && is_array($_SERVER) && isset( $_SERVER['HTTP_REFERER'] ) && is_string($_SERVER['HTTP_REFERER']) ) {
		if ( $pos = strpos( $_SERVER['HTTP_REFERER'], '?' ) ) {
			$arg_str = substr( $_SERVER['HTTP_REFERER'], $pos + 1 );
			$args = wp_parse_args( $arg_str );
	
			if ( ! empty( $args['post'] ) ) {
				if ( $_post = get_post( $args['post'] ) ) {
					if ( isset( $_post->post_type ) ) {
						//rs_errlog( "cr_find_post_type - {$_post->post_type} from http_referer" );
						return $_post->post_type;
					}
				}
			}	
		}
	}

	if ( empty($object_type) ) {
		if ( $return_default ) // default to post type
			return 'post';
	} elseif ( 'any' != $object_type ) {
		return $object_type;
	}
}

function cr_find_object_type( $src_name, $object = '' ) {
	if ( 'post' == $src_name )
		return cr_find_post_type( $object );
	
	global $scoper;
	$src = $scoper->data_sources->get( $src_name );
	$object_id = ( is_object($object ) ) ? $src->cols->id : $object;

	return $scoper->data_sources->detect( 'type', $src_name, $object_id );
}

function cr_get_type_object( $src_name, $object_type ) {
	if ( 'post' == $src_name )
		return get_post_type_object( $object_type );
		
	global $scoper;
	return $scoper->data_sources->member_property( $src_name, 'object_types', $object_type );
}

function scoper_get_object_id($src_name = 'post', $object_type = '') {
	global $scoper;
	return $scoper->data_sources->detect( 'id', $src_name, 0, $object_type );	
}


function is_administrator_rs( $src_or_tx = '', $admin_type = 'content', $user = '' ) {
	if ( ! $user ) {
		global $current_user;
		$user = $current_user;
		
		if ( IS_MU_RS && function_exists('is_super_admin') && is_super_admin() ) {
			return true;
		}
	}

	if ( empty($user->ID) )
		return false;
		
	$return = '';

	$admin_cap_name = scoper_get_administrator_cap( $admin_type );
	$return = ! empty( $user->allcaps[$admin_cap_name] );
	
	if ( ! $return && $src_or_tx && ! empty($GLOBALS['scoper']) ) {
		if ( ! is_object($src_or_tx) ) {
			global $scoper;
			if ( ! $src_or_tx = $scoper->data_sources->get( $src_or_tx ) )
				$src_or_tx = $scoper->taxonomies->get( $src_or_tx );
		}
			
		if ( is_object($src_or_tx) && ! empty($src_or_tx->defining_module) ) {
			if ( ! in_array( $src_or_tx->defining_module, array( 'role-scoper', 'wordpress', 'wp' ) ) ) {
				// user is not a universal administrator, but are they an administrator for the specified module (plugin-defined data source) ?
				static $admin_caps;
				
				if ( ! isset($admin_caps) )
					$admin_caps = apply_filters( 'define_administrator_caps_rs', array() );
		
				if ( ! empty( $admin_caps[ $src_or_tx->defining_module ] ) )
					$return = ! empty( $user->allcaps[ $admin_caps[ $src_or_tx->defining_module ] ] );
			}
		}
	}
	
	return $return;
}

function is_option_administrator_rs( $user = '' ) {
	return is_administrator_rs( '', 'option', $user );
}

function is_user_administrator_rs( $user = '' ) {
	return is_administrator_rs( '', 'user', $user );
} 

function is_content_administrator_rs( $user = '' ) {
	return is_administrator_rs( '', 'content', $user );
}

function scoper_get_administrator_cap( $admin_type ) {
	if ( ! $admin_type )
		$admin_type = 'content';
	
	// Note: to differentiate content administrator role, define a custom cap such as "administer_all_content", add it to a custom Role, and add the following line to wp-config.php: define( 'SCOPER_CONTENT_ADMIN_CAP', 'cap_name' );
	$default_cap = array( 'option' => 'manage_options', 'user' => 'edit_users', 'content' => 'activate_plugins' );

	$constant_name = 'SCOPER_' . strtoupper($admin_type) . '_ADMIN_CAP';
	$cap_name = ( defined( $constant_name ) ) ? constant( $constant_name ) : $default_cap[$admin_type];
	
	if ( 'read' == $cap_name )	// avoid catostrophic mistakes
		$cap_name = $default_cap[$admin_type];
		
	return $cap_name;
}

?>