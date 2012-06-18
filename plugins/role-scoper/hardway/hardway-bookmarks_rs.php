<?php

add_filter('get_bookmarks', array('ScoperHardwayBookmarks', 'flt_get_bookmarks'), 1, 2);	

class ScoperHardwayBookmarks {
	
	// scoped equivalent to WP 2.8.3 core get_bookmarks
	//	 Currently, scoped roles cannot be enforced without replicating the whole function 
	//
	// Enforces cap requirements as specified in CR_Data_Source::reqd_caps
	function flt_get_bookmarks($results, $args) {
		global $wpdb;

		$defaults = array(
			'orderby' => 'name', 'order' => 'ASC',
			'limit' => -1, 'category' => '',
			'category_name' => '', 'hide_invisible' => 1,
			'show_updated' => 0, 'include' => '',
			'exclude' => '', 'search' => ''
		);
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );


		// === BEGIN RoleScoper ADDITION: exemption for content administrators
		if ( is_content_administrator_rs() )
			return $results;
		// === END RoleScoper ADDITION ===
			
		// === BEGIN RoleScoper MODIFICATION: wp-cache key and flag specific to access type and user/groups --//
		//
		global $current_rs_user;
		$ckey = md5 ( serialize( $r ) . CURRENT_ACCESS_NAME_RS );
		
		$cache_flag = 'rs_get_bookmarks';
		
		$cache = $current_rs_user->cache_get( $cache_flag );
		
		if ( false !== $cache ) {
			if ( !is_array($cache) )
				$cache = array();
		
			if ( isset( $cache[ $ckey ] ) )
				//alternate filter name (WP core already called get_bookmarks filter)
				return apply_filters('get_bookmarks_rs', $cache[ $ckey ], $r);
		}
		//
		// === END RoleScoper MODIFICATION ===
		// ===================================

		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';  //ignore exclude, category, and category_name params if using include
			$category = '';
			$category_name = '';
			$inclinks = wp_parse_id_list($include);
			if ( count($inclinks) ) {
				foreach ( $inclinks as $inclink ) {
					if (empty($inclusions))
						$inclusions = ' AND ( link_id = ' . intval($inclink) . ' ';
					else
						$inclusions .= ' OR link_id = ' . intval($inclink) . ' ';
				}
			}
		}
		if (!empty($inclusions))
			$inclusions .= ')';
	
		$exclusions = '';
		if ( !empty($exclude) ) {
			$exlinks = wp_parse_id_list($exclude);
			if ( count($exlinks) ) {
				foreach ( $exlinks as $exlink ) {
					if (empty($exclusions))
						$exclusions = ' AND ( link_id <> ' . intval($exlink) . ' ';
					else
						$exclusions .= ' AND link_id <> ' . intval($exlink) . ' ';
				}
			}
		}
		if (!empty($exclusions))
			$exclusions .= ')';
	
		if ( ! empty($category_name) ) {
			if ( $category = get_term_by('name', $category_name, 'link_category') )
				$category = $category->term_id;
			else
				return array();
		}
	
		if ( ! empty($search) ) {
			$search = like_escape($search);
			$search = " AND ( (link_url LIKE '%$search%') OR (link_name LIKE '%$search%') OR (link_description LIKE '%$search%') ) ";
		}
		
		$category_query = '';
		$join = '';
		if ( !empty($category) ) {
			$incategories = wp_parse_id_list($category);
			if ( count($incategories) ) {
				foreach ( $incategories as $incat ) {
					if (empty($category_query))
						$category_query = ' AND ( tt.term_id = ' . intval($incat) . ' ';
					else
						$category_query .= ' OR tt.term_id = ' . intval($incat) . ' ';
				}
			}
		}
		if (!empty($category_query)) {
			$category_query .= ") AND taxonomy = 'link_category'";
			$join = " INNER JOIN $wpdb->term_relationships AS tr ON ($wpdb->links.link_id = tr.object_id) INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
		}
		
		if (get_option('links_recently_updated_time')) {
			$recently_updated_test = ", IF (DATE_ADD(link_updated, INTERVAL " . get_option('links_recently_updated_time') . " MINUTE) >= NOW(), 1,0) as recently_updated ";
		} else {
			$recently_updated_test = '';
		}
	
		if ($show_updated) {
			$get_updated = ", UNIX_TIMESTAMP(link_updated) AS link_updated_f ";
		} else
			$get_updated = '';
	
		$orderby = strtolower($orderby);
		$length = '';
		switch ($orderby) {
			case 'length':
				$length = ", CHAR_LENGTH(link_name) AS length";
				break;
			case 'rand':
				$orderby = 'rand()';
				break;
			default:
				$orderparams = array();
				foreach ( explode(',', $orderby) as $ordparam )
					$orderparams[] = 'link_' . trim($ordparam);
				$orderby = implode(',', $orderparams);
		}

		if ( 'link_id' == $orderby )
			$orderby = "$wpdb->links.link_id";
	
		$visible = '';
		if ( $hide_invisible )
			$visible = "AND link_visible = 'Y'";
		
		$query = "SELECT * $length $recently_updated_test $get_updated FROM $wpdb->links $join WHERE 1=1 $visible $category_query";
		$query .= " $exclusions $inclusions $search";
		$query .= " ORDER BY $orderby $order";
		if ($limit != -1)
			$query .= " LIMIT $limit";
			

		// === BEGIN RoleScoper MODIFICATION:  run query through scoping filter, cache key specific to user/group
		$query = apply_filters('objects_request_rs', $query, 'link', '', '');

		$results = scoper_get_results($query);

		// cache key and flag specific to access type and user/groups
		$cache[ $ckey ] = $results;
		$current_rs_user->cache_set( $cache, $cache_flag );
		
		// alternate hook name (WP core already applied get_bookmarks)
		$links = apply_filters('get_bookmarks_rs', $results, $r);
		//
		// === END RoleScoper MODIFICATION ===
		// ===================================
		
		
		// === BEGIN RoleScoper ADDITION: memory cache akin to page_cache to assist bulk operations
		//
		global $scoper;
		$ilim = count($links);
		for ($i = 0; $i < $ilim; $i++)
			$scoper->listed_ids['link'][$links[$i]->link_id] = true;
		//
		// === END RoleScoper ADDITION ===
		// ===================================
			

		return $links;
	}

} // end class
?>