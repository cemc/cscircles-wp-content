<?php
function scoper_startup_error( $msg_id = '' ) {
	// this is the normal situation on first pass after activation
	if ( 'wp_role_type' == $msg_id ) {
		awp_notice('Role Scoper cannot operate because the "WP" Role Type is no longer supported.  Please re-activate <a href="http://downloads.wordpress.org/plugin/role-scoper/download/">Role Scoper version 1.2.8 or earlier</a>, set Roles > Options > Role&nbsp;Type to "RS", then re-establish Roles and Restrictions before upgrading.  <strong>All content is hidden until you deactivate this Role Scoper version.</strong>', 'role-scoper' );
	}

	// To prevent inadverant content exposure, default to blocking all content if another plugin steals wp_set_current_user definition.
	if ( 'plugins.php' != $GLOBALS['pagenow'] ) {
		add_filter('posts_where', create_function('$a', "return 'AND 1=2';"), 99);
		add_filter('posts_results', create_function('$a', "return array();"), 1);
		add_filter('get_pages', create_function('$a', "return array();"), 99);
		add_filter('get_bookmarks', create_function('$a', "return array();"), 99);
		add_filter('get_categories', create_function('$a', "return array();"), 99);
		add_filter('get_terms', create_function('$a', "return array();"), 99);
		add_filter('option_sticky_posts', create_function('$a', "return false;"), 99);

		// Also run interference for all custom-defined where_hook, request_filter or results_filter
		require_once( dirname(__FILE__).'/role-scoper_main.php');
		
		global $scoper, $wpdb, $current_user;
		$buffer_user = $current_user;
		
		require_once( dirname(__FILE__).'/role-scoper_init.php' );
		
		$scoper = new Scoper();
		$scoper->load_config();
		
		$GLOBALS['current_user'] = $buffer_user;
		
		foreach( $scoper->data_sources->get_all() as $src ) {
			if ( ! empty($src->query_hooks->request) )
				add_filter($src->query_hooks->request, create_function('$a', "return 'SELECT * FROM $wpdb->posts WHERE 1=2';"), 99);
		
			if ( ! empty($src->query_hooks->where) )
				add_filter($src->query_hooks->where, create_function('$a', "return 'AND 1=2';"), 99);
		
			if ( ! empty($src->query_hooks->results) )
				add_filter($src->query_hooks->results, create_function('$a', "return array();"), 1);
		}
	}
}

function awp_notice( $message, $plugin_name ) {
	// slick method copied from NextGEN Gallery plugin			// TODO: why isn't there a class that can turn this text black?
	add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
	trigger_error("$plugin_name internal notice: $message");
	$err = new WP_Error($plugin_name, $message);
}
?>