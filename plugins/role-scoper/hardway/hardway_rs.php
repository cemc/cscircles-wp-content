<?php
// In effect, override corresponding WP functions with a scoped equivalent, 
// including per-group wp_cache.  Any previous result set modifications by other plugins
// would be discarded.  These filters are set to execute as early as possible to avoid such conflict.
//
// (note: if wp_cache is not enabled, WP core queries will execute pointlessly before these filters have a chance)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

global $scoper;

require_once( SCOPER_ABSPATH . '/lib/ancestry_lib_rs.php' );

if ( $scoper->is_front() ) {
	require_once( dirname(__FILE__).'/hardway-front_rs.php');
	
	if ( ! awp_ver('3.1') )
		require_once( dirname(__FILE__).'/hardway-front-legacy_rs.php');
}
	
if ( $scoper->data_sources->is_member('link') )
	require_once( dirname(__FILE__).'/hardway-bookmarks_rs.php' );


// flt_get_pages is required on the front end (even for administrators) to enable the inclusion of private pages
// flt_get_pages also needed for inclusion of private pages in some 3rd party plugin config UI (Simple Section Nav)

// flt_get_terms '' so private posts are included in count, as basis for display when hide_empty arg is used

add_filter('get_pages', array('ScoperHardway', 'flt_get_pages'), 1, 2);

/**
 * ScoperHardway PHP class for the WordPress plugin Role Scoper
 * hardway_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 * Used by Role Scoper Plugin as a container for statically-called functions
 *
 */	
class ScoperHardway
{	
	//  Scoped equivalent to WP 3.0 core get_pages
	//	Currently, scoped roles cannot be enforced without replicating the whole function  
	//
	//	Enforces cap requirements as specified in cr_get_reqd_caps
	public static function flt_get_pages($results, $args = array()) {
		$results = (array) $results;

		global $wpdb;

		// === BEGIN Role Scoper ADDITION: global var; various special case exemption checks ===
		//
		global $scoper, $current_rs_user;
		
		// need to skip cache retrieval if QTranslate is filtering get_pages with a priority of 1 or less
		$no_cache = ! defined('SCOPER_QTRANSLATE_COMPAT') && awp_is_plugin_active('qtranslate');

		// buffer titles in case they were filtered previously
		$titles = scoper_get_property_array( $results, 'ID', 'post_title' );

		// depth is not really a get_pages arg, but remap exclude arg to exclude_tree if wp_list_terms called with depth=1
		if ( ! empty($args['exclude']) && empty($args['exclude_tree']) && ! empty($args['depth']) && ( 1 == $args['depth'] ) )
			if ( 0 !== strpos( $args['exclude'], ',' ) ) // work around wp_list_pages() bug of attaching leading comma if a plugin uses wp_list_pages_excludes filter
				$args['exclude_tree'] = $args['exclude'];
		//
		// === END Role Scoper ADDITION ===
		// =================================
	
		$defaults = array(
			'child_of' => 0, 'sort_order' => 'ASC',
			'sort_column' => 'post_title', 'hierarchical' => 1,
			'exclude' => array(), 'include' => array(),
			'meta_key' => '', 'meta_value' => '',
			'authors' => '', 'parent' => -1, 'exclude_tree' => '',
			'number' => '', 'offset' => 0,
			'post_type' => 'page', 'post_status' => 'publish',
			
			'depth' => 0, 'suppress_filters' => 0,
			'remap_parents' => -1,	'enforce_actual_depth' => -1,	'remap_thru_excluded_parent' => -1
		);		// Role Scoper arguments added above
		
		// === BEGIN Role Scoper ADDITION: support front-end optimization
		$post_type = ( isset( $args['post_type'] ) ) ? $args['post_type'] : $defaults['post_type'];
		
		$use_post_types = (array) scoper_get_option( 'use_post_types' );
		if ( empty( $use_post_types[$post_type] ) )
			return $results;

		if ( $scoper->is_front() ) {
			if ( ( 'page' == $post_type ) && defined( 'SCOPER_GET_PAGES_LEAN' ) ) // custom types are likely to have custom fields
				$defaults['fields'] = "$wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_parent, $wpdb->posts.post_date, $wpdb->posts.post_date_gmt, $wpdb->posts.post_status, $wpdb->posts.post_name, $wpdb->posts.post_modified, $wpdb->posts.post_modified_gmt, $wpdb->posts.guid, $wpdb->posts.menu_order, $wpdb->posts.comment_count";
			else {
				$defaults['fields'] = "$wpdb->posts.*";
				
				if ( ! defined( 'SCOPER_FORCE_PAGES_CACHE' ) )
					$no_cache = true;	// serialization / unserialization of post_content for all pages is too memory-intensive for sites with a lot of pages
			}
		} else {
			// required for xmlrpc getpagelist method	
			$defaults['fields'] = "$wpdb->posts.*";
			
			if ( ! defined( 'SCOPER_FORCE_PAGES_CACHE' ) )
				$no_cache = true;
		}
		// === END Role Scoper MODIFICATION ===
		
		$r = wp_parse_args( $args, $defaults );
		
		extract( $r, EXTR_SKIP );
		$number = (int) $number;
		$offset = (int) $offset;

		$child_of = (int) $child_of;  // Role Scoper modification: null value will confuse children array check
		
		// Make sure the post type is hierarchical
		$hierarchical_post_types = get_post_types( array( 'public' => true, 'hierarchical' => true ) );
		if ( !in_array( $post_type, $hierarchical_post_types ) )
			return $results;
	
		// Make sure we have a valid post status
		if ( !in_array($post_status, get_post_stati()) )
			return $results;
			
		// for the page parent dropdown, return no available selections for a published main page if the logged user isn't allowed to de-associate it from Main
		if ( ! empty( $name ) && ( 'parent_id' == $name ) ) {
			global $post;
			
			if ( 'no_parent_filter' == scoper_get_option( 'lock_top_pages' ) )
				return $results;
			
			if ( ! $post->post_parent && ! $GLOBALS['scoper_admin_filters']->user_can_associate_main( $post_type ) ) {
				$status_obj = get_post_status_object( $post->post_status );
				if ( $status_obj->public || $status_obj->private )
					return array();
			}
			
			if ( ! empty( $post ) && ( $post_type == $post->post_type ) ) {
				if ( $post->post_parent )
					$append_page = get_post( $post->post_parent );

				$exclude_tree = $post->ID;
			}
		}
		
		//$scoper->last_get_pages_args = $r; // don't copy entire args array unless it proves necessary
		$scoper->last_get_pages_depth = $depth;
		$scoper->last_get_pages_suppress_filters = $suppress_filters;
		
		if ( $suppress_filters )
			return $results;
		
		// === BEGIN Role Scoper MODIFICATION: wp-cache key and flag specific to access type and user/groups
		//
		if ( ! scoper_get_otype_option( 'use_object_roles', 'post', $post_type ) )
			return $results;

		$key = md5( serialize( compact(array_keys($defaults)) ) );
		$ckey = md5 ( $key . CURRENT_ACCESS_NAME_RS );
		
		$cache_flag = 'rs_get_pages';

		$cache = $current_rs_user->cache_get($cache_flag);
		
		if ( false !== $cache ) {
			if ( !is_array($cache) )
				$cache = array();

			if ( ! $no_cache && isset( $cache[ $ckey ] ) )
				// alternate filter name (WP core already applied get_pages filter)
				return apply_filters('get_pages_rs', $cache[ $ckey ], $r);
		}
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================


		$inclusions = '';
		if ( !empty($include) ) {
			$child_of = 0; //ignore child_of, parent, exclude, meta_key, and meta_value params if using include
			$parent = -1;
			$exclude = '';
			$meta_key = '';
			$meta_value = '';
			$hierarchical = false;
			$incpages = wp_parse_id_list($include);
			if ( ! empty( $incpages ) ) {
				foreach ( $incpages as $incpage ) {
					if (empty($inclusions))
						$inclusions = ' AND ( ID = ' . intval($incpage) . ' ';
					else
						$inclusions .= ' OR ID = ' . intval($incpage) . ' ';
				}
			}
		}
		if (!empty($inclusions))
			$inclusions .= ')';
	
		$exclusions = '';
		if ( !empty($exclude) ) {
			$expages = wp_parse_id_list($exclude);
			if ( ! empty( $expages) ) {
				foreach ( $expages as $expage ) {
					if (empty($exclusions))
						$exclusions = ' AND ( ID <> ' . intval($expage) . ' ';
					else
						$exclusions .= ' AND ID <> ' . intval($expage) . ' ';
				}
			}
		}
		if (!empty($exclusions))
			$exclusions .= ')';
	
		$author_query = '';
		if (!empty($authors)) {
			$post_authors = wp_parse_id_list($authors);
	
			if ( ! empty( $post_authors ) ) {
				foreach ( $post_authors as $post_author ) {
					//Do we have an author id or an author login?
					if ( 0 == intval($post_author) ) {
						$post_author = get_userdatabylogin($post_author);
						if ( empty($post_author) )
							continue;
						if ( empty($post_author->ID) )
							continue;
						$post_author = $post_author->ID;
					}
	
					if ( '' == $author_query )
						$author_query = ' post_author = ' . intval($post_author) . ' ';
					else
						$author_query .= ' OR post_author = ' . intval($post_author) . ' ';
				}
				if ( '' != $author_query )
					$author_query = " AND ($author_query)";
			}
		}
	
		$join = '';
		$where = "$exclusions $inclusions ";

		if ( ! empty( $meta_key ) || ! empty($meta_value) ) {
			$join = " INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id";   // Role Scoper modification: was LEFT JOIN in WP 3.0 core (TODO: would that botch uro join results?
			
			// meta_key and meta_value might be slashed
			$meta_key = stripslashes($meta_key);
			$meta_value = stripslashes($meta_value);
			
			if ( ! empty( $meta_key ) )
				$where .= $wpdb->prepare(" AND $wpdb->postmeta.meta_key = %s", $meta_key);
			if ( ! empty( $meta_value ) )
				$where .= $wpdb->prepare(" AND $wpdb->postmeta.meta_value = %s", $meta_value);
		}
	
		if ( $parent >= 0 )
			$where .= $wpdb->prepare(' AND post_parent = %d ', $parent);
			
		// === BEGIN Role Scoper MODIFICATION:
		// allow pages of multiple statuses to be displayed (requires default status=publish to be ignored)
		//
		$where_post_type = $wpdb->prepare( "post_type = '%s'", $post_type );
		$where_status = '';
		
		$is_front = $scoper->is_front();
		$is_teaser_active = $scoper->is_front() && scoper_get_otype_option('do_teaser', 'post') && scoper_get_otype_option('use_teaser', 'post', $post_type); 
		$private_teaser = $is_teaser_active && scoper_get_otype_option('use_teaser', 'post', $post_type) && ! scoper_get_otype_option('teaser_hide_private', 'post', $post_type);
		
		if ( $is_front && ( ! empty($current_rs_user->ID) || $private_teaser ) )
			$frontend_list_private = scoper_get_otype_option('private_items_listable', 'post', 'page');  // currently using Page option for all hierarchical types
		else
			$frontend_list_private = false;

		$force_publish_status = ! $frontend_list_private && ( 'publish' == $post_status );
			
		// WP core does not include private pages in query.  Include private statuses in anticipation of user-specific filtering		
		if ( $post_status && ( ( 'publish' != $post_status ) || ( $is_front && ! $frontend_list_private ) ) )
			$where_status = $wpdb->prepare( "post_status = '%s'", $post_status );	
		else {
			// since we will be applying status clauses based on content-specific roles and restrictions, only a sanity check safeguard is needed when post_status is unspecified or defaulted to "publish"			
			$safeguard_statuses = array();
			foreach( get_post_stati( array('internal' => false), 'object' ) as $status_name => $status_obj )
				if ( ( ! $is_front ) || $status_obj->private || $status_obj->public )
					$safeguard_statuses []= $status_name;

			$where_status = "post_status IN ('" . implode("','", $safeguard_statuses ) . "')";
		}
		
		$query = "SELECT $fields FROM $wpdb->posts $join WHERE 1=1 AND $where_post_type AND ( $where_status $where $author_query ) ORDER BY $sort_column $sort_order";

		if ( !empty($number) )
			$query .= ' LIMIT ' . $offset . ',' . $number;
			
		if ( $is_teaser_active && ! defined('SCOPER_TEASER_HIDE_PAGE_LISTING') ) {
			// We are in the front end and the teaser is enabled for pages	

			$query = apply_filters( 'objects_request_rs', $query, 'post', $post_type, array( 'force_teaser' => true ) );
			
			$pages = scoper_get_results($query);			// execute unfiltered query

			// Pass results of unfiltered query through the teaser filter.
			// If listing private pages is disabled, they will be omitted completely, but restricted published pages
			// will still be teased.  This is a slight design compromise to satisfy potentially conflicting user goals without yet another option
			
			$pages = apply_filters( 'objects_results_rs', $pages, 'post', (array) $post_type, array( 'request' => $query, 'force_teaser' => true, 'object_type' => $post_type ) );
			
			// restore buffered titles in case they were filtered previously
			scoper_restore_property_array( $pages, $titles, 'ID', 'post_title' );
			
			$pages = apply_filters('objects_teaser_rs', $pages, 'post', $post_type, array('request' => $query, 'force_teaser' => true) );
			
			if ( $frontend_list_private ) {
				if ( ! scoper_get_otype_option('teaser_hide_private', 'post', $post_type) )
					$tease_all = true;
			}

		} else {
			$_args = array( 'skip_teaser' => true, 'retain_status' => $force_publish_status );
			
			if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
				if ( $post_type_obj = get_post_type_object( $post_type ) ) {
					$plural_name = plural_name_from_cap_rs( $post_type_obj );
					$_args['alternate_reqd_caps'][0] = array( "create_child_{$plural_name}" );
				}
			}

			// Pass query through the request filter
			$query = apply_filters('objects_request_rs', $query, 'post', $post_type, $_args );

			// Execute the filtered query
			$pages = scoper_get_results($query);
			
			// restore buffered titles in case they were filtered previously
			scoper_restore_property_array( $pages, $titles, 'ID', 'post_title' );
		}

		if ( empty($pages) )
			// alternate hook name (WP core already applied get_pages filter)
			return apply_filters('get_pages_rs', array(), $r);
		
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================

		// Role Scoper note: WP core get_pages has already updated wp_cache and pagecache with unfiltered results.
		update_post_cache($pages);
		

		// === BEGIN Role Scoper MODIFICATION: Support a disjointed pages tree with some parents hidden ========
		if ( $child_of || empty($tease_all) ) {  // if we're including all pages with teaser, no need to continue thru tree remapping

			$ancestors = ScoperAncestry::get_page_ancestors(); // array of all ancestor IDs for keyed page_id, with direct parent first

			$orderby = $sort_column;

			if ( ( $parent > 0 ) || ! $hierarchical )
				$remap_parents = false;
			else {
				// if these settings were passed into this get_pages call, use them
				if ( -1 === $remap_parents )
					$remap_parents = scoper_get_option( 'remap_page_parents' );
					
				if ( $remap_parents ) {
					if ( -1 === $enforce_actual_depth )
						$enforce_actual_depth = scoper_get_option( 'enforce_actual_page_depth' );
						
					if ( -1 === $remap_thru_excluded_parent )
						$remap_thru_excluded_parent = scoper_get_option( 'remap_thru_excluded_page_parent' );
				}
			}
			
			$remap_args = compact( 'child_of', 'parent', 'exclude', 'depth', 'orderby', 'remap_parents', 'enforce_actual_depth', 'remap_thru_excluded_parent' );  // one or more of these args may have been modified after extraction 
			
			ScoperHardway::remap_tree( $pages, $ancestors, 'ID', 'post_parent', $remap_args );
		}
		// === END Role Scoper MODIFICATION ===
		// ====================================

		if ( ! empty($exclude_tree) ) {
			$exclude = array();
	
			$exclude = (int) $exclude_tree;
			$children = get_page_children($exclude, $pages);	// RS note: okay to use unfiltered function here since it's only used for excluding
			$excludes = array();
			foreach ( $children as $child )
				$excludes[] = $child->ID;
			$excludes[] = $exclude;

			$total = count($pages);
			for ( $i = 0; $i < $total; $i++ ) {
				if ( in_array($pages[$i]->ID, $excludes) )
					unset($pages[$i]);
			}
		}
		
		if ( ! empty( $append_page ) && ! empty( $pages ) ) {
			$found = false;
			foreach( array_keys($pages) as $key ) { 
				if ( $post->post_parent == $pages[$key]->ID ) {
					$found = true;
					break;	
				}
			}
			
			if ( empty($found) )
				$pages []= $append_page;
		}
		
		// re-index the array, just in case anyone cares
        $pages = array_values($pages);
        
			
		// === BEGIN Role Scoper MODIFICATION: cache key and flag specific to access type and user/groups
		//
		if ( ! $no_cache ) {
			$cache[ $ckey ] = $pages;
			$current_rs_user->cache_set($cache, $cache_flag);
		}

		// alternate hook name (WP core already applied get_pages filter)
		$pages = apply_filters('get_pages_rs', $pages, $r);
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================

		return $pages;
	}
	
	
	
	public static function remap_tree( &$items, $ancestors, $col_id, $col_parent, $args ) {
		$defaults = array(
			'child_of' => 0, 			'parent' => -1,
			'orderby' => 'post_title',	'depth' => 0,
			'remap_parents' => true, 	'enforce_actual_depth' => true,
			'exclude' => '',			'remap_thru_excluded_parent' => false
		);

		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);

		if ( $depth < 0 )
			$depth = 0;
		
		if ( $exclude )
			$exclude = wp_parse_id_list($exclude);
		
		$filtered_items_by_id = array();
		foreach ( $items as $item )
			$filtered_items_by_id[$item->$col_id] = true;

		$remapped_items = array();

		// temporary WP bug workaround
		//$any_top_items = false;
		$first_child_of_match = -1;

		// The desired "root" is included in the ancestor array if using $child_of arg, but not if child_of = 0
		$one_if_root = ( $child_of ) ? 0 : 1;

		foreach ( $items as $key => $item ) {
			if ( ! empty($child_of) ) {
				if ( ! isset($ancestors[$item->$col_id]) || ! in_array($child_of, $ancestors[$item->$col_id]) ) {
					unset($items[$key]);
					
					continue;
				}
			}
			
			$parent_id = $item->$col_parent;
			
			if ( $remap_parents ) {
				$id = $item->$col_id;
				
				if ( $parent_id && ( $child_of != $parent_id ) && isset($ancestors[$id]) ) {
					
					// Don't use any ancestors higher than $child_of
					if ( $child_of ) {
						$max_key = array_search( $child_of, $ancestors[$id] );
						if ( false !== $max_key )
							$ancestors[$id] = array_slice( $ancestors[$id], 0, $max_key + 1 );
					}
					
					// Apply depth cutoff here so Walker is not thrown off by parent remapping.
					if ( $depth && $enforce_actual_depth ) {
						if ( count($ancestors[$id]) > ( $depth - $one_if_root ) )
							unset( $items[$key]	);
					}

					if ( ! isset($filtered_items_by_id[$parent_id]) ) {
					
						// Remap to a visible ancestor, if any 
						if ( ! $depth || isset($items[$key]) ) {
							$visible_ancestor_id = 0;
	
							foreach( $ancestors[$id] as $ancestor_id ) {
								if ( isset($filtered_items_by_id[$ancestor_id]) || ($ancestor_id == $child_of) ) {
									// don't remap through a parent which was explicitly excluded
									if( $exclude && in_array( $items[$key]->$col_parent, $exclude ) && ! $remap_thru_excluded_parent )
										break;

									$visible_ancestor_id = $ancestor_id;
									break;
								}
							}
							
							if ( $visible_ancestor_id )
								$items[$key]->$col_parent = $visible_ancestor_id;

							elseif ( ! $child_of )
								$items[$key]->$col_parent = 0;
	
							// if using custom ordering, force remapped items to the bottom
							if ( ( $visible_ancestor_id == $child_of ) && ( false !== strpos( $orderby, 'order' ) ) ) {
								$remapped_items [$key]= $items[$key];
								unset( $items[$key]	);
							}
						}	
					}
				}
			} elseif ( $parent_id && ( $depth == 1 ) && ! isset($filtered_items_by_id[$parent_id]) ) { // end if not skipping page parent remap
				unset($items[$key]); // Walker will not strip this item out based on wp_list_pages depth argument if its parent is missing

				continue;
			}
			
			// temporary WP bug workaround: need to keep track of parent, for reasons described below
			if (  $child_of && ! $remapped_items ) {
				//if ( ! $any_top_items && ( 0 == $items[$key]->$col_parent ) )
				//	$any_top_items = true;

				if ( ( $first_child_of_match < 0 ) && ( $child_of == $items[$key]->$col_parent ) )
					$first_child_of_match = $key;
			}
		}
		
		// temporary WP bug workaround
		//if ( $child_of && ( $parent < 0 ) && ( ! $any_top_items ) && $first_child_of_match ) {
		if ( $child_of && ( $parent < 0 ) && $first_child_of_match ) {
			if ( $first_item = reset($items) ) {
				if ( $child_of != $first_item->$col_parent ) {
					// As of WP 2.8.4, Walker class will botch this array because it assumes that the first element in the page array is a child of the display root
					// To work around, we must move first element with the desired child_of up to the top of the array
					$_items = array( $items[$first_child_of_match] );
					
					unset( $items[$first_child_of_match] );
					$items = array_merge( $_items, $items );
				}
			}
		}

		if ( $remapped_items )
			$items = array_merge($items, $remapped_items);

	} // end function rs_remap_tree
	
} // end class ScoperHardway

?>