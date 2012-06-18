<?php
function _scoper_bp_has_activities( $bp_activities, $bp_activities_template ) {
	$wp_keys = array();
	foreach( array_keys($bp_activities_template->activities) as $key ) {
		if ( 'new_blog_post' == $bp_activities_template->activities[$key]->type ) {
			// log listed ids first to buffer current_user_can query results
			$wp_id = $bp_activities_template->activities[$key]->secondary_item_id;
			$GLOBALS['scoper']->listed_ids['post'][$wp_id] = true;
			$wp_keys[$key] = $wp_id;
		}
	}

	$hidden_count = 0;
	foreach( $wp_keys as $key => $wp_id ) {
		if ( ! current_user_can( 'read_post', $wp_id ) ) {
			unset( $bp_activities_template->activities[$key] );
			$hidden_count++;
		}
	}

	if ( $hidden_count ) {
		$bp_activities_template->activities = array_values($bp_activities_template->activities);  // reset keys
		$bp_activities_template->activity_count -= $hidden_count;
	}

	return $bp_activities;
}
?>