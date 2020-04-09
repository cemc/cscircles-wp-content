<?php

	function scoper_flt_newlink_category( $link_category ) {
		$user_cats = get_terms( 'link_category', array( 'fields' => 'ids', 'hide_empty' => false ) );

		$selected_cats = ( ! empty( $_POST['link_category'] ) ) ? $_POST['link_category'] : array();

		$selected_cats = array_intersect( $selected_cats, $user_cats );

		if ( ! $selected_cats )
			$selected_cats = array( reset( $user_cats ) );	
			
		return $selected_cats;
	}

	function scoper_flt_link_category( $link_category ) {
		if ( empty( $_POST['link_id'] ) )
			return $link_category;
			
		$link_id = $_POST['link_id'];
		
		$stored_cats = wp_get_link_cats( $link_id );

		$user_cats = get_terms( 'link_category', array( 'fields' => 'ids' ) );

		if ( ! $link_category )
			$link_category = array();
		else
			$link_category = (array) $link_category;
			
		// remove any cats user lacks permission to select
		if ( $unselectable_cats = array_diff( $link_category, $user_cats ) )
			$link_category = array_diff( $link_category, $unselectable_cats );

		// reinstate stored cats which are not selectable by logged user
		if ( $hidden_cats = array_diff( $stored_cats, $user_cats ) )
			$link_category = array_merge( $link_category, $hidden_cats );

		// don't let user remove their editing access to link
		if ( ! array_intersect( $link_category, $user_cats ) )
			$link_category []= reset( $user_cats );	
	
		return $link_category;
	}
?>