<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
	
	// As of WP 3.0, "{$taxonomy}_pre" filter is not applied if none of its terms were selected
	// * force the filter for all associated taxonomies regardless of term selection
	// * apply default term(s) if defined
	function scoper_force_custom_taxonomy_filters( $post_id, $post ) {
		$post_type_obj = get_post_type_object( $post->post_type );

		foreach( $post_type_obj->taxonomies as $taxonomy ) {
			// if terms were selected, WP core already applied the filter and there is no need to apply default terms
			if ( in_array( $taxonomy, array( 'category', 'post_tag' ) ) || did_action( "pre_post_{$taxonomy}" ) )
				continue;

			// don't filter term selection for non-hierarchical taxonomies
			if ( empty( $GLOBALS['wp_taxonomies'][$taxonomy]->hierarchical ) )
				continue;
				
			if ( $taxonomy_obj = get_taxonomy($taxonomy) ) {
				if ( ! empty($_POST['tax_input'][$taxonomy]) && is_array($_POST['tax_input'][$taxonomy]) && ( reset($_POST['tax_input'][$taxonomy]) || ( count($_POST['tax_input'][$taxonomy]) > 1 ) ) )  // complication because (as of 3.0) WP always includes a zero-valued first array element
					$tags = $_POST['tax_input'][$taxonomy];
				elseif ( 'auto-draft' != $post->post_status )
					$tags = (array) get_option("default_{$taxonomy}");
				else
					$tags = array();
	
				if ( $tags ) {
					if ( ! empty($_POST['tax_input']) && is_array($_POST['tax_input'][$taxonomy]) ) // array = hierarchical, string = non-hierarchical.
						$tags = array_filter($tags);
	
					if ( current_user_can( $taxonomy_obj->cap->assign_terms ) ) {
						$tags = apply_filters("pre_post_{$taxonomy}", $tags);
						$tags = apply_filters("{$taxonomy}_pre", $tags);
	
						wp_set_post_terms( $post_id, $tags, $taxonomy );
					}
				}
			}
		}
	}
	
	
	// called by ScoperAdminFilters::mnt_save_object
	// This handler is meant to fire whenever an object is inserted or updated.
	// If the client does use such a hook, we will force it by calling internally from mnt_create and mnt_edit
	function scoper_mnt_save_object($src_name, $args, $object_id, $object = '') {
		global $scoper, $scoper_admin;

		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
		
		// operations in this function only apply to main post save action, not revision save
		if ( 'post' == $src_name ) {
			if ( is_object($object) && ! empty( $object->post_type ) && ( ( 'revision' == $object->post_type ) || ( 'auto-draft' == $object->post_status ) ) )
				return;
		}

		static $saved_objects;

		if ( ! isset($saved_objects) )
			$saved_objects = array();

		if ( isset($saved_objects[$src_name][$object_id]) )
			return;

		$defaults = array( 'object_type' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
			
		if ( 'post' == $src_name ) {
			global $wpdb;
			$is_new_object = ! get_post_meta($object_id, '_scoper_custom', true) && ! $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND src_or_tx_name = 'post' AND obj_or_term_id = '$object_id'" );
		} else
			$is_new_object = true;  // for other data sources, we have to assume object is new unless it has a role or restriction stored already.

		if ( empty($object_type) )
			$object_type = cr_find_object_type( $src_name, $object_id );

		$saved_objects[$src_name][$object_id] = 1;

		// parent settings can affect the auto-assignment of propagating roles/restrictions
		$last_parent = 0;
		$set_parent = 0;
		
		if ( $col_parent = $scoper->data_sources->member_property($src_name, 'cols', 'parent') ) {
			if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php', 'press-this.php' ) ) ) {
				if ( isset($_POST[$col_parent]) ) 
					$set_parent = $_POST[$col_parent];
			} else {
				if ( isset($object->$col_parent) ) // this should also work for handling regular WP edit form, but leaving existing code above until further testing
					$set_parent = $object->$col_parent;
			}
		}
	
		// Determine whether this object is new (first time this RS filter has run for it, though the object may already be inserted into db)
		if ( 'post' == $src_name ) {
			$post_type_obj = get_post_type_object( $object_type );	
			
			$last_parent = ( $object_id > 0 ) ? get_post_meta($object_id, '_scoper_last_parent', true) : '';
			
			if ( is_numeric($last_parent) ) // not technically necessary, but an easy safeguard to avoid re-inheriting parent roles
				$is_new_object = false;

			if ( isset($set_parent) && ($set_parent != $last_parent) && ($set_parent || $last_parent) )
				update_post_meta($object_id, '_scoper_last_parent', (int) $set_parent);
			
		} else {
			// for other data sources, we have to assume object is new unless it has a role or restriction stored already.
			require_once( dirname(__FILE__).'/filters-admin-save-custom_rs.php' );
			$is_new_object = ScoperCustomAdminFiltersSave::log_object_save( $src_name, $object_id, $is_new_object, $col_parent, $set_parent );
		}
	
		// used here and in UI display to enumerate role definitions
		$role_defs = $scoper->role_defs->get_matching('rs', $src_name, $object_type);
		$role_handles = array_keys($role_defs);
		

		// Were roles / restrictions previously customized by direct edit?
		if ( 'post' == $src_name )
			$roles_customized = $is_new_object ? false : get_post_meta($object_id, '_scoper_custom', true);
		else {
			$roles_customized = false;
			if ( ! $is_new_object )
				if ( $custom_role_objects = (array) get_option( "scoper_custom_{$src_name}" ) )
					$roles_customized = isset( $custom_role_objects[$object_id] );
		}
		
		$new_role_settings = false;
		$new_restriction_settings = false;
		
		$use_csv_entry = array( constant('ROLE_BASIS_USER') => scoper_get_option( 'user_role_assignment_csv' ) );

		// Were roles / restrictions custom-edited just now?
		if ( ! defined('XMLRPC_REQUEST') ) {
			// Now determine if roles/restrictions have changed since the edit form load
			foreach ( $role_defs as $role_handle => $role_def) {
				$role_code = 'r' . array_search($role_handle, $role_handles);

				// make sure the role assignment UI for this role was actually reviewed
				if ( ! isset($_POST["last_objscope_{$role_code}"]) )
					continue;

				// did user change roles?

				if ( $use_csv_entry[ROLE_BASIS_USER] && ( ! empty( $_POST[ "{$role_code}u_csv" ] ) || ! empty( $_POST[ "p_{$role_code}u_csv" ] ) ) ) {
					$new_role_settings = true;
				} 

				// even if CSV entry is enabled, user removal is via checkbox
				$compare_vars = array( "{$role_code}u" => "last_{$role_code}u", "{$role_code}g" => "last_{$role_code}g" );

				if ( $col_parent ) {
					$compare_vars ["p_{$role_code}u"] = "last_p_{$role_code}u";
					$compare_vars ["p_{$role_code}g"] = "last_p_{$role_code}g";
				}
				
				foreach ( $compare_vars as $var => $var_last ) {
					$agents = ( isset($_POST[$var]) ) ? $_POST[$var] : array();
					$last_agents = ( ! empty($_POST[$var_last]) ) ? explode("~", $_POST[$var_last]) : array();
					
					sort($agents);
					sort($last_agents);

					if ( $last_agents != $agents ) {
						$new_role_settings = true;
						break;
					}
				}
				
				// did user change restrictions?
				$compare_vars = array( "objscope_{$role_code}" => "last_objscope_{$role_code}" );
				
				if ( $col_parent )
					$compare_vars ["objscope_children_{$role_code}"] = "last_objscope_children_{$role_code}";
				
				foreach ( $compare_vars as $var => $var_last ) {
					$val = ( isset($_POST[$var]) ) ? $_POST[$var] : 0;
					$last_val = ( isset($_POST[$var_last]) ) ? $_POST[$var_last] : 0;
					
					if ( $val != $last_val ) {
						$new_role_settings = true;			// NOTE: We won't re-inherit roles/restrictions following parent change if roles OR restrictions have been manually set
						$new_restriction_settings = true;	// track manual restriction changes separately due to file filtering implications
						break;
					}
				}
				
				if ( $new_role_settings && $new_restriction_settings )
					break;
			}
		
			if ( $new_role_settings && ! $roles_customized ) {
				$roles_customized = true;
				
				if ( 'post' == $src_name )
					update_post_meta($object_id, '_scoper_custom', true);
				else {
					$custom_role_objects [$object_id] = true;
					update_option( "scoper_custom_{$src_name}", $custom_role_objects );
				}
			}
		} // endif user-modified roles/restrictions weren't already saved

		// apply default roles for new object
		if ( $is_new_object && ! $roles_customized ) {  // NOTE: this means we won't apply default roles if any roles have been manually assigned to the new object
			scoper_inherit_parent_roles($object_id, OBJECT_SCOPE_RS, $src_name, 0, $object_type);
		}
		
		// Inherit parent roles / restrictions, but only if a new parent is set and roles haven't been manually edited for this object
		if ( isset($set_parent) && ( $set_parent != $last_parent ) && ! $roles_customized ) {
			// clear previously propagated role assignments
			if ( ! $is_new_object ) {
				$args = array( 'inherited_only' => true, 'clear_propagated' => true );
				ScoperAdminLib::clear_restrictions(OBJECT_SCOPE_RS, $src_name, $object_id, $args);
				ScoperAdminLib::clear_roles(OBJECT_SCOPE_RS, $src_name, $object_id, $args);
			}

			// apply propagating roles, restrictions from selected parent
			if ( $set_parent ) {
				scoper_inherit_parent_roles($object_id, OBJECT_SCOPE_RS, $src_name, $set_parent, $object_type);
				scoper_inherit_parent_restrictions($object_id, OBJECT_SCOPE_RS, $src_name, $set_parent, $object_type);
			}
		} // endif new parent selection (or new object)

		// Roles/Restrictions were just edited manually, so store role settings (which may contain default roles even if no manual settings were made)
		if ( $new_role_settings && ! empty($_POST['rs_object_roles']) && ( empty($_POST['action']) || ( 'autosave' != $_POST['action'] ) ) && ! defined('XMLRPC_REQUEST') ) {
			$role_assigner = init_role_assigner();
		
			$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');
			if ( 
			( ( 'admin' != $require_blogwide_editor ) || is_user_administrator_rs() ) &&
			( ( 'admin_content' != $require_blogwide_editor ) || is_content_administrator_rs() ) 
			) {
				if ( $object_type && $scoper_admin->user_can_admin_object($src_name, $object_type, $object_id) ) {
					// store any object role (read/write/admin access group) selections
					$role_bases = array();
					if ( GROUP_ROLES_RS )
						$role_bases []= ROLE_BASIS_GROUPS;
					if ( USER_ROLES_RS )
						$role_bases []= ROLE_BASIS_USER;
					
					$set_roles = array_fill_keys( $role_bases, array() );
					$set_restrictions = array();
					
					$default_restrictions = $scoper->get_default_restrictions(OBJECT_SCOPE_RS);
					
					foreach ( $role_defs as $role_handle => $role_def) {
						if ( ! isset($role_def->valid_scopes[OBJECT_SCOPE_RS]) )
							continue;
	
						$role_code = 'r' . array_search($role_handle, $role_handles);
							
						// make sure the role assignment UI for this role was actually reviewed
						if ( ! isset($_POST["last_objscope_{$role_code}"]) )
							continue;

						foreach ( $role_bases as $role_basis ) {
							$id_prefix = $role_code . substr($role_basis, 0, 1);
							
							$for_entity_agent_ids = (isset( $_POST[$id_prefix]) ) ? $_POST[$id_prefix] : array();
							$for_children_agent_ids = ( isset($_POST["p_$id_prefix"]) ) ? $_POST["p_$id_prefix"] : array();
							
							// NOTE: restrict_roles, assign_roles functions validate current user roles before modifying assignments
	
							// handle csv-entered agent names
							if ( ! empty( $use_csv_entry[$role_basis] ) ) {
								$csv_id = "{$id_prefix}_csv";
								
								if ( $csv_for_item = ScoperAdminLib::agent_ids_from_csv( $csv_id, $role_basis ) )
									$for_entity_agent_ids = array_merge($for_entity_agent_ids, $csv_for_item);
	
								if ( $csv_for_children = ScoperAdminLib::agent_ids_from_csv( "p_$csv_id", $role_basis ) )
									$for_children_agent_ids = array_merge($for_children_agent_ids, $csv_for_children);
							}

							$set_roles[$role_basis][$role_handle] = array();
		
							if ( $for_both_agent_ids = array_intersect($for_entity_agent_ids, $for_children_agent_ids) )
								$set_roles[$role_basis][$role_handle] = $set_roles[$role_basis][$role_handle] + array_fill_keys($for_both_agent_ids, ASSIGN_FOR_BOTH_RS);
							
							if ( $for_entity_agent_ids = array_diff( $for_entity_agent_ids, $for_children_agent_ids ) )
								$set_roles[$role_basis][$role_handle] = $set_roles[$role_basis][$role_handle] + array_fill_keys($for_entity_agent_ids, ASSIGN_FOR_ENTITY_RS);
					
							if ( $for_children_agent_ids = array_diff( $for_children_agent_ids, $for_entity_agent_ids ) )
								$set_roles[$role_basis][$role_handle] = $set_roles[$role_basis][$role_handle] + array_fill_keys($for_children_agent_ids, ASSIGN_FOR_CHILDREN_RS);
						}
						
						if ( isset($default_restrictions[$src_name][$role_handle]) ) {
							$max_scope = BLOG_SCOPE_RS;
							$item_restrict = empty($_POST["objscope_{$role_code}"]);
							$child_restrict = empty($_POST["objscope_children_{$role_code}"]);
						} else {
							$max_scope = OBJECT_SCOPE_RS;
							$item_restrict = ! empty($_POST["objscope_{$role_code}"]);
							$child_restrict = ! empty($_POST["objscope_children_{$role_code}"]);
						}
						
						$set_restrictions[$role_handle] = array( 'max_scope' => $max_scope, 'for_item' => $item_restrict, 'for_children' => $child_restrict );
					}
					
					$args = array('implicit_removal' => true, 'object_type' => $object_type);
					
					// don't record first-time storage of default roles as custom settings
					if ( ! $new_role_settings )
						$args['is_auto_insertion'] = true;
					
					// Add or remove object role restrictions as needed (no DB update in nothing has changed)
					$role_assigner->restrict_roles(OBJECT_SCOPE_RS, $src_name, $object_id, $set_restrictions, $args );
					
					// Add or remove object role assignments as needed (no DB update if nothing has changed)
					foreach ( $role_bases as $role_basis )
						$role_assigner->assign_roles(OBJECT_SCOPE_RS, $src_name, $object_id, $set_roles[$role_basis], $role_basis, $args );
				} // endif object type is known and user can admin this object
			} // end if current user is an Administrator, or doesn't need to be
		} //endif roles were manually edited by user (and not autosave)
		
		
		// if post status has changed to or from private (or is a new private post), flush htaccess file rules for file attachment filtering
		if ( scoper_get_option( 'file_filtering' ) ) {
			/*
			if ( $new_restriction_settings ) {
				$maybe_flush_file_rules = true;
			} else {
				$maybe_flush_file_rules = false;
		
				global $scoper_admin_filters;
				
				if ( isset( $scoper_admin_filters->last_post_status[$object_id] ) ) {
					$new_status = ( isset($_POST['post_status']) ) ? $_POST['post_status'] : ''; // assume for now that XML-RPC will not modify post status
		
					if ( $scoper_admin_filters->last_post_status[$object_id] != $new_status )
						if ( ( 'private' == $new_status ) || ( 'private' == $scoper_admin_filters->last_post_status[$object_id] ) )
							$maybe_flush_file_rules = true;
		
				} elseif ( isset($_POST['post_status']) && ( 'private' == $_POST['post_status'] ) )
					$maybe_flush_file_rules = true;	
			}
			*/
			
			//if ( $maybe_flush_file_rules ) {
				global $wpdb;
				if ( scoper_get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = '$object_id' LIMIT 1" ) ) {   // no need to flush file rules unless this post has at least one attachment
					scoper_flush_file_rules();
				}
			//}
		}
		
		if ( ( 'post' == $src_name ) && $post_type_obj->hierarchical ) {
			$_post = get_post($object_id);
			if ( 'auto-draft' != $_post->post_status ) {
				delete_option('scoper_page_ancestors');
				scoper_flush_cache_groups('get_pages');
			}
		}
				
		// need this to make metabox captions update in first refresh following edit & save
		if ( is_admin() && isset( $GLOBALS['scoper_admin_filters_item_ui'] ) ) {
			$GLOBALS['scoper_admin_filters_item_ui']->act_tweak_metaboxes();
		}

		// possible TODO: remove other conditional calls since we're doing it here on every save
		scoper_flush_results_cache();	
	}

	
	// Filtering of Page Parent selection.  
	// This was a required after-the-fact operation for WP < 2.7 (due to inability to control inclusion of Main Page in UI dropdown)
	// For WP >= 2.7, it is an anti-hacking precaution
	//
	// There is currently no way to explictly restrict or grant Page Association rights to Main Page (root). Instead:
	// 	* Require blog-wide edit_others_pages cap for association of a page with Main
	//  * If an unqualified user tries to associate or un-associate a page with Main Page,
	//	  revert page to previously stored parent if possible. Otherwise set status to "unpublished".
	function scoper_flt_post_status ($status) {
		if ( ( 'async-upload.php' == $GLOBALS['pagenow'] ) || ( ! empty( $_POST['action'] ) && ( 'autosave' == $_POST['action'] ) ) )
			return $status;

		if ( defined('XMLRPC_REQUEST') ) {
			global $scoper_xmlrpc_post_status;
			$scoper_xmlrpc_post_status = $status;
		}

		/*
		// overcome any denials of publishing rights which were not filterable by user_has_cap	// TODO: confirm this is no longer necessary
		if ( ('pending' == $status) && ( ('publish' == $_POST['post_status']) || ('Publish' == $_POST['original_publish'] ) ) )
			if ( ! empty( $current_user->allcaps['publish_pages'] ) )
				$status = 'publish';
		*/

		// user can't associate / un-associate a page with Main page unless they have edit_pages blog-wide
		if ( ! empty( $_POST['post_ID'] ) ) {
			$post_id = $_POST['post_ID'];
			$post_type = $_POST['post_type'];
			$selected_parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : 0;
		} elseif( ! empty($GLOBALS['post']) ) {
			$post_id = $GLOBALS['post']->ID;
			$post_type = $GLOBALS['post']->post_type;
			$selected_parent_id = $GLOBALS['post']->post_parent;
		} else
			return $status;	

		$_post = get_post( $post_id );
		
		if ( $saved_status_object = get_post_status_object( $_post->post_status ) )
			$already_published = ( $saved_status_object->public || $saved_status_object->private );
		else
			$already_published = false;
		
		// if neither the stored nor selected parent is Main, we have no beef with it
		if ( ! empty($selected_parent_id) && ( ! empty($_post->post_parent ) || ! $already_published ) )
			return $status;
			
		// if the page is and was associated with Main Page, don't mess
		if ( empty($selected_parent_id) && empty($_post->post_parent) && $already_published )
			return $status;

		if ( empty($_POST['parent_id']) && ! $GLOBALS['scoper_admin_filters']->user_can_associate_main( $post_type ) ) {
			if ( ! $already_published ) {  // This should only ever happen if the POST data is manually fudged
				if ( $post_status_object = get_post_status_object( $status ) ) {
					if ( $post_status_object->public || $post_status_object->private )
						$status = 'draft';
				}
			}
		}

		return $status;
	}
	
	
	// Enforce any page parent filtering which may have been dictated by the flt_post_status filter, which executes earlier.
	function scoper_flt_page_parent ($parent_id) {
		if ( 'no_parent_filter' == scoper_get_option( 'lock_top_pages' ) )
			return (int) $parent_id;
	
		if ( ! empty($_REQUEST['post_ID']) ) 
			$post_id = $_REQUEST['post_ID'];
		elseif ( ! empty($_REQUEST['post_id']) )
			$post_id = $_REQUEST['post_id'];
		else
			return (int) $parent_id;
			
		if ( ! empty( $post_id ) )
			if ( $parent_id == $post_id )	// normal revision save
				return (int) $parent_id;
		
		if ( defined( 'RVY_VERSION' ) ) {
			global $revisionary;
			if ( ! empty($revisionary->admin->revision_save_in_progress) )
				return (int) $parent_id;
		}
		
		if ( empty($_POST['post_type']) )
			return (int) $parent_id;
		
		// Make sure the selected parent is valid.  Merely an anti-hacking precaution to deal with manually fudged POST data		
		global $scoper, $wpdb;
		
		$post_type = $_POST['post_type'];
		$plural_name = plural_name_from_cap_rs( get_post_type_object($post_type) );
		
		$args = array();
		$args['alternate_reqd_caps'][0] = array("create_child_{$plural_name}");

		if ( $descendant_ids = scoper_get_page_descendant_ids( $post_id ) )
			$exclusion_clause = "AND ID NOT IN('" . implode( "','", $descendant_ids ) . "')";
		else
			$exclusion_clause = '';
		
		$qry_parents = "SELECT ID FROM $wpdb->posts WHERE post_type = '$post_type' AND post_status != 'auto-draft' $exclusion_clause";
		$qry_parents = apply_filters('objects_request_rs', $qry_parents, 'post', $post_type, $args);
		$valid_parents = scoper_get_col($qry_parents);
				
		$post = get_post( $post_id );
		
		if ( $parent_id ) {
			if ( $post && ! in_array($parent_id, $valid_parents) ) {
				$parent_id = $post->post_parent;
			}
		} else {
			if ( ! $GLOBALS['scoper_admin_filters']->user_can_associate_main( $post_type ) ) {
				$already_published = false;
				if ( $post ) {
					if ( $saved_status_object = get_post_status_object( $post->post_status ) )
						$already_published = ( $saved_status_object->public || $saved_status_object->private );
				}

				if ( $already_published ) {
					// If post was previously published to another parent, revert it
					$parent_id = $post->post_parent;
				} elseif ( $valid_parents ) {
					// otherwise default to a valid parent
					sort( $valid_parents );
					$parent_id = reset($valid_parents);
					$_POST['parent_id'] = (int) $parent_id; // for subsequent post_status filter
				}
			}
		}
			
		return (int) $parent_id;
	}
	
	function scoper_get_page_descendant_ids($page_id, $pages = '' ) {
		global $wpdb;
		
		if ( empty( $pages ) )
			$pages = scoper_get_results( "SELECT ID, post_parent FROM $wpdb->posts WHERE post_parent > 0 AND post_type NOT IN ( 'revision', 'attachment' )" );	

		$descendant_ids = array();
		foreach ( (array) $pages as $page ) {
			if ( $page->post_parent == $page_id ) {
				$descendant_ids[] = $page->ID;
				if ( $children = get_page_children($page->ID, $pages) ) {
					foreach( $children as $_page )
						$descendant_ids []= $_page->ID;
				}
			}
		}
		
		return $descendant_ids;
	}
	
	
	function scoper_flt_pre_object_terms ($selected_terms, $taxonomy, $args = array()) {
		//rs_errlog( "scoper_flt_pre_object_terms input: " . serialize($selected_terms) );

		// strip out fake term_id -1 (if applied)
		if ( $selected_terms && is_array($selected_terms) )
			$selected_terms = array_diff($selected_terms, array(-1, 0, '0', '-1', ''));  // not sure who is changing empty $_POST['post_category'] array to an array with nullstring element, but we have to deal with that
			
		if ( defined('DISABLE_QUERYFILTERS_RS') )
			return $selected_terms;

		if ( ! is_array($selected_terms) ) {
			// don't filter term selection for non-hierarchical taxonomies
			if ( isset( $GLOBALS['wp_taxonomies'][$taxonomy] ) && empty( $GLOBALS['wp_taxonomies'][$taxonomy]->hierarchical ) )
				return $selected_terms;
		}

		global $scoper, $current_user, $wpdb;
			
		if ( ! $src_name = $scoper->taxonomies->member_property($taxonomy, 'object_source') )
			return $selected_terms;
		
		// don't filter selected terms for content administrator, but still need to apply default term as needed when none were selected
		if ( is_content_administrator_rs() ) {
			$user_terms = $selected_terms;
		} else {
			if ( defined( 'RVY_VERSION' ) ) {
				global $revisionary;
					 
				if ( ! empty($revisionary->admin->impose_pending_rev) )
					return $selected_terms;
			}
				
			$orig_selected_terms = $selected_terms;
				
			if ( ! is_array($selected_terms) )
				$selected_terms = array();
				
			require_once(dirname(__FILE__).'/filters-admin-term-selection_rs.php');
			$user_terms = array(); // will be returned by filter_terms_for_status
			
			$selected_terms = scoper_filter_terms_for_status($taxonomy, $selected_terms, $user_terms);
			
			if ( 'post' == $src_name ) { // TODO: abstract for other data sources
				if ( $object_id = $scoper->data_sources->detect('id', $src_name) ) {
					$stored_terms = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );

					if ( $deselected_terms = array_diff( $stored_terms, $selected_terms ) ) {
						if ( $unremovable_terms = array_diff( $deselected_terms, $user_terms ) ) {
							// --- work around storage of autodraft to default category ---
							$_post = get_post( $object_id );

							if ( ( 'category' == $taxonomy ) && ( 'draft' == $_post->post_status ) ) {
								$default_terms = (array) maybe_unserialize( scoper_get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'default_category'" ) );
								$unremovable_terms = array_diff( $unremovable_terms, $default_terms );
							}
							// --- end workaround ---
							
							$selected_terms = array_merge( $selected_terms, $unremovable_terms );
						}
					}
				}
			}
		}
		
		//rs_errlog( "$taxonomy - user terms: " . serialize($user_terms) );
		//rs_errlog( "selected terms: " . serialize($selected_terms) );

		if ( empty($selected_terms) ) {
			// For now, always check the DB for default terms.  TODO: only if the default_term_option property is set
			if ( ! $default_term_option = $scoper->taxonomies->member_property( $taxonomy, 'default_term_option' ) )
				$default_term_option = "default_{$taxonomy}";

			// avoid recursive filtering.  Todo: use remove_filter so we can call get_option, supporting filtering by other plugins 
			$default_terms = (array) maybe_unserialize( scoper_get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = '$default_term_option'" ) );
			//$selected_terms = (array) get_option( $tx->default_term_option );
			
			// but if the default term is not defined or is not in user's subset of usable terms, substitute first available
			if ( $user_terms ) {
				$_default_terms = array_intersect($default_terms, $user_terms);
				
				if ( ! $_default_terms ) {
					if ( $default_terms || defined( 'SCOPER_AUTO_DEFAULT_TERM' ) ) { // substitute 1st available only if default term option is set or constant defined
						//if ( $scoper->taxonomies->member_property( $taxonomy, 'requires_term' )  )
						$default_terms = (array) $user_terms[0];
					} else {
						$use_taxonomies = scoper_get_option( 'use_taxonomies' );  // If a 'requires_term' taxonomy (i.e. hierarchical) is enabled for RS filtering, a term must be stored
						if ( ! empty( $use_taxonomies[$taxonomy] ) )
							$default_terms = (array) $user_terms[0];
						else
							$default_terms = array();
					}
				}
			}
			
			//rs_errlog( "default_terms: " . serialize($default_terms) );

			$selected_terms = $default_terms;
		}

		//rs_errlog( "returning selected terms: " . serialize($selected_terms) );
		return $selected_terms;
	}

	
	// This handler is meant to fire whenever a term is inserted or updated.
	// If the client does use such a hook, we will force it by calling internally from mnt_create and mnt_edit
	function scoper_mnt_save_term($deprecated_taxonomy, $args, $term_id, $unused_tt_id = '', $taxonomy = '') {
		if ( ! $taxonomy )
			$taxonomy = $deprecated_taxonomy;

		static $saved_terms;
		
		if ( ! isset($saved_terms) )
			$saved_terms = array();
	
		// so this filter doesn't get called by hook AND internally
		if ( isset($saved_terms[$taxonomy][$term_id]) )
			return;
			
		
		global $scoper;
			
		// parent settings can affect the auto-assignment of propagating roles/restrictions
		$set_parent = 0;
		
		if ( $col_parent = $scoper->taxonomies->member_property($taxonomy, 'source', 'cols', 'parent') ) {
			$tx_src_name = $scoper->taxonomies->member_property($taxonomy, 'source', 'name');
			$set_parent = $scoper->data_sources->get_from_http_post('parent', $tx_src_name);
		}

		if ( empty($term_id) )
			$term_id = $scoper->data_sources->get_from_http_post('id', $tx_src_name);
		
		$saved_terms[$taxonomy][$term_id] = 1;
		
		// Determine whether this object is new (first time this RS filter has run for it, though the object may already be inserted into db)
		$last_parent = 0;
		
		$last_parents = get_option( "scoper_last_{$taxonomy}_parents" );
		if ( ! is_array($last_parents) )
			$last_parents = array();
		
		if ( ! isset($last_parents[$term_id]) ) {
			$is_new_term = true;
			$last_parents = array();
		} else
			$is_new_term = false;
		
		if ( isset( $last_parents[$term_id] ) )
			$last_parent = $last_parents[$term_id];

		if ( ($set_parent != $last_parent) && ($set_parent || $last_parent) ) {
			$last_parents[$term_id] = $set_parent;
			update_option( "scoper_last_{$taxonomy}_parents", $last_parents);
		}
		
		$roles_customized = false;
		if ( ! $is_new_term )
			if ( $custom_role_objects = get_option( "scoper_custom_{$taxonomy}" ) )
				$roles_customized = isset( $custom_role_objects[$term_id] );
				
		// Inherit parent roles / restrictions, but only for new terms, 
		// or if a new parent is set and no roles have been manually assigned to this term
		if ( $is_new_term || ( ! $roles_customized && ($set_parent != $last_parent) ) ) {
			// apply default roles for new term
			if ( $is_new_term )
				scoper_inherit_parent_roles($term_id, TERM_SCOPE_RS, $taxonomy, 0);
			else {
				$args = array( 'inherited_only' => true, 'clear_propagated' => true );
				ScoperAdminLib::clear_restrictions(TERM_SCOPE_RS, $taxonomy, $term_id, $args);
				ScoperAdminLib::clear_roles(TERM_SCOPE_RS, $taxonomy, $term_id, $args);
			}

			// apply propagating roles,restrictions from specific parent
			if ( $set_parent ) {
				scoper_inherit_parent_roles($term_id, TERM_SCOPE_RS, $taxonomy, $set_parent);
				scoper_inherit_parent_restrictions($term_id, TERM_SCOPE_RS, $taxonomy, $set_parent);
			}
		} // endif new parent selection (or new object)
		
		scoper_term_cache_flush();
		scoper_flush_roles_cache(TERM_SCOPE_RS, '', '', $taxonomy);
		
		delete_option("{$taxonomy}_children");
		delete_option("{$taxonomy}_children_rs");
		delete_option("{$taxonomy}_ancestors_rs");
	}
	
	
function scoper_get_parent_restrictions($obj_or_term_id, $scope, $src_or_tx_name, $parent_id) {
	global $wpdb;
		
	// Since this is a new object, propagate restrictions from parent (if any are marked for propagation)
	$qry = "SELECT * FROM $wpdb->role_scope_rs WHERE topic = '$scope' AND require_for IN ('children', 'both') AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id = '$parent_id' ORDER BY role_type, role_name";
	$results = scoper_get_results($qry);
	return $results;
}

function scoper_inherit_parent_restrictions($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '', $parent_restrictions = '') {
	global $scoper;

	if ( ! $parent_restrictions )
		$parent_restrictions = scoper_get_parent_restrictions($obj_or_term_id, $scope, $src_or_tx_name, $parent_id); 
	
	if ( $parent_restrictions ) {
		$role_assigner = init_role_assigner();

		if ( OBJECT_SCOPE_RS == $scope )
			$role_defs = $scoper->role_defs->get_matching('rs', $src_or_tx_name, $object_type);
		else
			$role_defs = $scoper->role_defs->get_all();
		
		foreach ( $parent_restrictions as $row ) {
			$role_handle = scoper_get_role_handle($row->role_name, $row->role_type);
			if ( isset($role_defs[$role_handle]) ) {
				$inherited_from = ( $row->obj_or_term_id ) ? $row->requirement_id : 0;
			
				$args = array ( 'is_auto_insertion' => true, 'inherited_from' => $inherited_from );
				
				$role_assigner->insert_role_restrictions ($scope, $row->max_scope, $role_handle, $src_or_tx_name, $obj_or_term_id, 'both', $row->requirement_id, $args);
				$did_insert = true;	
			}
		}
		
		if ( ! empty($did_insert) )
			$role_assigner->role_restriction_aftermath( $scope );
	}
}

function scoper_get_parent_roles($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '') {
	global $wpdb, $scoper;

	$role_clause = '';
		
	if ( ! $parent_id && (OBJECT_SCOPE_RS == $scope) ) {
		// for default roles, need to distinguish between otype-specific roles 
		// (note: this only works w/ RS role type. Default object roles are disabled for WP role type because we'd be stuck assigning all default roles to both post & page.)
		$src = $scoper->data_sources->get($src_or_tx_name);
		if ( ! empty($src->cols->type) ) {
			if ( ! $object_type )
				$object_type = cr_find_object_type($src_name, $object_id);
				
			if ( $object_type ) {
				$role_defs = $scoper->role_defs->get_matching('rs', $src_or_tx_name, $object_type);
				if ( $role_names = scoper_role_handles_to_names( array_keys($role_defs) ) )
					$role_clause = "AND role_type = 'rs' AND role_name IN ('" . implode("', '", $role_names) . "')";
			}
		}
	}
	
	// Since this is a new object, propagate roles from parent (if any are marked for propagation)
	$qry = "SELECT * FROM $wpdb->user2role2object_rs WHERE scope = '$scope' AND assign_for IN ('children', 'both') $role_clause AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id = '$parent_id' ORDER BY role_type, role_name";
	$results = scoper_get_results($qry);
	return $results;
}

function scoper_inherit_parent_roles($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type = '', $parent_roles = '') {
	global $scoper;

	if ( ! $parent_roles )
		$parent_roles = scoper_get_parent_roles($obj_or_term_id, $scope, $src_or_tx_name, $parent_id, $object_type); 

	if ( $parent_roles ) {
		$role_assigner = init_role_assigner();
		
		if ( OBJECT_SCOPE_RS == $scope )
			$role_defs = $scoper->role_defs->get_matching('rs', $src_or_tx_name, $object_type);
		else
			$role_defs = $scoper->role_defs->get_all();
			
		$role_handles = array_keys($role_defs);
		
		$role_bases = array();
		if ( GROUP_ROLES_RS )
			$role_bases []= ROLE_BASIS_GROUPS;
		if ( USER_ROLES_RS )
			$role_bases []= ROLE_BASIS_USER;
		
		foreach ( $role_bases as $role_basis ) {
			$col_ug_id = ( ROLE_BASIS_GROUPS == $role_basis ) ? 'group_id' : 'user_id';

			foreach ( $role_handles as $role_handle ) {
				$agents = array();
				$inherited_from = array();
				
				$role_duration_per_agent = array();
				$content_date_limits_per_agent = array();
				
				foreach ( $parent_roles as $row ) {
					$ug_id = $row->$col_ug_id;
					$row_role_handle = scoper_get_role_handle($row->role_name, $row->role_type);
					if ( $ug_id && ($row_role_handle == $role_handle) ) {

						$agents[$ug_id] = 'both';
					
						// Default roles for new objects are stored as direct assignments with no inherited_from setting.
						// 1) to prevent them from being cleared when page parent is changed with no custom role settings in place
						// 2) to prevent them from being cleared when the default for new pages is changed
						if ( $row->obj_or_term_id )
							$inherited_from[$ug_id] = $row->assignment_id;
							
						$role_duration_per_agent[$ug_id] = (object) array( 'date_limited' => $row->date_limited, 'start_date_gmt' => $row->start_date_gmt, 'end_date_gmt' => $row->end_date_gmt );
						$content_date_limits_per_agent[$ug_id] = (object) array( 'content_date_limited' => $row->content_date_limited, 'content_min_date_gmt' => $row->content_min_date_gmt, 'content_max_date_gmt' => $row->content_max_date_gmt );
					}
				}
				
				if ( $agents ) {
					$args = array ( 'is_auto_insertion' => true, 'inherited_from' => $inherited_from, 'role_duration_per_agent' => $role_duration_per_agent, 'content_date_limits_per_agent' => $content_date_limits_per_agent );
					$role_assigner->insert_role_assignments ($scope, $role_handle, $src_or_tx_name, $obj_or_term_id, $col_ug_id, $agents, array(), $args);
				}
			}
		}
	}
}

function cr_get_posted_object_terms( $taxonomy ) {
	if ( defined('XMLRPC_REQUEST') ) {
		require_once( dirname(__FILE__).'/filters-admin-xmlrpc_rs.php' );
		return _rs_get_posted_xmlrpc_terms( $taxonomy );
	}

	if ( 'category' == $taxonomy ) {
		if ( ! empty($_POST['post_category']) )
			return $_POST['post_category'];
			
	} elseif( 'post_tag' == $taxonomy ) {
		if ( ! empty($_POST['tags_input']) )
			return $_POST['tags_input'];

	} else {
		if ( $var = $GLOBALS['scoper']->taxonomies->member_property( $taxonomy, 'object_terms_post_var' ) ) {
			if ( isset( $_POST[$var] ) )
				return $_POST[$var];
		} elseif ( ! empty($_POST['tax_input'][$taxonomy]) ) {
 			return $_POST['tax_input'][$taxonomy];
		}
	}
		
	return array();
}
	
?>