<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
class Role_Usage_RS {
	var $checked_ids = array();
	
	var $restricted_ids = array();
	var $objscoped_ids = array();
	var $termscoped_ids = array();
	var $any_restricted_terms;
	var $any_restricted_objects;
	
	var $have_objrole_ids = array();
	var $have_termrole_ids = array();
	
	function Role_Usage_RS() {
		add_filter( 'posts_request', array( $this, 'clear_checked_ids' ) );	
	}
	
	function clear_checked_ids($query) {
		$this->checked_ids = array();
		return $query;
	}
	
	function determine_role_usage_rs( $src_name = 'post', $listed_ids = '' ) {
		global $scoper, $wpdb;

		if ( 'post' != $src_name )
			return;
		
		if ( empty($listed_ids) ) {
			if ( ! empty($scoper->listed_ids[$src_name]) )
				$listed_ids = $scoper->listed_ids[$src_name];
			else
				return;
		}

		if ( empty($this->checked_ids[$src_name]) )
			$this->checked_ids[$src_name] = array();
		else {
			if ( ! array_diff_key( $this->checked_ids[$src_name], $listed_ids ) )
				return;
		}
		
		$this->checked_ids[$src_name] = $this->checked_ids[$src_name] + $listed_ids;
		
		$src = $scoper->data_sources->get($src_name);
		$col_id = $src->cols->id;
		$col_type = ( isset($src->cols->type) ) ? $src->cols->type : '';
		
		if ( $viewing_object_type = cr_find_post_type() )
			$object_types = (array) $viewing_object_type;
		else
			$object_types = array_diff_key( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );

		// For now, only determine restricted posts if using RS role type.  
		// Backing this out will be more convoluted for WP role type; may need to just list which roles are restricted rather than trying to give an Restricted Read/Edit summary		
		$roles = array();
		if ( is_admin() ) {
			foreach ( $object_types as $_post_type ) {
				$roles["edit"][$_post_type] = array( "publish" => "rs_{$_post_type}_editor", "private" => "rs_{$_post_type}_editor", "draft" => "rs_{$_post_type}_contributor", "pending" => "rs_{$_post_type}_contributor", "future" => "rs_{$_post_type}_editor", "trash" => "rs_{$_post_type}_editor" );
				$roles["read"][$_post_type] = array( "publish" => "rs_{$_post_type}_reader", "private" => "rs_private_{$_post_type}_reader", "draft" => "rs_{$_post_type}_reader", "pending" => "rs_{$_post_type}_reader", "future" => "rs_{$_post_type}_reader", "trash" => "rs_{$_post_type}_editor" );
			}
		} else {
			foreach ( $object_types as $_post_type )
				$roles["read"][$_post_type] = array( "publish" => "rs_{$_post_type}_reader", "private" => "rs_private_{$_post_type}_reader" );
		}

		// which of these results ignore blog role assignments?
		$uses_taxonomies = scoper_get_taxonomy_usage( $src_name, $object_types );
		
		if ( ! empty( $uses_taxonomies ) ) {
			
			foreach ($uses_taxonomies as $taxonomy) {

				$tx_object_types = $object_types;
				
				foreach ( $tx_object_types as $key => $object_type ) { // ignore term restrictions / roles for object types which have them disabled
					$_use_term_roles = scoper_get_otype_option( 'use_term_roles', $src_name, $object_type );
					if( empty( $_use_term_roles[$taxonomy] ) )
						unset( $tx_object_types[$key] );
				}
					
				if ( ! $tx_object_types )
					continue;
					
				if ( ! $scoper->taxonomies->is_member($taxonomy) )
					continue;
					
				$qvars = $scoper->taxonomies->get_terms_query_vars($taxonomy);
				$term_join = " INNER JOIN {$qvars->term->table} {$qvars->term->as} ON {$src->table}.{$src->cols->id} = {$qvars->term->alias}.{$qvars->term->col_obj_id} ";
					
				// ======== Log term restrictions ========
				//
				if ( $scoper->taxonomies->member_property($taxonomy, 'requires_term') ) {
					
					if ( $strict_terms = $scoper->get_restrictions(TERM_SCOPE_RS, $taxonomy ) )
						$this->any_restricted_terms = true;

					$all_terms = $scoper->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS);

					foreach ( array_keys($roles) as $op_type ) {
						$status_where = array();
						
						foreach ( $tx_object_types as $object_type) {
							$term_clauses = array();

							foreach ( $roles[$op_type][$object_type] as $status => $check_role ) {
								if ( isset($strict_terms['restrictions'][$check_role]) && is_array($strict_terms['restrictions'][$check_role]) )
									$this_strict_terms = array_keys( $strict_terms['restrictions'][$check_role] );

								elseif ( isset($strict_terms['unrestrictions'][$check_role]) && is_array($strict_terms['unrestrictions'][$check_role]) )
									$this_strict_terms = array_diff($all_terms, array_keys( $strict_terms['unrestrictions'][$check_role] ) );	
								else
									$this_strict_terms = array();
									
								if ( ! $this_strict_terms ) {  // no terms in this taxonomy have restricted roles
									$term_clauses[$status] = '1=2';
	
								} elseif ( count($this_strict_terms) < count($all_terms) ) {  // some (but not all) terms in this taxonomy honor blog-wide assignment of the pertinent role
									$term_clauses[$status] = " {$qvars->term->alias}.{$qvars->term->col_id} IN ('" . implode("', '", $this_strict_terms) . "')";
								} else
									$term_clauses[$status] = '1=1';
									
								if ( isset($term_clauses[$status]) )
									$status_where[$object_type][$status] = " {$src->cols->status} = '$status' AND ( $term_clauses[$status] ) ";
											
							} // end foreach statuses
	
							if ( isset($status_where[$object_type]) ) // object_type='type_val' AND ( (status 1 clause) OR (status 2 clause) ...
								$status_where[$object_type] = " {$src->cols->type} = '$object_type' AND ( " . agp_implode(' ) OR ( ', $status_where[$object_type], ' ( ', ' ) ') . " )";
						} // end foreach tx_object_types
	
						// NOTE: we are querying for posts/pages which HAVE restrictions that apply to their current post_status
						//
						if ( $status_where ) {		// (object type 1 clause) OR (object type 2 clause) ...
							$where = ' AND (' . agp_implode(' ) OR ( ', $status_where, ' ( ', ' ) ') . ' )';
							$where .= " AND {$src->table}.$col_id IN ('" . implode( "', '", array_keys($listed_ids) ) . "')";
							
							$query = "SELECT DISTINCT $col_id FROM $src->table $term_join WHERE 1=1 $where";

							if ( isset( $query_results[$query] ) )
								$restricted_ids = $query_results[$query];
							else {
								$restricted_ids = scoper_get_col($query);
								$query_results[$query] = $restricted_ids;
							}
							
							foreach ( $restricted_ids as $id ) {
								$this->termscoped_ids[$src_name][$id][$op_type] = true;
								$this->restricted_ids[$src_name][$id][$op_type] = true;
							}
						}
						
					} // end foreach op_type (read/edit)
				} // end term restrictions logging
				
				
				// ======== Log term roles ========
				//
				if ( is_admin() && ! empty($qvars) ) {
					if ( $src_roles = $scoper->role_defs->get_matching('rs', 'post', $tx_object_types) ) {
						$otype_role_names = array();
						foreach ( array_keys($src_roles) as $role_handle )
							$otype_role_names []= $src_roles[$role_handle]->name;

						
							
						$role_clause = "AND uro.role_name IN ('" . implode("', '", $otype_role_names) . "')";

						$join_assigned = $term_join . " INNER JOIN $wpdb->user2role2object_rs AS uro ON uro.obj_or_term_id = {$qvars->term->alias}.{$qvars->term->col_id}"
												. " AND uro.scope = 'term' AND uro.role_type = 'rs' $role_clause AND uro.src_or_tx_name = '$taxonomy'";

						$where = " AND {$src->table}.$col_id IN ('" . implode("', '", array_keys($listed_ids)) . "')";
	
						$query = "SELECT DISTINCT $col_id, uro.role_name FROM $src->table $join_assigned WHERE 1=1 $where";

						$role_results = scoper_get_results($query);

						foreach ( $role_results as $row ) {
							$role_handle = scoper_get_role_handle($row->role_name, 'rs');
							$this->have_termrole_ids[$src_name][$row->$col_id][$role_handle] = true;
						}
					}
				} // end term roles logging
	
			} // end foreach of this data source's taxonomies
		} // endif this data source uses taxonomies
		

		
		// which of these results ignore blog AND term role assignments?
		if ( $objscope_objects = $scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name) )
			$this->any_restricted_objects = true;

		foreach ( array_keys($roles) as $op_type ) {
		
			foreach ( $object_types as $object_type) {
				if ( ! scoper_get_otype_option('use_object_roles', $src_name, $object_type) )
					continue;

				if ( is_array($roles[$op_type][$object_type]) ) {
					foreach ( array_keys($listed_ids) as $id ) {
						foreach ( $roles[$op_type][$object_type] as $check_role ) {
							// If a restriction is set for this object and role, 
							// OR if the role is default-restricted with no unrestriction for this object...
							if ( isset($objscope_objects['restrictions'][$check_role][$id])
							|| ( isset($objscope_objects['unrestrictions'][$check_role]) && is_array($objscope_objects['unrestrictions'][$check_role]) && ! isset($objscope_objects['unrestrictions'][$check_role][$id]) ) ) {
								$this->objscoped_ids[$src_name][$id][$op_type] = true;
								$this->restricted_ids[$src_name][$id][$op_type] = true;
							}
						}
					} //end foreach listed ids
				} // endif any applicable roles defined
				
			} // end forach object type
		} // end foreach op_type (read/edit)

	
		// query for object role assignments
		if ( is_admin() ) {
			if ( $scoper->get_applied_object_roles() ) {
				//$this->any_object_roles = true;
				
				$join_assigned = " INNER JOIN $wpdb->user2role2object_rs AS uro ON uro.obj_or_term_id = {$src->table}.$col_id"
										. " AND uro.src_or_tx_name = '$src_name' AND uro.scope = 'object' AND uro.role_type = 'rs'";
				
				$where = " AND {$src->table}.$col_id IN ('" . implode("', '", array_keys($listed_ids)) . "')";
				
				$query = "SELECT DISTINCT $col_id, uro.role_name FROM $src->table $join_assigned WHERE 1=1 $where";
				
				$role_results = scoper_get_results($query);

				foreach ( $role_results as $row ) {
					$role_handle = scoper_get_role_handle($row->role_name, 'rs');
					$this->have_objrole_ids[$src_name][$row->$col_id][$role_handle] = true;
					//$this->any_object_roles = true;
				}
			}
		}
	}

} // end class 
?>