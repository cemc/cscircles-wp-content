<?php

// renamed for clarity (was get_term_children)
function &rs_get_term_descendants($requested_parent_id, $qualified_terms, $taxonomy) {
	$empty_array = array();
	
	if ( empty($qualified_terms) )
		return $empty_array;

	$term_list = array();
	
	$has_children = ScoperAncestry::get_terms_children($taxonomy);

	if  ( $requested_parent_id && ! isset($has_children[$requested_parent_id]) ) {
		return $empty_array;
	}
	
	foreach ( $qualified_terms as $term ) {
		$use_id = false;
		if ( !is_object($term) ) {
			$term = get_term_by( 'id', $term, $taxonomy); 

			if ( is_wp_error( $term ) )
				return $term;

			$use_id = true;
		}

		if ( $term->term_id == $requested_parent_id )
			continue;
		
		// if this qualified term has the requested parent, log it and all its descendants
		if ( $term->parent == $requested_parent_id ) {
			if ( $use_id )
				$descendant_list[] = $term->term_id;
			else
				$descendant_list[] = $term;

			if ( ! isset($has_children[$term->term_id]) )
				continue;
	
			if ( $descendants = rs_get_term_descendants($term->term_id, $qualified_terms, $taxonomy) )
				$descendant_list = array_merge($descendant_list, $descendants);
		}
	}
	
	return $descendant_list;
}


// Rewritten from WP core pad_term_counts to make object count reflect any user-specific roles
// Recalculates term counts by including items from child terms (or if pad_counts is false, simply credits each term for readable private posts)
// Assumes all relevant children are already in the $terms argument
//
// note: this function does not deal with non-WP taxonomies
function rs_tally_term_counts(&$terms, $taxonomy, $args = array()) {
	global $wpdb, $scoper;
	
	$defaults = array ( 'pad_counts' => true, 'skip_teaser' => false, 'post_type' => '' );
	$args = array_merge( $defaults, (array) $args );
	extract($args);
	
	if ( ! $terms )
		return;

	$term_items = array();
	$terms_by_id = array();
	foreach ( $terms as $key => $term ) {
		$terms_by_id[$term->term_id] = & $terms[$key];
		$term_ids[$term->term_taxonomy_id] = $term->term_id;  // key and value will match for non-taxonomy category types
	}

	$tx_obj = get_taxonomy( $taxonomy );
	$post_types = array_unique( (array) $tx_obj->object_type );

	$enabled_types = array();
	foreach ( $post_types as $_post_type )
		if ( scoper_get_otype_option( 'use_term_roles', 'post', $_post_type ) || ( 'attachment' == $_post_type ) )
			$enabled_types []= $_post_type;
			
	if ( ! $enabled_types )
		return;

	if ( $post_type ) {
		$post_type = (array) $post_type;
		$enabled_types = array_intersect( $enabled_types, $post_type );
	}
	
	// Get the object and term ids and stick them in a lookup table
	$request = "SELECT DISTINCT $wpdb->posts.ID, tt.term_taxonomy_id, tt.term_id, tr.object_id"
			 . " FROM $wpdb->posts"
			 . " INNER JOIN $wpdb->term_relationships AS tr ON $wpdb->posts.ID = tr.object_id "
			 . " INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id "
			 . " WHERE tt.taxonomy = '$taxonomy' AND tt.term_id IN ('" . implode("','", $term_ids) . "') "
			 . " AND $wpdb->posts.post_type IN ('" . implode("','", $enabled_types) . "')";

	// no need to pass any parameters which do not pertain to the objects_request filter
	$args = array_intersect_key( $args, array_flip( array('skip_teaser') ) );

	//$post_type = reset($enabled_types);
	$post_type = $enabled_types;
	
	// note: don't pass in a taxonomies arg because we need to consider restrictions associated with any taxonomy to determine readable objects for terms of this taxonomy
	$request = apply_filters('objects_request_rs', $request, 'post', $post_type, $args);

	$results = scoper_get_results($request);
	
	foreach ( $results as $row ) {
		$id = $term_ids[$row->term_taxonomy_id];
		if ( isset($term_items[$id][$row->object_id]) )
			++$term_items[$id][$row->object_id];
		else
			$term_items[$id][$row->object_id] = 1;
	}
	
	// credit each term for every object contained in any of its descendant terms
	if ( $pad_counts && ScoperAncestry::get_terms_children($taxonomy) ) {
		foreach ( $term_ids as $term_id ) {
			$child_term_id = $term_id;
			
			while ( isset($terms_by_id[$child_term_id]->parent) ) {
				if ( ! $parent_term_id = $terms_by_id[$child_term_id]->parent )
					break;
				
				if ( ! empty($term_items[$term_id]) )
					foreach ( array_keys($term_items[$term_id]) as $item_id )
						$term_items[$parent_term_id][$item_id] = 1;
						
				$child_term_id = $parent_term_id;
			}
		}
	}
	
	// Tally and apply the item credits
	foreach ( $term_items as $term_id => $items )
		if ( isset($terms_by_id[$term_id]) )
			$terms_by_id[$term_id]->count = count($items);
			
	// update count property for zero-item terms too 
	foreach ( array_keys($terms_by_id) as $term_id )
		if ( ! isset($term_items[$term_id]) )
			if ( is_object($terms_by_id[$term_id]) )
				$terms_by_id[$term_id]->count = 0;
}

?>