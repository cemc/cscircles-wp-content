<?php
add_filter( 'the_comments', array( 'CommentsInterceptorAdmin_RS', 'log_comment_post_ids' ) );

class CommentsInterceptorAdmin_RS {
	function get_reqd_caps() {
		$reqd_caps = array();
		
		$generic_uri = in_array( $GLOBALS['pagenow'], array( 'index.php', 'comments.php' ) );

		if ( ! $generic_uri && ( $_post_type = cr_find_post_type( '', false ) ) )  // arg: don't return 'post' as default if detection fails
			$post_types = array( $_post_type => get_post_type_object( $_post_type ) );
		else
			$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );

		$use_post_types = scoper_get_option( 'use_post_types' );
		$post_statuses = get_post_stati( array( 'internal' => null ), 'object' );
		
		foreach( $post_types as $_post_type => $type_obj ) {
			if ( empty( $use_post_types[$_post_type] ) )
				continue;
		
			foreach ( $post_statuses as $status => $status_obj ) {
				$reqd_caps[$_post_type][$status] = array( $type_obj->cap->edit_others_posts );
				
				if ( scoper_get_option( 'require_moderate_comments_cap' ) )
					$reqd_caps[$_post_type][$status] []= 'moderate_comments';
				
				if ( $status_obj->private )
					$reqd_caps[$_post_type][$status] []= $type_obj->cap->edit_private_posts;
					
				if ( $status_obj->public || ( 'future' == $status ) )
					$reqd_caps[$_post_type][$status] []= $type_obj->cap->edit_published_posts;
			}
		}

		return $reqd_caps;
	}
	
	function log_comment_post_ids( $comments ) {
		global $scoper;
		
		// buffer the listed IDs for more efficient user_has_cap calls
		if ( empty($scoper->listed_ids['post']) ) {
			$scoper->listed_ids['post'] = array();
				
			foreach ( $comments as $row ) {
				if ( ! empty($row->comment_post_ID) )
					$scoper->listed_ids['post'][$row->comment_post_ID] = true;
			}
		}
		
		return $comments;
	}
} // end class
?>