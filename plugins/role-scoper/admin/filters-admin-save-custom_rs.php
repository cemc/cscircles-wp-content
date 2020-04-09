<?php

class ScoperCustomAdminFiltersSave {
	// called by scoper_mnt_save_object for non-post data sources
	public static function log_object_save( $src_name, $object_id, $is_new_object, $col_parent, $set_parent ) {
		global $wpdb;
		
		$is_new_object = true;
		
		$qry = "SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND src_or_tx_name = '$src_name' AND obj_or_term_id = '$object_id'";
		if ( $assignment_ids = scoper_get_col($qry) )
			$is_new_object = false;
		else {	
			$qry = "SELECT requirement_id FROM $wpdb->role_scope_rs WHERE topic = 'object' AND src_or_tx_name = '$src_name' AND obj_or_term_id = '$object_id'";
			if ( $requirement_ids = scoper_get_col($qry) )
				$is_new_object = false;
		}
		
		if ( $col_parent ) {
			if ( ! $is_new_object ) {
				$last_parents = get_option( "scoper_last_parents_{$src_name}");
				if ( ! is_array($last_parents) )
					$last_parents = array();
				
				if ( isset( $last_parents[$object_id] ) )
					$last_parent = $last_parents[$object_id];
			}
		
			if ( isset($set_parent) && ($set_parent != $last_parent) && ($set_parent || $last_parent) ) {
				$last_parents[$object_id] = $set_parent;
				update_option( "scoper_last_parents_{$src_name}", $last_parents);
			}
		}
		
		return $is_new_object;
	}
}