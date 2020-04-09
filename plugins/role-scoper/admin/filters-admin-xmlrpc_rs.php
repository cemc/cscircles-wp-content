<?php
function _rs_get_posted_xmlrpc_terms( $taxonomy ) {
	global $wp_xmlrpc_server;
	
	if ( empty($wp_xmlrpc_server->message) )
		return array();
		
	$xmlrpc_method = $GLOBALS['wp_xmlrpc_server']->message->methodName;

	if ( empty( $GLOBALS['wp_xmlrpc_server']->message->params ) )
		return array();

	if ( in_array( $xmlrpc_method, array( 'metaWeblog.newPost', 'metaWeblog.editPost' ) ) ) {
		if ( ! empty( $GLOBALS['wp_xmlrpc_server']->message->params[3] ) ) {
			$data = $GLOBALS['wp_xmlrpc_server']->message->params[3];
		
			if ( 'category' == $taxonomy ) {
				if ( is_array($data['categories']) ) {
					$post_category = array();
					foreach ($data['categories'] as $cat) {
						$post_category[] = get_cat_ID($cat);
					}
					
					return $post_category;
				}
			} elseif ( 'post_tag' == $taxonomy ) {
				if ( ! empty($data['mt_keywords']) ) {
					$tags = $data['mt_keywords'];
					$comma = _x( ',', 'tag delimiter' );
					if ( ',' !== $comma )
						$tags = str_replace( $comma, ',', $tags );
					$tags = explode( ',', trim( $tags, " \n\t\r\0\x0B," ) );
					return $tags;
				}
			}
		}
	} elseif ( in_array( $xmlrpc_method, array( 'blogger.newPost', 'blogger.editPost' ) ) ) {
		if ( ! empty( $GLOBALS['wp_xmlrpc_server']->message->params[4] ) ) {
			$data = $GLOBALS['wp_xmlrpc_server']->message->params[4];
			
			if ( 'category' == $taxonomy ) {
				if ( function_exists( 'xmlrpc_getpostcategory' ) ) {
					$post_category = xmlrpc_getpostcategory($data);
					return $post_category;
				}
			}
		}
	} elseif ( in_array( $xmlrpc_method, array( 'wp.newPost', 'wp.editPost' ) ) ) {
		if ( ! empty( $GLOBALS['wp_xmlrpc_server']->message->params[3] ) ) {
			$post_data = $GLOBALS['wp_xmlrpc_server']->message->params[3];

			// accumulate term IDs from terms and terms_names
			$terms = array();

			if ( isset( $post_data['terms'] ) && is_array( $post_data['terms'] ) ) {
				foreach ( $post_data['terms'][$taxonomy] as $term_id ) {
					if ( $term = get_term_by( 'id', $term_id, $taxonomy ) ) {
						$terms[] = (int) $term_id;
					}
				}
			}

			if ( isset( $post_data['terms_names'] ) && is_array( $post_data['terms_names'] ) ) {
				foreach ( $post_data['terms_names'][$taxonomy] as $term_name ) {
					if ( $term = get_term_by( 'name', $term_name, $taxonomy ) ) {  // term creation is outside the scope of this usage
						$terms[] = (int) $term->term_id;
					}
				}
			}
			
			return $terms;
		}
	}
	
	return array();
}
?>