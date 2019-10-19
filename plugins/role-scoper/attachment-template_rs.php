<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/attachment-interceptor_rs.php' );

class AttachmentTemplate_RS {
	// Filter attachment page content prior to display by attachment template.
	// Note: teaser-subject direct file URL requests also land here
	public static function attachment_access() {
		global $post, $wpdb;

		if ( empty($post) ) {
			global $wp_query;

			if ( ! empty($wp_query->query_vars['attachment_id']) ) {
				$post = scoper_get_row("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = '{$wp_query->query_vars['attachment_id']}'");
			
			} elseif ( ! empty($wp_query->query_vars['attachment']) )
				$post = scoper_get_row("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_name = '{$wp_query->query_vars['attachment']}'");
		}
		
		if ( ! empty($post) ) {
			$object_type = scoper_get_var("SELECT post_type FROM $wpdb->posts WHERE ID = '$post->post_parent'");

			// default to 'post' object type if retrieval failed for some reason
			if ( empty($object_type) )
				$object_type = 'post';
			
			if ( $post->post_parent ) {
				if ( ! current_user_can( "read_$object_type", $post->post_parent ) ) {
					if ( scoper_get_otype_option('do_teaser', 'post') ) {
						if ( $use_teaser_type = scoper_get_otype_option('use_teaser', 'post',  $object_type) )
							AttachmentTemplate_RS::impose_post_teaser($post, $object_type, $use_teaser_type);
						else
							unset( $post );
					} else
						unset( $post ); // WordPress generates 404 if teaser is not enabled
				}
			} elseif ( defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') && SCOPER_BLOCK_UNATTACHED_UPLOADS ) {
				unset( $post );
			}
		}
	}
	
	public static function impose_post_teaser(&$object, $post_type, $use_teaser_type = 'fixed') {
		global $current_user, $scoper, $wp_query;

		require_once( dirname(__FILE__).'/teaser_rs.php');
		
		$teaser_replace = array();
		$teaser_prepend = array();
		$teaser_append = array();
		
		$teaser_replace[$post_type]['post_content'] = ScoperTeaser::get_teaser_text( 'replace', 'content', 'post', $post_type, $current_user );

		$teaser_replace[$post_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'replace', 'excerpt', 'post', $post_type, $current_user );
		$teaser_prepend[$post_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'prepend', 'excerpt', 'post', $post_type, $current_user );
		$teaser_append[$post_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'append', 'excerpt', 'post', $post_type, $current_user );

		$teaser_prepend[$post_type]['post_name'] = ScoperTeaser::get_teaser_text( 'prepend', 'name', 'post', $post_type, $current_user );
		$teaser_append[$post_type]['post_name'] = ScoperTeaser::get_teaser_text( 'append', 'name', 'post', $post_type, $current_user );
	
		$force_excerpt = array();
		$force_excerpt[$post_type] = ( 'excerpt' == $use_teaser_type );
		
		$args = array( 'teaser_prepend' => $teaser_prepend,   'teaser_append' => $teaser_append, 	'teaser_replace' => $teaser_replace,  'force_excerpt' => $force_excerpt );
		ScoperTeaser::apply_teaser( $object, $post_type, $args );
		
		$wp_query->is_404 = false;
		$wp_query->is_attachment = true;
		$wp_query->is_single = true;
		$wp_query->is_singular = true;
		$object->ancestors = array( $object->post_parent );
		
		$wp_query->post_count = 1;
		$wp_query->is_attachment = true;
		$wp_query->posts[] = $object;
		
		if ( isset($wp_query->query_vars['error']) )
			unset( $wp_query->query_vars['error'] );
		
		if ( isset($wp_query->query['error']) )
			$wp_query->query['error'] = '';
	}

} // end class
?>