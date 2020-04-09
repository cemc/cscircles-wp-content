<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

add_filter('comments_array', array('QueryInterceptorFront_NonAdmin_RS', 'flt_comments_results'), 99);

add_filter( 'wp_get_nav_menu_items', array('QueryInterceptorFront_NonAdmin_RS', 'flt_nav_menu_items'), 50, 3);

global $wp_query;
if ( is_object($wp_query) && method_exists($wp_query, 'is_tax') && $wp_query->is_tax() )
	add_filter('posts_where', array('QueryInterceptorFront_NonAdmin_RS', 'flt_p2_where'), 1 );
	

class QueryInterceptorFront_NonAdmin_RS {
	
	// force scoping filter to process the query a second time, to handle the p2 clause imposed by WP core for custom taxonomy requirements
	public static function flt_p2_where( $where ) {
		if ( strpos( $where, 'p2.post_status' ) )
			$where = apply_filters( 'objects_where_rs', $where, 'post', '', array( 'source_alias' => 'p2' ) );

		return $where;
	}
	
	// Strips comments from teased posts/pages
	public static function flt_comments_results($results) {
		global $scoper;
	
		if ( $results && ! empty($scoper->teaser_ids) ) {
			foreach ( $results as $key => $row )
				if ( isset($row->comment_post_ID) && isset($scoper->teaser_ids['post'][$row->comment_post_ID]) )
					unset( $results[$key] );
		}
		
		return $results;
	}
	
	public static function flt_nav_menu_items( $items, $menu_name, $args ) {
		global $wpdb;
		$item_types = array();
		
		foreach ( $items as $key => $item ) {
			if ( ! isset( $item_types[ $item->type ] ) )
				$item_types[ "{$item->type}" ] = array();
				
			if ( ! isset( $item_types[ $item->type ][$item->object ] ) )
				$item_types[ $item->type ][ $item->object ] = array( $key => $item->object_id );
			else
				$item_types[ $item->type ][ $item->object ] [$key] = $item->object_id;
		} 
		
		$teaser_enabled = scoper_get_otype_option( 'do_teaser', 'post' );
		
		// remove unreadable terms	
		if ( isset( $item_types['taxonomy'] ) ) {
			foreach( $item_types['taxonomy'] as $taxonomy => $item_ids ) {
				if ( $teaser_enabled ) {
					if ( $taxonomy_obj = get_taxonomy( $taxonomy ) ) {
						foreach( $taxonomy_obj->object_type as $post_type ) {	// don't remove a term if it is associated with a post type that's being teased
							if ( scoper_get_otype_option( 'use_teaser', 'post', $post_type ) )
								continue 2;
						}
					}
				}

				/*
				$query_base = "SELECT t.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE 1=1 AND tt.taxonomy = '$taxonomy'";
				$query = apply_filters( 'terms_request_rs', $query_base, $taxonomy ); //, array( 'skip_teaser' => true ) );
				$okay_ids = scoper_get_col($query);
				*/

				$hide_empty = isset( $args['hide_empty'] ) ? $args['hide_empty'] : 0;

				$okay_ids = get_terms( $taxonomy, "fields=ids&hierarchical=0&hide_empty=$hide_empty" );

				if ( $remove_ids = array_diff( $item_ids, $okay_ids ) )
					$items = array_diff_key( $items, $remove_ids );
			}
		}
		
		// remove unreadable posts
		if ( isset( $item_types['post_type'] ) ) {
			foreach( $item_types['post_type'] as $post_type => $item_ids ) {
				$where = apply_filters( 'objects_where_rs', '', 'post', $post_type, array( 'skip_teaser' => true ) );
				$okay_ids = scoper_get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = '$post_type' $where AND ID IN ('" . implode("','", $item_ids ) . "')" );
				
				if ( $remove_ids = array_diff( $item_ids, $okay_ids ) ) {
					if ( $teaser_enabled && scoper_get_otype_option( 'use_teaser', 'post', $post_type ) ) {
						require_once( dirname(__FILE__).'/teaser_rs.php' );
						$teaser_prepend = ScoperTeaser::get_teaser_text( 'prepend', 'name', 'post', $post_type );
						$teaser_append = ScoperTeaser::get_teaser_text( 'append', 'name', 'post', $post_type );
						
						foreach( array_keys($remove_ids) as $key )
							$items[$key]->title = $teaser_prepend . $items[$key]->title . $teaser_append;
					} else
						$items = array_diff_key( $items, $remove_ids );
				}
			}
		}
		
		return $items;
	}
}
?>