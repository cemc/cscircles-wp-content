<?php
// In effect, override corresponding WP functions with a scoped equivalent, 
// including per-group wp_cache.  Any previous result set modifications by other plugins
// would be discarded.  These filters are set to execute as early as possible to avoid such conflict.
//
// (note: if wp_cache is not enabled, WP core queries will execute pointlessly before these filters have a chance)

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( SCOPER_ABSPATH . '/hardway/hardway_rs.php' );  // for remap_tree()
require_once( SCOPER_ABSPATH . '/lib/ancestry_lib_rs.php' );

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
class TermsInterceptor_RS
{	
	var $current_cache_key = array();
	var $no_cache;
	
	function __construct() {
		global $scoper;

		// flt_get_pages is required on the front end (even for administrators) to enable the inclusion of private pages
		// flt_get_terms '' so private posts are included in count, as basis for display when hide_empty arg is used
		if ( ( $scoper->is_front() || ! is_content_administrator_rs() ) && ( ! defined( 'DOING_AUTOSAVE' ) || ! DOING_AUTOSAVE ) ) {
			add_filter('get_terms_args', array(&$this, 'flt_get_terms_args'), 50, 2);
			add_filter('terms_clauses', array(&$this, 'flt_terms_clauses'), 50, 3);
			add_filter('get_terms', array(&$this, 'flt_get_terms'), 0, 3);   // WPML registers at priority 1
			
			// Since the NOT IN subquery is a painful aberration for filtering, replace it with the separate term query used by WP prior to 2.7
			add_filter('posts_where', array($this, 'flt_cat_not_in_subquery'), 1);
		}
		
		if ( $scoper->is_front() && ! is_content_administrator_rs() ) {
			add_filter('get_the_terms', array(&$this, 'flt_get_the_terms'), 10, 3 );
		}
		
		$this->no_cache = defined( 'SCOPER_NO_TERMS_CACHE' ) || ( ! defined('SCOPER_QTRANSLATE_COMPAT') && awp_is_plugin_active('qtranslate') );
	}

	function flt_get_the_terms( $terms, $id, $taxonomy ) {
		if ( $terms && is_array($terms) ) {
			if ( function_exists('is_teaser_rs') && is_teaser_rs($id) )
				return $terms;
		
			static $all_terms;
			
			if ( ! isset($all_terms) )
				$all_terms = array();
			
			if ( empty($taxonomy) || ! is_scalar($taxonomy) )
				return $terms;
			
			if ( ! isset($all_terms[$taxonomy]) ) {
				$all_terms[$taxonomy] = get_terms($taxonomy, array( 'fields'=> 'ids' ) );
			}
			
			foreach( array_keys($terms) as $key ) {
				if ( ! in_array( $terms[$key]->term_id, $all_terms[$taxonomy] ) )
					unset( $terms[$key] );
			}		
		}

		return $terms;
	}
	
	function get_cache_key( $taxonomy, $args, $criteria ) {
		// $default_criteria = array( 'is_term_admin' => false, 'filter_key' => '', 'required_operation' => '' );

		$arg_ser = md5( serialize($args) );
		if ( ! isset( $this->current_cache_key[$taxonomy][$arg_ser] ) ) {
			extract( $criteria );
			if ( ! isset($required_operation) ) { $required_operation = ''; }
			$this->current_cache_key[$taxonomy][$arg_ser] = md5( $taxonomy . serialize( $args ) . $filter_key . serialize( $GLOBALS['scoper']->get_terms_reqd_caps($taxonomy, $required_operation, ! empty($is_term_admin) ) ) );
		}
		
		return $this->current_cache_key[$taxonomy][$arg_ser];
	}
	
	function doing_teaser( $args ) {
		$fields = ( isset( $args['actual_args']['fields'] ) ) ? $args['actual_args']['fields'] : $args['fields'];
		return ( ( 'all' == $fields ) && $GLOBALS['scoper']->is_front() && empty($args['skip_teaser']) && scoper_get_otype_option('do_teaser', 'post') );
	}
	
	function skip_filtering( $taxonomies, $args ) {
		global $scoper;
		
		if ( ! empty( $args['rs_no_filter'] ) )
			return true;

		// this filtering currently only supports a single taxonomy for each get_terms call
		// (although the terms_where filter does support multiple taxonomies and this function could be made to do so)
		if ( count($taxonomies) > 1 )
			return true;
		
		$taxonomy = reset($taxonomies);
		
		if ( ! $tx_obj = get_taxonomy( $taxonomy ) )
			return true;
		
		$use_taxonomies = array();
		
		if ( is_admin() ) {
			if ( ( 'nav-menus.php' == $GLOBALS['pagenow'] ) && ( 'nav_menu' != $taxonomy ) ) {
				if ( ! scoper_get_option( 'admin_nav_menu_filter_items' ) )
					return true;
			}
		} else {
			if ( array_intersect( $tx_obj->object_type, get_post_types( array( 'public' => true ) ) ) ) {
				$use_taxonomies = scoper_get_option( 'use_taxonomies' );
				if ( empty( $use_taxonomies[$taxonomy] ) ) {
					return true;
				}
			}
		}

		// no wp-admin filtering for administrators (filters not added in that case)
		if ( ( is_admin() || defined('XMLRPC_REQUEST') ) && is_content_administrator_rs() )
			return true;
			
		// link category roles / restrictions are only scoped for management (TODO: review this)
		if ( ( 'link_category' == $taxonomy ) && $scoper->is_front() )
			return true;
		
		if ( ! empty( $args['object_ids'] ) && empty( $args['required_operation'] ) )  // WP 4.7 pushes wp_get_object_terms call through get_terms()
			return true;
		
		if ( $args['child_of'] || $args['parent'] ) {
			$children = ScoperAncestry::get_terms_children($taxonomy);
			
			if ( $args['child_of'] && ! isset($children[ $args['child_of'] ]) )
				return 'empty_result';  // get_terms filter will return empty result array
		
			if ( $args['parent'] && ! isset($children[ $args['parent'] ]) )
				return 'empty_result';  // get_terms filter will return empty result array
		}
		
		return false;
	}
	
	
	function get_filter_criteria( $args ) {
		$return = array();
	
		$return['filter_key'] = ( has_filter('list_terms_exclusions') ) ? serialize($GLOBALS['wp_filter']['list_terms_exclusions']) : '';
		
		if ( empty($args['required_operation']) ) {
			// support Quick Post Widget plugin
			if ( ! empty($args['name']) && ( 'quick_post_cat' == $args['name'] ) ) {
				$return['required_operation'] = 'edit';
				$return['post_type'] = 'post';
				$return['remap_parents'] = true;
			}

			if ( is_admin() && isset($GLOBALS['plugin_page']) && ( 's2' == $GLOBALS['plugin_page'] ) ) {
				$return['required_operation'] = 'read';
			}
		}

		if ( isset( $args['is_term_admin'] ) && ( '' === $args['is_term_admin'] ) ) {
			$return['is_term_admin'] = in_array( $GLOBALS['pagenow'], array( 'edit-tags.php', 'edit-link-categories.php' ) );
		} else {
			// support Quick Post Widget plugin
			if ( ! empty($args['name']) && ( 'quick_post_new_cat_parent' == $args['name'] ) ) {
				$return['is_term_admin'] = true;
				$return['remap_parents'] = true;
			}
		}

		return $return;
	}
	
	function flt_get_terms_args( $args, $taxonomies ) {
		if ( $this->skip_filtering( $taxonomies, $args ) )
			return $args;
		
		$taxonomy = reset($taxonomies);
	
		$rs_defaults = array(
			'depth' => 0,			'skip_teaser' => false,
			'remap_parents' => -1,	'enforce_actual_depth' => -1,	'remap_thru_excluded_parent' => -1,
			'post_type' => '',		'required_operation' => '',		'is_term_admin' => '',
		);
		$args = wp_parse_args( $args, $rs_defaults );

		$args['child_of'] = (int) $args['child_of'];  // null value will confuse subsequent RS checks
		
		if ( $args['post_type'] && is_string($args['post_type']) )
			$args['post_type'] = explode( ',', $args['post_type'] );
		
		// depth is not really a get_terms arg, but remap exclude arg to exclude_tree if wp_list_terms called with depth=1
		if ( ! empty($args['exclude']) && empty($args['exclude_tree']) && ( 1 == $args['depth'] ) ) {
			$args['exclude_tree'] = $args['exclude'];
		}

		if ( is_admin() ) {
			global $wpdb;
		
			switch( $GLOBALS['pagenow'] ) {
				case 'edit-tags.php' :
					$tx_obj = get_taxonomy( $taxonomy );
					if ( $tx_obj->hierarchical && ! empty( $_REQUEST['tag_ID'] ) ) {
						$editing_term_id = intval($_REQUEST['tag_ID']);
					
						// don't offer to set a category as its own parent
						if ( ! empty($args['exclude']) )
							$args['exclude'] .= ',';
						
						$args['exclude'] .= (int) $_REQUEST['tag_ID'];
					}
					
				break;
				case 'nav-menus.php' :
					if ( ( 'nav_menu' != $taxonomy ) && scoper_get_option( 'admin_nav_menu_filter_items' ) )
						$args['hide_empty'] = true;

				break;
				default:
			} // end switch
		}
		
		// turn off core post-processing of the result set, but buffer actual arg values for equivalent RS post-processing
		$buffer_args = array();
		foreach ( array( 'child_of' => 0, 'pad_counts' => false, 'hide_empty' => false, 'number' => '' ) as $arg_name => $force_val ) {
			if ( $args[$arg_name] != $force_val ) {
				$buffer_args[$arg_name] = $args[$arg_name];
				$args[$arg_name] = $force_val;
			}
		}
		
		if ( in_array( $args['fields'], array( 'ids', 'names', 'id=>parent' ) ) ) {
			// flt_get_terms needs parent col in intermediate result set even if final result set will be ids only, will convert result set back to expected format before returning
			add_filter( 'get_terms_fields', create_function( '$a,$b', "return array('t.*', 'tt.*');" ), 1, 2 );

			$buffer_args['fields'] = $args['fields'];
			$args['fields'] = 'force_all';
		}
		
		if ( $buffer_args )
			$args['actual_args'] = $buffer_args;
		
		return $args;
	}
	
	function flt_terms_clauses($clauses, $taxonomies, $args) {
		if ( $skip = $this->skip_filtering( $taxonomies, $args ) )
			return $clauses;
		
		global $scoper;
		
		$taxonomy = reset($taxonomies);
		extract( $args, EXTR_SKIP );
		
		$criteria = $this->get_filter_criteria( $args );
		extract( $criteria );	// sets $is_term_admin, $filter_key, $required_operation' (may also force $post_type and $remap_parents)

		if ( ! $this->no_cache ) {
			$ckey = $this->get_cache_key( $taxonomy, $args, $criteria );
		
			$cache = $GLOBALS['current_rs_user']->cache_get( 'rs_get_terms' );
			if ( false !== $cache ) {
				if ( !is_array($cache) )
					$cache = array();

				if ( isset( $cache[ $ckey ] ) ) {
					return $clauses;	// flt_get_terms() will return a cached result, so no need to further process query pieces
				}
			}
		}
		
		if ( ( 0 === $parent ) && ('ids' == $fields) ) {
			// otherwise termroles only work if parent terms also have role
			$clauses['where'] = str_replace( " AND tt.parent = '$parent'", '', $clauses['where'] );
		}
		
		if ( $hide_empty && ! $orig_args['hide_empty'] && ! $hierarchical ) {	// hide_empty may have been set by modify_args()
			$clauses['where'] .= ' AND tt.count > 0';
		}
		
		if ( is_admin() && ( 'edit-tags.php' == $GLOBALS['pagenow'] ) && ! empty( $_REQUEST['tag_ID'] ) ) {
			$tx_obj = get_taxonomy( $taxonomy );
			if ( $tx_obj->hierarchical ) {
				// don't filter current parent category out of selection UI even if current user can't manage it
				$clauses['where'] .= " OR t.term_id = (SELECT parent FROM {$GLOBALS['wpdb']->term_taxonomy} WHERE term_id = '" . intval($_REQUEST['tag_ID']) . "') ";
			}
		}

		// we forced $args['fields'] to preserve parent col in result set, but now filter clause back to proper fields
		if ( isset($actual_args['fields']) ) {
			$selects = array('t.term_id', 'tt.parent', 'tt.count');
			
			if ( 'names' == $actual_args['fields'] )
				$selects []= 't.name';

			$clauses['fields'] = implode(', ', apply_filters( 'get_terms_fields', $selects, $args ));
			
			$fields = $actual_args['fields'];	// for term_taxonomy_id check below
		}
		
		if ( in_array( $fields, array( 'ids', 'id=>parent', 'names' ) ) ) {
			if ( $clauses['fields'] && ( false === strpos( $clauses['fields'], 'tt.term_taxonomy_id' ) ) && ( false === strpos( $clauses['fields'], '*' ) ) )
				$clauses['fields'] .= ', tt.term_taxonomy_id';
		}

		// only force application of scoped query filter if we're NOT doing a teaser
		$term_id_col = ( strpos( $clauses['join'], ' AS tt' ) ) ? 'tt.term_taxonomy_id' : 't.term_id';
		$clauses['where'] = apply_filters( 'terms_where_rs', $clauses['where'], $taxonomy, array( 'term_id_col' => $term_id_col, 'skip_teaser' => ! $this->doing_teaser($args), 'is_term_admin' => $is_term_admin, 'required_operation' => $required_operation, 'post_type' => $post_type ) );

		// WPML attempts to pull taxonomy out of debug_backtrace() unless set in $_GET or $_POST; previous filter execution throws it off
		if ( defined('ICL_SITEPRESS_VERSION') && ! isset($_GET['taxonomy'] ) )
			$_GET['taxonomy'] = $taxonomy;

		if ( 0 !== strpos( $clauses['fields'], 'DISTINCT ' ) )
			$clauses['fields'] = 'DISTINCT ' . $clauses['fields'];
		
		return $clauses;
	}

	// Cap requirements depend on access type, and are specified by Scoper::get_terms_reqd_caps() corresponding to taxonomy in question
	function flt_get_terms($terms, $taxonomies, $args) {
		if ( empty($terms) ) {
			return array();
		}

		if ( $skip = $this->skip_filtering( $taxonomies, $args ) ) {
			if ( 'return_empty' === $skip ) {
				return array();
			} else {
				return $terms;
			}
		}

		$taxonomy = reset($taxonomies);
		extract( $args, EXTR_SKIP );
		
		$criteria = $this->get_filter_criteria( $args );
		extract( $criteria );	// sets $is_term_admin, $filter_key, $required_operation' (may also force $post_type and $remap_parents)
		
		//d_echo( 'flt_get_terms input:' );
		//dump($terms);

		if ( ! $this->no_cache ) {
			// NOTE: this caching eliminates both the results post-processing below and query clause filtering in flt_terms_clauses()
			$ckey = $this->get_cache_key( $taxonomy, $args, $criteria );
			$cache_flag = 'rs_get_terms';

			$cache = $GLOBALS['current_rs_user']->cache_get( $cache_flag );
			if ( false !== $cache ) {
				if ( !is_array($cache) )
					$cache = array();
				
				if ( isset( $cache[ $ckey ] ) ) {
					return $cache[ $ckey ];
				}
			}
		}
		
		// if some args were forced to prevent core post-processing, restore actual values now
		if ( ! empty( $args['actual_args'] ) )
			extract( $args['actual_args'] );

		// we'll need this array in most cases, to support a disjointed tree with some parents missing (note alternate function call - was _get_term_hierarchy)
		$children = ScoperAncestry::get_terms_children( $taxonomy );

		if ( 'all' == $fields ) {
			// buffer term names in case they were filtered previously
			$term_names = scoper_get_property_array( $terms, 'term_id', 'name' );
		
			$ancestors = ScoperAncestry::get_term_ancestors( $taxonomy ); // array of all ancestor IDs for keyed term_id, with direct parent first

			if ( ( $parent > 0 ) || ! $hierarchical ) {
				// in Term Edit form, need to list all editable terms even if parent is not editable
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
			
			$remap_args = compact( 'child_of', 'parent', 'depth', 'orderby', 'remap_parents', 'enforce_actual_depth', 'remap_thru_excluded_parent' );
			ScoperHardway::remap_tree( $terms, $ancestors, 'term_id', 'parent', $remap_args );
		}

		if ( ( $child_of || $hierarchical ) && ! empty($children) )
			$terms = rs_get_term_descendants($child_of, $terms, $taxonomy);		// rs_get_term_descendants is RS equivalent to WP _get_term_children()
		
		if ( ! $terms )
			return array();
		
		// Replace DB-stored term counts with actual number of posts this user can read.
		// In addition, without the rs_tally_term_counts() call, WP will hide terms that have no public posts (even if this user can read some of the pvt posts).
		// Post counts will be incremented to include child terms only if $pad_counts is true
		if ( ! defined('XMLRPC_REQUEST') && in_array( $fields, array( 'all', 'ids', 'names' ) ) && empty($is_term_admin) ) {
			if ( ! is_admin() || ! in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
				// rs_tally_term_counts() is RS equivalent to WP _pad_term_counts()
				if ( ! isset($post_type) ) $post_type = '';
				rs_tally_term_counts($terms, $taxonomy, array('pad_counts' => $pad_counts, 'skip_teaser' => ! $this->doing_teaser($args), 'post_type' => $post_type ) );
			}
		}

		// Empty terms will be identified via count property set by rs_tally_term_counts() instead of 'count > 0' clause, to reflect logged user's actual post access (including readable private posts)
		if ( $hide_empty ) {
			if ( $hierarchical ) {
				// Remove empty categories, but only if their descendants are all empty too.
				foreach ( $terms as $k => $term ) {
					if ( ! $term->count ) {
						if ( $descendants = rs_get_term_descendants($term->term_id, $terms, $taxonomies[0]) ) {
							foreach ( $descendants as $child ) {
								if ( $child->count )
									continue 2;
							}
						}

						// It really is empty
						unset($terms[$k]);
					}
				}
			} else {		
				foreach ( $terms as $key => $term )
					if ( ! $term->count )
						unset( $terms[$key] );
			}
		}
		reset ( $terms );
		
		// === Standard WP post-processing for include, fields, number args ===
		//
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
		// === end standard WP block ===
		
		if ( ! $this->no_cache ) {
			$cache[ $ckey ] = $terms;
			$GLOBALS['current_rs_user']->cache_set( $cache, $cache_flag );
		}
		
		// restore buffered term names in case they were filtered previously
		if ( 'all' == $fields )
			scoper_restore_property_array( $terms, $term_names, 'term_id', 'name' );

		return $terms;
	}
	
	function flt_cat_not_in_subquery( $where ) {
		if ( strpos( $where, "ID NOT IN ( SELECT tr.object_id" ) ) {
			global $wp_query;
			global $wpdb;
			
			// Since the NOT IN subquery is a painful aberration for filtering, 
			// replace it with the separatare term query used by WP prior to 2.7
			if ( strpos( $where, "AND {$wpdb->posts}.ID NOT IN ( SELECT tr.object_id" ) ) { // global wp_query is not set on manual WP_Query calls by template code
				$whichcat = '';	

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