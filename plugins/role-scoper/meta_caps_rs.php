<?php

// to override default WP core meta_cap translation
add_filter( 'map_meta_cap', '_map_core_meta_cap_rs', 1, 4 );  // register early because we are wiping out default meta_cap expansion

// used by UsersInterceptor for users_who_can filtering
add_filter( 'map_meta_cap_rs', '_map_meta_cap_rs', 1, 4 );  // register early because we are wiping out default meta_cap expansion


// no need to reconstruct WP-generated cap mapping except for revisions and attachments (not supporting custom statuses)
function _map_core_meta_cap_rs( $caps, $meta_cap, $user_id, $args ) {
	if ( ( -1 === $user_id ) || in_array( $meta_cap, array( 'read_attachment', 'edit_attachment', 'delete_attachment', 'read_revision', 'edit_revision', 'delete_revision' ) ) || defined( 'SCOPER_FORCE_MAP_METACAP' ) )
		$caps = _map_meta_cap_rs( $caps, $meta_cap, $user_id, $args );

	return $caps;
}

function _map_meta_cap_rs( $caps, $meta_cap, $user_id, $args ) {
	static $defined_meta_caps;
	
	// support usage by RS users_who_can function, which needs to remap meta caps to simple equivalent but builds owner cap adjustment into DB query
	$adjust_for_user = ( -1 !== $user_id );

	// separate filtering function _map_user_meta_cap_rs handles this for users_who_can filtering
	if ( in_array( $meta_cap, array( 'edit_users', 'delete_users', 'remove_users', 'promote_users' ) ) )
		return $caps;
	
	if ( ! isset( $defined_meta_caps ) ) {
		$defined_meta_caps = array();

		$defined_meta_caps ['read'] = array( 'read_attachment', 'read_revision' );
		$defined_meta_caps ['edit'] = array( 'edit_attachment', 'edit_revision' );
		$defined_meta_caps ['delete'] = array( 'delete_attachment', 'delete_revision' );
		
		$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );

		foreach( $post_types as $type => $post_type_obj ) {
			$defined_meta_caps ['read'] []= $post_type_obj->cap->read_post;
			$defined_meta_caps ['edit'] []= $post_type_obj->cap->edit_post;
			$defined_meta_caps ['delete'] []= $post_type_obj->cap->delete_post;
		}
	}

	$matched_op = false;
	foreach( array_keys($defined_meta_caps) as $op ) {
		if ( in_array( $meta_cap, $defined_meta_caps[$op] ) ) {
			$matched_op = $op;
			break;	
		}
	}
	
	if ( ! $matched_op )
		return $caps;

	$object_id = ( is_array($args) ) ? $args[0] : $args;

	if ( ! $object_id )
		return $caps;

	if ( ! $post = get_post( $object_id ) )
		return $caps;

	if ( in_array( $post->post_type, array( 'revision', 'attachment' ) ) )
		if ( ! $post = get_post( $post->post_parent ) )
			return $caps;

	$use_post_types = scoper_get_option( 'use_post_types' );
	if ( empty( $use_post_types[$post->post_type] ) )
		return $caps;
		
	if ( ! $post_type_obj = get_post_type_object( $post->post_type ) )
		return $caps;
		
	if ( ! $post_status_obj = get_post_status_object( $post->post_status ) )
		return $caps;
		
	// no need to modify meta caps for post/page checks with built-in status
	if ( in_array( $post->post_type, array( 'post', 'page' ) ) && $post_status_obj->_builtin && $adjust_for_user && $caps )
		return $caps;

	if ( ! $adjust_for_user ) {
		$is_post_author = false;

	} elseif ( ! $post->post_author ) {
		$is_post_author = true;	//No author set yet so treat current user as author for cap checks
		$require_all_status_caps = true;
	} else {
		$post_author_data = get_userdata( $post->post_author );
		$is_post_author = ( $user_id == $post_author_data->ID );
		$require_all_status_caps = ! defined( 'SCOPER_LEGACY_META_CAPS' );
	}

	// Need to override default output from core map_meta_caps.  This filter hooks early to avoid wiping out other supplemental filters.
	$caps = array();
	
	switch ( $op ) {
	case 'read':
		if ( ! empty($post_status_obj->private) ) {
			if ( $is_post_author )
				$caps[] = 'read';
			else
				$caps[] = $post_type_obj->cap->read_private_posts;
		} else
			$caps[] = 'read';

		break;
		
	case 'edit' :
		$caps[] = $post_type_obj->cap->edit_posts;
	
		// The post is public, extra cap required.
		if ( ! empty($post_status_obj->public) ) {
			$caps[] = $post_type_obj->cap->edit_published_posts;
		
		} elseif ( 'trash' == $post->post_status ) {
			if ('publish' == get_post_meta($post->ID, '_wp_trash_meta_status', true) )
				$caps[] = $post_type_obj->cap->edit_published_posts;
		}
			
		// note: as of 3.0, WP core requires edit_published_posts, but not edit_private_posts, when logged user is the post author.  That's inconsistent when used in conjunction with custom statuses
		if ( ! empty($post_status_obj->private) && ! $is_post_author ) //&& $require_all_status_caps )
			$caps[] = $post_type_obj->cap->edit_private_posts;
			
		if ( ! $is_post_author )
			$caps[] = $post_type_obj->cap->edit_others_posts;	// The user is trying to edit someone else's post.

		break;
		
	case 'delete' :
		$caps[] = $post_type_obj->cap->delete_posts;
		
		// The post is public, extra cap required.
		if ( ! empty($post_status_obj->public) ) {
			$caps[] = $post_type_obj->cap->delete_published_posts;
		
		} elseif ( 'trash' == $post->post_status ) {
			if ('publish' == get_post_meta($post->ID, '_wp_trash_meta_status', true) )
				$caps[] = $post_type_obj->cap->delete_published_posts;
		}
			
		// note: as of 3.0, WP core requires delete_published_posts, but not delete_private_posts, when logged user is the post author.  That's inconsistent when used in conjunction with custom statuses
		if ( ! empty($post_status_obj->private) && ! $is_post_author ) //&& $require_all_status_caps )
			$caps[] = $post_type_obj->cap->delete_private_posts;
			
		if ( ! $is_post_author )
			$caps[] = $post_type_obj->cap->delete_others_posts;	// The user is trying to delete someone else's post.

		break;
	} // end switch
	
	// if a capability is defined for this custom status, require it also
	if ( empty($require_all_status_caps) ) {
		if ( empty($post_status_obj->_builtin) ) {
			$status_cap_name = "{$op}_{$post->post_status}_posts";
			if ( ! empty( $post_type_obj->cap->$status_cap_name ) )
				$caps []= $post_type_obj->cap->$status_cap_name;
		}
	}
		
	$caps = array_unique( $caps );

	return $caps;
}
?>