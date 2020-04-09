<?php

class Scoper_RvyContentRoles extends RevisionaryContentRoles {
	function filter_object_terms( $terms, $taxonomy ) { 
		global $scoper_admin_filters;
		
		if ( ! empty($scoper_admin_filters) ) {
			return $scoper_admin_filters->flt_pre_object_terms( $terms, $taxonomy );
		}
		return array(); 
	}
	
	function get_metagroup_edit_link( $metagroup_name ) { 
		require_once( SCOPER_ABSPATH . '/admin/admin_lib_rs.php');
		
		if ( $group = ScoperAdminLib::get_group_by_name( '[' . $metagroup_name . ']' ) ) {
			return "admin.php?page=rs-groups&mode=edit&id=$group->ID";
		}
		return '';
	}
	
	function get_metagroup_members( $metagroup_name, $args = array() ) { 
		require_once( SCOPER_ABSPATH . '/admin/admin_lib_rs.php');
		
		if ( $group = ScoperAdminLib::get_group_by_name( '[' . $metagroup_name . ']' ) ) {
			return ScoperAdminLib::get_group_members( $group->ID, COL_ID_RS, true );
		}
		return array(); 
	}
	
	function users_who_can( $reqd_caps, $object_id = 0, $args = array() ) { 
		global $scoper;
		
		$defaults = array( 'src_name' => 'post' );
		$args = array_merge( $defaults, $args );
		extract($args, EXTR_SKIP);
		
		if ( ! empty($scoper) ) {
			$cols = ! empty( $args['cols'] ) ? $args['cols'] : 'all';
			return $scoper->users_who_can( $reqd_caps, $cols, $src_name, $object_id, $args );
		}
		return array();
	}
	
	function ensure_init() { 
		global $scoper;

		if ( ! isset($scoper) || is_null($scoper) ) {	
			require_once( SCOPER_ABSPATH . '/role-scoper_main.php');
			$scoper = new Scoper();
			scoper_init();
		}

		if ( empty($scoper->data_sources) )
			$scoper->load_config();
	}
	
	function add_listed_ids( $src_name, $object_type, $id ) {
		$GLOBALS['scoper']->listed_ids[$src_name][$id] = true;	
	}
	
	function set_hascap_flags( $flags ) { 
		global $scoper;
		
		if ( ! is_array($flags) )
			return;
		
		foreach( $flags as $key => $val ) {
			$scoper->cap_interceptor->$key = $val;
		}
	}
	
	function is_direct_file_access() {
		return ! empty( $GLOBALS['scoper']->direct_file_access );
	}
}

?>