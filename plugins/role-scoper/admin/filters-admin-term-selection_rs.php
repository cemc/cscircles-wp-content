<?php

	// Removes terms for which the user has edit cap, but not edit_[status] cap
	// If the removed terms are already stored to the post (by a user who does have edit_[status] cap), they will be reinstated by reinstate_hidden_terms
	function scoper_filter_terms_for_status($taxonomy, $selected_terms, &$user_terms, $args = array() ) {
		if ( defined( 'DISABLE_QUERYFILTERS_RS' ) ) // || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) )
			return $selected_terms;
			
		global $scoper;
			
		$defaults = array( 'object_id' => 0, 'object_type' => '', 'status' => '' );
		$args = array_merge( $defaults, $args );
		extract( $args );

		if ( ! $tx = $scoper->taxonomies->get($taxonomy) )
			return $selected_terms;
		
		if ( ! $src = $scoper->data_sources->get($tx->object_source) )
			return $selected_terms;
			
		if ( ! isset($src->statuses) || (count($src->statuses) < 2) )
			return $selected_terms;
			
		if ( ! $object_id )	
			$object_id = scoper_get_object_id( $src->name );
		
		if ( ! $status ) {
			// determine current post status
			if ( ! $status = $scoper->data_sources->get_from_http_post('status', $src) ) {
				if ( $object_id )
					$status = $scoper->data_sources->get_from_db('status', $src, $object_id);
			}
		}

		if ( ! $object_type ) {			
			if ( ! $object_type = cr_find_object_type( $src->name, $object_id ) ) {
				if ( defined( 'XMLRPC_REQUEST' ) )
					$object_type = 'post';	// default to post type for new XML-RPC insertions, pending better API 
				else
					return $selected_terms;
			}
		}

		if ( 'auto-draft' == $status )
			$status = 'draft';

		// make sure _others caps are required only for objects current user doesn't own
		$base_caps_only = true;
		if ( $object_id && ! empty($src->cols->owner) ) {
			$col_owner = $src->cols->owner;
			if ( $object = $scoper->data_sources->get_object($src->name, $object_id) )
				if ( ! empty($object->$col_owner) && ( $object->$col_owner != $GLOBALS['current_user']->ID) )
					$base_caps_only = false;
		}

		if ( ! $reqd_caps = cr_get_reqd_caps( $src->name, OP_EDIT_RS, $object_type, $status, $base_caps_only ) )
			return $selected_terms;

		$qualifying_roles = $scoper->role_defs->qualify_roles( $reqd_caps );
	
		if ( $qualifying_term_assigner_roles = $scoper->role_defs->qualify_roles( array( "assign_$taxonomy" ) ) ) {
			$qualifying_roles = array_merge( $qualifying_roles, $qualifying_term_assigner_roles );
		}
		
		$user_terms = $scoper->qualify_terms_daterange( $reqd_caps, $taxonomy, $qualifying_roles );
		
		foreach ( array_keys($user_terms) as $date_key ) {
			$date_clause = '';
			
			if ( $date_key && is_serialized($date_key) ) {
				// Check stored post date against any role date limits associated whith this set of terms (if not stored, check current date)
				
				$content_date_limits = unserialize($date_key);
				
				$post_date_gmt = ( $object_id ) ? $scoper->data_sources->get_from_db('date', $src, $object_id) : 0;
				
				if ( ! $post_date_gmt )
					$post_date_gmt = agp_time_gmt();

				if ( ( $post_date_gmt < $content_date_limits->content_min_date_gmt ) || ( $post_date_gmt > $content_date_limits->content_max_date_gmt ) )
					unset( $user_terms[$date_key] );
			}
		}
		
		$user_terms = agp_array_flatten( $user_terms );
		$selected_terms = array_intersect($selected_terms, $user_terms);

		return $selected_terms;
	}
?>