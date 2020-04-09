<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

global $HTTP_RAW_POST_DATA;
if ( ! empty($HTTP_RAW_POST_DATA) )
	$GLOBALS['scoper_last_raw_post_data'] = $HTTP_RAW_POST_DATA; // global var is not retained reliably
	
add_action( 'xmlrpc_call', '_rs_wlw_on_init', 0 );
add_filter( 'xmlrpc_methods', '_rs_adjust_methods' );

function _rs_wlw_on_init() {
	global $wp_xmlrpc_server;

	if ( isset( $wp_xmlrpc_server->message ) ) {
		if ( 'metaWeblog.newPost' == $wp_xmlrpc_server->message->methodName ) {
			if ( empty( $wp_xmlrpc_server->message->params[3]['categories'] ) ) {
				$wp_xmlrpc_server->message->params[3]['categories'] = (array) get_option( 'default_category' );
			}
		}
	}
} // end function

add_filter( 'pre_post_category', '_rs_pre_post_category' );

function _rs_pre_post_category( $catids ) {
	return apply_filters( 'pre_object_terms_rs', $catids, 'category' );
}

function _rs_adjust_methods( $methods ) {
	$methods['mt.setPostCategories'] = '_rs_mt_set_categories';
	return $methods;
}

// Override default method. Otherwise categories are unfilterable.
function _rs_mt_set_categories( $args ) {
	global $wp_xmlrpc_server;
	$wp_xmlrpc_server->escape($args);

	$post_ID    = (int) $args[0];
	$username  = $args[1];
	$password   = $args[2];
	$categories  = $args[3];

	if ( !$user = $wp_xmlrpc_server->login($username, $password) )
		return $wp_xmlrpc_server->error;

	if ( empty($categories) )
		$categories = array();

	$catids = array();
	foreach( $categories as $cat ) {
		$catids []= $cat['categoryId'];
	}

	$catids = apply_filters( 'pre_object_terms_rs', $catids, 'category' );

	do_action('xmlrpc_call', 'mt.setPostCategories');

	if ( ! get_post( $post_ID ) )
		return new IXR_Error( 404, __( 'Invalid post ID.' ) );

	if ( !current_user_can('edit_post', $post_ID) )
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	wp_set_post_categories($post_ID, $catids);
	
	return true;
}

// clean up after xmlrpc clients that don't specify a post_type for mw_editPost
if ( defined('WLW_XMLRPC_HACK' ) )
	include( dirname(__FILE__).'/xmlrpc-wlw_rs.php' );

/*	
$GLOBALS['scoper_xmlrpc_helper'] = new Scoper_XMLRPC_Helper();
	
class Scoper_XMLRPC_Helper {
	var $scheduled_term_restoration;
	
	function Scoper_XMLRPC_Helper() {
		add_action( 'xmlrpc_call', array( &$this, 'police_xmlrpc_action' ) );
	}
	
	function load_ixr() {
		if ( class_exists( 'IXR_Message' ) ) {
			return true;
		} elseif ( file_exists( ABSPATH . WPINC . '/class-IXR.php' ) ) {
			require_once( ABSPATH . WPINC . '/class-IXR.php' );
			return true;
		}
	}
	
	function police_xmlrpc_action( $method_name ) {
		if ( function_exists('is_content_administrator_rs') && is_content_administrator_rs() )
			return;

		switch( $method_name ) {
		// This method has no business passing an empty array of categories.  It usually means none are selected for a new post, and does not provide a hook for RS to filtering the default category prior to insertion 
		case 'mt.setPostCategories' :
			global $scoper_last_raw_post_data;
		
			if ( empty( $scoper_last_raw_post_data ) )
				return;

			if ( ! $this->load_ixr() )
				return;

			$msg = new IXR_Message($scoper_last_raw_post_data);
			
			if ( $msg->parse() ) {
				// params[0] = object id, params[3] = categories
				if ( is_array( $msg->params ) && ! empty( $msg->params[0] ) && isset( $msg->params[3] ) && ! $msg->params[3] ) {
					if ( $terms = wp_get_object_terms( $msg->params[0], 'category', array( 'fields' => 'ids' ) ) ) {
						foreach( $terms as $key => $val )
							$terms[$key] = (int) $val;	// otherwise wp_set_object_terms will store as a new category named "id"

						if ( empty( $this->scheduled_term_restoration ) ) {
							$this->scheduled_term_restoration = array();
							add_action( 'set_object_terms', array( &$this, 'maybe_restore_object_terms' ), 99, 6 );
						}
						$this->scheduled_term_restoration[ $msg->params[0] ]['category'] = $terms;
					}
				}
			}
		break; // 'mt.setPostCategories'
			
		} // end switch
	}
	
	function maybe_restore_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( isset( $this->scheduled_term_restoration[$object_id][$taxonomy] ) ) {
			wp_set_object_terms( $object_id, $this->scheduled_term_restoration[$object_id][$taxonomy], $taxonomy );
			unset( $this->scheduled_term_restoration[$object_id][$taxonomy] ); 
		}
	}	
} // end class
*/
  
// might have to do this someday, but prefer not to incur the liability of overriding entire method handlers
/*
function scoper_mw_edit_post($args) {
}
 
function scoper_flt_xmlrpc_methods($methods) {
	$methods['metaWeblog.editPost'] = 'scoper_mw_edit_post';
	$methods['wp.editPage'] = 'scoper_mw_edit_post';

	return $methods;
}

//add_filter('xmlrpc_methods', 'scoper_flt_xmlrpc_methods');
*/

?>