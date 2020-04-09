<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class QueryInterceptorBase_RS {
	var $scoper;
	
	function __construct() {
		$this->scoper =& $GLOBALS['scoper'];

		add_filter('posts_where', array(&$this, 'flt_defeat_publish_filter'), 40); // have to run this filter before QueryInterceptor_RS::flt_objects_where
		
		add_filter('objects_listing_rs', array(&$this, 'flt_objects_listing'), 50, 4);

		$arg_str = '$a';
		foreach ( $this->scoper->data_sources->get_all() as $src_name => $src ) {
			if ( isset($src->query_hooks->listing) ) {
				// Call our abstract handlers with a lambda function that passes in original hook name
				// In effect, make WP pass the hook name so multiple hooks can be registered to a single handler 
				$rs_args = "'$src_name', '', '' ";
				$func = "return apply_filters( 'objects_listing_rs', $arg_str , $rs_args );";
				add_filter( $src->query_hooks->listing, create_function( $arg_str, $func ), 50, 1 );	
				//d_echo ("adding filter: $original_hook -> $func <br />");
			}
		} //foreach data_sources
	}

	
	// Eliminate a primary plugin incompatibility by replacing front-end status='publish' requirement with scoped equivalent 
	// (i.e. include private posts/pages that this user has access to via RS role assignment).  
	//
	// Also defeats status requirement imposed by WP core when query includes a custom taxonomy requirement
	function flt_defeat_publish_filter($where) {
		$post_type = cr_find_post_type();
		$post_type_obj = get_post_type_object( $post_type );

		$object_id = scoper_get_object_id( 'post', $post_type );
		
		// don't alter the query if RS query filtering is disabled, or if this maneuver has been disabled via constant
		// note: for non-administrators, QueryInterceptor_RS::flt_objects_where will convert the publish requirement to publish OR private, if the user's blog role or RS-assigned roles grant private access
		if ( ! is_content_administrator_rs() || defined('SCOPER_RETAIN_PUBLISH_FILTER') || defined('DISABLE_QUERYFILTERS_RS') )
			return $where;
		
		global $wp_query;

		if ( is_admin() && ! empty( $wp_query->query['post_status'] ) )  // if a specific status was requested by URI, don't force inclusion of others
			return $where;
			
		// don't alter the where clause if in wp-admin and not filtering by taxonomy
		if ( is_admin() ) {
			global $wp_query;
			
			if ( empty($wp_query) && empty($wp_query->is_tax) )
				return $where;	
		}
			
		global $wpdb, $current_user;

		// don't alter the where clause for anonymous users
		if ( empty( $current_user->ID ) )
			return $where;

		$where = preg_replace( "/$wpdb->posts.post_status\s*=\s*'publish'/", "($wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'private')", $where);
		$where = preg_replace( "/p2.post_status\s*=\s*'publish'/", "(p2.post_status = 'publish' OR p2.post_status = 'private')", $where);
		$where = preg_replace( "/p.post_status\s*=\s*'publish'/", "(p.post_status = 'publish' OR p.post_status = 'private')", $where);

		return $where;
	}
	
	// can't do this from posts_results or it will throw off found_rows used for admin paging
	function flt_objects_listing($results, $src_name, $object_types, $args = array()) {
		global $wpdb;

		// it's not currently necessary or possible to log listed revisions from here
		//if ( isset($wpdb->last_query) && strpos( $wpdb->last_query, "post_type = 'revision'") )
		//	return $results;

		// if currently listed IDs are not already in post_cache, make our own equivalent memcache
		// ( create this cache for any data source, front end or admin )
		if ( 'post' == $src_name )
			global $wp_object_cache;
		
		$listed_ids = array();
		
		//if ( ('post' != $src_name) || empty($wp_object_cache->cache['posts']) ) {
			if ( empty($this->scoper->listed_ids[$src_name]) ) {
				
				if ( $col_id = $this->scoper->data_sources->member_property( $src_name, 'cols', 'id' ) ) {
		
					$listed_ids = array();
					
					// In edit.php, WP forces all objects into recordset for hierarchical post types.  But for perf enchancement, we need to know IDs of items which are actually listed
					if ( 'edit.php' == $GLOBALS['pagenow'] ) {
						$post_type = ( ! empty( $_GET['post_type'] ) ) ? sanitize_key( $_GET['post_type'] ) : 'post';
						$determine_listed_ids = ! is_content_administrator_rs() && is_post_type_hierarchical( $post_type ) && ! empty( $GLOBALS['query_interceptor']->last_request[$src_name] ) && ! strpos( $GLOBALS['query_interceptor']->last_request[$src_name], 'LIMIT ' );

						if ( $determine_listed_ids ) {
							// mimic recordset paging used in edit.php						
							$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
							if ( empty($pagenum) )
								$pagenum = 1;
							$edit_per_page = 'edit_' . $post_type . '_per_page';
							$per_page = (int) get_user_option( $edit_per_page );
							if ( empty( $per_page ) || $per_page < 1 )
								$per_page = 20;
							
							$per_page = apply_filters( $edit_per_page, $per_page );
							$per_page = apply_filters( 'edit_posts_per_page', $per_page, $post_type );

							if ( count($results) <= $per_page )
								$determine_listed_ids = false;
						}
					} else
						$determine_listed_ids = false;
	
					if ( $determine_listed_ids ) {
						// Construct and execute a secondary query (for IDs only) which includes the paging clause that would be used if edit.php did not defeat it
						$pgstrt = ($pagenum - 1) * $per_page . ', ';
						$limits = ' LIMIT ' . $pgstrt . $per_page;
						
						global $wpdb;
						$qry = $GLOBALS['query_interceptor']->last_request[$src_name] . $limits;
						$qry = str_replace ( "$wpdb->posts.*", "$wpdb->posts.ID", $qry );
						
						$_results = scoper_get_results( $qry );
						foreach ( $_results as $row ) {
							if ( isset($row->$col_id) )
								$listed_ids [$row->$col_id] = true;
						}
					} else {	
						// No secondary query, just buffer all IDs in the results set					
						foreach ( $results as $row )
							if ( isset($row->$col_id) )
								$listed_ids [$row->$col_id] = true;
					}
					
					if ( empty($this->scoper->listed_ids) )
						$this->scoper->listed_ids = array();
							
					$this->scoper->listed_ids[$src_name] = $listed_ids;	
				}
			} else
				return $results;
		//}
		
		// now determine what restrictions were in place on these results 
		// (currently only for post data source, front end or manage posts/pages)
		//
		// possible todo: support other data sources, WP role type
		if ( 'edit.php' == $GLOBALS['pagenow'] ) {
			if ( scoper_get_otype_option('restrictions_column', 'post') || scoper_get_otype_option('term_roles_column', 'post') || scoper_get_otype_option('object_roles_column', 'post') ) {
				global $scoper_role_usage;
				
				require_once( dirname(__FILE__).'/role_usage_rs.php' );
				$scoper_role_usage = new Role_Usage_RS();
				$scoper_role_usage->determine_role_usage_rs( 'post', $listed_ids );
			}
		}
		
		return $results;
	}

} // end class
?>