<?php
/*
Plugin Name: Role Scoper
Plugin URI: http://agapetry.net/
Description: CMS-like permissions for reading and editing. Content-specific restrictions and roles supplement/override WordPress roles. User groups optional.
Version: 1.3.61
Author: Kevin Behrens
Author URI: http://agapetry.net/
Min WP Version: 3.0
License: GPL version 2 - http://www.opensource.org/licenses/gpl-license.php
*/

/*
Copyright (c) 2008-2013, Kevin Behrens.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

if ( defined( 'SCOPER_VERSION' ) ) {
	// don't allow two copies of RS to run simultaneously
	if ( is_admin() && isset($GLOBALS['pagenow']) && ( 'plugins.php' == $GLOBALS['pagenow'] ) && empty( $_REQUEST['deactivate'] ) ) {
		$message = sprintf( __( '<strong>Error:</strong> Multiple copies of Role Scoper activated. Only version %1$s (in folder "%2$s") is functional.', 'scoper' ), SCOPER_VERSION, SCOPER_FOLDER );
		
		add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
	}

	return;
}

define ('SCOPER_VERSION', '1.3.61');
define ('SCOPER_DB_VERSION', '1.1.4');

// No filtering on dashboard Ajax or plugin installation/update, but run this check after defining version to prevent nuisance error message from Role Scoping for NGG
if ( isset($GLOBALS['pagenow']) && in_array( $GLOBALS['pagenow'], array( 'index-extra.php', 'update.php' ) ) )
	return;

/* --- ATTACHMENT FILTERING NOTE ---
Read access to uploaded file attachments is normally filtered (via .htaccess RewriteRules) to match post/page access.
To disable this attachment filtering, copy the following line to wp-config.php:
	define('DISABLE_ATTACHMENT_FILTERING', true);

To fail with a null response (no WP 404 screen, but still includes a 404 in response header), copy the folling line to wp-config.php:
	define ('SCOPER_QUIET_FILE_404', true);

Normally, files which are in the uploads directory but have no post/page attachment will not be blocked.
To block such files, copy the following line to wp-config.php:
	define('SCOPER_BLOCK_UNATTACHED_UPLOADS', true);
	
The Hidden Content Teaser may be configured to display the first X characters of a post/page if no excerpt or more tag is available.
To specify the number of characters (default is 50), copy the following line to wp-config.php:
	define('SCOPER_TEASER_NUM_CHARS', 100); // set to any number of your choice
	
To prevent teasing of feed items even if teaser is enabled for main posts/pages listing, copy the following line to wp-config.php:
	define( 'SCOPER_NO_FEED_TEASER', true );

To disable caching of the pages / categories listing, add the following lines to wp-config.php:
	define( 'SCOPER_NO_PAGES_CACHE', true );
	define( 'SCOPER_NO_TERMS_CACHE', true );
*/

// WP role type support is dropped as of 1.2.9.  If current installation is using it, bail out - hiding all content and displaying an explanation.
if ( $prev = get_option('scoper_version') ) {
	if ( version_compare( $prev['version'], '1.3.1', '<') ) {
		if ( $role_type = get_option( 'scoper_role_type' ) ) {
			if ( 'wp' == $role_type ) {
				require_once( dirname(__FILE__).'/error_rs.php' );
				scoper_startup_error( 'wp_role_type' );
				$bail = 1;
			}
		}	
	}
}

// avoid lockout in case of editing plugin via wp-admin
if ( defined('RS_DEBUG') && RS_DEBUG && is_admin() && isset($GLOBALS['pagenow']) && in_array( $GLOBALS['pagenow'], array( 'plugin-editor.php', 'plugins.php' ) ) ) {
	if ( empty( $_REQUEST['action'] ) || ! in_array( $_REQUEST['action'], array( 'activate', 'deactivate' ) ) )
		return;
}

define ('COLS_ALL_RS', 0);
define ('COL_ID_RS', 1);
define ('COLS_ID_DISPLAYNAME_RS', 2);
define ('COL_TAXONOMY_ID_RS', 3);
define ('COL_COUNT_RS', 4);
define ('COLS_ID_NAME_RS', 5);

define ('UNFILTERED_RS', 0);
define ('FILTERED_RS', 1);
define ('ADMIN_TERMS_FILTER_RS', 2);

define ('BASE_CAPS_RS', 1);

define ('STATUS_ANY_RS', -1);

define ('ORDERBY_HIERARCHY_RS', 'hierarchy');

define( 'SCOPER_MIN_DATE_STRING', '0000-00-00 00:00:00' );
define( 'SCOPER_MAX_DATE_STRING', '2035-01-01 00:00:00' );
define( 'SCOPER_MAX_DATE_VALUE', strtotime( constant('SCOPER_MAX_DATE_STRING') ) );

if ( defined('RS_DEBUG') ) {
	include_once( dirname(__FILE__).'/lib/debug.php');
	add_action( 'admin_footer', 'awp_echo_usage_message' );
} else
	include_once( dirname(__FILE__).'/lib/debug_shell.php');

//log_mem_usage_rs( 'plugin load' );

//if ( version_compare( phpversion(), '5.2', '<' ) )	// some servers (Ubuntu) return irregular version string format
if ( ! function_exists("array_fill_keys") )
	require_once( dirname(__FILE__).'/lib/php4support_rs.php');

require_once( dirname(__FILE__).'/lib/agapetry_lib.php');

// === awp_is_mu() function definition and usage: must be executed in this order, and before any checks of IS_MU_RS constant (such as in role-scoper_init.php) ===
require_once( dirname(__FILE__).'/lib/agapetry_wp_lib.php');
define( 'IS_MU_RS', awp_is_mu() );
// ----------------------------------------

require_once( dirname(__FILE__).'/role-scoper_init.php');	// Contains activate, deactivate, init functions. Adds mod_rewrite_rules.

// register these functions before any early exits so normal activation/deactivation can still run with RS_DEBUG
register_activation_hook(__FILE__, 'scoper_activate');
register_deactivation_hook(__FILE__, 'scoper_deactivate');

function scoper_activate() {
	require_once( dirname(__FILE__).'/role-scoper_activation.php');
	_scoper_activate();
}

function scoper_deactivate() {
	require_once( dirname(__FILE__).'/role-scoper_activation.php');
	_scoper_deactivate();
}

// define URL
define ('SCOPER_BASENAME', plugin_basename(__FILE__) );
define ('SCOPER_FOLDER', dirname( plugin_basename(__FILE__) ) );

if ( ! defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', site_url( 'wp-content' ) );

if ( ! defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', str_replace('\\', '/', ABSPATH) . 'wp-content' );

if ( defined('WP_PLUGIN_DIR') )
	define ('SCOPER_ABSPATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . basename(SCOPER_FOLDER) );
else
	define ('SCOPER_ABSPATH', WP_CONTENT_DIR . '/plugins/' . SCOPER_FOLDER);

define ('ANON_ROLEHANDLE_RS', 'wp_public_reader');

define ('BLOG_SCOPE_RS', 'blog');
define ('TERM_SCOPE_RS', 'term');
define ('OBJECT_SCOPE_RS', 'object');

define ('OP_READ_RS', 'read');
define ('OP_ASSOCIATE_RS', 'associate');
define ('OP_EDIT_RS', 'edit');
define ('OP_PUBLISH_RS', 'publish');
define ('OP_DELETE_RS', 'delete');
define ('OP_ADMIN_RS', 'admin');
define( 'OP_ASSIGN_RS', 'assign');

define ('ROLE_BASIS_GROUPS', 'groups');
define ('ROLE_BASIS_USER', 'user');
define ('ROLE_BASIS_USER_AND_GROUPS', 'ug');

define ('ANY_CONTENT_DATE_RS', '');
define ('NO_OBJSCOPE_CLAUSE_RS', '');

global $scoper_role_types;
$scoper_role_types = array('rs', 'wp', 'wp_cap');

global $wpdb;

$bail = 0;

if ( ! awp_ver('3.0') ) {
	rs_notice('Sorry, this version of Role Scoper requires WordPress 3.0 or higher.  Please upgrade Wordpress or deactivate Role Scoper.  If you must run WP 2.7 - 2.9.2, try <a href="http://agapetry.net/downloads/role-scoper_legacy">Role Scoper 1.2.x</a>.');
	$bail = 1;	
}

if ( is_admin() || defined('XMLRPC_REQUEST') ) {
	// Early bailout for problematic 3rd party plugin ajax calls
	if ( strpos($_SERVER['SCRIPT_NAME'], 'wp-wall-ajax.php') )
		return;

} elseif ( ! $bail ) {
	require_once( dirname(__FILE__).'/feed-interceptor_rs.php'); // must define get_currentuserinfo early
}

//log_mem_usage_rs( 'initial requires' );

if ( ! $bail ) {
	require_once( dirname(__FILE__).'/defaults_rs.php');
	
	//log_mem_usage_rs( 'defaults_rs' );
	
	if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) )
		scoper_refresh_options_sitewide();
	
	//log_mem_usage_rs( 'refresh_options_sitewide' );
	
	//scoper_refresh_default_options();
	
	// if role options were just updated via http POST, use new values rather than loading old option values from DB
	// These option values are used in WP_Scoped_User constructor
	if ( is_admin() && isset( $_POST['enable_group_roles'] ) && ( 0 === strpos( $GLOBALS['plugin_page_cr'], 'rs-' ) ) )
		scoper_use_posted_init_options();
	else
		scoper_get_init_options();
	
	if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) ) {
		// If groups are sitewide, default groups must also be defined/applied sitewide (and vice versa)
		global $scoper_sitewide_groups, $scoper_options_sitewide;
		if ( $scoper_sitewide_groups = scoper_get_site_option( 'mu_sitewide_groups' ) )
			$scoper_options_sitewide['default_groups'] = true;
		
		elseif( isset( $scoper_options_sitewide['default_groups'] ) )
			unset( $scoper_options_sitewide['default_groups'] );
	}

	// rs_blog_roles option has never been active in any RS release; leave commented here in case need arises
	//define ( 'RS_BLOG_ROLES', scoper_get_option('rs_blog_roles') );

	//log_mem_usage_rs( 'user-plug_rs' );

	add_action( 'set_current_user', 'scoper_act_set_current_user', 99 );
	
	if ( ! empty( $GLOBALS['current_user'] ) ) {  // some plugins force setting of current user before RS loads
		if ( ! is_admin() || ! isset($GLOBALS['pagenow']) || ( 'plugins.php' != $GLOBALS['pagenow'] ) || ( empty($_REQUEST['activate']) && empty($_REQUEST['action']) && empty($_REQUEST['action2']) ) ) {
			scoper_act_set_current_user();
		}
	}

	if ( ! defined( 'SCOPER_EARLY_INIT' ) ) {
		$early_init_uri = ( defined( 'NGGVERSION' ) ) ? array('/wp-admin/?nggupload') : array();
		foreach ( apply_filters( 'scoper_force_early_init_uris', $early_init_uri ) as $uri ) {
			if ( $uri && strpos( $_SERVER['REQUEST_URI'], $uri ) ) {
				define( 'SCOPER_EARLY_INIT', true );
				break;
			}
		}
	}
	
	// since sequence of set_current_user and init actions seems unreliable, make sure our current_user is loaded first
	$priority = ( defined( 'SCOPER_EARLY_INIT' ) ) ? 1 : 50;
	add_action('init', 'scoper_log_init_action', $priority);
}
?>