<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

add_filter('get_terms', array('ScoperHardwayFront', 'flt_get_tags'), 50, 3);

add_filter('get_the_category_for_list', array('ScoperHardwayFront', 'flt_get_the_category') );		// TODO: eliminate this - published in RS forum as a WP hack for use with a dev version
add_filter('get_the_category', array('ScoperHardwayFront', 'flt_get_the_category'), 10, 2);			// Todo: get this hook added to WP source

if ( awp_is_plugin_active('snazzy-archives') )
	add_filter( 'query', array('ScoperHardwayFront', 'flt_snazzy_archives') );

if ( ! awp_ver( '3.3' ) )
	add_action( 'admin_bar_menu', array('ScoperHardwayFront', 'flt_admin_bar_menu'), 50 );

//if ( defined( 'SCOPER_FORCE_FRONTEND_JQUERY' ) )
//	add_action( 'wp_head', array('ScoperHardwayFront', 'include_jquery') );

add_action( 'wp_print_footer_scripts', array('ScoperHardwayFront', 'flt_hide_empty_menus') );

if ( _rs_is_active_widget_prefix( 'calendar-' ) )
	add_filter( 'query', array( 'ScoperHardwayFront', 'flt_calendar' ) );

function _rs_is_active_widget_prefix( $id_prefix ) {
	global $wp_registered_widgets;

	foreach ( (array) wp_get_sidebars_widgets() as $sidebar => $widgets ) {
		if ( 'wp_inactive_widgets' != $sidebar && is_array($widgets) ) {
			foreach ( $widgets as $widget ) {
				if ( isset($wp_registered_widgets[$widget]['id']) && 0 === strpos( $wp_registered_widgets[$widget]['id'], $id_prefix ) )
					return $sidebar;
			}
		}
	}

	return false;
}
	
class ScoperHardwayFront
{	
	public static function include_jquery() {
		wp_enqueue_script( 'jquery' );	
	}

	public static function flt_hide_empty_menus() {
		if ( ! wp_script_is('jquery') )
			return;
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$("ul.menu").not(":has(li)").closest('div').prev('h3.widget-title').hide();
});
/* ]]> */
</script><?php
	}

	// filter "Add New" links out of admin bar if user lacks site-wide capability
	public static function flt_admin_bar_menu( &$bar ) {
		if ( is_content_administrator_rs() )
			return;

		$type = 'new-content';
			
		foreach( get_post_types( array( 'public' => true ), 'object' ) as $_post_type => $type_obj ) {
			$var = 'new-' . $_post_type;
			
			if ( isset( $bar->menu->{$type}['children']->{$var} ) ) {
				if ( ! cr_user_can( $type_obj->cap->edit_posts, 0, 0, array('skip_id_generation' => true, 'skip_any_object_check' => true ) ) )
					unset( $bar->menu->{$type}['children']->{$var} );
			}
		}
	}

	public static function flt_get_the_category( $cats, $context = '' ) {
		if ( $context && ( 'display' != $context ) )
			return;
	
		$readable_cats = apply_filters( 'get_terms', array(), 'category', array('fields' => 'ids', 'skip_teaser' => true) );

		foreach ( $cats as $key => $cat )
			if ( ! in_array($cat->term_id, $readable_cats) )
				unset( $cats[$key] );
	
		return $cats;
	}
	
	public static function flt_calendar( $query ) {
		if ( strpos( $query, "DISTINCT DAYOFMONTH" ) || strpos( $query, "post_title, DAYOFMONTH(post_date)" ) || strpos( $query, "MONTH(post_date) AS month" ) ) {
			$query = apply_filters( 'objects_request_rs', $query, 'post', '' );
		}
		
		return $query;
	}
	
	public static function flt_snazzy_archives( $query ) {
		if ( strpos( $query, "posts WHERE post_status = 'publish' AND post_password = '' AND post_type IN (" ) ) {
			
			// TODO: update this to deal with custom types
			
			// query parsing currently does not deal with IN syntax for post_type
			if ( strpos( $query, "('post','page')" ) ) {
				$object_type = array( 'post', 'page' );
				$query = str_replace( "post_type IN ('post','page')", "( post_type = 'post' OR post_type = 'page')", $query );
			
			} elseif ( strpos( $query, "('post')" ) ) {
				$object_type = 'post';
				$query = str_replace( "post_type IN ('post')", "post_type = 'post'", $query );
				
			} elseif ( strpos( $query, "('page')" ) ) {
				$object_type = 'page';
				$query = str_replace( "post_type IN ('page')", "post_type = 'page'", $query );
			}
			
			$query = str_replace( "post_status = 'publish' AND ", '', $query );

			$query = apply_filters( 'objects_request_rs', $query, 'post', $object_type );
		}
		
		return $query;
	}
	
	/* RS 1.1 no longer adds any join clauses, so this WP shortcoming is moot for us
	
	// wp_get_archives uses unfilterable SELECT * for postbypost archive type
	function flt_log_getarchives( $query ) {
		add_filter( 'query', array('ScoperHardwayFront', 'flt_archives_bugstomper') );

		return $query;
	}

	function flt_archives_bugstomper( $query ) {
		if ( strpos( $query, 'ELECT * FROM' ) ) {
			global $wpdb;
			
			$query = str_replace( "SELECT * FROM $wpdb->posts", "SELECT DISTINCT $wpdb->posts.* FROM $wpdb->posts", $query );
		
			remove_filter( 'query', array('ScoperHardwayFront', 'flt_archives_bugstomper') );
		}

		return $query;
	}
	*/

	public static function flt_get_tags( $results, $taxonomies, $args ) {
		if ( ! is_array($taxonomies) )
			$taxonomies = (array) $taxonomies;

		if ( ('post_tag' != $taxonomies[0]) || (count($taxonomies) > 1) )
			return $results;
			
		global $wpdb;

		$defaults = array(
		'exclude' => '', 'include' => '',
		'number' => '', 'offset' => '', 'slug' => '', 
		'name__like' => '', 'search' => '', 'hide_empty' => true );
		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);
		
		if ( ( 'ids' == $fields ) || ! $hide_empty )
			return $results;

		global $scoper, $current_rs_user;
		
		$filter_key = ( has_filter('list_terms_exclusions') ) ? serialize($GLOBALS['wp_filter']['list_terms_exclusions']) : '';
		$ckey = md5( serialize( compact(array_keys($defaults)) ) . serialize( $taxonomies ) . $filter_key );
		$cache_flag = 'rs_get_terms';

		if ( $cache = $current_rs_user->cache_get( $cache_flag ) )
			if ( isset( $cache[ $ckey ] ) )
				return apply_filters('get_tags_rs', $cache[ $ckey ], 'post_tag', $args);
				
		//------------ WP argument application code from get_terms(), with hierarchy-related portions removed -----------------
		//
		// NOTE: must change 'tt.count' to 'count' in orderby and hide_empty settings
		//		 Also change default orderby to name
		//
		$where = '';
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$exclude_tree = '';
			$interms = wp_parse_id_list($include);
			if ( count($interms) ) {
				foreach ( (array) $interms as $interm ) {
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
	
		$exclusions = '';
		if ( !empty($exclude) ) {
			$exterms = wp_parse_id_list($exclude);
			if ( count($exterms) ) {
				foreach ( (array) $exterms as $exterm ) {
					if ( empty($exclusions) )
						$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
					else
						$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
				}
			}
		}
	
		if ( !empty($exclusions) )
			$exclusions .= ')';
		$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args );
		$where .= $exclusions;
	
		if ( !empty($slug) ) {
			$slug = sanitize_title($slug);
			$where .= " AND t.slug = '$slug'";
		}
	
		if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";
	
		// don't limit the query results when we have to descend the family tree 
		if ( ! empty($number) ) {
			if( $offset )
				$limit = 'LIMIT ' . $offset . ',' . $number;
			else
				$limit = 'LIMIT ' . $number;
	
		} else
			$limit = '';
	
		if ( !empty($search) ) {
			$search = like_escape($search);
			$where .= " AND (t.name LIKE '%$search%')";
		}
		// ------------- end get_terms() argument application code --------------
		
		$post_type = cr_find_post_type();
		
		// embedded select statement for posts ID IN clause
		$posts_qry = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE 1=1";
		$posts_qry = apply_filters('objects_request_rs', $posts_qry, 'post', $post_type, array('skip_teaser' => true));

		$qry = "SELECT DISTINCT t.*, tt.*, COUNT(p.ID) AS count FROM $wpdb->terms AS t"
			. " INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id AND tt.taxonomy = 'post_tag'"
			. " INNER JOIN $wpdb->term_relationships AS tagr ON tagr.term_taxonomy_id = tt.term_taxonomy_id"
			. " INNER JOIN $wpdb->posts AS p ON p.ID = tagr.object_id WHERE p.ID IN ($posts_qry)"
			. " $where GROUP BY t.term_id ORDER BY count DESC $limit";  // must hardcode orderby clause to always query top tags
			
		$results = scoper_get_results( $qry );

		$cache[ $ckey ] = $results;
		$current_rs_user->cache_set( $cache, $cache_flag );
		
		$results = apply_filters('get_tags_rs', $results, 'post_tag', $args);

		return $results;
	}

} // end class
?>