<?php

function _cr_get_reqd_caps( $src_name, $op, $object_type = '-1', $status = '-1', $base_caps_only = false, $preview_future = false ) {
	if ( 'admin' == $op )
		$op = 'delete';
		
	if ( ( $object_type == -1 ) && ( $status == -1 ) && ! $base_caps_only && ! $preview_future ) {
		// only set / retrieve the static buffer for default Query_Interceptor usage
		static $reqd_caps;
		
		if ( ! isset($reqd_caps) )
			$reqd_caps = array();
	} else
		$reqd_caps = array();
	
	if ( ! isset( $reqd_caps[$src_name][$op] ) ) {
		$arr = array();
		
		switch ( $src_name ) {
		case 'post' :
			$property = "{$op}_posts";
			$others_property = "{$op}_others_posts";

			if ( ( -1 != $object_type ) && post_type_exists($object_type) )
				$post_types = array( $object_type => get_post_type_object($object_type) );
			else {
				$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );
				$use_post_types = scoper_get_option( 'use_post_types' );
			}
				
			if ( ( -1 != $status ) && isset($GLOBALS['wp_post_statuses'][$status]) ) {
				$post_statuses = array( $status => get_post_status_object($status) );
			} else {
				$post_statuses = get_post_stati( array( 'internal' => null ), 'object' );
				$post_statuses []= get_post_status_object( 'trash' );
			}
				
			foreach ( $post_types as $_post_type => $post_type_obj ) {	
				if ( ( -1 == $object_type ) && empty( $use_post_types[$_post_type] ) )
					continue;
				
				$plural_name = plural_name_from_cap_rs( $post_type_obj );
				
				$cap = $post_type_obj->cap;
		
				if ( 'read' != $op ) {
					// for "delete" op, select a value from stored cap property in this order of preference, if the proerpty is defined: delete_others_posts, delete_posts, edit_others_posts, edit_posts
					if ( ! empty($cap->$others_property) && ! $base_caps_only )
						$main_cap = $cap->$others_property;
					elseif ( ! empty($cap->$property) )
						$main_cap = $cap->$property;
					elseif ( ! empty($cap->edit_others_posts) && ! $base_caps_only )
						$main_cap = $cap->edit_others_posts;
					else
						$main_cap = $cap->edit_posts;
				}
				
				$use_statuses = ( ! empty( $post_type_obj->statuses ) ) ? $post_type_obj->statuses : $post_statuses;
	
				$use_statuses = array_intersect_key( $use_statuses, $post_statuses );
				
				foreach( $use_statuses as $status_obj ) {
					$_status = $status_obj->name;
					
					if ( ( 'read' == $op ) ) {
						if ( 'trash' == $_status )
							continue;
	
						if ( ( 'future' == $_status ) && ! $preview_future )
							continue;
							
						if ( ! $status_obj->protected && ! $status_obj->internal ) {
							// read
							$arr['read'][$_post_type][$_status] = array( 'read' );
							
							if ( ! $base_caps_only ) {
								// read_private_posts
								if ( $status_obj->private )
									$arr['read'][$_post_type][$_status] []= $cap->read_private_posts;
									
								// read_{$_status}_posts (if defined)
								if ( 'publish' != $_status ) {
									$status_cap = "read_{$_status}_{$plural_name}";
									if ( ! empty( $cap->$status_cap ) )
										$arr['read'][$_post_type][$_status] []= $status_cap;
								}
			
								$arr['read'][$_post_type][$_status] = array_unique( $arr['read'][$_post_type][$_status] );
							}
						
						} elseif ( ( ( 'future' == $_status ) || ! empty($_GET['preview']) ) && ( 'trash' != $_status ) ) {
							// preview supports non-published statuses, but requires edit capability
							//  array ( 'draft' => array($cap->edit_others_posts), 'pending' => array('edit_others_posts'), 'future' => array('edit_others_posts'), 'publish' => array('read'), 'private' => array('read', 'read_private_posts') );
							if ( $base_caps_only )
								$arr['read'][$_post_type][$_status] = array( $cap->edit_posts );
							else
								$arr['read'][$_post_type][$_status] = array( $cap->edit_others_posts );
															
							$status_cap = "read_published_{$plural_name}";
							if ( ! empty( $cap->$status_cap ) )
								$arr['read'][$_post_type][$_status] []= $status_cap;
						}
					
					} else { // op == delete / edit / other
						// edit_posts / edit_others_posts
						$arr[$op][$_post_type][$_status] []= $main_cap;
						
						// edit_published_posts
						if ( $status_obj->public || $status_obj->private || ( 'future' == $_status ) ) {
							$property = "{$op}_published_posts";

							$arr[$op][$_post_type][$_status] []= $cap->$property;
						}
						
						if ( ! $base_caps_only ) {
							// edit private posts
							if ( $status_obj->private ) {
								$property = "{$op}_private_posts";
								$arr[$op][$_post_type][$_status] []= $cap->$property;
							}
								
							// edit_{$_status}_posts (if defined)
							$status_string = ( 'publish' == $_status ) ? 'published' : $_status;
							$status_cap = "{$op}_{$status_string}_posts";

							if ( ! empty( $cap->$status_cap ) )
								$arr[$op][$_post_type][$_status] []= $cap->$status_cap;
						}
							
						$arr[$op][$_post_type][$_status] = array_unique( $arr[$op][$_post_type][$_status] );
					}
					
				} // end foreach status
			} // end foreach post type
		
			// TODO: re-implement OP_ADMIN distinction with dedicated admin caps
			//$arr['admin'] = $arr['edit'];
			
		break;
		case 'link' :
			$arr['read']['link'][''] = array( 'read' );
			$arr['edit']['link'][''] = array( 'manage_links' );		// object types with a single status store nullstring status key
			//$arr['admin']['link'][''] = array( 'manage_links' );
			
		break;
		case 'group' :
			$arr['edit']['group'][''] = array( 'manage_groups' );
			//$arr['admin']['group'][''] = array( 'manage_groups' );
		
		break;
		default:
			global $scoper;
			if ( $src = $scoper->data_sources->get( $src_name ) ) {
				if ( isset( $src->reqd_caps ) )	// legacy API support
					$arr = $src->reqd_caps;
			}
		} // end src_name switch
	
		if ( empty( $arr[$op] ) )
			$arr[$op] = array();

		$reqd_caps[$src_name][$op] = apply_filters( 'define_required_caps_rs', $arr[$op], $src_name, $op );
	} // endif pulling from static buffer

	if ( ( -1 != $status ) && ( -1 != $object_type ) ) {
		if ( isset( $reqd_caps[$src_name][$op][$object_type][$status] ) )
			return $reqd_caps[$src_name][$op][$object_type][$status];
		else
			return array();
			
	} elseif ( -1 != $object_type ) {
		if ( isset( $reqd_caps[$src_name][$op][$object_type] ) )
			return $reqd_caps[$src_name][$op][$object_type];
		else
			return array();
	} else {
		if( isset( $reqd_caps[$src_name][$op] ) )
			return $reqd_caps[$src_name][$op];
		else
			return array();
	}
}

?>