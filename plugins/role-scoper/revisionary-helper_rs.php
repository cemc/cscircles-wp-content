<?php
if ( did_action( 'plugins_loaded' ) )
	Rvy_Helper::init_rvy_interface();
else
	add_action( 'plugins_loaded', array( 'Rvy_Helper', 'init_rvy_interface' ) );


Class Rvy_Helper {
	function init_rvy_interface() {
		if ( ! empty($GLOBALS['revisionary']) && method_exists( $GLOBALS['revisionary'], 'set_content_roles' ) ) {
		
			if ( class_exists('RevisionaryContentRoles') ) {
				require_once( dirname(__FILE__).'/revisionary-content-roles_rs.php' );
				$GLOBALS['revisionary']->set_content_roles( new Scoper_RvyContentRoles() );
			}
		}
	}
	
	// Allow contributors and revisors to edit published post/page, with change stored as a revision pending review
	function convert_post_edit_caps( $rs_reqd_caps, $post_type )	{
		global $revisionary, $scoper;
				
		if ( ! empty( $revisionary->skip_revision_allowance ) || ! rvy_get_option('pending_revisions') )
			return $rs_reqd_caps;
		
		$post_id = $scoper->data_sources->detect('id', 'post');

		// don't need to fudge the capreq for post.php unless existing post has public/private status
		$status = get_post_field( 'post_status', $post_id, 'post' );
		$status_obj = get_post_status_object( $status );
		
		if ( empty( $status_obj->public ) && empty( $status_obj->private ) && ( 'future' != $status ) ) 
			return $rs_reqd_caps;
			
		if ( $type_obj = get_post_type_object( $post_type ) ) {
			$replace_caps = array( 'edit_published_posts', 'edit_private_posts', 'publish_posts', $type_obj->cap->edit_published_posts, $type_obj->cap->edit_private_posts, $type_obj->cap->publish_posts );
			$use_cap_req = $type_obj->cap->edit_posts;
		} else
			$replace_caps = array();		
		
		if ( array_intersect( $rs_reqd_caps, $replace_caps) ) {	
			foreach ( $rs_reqd_caps as $key => $cap_name )
				if ( in_array($cap_name, $replace_caps) )
					$rs_reqd_caps[$key] = $use_cap_req;
		}

		return $rs_reqd_caps;
	}
	
	// ensure proper cap requirements when a non-Administrator Quick-Edits or Bulk-Edits Posts/Pages (which may be included in the edit listing only for revision submission)
	function fix_table_edit_reqd_caps( $rs_reqd_caps, $orig_meta_cap, $_post, $object_type_obj ) {
		foreach( array( 'edit', 'delete' ) as $op ) {
			if ( in_array( $orig_meta_cap, array( "{$op}_post", "{$op}_page" ) ) ) {
				$status_obj = get_post_status_object( $_post->post_status );
				foreach( array( 'public' => 'published', 'private' => 'private' ) as $status_prop => $cap_suffix ) {
					if ( ! empty($status_obj->$status_prop) ) {
						$cap_prop = "{$op}_{$cap_suffix}_posts";
						$rs_reqd_caps[]= $object_type_obj->cap->$cap_prop;
						$GLOBALS['revisionary']->skip_revision_allowance = true;
					}
				}
			}
		}
		return $rs_reqd_caps;
	}
}

?>