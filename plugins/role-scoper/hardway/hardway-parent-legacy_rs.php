<?php

require_once( 'hardway-parent_rs.php' );
require_once( SCOPER_ABSPATH . '/admin/admin_lib-bulk-parent_rs.php' );

// filter parent_dropdown() function.  As of WP 3.0.1, it executes an otherwise unfilterable direct db query
class ScoperHardwayParentLegacy {

	// TODO: move this to legacy file (called by Role Scoping for NGG)
	function dropdown_pages($object_id = '', $stored_parent_id = '') {
		global $scoper, $wpdb;

		// buffer titles in case they are filtered on get_pages hook
		$titles = ScoperAdminBulkParent::get_page_titles();
		
		if ( ! is_numeric($object_id) ) {
			global $post_ID;
			
			if ( empty($post_ID) )
				$object_id = $scoper->data_sources->detect('id', 'post', 0, 'post');
			else
				$object_id = $post_ID;
		}
		
		if ( $object_id && ! is_numeric($stored_parent_id) )
			$stored_parent_id = $scoper->data_sources->detect('parent', 'post', $object_id);
		
		// make sure the currently stored parent page remains in dropdown regardless of current user roles
		if ( $stored_parent_id ) {
			$preserve_or_clause = " $wpdb->posts.ID = '$stored_parent_id' ";
			$args['preserve_or_clause'] = array();
			foreach (array_keys( $scoper->data_sources->member_property('post', 'statuses') ) as $status_name )
				$args['preserve_or_clause'][$status_name] = $preserve_or_clause;
		}
		
		// alternate_caps is a 2D array because objects_request / objects_where filter supports multiple alternate sets of qualifying caps
		$args['force_reqd_caps']['page'] = array();
		foreach (array_keys( $scoper->data_sources->member_property('post', 'statuses') ) as $status_name )
			$args['force_reqd_caps']['page'][$status_name] = array('edit_others_pages');
			
		$args['alternate_reqd_caps'][0] = array('create_child_pages');
		
		$all_pages_by_id = array();
		if ( $results = scoper_get_results( "SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_type = 'page'" ) )
			foreach ( $results as $row )
				$all_pages_by_id[$row->ID] = $row;

		$object_type = awp_post_type_from_uri();
				
		// Editable / associable draft and pending pages will be included in Page Parent dropdown in Edit Forms, but not elsewhere
		if ( is_admin() && ( 'page' != $object_type ) )
			$status_clause = "AND $wpdb->posts.post_status IN ('publish', 'private')";
		else
			$status_clause = "AND $wpdb->posts.post_status IN ('publish', 'private', 'pending', 'draft')";

		$qry_parents = "SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_type = 'page' $status_clause ORDER BY menu_order";
		
		$qry_parents = apply_filters('objects_request_rs', $qry_parents, 'post', 'page', $args);

		$filtered_pages_by_id = array();
		if ( $results = scoper_get_results($qry_parents) )
			foreach ( $results as $row )
				$filtered_pages_by_id [$row->ID] = $row;
			
		$hidden_pages_by_id = array_diff_key( $all_pages_by_id, $filtered_pages_by_id );

		// temporarily add in the hidden parents so we can order the visible pages by hierarchy
		$pages = ScoperAdminBulkParent::add_missing_parents($filtered_pages_by_id, $hidden_pages_by_id, 'post_parent');
		
		// convert keys from post ID to title+ID so we can alpha sort them
		$args['pages'] = array();
		foreach ( array_keys($pages) as $id )
			$args['pages'][ $pages[$id]->post_title . chr(11) . $id ] = $pages[$id];

		// natural case alpha sort
		uksort($args['pages'], "strnatcasecmp");
	
		$args['pages'] = ScoperAdminBulkParent::order_by_hierarchy($args['pages'], 'ID', 'post_parent');

		// take the hidden parents back out
		foreach ( $args['pages'] as $key => $page )
			if ( isset( $hidden_pages_by_id[$page->ID] ) )
				unset( $args['pages'][$key] );

		$output = '';
		
		// restore buffered titles in case they were filtered on get_pages hook
		scoper_restore_property_array( $args['pages'], $titles, 'ID', 'post_title' );
		
		if ( $object_id ) {
			$args['object_id'] = $object_id;
			$args['retain_page_ids'] = true; // retain static log to avoid redundant entries by subsequent call with use_parent_clause=false
			ScoperHardwayParentLegacy::walk_parent_dropdown($output, $args, true, $stored_parent_id);
		}
	
		// next we'll add disjointed branches, but don't allow this page's descendants to be offered as a parent
		$arr_parent = array();
		$arr_children = array();
		
		if ( $results = scoper_get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page' $status_clause") ) {
			foreach ( $results as $row ) {
				$arr_parent[$row->ID] = $row->post_parent;
				
				if ( ! isset($arr_children[$row->post_parent]) )
					$arr_children[$row->post_parent] = array();
					
				$arr_children[$row->post_parent] []= $row->ID;
			}
			
			$descendants = array();
			if ( ! empty( $arr_children[$object_id] ) ) {
				foreach ( $arr_parent as $page_id => $parent_id ) {
					if ( ! $parent_id || ($page_id == $object_id) )
						continue;
						
					do {
						if ( $object_id == $parent_id ) {
							$descendants[$page_id] = true;
							break;
						}
						
						$parent_id = $arr_parent[$parent_id];
					} while ( $parent_id );
				}
			}
			$args['descendants'] = $descendants;
		}

		ScoperHardwayParentLegacy::walk_parent_dropdown($output, $args, false, $stored_parent_id);
		
		//log_mem_usage_rs( 'end dropdown_pages()' );
		
		return $output;
	}
	
			
	// slightly modified transplant of WP 2.6 core parent_dropdown
	function walk_parent_dropdown( &$output, &$args, $use_parent_clause = true, $default = 0, $parent = 0, $level = 0 ) {
		static $use_class;
		static $page_ids;
		
		if ( ! isset($use_class) )
			$use_class = true;

		if ( ! isset( $page_ids ) )
			$page_ids = array();
			
		// todo: defaults, merge
		//extract($args);
		// args keys: pages, object_id
		
		$page_ids[$parent] = true;
		
		if ( ! is_array( $args['pages'] ) )
			$args['pages'] = array();

		if ( empty($args['descendants'] ) || ! is_array( $args['descendants'] ) )
			$args['descendants'] = array();

		foreach ( array_keys($args['pages']) as $key ) {
			// we call this without parent criteria to include pages whose parent is unassociable
			if ( $use_parent_clause && $args['pages'][$key]->post_parent != $parent )
				continue;
				
			$id = $args['pages'][$key]->ID;
				
			if ( in_array($id, array_keys($args['descendants']) ) )
				continue;

			if ( isset($page_ids[$id]) )
				continue;
		
			$page_ids[$id] = true;
		
			// A page cannot be its own parent.
			if ( $args['object_id'] && ( $id == $args['object_id'] ) )
				continue;

			$class = ( $use_class ) ? 'class="level-' . $level . '" ' : '';

			$current = ( $id == $default) ? ' selected="selected"' : '';
			$pad = str_repeat( '&nbsp;', $level * 3 );
			$output .= "\n\t<option " . $class . 'value="' . $id . '"' . $current . '>' . $pad . esc_html($args['pages'][$key]->post_title) . '</option>';
			
			ScoperHardwayParentLegacy::walk_parent_dropdown( $output, $args, true, $default, $id, $level +1 );
		}
		
		if ( ! $level && empty($args['retain_page_ids']) )
			$page_ids = array();
	}

}
?>