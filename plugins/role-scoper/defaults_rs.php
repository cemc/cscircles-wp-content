<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * functions for the WordPress plugin Role Scoper
 * defaults_rs.php
 * 
 * @description 
 * These functions define create an object collection of data sources, object types, taxonomies, capabilites 
 * and roles which dictate what access Role Scoper filters. The object properties are a combination of
 * database schema information and RS functionality switches.
 *
 * These definitions may be modified by filters which are applied in Scoper::Scoper() and Scoper::load_config().
 * For an example of 3rd party usage, see the plugin rs-config-ngg (Role Scoping for NextGenGallery)
 *
 * Note: for performance, default config mirrors Class definitions using stdObject cast from array
 *
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */
 
$GLOBALS['rs_default_disable_taxonomies'] = (array) apply_filters( 'default_disable_taxonomies_rs', array_fill_keys( array( 'link_category', 'post_tag', 'post_format', 'ngg_tag' ), true ) );
$GLOBALS['rs_forbidden_taxonomies'] = (array) apply_filters( 'forbidden_taxonomies_rs', array_fill_keys( array( 'post_status', 'following_users', 'unfollowing_users', 'following_usergroups', 'ef_editorial_meta' ), true ) );   // avoid extreme config confusion with these Edit Flow taxonomies

function scoper_default_options() {
	$def = array(
		'persistent_cache' => 1,
		'define_usergroups' => 1,
		'group_ajax' => 1,
		'group_requests' => 0,
		'group_recommendations' => 0,
		'enable_group_roles' => 1,
		'enable_user_roles' => 1,
		/*'rs_blog_roles' => 1, */
		'custom_user_blogcaps' => 0,
		'user_role_caps' => array(),	/* NOTE: "user" here does not refer to WP user account(s), but to the user of the plugin.  The option value adds capabilities to RS Role Definitions, and would have been better named "custom_role_caps"  */
		'disabled_role_caps' => array(),
		'disabled_access_types' => array(),
		'no_frontend_admin' => 0,
		'indicate_blended_roles' => 1,
		'version_update_notice' => 1,
		'version_check_minutes' => 30,
		'strip_private_caption' => 0,
		'display_hints' => 1,
		'hide_non_editor_admin_divs' => 1,
		'role_admin_blogwide_editor_only' => 1,
		'feed_link_http_auth' => 0,
		'rss_private_feed_mode' => 'title_only',
		'rss_nonprivate_feed_mode' => 'full_content',
		'feed_teaser' => scoper_po_trigger( "View the content of this <a href='%permalink%'>article</a>" ),
		'rs_page_reader_role_objscope' => 0,
		'rs_page_author_role_objscope' => 0,
		'rs_post_reader_role_objscope' => 0,
		'rs_post_author_role_objscope' => 0,
		'lock_top_pages' => 0,
		'display_user_profile_groups' => 0,
		'display_user_profile_roles' => 0,
		'user_role_assignment_csv' => 0,
		'admin_others_attached_files' => 0,
		'admin_others_unattached_files' => 0,
		'remap_page_parents' => 0,
		'enforce_actual_page_depth' => 1,
		'remap_thru_excluded_page_parent' => 0,
		'remap_term_parents' => 0,
		'enforce_actual_term_depth' => 1,
		'remap_thru_excluded_term_parent' => 0,
		'limit_user_edit_by_level' => 1,
		'file_filtering' => 0,
		'mu_sitewide_groups' => 1,  // version check code will set this to 0 for first-time execution of this version on mu installations that ran a previous RS version
		'role_duration_limits' => 1,
		'role_content_date_limits' => 1,
		'filter_users_dropdown' => 1,
		'auto_private' => 1,
		'admin_nav_menu_filter_items' => 0,
		'require_moderate_comments_cap' => 0,
		'define_create_posts_cap' => 0,
		'dismissals' => array(),
	);
	
	// NOTE: scoper_get_option() applies these defaults
	if ( in_array( $GLOBALS['plugin_page_cr'], array( 'rs-options', 'rs-site_options' ) ) ) {
		$post_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
		foreach ( $post_types as $type )
			$def['use_post_types'][$type] = 1;

		/* // can't do this yet because lots of other get_post_types() calls assume public-only
		if ( ! defined( 'SCOPER_NO_PRIVATE_TYPES' ) ) {
			foreach ( array_diff( get_post_types( array( 'public' => false ) ), array( 'revision', 'nav_menu_item' ) ) as $type )
				$def['use_post_types'][$type] = 0;
		}
		*/
		
		foreach ( array_diff( get_taxonomies( array( 'public' => true ) ), array_keys($GLOBALS['rs_forbidden_taxonomies']) ) as $taxonomy ) {
			if ( isset( $GLOBALS['rs_default_disable_taxonomies'][$taxonomy] ) )
				$def['use_taxonomies'][$taxonomy] = 0;
			else
				$def['use_taxonomies'][$taxonomy] = 1;
		}

		/* // can't do this yet because lots of other get_taxonomies() calls assume public-only
		if ( ! defined( 'SCOPER_NO_PRIVATE_TAXONOMIES' ) ) {
			foreach ( array_diff( get_taxonomies( array( 'public' => false ) ), array_keys($GLOBALS['rs_forbidden_taxonomies']) ) as $taxonomy ) {
				$def['use_taxonomies'][$taxonomy] = 0;
			}
		}
		*/
	}
	
	return $def;
}

function scoper_po_trigger( $string ) {
	return $string;	
}

function scoper_default_otype_options( $include_custom_types = true ) {
	$def = array();
	
	//------------------------ DEFAULT OBJECT TYPE OPTIONS ---------------------		
	// 	format for second key is {src_name}:{object_type}

	if ( $include_custom_types )
		$post_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	else
		$post_types = get_post_types( array( '_builtin' => true, 'public' => true ) );

	foreach ( $post_types as $type ) {
		$def['limit_object_editors']["post:{$type}"] = 0;
		$def['default_private']["post:{$type}"] = 0;
		$def['sync_private']["post:{$type}"] = 0;
		$def['restrictions_column']["post:{$type}"] = 1;
		$def['term_roles_column']["post:{$type}"] = 1;
		$def['object_roles_column']["post:{$type}"] = 1;
		
		$def['use_teaser'] ["post:{$type}"] = 1;  // use teaser (if enabled) for WP posts.  Note: Use integer because this option is multi-select.  Other valid setting is "excerpt"
		
		// TODO: set additional teaser defaults (and all defaults) only as needed?
		
		$def['teaser_hide_private']["post:{$type}"] = 0;
		$def['teaser_logged_only'] ["post:{$type}"] = 0;
		
		$def['teaser_replace_content']		["post:{$type}"] = scoper_po_trigger( "Sorry, this content requires additional permissions.  Please contact an administrator for help." );
		$def['teaser_replace_content_anon']	["post:{$type}"] = scoper_po_trigger( "Sorry, you don't have access to this content.  Please log in or contact a site administrator for help." );
		$def['teaser_prepend_content']		["post:{$type}"] = '';
		$def['teaser_prepend_content_anon']	["post:{$type}"] = '';
		$def['teaser_append_content']		["post:{$type}"] = '';
		$def['teaser_append_content_anon']	["post:{$type}"] = '';
		$def['teaser_prepend_name']			["post:{$type}"] = '(';
		$def['teaser_prepend_name_anon']	["post:{$type}"] = '(';
		$def['teaser_append_name']			["post:{$type}"] = ')*';
		$def['teaser_append_name_anon']		["post:{$type}"] = ')*';
		$def['teaser_replace_excerpt']		["post:{$type}"] = '';
		$def['teaser_replace_excerpt_anon']	["post:{$type}"] = '';
		$def['teaser_prepend_excerpt']		["post:{$type}"] = '';
		$def['teaser_prepend_excerpt_anon']	["post:{$type}"] = '';
		$def['teaser_append_excerpt']		["post:{$type}"] = "<br /><small>" . scoper_po_trigger( "note: This content requires a higher login level." ) . "</small>";
		$def['teaser_append_excerpt_anon']	["post:{$type}"] = "<br /><small>" . scoper_po_trigger( "note: This content requires site login." ) . "</small>";
		
		$def['use_object_roles']["post:{$type}"] = 1;
	} // end foreach post type
	
	$taxonomies = array_diff_key( get_taxonomies( array( 'public' => true ), 'object' ), $GLOBALS['rs_forbidden_taxonomies'] );
	$taxonomies ['nav_menu']= get_taxonomy( 'nav_menu' );

	$post_types []= 'nav_menu_item';
	
	foreach ( $taxonomies as $taxonomy => $taxonomy_obj ) {
		$_object_types = (array) $taxonomy_obj->object_type;
		
		foreach( $_object_types as $object_type ) {
			if ( in_array( $object_type, $post_types ) )
				$def['use_term_roles']["post:{$object_type}"][$taxonomy] = 1;
		}
	}
	
	$def['do_teaser'] ['post'] = false;  					// don't enable teaser by default (separate per-type settings default to true)
	
	if( isset( $def['use_term_roles']['post:page']['category'] ) )
		$def['use_term_roles']['post:page']['category'] = 0;  // Wordpress core does not categorize pages by default
	
	$def['use_term_roles']['link:link']['link_category'] = 1; // Use Link Category roles by default

	$def['private_items_listable']['post:page'] = 1;
	
	$def['admin_css_ids'] ['post:post'] = 'password-span; slugdiv; edit-slug-box; authordiv; commentstatusdiv; trackbacksdiv; postcustom; revisionsdiv';	// this applied for all object types other than post
	$def['admin_css_ids'] ['post:page'] = 'password-span; pageslugdiv; edit-slug-box; pageauthordiv; pageparentdiv; pagecommentstatusdiv; pagecustomdiv; revisionsdiv';
	
	return $def;
}
?>