<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperRoleAssigner
{
	var $scoper;
	var $scoper_admin;
	
	function ScoperRoleAssigner() {
		$this->scoper =& $GLOBALS['scoper'];
		$this->scoper_admin =& $GLOBALS['scoper_admin'];
	}
	
	function user_has_role_in_term($role_handle, $taxonomy, $term_id, $user = '', $args = '') {
		if ( empty( $this->scoper->role_defs->role_caps[$role_handle] ) )
			return false;

		if ( $terms = $this->scoper->qualify_terms( array_keys($this->scoper->role_defs->role_caps[$role_handle]), $taxonomy, '', compact( 'user' ) ) )  // qualify_terms result is memcached
			return in_array( $term_id, $terms );
	}
	
	function _validate_assigner_roles($scope, $src_or_tx_name, $item_id, $roles) {
		if ( ! $item_id && ! is_user_administrator_rs() )
			return false;
		
		$user_has_role = array();
		if ( TERM_SCOPE_RS == $scope ) {
				foreach ( array_keys($roles) as $role_handle ) {
					$role_attributes = $this->scoper->role_defs->get_role_attributes($role_handle);
					$args = array( 'src_name' => $role_attributes->src_name, 'object_type' => $role_attributes->object_type );
					$user_has_role[$role_handle] = $this->user_has_role_in_term( $role_handle, $src_or_tx_name, $item_id, $args);
				}
		} else {
			if ( $require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only') ) {
				global $current_user;
			
				$is_user_administrator = is_user_administrator_rs();
				$is_content_administrator = is_content_administrator_rs();
			}
				
			foreach ( array_keys($roles) as $role_handle ) {
				// a user must have a blog-wide edit cap to modify editing role assignments (even if they have Editor role assigned for some current object)
				if ( $require_blogwide_editor ) {
					
					if ( ! $is_user_administrator && ( 'admin' == $require_blogwide_editor ) ) {
						$user_has_role[$role_handle] = false;
						continue;	
					}
					
					if ( ! $is_content_administrator && ( 'admin_content' == $require_blogwide_editor ) ) {
						$user_has_role[$role_handle] = false;
						continue;	
					}

					$src_name = $this->scoper->role_defs->member_property($role_handle, 'src_name');
					$object_type = $this->scoper->role_defs->member_property($role_handle, 'object_type');

					static $can_edit_blogwide;

					if ( ! isset($can_edit_blogwide) )
						$can_edit_blogwide = array();
						
					if ( ! isset($can_edit_blogwide[$src_name][$object_type]) )
						$can_edit_blogwide[$src_name][$object_type] = $this->scoper->user_can_edit_blogwide($src_name, $object_type, array( 'require_others_cap' => true ) );
		
					if ( ! $can_edit_blogwide[$src_name][$object_type] )  {
						$user_has_role[$role_handle] = false;
						continue;
					}
				}

				if ( ! empty( $this->scoper->role_defs->role_caps[$role_handle] ) )
					$user_has_role[$role_handle] = cr_user_can( array_keys($this->scoper->role_defs->role_caps[$role_handle]), $item_id );
			}
		}
		
		return $user_has_role;
	}
	
	function _compare_role_settings($assign_for, $role_assignment, &$delete_assignments, &$update_assign_for, &$update_role_duration, &$update_content_date_limits, $set_role_duration = '', $set_content_date_limits = '', $skip_assignfor_comparison = false ) {
		$retval = array( 'role_change' => false, 'unset' => false );
		$assignment_id = 0;
		
		if ( REMOVE_ASSIGNMENT_RS == $assign_for ) {
			// since the role is being removed for this user/group, don't insert it
			$retval['unset'] = true;

			if ( ! empty($role_assignment['assignment_id']) ) {
				$assignment_id = $role_assignment['assignment_id'];
				$delete_assignments [$assignment_id] = true;

				$retval['role_change'] = true;
			}
		} else {
			if ( ! empty($role_assignment) ) {
				// no need for any insertion for this entity if a record already exists
				// (will still consider update and possibly insert roles for children)
				$retval['unset'] = true;
				
				$assignment_id = $role_assignment['assignment_id'];
				
				if ( ! $skip_assignfor_comparison ) {
					// If the currently stored assignment has a different 'assign_for' setting, update the record.
					// If the currently stored assignment was inherited, convert it to a direct assignment.
					if ( ($role_assignment['assign_for'] != $assign_for) || ($role_assignment['inherited_from'] != '0') ) {
						$update_assign_for[$assign_for] []= $role_assignment['assignment_id'];
						$retval['role_change'] = true;
					} //endif assign_for changed
				}
					
				if ( $set_role_duration ) {
					if ( ($role_assignment['date_limited'] != $set_role_duration->date_limited) || ($role_assignment['start_date_gmt'] != $set_role_duration->start_date_gmt) || ($role_assignment['end_date_gmt'] != $set_role_duration->end_date_gmt) ) {
						$update_role_duration []= $role_assignment['assignment_id'];
						$retval['role_change'] = true;
					} //endif role duration changed
				}
				
				if ( $set_content_date_limits ) {
					if ( ($role_assignment['content_date_limited'] != $set_content_date_limits->content_date_limited) || ($role_assignment['content_min_date_gmt'] != $set_content_date_limits->content_min_date_gmt) || ($role_assignment['content_max_date_gmt'] != $set_content_date_limits->content_max_date_gmt) ) {
						$update_content_date_limits []= $role_assignment['assignment_id'];
						$retval['role_change'] = true;
					} //endif role duration changed
				}
					
			} else //endif any assignment currently stored for this user/group
				$retval['role_change'] = true;
			
			// if assign_for was changed from 'entity' to 'children' or 'both', need to insert roles for children
			if ( ($assign_for == ASSIGN_FOR_CHILDREN_RS) || ($assign_for == ASSIGN_FOR_BOTH_RS) ) {
				if ( empty($role_assignment) || ( ASSIGN_FOR_ENTITY_RS == $role_assignment['assign_for'] ) ) {
					$retval['new_propagation'] = ( $assignment_id ) ? $assignment_id : true;
					$retval['role_change'] = true;
				}
			}
		}
		
		return $retval;
	}
	
	function assign_roles($scope, $src_or_tx_name, $item_id, $roles, $role_basis = ROLE_BASIS_USER, $args = array() ) {
		$defaults = array( 'implicit_removal' => false, 'is_auto_insertion' => false, 'force_flush' => false, 'set_role_duration' => '', 'set_content_date_limits' => '', 'user_has_role' => array() );
		$args = array_merge($defaults, (array) $args);
		extract($args);
		
		global $wpdb;

		$col_ug_id = ( ROLE_BASIS_GROUPS == $role_basis ) ? 'group_id' : 'user_id';
		
		$is_administrator = is_administrator_rs( $src_or_tx_name, 'user' );

		$role_change_agent_ids = array();
		$delete_assignments = array();
		$propagate_agents = array();
		
		// make sure end date is never accidentally set to zero
		if ( $set_role_duration && ! $set_role_duration->end_date_gmt )
			$set_role_duration->end_date_gmt = SCOPER_MAX_DATE_STRING;
	
		if ( $set_content_date_limits && ! $set_content_date_limits->content_max_date_gmt )
			$set_content_date_limits->content_max_date_gmt = SCOPER_MAX_DATE_STRING;
			
		$ug_clause = ( ROLE_BASIS_USER == $role_basis ) ? "AND user_id > 0" : "AND group_id > 0";
				
		$qry = "SELECT $col_ug_id, assignment_id, assign_for, inherited_from, role_name, date_limited, start_date_gmt, end_date_gmt, content_date_limited, content_min_date_gmt, content_max_date_gmt FROM $wpdb->user2role2object_rs WHERE scope = '$scope' $ug_clause"
			. " AND role_type = 'rs' AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id = '$item_id'";
			
		$results = scoper_get_results($qry);

		$stored_assignments = array();
		$assignment_ids = array();

		if ( OBJECT_SCOPE_RS == $scope ) {
			$is_objscope_equiv = array();
			foreach ( $this->scoper->role_defs->get_all() as $role_handle => $role_def )
				if ( isset($role_def->objscope_equivalents) )
					foreach ( $role_def->objscope_equivalents as $equiv_role_handle )
						$is_objscope_equiv[$equiv_role_handle] = $role_handle;
		}
		
		foreach ($results as $key => $ass) {
			$role_handle = scoper_get_role_handle( $ass->role_name, 'rs' );
			
			if ( (OBJECT_SCOPE_RS == $scope) && isset($is_objscope_equiv[$role_handle]) )
				$role_handle = $is_objscope_equiv[$role_handle];
			
			$stored_assignments[$role_handle][$ass->$col_ug_id] = (array) $ass;	 // last-stored assignment for this object and user/group
			$assignment_ids[$role_handle][$ass->$col_ug_id][$ass->assignment_id] = true;	// all assignment ids for this object and user/group
		}

		if ( ! $is_administrator && empty($user_has_role[$role_handle]) )
			$user_has_role = $this->_validate_assigner_roles($scope, $src_or_tx_name, $item_id, $roles);

		foreach ( $roles as $role_handle => $agents ) {
			if ( ! $is_administrator && ! $user_has_role[$role_handle] )
				continue;

			$propagate_agents = array();
				
			$update_assign_for = array( ASSIGN_FOR_ENTITY_RS => array(), ASSIGN_FOR_CHILDREN_RS => array(), ASSIGN_FOR_BOTH_RS => array() );
			$update_role_duration = array();
			$update_content_date_limits = array();
			
			if ( $implicit_removal && isset($stored_assignments[$role_handle]) ) {
				// Stored assignments which are not included in $agents will be deleted (along with their prodigy)
				foreach ( $stored_assignments[$role_handle] as $ug_id => $ass ) {		
					if ( ! isset($agents[$ug_id]) && ! empty($ass['assignment_id']) ) {
						$assignment_id = $ass['assignment_id'];
						$delete_assignments [ $assignment_id ] = true;
					}
				}
			}

			$comparison = array();
			
			foreach ( $agents as $ug_id => $assign_for ) {
				// don't assign a role which would remove existing assignment of role the current user doesn't have 
				// (i.e. you can't change someone else from category Editor to category Reader if you are only a category Contributor)
				if ( ! $is_administrator ) {
					foreach ( $stored_assignments as $stored_role_handle => $this_stored_assignment ) {
						if ( isset($this_stored_assignment[$ug_id]) ) {
							if ( ! $user_has_role[$role_handle] ) {
								unset( $agents[$ug_id] );
								continue 2;
							}
						}
					}
				}
				
				if ( REMOVE_ASSIGNMENT_RS == $assign_for ) {
				    // If redundant role entries somehow got stored, make sure we delete them all (_compare_role_settings() does not)
					if ( ! empty( $assignment_ids[$role_handle][$ug_id] ) ) {
						$comparison['unset'] = true;

						foreach ( array_keys($assignment_ids[$role_handle][$ug_id]) as $assignment_id ) {
							$delete_assignments [$assignment_id] = true;
							$comparison['role_change'] = true;
						}
					}
				} else {
					$stored_assignment = ( isset($stored_assignments[$role_handle][$ug_id]) ) ? $stored_assignments[$role_handle][$ug_id] : array();					
					$comparison = $this->_compare_role_settings($assign_for, $stored_assignment, $delete_assignments, $update_assign_for, $update_role_duration, $update_content_date_limits, $set_role_duration, $set_content_date_limits);

					// Mark assignment for propagation to child items (But don't do so on storage of default role to root item. Default roles are only applied at item creation.)
					if ( $item_id && isset($comparison['new_propagation']) )
						$propagate_agents[$ug_id] = $comparison['new_propagation'];
				}
				
				if ( ! empty($comparison['unset']) )
					unset( $agents[$ug_id] );
					
				if ( ! empty($comparison['role_change']) )
					$role_change_agent_ids[$role_basis][$ug_id] = 1;
			} // end foreach users or groups
			
			// do this for each role prior to insert call because insert function will consider inherited_from value
			foreach ($update_assign_for as $assign_for => $this_ass_ids) {
				if ( $this_ass_ids ) {
					$id_in = "'" . implode("', '", $this_ass_ids) . "'";
					$qry = "UPDATE $wpdb->user2role2object_rs SET assign_for = '$assign_for', inherited_from = '0' WHERE assignment_id IN ($id_in)";
					scoper_query($qry);
					
					if ( 'entity' == $assign_for ) {
						// If a parent role is changed from "both" or "children" to "entity", delete propagated roles
						$qry = "DELETE FROM $wpdb->user2role2object_rs WHERE inherited_from IN ($id_in)";
						scoper_query($qry);
					}
				}
			}

			if ( $update_role_duration ) {
				$id_in = "'" . implode("', '", $update_role_duration) . "'";
				$qry = "UPDATE $wpdb->user2role2object_rs SET date_limited = '" . (int) $set_role_duration->date_limited . "'";
				
				if ( -1 != $set_role_duration->start_date_gmt )
					$qry .= ", start_date_gmt = '$set_role_duration->start_date_gmt'";
				
				if ( -1 != $set_role_duration->end_date_gmt )
					$qry .= ", end_date_gmt = '$set_role_duration->end_date_gmt'";
					
				$qry .= " WHERE assignment_id IN ($id_in)";
				scoper_query($qry);
			}
			
			if ( $update_content_date_limits ) {
				$id_in = "'" . implode("', '", $update_content_date_limits) . "'";
				$qry = "UPDATE $wpdb->user2role2object_rs SET content_date_limited = '" . (int) $set_content_date_limits->content_date_limited . "'";

				if ( -1 != $set_content_date_limits->content_min_date_gmt )
					$qry .= ", content_min_date_gmt = '$set_content_date_limits->content_min_date_gmt'";
				
				if ( -1 != $set_content_date_limits->content_max_date_gmt )
					$qry .= ", content_max_date_gmt = '$set_content_date_limits->content_max_date_gmt'";
					
				$qry .= " WHERE assignment_id IN ($id_in)";

				scoper_query($qry);
			}
			
			if ( $agents || $propagate_agents )
				$this->insert_role_assignments($scope, $role_handle, $src_or_tx_name, $item_id, $col_ug_id, $agents, $propagate_agents, $args );
		} // end foreach roles
		
		// delete assignments; flush user/group roles cache
		$this->role_assignment_aftermath( $scope, $role_basis, $role_change_agent_ids, $delete_assignments, '', $force_flush || ! empty($update_assign_for) );
	
		// possible todo: reinstate this after further testing
		//$this->delete_orphan_roles($scope, $src_or_tx_name);
	}
	
	function role_assignment_aftermath($scope, $role_basis, $role_change_agent_ids, $delete_assignments, $object_type = '', $force_flush = false) {
		global $wpdb;
		
		if ( count($delete_assignments) ) {
			// Propagated roles will be deleted only if the original progenetor goes away.  Removal of a "link" in the parent/child propagation chain has no effect.
			$id_in = "'" . implode("', '", array_keys($delete_assignments) ) . "'";
			$qry = "DELETE FROM $wpdb->user2role2object_rs WHERE assignment_id IN ($id_in) OR (inherited_from IN ($id_in) AND inherited_from != '0')";
			
			scoper_query($qry);
		}
		
		if ( count($role_change_agent_ids) || $force_flush ) {
			$role_change_user_ids = array();
		
			// just flush entire cache until selective auto-flushing can be re-verified
			wpp_cache_flush();
			
			/*
			if ( ROLE_BASIS_GROUPS == $role_basis ) {
				scoper_flush_roles_cache( $scope, ROLE_BASIS_GROUPS );
				scoper_flush_results_cache( ROLE_BASIS_GROUPS );

				// also delete corresponding combined user/group roles cache for all group members
				if ( isset($role_change_agent_ids['groups']) ) {
					foreach ( array_keys($role_change_agent_ids['groups']) as $group_id ) {
						$group_members = ScoperAdminLib::get_group_members( $group_id, COL_ID_RS );
						$role_change_user_ids = array_merge($role_change_user_ids, $group_members);
					}
				}
				
				if ( $role_change_user_ids ) {
					$role_change_user_ids = array_unique($role_change_user_ids);
					scoper_flush_roles_cache( $scope, ROLE_BASIS_USER_AND_GROUPS, $role_change_user_ids );
					scoper_flush_results_cache( ROLE_BASIS_USER_AND_GROUPS, $role_change_user_ids );
				}
			} else {
				scoper_flush_roles_cache( $scope, ROLE_BASIS_USER, array_keys($role_change_user_ids) );
				scoper_flush_results_cache( ROLE_BASIS_USER, array_keys($role_change_user_ids) );
				
				scoper_flush_roles_cache( $scope, ROLE_BASIS_USER_AND_GROUPS, array_keys($role_change_user_ids) );
				scoper_flush_results_cache( ROLE_BASIS_USER_AND_GROUPS, array_keys($role_change_user_ids) );
			}
			*/
		}
	}
	
	
	// $agent_ids[agent_id] = role assignment method ('entity', 'children' or 'both')
	// $propagate_agents[agent_id] = assignment_id for inherited_from
	function insert_role_assignments ($scope, $role_handle, $src_or_tx_name, $obj_or_term_id, $col_ug_id, $insert_agents, $propagate_agents, $args = array()) {
		$defaults = array( 'inherited_from' => array(), 'is_auto_insertion' => false, 'set_role_duration' => '', 'set_content_date_limits' => '', 'role_duration_per_agent' => '', 'content_date_limits_per_agent' => '' );  // auto_insertion arg set for role propagation from parent objects
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		global $current_user, $wpdb;
		
		$assigner_id = $current_user->ID;
		
		if ( ! $role_spec = scoper_explode_role_handle($role_handle) )
			return;

		// keep track of which objects from non-post data sources have ever had their roles/restrictions custom-edited
		if ( ! $is_auto_insertion && ( (TERM_SCOPE_RS == $scope) || ( (OBJECT_SCOPE_RS == $scope) && ('post' != $src_or_tx_name) ) ) ) {
			$custom_role_items = get_option( "scoper_custom_{$src_or_tx_name}" );

			if ( ! is_array($custom_role_items) )
				$custom_role_items = array();
		}
		
		// Before inserting a role, delete any overlooked old assignment.
		// Also delete (for the same user/group) any roles which cannot be simultaneously assigned
		if ( $role_attrib = $this->scoper->role_defs->get_role_attributes($role_handle) ) {
			$this_otype_role_defs = $this->scoper->role_defs->get_matching($role_spec->role_type, $role_attrib->src_name, $role_attrib->object_type);
			$delete_role_if_exists = array_keys($this_otype_role_defs);
			
			// need object_type for permission check when modifying propagated object roles
			$object_type = $role_attrib->object_type;
		} else {
			$delete_role_if_exists = array($role_handle);
			$object_type = '';  // probably won't be able to propagate roles if this error occurs
		}
		
		// prepare hierarchy and object type data for subsequent propagation
		if ( ! empty($propagate_agents) ) {
			if ( TERM_SCOPE_RS == $scope ) {
				if ( ! $tx = $this->scoper->taxonomies->get($src_or_tx_name) )
					return;

				$src = $this->scoper->data_sources->get( $tx->source );
				
			} elseif ( ! $src = $this->scoper->data_sources->get($src_or_tx_name) )
				return;
			
			if ( empty( $src->cols->parent ) )
				return;

			$descendant_ids = awp_query_descendant_ids( $src->table, $src->cols->id, $src->cols->parent, $obj_or_term_id);

			$remove_ids = array();
			foreach ( $descendant_ids as $id ) {
				if ( TERM_SCOPE_RS == $scope ) {
					if ( ! $this->scoper_admin->user_can_admin_terms($src_or_tx_name, $id) )
						$remove_ids []= $id;
				} else {
					if ( ! $this->scoper_admin->user_can_admin_object($src_or_tx_name, $object_type, $id) )
						$remove_ids []= $id;
				}
			}
			if ( $remove_ids )
				$descendant_ids = array_diff( $descendant_ids, $remove_ids );

			$retain_roles = $this->scoper->role_defs->add_containing_roles( array($role_handle => true), $role_spec->role_type );
			$retain_role_names = scoper_role_handles_to_names(array_keys($retain_roles));

			$role_in = "'" . implode("', '", $retain_role_names) . "'";
			$role_clause = "AND role_name IN ($role_in)";
		}
		
		
		$delete_role_in = "'" . implode("', '", scoper_role_handles_to_names( $delete_role_if_exists ) ) . "'";

		$qry_delete_base = "DELETE FROM $wpdb->user2role2object_rs"
						. " WHERE scope = '$scope' AND src_or_tx_name = '$src_or_tx_name'"
						. " AND role_type = '$role_spec->role_type' AND role_name IN ($delete_role_in)";
		
		$qry_select_base = "SELECT assignment_id FROM $wpdb->user2role2object_rs"
						. " WHERE scope = '$scope' AND src_or_tx_name = '$src_or_tx_name'"
						. " AND role_type = '$role_spec->role_type'";
						
		if ( ! empty($set_role_duration) || ! empty( $role_duration_per_agent ) )
			$duration_cols = "date_limited, start_date_gmt, end_date_gmt,";
		else
			$duration_cols = '';
			
		if ( ! empty($set_content_date_limits) || ! empty( $content_date_limits_per_agent ) )
			$content_date_cols = "content_date_limited, content_min_date_gmt, content_max_date_gmt,";
		else
			$content_date_cols = '';
				
		if ( ! empty($set_role_duration) )
			$universal_duration_vals = "'" . (int) $set_role_duration->date_limited . "', '$set_role_duration->start_date_gmt', '$set_role_duration->end_date_gmt',";
		else
			$universal_duration_vals = '';
			
		if ( ! empty($set_content_date_limits) )
			$universal_content_date_vals = "'" . (int) $set_content_date_limits->content_date_limited . "', '$set_content_date_limits->content_min_date_gmt', '$set_content_date_limits->content_max_date_gmt',";
		else
			$universal_content_date_vals = '';
					
		$duration_vals = '';
		$content_date_vals = '';
			
		$qry_insert_base = "INSERT INTO $wpdb->user2role2object_rs"
						 . " (src_or_tx_name, role_type, role_name, assigner_id, scope, $duration_cols $content_date_cols obj_or_term_id, assign_for, inherited_from, $col_ug_id)"
						 . " VALUES ('$src_or_tx_name', '$role_spec->role_type', '$role_spec->role_name', '$assigner_id', '$scope', "; // duration values, content date limit values, obj_or_term_id, propagate, inherited_from and group_id/user_id values must be appended
		
		$all_agents = $propagate_agents + $insert_agents;

		foreach ( array_keys($all_agents) as $ug_id ) {
			$assignment_id = 0;

			if ( $duration_cols ) {
				if ( $universal_duration_vals )
					$duration_vals = $universal_duration_vals;
				else {
					if ( ! empty($role_duration_per_agent[$ug_id]) ) {
						if ( '' === $role_duration_per_agent[$ug_id]->start_date_gmt )
							$role_duration_per_agent[$ug_id]->start_date_gmt = SCOPER_MIN_DATE_STRING;
							
						if ( '' === $role_duration_per_agent[$ug_id]->end_date_gmt )
							$role_duration_per_agent[$ug_id]->end_date_gmt = SCOPER_MAX_DATE_STRING;
			
						$duration_vals = "'" . (int) $role_duration_per_agent[$ug_id]->date_limited . "', '" . $role_duration_per_agent[$ug_id]->start_date_gmt . "', '" . $role_duration_per_agent[$ug_id]->end_date_gmt . "',";
					} else
						$duration_vals = "'0', '" . SCOPER_MIN_DATE_STRING . "', '" . SCOPER_MAX_DATE_STRING . "',";
				}
			}

			if ( $content_date_cols ) {
				if ( $universal_content_date_vals )
					$content_date_vals = $universal_content_date_vals;
				else {
					if ( ! empty($content_date_limits_per_agent[$ug_id]) ) {
						if ( '' === $content_date_limits_per_agent[$ug_id]->content_min_date_gmt )
							$content_date_limits_per_agent[$ug_id]->content_min_date_gmt = SCOPER_MIN_DATE_STRING;
							
						if ( '' === $content_date_limits_per_agent[$ug_id]->content_max_date_gmt )
							$content_date_limits_per_agent[$ug_id]->content_max_date_gmt = SCOPER_MAX_DATE_STRING;

						$content_date_vals = "'" . (int) $content_date_limits_per_agent[$ug_id]->content_date_limited . "', '" . $content_date_limits_per_agent[$ug_id]->content_min_date_gmt . "', '" . $content_date_limits_per_agent[$ug_id]->content_max_date_gmt . "',";
					} else
						$content_date_vals = "'0', '" . SCOPER_MIN_DATE_STRING . "', '" . SCOPER_MAX_DATE_STRING . "',";
				}
			}
			
			// won't delete any other role assignments which have date limits
			$date_clause = " AND date_limited = '0' AND start_date_gmt = '" . SCOPER_MIN_DATE_STRING . "' AND end_date_gmt = '" . SCOPER_MAX_DATE_STRING . "' AND content_date_limited = '0' AND content_min_date_gmt = '" . SCOPER_MIN_DATE_STRING . "' AND content_max_date_gmt = '" . SCOPER_MAX_DATE_STRING . "'";

			if ( isset($insert_agents[$ug_id]) ) {
				$assign_for = ( in_array( $insert_agents[$ug_id], array( 'entity', 'children', 'both' ) ) ) ? $insert_agents[$ug_id] : 'entity';  // sanity check to avoid empty assign_for value
				$this_inherited_from = ( isset($inherited_from[$ug_id]) ) ? $inherited_from[$ug_id] : 0;
				
				// don't delete other role assignments if this insertion has date limits
				if ( ! $duration_vals && ! $content_date_vals ) {
					// before inserting the role, delete any other matching or conflicting assignments this user/group has for the same object
					scoper_query( $qry_delete_base . "$date_clause AND $col_ug_id = '$ug_id' AND obj_or_term_id = '$obj_or_term_id';" );
				}

				// insert role for specified object and group(s)
				scoper_query( $qry_insert_base . " $duration_vals $content_date_vals '$obj_or_term_id', '$assign_for', '$this_inherited_from', '$ug_id')" );
				$assignment_id = (int) $wpdb->insert_id;
				
				// keep track of which objects have ever had their roles/restrictions custom-edited
				if ( ! $is_auto_insertion ) {
					if ( (OBJECT_SCOPE_RS == $scope) && ('post' == $src_or_tx_name) )
						update_post_meta($obj_or_term_id, '_scoper_custom', true);
					else
						$custom_role_items[$obj_or_term_id] = true;
				}
			}
			
			// insert role for all descendant items
			if ( isset($propagate_agents[$ug_id]) ) {
				if ( ! $assignment_id )
					$assignment_id = $propagate_agents[$ug_id];

				// note: Propagated roles will be converted to direct-assigned roles if the parent object/term is deleted.
				foreach ( $descendant_ids as $id ) {
					// Don't overwrite an explicitly assigned object role with a propagated assignment
					// unless the propagated role would be an upgrade
					if ( $direct_assignment = scoper_get_var( "$qry_select_base AND inherited_from = '0' $role_clause AND $col_ug_id = '$ug_id' AND obj_or_term_id = '$id' LIMIT 1" ) )
						continue;
					
					// don't delete other role assignments if this insertion has date limits
					if ( ! $duration_vals && ! $content_date_vals ) {
						// before inserting the role, delete any other propagated assignments this user/group has for the same object type
						scoper_query( $qry_delete_base . " $date_clause AND $col_ug_id = '$ug_id' AND obj_or_term_id = '$id'" );
					}
					
					scoper_query( $qry_insert_base . "$duration_vals $content_date_vals '$id', 'both', '$assignment_id', '$ug_id')" );
				}
			}
		}
		
		// keep track of which objects from non-post data sources have ever had their roles/restrictions custom-edited
		if ( ! empty($custom_role_items) )
			update_option( "scoper_custom_{$src_or_tx_name}", $custom_role_items );
	}
	
	function restrict_roles($scope, $src_or_tx_name, $item_id, $roles, $args = array() ) {
		$defaults = array( 'implicit_removal' => false, 'is_auto_insertion' => false, 'force_flush' => false );
		$args = array_merge($defaults, (array) $args);
		extract($args);
		
		global $wpdb;
		
		$is_administrator = is_administrator_rs($src_or_tx_name, 'user');
		
		$delete_reqs = array();
		$role_change = false;
		$default_strict_modes = array( false );
		$strict_role_in = '';

		// for object restriction, handle auto-setting of equivalent object roles ( 'post reader' for 'private post reader', 'post author' for 'post editor' ).  There is no logical distinction between these roles where a single object is concerned.
		if ( OBJECT_SCOPE_RS == $scope ) {
			foreach ( array_keys($roles) as $role_handle ) {
				$equiv_role_handles = array();
				
				if ( $objscope_equivalents = $this->scoper->role_defs->member_property($role_handle, 'objscope_equivalents') )
					foreach ( $objscope_equivalents as $equiv_role_handle )
						if ( ! isset( $roles[$equiv_role_handle] ) )	// if the equiv role was set manually, leave it alone.  This would not be normal RS behavior
							$roles[$equiv_role_handle] = $roles[$role_handle];
			}
		}
		
		if ( $item_id ) {
			$default_restrictions = $this->scoper->get_default_restrictions($scope);
			$default_strict_roles = ( ! empty($default_restrictions[$src_or_tx_name] ) ) ? array_keys($default_restrictions[$src_or_tx_name]) : array();
			
			if ( $default_strict_roles ) {
				$strict_role_in = "'" . implode("', '", scoper_role_handles_to_names($default_strict_roles) ) . "'";
				$default_strict_modes []= true;
			}
		}
		
		foreach ( $default_strict_modes as $default_strict ) {
			$stored_reqs = array();
			$req_ids = array();

			if ( $default_strict && $strict_role_in )
				$role_clause = "AND role_name IN ($strict_role_in)";
			elseif ($strict_role_in)
				$role_clause = "AND role_name NOT IN ($strict_role_in)";
			else
				$role_clause = '';
			
			// IMPORTANT: max_scope value determines whether we are inserting / updating RESTRICTIONS or UNRESTRICTIONS
			if ( TERM_SCOPE_RS == $scope )
				$query_max_scope = ( $default_strict ) ? 'blog' : 'term';  // note: max_scope='object' entries are treated as separate, overriding requirements
			else
				$query_max_scope = ( $default_strict ) ? 'blog' : 'object'; // Storage of 'blog' max_scope as object restriction does not eliminate any term restrictions.  It merely indicates, for data sources that are default strict, that this object does not restrict roles
				
			$qry = "SELECT requirement_id AS assignment_id, require_for AS assign_for, inherited_from, role_name FROM $wpdb->role_scope_rs WHERE topic = '$scope' AND max_scope = '$query_max_scope'"
				. " AND src_or_tx_name = '$src_or_tx_name' AND obj_or_term_id = '$item_id' AND role_type = 'rs' $role_clause";

			if ( $results = scoper_get_results($qry) ) {
				foreach ($results as $key => $req) {
					$role_handle = 'rs_' . $req->role_name;
					
					if ( (OBJECT_SCOPE_RS == $scope) && isset($is_objscope_equiv[$role_handle]) )
						$role_handle = $is_objscope_equiv[$role_handle];
					
					$stored_reqs[$role_handle] = array( 'assignment_id' => $req->assignment_id, 'assign_for' => $req->assign_for, 'inherited_from' => $req->inherited_from );
					$req_ids[$role_handle][$req->assignment_id] = array();
				}
			}

			if ( ! $is_administrator )
				$user_has_role = $this->_validate_assigner_roles($scope, $src_or_tx_name, $item_id, $roles);
			
			if ( $implicit_removal ) {
				// Stored restrictions which are not mirrored in $roles will be deleted (along with their prodigy)
				foreach ( array_keys($stored_reqs) as $role_handle ) {
					$max_scope = isset($roles[$role_handle]['max_scope']) ? $roles[$role_handle]['max_scope'] : false;
					
					if ( $max_scope != $query_max_scope ) {
						$delete_reqs = $delete_reqs + $req_ids[$role_handle];
					}
				}
			}

			foreach ( $roles as $role_handle => $setting ) {
				if ( ! $is_administrator && empty( $user_has_role[$role_handle] ) )
					continue;
	
				if ( $default_strict && ! in_array($role_handle, $default_strict_roles) )
					continue;

				if ( ! $default_strict && ! empty($default_strict_roles) && in_array($role_handle, $default_strict_roles) )
					continue;
					
				$max_scope = $setting['max_scope'];
				
				if ( $max_scope != $query_max_scope )
					$require_for = REMOVE_ASSIGNMENT_RS;
				elseif ( $setting['for_item'] )
					$require_for = ( $setting['for_children'] ) ? ASSIGN_FOR_BOTH_RS : ASSIGN_FOR_ENTITY_RS;
				else
					$require_for = ( $setting['for_children'] ) ? ASSIGN_FOR_CHILDREN_RS : REMOVE_ASSIGNMENT_RS;

				$update_require_for = array( ASSIGN_FOR_ENTITY_RS => array(), ASSIGN_FOR_CHILDREN_RS => array(), ASSIGN_FOR_BOTH_RS => array() );

				$stored_req = ( isset($stored_reqs[$role_handle]) ) ? $stored_reqs[$role_handle] : array();
				
				$unused_byref_arg = '';
				$comparison = $this->_compare_role_settings($require_for, $stored_req, $delete_reqs, $update_require_for, $unused_byref_arg, $unused_byref_arg);
				
				$insert_restriction = ( $comparison['unset'] ) ? false : $require_for;
				
				// Mark assignment for propagation to child items (But don't do so on storage of default restriction to root item. Default restrictions are only applied at item creation.)
				$propagate_restriction =  ( $item_id && isset($comparison['new_propagation']) ) ? $comparison['new_propagation'] : '';
				
				if ( $comparison['role_change'] )
					$role_change = true;
				
				if ( ! empty($req_ids[$role_handle]) ) {
					$id_in = "'" . implode("', '", array_keys($req_ids[$role_handle]) ) . "'";
					
					// do this for each role prior to insert call because insert function will consider inherited_from value
					foreach ($update_require_for as $require_for => $this_ass_ids) {
						if ( $this_ass_ids ) {
							$id_in = "'" . implode("', '", $this_ass_ids) . "'";
							$qry = "UPDATE $wpdb->role_scope_rs SET require_for = '$require_for', inherited_from = '0' WHERE requirement_id IN ($id_in)";
							scoper_query($qry);
							
							if ( 'entity' == $require_for ) {
								// If a parent restriction is changed from "both" or "children" to "entity", delete propagated restrictions
								$qry = "DELETE FROM $wpdb->role_scope_rs WHERE inherited_from IN ($id_in)";
								scoper_query($qry);
							}
						}
					}
				}

				if ( $insert_restriction || $propagate_restriction )
					$this->insert_role_restrictions($scope, $max_scope, $role_handle, $src_or_tx_name, $item_id, $insert_restriction, $propagate_restriction, $args );
			} // end foreach roles
		}
		
		// delete assignments; flush user,group results cache
		if ( $role_change || ! empty($delete_reqs) || $update_require_for || $force_flush ) {
			$this->role_restriction_aftermath($scope, $delete_reqs);
			
			if ( ! $item_id )
				$this->scoper->default_restrictions = array();
		}
		
		// possible TODO: reinstate this after further testing
		//$this->delete_orphan_restrictions($scope, $src_or_tx_name);
	}
	
	function role_restriction_aftermath($scope, $delete_restrictions = '') {
		global $wpdb;
		
		if ( is_array($delete_restrictions) && count($delete_restrictions) ) {
			// Propagated roles will be deleted only if the original progenetor goes away.  Removal of a "link" in the parent/child propagation chain has no effect.
			$id_in = "'" . implode("', '", array_keys($delete_restrictions) ) . "'";
			$qry = "DELETE FROM $wpdb->role_scope_rs WHERE requirement_id IN ($id_in) OR (inherited_from IN ($id_in) AND inherited_from != '0')";
			
			scoper_query($qry);
		}

		// just flush entire cache until selective auto-flushing can be re-verified
		wpp_cache_flush();
		
		/*
		scoper_flush_restriction_cache( $scope );
		scoper_flush_results_cache();
		*/
	}
	
	// $insert_restriction = require_for value for insertion ('entity', 'children' or 'both')
	// $propagate_from_req_id = requirement_id for inherited_from
	function insert_role_restrictions ($topic, $max_scope, $role_handle, $src_or_tx_name, $obj_or_term_id, $insert_restriction, $propagate_from_req_id, $args = array()) {
		$defaults = array( 'inherited_from' => 0, 'is_auto_insertion' => false );  // auto_insertion arg set for restriction propagation from parent objects
		$args = array_merge( $defaults, (array) $args );
		extract($args);
	
		global $current_user, $wpdb;

		if ( ! $role_spec = scoper_explode_role_handle($role_handle) )
			return;
		
		// keep track of which objects from non-post data sources have ever had their roles/restrictions custom-edited
		if ( ! $is_auto_insertion && ( (TERM_SCOPE_RS == $max_scope) || ( (OBJECT_SCOPE_RS == $max_scope) && ('post' != $src_or_tx_name) ) ) ) {
			$custom_role_items = get_option( "scoper_custom_{$src_or_tx_name}" );

			if ( ! is_array($custom_role_items) )
				$custom_role_items = array();
		}
			
		// need object_type for permission check when modifying propagated object roles
		if ( OBJECT_SCOPE_RS == $topic ) {
			if ( $role_attrib = $this->scoper->role_defs->get_role_attributes($role_handle) )
				$object_type = $role_attrib->object_type;
			else
				$object_type = '';  // probably won't be able to propagate roles if this error occurs
		}
		
		// prepare hierarchy and object type data for subsequent propagation
		if ( $propagate_from_req_id ) {
			if ( TERM_SCOPE_RS == $topic ) {
				if ( ! $tx = $this->scoper->taxonomies->get($src_or_tx_name) )
					return;
				
				if ( ! $src = $this->scoper->data_sources->get($tx->source) )
					return;

			} elseif ( ! $src = $this->scoper->data_sources->get($src_or_tx_name) )
				return;
			
			if ( empty( $src->cols->parent ) )
				return;
				
			$descendant_ids = awp_query_descendant_ids( $src->table, $src->cols->id, $src->cols->parent, $obj_or_term_id);

			$remove_ids = array();
			foreach ( $descendant_ids as $id ) {
				if ( TERM_SCOPE_RS == $topic ) {
					if ( ! $this->scoper_admin->user_can_admin_terms($src_or_tx_name, $id) )
						$remove_ids []= $id;
				} else {
					if ( ! $this->scoper_admin->user_can_admin_object($src_or_tx_name, $object_type, $id) )
						$remove_ids []= $id;
				}
			}
			if ( $remove_ids )
				$descendant_ids = array_diff( $descendant_ids, $remove_ids );
		}
		
		// Before inserting a restriction, delete any overlooked old restriction.
		$qry_delete_base = "DELETE FROM $wpdb->role_scope_rs"
						. " WHERE topic = '$topic' AND max_scope = '$max_scope' AND src_or_tx_name = '$src_or_tx_name'"
						. " AND role_type = '$role_spec->role_type' AND role_name = '$role_spec->role_name'";
		
		$qry_select_base = "SELECT requirement_id AS assignment_id FROM $wpdb->role_scope_rs"
						. " WHERE topic = '$topic' AND max_scope = '$max_scope' AND src_or_tx_name = '$src_or_tx_name'"
						. " AND role_type = '$role_spec->role_type' AND role_name = '$role_spec->role_name'";
				
		$qry_insert_base = "INSERT INTO $wpdb->role_scope_rs"
						 . " (src_or_tx_name, role_type, role_name, topic, max_scope, obj_or_term_id, require_for, inherited_from)"
						 . " VALUES ('$src_or_tx_name', '$role_spec->role_type', '$role_spec->role_name', '$topic', '$max_scope',"; // obj_or_term_id, propagate, inherited_from values must be appended
		
		if ( $insert_restriction ) {
			// before inserting the role, delete any other matching or conflicting assignments this user/group has for the same object
			scoper_query( $qry_delete_base . " AND obj_or_term_id = '$obj_or_term_id';" );

			// insert role for specified object and group(s)
			scoper_query( $qry_insert_base . "'$obj_or_term_id', '$insert_restriction', '$inherited_from')" );
			$inserted_req_id = (int) $wpdb->insert_id;
			
			// keep track of which objects have ever had their roles/restrictions custom-edited
			if ( ! $is_auto_insertion ) {
				if ( (OBJECT_SCOPE_RS == $max_scope) && ('post' == $src_or_tx_name) )
					update_post_meta($obj_or_term_id, '_scoper_custom', true);
				else
					$custom_role_items[$obj_or_term_id] = true;
			}
		}
		
		// insert role for all descendant items
		if ( $propagate_from_req_id ) {
			if ( $insert_restriction )
				$propagate_from_req_id = $inserted_req_id;
				
			// note: Propagated roles will be converted to direct-assigned roles if the parent object/term is deleted.
			//		 But if the parent setting is changed without deleting old object/term, inherited roles from the old parent remain. 
			// TODO: 're-inherit parent roles' checkbox for object and term role edit UI
			foreach ( $descendant_ids as $id ) {
				// Don't overwrite an explicitly assigned object role with a propagated assignment
				if ( $direct_assignment = scoper_get_var( "$qry_select_base AND inherited_from = '0' AND obj_or_term_id = '$id' LIMIT 1" ) )
					continue;

				// before inserting the role, delete any other propagated assignments this user/group has for the same object type
				scoper_query( $qry_delete_base . " AND obj_or_term_id = '$id'" );
				
				scoper_query( $qry_insert_base . "'$id', 'both', '$propagate_from_req_id')" );
			}
		}
		
		// keep track of which objects from non-post data sources have ever had their roles/restrictions custom-edited
		if ( ! empty($custom_role_items) )
			update_option( "scoper_custom_{$src_or_tx_name}", $custom_role_items );
	}
	

} // end class ScoperRoleAssigner


function awp_query_descendant_ids( $table_name, $col_id, $col_parent, $parent_id ) {
	global $wpdb;
	
	$descendant_ids = array();
	
	// todo: abstract this
	$type_clause = ( $table_name == $wpdb->posts ) ? "AND post_type != 'revision'" : '';
	
	$query = "SELECT $col_id FROM $table_name WHERE $col_parent = '$parent_id' $type_clause";
	if ( $results = scoper_get_col($query) ) {
		foreach ( $results as $id ) {
			if ( ! in_array( $id, $descendant_ids ) ) {
				$descendant_ids []= $id;
				$next_generation = awp_query_descendant_ids($table_name, $col_id, $col_parent, $id, $descendant_ids);
				$descendant_ids = array_merge($descendant_ids, $next_generation);
			}
		}
	}
	return $descendant_ids;
}
?>