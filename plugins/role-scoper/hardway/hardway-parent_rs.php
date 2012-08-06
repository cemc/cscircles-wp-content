<?php

class ScoperHardwayParent {

	function flt_dropdown_pages($orig_options_html) {
		if ( 'no_parent_filter' == scoper_get_option( 'lock_top_pages' ) )
			return $orig_options_html;

		if ( ! strpos( $orig_options_html, 'parent_id' ) || ! $orig_options_html || is_content_administrator_rs() )
			return $orig_options_html;

		$post_type = awp_post_type_from_uri();

		// User can't associate or de-associate a page with Main page unless they have edit_pages blog-wide.
		// Prepend the Main Page option if appropriate (or, to avoid submission errors, if we generated no other options)
		if ( ! $GLOBALS['scoper_admin_filters']->user_can_associate_main( $post_type ) ) {
			$is_new = ( $GLOBALS['post']->post_status == 'auto-draft' );
				
			if ( ! $is_new ) {
				global $post;
				$object_id = ( ! empty($post->ID) ) ? $post->ID : $GLOBALS['scoper']->data_sources->detect('id', 'post', 0, 'post');

				$stored_parent_id = ( ! empty($post->ID) ) ? $post->post_parent : get_post_field( 'post_parent', $object_id );
			}

			if ( $is_new || $stored_parent_id ) {
				$mat = array();
				preg_match('/<option[^v]* value="">[^<]*<\/option>/', $orig_options_html, $mat);
	
				if ( ! empty($mat[0]) )
					return str_replace( $mat[0], '', $orig_options_html );
			}
		}
		
		return $orig_options_html;
	}
}

?>