<?php
// In effect, override corresponding WP functions with a scoped equivalent, 
// including per-group wp_cache.  Any previous result set modifications by other plugins
// would be discarded.  These filters are set to execute as early as possible to avoid such conflict.
//
// (note: if wp_cache is not enabled, WP core queries will execute pointlessly before these filters have a chance)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

global $scoper;

require_once( dirname(__FILE__).'/hardway_rs.php' );
require_once( SCOPER_ABSPATH . '/lib/ancestry_lib_rs.php' );

// flt_get_pages is required on the front end (even for administrators) to enable the inclusion of private pages
// flt_get_terms '' so private posts are included in count, as basis for display when hide_empty arg is used
if ( $scoper->is_front() || ! is_content_administrator_rs() ) {	
	add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);   // WPML registers at priority 1
	
	// Since the NOT IN subquery is a painful aberration for filtering, replace it with the separate term query used by WP prior to 2.7
	add_filter('posts_where', array('ScoperHardwayTaxonomy', 'flt_cat_not_in_subquery'), 1);
}


/**
 * ScoperHardwayTaxonomy PHP class for the WordPress plugin Role Scoper
 * hardway-taxonomy_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 * Used by Role Scoper Plugin as a container for statically-called functions
 *
 */	
class ScoperHardwayTaxonomy
{	
	//  Scoped equivalent to WP 3.0 core get_terms
	//	Currently, scoped roles cannot be enforced without replicating the whole function 
	// 
	// Cap requirements depend on access type, and are specified by Scoper::get_terms_reqd_caps() corresponding to taxonomy in question
	public static function flt_get_terms($results, $taxonomies, $args) {
		global $wpdb;
		$empty_array = array();
		
		//d_echo( 'flt_get_terms input:' );
		
		$single_taxonomy = false;
		if ( !is_array($taxonomies) ) {
			$single_taxonomy = true;
			$taxonomies = array($taxonomies);
		}
		// === BEGIN Role Scoper MODIFICATION: single-item array is still a single taxonomy ===
		elseif( count($taxonomies) < 2 )
			$single_taxonomy = true;
		// === END Role Scoper MODIFICATION ===
		
		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists($taxonomy) ) {
				// === BEGIN Role Scoper MODIFICATION: this caused plugin activation error in some situations (though at that time, the error object was created and return on a single line, not byRef as now) ===
				//
				//$error = & new WP_Error('invalid_taxonomy', __awp('Invalid Taxonomy'));
				//return $error;
				return array();
				//
				// === END Role Scoper MODIFICATION ===
			}
		}
		
		// === BEGIN Role Scoper ADDITION: global var; various special case exemption checks ===
		//
		global $scoper;
		
		if ( $tx_obj = get_taxonomy( $taxonomies[0] ) ) {	// don't require use_taxonomies setting for link_categories or other non-post taxonomies
			if ( array_intersect( $tx_obj->object_type, get_post_types( array( 'public' => true ) ) ) ) {
				$use_taxonomies = scoper_get_option( 'use_taxonomies' );
	
				if ( empty( $use_taxonomies[$taxonomy] ) )
					return $results;
			}
		}

		// no backend filter for administrators
		$parent_or = '';
		if ( ( is_admin() || defined('XMLRPC_REQUEST') ) ) {
			if ( is_content_administrator_rs() ) {
				return $results;
			} else {
				if ( $tx = $scoper->taxonomies->get($taxonomies[0]) ) {
					// is a Category Edit form being displayed?
					if ( ! empty( $tx->uri_vars ) )
						$term_id = (int) $scoper->data_sources->detect('id', $tx);
					else
						$term_id = (int) $scoper->data_sources->detect('id', $tx->source);
					
					if ( $term_id )
						// don't filter current parent category out of selection UI even if current user can't manage it
						$parent_or = " OR t.term_id = (SELECT parent FROM $wpdb->term_taxonomy WHERE term_id = '$term_id') ";
				}
			}
		}
		
		// need to skip cache retrieval if QTranslate is filtering get_terms with a priority of 1 or less
		static $no_cache;
		if ( ! isset($no_cache) )
			$no_cache = defined( 'SCOPER_NO_TERMS_CACHE' ) || ( ! defined('SCOPER_QTRANSLATE_COMPAT') && awp_is_plugin_active('qtranslate') );
			
		// this filter currently only supports a single taxonomy for each get_terms call
		// (although the terms_where filter does support multiple taxonomies and this function could be made to do so)
		if ( ! $single_taxonomy )
				return $results;

		// link category roles / restrictions are only scoped for management (TODO: abstract this)
		if ( $single_taxonomy && ( 'link_category' == $taxonomies[0] ) && $scoper->is_front() )
			return $results;
			
		// depth is not really a get_terms arg, but remap exclude arg to exclude_tree if wp_list_terms called with depth=1
		if ( ! empty($args['exclude']) && empty($args['exclude_tree']) && ! empty($args['depth']) && ( 1 == $args['depth'] ) )
			$args['exclude_tree'] = $args['exclude'];
	
		// don't offer to set a category as its own parent
		if ( 'edit-tags.php' == $GLOBALS['pagenow'] ) {			
			if ( $tx_obj->hierarchical ) {
				if ( $editing_cat_id = (int) $scoper->data_sources->get_from_uri('id', 'term') ) {
					if ( ! empty($args['exclude']) )
						$args['exclude'] .= ',';
	
					$args['exclude'] .= $editing_cat_id;
				}
			}
		}
		
		// we'll need this array in most cases, to support a disjointed tree with some parents missing (note alternate function call - was _get_term_hierarchy)
		$children = ScoperAncestry::get_terms_children($taxonomies[0]);
		//
		// === END Role Scoper ADDITION ===
		// =================================
		

		$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";
		
		$defaults = array('orderby' => 'name', 'order' => 'ASC',
			'hide_empty' => true, 'exclude' => '', 'exclude_tree' => '', 'include' => '',
			'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
			'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '',
			'pad_counts' => false, 'offset' => '', 'search' => '', 'skip_teaser' => false,
			
			'depth' => 0,	
			'remap_parents' => -1,	'enforce_actual_depth' => -1,	'remap_thru_excluded_parent' => -1,
			'post_type' => ''
			  );	// Role Scoper arguments added above

		$args = wp_parse_args( $args, $defaults );
		$args['number'] = (int) $args['number'];
		$args['offset'] = absint( $args['offset'] );
		
		$args['child_of'] = (int) $args['child_of'];	// Role Scoper modification: null value will confuse children array check
		
		if ( !$single_taxonomy || !is_taxonomy_hierarchical($taxonomies[0]) ||
			'' !== $args['parent'] ) {
			$args['child_of'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}

		if ( 'all' == $args['get'] ) {
			$args['child_of'] = 0;
			$args['hide_empty'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}
		
		extract($args, EXTR_SKIP);
		
		// === BEGIN Role Scoper MODIFICATION: use the $children array we already have ===		
		//
		if ( 'nav-menus.php' == $GLOBALS['pagenow'] ) {
			if ( 'nav_menu' != $taxonomies[0] ) {
				if ( ! scoper_get_option( 'admin_nav_menu_filter_items' ) )
					return $results;
				else
					$hide_empty = 1;
			}
		}
		
		if ( $child_of && ! isset($children[$child_of]) )
			return array();
	
		if ( $parent && ! isset($children[$parent]) )
			return array();
			
		if ( $post_type && is_string($post_type) )
			$post_type = explode( ',', $post_type );
		
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================

		$is_term_admin = in_array( $GLOBALS['pagenow'], array( 'edit-tags.php', 'edit-link-categories.php' ) );

		$filter_key = ( has_filter('list_terms_exclusions') ) ? serialize($GLOBALS['wp_filter']['list_terms_exclusions']) : '';
		$key = md5( serialize( compact(array_keys($defaults)) ) . serialize( $taxonomies ) . $filter_key );
		
		
		// === BEGIN Role Scoper MODIFICATION: cache key specific to access type and user/groups ===
		
		// support Quick Post Widget plugin
		if ( isset($name) && 'quick_post_cat' == $name ) {
			$required_operation = 'edit';
			$post_type = 'post';
			$remap_parents = true;
		} elseif ( isset($name) && 'quick_post_new_cat_parent' == $name ) {
			$is_term_admin = true;
			$required_operation = '';
			$remap_parents = true;
		} else {
			$required_operation = '';
		}

		$object_src_name = $scoper->taxonomies->member_property($taxonomies[0], 'object_source', 'name');
		$ckey = md5( $key . serialize( $scoper->get_terms_reqd_caps($taxonomies[0], $required_operation, $is_term_admin) ) );
		
		global $current_rs_user;
		$cache_flag = 'rs_get_terms';

		$cache = $current_rs_user->cache_get( $cache_flag );
		
		if ( false !== $cache ) {
			if ( !is_array($cache) )
				$cache = array();
			
			if ( ! $no_cache && isset( $cache[ $ckey ] ) ) {
				// RS Modification: alternate filter name (get_terms filter is already applied by WP)
				remove_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);
				$terms = apply_filters('get_terms', $cache[ $ckey ], $taxonomies, $args);
				$terms = apply_filters('get_terms_rs', $terms, $taxonomies, $args);
				add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);
				return $terms;
			}
		}
		
		// buffer term names in case they were filtered previously
		if ( 'all' == $fields )
			$term_names = scoper_get_property_array( $results, 'term_id', 'name' );
		
		
		//
		// === END Role Scoper MODIFICATION ===
		// =====================================
		

		$_orderby = strtolower($orderby);
		if ( 'count' == $_orderby )
			$orderby = 'tt.count';
		else if ( 'name' == $_orderby )
			$orderby = 't.name';
		else if ( 'slug' == $_orderby )
			$orderby = 't.slug';
		else if ( 'term_group' == $_orderby )
			$orderby = 't.term_group';
		else if ( 'none' == $_orderby ) {
			$orderby = '';
			$order = '';
		} else if ( empty($_orderby) || 'id' == $_orderby )
			$orderby = 't.term_id';
		elseif ( 'order' == $_orderby )
			$orderby = 't.term_order';
		else
			$orderby = 't.name';
	
		$orderby = apply_filters( 'get_terms_orderby', $orderby, $args );

		if ( !empty($orderby) )
			$orderby = "ORDER BY $orderby";
		
		$where = '';
		
		// === Role Scoper MODIFICATION: if an include argument is provided, strip out non-matching terms after filtering is done. ===
		/*
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$exclude_tree = '';
			$interms = wp_parse_id_list($include);
			if ( count($interms) ) {
				foreach ( $interms as $interm ) {
					if (empty($inclusions))
						$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
					else
						$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
				}
			}
		}
	
		if ( !empty($inclusions) )
			$inclusions .= ')';
		$where .= $inclusions;
		*/
		// === END Role Scoper MODIFICATION ===
		
		$exclusions = '';
		
		if ( ! empty( $exclude_tree ) ) {
			// === BEGIN Role Scoper MODIFICATION: temporarily unhook this filter for unfiltered get_terms calls ===
			remove_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);
			// === END Role Scoper MODIFICATION ===
			
			$excluded_trunks = wp_parse_id_list($exclude_tree);
			foreach( (array) $excluded_trunks as $extrunk ) {
				$excluded_children = (array) get_terms($taxonomies[0], array('child_of' => intval($extrunk), 'fields' => 'ids'));
				$excluded_children[] = $extrunk;
				foreach( $excluded_children as $exterm ) {
					if ( empty($exclusions) )
						$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
					else
						$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
	
				}
			}
			
			// === BEGIN Role Scoper MODIFICATION: re-hook this filter
			add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);
			// === END Role Scoper MODIFICATION ===
		}

		if ( !empty($exclude) ) {
			$exterms = wp_parse_id_list($exclude);		
			foreach ( $exterms as $exterm ) {
				if (empty($exclusions))
					$exclusions = ' AND ( t.term_id <> "' . intval($exterm) . '" ';
				else
					$exclusions .= ' AND t.term_id <> "' . intval($exterm) . '" ';
			}
		}
	
		if ( !empty($exclusions) )
			$exclusions .= ')';
		
		// WPML attempts to pull taxonomy out of debug_backtrace() unless set in $_GET or $_POST; previous filter execution throws it off
		if ( defined('ICL_SITEPRESS_VERSION') && ! isset($_GET['taxonomy'] ) )
			$_GET['taxonomy'] = current($taxonomies);

		$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args);
		$where .= $exclusions;
	
		if ( !empty($slug) ) {
			$slug = sanitize_title($slug);
			$where .= " AND t.slug = '$slug'";
		}
	
		if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";
		
		if ( '' !== $parent ) {
			$parent = (int) $parent;
			
			// === BEGIN Role Scoper MODIFICATION: otherwise termroles only work if parent terms also have role
			if ( ( $parent ) || ('ids' != $fields) )
				$where .= " AND tt.parent = '$parent'";
			// === END Role Scoper MODIFICATION ===
		}
		
		
		// === BEGIN Role Scoper MODIFICATION: instead, manually remove truly empty cats at the bottom of this function, so we don't exclude cats with private but readable posts
		//if ( $hide_empty && !$hierarchical )
		//	$where .= ' AND tt.count > 0';
		// === END Role Scoper MODIFICATION ===
		

		// don't limit the query results when we have to descend the family tree 
		if ( ! empty($number) && ! $hierarchical && empty( $child_of ) && '' == $parent ) {
			if ( $offset )
				$limit = 'LIMIT ' . $offset . ',' . $number;
			else
				$limit = 'LIMIT ' . $number;
	
		} else
			$limit = '';
	
		if ( ! empty($search) ) {
			$search = like_escape($search);
			$where .= " AND (t.name LIKE '%$search%')";
		}
			
		$selects = array();
		switch ( $fields ) {
			case 'all':
				$selects = array('t.*', 'tt.*');
				break;
			case 'ids':
			case 'id=>parent':
				$selects = array('t.term_id', 'tt.term_taxonomy_id', 'tt.parent', 'tt.count');
				break;
			case 'names':
				$selects = array('t.term_id', 'tt.term_taxonomy_id', 'tt.parent', 'tt.count', 't.name');
				break;
			case 'count':
				$orderby = '';
				$order = '';
				$selects = array('COUNT(*)');
		}
		$select_this = implode(', ', apply_filters( 'get_terms_fields', $selects, $args ));


		// === BEGIN Role Scoper MODIFICATION: run the query through scoping filter
		//
		$query_base = "SELECT DISTINCT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE 1=1 AND tt.taxonomy IN ($in_taxonomies) $where $parent_or $orderby $order $limit";

		// only force application of scoped query filter if we're NOT doing a teaser  
		if ( 'all' == $fields )
			$do_teaser = ( $scoper->is_front() && empty($skip_teaser) && scoper_get_otype_option('do_teaser', 'post') );
		else
			$do_teaser = false;
	
		$query = apply_filters( 'terms_request_rs', $query_base, $taxonomies[0], array('skip_teaser' => ! $do_teaser, 'is_term_admin' => $is_term_admin, 'required_operation' => $required_operation, 'post_type' => $post_type ) );
		
		// if no filering was applied because the teaser is enabled, prevent a redundant query
		if ( ! empty($exclude_tree) || ($query_base != $query) || $parent || ( 'all' != $fields ) ) {
			$terms = scoper_get_results($query);
		} else
			$terms = $results;
	
		if ( 'count' == $fields ) {
			$term_count = $wpdb->get_var($query);
			return $term_count;
		}
			
		if ( ( 'all' == $fields ) && empty($include) )
			update_term_cache($terms);
			
		// RS: don't cache an empty array, just in case something went wrong
		if ( empty($terms) ) {
			return array();
		}	
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		// === BEGIN Role Scoper ADDITION: Support a disjointed terms tree with some parents hidden
		//
		if ( 'all' == $fields ) {
			$ancestors = ScoperAncestry::get_term_ancestors( $taxonomy ); // array of all ancestor IDs for keyed term_id, with direct parent first

			if ( ( $parent > 0 ) || ! $hierarchical ) {
				// in Category Edit form, need to list all editable cats even if parent is not editable
				$remap_parents = false;
				$enforce_actual_depth = true;
				$remap_thru_excluded_parent = false;
			} else {
				// if these settings were passed into this get_terms call, use them
				if ( is_admin() ) {
					$remap_parents = true;
					
				} else {
					if ( -1 === $remap_parents )
						$remap_parents = scoper_get_option( 'remap_term_parents' );
					
					if ( $remap_parents ) {
						if ( -1 === $enforce_actual_depth )
							$enforce_actual_depth = scoper_get_option( 'enforce_actual_term_depth' );
							
						if ( -1 === $remap_thru_excluded_parent )
							$remap_thru_excluded_parent = scoper_get_option( 'remap_thru_excluded_term_parent' );
					}
				}
			}
			
			$remap_args = compact( 'child_of', 'parent', 'depth', 'orderby', 'remap_parents', 'enforce_actual_depth', 'remap_thru_excluded_parent' );	// one or more of these args may have been modified after extraction 
			
			ScoperHardway::remap_tree( $terms, $ancestors, 'term_id', 'parent', $remap_args );
		}
		
		//
		// === END Role Scoper ADDITION ===
		// ================================
		
		// === BEGIN Role Scoper MODIFICATION: call alternate functions 
		// rs_tally_term_counts() replaces _pad_term_counts()
		// rs_get_term_descendants replaces _get_term_children()
		//

		if ( ( $child_of || $hierarchical ) && ! empty($children) )
			$terms = rs_get_term_descendants($child_of, $terms, $taxonomies[0]);
		
		if ( ! $terms )
			return array();
		
		// Replace DB-stored term counts with actual number of posts this user can read.
		// In addition, without the rs_tally_term_counts call, WP will hide categories that have no public posts (even if this user can read some of the pvt posts).
		// Post counts will be incremented to include child categories only if $pad_counts is true
		if ( ! defined('XMLRPC_REQUEST') && in_array( $fields, array( 'all', 'ids', 'names' ) ) && ! $is_term_admin ) {
			if ( ! is_admin() || ! in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
			
				//-- RoleScoper Modification - alternate function call (was _pad_term_counts) --//
				rs_tally_term_counts($terms, $taxonomies[0], array('pad_counts' => $pad_counts, 'skip_teaser' => ! $do_teaser, 'post_type' => $post_type ) );
			}
		}

		// Make sure we show empty categories that have children.
		if ( $hierarchical && $hide_empty ) {
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					//-- RoleScoper Modification - call alternate function (was _get_term_children) --//
					if ( $children = rs_get_term_descendants($term->term_id, $terms, $taxonomies[0]) )
						foreach ( $children as $child )
							if ( $child->count )
								continue 2;

					// It really is empty
					unset($terms[$k]);
				}
			}
		}
		reset ( $terms );
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		
		// === BEGIN Role Scoper ADDITION: hide empty cats based on actual query result instead of 'count > 0' clause, so we don't exclude cats with private but readable posts
		if ( $terms && empty( $hierarchical ) && ! empty( $hide_empty ) ) {
			foreach ( $terms as $key => $term )
				if ( ! $term->count )
					unset( $terms[$key] );
		}
		//
		// === END Role Scoper ADDITION ===
		// ================================
		
		if ( ! empty($include) ) {
			$interms = wp_parse_id_list($include);
			foreach( $terms as $key => $term ) {
				if ( ! in_array( $term->term_id, $interms ) )
					unset( $terms[$key] );
			}
		}
		
		$_terms = array();
		if ( 'id=>parent' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[$term->term_id] = $term->parent;
			$terms = $_terms;
		} elseif ( 'ids' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->term_id;
			$terms = $_terms;
		} elseif ( 'names' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->name;
			$terms = $_terms;
		}
			
		if ( 0 < $number && intval(@count($terms)) > $number ) {
			$terms = array_slice($terms, $offset, $number);
		}
		
		// === BEGIN Role Scoper MODIFICATION: cache key is specific to user/group
		//
		if ( ! $no_cache ) {
			$cache[ $ckey ] = $terms;
			$current_rs_user->cache_set( $cache, $cache_flag );
		}
		
		// RS Modification: alternate filter name (get_terms filter is already applied by WP)
		remove_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);
		$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
		$terms = apply_filters('get_terms_rs', $terms, $taxonomies, $args);
		add_filter('get_terms', array('ScoperHardwayTaxonomy', 'flt_get_terms'), 0, 3);
		
		// restore buffered term names in case they were filtered previously
		if ( 'all' == $fields )
			scoper_restore_property_array( $terms, $term_names, 'term_id', 'name' );
		//
		// === END Role Scoper MODIFICATION ===
		// ====================================
		
		//dump($terms);
		
		return $terms;
	}
	
	
	public static function flt_cat_not_in_subquery( $where ) {
		if ( strpos( $where, "ID NOT IN ( SELECT tr.object_id" ) ) {
			global $wp_query;
			global $wpdb;
			
			// Since the NOT IN subquery is a painful aberration for filtering, 
			// replace it with the separatare term query used by WP prior to 2.7
			if ( strpos( $where, "AND {$wpdb->posts}.ID NOT IN ( SELECT tr.object_id" ) ) { // global wp_query is not set on manual WP_Query calls by template code
				$whichcat = '';	
			
			//if ( ! empty($wp_query->query_vars['category__not_in']) ) {
				$ids = get_objects_in_term($wp_query->query_vars['category__not_in'], 'category');
				if ( is_wp_error( $ids ) )
					$ids = array();
				if ( is_array($ids) && count($ids > 0) ) {
					$out_posts = "'" . implode("', '", $ids) . "'";
					$whichcat .= " AND $wpdb->posts.ID NOT IN ($out_posts)";
				}
				$where = preg_replace( "/ AND {$wpdb->posts}\.ID NOT IN \( SELECT tr\.object_id [^)]*\) \)/", $whichcat, $where );
			}
		}
		
		return $where;
	}
}

?>