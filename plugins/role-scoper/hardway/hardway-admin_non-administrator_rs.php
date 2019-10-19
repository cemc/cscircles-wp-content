<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

if ( 'nav-menus.php' == $GLOBALS['pagenow'] ) {	// nav-menus.php only needs admin_referer check.  TODO: split this file
	add_action( 'check_admin_referer', array('ScoperAdminHardway_Ltd', 'act_check_admin_referer') );
} else {
	//if ( false === strpos( $_SERVER['REQUEST_URI'], 'upload.php' ) )	// TODO: internal criteria to prevent application of flt_last_resort when scoped user object is not fully loaded
		ScoperAdminHardway_Ltd::add_filters();
}

class ScoperAdminHardway_Ltd {
	public static function add_filters() {
		add_action( 'check_admin_referer', array('ScoperAdminHardway_Ltd', 'act_check_admin_referer') );
	
		add_action( 'check_ajax_referer', array('ScoperAdminHardway_Ltd', 'act_check_ajax_referer') );
		
		// TODO: better handling of low-level AJAX filtering
		// URIs ending in specified filename will not be subjected to low-level query filtering
		$nomess_uris = apply_filters( 'scoper_skip_lastresort_filter_uris', array( 'categories.php', 'themes.php', 'plugins.php', 'profile.php', 'link.php' ) );
		
		if ( empty( $_POST['ps'] ) )	// need to filter Find Posts query in Media Library
			$nomess_uris = array_merge($nomess_uris, array('admin-ajax.php'));
		
		if ( ! in_array( $GLOBALS['pagenow'], $nomess_uris ) && ! in_array( $GLOBALS['plugin_page_cr'], $nomess_uris ) )
		//|| ( defined('DOING_AJAX') && DOING_AJAX && defined('SCOPER_FILTERED_AJAX_ACTIONS') && ! empty($_REQUEST['action']) && in_array( $_REQUEST['action'], explode( ',', str_replace(' ', '', SCOPER_FILTERED_AJAX_ACTIONS ) ) ) ) )
			add_filter('query', array('ScoperAdminHardway_Ltd', 'flt_last_resort_query') );
	}

	// next-best way to handle any permission checks for non-Ajax operations which can't be done via has_cap filter
	public static function act_check_admin_referer( $referer_name ) {
		
		if ( ! empty($_POST['tag_ID']) && ( 'update-tag_' . $_POST['tag_ID'] == $referer_name ) ) {
			// filter category parent selection for Category editing
			if ( ! isset( $_POST['tag_ID'] ) )
				return;
			
			$taxonomy = $_POST['taxonomy'];
			
			if ( ! $tx = get_taxonomy($taxonomy) )
				return;
				
			if ( ! $tx->hierarchical )
				return;
			
			$stored_term = get_term_by( 'id', $_POST['tag_ID'], $taxonomy );

			$selected_parent = $_POST['parent'];
			
			if ( -1 == $selected_parent )
				$selected_parent = 0;
			
			if ( $stored_term->parent != $selected_parent ) {
				global $scoper;
				
				if ( $tx_obj = get_taxonomy( $taxonomy ) ) {
					if ( $selected_parent ) {
						$user_terms = $scoper->qualify_terms( $tx_obj->cap->manage_terms, $taxonomy );
						$permit = in_array( $selected_parent, $user_terms );
					} else {
						$permit = cr_user_can( $tx_obj->cap->manage_terms, 0, 0, array( 'skip_id_generation' => true, 'skip_any_term_check' => true ) );
					}
				}
				
				if ( ! $permit )
					wp_die( __('You do not have permission to select that Category Parent', 'scoper') );
			}

		} elseif ( 'update-nav_menu' == $referer_name ) {
			$tx = get_taxonomy( 'nav_menu' );
			
			$use_term_roles = scoper_get_otype_option( 'use_term_roles', 'post', 'nav_menu' );

			if ( empty ( $GLOBALS['current_user']->allcaps['edit_theme_options'] ) || ! empty( $use_term_roles['nav_menu'] ) ) {
				if ( ! cr_user_can( $tx->cap->manage_terms, (int) $_REQUEST['menu'], 0, array( 'skip_id_generation' => true, 'skip_any_term_check' => true ) ) ) {
					if ( $_REQUEST['menu'] )
						wp_die( __('You do not have permission to update that Navigation Menu', 'scoper') );
					else
						wp_die( __('You do not have permission to create new Navigation Menus', 'scoper') );
				}
			}
		
		} elseif ( false !== strpos( $referer_name, 'delete-menu_item_' ) ) {
			if ( scoper_get_option( 'admin_nav_menu_filter_items' ) ) {
				$menu_item_id = substr( $referer_name, strlen( 'delete-menu_item_' ) );
				require_once( SCOPER_ABSPATH . '/admin/filters-admin-nav_menus_rs.php' );
				_rs_mnt_modify_nav_menu_item( $menu_item_id, 'delete' );
			}
		} elseif ( $referer_name == 'move-menu_item' ) {
			if ( scoper_get_option( 'admin_nav_menu_filter_items' ) ) {
				require_once( SCOPER_ABSPATH . '/admin/filters-admin-nav_menus_rs.php' );
				_rs_mnt_modify_nav_menu_item( (int) $_REQUEST['menu-item'], 'move' );
			}
		} elseif ( 'add-bookmark' == $referer_name ) {
			require_once( dirname(__FILE__).'/hardway-admin-links_rs.php' );
			$link_category = ! empty( $_POST['link_category'] ) ? $_POST['link_category'] : array();
			$_POST['link_category'] = scoper_flt_newlink_category( $link_category );

		} elseif ( 0 === strpos( $referer_name, 'update-bookmark_' ) ) {
			require_once( dirname(__FILE__).'/hardway-admin-links_rs.php' );
			$link_category = ! empty( $_POST['link_category'] ) ? $_POST['link_category'] : array();
			$_POST['link_category'] = scoper_flt_link_category( $link_category );
		}
	}
	
	
	// next-best way to handle permission checks for Ajax operations which can't be done via has_cap filter
	public static function act_check_ajax_referer( $referer_name ) {
		if ( 'add-tag' == $referer_name ) {			
			if ( $tx_obj = get_taxonomy( $_POST['taxonomy'] ) )
				$cap_name = $tx_obj->cap->manage_terms;

			if ( empty($cap_name) )
				$cap_name = 'manage_categories';

			// Concern here is for addition of top level terms.  Subcat addition attempts will already be filtered by has_cap filter.
			if ( ( empty( $_POST['parent'] ) || $_POST['parent'] < 0 ) && ! cr_user_can( $cap_name, BLOG_SCOPE_RS ) )
				die('-1');	
		
		} elseif ( 'add-link-category' == $referer_name ) {		
			if ( ! cr_user_can( 'manage_categories', BLOG_SCOPE_RS ) )
				die('-1');	
		
		}
	}
	
	public static function flt_last_resort_query($query) {
		static $in_process = false;
		
		if ( $in_process )
			return $query;
			
		$in_process = true;
		$query = self::_flt_last_resort_query($query);
		$in_process = false;
		return $query;
	}
	
	// low-level filtering of otherwise unhookable queries
	//
	// Todo: review all queries for version-specificity; apply regular expressions to make it less brittle
	static function _flt_last_resort_query($query) {
		// no recursion
		if ( scoper_querying_db() || $GLOBALS['cap_interceptor']->in_process )
			return $query;

		global $wpdb, $pagenow, $scoper;

		$posts = $wpdb->posts;

		// Search on query portions to make this as forward-compatible as possible.
		// Important to include " FROM table WHERE " as a strpos requirement because scoped queries (which should not be further altered here) will insert a JOIN clause
		// strpos search for "ELECT " rather than "SELECT" so we don't have to distinguish 0 from false
		
		// wp_count_posts() :
		// SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s
		$matches = array();
		if ( strpos($query, "ELECT post_status, COUNT( * ) AS num_posts ") && preg_match("/FROM\s*{$posts}\s*WHERE post_type\s*=\s*'([^ ]+)'/", $query, $matches) ) {
			$_post_type = ( ! empty( $matches[1] ) ) ? $matches[1]	: cr_find_post_type();

			if ( $_post_type ) {
				global $current_user;

				foreach( get_post_stati( array( 'private' => true ) ) as $_status )
					$query = str_replace( "AND (post_status != '$_status' OR ( post_author = '{$current_user->ID}' AND post_status = '$_status' ))", '', $query);
				
				$query = str_replace( "post_status", "$posts.post_status", $query);
				
				$query = apply_filters( 'objects_request_rs', $query, 'post', $_post_type, array( 'objrole_revisions_clause' => true ) );
				
				// as of WP 3.0.1, additional queries triggered by objects_request filter breaks all subsequent filters which would have operated on this query
				if ( defined( 'RVY_VERSION' ) ) {
					if ( class_exists( 'RevisionaryAdminHardway_Ltd' ) )
						$query = RevisionaryAdminHardway_Ltd::flt_last_resort_query( $query );

					if ( class_exists( 'RevisionaryAdminHardway' ) )
						$query = RevisionaryAdminHardway::flt_include_pending_revisions( $query );
				}
			}

			return $query;
		}
		
		// parent_dropdown() :
		// SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'page' ORDER BY menu_order
		if ( 'admin.php' == $pagenow ) {
			if ( strpos ($query, "ELECT ID, post_parent, post_title") && strpos($query, "FROM $posts WHERE post_parent =") && function_exists('parent_dropdown') ) {
				$page_temp = '';
				$object_id = (int) $scoper->data_sources->detect( 'id', 'post' );
				if ( $object_id )
					$page_temp = get_post( $object_id );

				if ( empty($page_temp) || ! isset($page_temp->post_parent) || $page_temp->post_parent ) {
					require_once( SCOPER_ABSPATH . '/hardway/hardway-parent-legacy_rs.php');
					$output = ScoperHardwayParentLegacy::dropdown_pages();
					echo $output;
				}
				$query = "SELECT ID, post_parent FROM $posts WHERE 1=2";
				
				return $query;
			}
		}
		
		// Media Library - unattached
		//
		// WP_MediaListTable::get_views() :
		// SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1
		if ( strpos($query, "post_type = 'attachment'") && strpos($query, "post_parent < 1") && strpos($query, '* FROM') ) {
			if ( $where_pos = strpos($query, 'WHERE ') ) {
				// optionally hide other users' unattached uploads, but not from blog-wide Editors
				if ( ( ! scoper_get_option( 'admin_others_unattached_files' ) ) && ! $scoper->user_can_edit_blogwide( 'post', '', array( 'require_others_cap' => true, 'status' => 'publish' ) ) ) {
					global $current_user;
					
					$author_clause = "AND $wpdb->posts.post_author = '{$current_user->ID}'";

					$query = str_replace( "post_type = 'attachment'", "post_type = 'attachment' $author_clause", $query);

					return $query;
				}
			}
		}
		
		// wp_count_attachments() :
		//SELECT post_mime_type, COUNT( * ) AS num_posts FROM wp_trunk_posts WHERE post_type = 'attachment' GROUP BY post_mime_type
		if ( strpos($query, "post_type = 'attachment'") && ( 0 === strpos($query, "SELECT " ) ) ) {
			if ( $where_pos = strpos($query, 'WHERE ') ) {

				if ( ! defined( 'SCOPER_ALL_UPLOADS_EDITABLE' ) ) {  // note: this constant actually just prevents Media Library filtering, falling back to WP Roles for attachment editability and leaving uneditable uploads viewable in Library
					static $att_sanity_count = 0;
					
					if ( $att_sanity_count > 5 )  // TODO: why does this apply filtering to 300+ queries on at least one MS installation?
						return $query;
					
					$att_sanity_count++;
					
					$admin_others_attached = scoper_get_option( 'admin_others_attached_files' );
					$admin_others_unattached = scoper_get_option( 'admin_others_unattached_files' );
					
					if ( ( ! $admin_others_attached ) || ! $admin_others_unattached )
						$can_edit_others_blogwide = $scoper->user_can_edit_blogwide( 'post', '', array( 'require_others_cap' => true, 'status' => 'publish' ) );
	
					global $wpdb, $current_user;
					
					// optionally hide other users' unattached uploads, but not from blog-wide Editors
					if ( $admin_others_unattached || $can_edit_others_blogwide )
						$author_clause = '';
					else
						$author_clause = "AND $wpdb->posts.post_author = '{$current_user->ID}'";
					
					if ( ! defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') || ! SCOPER_BLOCK_UNATTACHED_UPLOADS )
						$unattached_clause = "( $wpdb->posts.post_parent = 0 $author_clause ) OR";
					else
						$unattached_clause = '';
	
					$attached_clause = ( $admin_others_attached || $can_edit_others_blogwide ) ? '' : "AND $wpdb->posts.post_author = '{$current_user->ID}'";
	
					$parent_query = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE 1=1";

					$parent_query = apply_filters('objects_request_rs', $parent_query, 'post' );

					$where_insert = "( $unattached_clause ( $wpdb->posts.post_parent IN ($parent_query) $attached_clause ) ) AND ";
					
					$query = substr( $query, 0, $where_pos + strlen('WHERE ') ) . $where_insert . substr($query, $where_pos + strlen('WHERE ') );
				}
				
				return $query;
			}
		}
		
		// admin-ajax.php 'find_posts' :
		// SELECT ID, post_title, post_status, post_date FROM $wpdb->posts WHERE post_type = '$what' AND post_status IN ('draft', 'publish') AND ($search) ORDER BY post_date_gmt DESC LIMIT 50
		if ( strpos( $query, "ELECT ID, post_title, post_status, post_date FROM" ) ) {
			if ( ! empty( $_POST['post_type'] ) )
				$query = apply_filters('objects_request_rs', $query, 'post', $_POST['post_type'] );	
		}
		
		return $query;
	} // end function flt_last_resort_query
} // end class
?>