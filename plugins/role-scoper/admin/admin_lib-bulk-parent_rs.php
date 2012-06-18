<?php

class ScoperAdminBulkParent {

	// object_array = db results 2D array
	function order_by_hierarchy($object_array, $col_id, $col_parent, $id_key = false) {
		$ordered_results = array();
		$find_parent_id = 0;
		$last_parent_id = array();
		
		do {
			$found_match = false;
			$lastcount = count($ordered_results);
			foreach ( $object_array as $key => $item )
				if ( $item->$col_parent == $find_parent_id ) {
					if ( $id_key )
						$ordered_results[$item->$col_id]= $object_array[$key];
					else
						$ordered_results[]= $object_array[$key];
					
					unset($object_array[$key]);
					$last_parent_id[] = $find_parent_id;
					$find_parent_id = $item->$col_id;
					
					$found_match = true;
					break;	
				}
			
			if ( ! $found_match ) {
				if ( ! count($last_parent_id) )
					break;
				else
					$find_parent_id = array_pop($last_parent_id);
			}
		} while ( true );
		
		return $ordered_results;
	}
	
	// listed_objects[object_id] = object, including at least the parent property
	// unlisted_objects[object_id] = object, including at least the parent property
	function add_missing_parents($listed_objects, $unlisted_objects, $col_parent) {
		$need_obj_ids = array();
		foreach ( $listed_objects as $obj )
			if ( $obj->$col_parent && ! isset($listed_objects[ $obj->$col_parent ]) )
				$need_obj_ids[$obj->$col_parent] = true;

		$last_need = '';
				
		while ( $need_obj_ids ) { // potentially query for several generations of object hierarchy (but only for parents of objects that have roles assigned)
			if ( $need_obj_ids == $last_need )
				break; //precaution

			$last_need = $need_obj_ids;

			if ( $add_objects = array_intersect_key( $unlisted_objects, $need_obj_ids) ) {
				$listed_objects = $listed_objects + $add_objects; // array_merge will not maintain numeric keys
				$unlisted_objects = array_diff_key($unlisted_objects, $add_objects);
			}
			
			$new_need = array();
			foreach ( array_keys($need_obj_ids) as $id ) {
				if ( ! empty($listed_objects[$id]->$col_parent) )  // does this object itself have a nonzero parent?
					$new_need[$listed_objects[$id]->$col_parent] = true;
			}

			$need_obj_ids = $new_need;
		}
		
		return $listed_objects;
	}
	
	function get_page_titles() {
		global $wpdb;
		
		$is_administrator = is_content_administrator_rs();
		
		if ( ! $is_administrator )
			remove_filter('get_pages', array('ScoperHardway', 'flt_get_pages'), 1, 2);
		
		// don't retrieve post_content, to save memory
		$all_pages = scoper_get_results( "SELECT ID, post_parent, post_title, post_date, post_date_gmt, post_status, post_name, post_modified, post_modified_gmt, guid, menu_order, comment_count FROM $wpdb->posts WHERE post_type = 'page'" );
		
		foreach ( array_keys( $all_pages ) as $key )
			$all_pages[$key]->post_content = '';		// add an empty post_content property to each item, in case some plugin filter requires it
		
		$all_pages = apply_filters( 'get_pages', $all_pages );

		if ( ! $is_administrator )
			add_filter('get_pages', array('ScoperHardway', 'flt_get_pages'), 1, 2);

		return scoper_get_property_array( $all_pages, 'ID', 'post_title' );
	}	
	
	function get_objects_info($object_ids, &$object_names, &$object_status, &$unlisted_objects, $src, $otype, $ignore_hierarchy) {
		global $wpdb;

		// buffer titles in case they are translated
		if ( 'page' == $otype->name )
			$titles = ScoperAdminBulkParent::get_page_titles();
		
		$col_id = $src->cols->id;
		$col_name = $src->cols->name;
	
		$cols = "$col_name, $col_id";
		if ( isset($src->cols->parent) && ! $ignore_hierarchy ) {
			$col_parent = $src->cols->parent;
			$cols .= ", $col_parent";
		} else
			$col_parent = '';
		
		$col_status = ( ! empty($src->cols->status) ) ? $src->cols->status : '';
		if ( $col_status )
			$cols .= ", $col_status";
			
		$unroled_count = 0;
		$unroled_limit = ( ! empty($otype->admin_max_unroled_objects) ) ? $otype->admin_max_unroled_objects : 999999;
	
		if ( ! empty($src->cols->type) && ! empty($otype->name) ) {
			$otype_clause = "AND {$src->cols->type} = '$otype->name'";
			if ( 'post' == $src->name )
				$otype_clause .= " AND {$src->cols->status} NOT IN ('auto-draft', 'trash')";
		} else
			$otype_clause = '';
		
		$obj = '';

		if ( $results = scoper_get_results("SELECT $cols FROM $src->table WHERE 1=1 $otype_clause ORDER BY $col_id DESC") ) {
		
			foreach ( $results as $row ) {
				if ( isset($titles[$row->$col_id]) )
					$object_names[$row->$col_id] = $titles[$row->$col_id];
				elseif ( 'post' == $src->name )
					$object_names[$row->$col_id] = apply_filters( 'the_title', $row->$col_name, $row->$col_id );
				else
					$object_names[$row->$col_id] = $row->$col_name;
				
				if ( $col_status )
					$object_status[$row->$col_id] = $row->$col_status;
				
				unset($obj);
				
				if ( $col_parent )	// temporarily key by name for alpha sort of additional items prior to hierarchy sort
					$obj = (object) array($col_id => $row->$col_id, $col_name => $row->$col_name, $col_parent => $row->$col_parent);
				else
					$obj = (object) array($col_id => $row->$col_id, $col_name => $row->$col_name);
				
				// List only a limited number of unroled objects
				if ( ($unroled_limit >= 0) && ! isset($object_ids[$row->$col_id]) ) {
					if ( $unroled_count >= $unroled_limit ) {
	
						$unlisted_objects[$row->$col_id] = $obj;
						continue;
					}
					$unroled_count++;
					
				}
				
				$listed_objects[$row->$col_id] = $obj;
			}
		}
		
		// restore buffered page titles in case they were filtered previously
		if ( 'page' == $otype->name ) {
			scoper_restore_property_array( $listed_objects, $titles, 'ID', 'post_title' );
			scoper_restore_property_array( $unlisted_objects, $titles, 'ID', 'post_title' );
		}
	
		return $listed_objects;
	}
}
?>