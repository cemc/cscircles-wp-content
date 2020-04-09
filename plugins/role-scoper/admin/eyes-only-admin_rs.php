<?php
add_filter( 'sseo_rs_group_items', '_scoper_sseo_groups' );

function _scoper_sseo_groups( $group_labels ) {
	$group_labels = array();
	
	$groups = ScoperAdminLib::get_all_groups( UNFILTERED_RS, COLS_ALL_RS, array() );
	foreach( $groups as $group ) {
		if ( empty($group->meta_id) )
			$group_labels[$group->ID] = $group->display_name;
	}
	
	return $group_labels;
}
