<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

global $HTTP_RAW_POST_DATA;
if ( ! empty($HTTP_RAW_POST_DATA) )
	$GLOBALS['scoper_last_raw_post_data'] = $HTTP_RAW_POST_DATA; // global var is not retained reliably
	
add_action( 'xmlrpc_call', '_rs_wlw_on_init', 0 );

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
	
// clean up after xmlrpc clients that don't specify a post_type for mw_editPost
if ( defined('WLW_XMLRPC_HACK' ) )
	include( dirname(__FILE__).'/xmlrpc-wlw_rs.php' );

  
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