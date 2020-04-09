<?php

add_action( 'scoper_init', 'scoper_review_file_htaccess' );

// indicates, for MU installations, which of the RS options (and OType options) should be controlled network-wide
function scoper_default_options_sitewide() {
	$def = array(
		'persistent_cache' => true,
		'define_usergroups' => true,
		'group_ajax' => true,
		'group_requests' => true,
		'group_recommendations' => true,
		'enable_group_roles' => true,
		'enable_user_roles' => true,
		'custom_user_blogcaps' => true,
		'no_frontend_admin' => true,
		'indicate_blended_roles' => true,
		
		'rs_page_reader_role_objscope' => true,
		'rs_page_author_role_objscope' => true,
		'rs_post_reader_role_objscope' => true,
		'rs_post_author_role_objscope' => true,
		
		'display_user_profile_groups' => true,
		'display_user_profile_roles' => true,
		'user_role_assignment_csv' => true,
		'remap_page_parents' => false,
		'enforce_actual_page_depth' => true,
		'remap_thru_excluded_page_parent' => true,
		'remap_term_parents' => false,
		'enforce_actual_term_depth' => true,
		'remap_thru_excluded_term_parent' => true,
		'mu_sitewide_groups' => true,
		'file_filtering' => true,
		'file_filtering_regen_key' => true,
		'role_duration_limits' => true,
		'role_content_date_limits' => true,

		'disabled_access_types' => true,
		'use_taxonomies' => true,
		'use_post_types' => true,
		'use_term_roles' => true,
		'use_object_roles' => true,
		'disabled_role_caps' => true,
		'user_role_caps' => true,
		'filter_users_dropdown' => true,
		'restrictions_column' => true,
		'term_roles_column' => true,
		'object_roles_column' => true,
		
		'admin_nav_menu_filter_items' => true,
		'require_moderate_comments_cap' => true,
	);
	return $def;	
}


function scoper_refresh_options_sitewide() {
	if ( ! IS_MU_RS )
		return;
		
	global $scoper_options_sitewide;
	$scoper_options_sitewide = apply_filters( 'options_sitewide_rs', array_intersect( scoper_default_options_sitewide(), array( true ) ) );	// establishes which options are set site-wide
	
	if ( $options_sitewide_reviewed =  scoper_get_site_option( 'options_sitewide_reviewed' ) ) {
		$custom_options_sitewide = (array) scoper_get_site_option( 'options_sitewide' );

		$unreviewed_default_sitewide = array_diff( array_keys($scoper_options_sitewide), $options_sitewide_reviewed );

		$scoper_options_sitewide = array_fill_keys( array_merge( $custom_options_sitewide, $unreviewed_default_sitewide ), true );
	}
	
	if ( empty( $scoper_options_sitewide['file_filtering'] ) )
		$scoper_options_sitewide['file_filtering'] = true;	// file filtering option must be set site-wide (this DOES NOT set the option value itself)
	
	if ( empty( $scoper_options_sitewide['mu_sitewide_groups'] ) )
		$scoper_options_sitewide['mu_sitewide_groups'] = true;	// sitewide_groups option must be set site-wide!
}

function scoper_apply_custom_default_options( $options_var ) {
	global $wpdb, $scoper_options_sitewide;
	
	if ( $results = scoper_get_results( "SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE site_id = '$wpdb->siteid' AND meta_key LIKE 'scoper_default_%'" ) ) {
		foreach ( $results as $row ) {
			$option_basename = str_replace( 'scoper_default_', '', $row->meta_key );

			if ( ! empty( $scoper_options_sitewide[$option_basename] ) )
				continue;	// custom defaults are only for blog-specific options

			if( isset( $GLOBALS[$options_var][$option_basename] ) )
				$GLOBALS[$options_var][$option_basename] = maybe_unserialize( $row->meta_value );
		}
	}
}

function scoper_establish_group_scope() {
	// TODO : possibly change this back to scoper_get_option call
	$sitewide_groups = get_site_option( 'scoper_mu_sitewide_groups' );

	$last_sitewide_groups = get_option( 'scoper_mu_last_sitewide_groups' );	// sitewide groups is a sitewide option, but schema update must be run for each blog if that sitewide option changes
	
	global $scoper_site_options, $scoper_blog_options;
	
	if ( false === $sitewide_groups ) {
		$no_setting = true;
		
		$last_version = ( isset($scoper_blog_options['scoper_version']) ) ? unserialize($scoper_blog_options['scoper_version']) : array();

		if ( ! empty($last_version['version']) ) {
			// MU installations that ran previous RS version might have existing blog-specific groups; must default to not using site-wide	
			add_site_option( 'scoper_mu_sitewide_groups', 0 );
			$sitewide_groups = 0;
		} else {
			// if this is the first RS run (or the installation has been fully wiped), explicitly default to sitewide groups by storing option
			add_site_option( 'scoper_mu_sitewide_groups', 1 );
			$sitewide_groups = 1;
		}
	} else
		$no_setting = false;
	
	$scoper_site_options['mu_sitewide_groups'] = $sitewide_groups;
		
	if ( ( $sitewide_groups != $last_sitewide_groups ) || $no_setting ) {
		update_option( 'scoper_mu_last_sitewide_groups', intval($sitewide_groups) );

		delete_option( 'scoper_version' );	// force db schema update on sitewide groups change
	}
		
	return $sitewide_groups;
}


function scoper_review_file_htaccess() {
	$min_date = (int) scoper_get_site_option( 'file_htaccess_min_date' );
	$last_regen = (int) scoper_get_option( 'file_htaccess_date' );

	if ( ! $last_regen || ( $min_date > $last_regen ) )
		scoper_flush_file_rules();
}
?>