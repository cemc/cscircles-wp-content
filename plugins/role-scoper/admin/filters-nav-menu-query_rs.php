<?php

/**
 * ScoperNavMenuQuery class
 * 
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2013, Agapetry Creations LLC
 * 
 */
class ScoperNavMenuQuery
{
	function __construct() {
		add_filter( 'parse_query', array(&$this, 'available_menu_items_parse_query' ) );
	}
	
	// enable this to prevent Nav Menu Managers from adding items they cannot edit
	function available_menu_items_parse_query( &$query ) {
		if ( scoper_get_option( 'admin_nav_menu_filter_items' ) ) {
			$query->query_vars['include'] = '';
			$query->query_vars['post__in'] = '';
			
			if ( ! awp_ver( '4.0' ) ) {
				$query->query_vars_hash = '';
				$query->query_vars_changed = true;
			}
			
			$query->query['include'] = '';
			$query->query['post__in'] = '';

			$query->query_vars['suppress_filters'] = false;
			$query->query_vars['post_status'] = '';
			
			$query->query['suppress_filters'] = false;
			$query->query['post_status'] = '';
		}
	}
}
?>