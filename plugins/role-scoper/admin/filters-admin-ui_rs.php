<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/admin_lib_rs.php' );

/**
 * ScoperAdminFiltersUI PHP class for the WordPress plugin Role Scoper
 * filters-admin-ui_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */
class ScoperAdminFiltersUI
{
	var $scoper;
	var $scoper_admin;
	
	function __construct() {
		global $pagenow, $plugin_page_cr;

		$this->scoper =& $GLOBALS['scoper'];
		$this->scoper_admin =& $GLOBALS['scoper_admin'];

		$item_edit_scripts = apply_filters( 'item_edit_scripts_rs', array('post-new.php', 'post.php', 'page.php', 'page-new.php', 'edit-tags.php') );
		$item_edit_scripts []= 'admin-ajax.php';

		if ( in_array( $pagenow, $item_edit_scripts ) || in_array( $plugin_page_cr, $item_edit_scripts ) ) {
			require_once( dirname(__FILE__).'/filters-admin-ui-item_rs.php' );
			global $scoper_admin_filters_item_ui;
			$scoper_admin_filters_item_ui = new ScoperAdminFiltersItemUI();
		}

		add_action('admin_head', array(&$this, 'ui_hide_admin_divs') );

		if ( is_user_administrator_rs() || ( 0 === strpos( $plugin_page_cr, 'rs-' ) ) )
			add_action('in_admin_footer', array(&$this, 'ui_admin_footer') );

		if ( GROUP_ROLES_RS ) {
			add_action('show_user_profile', array(&$this, 'ui_user_groups'), 2);
			add_action('edit_user_profile', array(&$this, 'ui_user_groups'), 2);
			add_action('edit_group_profile_rs', array(&$this, 'ui_group_roles'), 10);
		}
		
		add_action('show_user_profile', array(&$this, 'ui_user_roles'), 2);
		add_action('edit_user_profile', array(&$this, 'ui_user_roles'), 2);
		
		// possible TODO: equivalent message display for other role management plugins
		if ( ( 'role-management.php' == $plugin_page_cr ) && empty( $_POST ) )
			add_filter( 'capabilities_list', array(&$this, 'flt_capabilities_list') );	// capabilities_list is a Role Manager hook

		if ( 'nav-menus.php' == $pagenow ) {
			add_action( 'admin_head', array(&$this, 'ui_hide_add_menu') );
		}
		
		add_action( 'admin_head', array(&$this, 'ui_hide_appearance_submenus') );
			
	} // end class constructor
	
	function ui_hide_admin_divs() {
		if ( ! in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) )
			return;

		if ( ! $object_type = cr_find_post_type() )
			return;

		// For this data source, is there any html content to hide from non-administrators?
		$option_type = ( 'page' == $object_type ) ? 'page' : 'post';
		$css_ids = scoper_get_otype_option('admin_css_ids', 'post', $option_type);
		$css_ids = str_replace(' ', '', $css_ids);
		$css_ids = str_replace(',', ';', $css_ids);
		$css_ids = explode(';', $css_ids);	// option storage is as semicolon-delimited string
	
		if ( empty($css_ids) )
			return;

		$object_id = scoper_get_object_id( 'post' );

		$can_edit_blogwide = $this->scoper->user_can_edit_blogwide('post', $object_type);
		
		$blogwide_requirement = scoper_get_option('hide_non_editor_admin_divs');
		
		if ( 'admin_option' == $blogwide_requirement )
			$blogwide_requirement_met = is_option_administrator_rs();
		
		elseif ( 'admin_user' == $blogwide_requirement )
			$blogwide_requirement_met = is_user_administrator_rs();
			
		elseif ( 'admin_content' == $blogwide_requirement )
			$blogwide_requirement_met = is_content_administrator_rs();
			
		elseif ( 'editor' == $blogwide_requirement )
			$blogwide_requirement_met = $this->scoper->user_can_edit_blogwide('post', $object_type, array( 'status' => 'publish', 'require_others_cap' => true ) );
		
		elseif ( 'author' == $blogwide_requirement )
			$blogwide_requirement_met = $this->scoper->user_can_edit_blogwide('post', $object_type, array( 'status' => 'publish' ) );
		
		elseif ( $blogwide_requirement )
			$blogwide_requirement_met = $can_edit_blogwide;
		else
			$blogwide_requirement_met = true;

		if ( $can_edit_blogwide && $blogwide_requirement_met ) {
			// don't hide anything if a user with sufficient blog-wide role is creating a new object
			if ( ! $object_id )
				return;

			if ( ! $object = $this->scoper->data_sources->get_object('post', $object_id) )
				return;

			if ( empty($object->post_date) ) // don't prevent the full editing of new posts/pages
				return;

			// don't hide anything if a user with sufficient blog-wide role is editing their own object
			/*
			global $current_user;
			if ( empty($object->post_author) || ( $object->post_author == $current_user->ID) )
				return;
			*/
		}

		
		if ( ( $blogwide_requirement && ! $blogwide_requirement_met ) || ! $this->scoper_admin->user_can_admin_object('post', $object_type, $object_id, '', '', true) ) {
			echo( "\n<style type='text/css'>\n<!--\n" );
			
			$removeable_metaboxes = apply_filters( 'scoper_removeable_metaboxes', array( 'categorydiv', 'tagsdiv-post_tag', 'postcustom', 'pagecustomdiv', 'authordiv', 'pageauthordiv', 'trackbacksdiv', 'revisionsdiv', 'pending_revisions_div', 'future_revisionsdiv' ) );
			
			foreach ( $css_ids as $id ) {
				if ( in_array( $id, $removeable_metaboxes ) ) {
					// thanks to piemanek for tip on using remove_meta_box for any core admin div
					remove_meta_box($id, $object_type, 'normal');
					remove_meta_box($id, $object_type, 'advanced');
				} else {
					// hide via CSS if the element is not a removeable metabox
					echo "#$id { display: none !important; }\n";  // this line adapted from Clutter Free plugin by Mark Jaquith
				}
			}
			
			echo "-->\n</style>\n";
		}
		
	}

	// Don't show add new menu UI if user can't edit nav menus blogwide.  Otherwise they could create a new menu but not edit it later.
	function ui_hide_add_menu() {
		$tx_obj = get_taxonomy( 'nav_menu' );

		if ( ! empty ( $GLOBALS['current_user']->allcaps['edit_theme_options'] ) ) {
			$use_term_roles = scoper_get_otype_option( 'use_term_roles', 'post' );
			if ( empty( $use_term_roles['nav_menu'] ) )
				return;
		}

		if ( cr_user_can( $tx_obj->cap->manage_terms, BLOG_SCOPE_RS ) )
			return;
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('.menu-add-new').hide();
	
	if ( $('.add-new-menu-action').length )
		$('.add-new-menu-action').hide();
	else
		$('.add-edit-menu-action').hide();
		
	$('#nav-menu-theme-locations').hide();
	
	$('.menu-theme-locations').hide();
	$('.nav-tab-wrapper a[href*="action=locations"]').hide();
});
/* ]]> */
</script>
<?php
	} // end function 
	

	// In case we are fudging the edit_theme_options cap for nav menu management, keep other Appearance menu items hidden
	function ui_hide_appearance_submenus() {
		global $current_user;
		
		if ( is_content_administrator_rs() || ! empty( $current_user->allcaps['edit_theme_options'] ) )
			return;
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#menu-appearance a[href!="nav-menus.php"]').hide();
	$('#menu-appearance a[class*="wp-has-submenu"]').show();
	
	<?php if ( 'nav-menus.php' == $GLOBALS['pagenow'] ) :?>
	$('#nav-menu-header .delete-action a').hide();
	<?php endif; ?>
});
/* ]]> */
</script>
<?php
	} // end function 
	
	
	function ui_admin_footer() {
		if ( (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'msie 7') ) )
			echo '<span style="float:right; margin-left: 2em"><a href="http://agapetry.net/">' . __('Role Scoper', 'scoper') . '</a> ' . SCOPER_VERSION . ' | ' . '<a href="http://agapetry.net/forum/">' . __('Support Forum', 'scoper') . '</a>&nbsp;</span>';
	}

	function ui_user_groups() {
		if ( ! is_user_administrator_rs() && ! scoper_get_option( 'display_user_profile_groups' ) )
			return;
			
		include_once( dirname(__FILE__).'/profile_ui_rs.php');
		ScoperProfileUI::display_ui_user_groups();
	} // end function act_edit_user_groups
	

	function ui_group_roles($group_id) {
		if ( ! $group_id )
			return;

		include_once( dirname(__FILE__).'/profile_ui_rs.php');
		ScoperProfileUI::display_ui_group_roles($group_id);
	}
	
	function ui_user_roles() {
		if ( ! is_user_administrator_rs() && ! scoper_get_option( 'display_user_profile_roles' ) )
			return;
		
		global $profileuser, $current_rs_user;

		$profile_user_rs = ( $profileuser->ID == $current_rs_user->ID ) ? $current_rs_user : new WP_Scoped_User($profileuser->ID);
		
		include_once( dirname(__FILE__).'/profile_ui_rs.php');
		ScoperProfileUI::display_ui_user_roles($profile_user_rs);
	}
	
	function flt_capabilities_list($unused) {
		echo '<p>';
		_e("<strong>Note:</strong> Role Manager settings determine each user's default blog-wide capabilities. Since the Role Scoper plugin is also enabled, <strong>Term-specific or Object-specific Role Assignments</strong> may increase or decrease a user's actual capabilities.", 'scoper');
		echo '</p>';

		return $unused;
	}
}

?>