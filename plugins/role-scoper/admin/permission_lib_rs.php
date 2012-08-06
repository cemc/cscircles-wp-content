<?php

	function user_can_admin_role_rs($role_handle, $item_id, $src_name = '', $object_type = '' ) {
		if ( is_user_administrator_rs() )
			return true;

		global $scoper;
			
		static $require_blogwide_editor;
			
		if ( ! isset($require_blogwide_editor) )
			$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');

		if ( 'admin' == $require_blogwide_editor )
			return false;  // User Admins already returned true
		
		if ( ( 'admin_content' == $require_blogwide_editor ) && ! is_content_administrator_rs() )
			return false;

		static $role_ops;

		if ( ! isset($role_ops) )
			$role_ops = array();
		
		if ( ! isset($role_ops[$role_handle]) )
			$role_ops[$role_handle] = $scoper->cap_defs->get_cap_ops( array_keys($scoper->role_defs->role_caps[$role_handle]) );

		// user can't view or edit role assignments unless they have all rolecaps
		// however, if this is a new post, allow read role to be assigned even if contributor doesn't have read_private cap blog-wide
		if ( $item_id || ( $role_ops[$role_handle] != array( 'read' => 1 ) ) ) {
			static $reqd_caps;
			
			if ( ! isset($reqd_caps) )
				$reqd_caps = array();
				
			if ( ! isset($reqd_caps[$role_handle]) )
				$reqd_caps[$role_handle] = $scoper->role_defs->role_caps[$role_handle];

			$type_caps = $scoper->cap_defs->get_matching( $src_name, $object_type );
			$reqd_caps[$role_handle] = array_intersect_key( $reqd_caps[$role_handle], $type_caps );

			if ( is_null($item_id) )
				$item_id = 0;

			if ( defined( 'SCOPER_AUTHORS_ASSIGN_ANY_ROLE' ) && ( 'post' == $src_name ) )
				$author_post = get_post( $item_id );
			
			if ( empty($author_post) || ( $author_post->post_author == $GLOBALS['current_user']->ID ) || ! user_can_admin_object_rs( 'post', $author_post->post_type, $item_id ) ) {
				if ( ! cr_user_can( array_keys($reqd_caps[$role_handle]), $item_id ) )
					return false;
			}
			
			// are we also applying the additional requirement (based on RS Option setting) that the user is a blog-wide editor?
			if ( $require_blogwide_editor ) {
				static $can_edit_blogwide;

				if ( ! isset($can_edit_blogwide) )
					$can_edit_blogwide = array();
					
				if ( ! isset($can_edit_blogwide[$src_name][$object_type]) )
					$can_edit_blogwide[$src_name][$object_type] = user_can_edit_blogwide_rs($src_name, $object_type, array( 'require_others_cap' => true ) );
	
				if ( ! $can_edit_blogwide[$src_name][$object_type] )
					return false;
			}
		}
		
		return true;
	}
	
	function user_can_admin_object_rs($src_name, $object_type, $object_id = false, $any_obj_role_check = false, $user = '' ) {
		if ( is_content_administrator_rs() )
			return true;

		global $scoper;
																	// TODO: is this necessary?
		$is_new_object = ( ! $object_id && ( false !== $object_id ) ) || ( ( 'post' == $src_name ) && ! empty($GLOBALS['post']) && ( 'auto-draft' == $GLOBALS['post']->post_status ) );
		
		if ( $is_new_object ) {
			$status_name = ( 'post' == $src_name ) ? 'draft' : '';
		} else {
			$status_name = $scoper->data_sources->detect('status', $src_name, $object_id);
		}

		// TODO: is multi-value array ever passed?
		if ( is_array($object_type) ) {
			if ( count($object_type) == 1 )
				$object_type = reset($object_type);
			else
				// only WP roles should ever have multiple sources / otypes
				$object_type = $scoper->data_sources->get_from_db('type', $src_name, $object_id);
		}

		$base_caps_only = $is_new_object;
		
		// Possible TODO: re-implement OP_ADMIN distinction with admin-specific capabilities	
		//$admin_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_ADMIN_RS, $status_name, $base_caps_only);
		$admin_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_EDIT_RS, $status_name, $base_caps_only);
		
		$delete_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_DELETE_RS, $status_name, $base_caps_only);
		$reqd_caps = array_merge( array_keys($admin_caps), array_keys($delete_caps) );

		if ( ! $reqd_caps )
			return true;	// apparantly this src/otype has no admin caps, so no restriction to apply
			
		// Note on 'require_full_object_role' argument: 
		// Normally we want to disregard "others" cap requirements if a role is assigned directly for an object
		// This is an exception - we need to retain a "delete_others" cap requirement in case it is the
		// distinguishing cap of an object administrator
		$return = cr_user_can($reqd_caps, $object_id, 0, array( 'require_full_object_role' => true, 'skip_revision_allowance' => true ) );

		if ( ! $return && ! $object_id && $any_obj_role_check ) {
			// No object ID was specified, and current user does not have the cap blog-wide.  Credit user for capability on any individual object.
			
			// Possible TODO: re-implement OP_ADMIN distinction with admin-specific capabilities	
			//$admin_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_ADMIN_RS, STATUS_ANY_RS);
			$admin_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_EDIT_RS, STATUS_ANY_RS);
			$delete_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_DELETE_RS, STATUS_ANY_RS);
			
			if ( $reqd_caps = array_merge( array_keys($admin_caps), array_keys($delete_caps) ) ) {
				if ( ! defined('DISABLE_QUERYFILTERS_RS') ) {
					global $cap_interceptor;

					if ( $cap_interceptor->user_can_for_any_object( $reqd_caps ) )
						$return = true;
				}
			}
		}
		
		return $return;
	}
	
	function user_can_admin_terms_rs($taxonomy = '', $term_id = '', $user = '') {
		if ( is_user_administrator_rs() )
			return true;

		global $scoper;
			
		if ( ! is_object($user) ) {
			$user = $GLOBALS['current_rs_user'];
		}
		
		$taxonomies = array();
		$qualifying_caps = array();
		
		if ( $tx_obj = get_taxonomy( $taxonomy ) ) {
			$qualifying_caps = array( $tx_obj->cap->manage_terms => 1 );
			$taxonomies[$taxonomy] = 1;
		} else {
		foreach ( $scoper->cap_defs->get_all() as $cap_name => $capdef )
			if ( isset($capdef->op_type) && (OP_ADMIN_RS == $capdef->op_type) && ! empty($capdef->object_types) ) {
				foreach ( $capdef->object_types as $_object_type ) {
					if ( isset( $scoper->taxonomies->members[$_object_type] ) ) {		 
						if ( ! $taxonomy || ( $_object_type == $taxonomy ) ) {
							$qualifying_caps[$cap_name] = 1;
							$taxonomies[$_object_type] = 1;
						}
					}
				}
			}
		}

		if ( empty($qualifying_caps) )
			return false;

		// does current user have any blog-wide admin caps for term admin?
		$qualifying_roles = $scoper->role_defs->qualify_roles(array_flip($qualifying_caps), 'rs');
		
		if ( $user_blog_roles = array_intersect_key( $user->blog_roles[ANY_CONTENT_DATE_RS], $qualifying_roles) ) {
			if ( $term_id ) {
				$strict_terms = $scoper->get_restrictions(TERM_SCOPE_RS, $taxonomy);
			
				foreach ( array_keys($user_blog_roles) as $role_handle ) {
					// can't blend in blog role if term requires term role assignment
					// Presence of an "unrestrictions" element in array indicates that the role is default-restricted.
					if ( isset($strict_terms['unrestrictions'][$role_handle][$term_id])
					|| ( ( ! isset($strict_terms['unrestrictions'][$role_handle]) || ! is_array($strict_terms['unrestrictions'][$role_handle]) ) && ! isset($strict_terms['restrictions'][$role_handle][$term_id]) ) ) {
						return true;
					}
				}
			} else {
				// todo: more precision by checking whether ANY terms are non-strict for the qualifying role(s)
				return true;
			}
		}
		
		// does current user have any term-specific admin caps for term admin?
		if ( $taxonomies ) {
			foreach ( array_keys($taxonomies) as $taxonomy ) {
				if ( ! isset($user->term_roles[$taxonomy]) )
					$user->get_term_roles_daterange($taxonomy);		// call daterange function populate term_roles property - possible perf enhancement for subsequent code even though we don't conider content_date-limited roles here
				
				if ( ! empty($user->term_roles[$taxonomy][ANY_CONTENT_DATE_RS]) ) {
					foreach ( array_keys( $user->term_roles[$taxonomy][ANY_CONTENT_DATE_RS] ) as $role_handle ) {
						if ( ! empty($scoper->role_defs->role_caps[$role_handle]) ) {
							if ( array_intersect_key($qualifying_caps, $scoper->role_defs->role_caps[$role_handle]) ) {
								if ( ! $term_id || in_array($term_id, $user->term_roles[$taxonomy][ANY_CONTENT_DATE_RS][$role_handle]) )
									return true;
							}
						}
					}
				}
			}
		} // endif any taxonomies have cap defined
	} // end function
	
	
	function user_can_edit_blogwide_rs( $src_name = '', $object_type = '', $args = array() ) {
		if ( is_administrator_rs($src_name) )
			return true;

		global $scoper, $current_rs_user;

		$defaults = array( 'require_others_cap' => false, 'status' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		$object_types = ( $object_type ) ? (array) $object_type : array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
		
		foreach( $object_types as $object_type ) {
			$cap_defs = $scoper->cap_defs->get_matching( $src_name, $object_type, OP_EDIT_RS, '', ! $require_others_cap );

			if ( $status )
				$cap_defs = array_merge( $cap_defs, $scoper->cap_defs->get_matching( $src_name, $object_type, OP_EDIT_RS, $status, ! $require_others_cap ) );

			foreach ( array_keys($current_rs_user->blog_roles[ANY_CONTENT_DATE_RS]) as $role_handle ) {
				if ( isset($scoper->role_defs->role_caps[$role_handle]) ) {
					if ( ! array_diff_key( $cap_defs, $scoper->role_defs->role_caps[$role_handle] ) ) {
						return true;			
					}
				}
			}
		}
	}

?>