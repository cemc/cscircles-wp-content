<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
class ScoperOptionUI {
	var $sitewide;
	var $customize_defaults;
	var $form_options;
	var $tab_captions;
	var $section_captions;
	var $option_captions;
	var $all_options;
	var $all_otype_options;
	var $def_otype_options;
	var $display_hints;
	
	function ScoperOptionUI( $sitewide, $customize_defaults ) {
		$this->sitewide = $sitewide;
		$this->customize_defaults = $customize_defaults;
	}
	
	function option_checkbox( $option_name, $tab_name, $section_name, $hint_text, $trailing_html, $args = array()) {
		$return = array( 'in_scope' => false, 'val' => '' );
		
		if ( in_array( $option_name, $this->form_options[$tab_name][$section_name] ) ) {
			$this->all_options []= $option_name;
			
			$return['val'] = scoper_get_option($option_name, $this->sitewide, $this->customize_defaults);
				
			$js_clause = ( ! empty($args['js_call']) ) ? 'onclick="' . $args['js_call'] . '"'  : '';
			$style = ( ! empty($args['style']) ) ? $args['style'] : '';
			
			echo "<div class='agp-vspaced_input'>"
				. "<label for='$option_name'><input name='$option_name' type='checkbox' $js_clause $style id='$option_name' value='1' ";
				
				 checked('1', $return['val']);
				 
			echo " /> "
				. $this->option_captions[$option_name]
				. "</label>";
				
			if ( $hint_text && $this->display_hints )
				echo "<br /><span class='rs-subtext'>" . $hint_text . "</span>";
				
			echo "</div>";

			if ( $trailing_html )
				echo $trailing_html;
			
			$return['in_scope'] = true;	
		}
		
		return $return;
	}
	

	function otype_option_checkboxes( $option_name, $caption, $tab_name, $section_name, $hint_text, $trailing_html, $args = array() ) {
		global $scoper, $scoper_admin;
		
		$defaults = array( 'caption_header' => true );
		$args = array_merge( $defaults, $args );
		extract( $args );
		
		$return = array( 'in_scope' => false, 'val' => array() );
		
		if ( in_array( $option_name, $this->form_options[$tab_name][$section_name] ) ) {
			$this->all_otype_options []= $option_name;
			
			if ( isset($this->def_otype_options[$option_name]) ) {
				if ( ! $return['val'] = scoper_get_option( $option_name, $this->sitewide, $this->customize_defaults ) )
					$return['val'] = array();
					
				$return['val'] = array_merge($this->def_otype_options[$option_name], $return['val']);
				
				$label_property = ( isset($args['label_property']) ) ? $args['label_property'] : 'name';
				
				$first_pass = true;
				foreach ( $return['val'] as $src_otype => $val ) {
					if ( $caption_header && $first_pass ) {
						printf( $caption, $scoper_admin->interpret_src_otype($src_otype, $label_property) );
						echo '<br /><div style="margin-left: 2em">';
						$first_pass = false;
					}
					
					$arr_src_otype = explode( ':', $src_otype );
					if ( ! scoper_get_otype_option( 'use_object_roles', $arr_src_otype[0], $arr_src_otype[1] ) )
						continue;
					
					$item_label = $scoper_admin->interpret_src_otype($src_otype, $label_property); //arg: use plural display name
						
					$id = str_replace(':', '_', $option_name . '-' . $src_otype);
					
					echo "<label for='$id'>";
					echo "<input name='$id' type='checkbox' id='$id' value='1' ";
					checked('1', $val);
					echo " /> ";
					
					if ( $caption_header )
						echo $item_label;
					else
						printf( $caption, $item_label );
					
					echo ('</label><br />');
				} // end foreach src_otype
				
				if ( $caption_header )
					echo '</div>';
				
				if ( $hint_text && $this->display_hints )
					echo "<span class='rs-subtext'>" . $hint_text . "</span>";
			
				if ( $trailing_html )
					echo $trailing_html;
					
			} // endif default option isset
			
			$return['in_scope'] = true;
				
		} // endif in this option is controlled in this scope
		
		return $return;
	}
}

	
function scoper_options( $sitewide = false, $customize_defaults = false ) {
	
if ( ! is_option_administrator_rs() || ( $sitewide && function_exists('is_super_admin') && ! is_super_admin() ) )
	wp_die(__awp('Cheatin&#8217; uh?'));

if ( $sitewide )
	$customize_defaults = false;	// this is intended only for storing custom default values for blog-specific options

$ui = new ScoperOptionUI( $sitewide, $customize_defaults );
		
if ( isset($_POST['all_otype_options']) ) {
	wpp_cache_flush( $sitewide );

	//global $wp_rewrite;
	//if ( ! empty($wp_rewrite) )
	//	$wp_rewrite->flush_rules();	

	if ( isset($_POST['rs_role_resync']) )
		ScoperAdminLib::sync_wproles();
			
	if ( isset($_POST['rs_defaults']) )
		$msg = __('Role Scoper options were reset to defaults.', 'scoper');
		
	elseif ( isset($_POST['rs_flush_cache']) )
		$msg = __('The persistent cache was flushed.', 'scoper');
	else
		$msg = __('Role Scoper options were updated.', 'scoper');

	// submittee_rs.php fielded this submission, but output the message here.
	echo '<div id="message" class="updated fade"><p>';
	echo $msg;
	echo '</p></div>';
}

global $scoper, $scoper_admin;

define('SCOPER_REALM_ADMIN_RS', 1);  // We need to access all config items here, even if they are normally removed due to disabling
$scoper->load_config();

// scoper_default_otype_options is hookable for other plugins to add pertinent items for their data sources
scoper_refresh_default_options();
scoper_refresh_default_otype_options();

global $scoper_default_otype_options;
$ui->def_otype_options = $scoper_default_otype_options;

$ui->all_options = array();
$ui->all_otype_options = array();


$ui->tab_captions = array( 'features' => __( 'Features', 'scoper' ), 'advanced' => __( 'Advanced', 'scoper' ), 'realm' => __( 'Realm', 'scoper' ), 
						'rs_role_definitions' => __( 'RS Role Definitions', 'scoper' ), 'wp_role_definitions' => __( 'WP Role Definitions', 'scoper'), 'optscope' => __( 'Option Scope', 'scoper' ) );

$ui->section_captions = array(
	'features' => array(
		'user_groups'	=> __('User Groups', 'scoper'),
		'front_end' 	=> __('Front End', 'scoper'),	
		'pages_listing' => __('Pages Listing', 'scoper'),
		'categories_listing' =>	__('Categories Listing', 'scoper'),
		'content_maintenance'=> __('Content Maintenance', 'scoper'),
		'role_assignment' =>	__('Role Assignment', 'scoper'),
		'nav_menu_management' =>	__('Nav Menu Management', 'scoper' ),
		'media_library' => __('Media Library', 'scoper'),
		'file_filtering' =>	__('File Filtering', 'scoper'),
		'date_limits' =>	__('Role Date Limits', 'scoper'),
		'internal_cache' =>	__('Internal Cache', 'scoper'),	
		'version' =>		__('Version', 'scoper'),
		'rss_feeds' =>		__('RSS Feeds', 'scoper'),
		'hidden_content_teaser' => __('Hidden Content Teaser', 'scoper')
),
'advanced' => array(
	'role_basis'	=>		__('Role Basis', 'scoper'),
	'page_structure' =>		__('Page Structure', 'scoper'),
	'user_profile' =>		__('User Profile', 'scoper'),
	'user_management' => 	__('User Management', 'scoper'),
	'administrator_definition' => __('Administrator Definition', 'scoper'),
	'limited_editing_elements' => __('Limited Editing Elements', 'scoper'),
	'role_assignment_interface' => __('Role Assignment Interface', 'scoper'),
	'custom_columns' =>		__('Custom Columns', 'scoper'),
	'additional_object_roles' => __('Additional Object Roles', 'scoper')
), 
'realm' => array(
	'term_object_scopes' =>	__('Term / Object Scope', 'scoper'),
	'taxonomy_usage' =>		__('Taxonomy Usage', 'scoper'),
	'post_type_usage' =>	__('Post Type Usage', 'scoper'),
	'term_scope' =>			__('Term Scope', 'scoper'),
	'object_scope' =>		__('Object Scope', 'scoper'),
	'access_types' =>		__('Access Types', 'scoper')
), 
'rs_role_definitions' => array(	
	'' =>					''
), 
'wp_role_definitions' => array(
	'' =>					''
) 
);


// TODO: replace individual _e calls with these (and section, tab captions)
$ui->option_captions = array(
	'persistent_cache' => __('Cache roles and groups to disk', 'scoper'),
	'define_usergroups' => __('Enabled', 'scoper'),
	'group_ajax' => __('Use jQuery selection UI', 'scoper' ),
	'group_requests' => __('Enable membership requests', 'scoper' ),
	'group_recommendations' => __('Enable membership recommendations', 'scoper' ),
	'enable_group_roles' => __('Apply Group Roles', 'scoper'),
	'enable_user_roles' => __('Apply User Roles', 'scoper'),
	'custom_user_blogcaps' => __('Support WP Custom User Caps', 'scoper'),
	'no_frontend_admin' => __('Assume No Front-end Admin', 'scoper'),
	'indicate_blended_roles' => __('Indicate blended roles', 'scoper'),
	'version_update_notice' => __('Notify on Version Updates', 'scoper'),
	/* 'version_check_minutes' => __('', 'scoper'), */
	'strip_private_caption' => __('Suppress "Private:" Caption', 'scoper'),
	'display_hints' => __('Display Administrative Hints', 'scoper'),
	'hide_non_editor_admin_divs' => __('Specified element IDs also require the following site-wide Role:', 'scoper'),
	'role_admin_blogwide_editor_only' => __('Roles and Restrictions can be set:', 'scoper'),
	'feed_link_http_auth' => __( 'HTTP Authentication Request in RSS Feed Links', 'scoper' ),
	'rss_private_feed_mode' => __( 'Display mode for readable private posts', 'scoper' ),
	'rss_nonprivate_feed_mode' => __( 'Display mode for readable non-private posts', 'scoper' ),
	'feed_teaser' => __( 'Feed Replacement Text (use %permalink% for post URL)', 'scoper' ),
	'rs_page_reader_role_objscope' => $scoper->role_defs->get_display_name('rs_page_reader'),
	'rs_page_author_role_objscope' => $scoper->role_defs->get_display_name('rs_page_author'),
	'rs_post_reader_role_objscope' => $scoper->role_defs->get_display_name('rs_post_reader'),
	'rs_post_author_role_objscope' => $scoper->role_defs->get_display_name('rs_post_author'),
	'lock_top_pages' => __('Pages can be set or removed from Top Level by:', 'scoper'),
	'display_user_profile_groups' => __('Display User Groups', 'scoper'),
	'display_user_profile_roles' => __('Display User Roles', 'scoper'),
	'user_role_assignment_csv' => __('Users CSV Entry', 'scoper'),
	'admin_others_attached_files' => __('Non-editors see other users\' attached uploads', 'scoper'),
	'admin_others_unattached_files' => __('Non-editors see other users\' unattached uploads', 'scoper'),
	'remap_page_parents' => __('Remap pages to visible ancestor', 'scoper'),
	'enforce_actual_page_depth' => __('Enforce actual page depth', 'scoper'),
	'remap_thru_excluded_page_parent' => __('Remap through excluded page parent', 'scoper'),
	'remap_term_parents' => __('Remap terms to visible ancestor', 'scoper'),
	'enforce_actual_term_depth' => __('Enforce actual term depth', 'scoper'),
	'remap_thru_excluded_term_parent' => __('Remap through excluded term parent', 'scoper'),
	'limit_user_edit_by_level' => __('Limit User Edit by Level', 'scoper'),
	'file_filtering' => __('Filter Uploaded File Attachments', 'scoper'),
	'file_filtering_regen_key' => __('File Filtering Reset Key', 'scoper'),
	'mu_sitewide_groups' => __('Share groups network-wide', 'scoper'),
	'role_duration_limits' => __('Enable Role Duration Limits', 'scoper'),
	'role_content_date_limits' => __('Enable Content Date Limits', 'scoper'),
	'filter_users_dropdown' => __('Filter Users Dropdown', 'scoper'),
	'restrictions_column' => __('Restrictions Column', 'scoper'),
	'term_roles_column' => __('Term Roles Column', 'scoper' ),
	'object_roles_column' => __('Object Roles Column', 'scoper'),
	'admin_nav_menu_filter_items' => __( 'List only user-editable content as available items', 'scoper' ),

	'do_teaser' => __('Enable', 'scoper'),	/* NOTE: submitee.php must sync all other teaser-related options to do_teaser scope setting */
	'admin_css_ids' => __('Limited Editing Elements', 'scoper'),
	'limit_object_editors' => __('Limit eligible users for object-specific editing roles', 'scoper'),
	'private_items_listable' => __('Include Private Pages in listing if user can read them', 'scoper'),
	'use_term_roles' => __('settings', 'scoper'),
	'disabled_access_types' => __('settings', 'scoper'),
	/* disabled_role_caps' => __('settings', 'scoper'), */
	'user_role_caps' => __('settings', 'scoper'),	/* NOTE: submitee.php must sync disabled_role_caps and user_role_caps scope setting */
	'define_create_posts_cap' => __( 'Require create_posts capability', 'scoper' ),
	'require_moderate_comments_cap' => __( 'Require moderate_comments capability', 'scoper' ),
);


$ui->form_options = array( 
'features' => array(
	'user_groups'	=> 		array( 'define_usergroups', 'group_ajax', 'group_requests', 'group_recommendations', 'mu_sitewide_groups' ),
	'front_end' 	=> 		array( 'strip_private_caption', 'no_frontend_admin' ),
	'pages_listing' => 		array( 'private_items_listable', 'remap_page_parents', 'enforce_actual_page_depth', 'remap_thru_excluded_page_parent' ),
	'categories_listing' =>	array( 'remap_term_parents', 'enforce_actual_term_depth', 'remap_thru_excluded_term_parent' ),
	'content_maintenance'=> array( 'default_private', 'sync_private', 'filter_users_dropdown', 'define_create_posts_cap', 'require_moderate_comments_cap' ),
	'nav_menu_management' => array( 'admin_nav_menu_filter_items' ),
	'role_assignment' =>	array( 'role_admin_blogwide_editor_only' ),
	'media_library' =>		array( 'admin_others_attached_files', 'admin_others_unattached_files' ),
	'file_filtering' =>		array( 'file_filtering', 'file_filtering_regen_key' ),
	'date_limits' =>		array( 'role_duration_limits', 'role_content_date_limits' ),
	'internal_cache' =>		array( 'persistent_cache' ),
	'version' =>			array( 'version_update_notice' ),
	'rss_feeds' =>			array( 'feed_link_http_auth', 'rss_private_feed_mode', 'rss_nonprivate_feed_mode', 'feed_teaser' ),
	'hidden_content_teaser' => array( 'do_teaser' ) /* NOTE: all teaser options follow scope setting of do_teaser */
	
							/*, 'teaser_hide_private', 'use_teaser', 'teaser_logged_only', 
								'teaser_replace_content', 'teaser_replace_content_anon', 'teaser_prepend_content', 'teaser_prepend_content_anon',
								'teaser_append_content', 'teaser_append_content_anon', 'teaser_prepend_name', 'teaser_prepend_name_anon',
								'teaser_append_name', 'teaser_append_name_anon', 'teaser_replace_excerpt', 'teaser_replace_excerpt_anon',
								'teaser_prepend_excerpt', 'teaser_prepend_excerpt_anon', 'teaser_append_excerpt', 'teaser_append_excerpt_anon' ), */
),
'advanced' => array(
	'role_basis'	=>		array( 'enable_group_roles', 'enable_user_roles', 'custom_user_blogcaps' ),
	'page_structure' =>		array( 'lock_top_pages' ),
	'user_profile' =>		array( 'display_user_profile_groups', 'display_user_profile_roles' ),
	'user_management' => 	array( 'limit_user_edit_by_level' ),
	/* 'administrator_definition' => array( '' ), */  /* NOTE: administrator definition is always displayed with sitewide options (or non-mu) */
	
	'limited_editing_elements' => array( 'admin_css_ids', 'hide_non_editor_admin_divs' ),
	'role_assignment_interface' => array( 'limit_object_editors', 'indicate_blended_roles', 'display_hints', 'user_role_assignment_csv' ),
	'custom_columns' =>	array( 'restrictions_column', 'term_roles_column', 'object_roles_column' ),
	'additional_object_roles' => array( 'rs_page_reader_role_objscope', 'rs_post_reader_role_objscope', 'rs_page_author_role_objscope', 'rs_post_author_role_objscope' )
), 
'realm' => array(
	'term_object_scopes' =>	array( 'use_term_roles' ), /* 'use_term_roles', 'use_object_roles'  NOTE: all related options follow scope setting of use_term_roles */
	'access_types' =>		array( 'disabled_access_types' )
), 
'rs_role_definitions' => array(
	'' =>					array( 'user_role_caps' ) /* 'disabled_role_caps' ), NOTE: user_role_caps scope follows disabled_role_caps */
)
/*
'wp_role_definitions' => array(
	'' =>					array( '' )
) */
);
	

if ( IS_MU_RS && agp_is_plugin_network_active( SCOPER_BASENAME ) ) {
	if ( $sitewide )
		$available_form_options = $ui->form_options;
		
	global $scoper_options_sitewide;
	global $rvy_options_sitewide;
	
	foreach ( $ui->form_options as $tab_name => $sections )
		foreach ( $sections as $section_name => $option_names )
			if ( $sitewide )
				$ui->form_options[$tab_name][$section_name] = array_intersect( $ui->form_options[$tab_name][$section_name], array_keys($scoper_options_sitewide) );
			else
				$ui->form_options[$tab_name][$section_name] = array_diff( $ui->form_options[$tab_name][$section_name], array_keys($scoper_options_sitewide) );
	
	// WP Role Defs display follows RS Role Defs
	if ( ! empty( $ui->form_options['rs_role_defs'][''] ) )
		$ui->form_options['wp_role_defs'][''] = array( '' );
		
	foreach ( $ui->form_options as $tab_name => $sections )
		foreach ( array_keys($sections) as $section_name )
			if ( empty( $ui->form_options[$tab_name][$section_name] ) )
				unset( $ui->form_options[$tab_name][$section_name] );
}


$ui->display_hints = scoper_get_option('display_hints', $sitewide, $customize_defaults);
?>
<div class='wrap'>
<?php
echo '<form action="" method="post">';
wp_nonce_field( 'scoper-update-options' );

if ( $sitewide )
	echo "<input type='hidden' name='rs_options_doing_sitewide' value='1' />";
	
if ( $customize_defaults )
	echo "<input type='hidden' name='rs_options_customize_defaults' value='1' />";
	
?>
<table width = "100%"><tr>
<td width = "90%">
<h2><?php 
if ( $sitewide ) {
	_e('Role Scoper Network Options', 'scoper');
} elseif ( $customize_defaults ) {
	_e('Role Scoper Default Site Options', 'scoper');
} elseif ( IS_MU_RS )
	_e('Role Scoper Single Site Options', 'scoper');
else
	_e('Role Scoper Options', 'scoper');
?>
</h2>
</td>
<td>
<div class="submit" style="border:none;float:right;margin:0;">
<input type="submit" name="rs_submit" class="button-primary" value="<?php _e('Update &raquo;', 'scoper');?>" />
</div>
</td>
</tr></table>
<?php
if ( $sitewide ) {
	$color_class = 'rs-backgreen';
	echo '<p style="margin-top:0">';
	_e( 'These settings will be applied to all sites.', 'scoper' );
	echo '</p>';
	
} elseif ( $customize_defaults ) {
	$color_class = 'rs-backgray';
	echo '<p style="margin-top:0">';
	_e( 'These are the <strong>default</strong> settings for options which can be adjusted per-site.', 'scoper' );
	echo '</p>';
	
} else
	$color_class = 'rs-backtan';

$class_selected = "agp-selected_agent agp-agent $color_class";
$class_unselected = "agp-unselected_agent agp-agent";

// todo: prevent line breaks in these links
$js_call = "agp_swap_display('rs-features', 'rs-realm', 'rs_show_features', 'rs_show_realm', '$class_selected', '$class_unselected');";
$js_call .= "agp_swap_display('', 'rs-roledefs', '', 'rs_show_roledefs', '$class_selected', '$class_unselected');";
$js_call .= "agp_swap_display('', 'rs-advanced', '', 'rs_show_advanced', '$class_selected', '$class_unselected');";
$js_call .= "agp_swap_display('', 'wp-roledefs', '', 'wp_show_roledefs', '$class_selected', '$class_unselected');";
$js_call .= "agp_swap_display('', 'rs-optscope', '', 'rs_show_optscope', '$class_selected', '$class_unselected');";
echo "<ul class='rs-list_horiz' style='margin-bottom:-0.1em'>"
	. "<li class='$class_selected'>"
	. "<a id='rs_show_features' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['features'] . '</a>'
	. '</li>';
	
if ( ! empty( $ui->form_options['advanced'] ) ) {
	$js_call = "agp_swap_display('rs-advanced', 'rs-features', 'rs_show_advanced', 'rs_show_features', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-realm', '', 'rs_show_realm', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-roledefs', '', 'rs_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'wp-roledefs', '', 'wp_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-optscope', '', 'rs_show_optscope', '$class_selected', '$class_unselected');";
	echo "<li class='$class_unselected'>"
		. "<a id='rs_show_advanced' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['advanced'] . '</a>'
		. '</li>';
}
	
if ( ! empty( $ui->form_options['realm'] ) ) {
	$js_call = "agp_swap_display('rs-realm', 'rs-features', 'rs_show_realm', 'rs_show_features', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-roledefs', '', 'rs_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-advanced', '', 'rs_show_advanced', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'wp-roledefs', '', 'wp_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-optscope', '', 'rs_show_optscope', '$class_selected', '$class_unselected');";
	echo "<li class='$class_unselected'>"
		. "<a id='rs_show_realm' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['realm'] . '</a>'
		. '</li>';
}

if ( ! empty( $ui->form_options['rs_role_definitions'] ) ) {	
	$js_call = "agp_swap_display('rs-roledefs', 'rs-features', 'rs_show_roledefs', 'rs_show_features', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-realm', '', 'rs_show_realm', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-advanced', '', 'rs_show_advanced', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'wp-roledefs', '', 'wp_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-optscope', '', 'rs_show_optscope', '$class_selected', '$class_unselected');";
	echo "<li class='$class_unselected'>"
		. "<a id='rs_show_roledefs' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['rs_role_definitions'] . '</a>'
		. '</li>';
	
	$js_call = "agp_swap_display('wp-roledefs', 'rs-features', 'wp_show_roledefs', 'rs_show_features', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-realm', '', 'rs_show_realm', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-advanced', '', 'rs_show_advanced', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-roledefs', '', 'rs_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-optscope', '', 'rs_show_optscope', '$class_selected', '$class_unselected');";
	echo "<li class='$class_unselected'>"
		. "<a id='wp_show_roledefs' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['wp_role_definitions'] . '</a>'
		. '</li>';
}

if ( $sitewide ) {
	$js_call = "agp_swap_display('rs-optscope', 'rs-features', 'rs_show_optscope', 'rs_show_features', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-advanced', '', 'rs_show_advanced', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-roledefs', '', 'rs_show_roledefs', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'rs-realm', '', 'rs_show_realm', '$class_selected', '$class_unselected');";
	$js_call .= "agp_swap_display('', 'wp-roledefs', '', 'wp_show_roledefs', '$class_selected', '$class_unselected');";
	echo "<li class='$class_unselected'>"
		. "<a id='rs_show_optscope' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['optscope'] . '</a>'
		. '</li>';
}

echo '</ul>';

// ------------------------- BEGIN Features tab ---------------------------------

$tab = 'features';
echo "<div id='rs-features' style='clear:both;margin:0' class='rs-options $color_class'>";
	
if ( scoper_get_option('display_hints', $sitewide, $customize_defaults) ) {
	echo '<div class="rs-optionhint">';
	_e("This page enables <strong>optional</strong> adjustment of Role Scoper's features. For most installations, the default settings are fine.", 'scoper');

	require_once( dirname(__FILE__).'/misc/version_notice_rs.php' );
	$message = scoper_pp_msg();
	echo "<br /><br /><div>$message</div>";
	
	if ( IS_MU_RS && function_exists('is_super_admin') && is_super_admin() ) {
		if ( $sitewide ) {
			if ( ! $customize_defaults ) {
				$link_open = "<a href='admin.php?page=rs-options'>";
				$link_close = '</a>';
		
				echo  ' ';
				
				if ( awp_ver( '3.1' ) )
					_e('Note that, depending on your configuration, site-specific options may also be available.', 'scoper');
				else
					printf( __('Note that, depending on your configuration, %1$s site-specific options%2$s may also be available.', 'scoper'), $link_open, $link_close );
			}
		} else {
			$link_open = ( awp_ver( '3.1' ) ) ? "<a href='network/sites.php?page=rs-site_options'>" : "<a href='admin.php?page=rs-site_options'>";
			$link_close = '</a>';
	
			echo ' ';
			
			printf( __('Note that, depending on your configuration, %1$s network-wide options%2$s may also be available.', 'scoper'), $link_open, $link_close );
		}
	}
	
	echo '</div>';
}

$table_class = 'form-table rs-form-table';
?>

<table class="<?php echo($table_class);?>" id="rs-admin_table">

<?php
// possible TODO: replace redundant hardcoded IDs with $id

$section = 'user_groups';				// --- USER GROUPS SECTION ---
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top"><th scope="row">
	<?php echo $ui->section_captions[$tab][$section]; ?>
	</th><td>
	
	<?php
	$hint = '';
	$ui->option_checkbox( 'define_usergroups', $tab, $section, $hint, '<br />' );

	if ( IS_MU_RS ) {
		$hint = __('If enabled, each user group will be available for role assignment in any site.  Any existing site-specific groups will be unavailable.  Group role assignments are still site-specific.', 'scoper');
		$ui->option_checkbox( 'mu_sitewide_groups', $tab, $section, $hint, '' );
	}	

	$hint = __('Specify group membership via a search/results interface, instead of simple checkboxes.', 'scoper');
	$js_call = "agp_display_if('group_requests_div', 'group_ajax');agp_display_if('group_recommendations_div', 'group_ajax');";
	$ret = $ui->option_checkbox( 'group_ajax', $tab, $section, $hint, '', array( 'js_call' => $js_call ) );	
	$group_ajax = $ret['val'] || ! $ret['in_scope'];
	
	$hint = __('A general role of Group Applicant (or the request_group_membership capability) allows a user to request membership in any existing group via their user profile.', 'scoper');
	$css_display = ( $group_ajax ) ? 'block' : 'none';
	echo "<div id='group_requests_div' style='display:$css_display; margin-top: 1em;margin-left:2em;'>";
	$ui->option_checkbox( 'group_requests', $tab, $section, $hint, '' );	
	echo '</div>';
	
	$hint = __('A Group Moderator role (general or group-specific) allows a user to recommend group members, possibly in response to requests.  This can serve as a two-tier approval mechanism.', 'scoper');
	$css_display = ( $group_ajax ) ? 'block' : 'none';
	echo "<div id='group_recommendations_div' style='display:$css_display; margin-top: 1em;margin-left:2em;'>";
	$ui->option_checkbox( 'group_recommendations', $tab, $section, $hint, '' );
	echo '</div>';
	?>
	</td></tr>
<?php endif; // any options accessable in this section

 
								// --- FRONT END SECTION ---
$section = 'front_end';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];?></th>
	<td>

	<?php
	$hint = __('Remove the "Private:" and "Protected" prefix from Post, Page titles', 'scoper');
	$ui->option_checkbox( 'strip_private_caption', $tab, $section, $hint, '<br />' );
	
	$hint =  __('Reduce memory usage for front-end access by assuming no content, categories or users will be created or edited there. Worst case scenario if you assume wrong: manually assign roles/restrictions to new content or re-sync user roles via plugin re-activation.', 'scoper');
	$ui->option_checkbox( 'no_frontend_admin', $tab, $section, $hint, '' );
	?>
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'pages_listing';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php 		// --- PAGES LISTING SECTION ---
	echo $ui->section_captions[$tab][$section]; ?></th>
	<td>

	<?php 
	$otype_caption = __('Include Private %s in listing if user can read them', 'scoper');
	$hint = __('Determines whether administrators, editors and users who have been granted access to a private page will see it in their sidebar or topbar page listing.', 'scoper');
	$ui->otype_option_checkboxes( 'private_items_listable', $otype_caption, $tab, $section, $hint, '<br /><br />', array( 'caption_header' => false ) );

	$hint = __('If a page\'s parent is not visible to the user, it will be listed below a visible grandparent instead.', 'scoper');
	$js_call = "agp_display_if('enforce_actual_page_depth_div', 'remap_page_parents');agp_display_if('remap_thru_excluded_page_parent_div', 'remap_page_parents');";
	$ret = $ui->option_checkbox( 'remap_page_parents', $tab, $section, $hint, '', array( 'js_call' => $js_call ) );	
	$do_remap = $ret['val'] || ! $ret['in_scope'];
	
	$hint = __('When remapping page parents, apply any depth limits to the actual depth below the requested root page.  If disabled, depth limits apply to apparant depth following remap.', 'scoper');
	$css_display = ( $do_remap ) ? 'block' : 'none';
	echo "<div id='enforce_actual_page_depth_div' style='display:$css_display; margin-top: 1em;margin-left:2em;'>";
	$ui->option_checkbox( 'enforce_actual_page_depth', $tab, $section, $hint, '' );	
	echo '</div>';
	
	$hint = __('Remap a page to next visible ancestor even if some hidden ancestors were explicitly excluded in the get_pages / list_pages call.', 'scoper');
	$css_display = ( $do_remap ) ? 'block' : 'none';
	echo "<div id='remap_thru_excluded_page_parent_div' style='display:$css_display; margin-top: 1em;margin-left:2em;'>";
	$ui->option_checkbox( 'remap_thru_excluded_page_parent', $tab, $section, $hint, '' );
	echo '</div>';
	?>
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'categories_listing';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php 		// --- CATEGORIES LISTING SECTION ---
	echo $ui->section_captions[$tab][$section]; ?></th>
	<td>

	<?php
	$hint = __('If a category\'s parent is not visible to the user, it will be listed below a visible grandparent instead.', 'scoper');
	$js_call = "agp_display_if('enforce_actual_term_depth_div', 'remap_term_parents');agp_display_if('remap_thru_excluded_term_parent_div', 'remap_term_parents');";
	$ret = $ui->option_checkbox( 'remap_term_parents', $tab, $section, $hint, '', array( 'js_call' => $js_call ) );	
	$do_remap = $ret['val'] || ! $ret['in_scope'];
	
	$hint = __('When remapping category parents, apply any depth limits to the actual depth below the requested root category.  If disabled, depth limits apply to apparant depth following remap.', 'scoper');
	$css_display = ( $do_remap ) ? 'block' : 'none';
	echo "<div id='enforce_actual_term_depth_div' style='display:$css_display; margin-top: 1em;margin-left:2em;'>";
	$ret = $ui->option_checkbox( 'enforce_actual_term_depth', $tab, $section, $hint, '' );	
	echo '</div>';
	
	$hint = __('Remap a category to next visible ancestor even if some hidden ancestors were explicitly excluded in the get_terms / get_categories call.', 'scoper');
	$css_display = ( $do_remap ) ? 'block' : 'none';
	echo "<div id='remap_thru_excluded_term_parent_div' style='display:$css_display; margin-top: 1em;margin-left:2em;'>";
	$ret = $ui->option_checkbox( 'remap_thru_excluded_term_parent', $tab, $section, $hint, '' );
	echo '</div>';
	?>	

	</td></tr>
<?php endif; // any options accessable in this section


$section = 'content_maintenance';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php 
									// --- CONTENT MAINTENANCE SECTION ---
	echo $ui->section_captions[$tab][$section]; ?>
	</th><td>

	<?php
	$caption = __( 'Default new %s to Private visibility', 'scoper' );
	$hint = __('Note: this does not apply to Quickposts or XML-RPC submissions.', 'scoper');
	$ui->otype_option_checkboxes( 'default_private', $caption, $tab, $section, $hint, '<br /><br />' );
	
	$caption = __( 'Auto-set %s to Private visibility if Reader role is restricted', 'scoper' );
	$hint = __('Note: this is only done if the Reader role is restricted via Post/Page edit form.', 'scoper');
	$ui->otype_option_checkboxes( 'sync_private', $caption, $tab, $section, $hint, '<br /><br />' );
	
	$hint = __('If enabled, Post Author and Page Author selection dropdowns will be filtered based on scoped roles.', 'scoper');
	$ret = $ui->option_checkbox( 'filter_users_dropdown', $tab, $section, $hint, '<br />' );
	
	$hint = __('If enabled, the create_posts, create_pages, etc. capabilities will be enforced for all post types enabled for RS filtering.', 'scoper' );
	$ret = $ui->option_checkbox( 'define_create_posts_cap', $tab, $section, $hint, '' );
	
	$hint = __('If enabled, Post Author / Editors cannot moderate comments unless their assigned role(s) include the moderate_comments capability.', 'scoper' );
	$ret = $ui->option_checkbox( 'require_moderate_comments_cap', $tab, $section, $hint, '' );
	?>

	</td>
	</tr>
<?php endif; // any options accessable in this section


$section = 'nav_menu_management';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php 				
	echo $ui->section_captions[$tab][$section];		// --- NAV MENU MANAGEMENT SECTION ---
	?></th><td>
	
	<?php
	$hint = '';
	$ui->option_checkbox( 'admin_nav_menu_filter_items', $tab, $section, $hint, '' );
	?>
		
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'role_assignment';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php 
									// --- ROLE ASSIGNMENT SECTION ---
	echo $ui->section_captions[$tab][$section]; ?>
	</th><td>
<?php
	if ( in_array( 'role_admin_blogwide_editor_only', $ui->form_options[$tab][$section] ) ) {
		$id = 'role_admin_blogwide_editor_only';
		$ui->all_options []= $id;
		$current_setting = strval( scoper_get_option($id, $sitewide, $customize_defaults) );  // force setting and corresponding keys to string, to avoid quirks with integer keys

		if ( $current_setting === '' )
			$current_setting = '0';
		?>
		<div class="agp-vspaced_input">
		<label for="role_admin_blogwide_editor_only">
		<?php
		echo $ui->option_captions['role_admin_blogwide_editor_only'];
		
		$captions = array( '0' => __('by the Author or Editor of any Post/Category/Page', 'scoper'), '1' => __('by site-wide Editors and Administrators', 'scoper'), 'admin_content' => __('by Content Administrators only', 'scoper'), 'admin' => __('by User Administrators only', 'scoper') );   // legacy-stored 'admin' in older versions
		
		foreach ( $captions as $key => $value) {
			$key = strval($key);
			echo "<div style='margin: 0 0 0.5em 2em;'><label for='{$id}_{$key}'>";
			$checked = ( $current_setting === $key ) ? "checked='checked'" : '';
		
			echo "<input name='$id' type='radio' id='{$id}_{$key}' value='$key' $checked />";
			echo $value;
			echo '</label></div>';
		}
		?>
		<span class="rs-subtext">
		<?php if ( $ui->display_hints) _e('Specify which users can assign and restrict roles <strong>for their content</strong> - via Post/Page Edit Form or Roles/Restrictions sidebar menu.  For a description of Administrator roles, see the Advanced tab.', 'scoper');?>
		</span>
		</div>
	<?php 
	} // endif role_admin_blogwide_editor_only controlled in this option scope
?>
	</td>
	</tr>
<?php endif; // any options accessable in this section



$section = 'media_library';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php 
									// --- MEDIA LIBRARY SECTION ---
	echo $ui->section_captions[$tab][$section]; ?>
	</th><td>
<?php
	$hint = __('For users who are not site-wide Editors, determines Media Library visibility of files uploaded by another user and now attached to a post which the logged user can edit.', 'scoper');
	$ret = $ui->option_checkbox( 'admin_others_attached_files', $tab, $section, $hint, '' );	
	
	$hint = __('For users who are not site-wide Editors, determines Media Library visibility of unattached files which were uploaded by another user.', 'scoper');
	$ret = $ui->option_checkbox( 'admin_others_unattached_files', $tab, $section, $hint, '' );	
?>
	</td>
	</tr>
<?php endif; // any options accessable in this section
	
	


$section = 'file_filtering';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
	<tr valign="top">
	<th scope="row"><?php 
								// --- ATTACHMENTS SECTION ---
	echo $ui->section_captions[$tab][$section]; ?></th>
	<td>
	
	<?php if ( in_array( 'file_filtering', $ui->form_options[$tab][$section] ) ) :
		$ui->all_options []= 'file_filtering';
		
		$site_url = untrailingslashit( get_option('siteurl') );
		if ( defined('DISABLE_ATTACHMENT_FILTERING') )
			$content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments will not be filtered because DISABLE_ATTACHMENT_FILTERING is defined, perhaps in wp-config.php or role-scoper.php', 'scoper');

		elseif ( MULTISITE && defined('SCOPER_NO_HTACCESS') )
			$content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments will not be filtered because SCOPER_NO_HTACCESS is defined, perhaps in wp-config.php or role-scoper.php', 'scoper');

		else {
			require_once( SCOPER_ABSPATH . '/rewrite-rules_rs.php' );
			
			require_once( SCOPER_ABSPATH . '/uploads_rs.php' );
			$uploads = scoper_get_upload_info();
			
			if ( ! got_mod_rewrite() )
				$content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because mod_rewrite is not enabled on your server.', 'scoper');
			
			elseif ( false === strpos( $uploads['baseurl'], $site_url ) )
				$content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because your WP_CONTENT_DIR is not in the WordPress branch.', 'scoper');
			
			elseif( ! ScoperRewrite::site_config_supports_rewrite() )
				$content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments will not be filtered due to your nonstandard UPLOADS path.', 'scoper');
	
			else {
				global $wp_rewrite;
				if ( empty($wp_rewrite->permalink_structure) )
					$content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because WordPress permalinks are set to default.', 'scoper');
			}
		}

		$disabled = ! empty($content_dir_notice);
		$attachment_filtering = ! $disabled && scoper_get_option('file_filtering', $sitewide, $customize_defaults);
		
		?>
		<label for="file_filtering">
		<input name="file_filtering" type="checkbox" id="file_filtering" <?php echo $disabled;?> value="1" <?php checked(true, $attachment_filtering );?> />
		<?php echo $ui->option_captions['file_filtering']; ?></label>
		<br />
		<div class="rs-subtext">
		<?php
		if ( $ui->display_hints) 
			_e('Block direct URL access to images and other uploaded files in the WordPress uploads folder which are attached to post(s)/page(s) that the user cannot read.  A separate RewriteRule will be added to your .htaccess file for each protected file.  Non-protected files are returned with no script execution whatsoever.', 'scoper');
		if ( $attachment_filtering ) {
			/*
			if ( $ui->display_hints) {
				if ( IS_MU_RS && ! defined('SCOPER_MU_FILE_PROCESSING') ) {
					echo '</div><div class="agp-vspaced_input" style="margin-top: 1em">';
					_e("<strong>Note:</strong> The default WP-MU file request script, <strong>wp-content/blogs.php</strong>, requires a patch for compatibility with RS file filtering.  If you need the advanced cache control provided by blogs.php, review the notes in <strong>role-scoper/mu_wp_content_optional/blogs.php</strong> and copy it into wp-content if desired.  Otherwise, files will be accessed directly via header redirect following RS filtering.  If you decide to install the patched blogs.php, add the following line to wp-config.php:<br />&nbsp;&nbsp;&nbsp; define( 'SCOPER_MU_FILE_PROCESSING', true);", 'scoper');
					echo '</div>';
				}
			}
			*/
		} elseif ( ! empty($content_dir_notice) ) {
			echo '<br /><span class="rs-warning">';
			echo $content_dir_notice;
			echo '</span>';
		}
		?>
		</div><br />
		
		<?php
		$id = 'file_filtering_regen_key';
		$ui->all_options []= $id;		
		$val = scoper_get_option( $id );

		echo "<div><label for='$id'>";
		echo( $ui->option_captions[$id] );
		?>
		<input name="<?php echo($id);?>" type="text" style="vertical-align:middle; width: 8em" id="<?php echo($id);?>" value="<?php echo($val);?>" />
		</label>
		</div>
		
		<?php
		if ( $ui->display_hints)  {
			echo '<div class="rs-subtext">';
			if ( $val ) {
				if ( IS_MU_RS )
					_e( 'To force regeneration of file attachment access keys (at next site access), execute the following URL:', 'scoper' );
				else
					_e( 'To force regeneration of file attachment access keys, execute the following URL:', 'scoper' );
					
				$url = site_url( "index.php?action=expire_file_rules&key=$val" );
				echo( "<br />&nbsp;&nbsp;<a href='$url'>$url</a>" );
			} else
				_e( 'Supply a custom key which will enable a support url to regenerate file access keys.  Then execute the url regularly (using your own cron service) to prevent long-term bookmarking of protected files.', 'scoper');

			echo '</div>';
		}
		?>
		<br />

		<?php
		//printf( _ x('<strong>Note:</strong> FTP-uploaded files will not be filtered correctly until you run the %1$sAttachments Utility%2$s.', 'arguments are link open, link close', 'scoper'), "<a href='admin.php?page=rs-attachments_utility'>", '</a>');
		printf( __('<strong>Note:</strong> FTP-uploaded files will not be filtered correctly until you run the %1$sAttachments Utility%2$s.', 'scoper'), "<a href='admin.php?page=rs-attachments_utility'>", '</a>');
		
		echo '<br /><br />';
		_e( 'If WordPress or any plugin output visible PHP warnings during filtering of a protected image request, the image will not be successfully returned.', 'scoper' );
		?>
		<br />
	<?php endif;?>
	
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'date_limits';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];		// --- ROLE DATE LIMITS SECTION ---
	?></th><td>
	
	<?php
	$hint = __('Allow the delay or expiration of roles based on a specified date range.', 'scoper');
	$ret = $ui->option_checkbox( 'role_duration_limits', $tab, $section, $hint, '' );	
	
	$hint = __('Allow General Roles and Category Roles to be limited to content dated within in a specified range.', 'scoper');
	$ret = $ui->option_checkbox( 'role_content_date_limits', $tab, $section, $hint, '' );	
	?>
	
	</td></tr>
<?php endif; // any options accessable in this section



$section = 'internal_cache';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :
									// --- PERSISTENT CACHE SECTION ---
?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
	<td>
		
	<?php if ( in_array( 'persistent_cache', $ui->form_options[$tab][$section] ) ) :
		$ui->all_options []= 'persistent_cache';
						
		$cache_selected = scoper_get_option('persistent_cache', $sitewide, $customize_defaults);
		$cache_enabled = $cache_selected && ! defined('DISABLE_PERSISTENT_CACHE');
		?>
		<label for="persistent_cache">
		<input name="persistent_cache" type="checkbox" id="persistent_cache" value="1" <?php checked(true, $cache_enabled);?> />
		<?php echo $ui->option_captions['persistent_cache']; ?></label>
		<br />
		<span class="rs-subtext">
		<?php 
		if ( $ui->display_hints) _e('Group membership, role restrictions, role assignments and some filtered results (including term listings and WP page, category and bookmark listings) will be stored to disk, on a user-specific or group-specific basis where applicable.  This does not cache content such as post listings or page views.', 'scoper');
		echo '</span>';
		
		$cache_msg = '';
		if ( $cache_selected && ! wpp_cache_test( $cache_msg, 'scoper' ) ) {
			echo '<div class="agp-vspaced_input"><span class="rs-warning">';
			echo $cache_msg;
			echo '</span></div>';
		} elseif ( $cache_enabled && ! file_exists('../rs_cache_flush.php') && ! file_exists('..\rs_cache_flush.php') ) {
			echo '<div class="agp-vspaced_input"><span class="rs-warning">';	
			_e('<strong>Note:</strong> The internal caching code contains numerous safeguards against corruption.  However, if it does become corrupted your site may be inaccessable.  For optimal reliability, copy rs_cache_flush.php into your WP root directory so it can be executed directly if needed.', 'scoper');
			echo '</span></div>';
		}
		?>
		
		<?php if($cache_enabled):?>
		<br />
		<span class="submit" style="border:none;float:left;margin-top:0">
		<input type="submit" name="rs_flush_cache" value="<?php _e('Flush Cache', 'scoper') ?>" />
		</span>
		<?php endif;?>
	<?php endif;?>
		
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'version';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :
								// --- VERSION SECTION ---
	?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
	<td>
	
	<?php if ( in_array( 'version_update_notice', $ui->form_options[$tab][$section] ) ) :
		$ui->all_options []= 'version_update_notice';?>
		<?php
		printf( __( "Role Scoper Version: %s", 'scoper'), SCOPER_VERSION);
		echo '<br />';
		printf( __( "Database Schema Version: %s", 'scoper'), SCOPER_DB_VERSION);
		echo '<br />';
		global $wp_version;
		printf( __( "WordPress Version: %s", 'scoper'), $wp_version );
		echo '<br />';
		printf( __( "PHP Version: %s", 'scoper'), phpversion() );
		echo '<br />';
		
		$hint = '';
		$ui->option_checkbox( 'version_update_notice', $tab, $section, $hint, '<br />' );
		?>
	<?php endif;?>
		
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'rss_feeds';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php 
									// --- RSS FEEDS SECTION ---
	echo $ui->section_captions[$tab][$section]; ?></th>
	<td>
	
	<?php if ( in_array( 'feed_link_http_auth', $ui->form_options[$tab][$section] ) ) :
		if ( ! defined('HTTP_AUTH_DISABLED_RS') ) {
			$id = 'feed_link_http_auth';
			$ui->all_options []= $id;
			$current_setting = scoper_get_option($id, $sitewide, $customize_defaults);
			
			echo $ui->option_captions['feed_link_http_auth'];
		
			echo "&nbsp;<select name='$id' id='$id'>";
			$captions = array( 0 => __('never', 'scoper'), 1 => __('always', 'scoper'), 'logged' => __('for logged users', 'scoper') );
			foreach ( $captions as $key => $value) {
				$selected = ( $current_setting == $key ) ? 'selected="selected"' : '';
				echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
			}
			echo '</select>&nbsp;';
			echo "<br />";
			echo '<span class="rs-subtext">';
			if ( $ui->display_hints ) _e('Suffix RSS feed links with an extra parameter to trigger required HTTP authentication. Note that anonymous and cookie-based RSS will still be available via the standard feed URL.', 'scoper');
			echo '</span>';
		} else {
			echo '<span class="rs-warning">';
			_e( 'cannot use HTTP Authentication for RSS Feeds because another plugin has already defined the function "get_currentuserinfo"', 'scoper' );
			echo '</span>';
		}
		
		echo "<br /><br />";
	endif;?>
		
	<?php if ( in_array( 'rss_private_feed_mode', $ui->form_options[$tab][$section] ) ) :
		$ui->all_options []= 'rss_private_feed_mode'; 
		
		//echo ( _ x( 'Display', 'prefix to RSS content dropdown', 'scoper' ) );
		echo ( __( 'Display', 'scoper' ) );
		echo '&nbsp;<select name="rss_private_feed_mode" id="rss_private_feed_mode">';
		
		$captions = array( 'full_content' => __("Full Content", 'scoper'), 'excerpt_only' => __("Excerpt Only", 'scoper'), 'title_only' => __("Title Only", 'scoper') );
		foreach ( $captions as $key => $value) {
			$selected = ( scoper_get_option('rss_private_feed_mode', $sitewide, $customize_defaults) == $key ) ? 'selected="selected"' : '';
			echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
		}
		echo '</select>&nbsp;';
		//echo ( _ x( 'for readable private posts', 'suffix to RSS content dropdown', 'scoper' ) );
		echo ( __( 'for readable private posts', 'scoper' ) );
		echo "<br />";
	endif;?>
	
	<?php if ( in_array( 'rss_nonprivate_feed_mode', $ui->form_options[$tab][$section] ) ) :
		$ui->all_options []= 'rss_nonprivate_feed_mode'; 
		//echo ( _ x( 'Display', 'prefix to RSS content dropdown', 'scoper' ) );
		echo ( __( 'Display', 'scoper' ) );
		echo '&nbsp;<select name="rss_nonprivate_feed_mode" id="rss_nonprivate_feed_mode">';
		
		$captions = array( 'full_content' => __("Full Content", 'scoper'), 'excerpt_only' => __("Excerpt Only", 'scoper'), 'title_only' => __("Title Only", 'scoper') );
		foreach ( $captions as $key => $value) {
			$selected = ( scoper_get_option('rss_nonprivate_feed_mode', $sitewide, $customize_defaults) == $key ) ? 'selected="selected"' : '';
			echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
		}
		echo '</select>&nbsp;';
		//echo ( _ x( 'for readable non-private posts', 'suffix to RSS content dropdown', 'scoper' ) );
		echo ( __( 'for readable non-private posts', 'scoper' ) );
		
		echo "<br />";
		?>
		<span class="rs-subtext">
		<?php if ( $ui->display_hints ) _e('Since some browsers will cache feeds without regard to user login, block RSS content even for qualified users.', 'scoper');?>
		</span>
		<br /><br />
	<?php endif;?>
		
	<?php if ( in_array( 'feed_teaser', $ui->form_options[$tab][$section] ) ) :
		$id = 'feed_teaser';
		$ui->all_options []= $id;
		$val = htmlspecialchars( scoper_get_option($id, $sitewide, $customize_defaults) );
		
		echo "<label for='$id'>";
		_e ( 'Feed Replacement Text (use %permalink% for post URL)', 'scoper' );
		echo "<br /><textarea name='$id' cols=60 rows=1 id='$id'>$val</textarea>";
		echo "</label>";
	endif;?>
	
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'hidden_content_teaser';
if ( ! empty( $ui->form_options[$tab][$section] ) && in_array( 'do_teaser', $ui->form_options[$tab][$section] ) ) : 	// for now, teaser option are all-or-nothing sitewide / blogwide 
?>
	<tr valign="top">
	<th scope="row"><?php 
									// --- HIDDEN CONTENT TEASER SECTION ---
	echo $ui->section_captions[$tab][$section]; ?></th>
	<td>
	<?php
		// a "do teaser checkbox for each data source" that has a def_otype_options	entry
		$option_basename = 'do_teaser';
		if ( isset($ui->def_otype_options[$option_basename]) ) {
			$ui->all_otype_options []= $option_basename;
		
			$opt_vals = scoper_get_option( $option_basename, $sitewide, $customize_defaults );
			if ( ! $opt_vals || ! is_array($opt_vals) )
				$opt_vals = array();
			$do_teaser = array_merge( $ui->def_otype_options[$option_basename], $opt_vals );
			
			$option_hide_private = 'teaser_hide_private';
			$ui->all_otype_options []= $option_hide_private;
			$opt_vals = scoper_get_option( $option_hide_private, $sitewide, $customize_defaults );
			if ( ! $opt_vals || ! is_array($opt_vals) )
				$opt_vals = array();
			$hide_private = array_merge( $ui->def_otype_options[$option_hide_private], $opt_vals );
			
			$option_use_teaser = 'use_teaser';
			$ui->all_otype_options []= $option_use_teaser;
			$opt_vals = scoper_get_option( $option_use_teaser, $sitewide, $customize_defaults );
			if ( ! $opt_vals || ! is_array($opt_vals) )
				$opt_vals = array();
			$use_teaser = array_merge( $ui->def_otype_options[$option_use_teaser], $opt_vals );
			
			$option_logged_only = 'teaser_logged_only';
			$ui->all_otype_options []= $option_logged_only;
			$opt_vals = scoper_get_option( $option_logged_only, $sitewide, $customize_defaults );
			if ( ! $opt_vals || ! is_array($opt_vals) )
				$opt_vals = array();
			$logged_only = array_merge( $ui->def_otype_options[$option_logged_only], $opt_vals );
			
			// loop through each source that has a default do_teaser setting defined
			foreach ( $do_teaser as $src_name => $val ) {
				$id = $option_basename . '-' . $src_name;
				
				echo '<div class="agp-vspaced_input">';
				echo "<label for='$id'>";
				$checked = ( $val ) ? ' checked="checked"' : '';
				$js_call = "agp_display_if('teaserdef-$src_name', '$id');agp_display_if('teaser_usage-$src_name', '$id');agp_display_if('teaser-pvt-$src_name', '$id');";
				echo "<input name='$id' type='checkbox' onclick=\"$js_call\" id='$id' value='1' $checked /> ";
	
				$display = scoper_display_otypes_or_source_name($src_name);
				printf(__("Enable teaser for %s", 'scoper'), $display);
				echo ('</label><br />');
				
				$css_display = ( $do_teaser[$src_name] ) ? 'block' : 'none';
	
				$style = "style='margin-left: 1em;'";
				echo "<div id='teaser_usage-$src_name' style='display:$css_display;'>";
				
				// loop through each object type (for current source) to provide a use_teaser checkbox
				foreach ( $use_teaser as $src_otype => $teaser_setting ) {
					if ( $src_name != scoper_src_name_from_src_otype($src_otype) )
						continue;
					
					if ( is_bool($teaser_setting) )
						$teaser_setting = intval($teaser_setting);
						
					$id = str_replace(':', '_', $option_use_teaser . '-' . $src_otype);
					
					echo '<div class="agp-vspaced_input">';
					echo "<label for='$id' style='margin-left: 2em;'>";
					
					$item_label_singular = $scoper_admin->interpret_src_otype($src_otype);
					printf(__("%s:", 'scoper'), $item_label_singular);
					
					echo "<select name='$id' id='$id'>";
					$num_chars = ( defined('SCOPER_TEASER_NUM_CHARS') ) ? SCOPER_TEASER_NUM_CHARS : 50;
					$captions = array( 0 => __("no teaser", 'scoper'), 1 => __("fixed teaser (specified below)", 'scoper'), 'excerpt' => __("excerpt as teaser", 'scoper'), 'more' => __("excerpt or pre-more as teaser", 'scoper'), 'x_chars' => sprintf(__("excerpt, pre-more or first %s chars", 'scoper'), $num_chars) );
					foreach ( $captions as $teaser_option_val => $teaser_caption) {
						$selected = ( $teaser_setting == $teaser_option_val ) ? 'selected="selected"' : '';
						echo "\n\t<option value='$teaser_option_val' $selected>$teaser_caption</option>";
					}
					echo '</select></label><br />';
					
					// Checkbox option to skip teaser for anonymous users
					$id = str_replace(':', '_', $option_logged_only . '-' . $src_otype);
					echo "<span style='margin-left: 6em'>";
					//echo( _ x( 'for:', 'teaser: anonymous, logged or both', 'scoper') );
					echo( __( 'for:', 'scoper') );
					echo "&nbsp;&nbsp;<label for='{$id}_logged'>";
					$checked = ( ! empty($logged_only[$src_otype]) && 'anon' == $logged_only[$src_otype] ) ? ' checked="checked"' : '';
					echo "<input name='$id' type='radio' id='{$id}_logged' value='anon' $checked />";
					echo "";
					_e( "anonymous", 'scoper');
					echo '</label></span>';
	
					// Checkbox option to skip teaser for logged users
					echo "<span style='margin-left: 1em'><label for='{$id}_anon'>";
					$checked = ( ! empty($logged_only[$src_otype]) && 'anon' != $logged_only[$src_otype] ) ? ' checked="checked"' : '';
					echo "<input name='$id' type='radio' id='{$id}_anon' value='1' $checked />";
					echo "";
					_e( "logged", 'scoper');
					echo '</label></span>';
	
					// Checkbox option to do teaser for BOTH logged and anon users
					echo "<span style='margin-left: 1em'><label for='{$id}_all'>";
					$checked = ( empty($logged_only[$src_otype]) ) ? ' checked="checked"' : '';
					echo "<input name='$id' type='radio' id='{$id}_all' value='0' $checked />";
					echo "";
					_e( "both", 'scoper');
					echo '</label></span>';
					
					echo ('</div>');
				}
				echo '</div>';
	
				if ( empty($displayed_teaser_caption) ) {
					echo '<span class="rs-subtext">';
					if ( $ui->display_hints) {
						_e('If content is blocked, display replacement text instead of hiding it completely.', 'scoper');
						echo '<br />';
						_e('<strong>Note:</strong> the prefix and suffix settings below will always be applied unless the teaser mode is "no teaser".', 'scoper');
					}
					echo '</span>';
						
					$displayed_teaser_caption = true;
				}
	
				// provide hide private (instead of teasing) checkboxes for each pertinent object type
				echo '<br /><br />';
				$display_style = ( $do_teaser[$src_name] ) ? '' : "style='display:none;'";
				echo "<div id='teaser-pvt-$src_name' $display_style>";

				$type_caption = $scoper_admin->interpret_src_otype('post:post');
				printf(__("Hide private %s (instead of teasing)", 'scoper'), $type_caption);	// back compat for existing translations
				echo '<br />';
				echo '<div style="margin-left: 2em">';
				foreach ( $hide_private as $src_otype => $teaser_setting ) {
					if ( $src_name != scoper_src_name_from_src_otype($src_otype) )
						continue;
	
					$id = str_replace(':', '_', $option_hide_private . '-' . $src_otype);
					
					echo "<label for='$id'>";
					$checked = ( $teaser_setting ) ? ' checked="checked"' : '';
					echo "<input name='$id' type='checkbox' id='$id' value='1' $checked /> ";

					echo ( $scoper_admin->interpret_src_otype($src_otype) );
					echo ('</label><br />');
				}
				echo '</div>';
				echo '<span class="rs-subtext">';
				if ( $ui->display_hints) _e('Hide private content completely, while still showing a teaser for content which is published with restrictions.  <strong>Note:</strong> Private posts hidden in this way will reduce the total number of posts on their "page" of a blog listing.', 'scoper');
				echo '</span>';
				echo '</div>';
			} // end foreach source's do_teaser setting
	?>
	</div>
	<?php		
			// now draw the teaser replacement / prefix / suffix input boxes
			$user_suffixes = array('_anon', '');
			$item_actions = array(	'name' => 	array('prepend', 'append'), 
							'content' => array('replace', 'prepend', 'append'), 
							'excerpt' => array('replace', 'prepend', 'append') ); 
			
			$items_display = array( 'name' => __('name', 'scoper'), 'content' => __('content', 'scoper'), 'excerpt' => __('excerpt', 'scoper') );
			$actions_display = array( 'replace' => __('replace with (if using fixed teaser, or no excerpt available):', 'scoper'), 'prepend' => __('prefix with:', 'scoper'), 'append' => __('suffix with:', 'scoper') );	
			
			// first determine all src:otype keys
			$src_otypes = array();
			foreach ( $user_suffixes as $anon )
				foreach ( $item_actions as $item => $actions )
					foreach ( $actions as $action ) {
						$ui->all_otype_options []= "teaser_{$action}_{$item}{$anon}";
						
						if ( ! empty($ui->def_otype_options["teaser_{$action}_{$item}{$anon}"]) )
							$src_otypes = array_merge($src_otypes, $ui->def_otype_options["teaser_{$action}_{$item}{$anon}"]);
					}
					
			$last_src_name = '';	
			foreach ( array_keys($src_otypes) as $src_otype ) {
				$src_name = scoper_src_name_from_src_otype($src_otype);
				if ( $src_name != $last_src_name ) {
					if ( $last_src_name )
						echo '</div>';
					
					$last_src_name = $src_name;
					$css_display = ( $do_teaser[$src_name] ) ? 'block' : 'none';
					echo "<div id='teaserdef-$src_name' style='display:$css_display; margin-top: 2em;'>";
				}
				
				$item_label_singular = $scoper_admin->interpret_src_otype($src_otype);
				
				// separate input boxes to specify teasers for anon users and unpermitted logged users
				foreach ( $user_suffixes as $anon ) {
					$user_descript = ( $anon ) ?  __('anonymous users', 'scoper') : __('logged users', 'scoper');
					
					echo '<strong>';
					printf( __('%1$s Teaser Text (%2$s):', 'scoper'), $item_label_singular, $user_descript );
					echo '</strong>';
					echo ('<ul class="rs-textentries">');
				
					// items are name, content, excerpt
					foreach ( $item_actions as $item => $actions ) {
						echo ('<li>' . $items_display[$item] . ':');
						echo '<ul>';
						
						// actions are prepend / append / replace
						foreach( $actions as $action ) {
							$option_name = "teaser_{$action}_{$item}{$anon}";
							if ( ! $opt_vals = scoper_get_option( $option_name, $sitewide, $customize_defaults ) )
								$opt_vals = array();
							
							$ui->all_otype_options []= $option_name;
								
							if ( ! empty($ui->def_otype_options["teaser_{$action}_{$item}{$anon}"]) )
								$opt_vals = array_merge($ui->def_otype_options[$option_name], $opt_vals);
								
							if ( isset($opt_vals[$src_otype]) ) {
								$val = htmlspecialchars($opt_vals[$src_otype]);
								$id = str_replace(':', '_', $option_name . '-' . $src_otype);
								
								echo "<li><label for='$id'>";
								echo( $actions_display[$action] );
	?>
	<input name="<?php echo($id);?>" type="text" style="width: 95%" id="<?php echo($id);?>" value="<?php echo($val);?>" />
	</label><br /></li>
	<?php
							} // endif isset($opt_vals)
						} // end foreach actions
						
						echo ('</ul></li>');
					} // end foreach item_actions
					
					echo ("</ul><br />");
				} // end foreach user_suffixes
				
			} // end foreach src_otypes
			
			echo '</div>';
		} // endif any default otype_options for do_teaser
	?>
	</td>
	</tr>
<?php endif; // any options accessable in this section

if ( ! defined('RVY_VERSION' ) ) {
	echo '<tr><td colspan="2"><div class="rs-optionhint"><p style="margin-left:4em;text-indent:-3.5em">&nbsp;';
	printf( __('<span class="rs-green"><strong>Idea:</strong></span> For Scheduled Revisions and Pending Revisions functionality that integrates with your RS Roles and Restrictions, install %1$s Revisionary%2$s, another %3$s Agapetry&nbsp;Creations%4$s plugin.', 'scoper'), "<a href='" . awp_plugin_info_url("revisionary") . "'>", '</a>', "<a href='http://agapetry.net'>", '</a>' );	
	echo '</p></div></td></tr>';
}

?>
	
</table>

</div>
<?php
// ------------------------- END Features tab ---------------------------------

// ------------------------- BEGIN Advanced tab ---------------------------------
$tab = 'advanced';

if ( ! empty( $ui->form_options[$tab] ) ) :
?>

<?php
echo "<div id='rs-advanced' style='clear:both;margin:0' class='rs-options agp_js_hide $color_class'>";

if ( $ui->display_hints ) {
	echo '<div class="rs-optionhint">';
	_e("<strong>Note:</strong> for most installations, the default settings are fine.", 'scoper');
	echo '</div>';
}
?>
<table class="<?php echo($table_class);?>" id="rs-advanced_table">
<?php

$section = 'role_basis';
if ( ! empty( $ui->form_options[$tab][$section] ) ) :														
?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];		// --- ROLE BASIS SECTION --- 
	?>
	</th><td>
	
	<?php
	$hint = '';
	$ui->option_checkbox( 'enable_group_roles', $tab, $section, $hint, '' );
	
	$hint = '';
	$ui->option_checkbox( 'enable_user_roles', $tab, $section, $hint, '' );
	
	if ( scoper_get_option('custom_user_blogcaps', $sitewide, $customize_defaults) || ScoperAdminLib::any_custom_caps_assigned() ) {
		$hint = __('Some users created under older WP versions may have direct-assigned capabilities in addition to their blog-wide role assignment.  This setting does not enable or block that feature, but determines whether Role Scoper must account for it.  Disable when possible (capabilities unrelated to RS Role Definitions are irrelevant).', 'scoper');
		$ui->option_checkbox( 'custom_user_blogcaps', $tab, $section, $hint, '' );
	}
	?>
	
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'page_structure';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];		// --- PAGE STRUCTURE SECTION ---
	?></th><td>

	<?php
	$id = 'lock_top_pages';
	$ui->all_options []= $id;
	$current_setting = strval( scoper_get_option($id, $sitewide, $customize_defaults) );  // force setting and corresponding keys to string, to avoid quirks with integer keys

	echo $ui->option_captions['lock_top_pages'];

	$captions = array( 'no_parent_filter' => __( 'no Page Parent filter (all published pages always selectable)' , 'scoper' ), 'author' => __('Page Authors, Editors and Administrators', 'scoper'), '' => __('Page Editors and Administrators', 'scoper'), '1' => __('Administrators', 'scoper') );
	
	foreach ( $captions as $key => $value) {
		$key = strval($key);
		echo "<div style='margin: 0 0 0.5em 2em;'><label for='{$id}_{$key}'>";
		$checked = ( $current_setting === $key ) ? "checked='checked'" : '';
	
		echo "<input name='$id' type='radio' id='{$id}_{$key}' value='$key' $checked /> ";
		echo $value;
		echo '</label></div>';
	}
	
	echo '<span class="rs-subtext">';
	if ( $ui->display_hints )
		_e('Users who do not meet this site-wide role requirement may still be able to save and/or publish pages, but will not be able to publish a new page with a Page Parent setting of "Main Page".  Nor will they be able to move a currently published page from "Main Page" to a different Page Parent.', 'scoper');
	
	echo '</span>';
	?>

	</td></tr>
<?php endif; // any options accessable in this section


$section = 'user_profile';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];		// --- USER PROFILE SECTION ---
	?></th><td>
	
	<?php
	$hint = '';
	$ui->option_checkbox( 'display_user_profile_groups', $tab, $section, $hint, '' );
	
	$hint = '';
	$ui->option_checkbox( 'display_user_profile_roles', $tab, $section, $hint, '' );
	?>
		
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'user_management';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];		// --- USER MANAGEMENT SECTION ---
	?></th><td>
	
	<?php
	$hint =  __('If enabled, prevents those with edit_users capability from editing a user with a higher level or assigning a role higher than their own.', 'scoper');
	$ui->option_checkbox( 'limit_user_edit_by_level', $tab, $section, $hint, '' );
	?>

	</td></tr>
<?php endif; // any options accessable in this section


$section = 'administrator_definition';
if ( $sitewide || ! IS_MU_RS ) : ?>
	<tr valign="top">
	<th scope="row"><?php echo $ui->section_captions[$tab][$section];		// --- ADMINISTRATOR DEFINITION SECTION ---
	?></th><td>
	<?php
	$default_cap = array( 'option' => 'manage_options', 'user' => 'edit_users', 'content' => 'activate_plugins' );
	
	$name['content'] = __('Content Administrator', 'scoper');
	$name['user'] = __('User Administrator', 'scoper');
	$name['option'] = __('Option Administrator', 'scoper');
	
	$descript['content'] = __('RS never applies restricting or enabling content filters', 'scoper');
	$descript['user'] = __('RS allows full editing of all user groups and scoped roles / restrictions', 'scoper');
	$descript['option'] = __('Can edit Role Scoper options', 'scoper');
	
	_e( 'Role Scoper internally checks the following capabilities for content and role administration.  By default, these capabilities are all contained in the Administrator role only.  You can use the Capability Manager plugin to split/overlap them among different roles as desired.', 'scoper' );
	
	echo '<table id="rs-admin-info" style="max-width: 80em">'
		. '<tr>'
		. '<th style="font-weight:normal">' . __( 'Administrator Type', 'scoper' ) . '</th>'
		. '<th style="font-weight:normal">' . __( 'Required Capability', 'scoper' ) . '</th>'
		. '<th width="80%" style="font-weight:normal">' . __awp( 'Description' ) . '</th>'
		. '</tr>';
	
	foreach ( array_keys($name) as $admin_type ) {
		$constant_name = 'SCOPER_' . strtoupper($admin_type) . '_ADMIN_CAP';
		$cap_name = ( defined( $constant_name ) ) ? constant( $constant_name ) : $default_cap[$admin_type];
		
		echo '<tr>'
			. '<td><strong>' . $name[$admin_type] . '</strong></td>'
			. '<td>' . $cap_name . '</td>'
			. '<td width="60%" >' . $descript[$admin_type] . '</td>'
			. '</tr>';
			
		if ( ( 'content' == $admin_type ) && ( $cap_name != $default_cap['content'] ) )
			$custom_content_admin_cap = true;
	}
	echo '</table>';
	
	_e( 'Each administrator type\'s <strong>capability can be modified</strong> by adding any of the following define statements to your wp-config.php:', 'scoper' );
	echo '<div style="margin:0 10em 2em 10em">';
	echo( "<code>define( 'SCOPER_CONTENT_ADMIN_CAP', 'your_content_capname' );</code>" );
	echo '<br />';
	echo( "<code>define( 'SCOPER_USER_ADMIN_CAP', 'your_user_capname' );</code>" );
	echo '<br />';
	echo( "<code>define( 'SCOPER_OPTION_ADMIN_CAP', 'your_option_capname' );</code>" );
	echo '</div>';
	_e( 'For example, by using Capability Manager to add a custom capability such as <strong>administer_all_content</strong> to certain WordPress roles, you could mirror that setting in the SCOPER_CONTENT_ADMIN_CAP definition to ensure that those users are never content-restricted by Role Scoper.', 'scoper');
	?>
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'limited_editing_elements';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php 
	echo $ui->section_captions[$tab][$section];		// --- LIMITED EDITING ELEMENTS SECTION ---
	?></th><td>
	
	<?php if ( in_array( 'admin_css_ids', $ui->form_options[$tab][$section] ) ) :?>
		<div class="agp-vspaced_input">
		<?php
			if ( $ui->display_hints) {
				echo ('<div class="agp-vspaced_input">');
				_e('Remove Edit Form elements with these html IDs from users who do not have full editing capabilities for the post/page. Separate with&nbsp;;', 'scoper');
				echo '</div>';
			}
		?>
		</div>
		<?php
			$option_name = 'admin_css_ids';
			if ( isset($ui->def_otype_options[$option_name]) ) {
				if ( ! $opt_vals = scoper_get_option( $option_name, $sitewide, $customize_defaults ) )
					$opt_vals = array();
					
				$opt_vals = array_merge($ui->def_otype_options[$option_name], $opt_vals);
				
				$ui->all_otype_options []= $option_name;
				
				$sample_ids = array();
		
				// note: 'post:post' otype option is used for all non-page types
				$sample_ids['post:post'] = '<span id="rs_sample_ids_post:post" class="rs-gray" style="display:none">' . 'rs_private_post_reader; rs_post_contributor; categorydiv; password-span; slugdiv; authordiv; commentstatusdiv; postcustom; trackbacksdiv; tagsdiv-post_tag; postexcerpt; revisionsdiv; visibility; misc-publishing-actions; edit-slug-box' . '</span>';
				$sample_ids['post:page'] = '<span id="rs_sample_ids_post:page" class="rs-gray" style="display:none">' . 'rs_private_page_reader; rs_page_contributor; rs_page_associate; password-span; pageslugdiv; pageauthordiv; pagecommentstatusdiv; pagecustomdiv; pageparentdiv; revisionsdiv; visibility; misc-publishing-actions; edit-slug-box' . '</span>';
				
				foreach ( $opt_vals as $src_otype => $val ) {
					$id = str_replace(':', '_', $option_name . '-' . $src_otype);
					$display = $scoper_admin->interpret_src_otype($src_otype, 'singular_name');
					echo('<div class="agp-vspaced_input">');
					echo('<span class="rs-vtight">');
					printf(__('%s Edit Form HTML IDs:', 'scoper'), $display);
		?>
		<label for="<?php echo($id);?>">
		<input name="<?php echo($id);?>" type="text" size="45" style="width: 95%" id="<?php echo($id);?>" value="<?php echo($val);?>" />
		</label>
		</span>
		<br />
		<?php
		if ( isset($sample_ids[$src_otype]) ) {
			$js_call = "agp_set_display('rs_sample_ids_$src_otype', 'inline');";
			printf(__('%1$s sample IDs:%2$s %3$s', 'scoper'), "<a href='javascript:void(0)' onclick=\"$js_call\">", '</a>', $sample_ids[$src_otype] );
		}
		?>
		</div>
		<?php
				} // end foreach optval
			} // endif any default admin_css_ids options
		?>
		
		<br />
	<?php endif;?>
		
	
	<?php if ( in_array( 'hide_non_editor_admin_divs', $ui->form_options[$tab][$section] ) ) :
		$id = 'hide_non_editor_admin_divs';
		$ui->all_options []= $id;
		$current_setting = strval( scoper_get_option($id, $sitewide, $customize_defaults) );  // force setting and corresponding keys to string, to avoid quirks with integer keys
		?>
		<div class="agp-vspaced_input">
		<?php
		_e('Specified element IDs also require the following site-wide Role:', 'scoper');

		$admin_caption = ( ! empty($custom_content_admin_cap) ) ? __('Content Administrator', 'scoper') : __awp('Administrator');
		
		$captions = array( '0' => __('no requirement', 'scoper'), '1' => __('Contributor / Author / Editor', 'scoper'), 'author' => __('Author / Editor', 'scoper'), 'editor' => __awp('Editor'), 'admin_content' => __('Content Administrator', 'scoper'), 'admin_user' => __('User Administrator', 'scoper'), 'admin_option' => __('Option Administrator', 'scoper') );
		
		foreach ( $captions as $key => $value) {
			$key = strval($key);
			echo "<div style='margin: 0 0 0.5em 2em;'><label for='{$id}_{$key}'>";
			$checked = ( $current_setting === $key ) ? "checked='checked'" : '';
		
			echo "<input name='$id' type='radio' id='{$id}_{$key}' value='$key' $checked />";
			echo $value;
			echo '</label></div>';
		}
		?>
		<span class="rs-subtext">
		<?php 
		if ( $ui->display_hints)
			_e('Note: The above roles are type-specific RS roles (for the object type involved) which must be contained in a user\'s site-wide WordPress role.', 'scoper');
		?>
		</span>
		</div>
	<?php endif;?>
		
	</td>
	</tr>
<?php endif; // any options accessable in this section


$section = 'role_assignment_interface';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php 				
	echo $ui->section_captions[$tab][$section];		// --- ROLE ASSIGNMENT INTERFACE SECTION ---
	?></th><td>
	
	<?php
	$otype_caption = __('Limit eligible users for %s-specific editing roles', 'scoper');
	$hint = __('Role Scoper can enable any user to edit a post or page you specify, regardless of their site-wide WordPress role.  If that\'s not a good thing, check above options to require basic editing capability blog-wide or category-wide.', 'scoper');
	$ui->otype_option_checkboxes( 'limit_object_editors', $otype_caption, $tab, $section, $hint, '<br /><br />', array( 'label_property' => 'singular_name' ) );
	
	$hint =  __('In the Edit Post/Edit Page roles tabs, decorate user/group name with colors and symbols if they have the role implicitly via group, general role, category role, or a superior post/page role.', 'scoper');
	$ui->option_checkbox( 'indicate_blended_roles', $tab, $section, $hint, '<br />' );
	
	$hint =  __('Display introductory descriptions at the top of various role assignment / definition screens.', 'scoper');
	$ui->option_checkbox( 'display_hints', $tab, $section, $hint, '<br />' );
	
	$hint =  __('Accept entry of user names or IDs via comma-separated text instead of individual checkboxes.', 'scoper');
	$ui->option_checkbox( 'user_role_assignment_csv', $tab, $section, $hint, '' );
	?>
		
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'custom_columns';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php 				
	echo $ui->section_captions[$tab][$section];		// --- ROLE ASSIGNMENT INTERFACE SECTION ---
	?></th><td>
	
	<?php
	$hint = '';
	$otype_caption = __('Restrictions column in Edit %s listing', 'scoper');
	$ui->otype_option_checkboxes( 'restrictions_column', $otype_caption, $tab, $section, $hint, '<br />' );
	
	$otype_caption = __('Term Roles column in Edit %s listing', 'scoper');
	$ui->otype_option_checkboxes( 'term_roles_column', $otype_caption, $tab, $section, $hint, '<br />' );

	$otype_caption = __('Object Roles column in Edit %s listing', 'scoper');
	$ui->otype_option_checkboxes( 'object_roles_column', $otype_caption, $tab, $section, $hint, '<br />' );
	?>
		
	</td></tr>
<?php endif; // any options accessable in this section


$section = 'additional_object_roles';
if (  ! empty( $ui->form_options[$tab][$section] ) ) :
	$post_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ); 
	foreach( $post_types as $_type ) {
		$objscope_equiv_roles["rs_{$_type}_reader"] = "rs_private_{$_type}_reader";
		$objscope_equiv_roles["rs_{$_type}_author"] = "rs_{$_type}_editor";
	}

	if ( IS_MU_RS ) {  // apply option scope filtering for mu
		foreach ( array_keys($objscope_equiv_roles) as $role_name )
			if ( ! in_array( $role_name . '_role_objscope', $ui->form_options[$tab][$section] ) )
				$objscope_equiv_roles = array_diff_key( $objscope_equiv_roles, array( $role_name => true ) );
	}

	if ( ! empty( $objscope_equiv_roles ) ) : ?>
		<?php 
		foreach ( $objscope_equiv_roles as $role_handle => $equiv_role_handle ) {
			$ui->all_options []= "{$role_handle}_role_objscope";
		}
		?>
		<tr valign="top">
		<th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
		<td>
		<?php 
		foreach ( $objscope_equiv_roles as $role_handle => $equiv_role_handle ) {
			$id = "{$role_handle}_role_objscope";
			$checked = ( scoper_get_option( $id, $sitewide, $customize_defaults ) ) ? "checked='checked'" : '';
			echo '<div class="agp-vspaced_input">';
			echo "<label for='$id'>";
			echo "<input name='$id' type='checkbox' id='$id' value='1' $checked /> ";
			
			if ( in_array( $role_handle, array( 'rs_post_reader', 'rs_post_author' ) ) )
				printf ( __('%1$s (normally equivalent to %2$s)', 'scoper'), $scoper->role_defs->get_display_name($role_handle), $scoper->role_defs->get_display_name($equiv_role_handle) );
			else
				echo $scoper->role_defs->get_display_name($role_handle);

			echo '</label></div>';
		}
		?>
		<span class="rs-subtext">
		<?php 
		if ( $ui->display_hints) {
			_e('By default, the above roles are not available for object-specific assignment because another role is usually equivalent. However, the distinctions may be useful if you propagate roles to sub-Pages, set Default Roles or customize RS Role Definitions.', 'scoper');
			echo '<br /><br />';
			_e('Note: Under the default configuration, the tabs labeled "Reader" in the Post/Page Edit Form actually assign the corresponding Private Reader role.', 'scoper');
		}	
		?>
		</span>
		</td></tr>
	
	<?php endif; // any objscope_equiv_roles options available in this section
	?>	
		
<?php endif; // any options accessable in this section
?>	


</table>
</div>

<?php endif; // any options accessable in this tab
?>	


<?php
// ------------------------- BEGIN Realm tab ---------------------------------
$tab = 'realm';

if ( ! empty( $ui->form_options[$tab] ) ) : ?>
<?php
echo "<div id='rs-realm' style='clear:both;margin:0' class='rs-options agp_js_hide $color_class'>";

if ( $ui->display_hints ) {
	echo '<div class="rs-optionhint">';
	_e("These <strong>optional</strong> settings allow advanced users to adjust Role Scoper's sphere of influence. For most installations, the default settings are fine.", 'scoper');
	echo '</div>';
}
?>

<table class="<?php echo($table_class);?>" id="rs-realm_table">

<?php
$section_alias = 'term_object_scopes';

if ( ! empty( $ui->form_options[$tab][$section_alias] ) ) : ?>


	<tr valign="top">
	<th scope="row"><?php 
									// --- TAXONOMY / OBJECT TYPE USAGE SECTION ---
	$section = 'taxonomy_usage';
	echo $ui->section_captions[$tab][$section];
	echo '<br />';
	?></th><td>
	<?php
		// note: update_wp_taxonomies gets special handling in submitee.php, doesn't need to be included in $ui->all_options array
		
		global $wp_taxonomies;
		global $scoper_default_options;
		
		_e('Specify which WordPress Taxonomies can have Restrictions and Roles:', 'scoper');
		echo '<br />';

		$registered = array();
		$registered['object'] = get_post_types( array() );
		$registered['term'] = get_taxonomies( array() );
		
		$public = array();
		$public['object'] = get_post_types( array( 'public' => true ) );
		$public['term'] = get_taxonomies( array( 'public' => true ) );

		$scopes = array( 'term', 'object');
		foreach ( $scopes as $scope ) {
			if ( 'object' == $scope ) {
?></td></tr>
<?php
				$section = 'post_type_usage';
				$option_name = 'use_post_types';
?>	<tr valign="top">
	<th scope="row">
<?php 				
				echo $ui->section_captions[$tab][$section];
?></th><td>
<?php 		
				_e('Specify which Post Types can have Restrictions and Roles:', 'scoper');
			} else { // end if loop iteration is for object scope
				$option_name = 'use_taxonomies';
			}
			
			if ( in_array( 'use_term_roles', $ui->form_options[$tab][$section_alias] ) ) {	// use_object_types follow option scope of use_term_roles
				$ui->all_options []= $option_name;
				
				if ( isset($scoper_default_options[$option_name]) ) {
					if ( ! $opt_vals = scoper_get_option( $option_name, $sitewide, $customize_defaults ) )
						$opt_vals = array();
							
					$opt_vals = array_merge($scoper_default_options[$option_name], $opt_vals);

					foreach ( $opt_vals as $key => $val ) {
						if ( ! $key )
							continue;
						
						$id = $option_name . '-' . $key;
						?>
						
						<?php // nav menu and link category are currently governed by "Term Scope" setting only, so just set a hidden enable here
						if ( in_array( $key, array( 'nav_menu', 'link_category' ) ) ) :?>
							<input name="<?php echo($id);?>" type="hidden" id="<?php echo($id);?>" value="1" />
						<?php else: ?>
							<div class="agp-vspaced_input">
							<label for="<?php echo($id);?>" title="<?php echo($key);?>">
							
							<input name="<?php echo($id);?>" type="checkbox" id="<?php echo($id);?>" value="1" <?php checked('1', $val);?> />
							
							<?php 
							if ( TERM_SCOPE_RS == $scope ) {
								if ( $tx = get_taxonomy( $key ) )
									$display_name = $tx->labels->name;
								else
									$display_name = '';
							} else {
								if ( $type_obj = get_post_type_object( $key ) )
									$display_name = $type_obj->labels->name;
								else
									$display_name = '';
							}
								
							if ( ! $display_name )
								$display_name = $key;
		
							if ( ! isset($public[$scope][$key]) ) {
								if ( isset($registered[$scope][$key]) ) {
									$display_name .= '<span class="rs-warning"> <big>*</big></span>';
									$any_private_types = true;
								} else {
									$display_name .= '<span class="rs-warning"> <big>**</big></span>';
									$any_unregistered_types = true;
								}
							}

							echo $display_name;
	
							echo ('</label></div>');
	
						endif;  // displaying checkbox UI
						
					} // end foreach src_otype
				} // endif default option isset
				
			} // endif displaying this option in form
			
			if ( MULTISITE )
				$link_open = $link_close = '';
			else {
				$link_open = "<a href='admin.php?page=rs-general_roles'>";
				$link_close = '</a>';
			}

			if ( ! empty($any_private_types) ) {
				$msg = ( 'term' == $scope ) ? __( '<big>*</big> = private type, filtering may not be valid', 'scoper' ) : __( '<big>*</big> = private taxonomy, filtering may not be valid', 'scoper' );
				echo '<div class="rs-warning">' . $msg . '</div><br />';
				$any_private_types = false;
			}
			
			if ( ! empty($any_unregistered_types) ) {
				$msg = __( '<big>**</big> = currently unregistered, corresponding plugin may be deactivated', 'scoper' );
				echo '<div class="rs-warning">' . $msg . '</div><br />';
				$any_unregistered_types = false;
			}
			
			if ( 'term' == $scope ) {
				if ( get_taxonomies( array( '_builtin' => false, 'public' => true ) ) ) {
					echo '<div>';
					printf( __( '<strong>NOTE:</strong> Non-Administrators need a %1$sTaxonomy-specific Role assignment%2$s to manage Custom Taxonomies selected here.', 'scoper' ), $link_open, $link_close );
					echo '</div>';
				}
			} else {
				if ( get_post_types( array( '_builtin' => false, 'public' => true ) ) ) {
					echo '<div>';
					printf( __( '<strong>NOTE:</strong> Non-Administrators need a %1$sType-specific Role assignment%2$s to manage Custom Post Types selected here.', 'scoper' ), $link_open, $link_close );
					echo '</div><br />';
				}
			}
			
		} // end foreach scope
		
		if ( ! defined( 'SCOPER_EARLY_INIT' ) ) {	
			echo '<div>';
			_e( '<strong>NOTE:</strong> Role Scoper is operating in late-initialization mode, for compatibility with plugins which register taxonomies or post types on the "init" hook without specifying early execution priority.  If other plugins internally query posts (or post editing capabilities) on the "init" action, those results will not be filtered.  In that event, find a way to register your taxonomies/post types earlier and add the following to wp-config.php:', 'scoper' );
			echo '<div id="rs-type-tx-help">';
			echo ( "<pre>&nbsp;&nbsp;define( 'SCOPER_EARLY_INIT', true );</pre>" );
			echo '</div>';
			echo '</div>';
		}
	?>
	</td>
	</tr>


	<tr valign="top">
	<th scope="row"><?php 
									// --- TERM SCOPE SECTION ---
	$section = 'term_scope';
	echo $ui->section_captions[$tab][$section];
	echo '<br /><span style="font-size: 0.9em; font-style: normal; font-weight: normal">(&nbsp;<a href="#scoper_notes">' . __('see notes', 'scoper') . '</a>&nbsp;)</span>';
	?></th><td>
	<?php
		// note: update_wp_taxonomies gets special handling in submitee.php, doesn't need to be included in $ui->all_options array
		
		global $wp_taxonomies;
	
		_e('Specify available Term Restrictions and Roles, for each object type and taxonomy:', 'scoper');
		echo '<br />&nbsp;&nbsp;&nbsp;&nbsp;';
		_e('<b>Note:</b> Taxonomy Usage must also be enabled above', 'scoper');
		echo '<br />';
	
		$scopes = array( 'term', 'object');
		foreach ( $scopes as $scope ) {
			if ( 'object' == $scope ) {
?></td></tr>
<?php
				$section = 'object_scope';
?>	<tr valign="top">
	<th scope="row">
<?php 
													// --- TERM / OBJECT SCOPE SECTION ---
				echo $ui->section_captions[$tab][$section];
?></th><td>
<?php 		
				_e('Specify whether Restrictions and Roles can be set for individual Objects:', 'scoper');
				echo '<br />&nbsp;&nbsp;&nbsp;&nbsp;';
				_e('<b>Note:</b> Post Type Usage must also be enabled above', 'scoper');
			} else // end if loop iteration is for object scope
				$section = 'term_scope';
			
			$option_name = "use_{$scope}_roles";
			
			if ( in_array( 'use_term_roles', $ui->form_options[$tab][$section_alias] ) ) {	// use_object_roles follow option scope of use_term_roles
			
				$ui->all_otype_options []= $option_name;
				
				if ( isset($ui->def_otype_options[$option_name]) ) {
					if ( ! $opt_vals = scoper_get_option( $option_name, $sitewide, $customize_defaults ) )
						$opt_vals = array();
							
					if ( 'use_term_roles' == $option_name ) {
						foreach( array_keys( $ui->def_otype_options[$option_name] ) as $src_otype ) {
							if ( isset( $opt_vals[$src_otype] ) )
								$opt_vals[$src_otype] = array_merge( $ui->def_otype_options[$option_name][$src_otype], $opt_vals[$src_otype] );
							else
								$opt_vals[$src_otype] = $ui->def_otype_options[$option_name][$src_otype];
						}
					} else 
						$opt_vals = array_merge($ui->def_otype_options[$option_name], $opt_vals);

					foreach ( $opt_vals as $src_otype => $val ) {
						if ( TERM_SCOPE_RS == $scope ) {
							echo '<div style="margin-bottom: 2em">';
							foreach( array_keys($opt_vals[$src_otype]) as $taxonomy ) {
								$id = str_replace( ':', '_', $option_name . '-' . $src_otype . '-' . $taxonomy );
								?>
								<div class="agp-vspaced_input">
								<label for="<?php echo($id);?>" title="<?php echo($taxonomy);?>">
								<input name="<?php echo($id);?>" type="checkbox" id="<?php echo($id);?>" value="1" <?php checked('1', $val[$taxonomy]);?> />
								<?php 
								$tx_display = $scoper->taxonomies->member_property( $taxonomy, 'labels', 'name' );

								if ( ! $tx_display )
									$tx_display = $taxonomy;

								$display_name_plural = $scoper_admin->interpret_src_otype($src_otype);

								//printf( _ x('%1$s (for %2$s)', 'Category (for Posts)', 'scoper'), $tx_display, $display_name_plural );
								printf( __('%1$s (for %2$s)', 'scoper'), $tx_display, $display_name_plural );
								
								if ( ! $scoper->taxonomies->member_property($taxonomy, 'requires_term') ) {
									echo '* ';
									$any_loose_taxonomy = true;
								}
									
								echo ('</label></div>');
							}
							echo '</div>';

						} else {
							$id = str_replace( ':', '_', $option_name . '-' . $src_otype );
							?>
							<div class="agp-vspaced_input">
							<label for="<?php echo($id);?>" title="<?php echo($src_otype);?>">
							<input name="<?php echo($id);?>" type="checkbox" id="<?php echo($id);?>" value="1" <?php checked('1', $val);?> />
							<?php 
							echo $scoper_admin->interpret_src_otype($src_otype);
							echo ('</label></div>');
						}
					} // end foreach src_otype
				} // endif default option isset
				
			} // endif displaying this option in form
			
			
			if ( 'term' == $scope ) {
				if ( ! empty($any_loose_taxonomy) )
					echo "<span class='rs-gray'>" . __( '* = Role Assignment only (no Restrictions)', 'scoper' ) . '</span>';
			}
			
		} // end foreach scope
	?>
	</td>
	</tr>
<?php endif; // any options accessable in this section


$section = 'access_types';
if ( ! empty( $ui->form_options[$tab][$section] ) ) : ?>
	<tr valign="top">
	<th scope="row"><?php 
									// --- ACCESS TYPES SECTION ---
	echo $ui->section_captions[$tab][$section]; ?></th>
	<td>
	<?php
		// note: disabled_access_types option gets special handling in submitee.php, doesn't need to be included in $ui->all_options array
	
		_e('Apply Roles and Restrictions for:', 'scoper');
		echo '<br />';
		$topic = "access_types";
		$opt_vals = scoper_get_option("disabled_{$topic}", $sitewide, $customize_defaults);
		
		$all = implode(',', $scoper->access_types->get_all_keys() );
		echo "<input type='hidden' name='all_access_types' value='$all' />";
		
		foreach ( $scoper->access_types->get_all() as $access_name => $access_type) {
			$id = $topic . '-' . $access_name;
			$val = empty( $opt_vals[$access_name] );
	?>
	<div class="agp-vspaced_input">
	<label for="<?php echo($id);?>">
	<input name="<?php echo($id);?>[]" type="checkbox" id="<?php echo($id);?>" value="<?php echo($access_name);?>" <?php checked('1', $val);?> />
	<?php
			if ( 'front' == $access_name )
				_e('Viewing content (front-end)', 'scoper');
			elseif ( 'admin' == $access_name )
				_e('Editing and administering content (admin)', 'scoper');
			else
				echo($access_type->labels->name);
			echo('</label></div>');
		} // end foreach access types
	?>
	<br />
	</td></tr>
<?php endif; // any options accessable in this section
?>

<?php
	// NOTE: Access Types section (for disabling data sources / otypes individually) was removed due to complication with hardway filtering.
	// For last source code, see 1.0.0-rc8
?>

</table>

<?php
echo '<h4 style="margin-bottom:0.1em"><a name="scoper_notes"></a>' . __("Notes", 'scoper') . ':</h4><ul class="rs-notes">';

echo '<li>';
_e('The &quot;Post Tag&quot; taxonomy cannot be used to define restrictions because tags are not mandatory. For most installations, categories are a better mechanism to define roles. Note that <strong>Role Scoper does not filter tag storage</strong> based on the editing user\'s access.  As with any other custom-defined taxonomy, use this option at your own discretion.', 'scoper');
echo '</li>';

echo '</ul>';
echo '</div>';

endif; // any options accessable in this tab

// ------------------------- END Realms tab ---------------------------------




if ( ! empty( $ui->form_options['rs_role_definitions'] ) ) {
	// RS Role Definitions Tab
	include( dirname(__FILE__).'/role_definition.php');
	scoper_display_rs_roledefs( array( 'bgcolor_class' => $color_class, 'sitewide' => $sitewide, 'customize_defaults' => $customize_defaults ) );

	// WP Role Definitions Tab
	include( dirname(__FILE__).'/role_definition_wp.php');
	scoper_display_wp_roledefs( array( 'bgcolor_class' => $color_class ) );
}



// ------------------------- BEGIN Option Scope tab ---------------------------------
$tab = 'optscope';

if ( $sitewide ) : ?>
<?php
echo "<div id='rs-optscope' style='clear:both;margin:0' class='rs-options agp_js_hide $color_class'>";

if ( $ui->display_hints ) {
	echo '<div class="rs-optionhint">';
	_e("Specify which Role Scoper Options should be applied site-wide.", 'scoper');
	echo '</div><br />';
}

echo '<ul>';
$all_movable_options = array();

$option_scope_stamp = __( 'network-wide control of "%s"', 'scoper' );

foreach ( $available_form_options as $tab_name => $sections ) {
	echo '<li>';

	$explanatory_caption = __( 'Selected options will be controlled network-wide via <strong>Super Admin > Role Options</strong>; unselected options can be set per-site via <strong>Roles > Options</strong>', 'scoper' );

	if ( isset( $ui->tab_captions[$tab_name] ) )
		$tab_caption = $ui->tab_captions[$tab_name];
	else
		$tab_caption = $tab_name;

	echo '<div style="margin:1em 0 1em 0">';
	if ( $ui->display_hints )
		printf( __( '<span class="rs-h3text rs-blue">%1$s</span> (%2$s)', 'scoper' ), $tab_caption, $explanatory_caption );
		//printf( _ x( '<span class="rs-h3text rs-blue">%1$s</span> (%2$s)', 'option_tabname (explanatory note)', 'scoper' ), $tab_caption, $explanatory_caption );
	else
		echo $tab_caption;
	echo '</div>';
	
	echo '<ul style="margin-left:2em">';
		
	foreach ( $sections as $section_name => $option_names ) {
		if ( empty( $sections[$section_name] ) )
			continue;
		
		echo '<li><strong>';
		
		if ( isset( $ui->section_captions[$tab_name][$section_name] ) )
			echo $ui->section_captions[$tab_name][$section_name];
		else
			_e( $section_name );
		
		echo '</strong><ul style="margin-left:2em">';
			
		foreach ( $option_names as $option_name ) {
			if ( $option_name && $ui->option_captions[$option_name] ) {
				$all_movable_options []= $option_name;
				echo '<li>';
				
				$disabled = ( in_array( $option_name, array( 'file_filtering', 'mu_sitewide_groups' ) ) ) ? "disabled='disabled'" : '';
				
				$id = "{$option_name}_sitewide";
				$val = isset( $scoper_options_sitewide[$option_name] );
				echo "<label for='$id'>";
				echo "<input name='rs_options_sitewide[]' type='checkbox' id='$id' value='$option_name' $disabled ";
				checked('1', $val);
				echo " />";

				printf( $option_scope_stamp, $ui->option_captions[$option_name] );
					
				echo '</label></li>';
			}
		}
		
		echo '</ul>';
	}
	echo '</ul><br /><hr />';
}
echo '</ul>';

echo '</div>';

$all_movable_options = implode(',', $all_movable_options);
echo "<input type='hidden' name='rs_all_movable_options' value='$all_movable_options' />";

endif; // any options accessable in this tab
// ------------------------- END Option Scope tab ---------------------------------



// this was required for Access Types section, which was removed
//$all = implode(',', $all_otypes);
//echo "<input type='hidden' name='all_object_types' value='$all' />";

$ui->all_options = implode(',', $ui->all_options);
$ui->all_otype_options = implode(',', array_unique( $ui->all_otype_options ) );
echo "<input type='hidden' name='all_options' value='$ui->all_options' />";
echo "<input type='hidden' name='all_otype_options' value='$ui->all_otype_options' />";

echo "<input type='hidden' name='rs_submission_topic' value='options' />";
?>
<p class="submit" style="border:none;float:right">
<input type="submit" name="rs_submit" class="button-primary" value="<?php _e('Update &raquo;', 'scoper');?>" />
</p>

<?php
$msg = __( "All settings in this form (including those on undisplayed tabs) will be reset to DEFAULTS.  Are you sure?", 'scoper' );
$js_call = "javascript:if (confirm('$msg')) {return true;} else {return false;}";
?>
<p class="submit" style="border:none;float:left">

<input type="submit" name="rs_defaults" value="<?php _e('Revert to Defaults', 'scoper') ?>" onclick="<?php echo $js_call;?>" />
</p>
</form>
<p style='clear:both'>
</p>
</div>

<?php
} // end function


function scoper_src_name_from_src_otype($src_otype) {
	if ( $arr_src_otype = explode(':', $src_otype) )
		return $arr_src_otype[0];
}

function scoper_display_otypes_or_source_name($src_name) {
	global $scoper;
	
	if ( $object_types = $scoper->data_sources->member_property($src_name, 'object_types') ) {
		$display_names = array();
		foreach ( $object_types as $otype)
			$display_names[] = $otype->labels->name;
		$display = implode(', ', $display_names);
	} else {
		$item_label = $scoper->data_sources->member_property( $src_name, 'labels', 'name' );
		$display = sprintf(__("%s data source", 'scoper'), $item_label);
	}
	
	return $display;
}
?>