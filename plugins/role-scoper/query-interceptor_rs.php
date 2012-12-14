<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * QueryInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * query-interceptor_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */

class QueryInterceptor_RS
{	
	var $scoper;
	var $skip_teaser; 	// this is only used by templates making a direct call to query_posts but wishing to get non-teased results
	var $last_request = array();
	
	function QueryInterceptor_RS() {
		$this->scoper =& $GLOBALS['scoper'];

		$is_administrator = is_content_administrator_rs();

		// ---- ABSTRACT ROLE SCOPER HOOKS - wrap around source-specific hooks based on DataSources config ------
		//
		//	Request / Where Filter:
		//  Support filtering of any query request (WP or plugin-defined) based on scoped roles.
		//  Resulting content may be narrowed or expanded from WP core results. 
		//		(currently require request/where/join string as first hook arg, ignore other args)
		// 
		//	Results Teaser:
		//  Alternately, if a results hook is defined, unqualified records can 
		//  be left in the result set, but with the content stripped and the excerpt
		//  replaced or appended with a teaser message.
		//		(currently require results as array of objects in first hook arg, ignore other args)
		//
		//	suported filter interface:
		//		request hooks: $arg1 = full request query
		//		results hooks: $arg1 = results set
		
		// filter args: $item, $src_name, $object_type, $args (note: to customize other args, filter must be called directly)
		
		add_filter('objects_where_rs', array(&$this, 'flt_objects_where'), 2, 4);
		add_filter('objects_request_rs', array(&$this, 'flt_objects_request'), 2, 4);
		add_filter('objects_results_rs', array(&$this, 'flt_objects_results'), 50, 4);
		add_filter('objects_teaser_rs', array(&$this, 'flt_objects_teaser'), 50, 4);
		
		if ( ! $this->scoper->direct_file_access ) {
			// Append any limiting clauses to WHERE clause for taxonomy query
			// args: ($where, $taxonomy, $object_type = '', $reqd_op = '')  e.g. ($where, 'categories', 'post', 'edit')
			// Note: If any of the optional args are missing or nullstring, an attempt is made
			// to determine them from URI based on Scoped_Taxonomy properties
			add_filter('terms_request_rs', array(&$this, 'flt_terms_request'), 50, 4);
			add_filter('terms_where_rs', array(&$this, 'flt_terms_where'), 50, 3);
		}
		
		// note: If DISABLE_QUERYFILTERS_RS is set, the RS filters are still defined above for selective internal use,
		//		 but in that case are not mapped to the defined data source hooks ('posts_where', etc.) below
		if ( ! defined('DISABLE_QUERYFILTERS_RS') ) {
			//in effect, make WP pass the hook name so multiple hooks can be registered to a single handler 
			$rs_hooks = array();

			foreach ( $this->scoper->data_sources->get_all() as $src_name => $src ) {
				if ( empty($src->query_hooks) )
					continue;
			
				if ( ! $is_administrator ) {
					if ( isset($src->query_hooks->where) ) {
						$rs_hooks[$src->query_hooks->where] = 	(object) array( 'name' => 'objects_where_rs',	'rs_args' => "'$src_name', '', '' ");	
					
					} elseif ( isset($src->query_hooks->request) )
						$rs_hooks[$src->query_hooks->request] = (object) array( 'name' => 'objects_request_rs',	'rs_args' => "'$src_name', '', '' ");
				}
				
				// log results (to identify restricted posts) even for admin.  Also, possibly apply front end teaser
				if ( isset($src->query_hooks->results) )
					$rs_hooks[$src->query_hooks->results] = 	(object) array( 'name' => 'objects_results_rs', 'rs_args' => "'$src_name', '', '' ");
	
			} //foreach data_sources

			if ( ! $is_administrator ) {
				// use late-firing filter so teaser filtering is also applied to sticky posts
				add_filter( 'the_posts', array( &$this, 'flt_the_posts' ), 50, 2 );
				
				// manually hook posts_request to pass on object_type value in the referenced wp_query object
				add_filter( 'posts_request', array( &$this, 'flt_posts_request'), 50, 2 );
			}
				
			// ...but don't include this hook in the filter-wrapping loop below
			if ( isset( $rs_hooks['posts_request'] ) )
				unset( $rs_hooks['posts_request'] );

						
			// call our abstract handlers with a lambda function that passes in original hook name
			foreach ( $rs_hooks as $original_hook => $rs_hook ) {
				if ( ! $original_hook )
					continue;
				
				$arg_str = '$a';
				$comma = ( $rs_hook->rs_args ) ? ',' : '';
				$func = "return apply_filters( '$rs_hook->name', $arg_str $comma $rs_hook->rs_args );";
				add_filter( $original_hook, create_function( $arg_str, $func ), 50, 1 );	

				//d_echo ("adding filter: $original_hook -> $func <br />");
			}
		}
		
		//add_filter( 'posts_request', array( &$this, 'flt_debug_query'), 999 );
	}


	//function flt_debug_query( $query ) {
	//	d_echo( $query . '<br /><br />' );
	//	return $query;
	//}

	
	function flt_posts_request( $request, $_wp_query = false ) {
		if ( is_object( $_wp_query ) && ! empty( $_wp_query->query_vars['post_type'] ) ) {
			$object_types = $_wp_query->query_vars['post_type'];

			if ( 'any' == $object_types )
				$object_types = '';
		} else
			$object_types = '';

		return $this->flt_objects_request( $request, 'post', $object_types );
	}

	// Append any limiting clauses to WHERE clause for taxonomy query
	//$reqd_caps_by_taxonomy[tx_name][op_type] = array of cap names
	function flt_terms_request($request, $taxonomies, $args = array()) {
		//$defaults = array( 'reqd_caps_by_otype' => array(), 'is_term_admin' => false, 'required_operation' => '', 'post_type' => '' );

		// determine term id col (term_id or term_taxonomy_id) for term management queries 
		if ( strpos( $request, 'AS tt' ) )
			$args['term_id_col'] = 'tt.term_taxonomy_id';
		elseif ( strpos( $request, 'AS t' ) )
			$args['term_id_col'] = 't.term_id';
		else {
			global $wpdb;
			if ( strpos( $request, $wpdb->terms ) )
				$args['term_id_col'] = "$wpdb->terms.term_id";
			elseif ( strpos( $request, $wpdb->term_taxonomy ) )
				$args['term_id_col'] = "$wpdb->term_taxonomy.term_taxonomy_id";
		}

		if ( $rs_where = $this->flt_terms_where('', $taxonomies, $args) ) {
			if ( strpos( $request, ' WHERE ' ) )
				$request = str_replace( ' WHERE ', " WHERE 1=1 $rs_where AND ", $request );
			elseif ( $pos_suffix = agp_get_suffix_pos( $request ) )
				$request = substr( $request, 0, $pos_suffix ) . " WHERE 1=1 $rs_where " . substr( $request, $pos_suffix );
			else
				$request .= " WHERE 1=1 $rs_where ";
		}

		//d_echo ("<br /><br />terms_request output:$request<br /><br />");
		
		return $request;
	}
	
	function flt_terms_where($where, $taxonomies, $args = array()) {
		$defaults = array( 'reqd_caps_by_otype' => array(), 'is_term_admin' => false, 'required_operation' => '', 'post_type' => '', 'term_id_col' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract( $args, EXTR_SKIP );
		
		$taxonomies = (array) $taxonomies;
		if ( ! $taxonomies )
			return $where;
	
		$enabled_taxonomies = array_keys( array_intersect( scoper_get_option( 'use_taxonomies' ), array( 1, '1', true ) ) );
		$enabled_taxonomies []= 'link_category';

		if ( ! array_intersect( $taxonomies, $enabled_taxonomies ) )
			return $where;

		if ( $post_type )
			$post_type = (array) $post_type;
	
		// support multiple taxonomies, but only if they all use the same object data source
		$taxonomy_sources = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! $this->scoper->taxonomies->is_member( $taxonomy ) )
				continue;
			
			$src_name = $this->scoper->taxonomies->member_property( $taxonomy, 'object_source' );
			
			if ( is_object($src_name) )		// support legacy code which stored object variable to property (TODO: eliminate this)
				$src_name = $src_name->name;
			
			$taxonomy_sources[$src_name] = true;
		}
		
		if ( count($taxonomy_sources) != 1 )
			return $where;
			
		// if the filter call did not specify required caps...
		if ( ! $reqd_caps_by_otype ) {
			$reqd_caps_by_otype = array();

			// try to determine context from URI (if taxonomy definition includes such clues)
			foreach( $taxonomies as $taxonomy ) {
				$reqd_caps_by_otype = array_merge( $reqd_caps_by_otype, $this->scoper->get_terms_reqd_caps( $taxonomy, $required_operation, $is_term_admin ) );   // NOTE: get_terms_reqd_caps() returns term management caps on edit-tags.php, otherwise post edit caps
			}

			if ( $post_type ) 
				$reqd_caps_by_otype = array_intersect_key( $reqd_caps_by_otype, array_flip( $post_type ) );
			
			// if required operation still unknown, default based on access type
			if ( ! $reqd_caps_by_otype )
				return $where;
		}
		
		// prevent hardway-admin filtering of any queries which may be triggered by this filter
		if ( ! isset( $GLOBALS['scoper_status'] ) )
			$GLOBALS['scoper_status'] = (object) array();
		
		$GLOBALS['scoper_status']->querying_db = true;
		
		// Note that term management capabilities (i.e. "manage_categories") are implemented via Term Roles on the Posts data source, with taxonomy as the object type
		//
		// if this is a term management query, no need to involve objects query filtering
		if ( ( 'post' == $src_name ) && isset( $reqd_caps_by_otype[ $taxonomies[0] ] ) ) {
			$qualifying_roles = $this->scoper->role_defs->qualify_roles( $reqd_caps_by_otype[$taxonomies[0]], 'rs', $taxonomies[0] );	// otherwise qualify_terms() will not filter out other taxonomy manager roles that also use manage_categories cap
			
			if ( ! $term_id_col ) {
				// can't attempt filtering without this info
				$GLOBALS['scoper_status']->querying_db = false;
				return $where;
			}
			
			$return_id_type = ( strpos( $term_id_col, 'term_taxonomy_id' ) ) ? COL_TAXONOMY_ID_RS : COL_ID_RS;
			
			if ( $ids = $this->scoper->qualify_terms( $reqd_caps_by_otype[$taxonomies[0]], $taxonomies[0], $qualifying_roles, compact('return_id_type') ) ) {	// returns term_id since COL_TAXONOMY_ID_RS is not passed as args['return_id_type']
				$where .= " AND $term_id_col IN ('" . implode( "','", $ids ) . "')";
			} else
				$where = ' AND 1=2';
		} else {
			// Call objects_where_role_clauses() with src_name of the taxonomy source.
			// This works as a slight subversion of the normal flt_objects_where query building because we are also forcing taxonomies explicitly and passing the terms_query arg.
			$args['terms_query'] = true;
			$args['use_object_roles'] = false;
			$args['skip_owner_clause'] = true;
			$args['terms_reqd_caps'] = $reqd_caps_by_otype;
			$args['taxonomies'] = $taxonomies;

			if ( is_admin() )
				$args['alternate_reqd_caps'][0] = array( "assign_$taxonomy" );

			$where .= $this->flt_objects_where('', $src_name, '', $args);

			// For Edit Form display, include currently stored terms.  User will still not be able to remove them without proper editing roles for object. (TODO: abstract for other data sources)
			if ( ( 'post.php' == $GLOBALS['pagenow'] ) && empty( $_REQUEST['admin_bar'] ) ) {
				if ( 'post' == $src_name ) {
					if ( $object_id = $this->scoper->data_sources->detect( 'id', $src_name ) ) {
						if ( $stored_terms = wp_get_object_terms( $object_id, $taxonomies[0] ) ) {
							$tt_ids = array();
							foreach( array_keys($stored_terms) as $key ) 
								$tt_ids []= $stored_terms[$key]->term_taxonomy_id;						
								
							$where .= " OR tt.term_taxonomy_id IN ('" . implode( "','", $tt_ids ) . "')";
						}
					}
				}
			}
		}

		$GLOBALS['scoper_status']->querying_db = false;  // re-enable hardway-admin filtering
		
		return $where;
	}
	
	function flt_objects_request($request, $src_name, $object_types = '', $args = array()) {
		if ( $args ) {
			$defaults = array( 'skip_teaser' => false );
			$args = array_diff_key($args, array_flip( array('request', 'src_name', 'object_types' ) ) );
			$args = array_merge( $defaults, (array) $args );
			extract($args);
		}
		
		// if Media Library filtering is disabled, don't filter listing for TinyMCE popup either
		if ( is_admin() && defined( 'SCOPER_ALL_UPLOADS_EDITABLE' ) && strpos( $_SERVER['SCRIPT_NAME'], 'wp-admin/media-upload.php' ) )
			return $request;

		// Filtering in user_has_cap sufficiently controls revision access; a match here should be for internal, pre-validation purposes
		if ( strpos( $request, "post_type = 'revision'") )
			return $request; 

		// no need to apply objects query filtering within NextGEN Gallery / Grand Flash Gallery upload operation (was failing with undefined $current_user)
		if ( is_admin() ) {
			if ( defined( 'SCOPER_ALL_UPLOADS_EDITABLE' ) && ( $GLOBALS['pagenow'] == 'upload.php' ) )
				return $request;

			$nofilter_scripts = array( '/admin/upload.php' );
			if ( $nofilter_scripts = apply_filters( 'noqueryfilter_scripts_rs', $nofilter_scripts ) ) {
				foreach( $nofilter_scripts as $_script_name ) {
					if ( false !== strpos( $_SERVER['SCRIPT_NAME'], $_script_name ) )
						return $request;
				}
			}
		}
			
		// prevent hardway-admin filtering of any queries which may be triggered by this filter
		if ( ! isset($GLOBALS['scoper_status']) )
			$GLOBALS['scoper_status'] = (object) array();
		
		$GLOBALS['scoper_status']->querying_db = true;
			
		if ( empty($skip_teaser) ) {
			$this->last_request[$src_name] = $request;	// Store for potential use by subsequent teaser filter
		}
		
		//$request = agp_force_distinct($request); // in case data source didn't provide a hook for objects_distinct

		if ( ! preg_match('/\s*WHERE\s*1=1/', $request) )
			$request = preg_replace('/\s*WHERE\s*/', ' WHERE 1=1 AND ', $request);

		$pos_where = 0;
		$pos_suffix = 0;
		$where = agp_parse_after_WHERE_11( $request, $pos_where, $pos_suffix );  // any existing where, orderby or group by clauses remain in $where
		if ( ! $pos_where && $pos_suffix ) {
			$request = substr($request, 0, $pos_suffix) . ' WHERE 1=1' .  substr($request, $pos_suffix);
			$pos_where = $pos_suffix;
		}

		if ( 'post' == $src_name ) {
			// If the query uses an alias for the posts table, be sure to use that alias in the WHERE clause also.
			//
			// NOTE: if query refers to non-active blog, this code will prevent a DB syntax error, but will not cause the correct roles / restrictions to be applied.
			// 		Other plugins need to use switch_to_blog() rather than just executing a query on a non-main blog.
			$matches = array();
			if ( $return = preg_match( '/SELECT .* FROM [^ ]+posts AS ([^ ]) .*/', $request, $matches ) )
				$args['source_alias'] = $matches[2];
			elseif ( $return = preg_match( '/SELECT .* FROM ([^ ]+)posts .*/', $request, $matches ) )
				$args['source_alias'] = $matches[1] . 'posts';
		}

		// TODO: abstract this
		if ( strpos( $request, "post_type = 'attachment'" ) ) {
			global $wpdb;
			
			if ( ! is_admin() && ! empty($_REQUEST['attachment_id']) && ! defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') ) {
				if ( $_att = get_post($_REQUEST['attachment_id']) ) {
					if ( 0 === $_att->post_parent )
						return $request;
				}
			}
			
			// filter attachments by inserting a scoped subquery based on user roles on the post/page attachment is tied to
			$rs_where = $this->flt_objects_where( '', $src_name, '', $args );
			$subqry = "SELECT ID FROM $wpdb->posts WHERE 1=1 $rs_where";

			if ( is_admin() ) {
				// The listed objects are attachments, so query filter is based on objects they inherit from
				$admin_others_attached = scoper_get_option( 'admin_others_attached_files' );
				$admin_others_unattached = scoper_get_option( 'admin_others_unattached_files' );

				if ( ( ! $admin_others_attached ) || ! $admin_others_unattached )
					$can_edit_others_blogwide = $this->scoper->user_can_edit_blogwide( 'post', '', array( 'require_others_cap' => true, 'status' => 'publish' ) );

				global $current_user;
				
				// optionally hide other users' unattached uploads, but not from blog-wide Editors
				if ( $admin_others_unattached || $can_edit_others_blogwide )
					$author_clause = '';
				else
					$author_clause = "AND $wpdb->posts.post_author = '{$current_user->ID}'";
				
				if ( is_admin() && ( ! defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') || ! SCOPER_BLOCK_UNATTACHED_UPLOADS ) )
					$unattached_clause = "( $wpdb->posts.post_parent = 0 $author_clause ) OR";
				else
					$unattached_clause = '';

				$attached_clause = ( $admin_others_attached || $can_edit_others_blogwide ) ? '' : "AND $wpdb->posts.post_author = '{$current_user->ID}'";
				
				$request = str_replace( "$wpdb->posts.post_type = 'attachment'", "( $wpdb->posts.post_type = 'attachment' AND ( $unattached_clause ( $wpdb->posts.post_parent IN ($subqry) $attached_clause ) ) )", $request );
			} else
				$request = str_replace( "$wpdb->posts.post_type = 'attachment'", "( $wpdb->posts.post_type = 'attachment' AND ( $wpdb->posts.post_parent IN ($subqry) ) )", $request );

		} else {
			// Generate a query filter based on roles for the listed objects
			$rs_where = $this->flt_objects_where($where, $src_name, $object_types, $args);

			if ( $pos_where === false )
				$request = $request . ' WHERE 1=1 ' . $where;
			else
				$request = substr($request, 0, $pos_where) . ' WHERE 1=1 ' . $rs_where; // any pre-exising join clauses remain in $request
		}

		// re-enable hardway-admin filtering
		$GLOBALS['scoper_status']->querying_db = false;
		
		//d_echo( "<br />filtered: $request<br /><br />" );
		return $request;
	}
	
	// called by flt_objects_where, flt_objects_results
	function _get_object_types( $src, $object_types = '' ) {
		if ( ! $object_types ) {
			if ( ! is_object($src) )
				if ( ! $src = $this->scoper->data_sources->get($src) )
					return array();
	
			return array_keys($src->object_types);  // include all defined otypes in the query if none were specified
		} else
			return (array) $object_types;	// make sure the passed-in value is an array
	}
	
	// called by flt_objects_where, flt_objects_results
	function _get_teaser_object_types($src_name, $object_types, $args = array()) {
		$args = (array) $args;

		if ( ! empty($args['skip_teaser']) || is_admin() || is_content_administrator_rs() || is_attachment_rs() || defined('XMLRPC_REQUEST') || ! empty($this->skip_teaser) )
			return array();

		if ( is_feed() && defined( 'SCOPER_NO_FEED_TEASER' ) )
			return array();

		if ( ( ! empty( $args['required_operation'] ) && ( 'read' != $args['required_operation'] ) ) )
			return array();
			
		if ( empty($object_types) )
			$object_types = $this->_get_object_types($src_name);
			
		$tease_otypes = array();
		
		if ( scoper_get_otype_option('do_teaser', $src_name) ) {
			global $current_user;

			foreach ( $object_types as $object_type )
				if ( scoper_get_otype_option('use_teaser', $src_name, $object_type) ) {
					$teased_users = scoper_get_otype_option( 'teaser_logged_only', $src_name, $object_type );

					if ( empty( $teased_users )
					|| ( ( 'anon' == $teased_users ) && empty($current_user->ID) )
					|| ( ( 'anon' != $teased_users ) && ! empty($current_user->ID) ) )
						$tease_otypes []= $object_type;
				}
		}

		return $tease_otypes;
	}
	
	// NOTE: Setting use_object_roles or use_term_roles to a boolean forces enable/disable for all types / taxonomies.  Otherwise stored options will be retrieved for each object type / taxonomy.
	function flt_objects_where($where, $src_name, $object_types = '', $args = array() ) {
		$defaults = array( 'user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 
							'taxonomies' => array(), 'request' => '', 'terms_query' => 0, 
							'force_reqd_caps' => '', 'alternate_reqd_caps' => '',	'source_alias' => '',
							'required_operation' => '', 'terms_reqd_caps' => '', 'skip_teaser' => false
							 );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		// filtering in user_has_cap sufficiently controls revision access; a match here should be for internal, pre-validation purposes
		if ( strpos( $where, "post_type = 'revision'") )
			return $where; 

		$where_prepend = '';

		//rs_errlog ("object_where input: $where");
		//rs_errlog ('');
		
		//d_echo ("<br /><strong>object_where input:</strong> $where<br />");
		//echo "<br />$where<br />";
		
		if ( ! is_object($user) ) {
			$user = $GLOBALS['current_rs_user'];
			$args['user'] = $user;
		}

		if ( ! $src = $this->scoper->data_sources->get($src_name) )
			return $where;	// the specified data source is not know to Role Scoper
			
		$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;

		// verify table name and id col definition (the actual existance checked at time of admin entry)
		if ( ! ($src->table && $src->cols->id) ) {
			rs_notice( sprintf( 'Role Scoper Configuration Error: table_basename or col_id are undefined for the %s data source.', $src_name) );
			return $where;
		}
		
		// need to allow ambiguous object type for special cap requirements like comment filtering
		$object_types = $this->_get_object_types($src, $object_types);
		$tease_otypes = array_intersect( $object_types, $this->_get_teaser_object_types($src_name, $object_types, $args) );
		
		if ( ! empty($src->no_object_roles) )
			$use_object_roles = false;
			
		if ( $terms_query && $terms_reqd_caps ) {
			foreach ( array_keys($terms_reqd_caps) as $_object_type )
				$otype_status_reqd_caps[$_object_type][''] = $terms_reqd_caps[$_object_type];  // terms request does not support multiple statuses

		} else {
			if ( $force_reqd_caps && is_array($force_reqd_caps) ) {
				$otype_status_reqd_caps = $force_reqd_caps;
			} else {
				global $wpdb;
				
				if ( ! $required_operation )
					$required_operation = ( 'front' == CURRENT_ACCESS_NAME_RS ) ? OP_READ_RS : OP_EDIT_RS;

				$preview_future = strpos( $where, "$wpdb->posts.post_name =" ) || strpos( $where, "$wpdb->posts.ID =" );

				if ( ! $otype_status_reqd_caps = cr_get_reqd_caps( $src_name, $required_operation, -1, -1, false, $preview_future ) )
					return $where;
			}
			
			$otype_status_reqd_caps = array_intersect_key($otype_status_reqd_caps, array_flip($object_types) );
		}
		
		// Since Role Scoper can restrict or expand access regardless of post_status, query must be modified such that
		//  * the default owner inclusion clause "OR post_author = [user_id] AND post_status = 'private'" is removed
		//  * all statuses are listed apart from owner inclusion clause (and each of these status clauses is subsequently replaced with a scoped equivalent which imposes any necessary access limits)
		//  * a new scoped owner clause is constructed where appropriate (see $where[$cap_name]['owner'] in function objects_where_role_clauses()
		//
		if ( $src->cols->owner && $user->ID ) {
			// force standard query padding
			$where = preg_replace("/{$src->cols->owner}\s*=\s*/", "{$src->cols->owner} = ", $where);
			
			$where = str_replace( " {$src->cols->owner} =", " $src_table.{$src->cols->owner} =", $where);
			$where = str_replace( " {$src->cols->owner} IN", " $src_table.{$src->cols->owner} IN", $where);
		}
		
		if ( ! empty($src->query_replacements) ) {
			foreach ( $src->query_replacements as $find => $replace ) {
				// for posts_request, remove the owner inclusion clause "OR post_author = [user_id] AND post_status = 'private'" because we'll account for each status based on properties of required caps
				$find_ = str_replace('[user_id]', $user->ID, $find);
				if ( false !== strpos($find_, '[') ||  false !== strpos($find_, ']') ) {
					rs_notice( sprintf( 'Role Scoper Config Error: invalid query clause search criteria for %1$s (%2$s).<br /><br />Valid placeholders are:<br />', $src_name, $find) . print_r(array_keys($map)) ); 
					return ' AND 1=2 ';
				}
					
				$replace_ = str_replace('[user_id]', $user->ID, $replace);

				if ( false !== strpos($replace_, '[') ||  false !== strpos($replace_, ']') ) {
					rs_notice( sprintf( 'Role Scoper Config Error: invalid query clause replacement criteria for %1$s (%2$s).<br /><br />Valid placeholders are:<br />', $src_name, $replace) . print_r(array_keys($map)) ); 
					return ' AND 1=2 ';
				}
				
				$where = str_replace($find_, $replace_, $where);
			}
		}
		
		$force_single_type = false;
		
		$col_type = ( ! empty( $src->cols->type ) ) ? $src->cols->type : '';
		if ( $col_type ) {
			// If the passed request contains a single object type criteria, maintain that status exclusively (otherwise include type-specific conditions for each available type)
			$matches = array();
			$num_matches = preg_match_all( "/$col_type\s*=\s*'([^']+)'/", $where, $matches );
			if ( 1 == $num_matches ) {
				$force_single_type = true;
				$object_types = array( $matches[1][0] );

				if ( $matched_reqd_caps = array_intersect_key( $otype_status_reqd_caps, array_flip($object_types) ) ) // sanity check prevents running with an empty reqd_caps array if something goes wrong with otype detection
					$otype_status_reqd_caps = $matched_reqd_caps;
			}
		}

		if ( ( 'post' == $src_name ) && ! array_intersect( $object_types, array_keys( array_intersect( scoper_get_option( 'use_post_types' ), array( true ) ) ) ) )
			return $where;
		elseif ( empty( $otype_status_reqd_caps) )
			return ' AND 1=2 ';
		
		$basic_status_clause = array();
		$force_single_status = false;
		$status_clause_pos = 0;
		
		$col_status = ( ! empty( $src->cols->status ) ) ? $src->cols->status : '';
		if ( $col_status ) {
			// force standard query padding
			$where = preg_replace("/$col_status\s*=\s*'/", "$col_status = '", $where);
			
			$where = str_replace(" $col_status =", " {$src_table}.$col_status =", $where);
			$where = str_replace(" $col_status IN", " {$src_table}.$col_status IN", $where);

			foreach ( array_keys( $otype_status_reqd_caps ) as $listing_otype )
				foreach( array_keys( $otype_status_reqd_caps[$listing_otype] ) as $status )
					$basic_status_clause[$status] = "{$src_table}.$col_status = '$status'";
			
			// If the passed request contains a single status criteria, maintain that status exclusively (otherwise include status-specific conditions for each available status)
			// (But not if user is anon and hidden content teaser is enabled.  In that case, we need to replace the default "status=publish" clause)
			$matches = array();
			if ( $num_matches = preg_match_all( "/{$src_table}.$col_status\s*=\s*'([^']+)'/", $where, $matches ) ) {
				$where = str_replace( $matches[0][0], "( {$matches[0][0]} )", $where );
				$status_clause_pos = strpos( $where, $matches[0][0] ); // note the match position for use downstream
			}
			
			if ( 1 == $num_matches ) {
				$use_status = $matches[1][0];
				
				// Eliminate a primary plugin incompatibility by skipping this preservation of existing single status requirements if we're on the front end and the requirement is 'publish'.  
				// (i.e. include private posts that this user has access to via RS role assignment).  
				if ( ! $this->scoper->is_front() || ( 'publish' != $use_status ) || ( empty( $args['user']->ID ) && empty($tease_otypes) ) || defined('SCOPER_RETAIN_PUBLISH_FILTER') ) { 
					$force_single_status = true;

					foreach ( array_keys($otype_status_reqd_caps) as $_object_type )
						$otype_status_reqd_caps[$_object_type] = array_intersect_key( $otype_status_reqd_caps[$_object_type], array( $use_status => true ) );
				}
			}
		} else {
			// this source doesn't define statuses
			$basic_status_clause = array ( '' => '');
		}

		if ( empty($skip_teaser) && ! array_diff($object_types, $tease_otypes) ) {
			if ( $status_clause_pos && $force_single_type ) { // All object types potentially returned by this query will have a teaser filter applied to results, so we don't need to filter the query
			
				// override our sanity safeguard against exposing private posts to anonymous readers
				if ( empty($user->ID) ) { 
		
					// Since we're dropping out of this function early in advance of teaser filtering, 
					// must take this opportunity to add private status to the query (otherwise WP excludes private for anon user)
					// (But don't do this if teaser is configured to hide private content)
					$check_otype = ( count($tease_otypes) && in_array('post', $tease_otypes) ) ? 'post' : $tease_otypes[0];
					
					$post_type_obj = get_post_type_object($check_otype);
					
					if ( ! scoper_get_otype_option('teaser_hide_private', $src_name, $check_otype) && ( ! $post_type_obj->hierarchical || scoper_get_otype_option('private_items_listable', 'post', 'page') ) ) {
						if ( $col_status && isset( $otype_status_reqd_caps[$check_otype] ) ) {
							$status_or = "{$src_table}.$col_status = '" . implode("' OR {$src_table}.$col_status = '", array_keys($otype_status_reqd_caps[$check_otype]) ) . "'";
							$where = str_replace( $basic_status_clause['publish'], "( $status_or )", $where);
						} else {
							$where = str_replace( $basic_status_clause['publish'], "1=1", $where);
						}
					}
				}
			}

			return $where;
		}
		
		$is_administrator = is_content_administrator_rs();	// make sure administrators never have content limited
		
		$status_or = '';
		$status_where = array();
		
		foreach ($otype_status_reqd_caps as $object_type => $status_reqd_caps) {
			if ( ! is_array($status_reqd_caps) ) {
				rs_notice( sprintf( 'Role Scoper Configuration Error: reqd_caps for the %s data source must be array[operation][object_type][status] where operation is "read", "edit" or "admin".', $src_name) );
				return $where;
			}
			
			// don't bother generating these parameters if we're just going to pass the object type through for teaser filtering
			if ( ! in_array($object_type, $tease_otypes) ) {
				if ( true === $use_term_roles ) {  // if boolean true was passed in, force usage of all term roles
					if ( 'post' == $src_name ) {
						//$otype_use_term_roles = array_fill_keys( get_taxonomies( array( 'public' => true, 'object_type' => $object_type ) ), 1 );
						$otype_use_term_roles = array();
						foreach( get_taxonomies( array( 'public' => true ), 'object' ) as $taxonomy => $taxonomy_obj )
							if ( in_array( $object_type, $taxonomy_obj->object_type ) )
								$otype_use_term_roles[$taxonomy] = 1;
					} else
						$otype_use_term_roles = ( ! empty( $src->uses_taxonomies ) ) ? array_fill_keys( $src->uses_taxonomies, true ) : array();
				} else {
					$check_object_type = ( 'link_category' == $object_type ) ? 'link' : $object_type;	
					$otype_use_term_roles = ( -1 == $use_term_roles ) ? scoper_get_otype_option('use_term_roles', $src_name, $check_object_type) : false;
				}

				if ( ( ! $otype_use_term_roles ) && $terms_query )
					continue;	

				// if a boolean was passed in, override the stored option
				$otype_use_object_roles = ( -1 == $use_object_roles ) ? scoper_get_otype_option('use_object_roles', $src_name, $object_type) : $use_object_roles;
			} else {
				$otype_use_term_roles = false;
				$otype_use_object_roles = false;
			}
			
			//now step through all statuses and corresponding cap requirements for this otype and access type
			// (will replace "col_status = status_name" with "col_status = status_name AND ( [scoper requirements] )
			foreach ($status_reqd_caps as $status_name => $reqd_caps) {
				if ( 'trash' == $status_name )	// in wp-admin, we need to include trash posts for the count query, but not for the listing query unless trash status is requested
					if ( ( empty($this->last_request[$src_name]) || ! strpos($this->last_request[$src_name], 'COUNT') ) && ( empty( $_GET['post_status'] ) || ( 'trash' != $_GET['post_status'] ) ) )
						continue;

				if ( $is_administrator )
					$status_where[$status_name][$object_type] = '1=1';
				elseif ( empty($skip_teaser) && in_array($object_type, $tease_otypes) )
					if ( $terms_query && ! $otype_use_object_roles )
						$status_where[$status_name][$object_type] = '1=1';
					else
						$status_where[$status_name][$object_type] = "{$src_table}.{$src->cols->type} = '$object_type'"; // this object type will be teaser-filtered
				else {
					// filter defs for otypes which don't define a status will still have a single status element with value ''
					$args = array_merge( $args, array( 'object_type' => $object_type, 'otype_use_term_roles' => $otype_use_term_roles, 'otype_use_object_roles' => $otype_use_object_roles ) );
					
					$clause = $this->objects_where_role_clauses($src_name, $reqd_caps, $args);

					if ( empty($clause) || ( '1=2' == $clause ) )	// this means no qualifying roles are available
						$status_where[$status_name][$object_type] = '1=2';
						
					// array key order status/object is reversed intentionally for subsequent processing
					elseif ( (count($otype_status_reqd_caps) > 1) && ( ! $terms_query || $otype_use_object_roles) ) // more than 1 object type
						$status_where[$status_name][$object_type] = "( {$src_table}.{$src->cols->type} = '$object_type' AND ( $clause ) )";
					else
						$status_where[$status_name][$object_type] = $clause;
				}
			}
		}
		
		// all otype clauses concat: object_type1 clause [OR] [object_type2 clause] [OR] ...
		foreach ( array_keys($status_where) as $status_name ) {
			if ( isset($preserve_or_clause[$status_name]) )
				$status_where[$status_name][] = $preserve_or_clause[$status_name];

			if ( $tease_otypes )
				$check_otype = ( count($tease_otypes) && in_array('post', $tease_otypes) ) ? 'post' : $tease_otypes[0];

			// extra line of defense: even if upstream logic goes wrong, never disclose a private item to anon user (but if the where clause was passed in with explicit status=private, must include our condition)
			if ( ('private' == $status_name) && ! $force_single_status && empty($GLOBALS['current_user']->ID) && ! defined( 'SCOPER_ANON_METAGROUP' ) && ( ! $tease_otypes || scoper_get_otype_option('teaser_hide_private', $src_name, $check_otype) ) )
				unset( $status_where[$status_name] );
			else
				$status_where[$status_name] = agp_implode(' ) OR ( ', $status_where[$status_name], ' ( ', ' ) ');
		}
		
		// combine identical status clauses
		$duplicate_clause = array();
		$replace_clause = array();
		if (  $col_status && count($status_where) > 1 ) { // more than one status clause
			foreach ( $status_where as $status_name => $status_clause) {
				if ( isset($duplicate_clause[$status_name]) )
					continue;

				reset($status_where);
				if ( $other_status_name = array_search($status_clause, $status_where) ) {
					if ( $other_status_name == $status_name ) $other_status_name = array_search($status_clause, $status_where);
					if ( $other_status_name && ( $other_status_name != $status_name ) ) {
						$duplicate_clause[$other_status_name][$status_name] = true;
						$replace_clause[$status_name] = true;
					}
				}
			}
		}
		
		$status_where = array_diff_key($status_where, $replace_clause);
		
		foreach ( $status_where as $status_name => $this_status_where) {
		
			if ( $status_clause_pos && $force_single_status ) {
				//We are maintaining the single status which was specified in original query

				if ( ! $this_status_where || ( $this_status_where == '1=2' ) )
					$where_prepend = '1=2';

				elseif ( $this_status_where == '1=1' )
					$where_prepend = '';

				else {
					//insert at original status clause position
					$where_prepend = '';
					$where = substr($where, 0, $status_clause_pos) . "( $this_status_where ) AND " . substr($where, $status_clause_pos);
				}

				break;
			}

			// We may be replacing or inserting status clauses
			
			if ( ! empty($duplicate_clause[$status_name]) ) {
				// We generated duplicate clauses for some statuses
				foreach ( array_keys($duplicate_clause[$status_name]) as $other_status_name ) {
					$where = str_replace($basic_status_clause[$other_status_name], '1=2', $where);
				}
					
				$duplicate_clause[$status_name] = array_merge($duplicate_clause[$status_name], array($status_name=>1) );

				if ( $col_status ) {
					$name_in = "'" . implode("', '", array_keys($duplicate_clause[$status_name])) . "'";
					$status_prefix = "{$src_table}.$col_status IN ($name_in)";
				} else {
					$status_prefix = "1=1";
				}
			} elseif ( $col_status && $status_name ) {
				$status_prefix = $basic_status_clause[$status_name];	
			} else
				$status_prefix = '';

			if ( $this_status_where && ( $this_status_where != '1=2' || count($status_where) > 1 ) ) {  //todo: confirm we can OR the 1=2 even if only one status clause
				if ( '1=1' == $this_status_where )
					$status_clause = ( $status_prefix ) ? "$status_prefix " : ''; 
				else {
					$status_clause = ( $col_status && $status_prefix ) ? "$status_prefix AND " : ''; 
					$status_clause .= "( $this_status_where )";											// TODO: reduce number of parentheses
					$status_clause = " ( $status_clause )";
				}
			} else
				$status_clause = '1=2';

			if ( $status_clause ) {	
				if ( $col_status && $status_name && strpos($where, $basic_status_clause[$status_name]) ) {
					// Replace existing status clause with our scoped equivalent
					$where = str_replace($basic_status_clause[$status_name], "$status_clause", $where);

					// account for padding and parentheses that may have been inserted ahead of first status clause
					$matches = array();
					if ( $num_matches = preg_match_all( "/{$src_table}.$col_status\s*=\s*'([^']+)'/", $where, $matches ) )
						$status_clause_pos = strpos( $where, $matches[0][0] ); // note the match position for use downstream

				} elseif ( $status_clause_pos && ( $status_clause != '1=2' ) ) {
					// This status was not in the original query, but we now insert it with scoping clause at the position of another existing status clause
					$where = substr($where, 0, $status_clause_pos) . "$status_clause OR " . substr($where, $status_clause_pos);
				
				} else {
					// Default query makes no mention of status (perhaps because this data source doesn't define statuses), 
					// so prepend this clause to front of where clause
					$where_prepend .= "$status_or $status_clause";
					$status_or = ' OR';
				}
			}
		}

		// Existance of this variable means no status clause exists in default WHERE.  AND away we go.
		// Prepend so we don't disturb any orderby/groupby/limit clauses which are along for the ride
		if ( $where_prepend ) {
			if ( $where )
				$where = " AND ( $where_prepend ) $where";
			else
				$where = " AND ( $where_prepend )";
		}
	
		//d_echo ("<br /><br /><strong>objects_where output:</strong> $where<br /><br />");
		//echo "<br />$where<br />";
		
		//rs_errlog ("object_where output: $where");
		//rs_errlog ('');
		//rs_errlog ('');

		return $where;
	}

	
	// core Role Scoper where clause concatenation called by listing filter (flt_objects_request) and single access filter (flt_user_has_cap)
	// $reqd_caps[cap_name] = min scope
	//
	// required args: user, object_type, otype_use_object_roles, otype_use_term_roles
	//
	function objects_where_role_clauses($src_name, $reqd_caps, $args = array() ) {	
		$defaults = array( 'taxonomies' => array(), 'terms_query' => false, 'alternate_reqd_caps' => '', 
						'custom_user_blogcaps' => '', 'skip_owner_clause' => false, 'require_full_object_role' => false );
									
		// Required Args
		// NOTE: use_object_roles is a boolean for the single object_type in question, but otype_use_object_roles is array[taxonomy] = true or false
		$required = array_fill_keys( array( 'user', 'object_type', 'otype_use_term_roles', 'otype_use_object_roles' ), true );
		
		if ( $missing = array_diff_key( $required, $args ) ) {
			rs_notice ( sprintf( 'Role Scoper Runtime Error (%1$s) - Missing argument(s): %2$s', 'objects_where_scope_clauses', implode( ", ", array_keys($missing) ) ) );  
			return ' 1=2 ';	
		}

		$defaults = array_merge( $defaults, $required );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		if ( '' === $custom_user_blogcaps )
			$custom_user_blogcaps = SCOPER_CUSTOM_USER_BLOGCAPS;
		
		$reqd_caps = (array) $reqd_caps;
		$reqd_caps = $this->scoper->role_defs->role_handles_to_caps($reqd_caps);

		// accomodate editing of published posts/pages to revision
		if ( defined( 'RVY_VERSION' ) && rvy_get_option('pending_revisions') ) {
			if ( empty( $GLOBALS['revisionary']->skip_revision_allowance ) ) {
				$revision_uris = apply_filters( 'scoper_revision_uris', array( 'edit.php', 'upload.php', 'widgets.php', 'admin-ajax.php', 'rvy-revisions' ) );

				if ( is_admin() || ! empty( $_GET['preview'] ) )
					$revision_uris []= 'index.php';	

				$plugin_page = is_admin() ? $GLOBALS['plugin_page_cr'] : '';

				if ( is_preview() || in_array( $GLOBALS['pagenow'], $revision_uris ) || in_array( $plugin_page, $revision_uris ) ) {
					$strip_capreqs = array();

					foreach( (array) $object_type as $_object_type ) {
						if ( $type_obj = get_post_type_object( $_object_type ) ) {
							$strip_capreqs = array_merge( $strip_capreqs, array( $type_obj->cap->edit_published_posts, $type_obj->cap->edit_private_posts ) );
							
							if ( array_intersect( $reqd_caps, $strip_capreqs ) )
								$reqd_caps []= $type_obj->cap->edit_posts;
						}
					}

					$reqd_caps = array_unique( array_diff($reqd_caps, $strip_capreqs) );
				}
				
				$do_revision_clause = true;
			}	
		}

		if ( ! is_object($user) ) {		// TODO: can we skip this now that user is a required arg?
			$user = $GLOBALS['current_rs_user'];
		}
		
		if ( ! $src = $this->scoper->data_sources->get($src_name) ) {
			rs_notice ( sprintf( 'Role Scoper Config Error (%1$s): Data source (%2$s) is not defined.', 'objects_where_role_clauses', $src_name) );  
			return ' 1=2 ';
		}
		
		$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;
		// special case to include pending / scheduled revisions by object role
		if ( ! isset( $args['objrole_revisions_clause'] ) ) {
			$args['objrole_revisions_clause'] = ( 'edit.php' == $GLOBALS['pagenow'] );
		}
		
		// These arguments are simply passed on to objects_where_scope_clauses()
		if ( 'group' ==  $src_name )
			$args['otype_use_object_roles'] = true;
		elseif ( ! empty($src->no_object_roles) )
			$args['otype_use_object_roles'] = false;

		if ( $args['otype_use_object_roles'] ) {
			// Return all object_ids that require any role to be object-assigned 
			// We will use an ID NOT IN clause so these are not satisfied by blog/term role assignment
			$args['objscope_objects'] = $this->scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name);
		}
		
		$where = array();
		
		foreach ( $reqd_caps as $cap_name ) {
			// If supporting custom user blogcaps, a separate role clause for each cap
			// Otherwise (default) all reqd_caps from one role assignment (whatever scope it may be)
			if ( $custom_user_blogcaps ) {
				$reqd_caps_arg = array($cap_name);
			} else {
				$reqd_caps_arg = $reqd_caps;
				$cap_name = '';
			}

			$qualifying_roles = $this->scoper->role_defs->qualify_roles($reqd_caps_arg, '', $object_type );
			
			/*
			rs_errlog( '' );
			rs_errlog( "reqd_caps arg: " . serialize($reqd_caps_arg) );
			rs_errlog( "qualifying roles for $object_type: " . serialize($qualifying_roles) );
			rs_errlog( '' );
			*/

			if ( $alternate_reqd_caps && is_array( $alternate_reqd_caps ) ) { // $alternate_reqd_caps[setnum] = array of cap_names
				foreach ( $alternate_reqd_caps as $alternate_capset ) {
					foreach ( $alternate_capset as $alternate_reqd_caps ) {
						if ( $alternate_roles = $this->scoper->role_defs->qualify_roles($alternate_reqd_caps) )
							$qualifying_roles = array_merge($qualifying_roles, $alternate_roles);				
					}	
				}
			}
			
			// this is needed mainly for the chicken-egg situation of uploading a file into a post before a category is stored, when editing rights are based on category
			//if ( $args['otype_use_object_roles'] )
				$args['ignore_restrictions'] = ( 1 == count($reqd_caps_arg) ) && $this->scoper->cap_defs->member_property( reset($reqd_caps_arg), 'ignore_restrictions' );
			
			if ( $owner_reqd_caps = $this->scoper->cap_defs->get_base_caps($reqd_caps_arg) ) {
				$owner_roles = ( $require_full_object_role ) ? $qualifying_roles : $this->scoper->role_defs->qualify_roles($owner_reqd_caps, '', $object_type);
				
				if ( ! empty($alternate_roles ) )
					$owner_roles = array_merge( $owner_roles, $alternate_roles );				
			} else
				$owner_roles = array();
			
			// have to pass qualifying_object_roles in for 'user' call because qualifying_roles may not include a qualifying object role (i.e. Page Contributor object role assignment)
			if ( $owner_roles && ( empty( $GLOBALS['revisionary'] ) || empty( $GLOBALS['revisionary']->skip_revision_allowance ) ) )
				$qualifying_object_roles = $this->scoper->confirm_object_scope( $owner_roles );
			else
				$qualifying_object_roles = $this->scoper->confirm_object_scope( $qualifying_roles ); // get_base_caps() strips out edit_private_* cap requirement for post owner, in compliance with WP metacap mapping.  But for Revisionary, that causes Revisors to have full editing caps if a page is privately published (but not if it's publicly published).

			if ( $qualifying_roles || ! empty($qualifying_object_roles) ) {
				//d_echo( "regular objects_where_scope_clauses for " . serialize( $reqd_caps ) );
				$args = array_merge( $args, compact( 'qualifying_roles', 'qualifying_object_roles' ) );
				$where[$cap_name]['user'] = $this->objects_where_scope_clauses($src_name, $reqd_caps_arg, $args );
			}

			if ( ! empty($src->cols->owner) && ! $skip_owner_clause && $user->ID ) {
				if ( ! $require_full_object_role ) {
					// if owner qualifies for the operation by any different roles than other users, add separate owner clause
					$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;

					if ( ! $owner_reqd_caps ) {
						// all reqd_caps are granted to owner automatically
						$where[$cap_name]['owner'] = "$src_table.{$src->cols->owner} = '$user->ID'";
					} elseif ( $owner_reqd_caps != $reqd_caps_arg ) {
						if ( $owner_roles ) {
							//d_echo( "owner objects_where_scope_clauses: " );
							$args = array_merge($args, array( 'qualifying_roles' => $owner_roles ) );
							$scope_temp = $this->objects_where_scope_clauses($src_name, $owner_reqd_caps, $args );

							if ( ( $scope_temp != $where[$cap_name]['user'] ) && ! is_null($scope_temp) ) { // TODO: why is null ever returned?
								$parent_clause = '';

								// enable authors to view / edit / approve revisions to their published posts
								if ( ! empty( $do_revision_clause ) && ! defined( 'HIDE_REVISIONS_FROM_AUTHOR' ) ) {
									static $owner_ids = array();

									if ( ! isset( $owner_ids[$user->ID][$object_type] ) ) {	// also keying by user ID in case this filter is invoked for a non-current user
										$type_clause = ( ! empty($src->cols->type) ) ? "{$src->cols->type} = '$object_type' AND" : '';
					
										$owner_ids[$user->ID][$object_type] = scoper_get_col( "SELECT {$src->cols->id} FROM $src->table WHERE $type_clause {$src->cols->owner} = '$user->ID'" );
									}
										
									if ( ! empty($src->cols->type) && ! empty($src->cols->parent) && $owner_ids[$user->ID][$object_type] )
										$parent_clause = "OR $src_table.{$src->cols->type} = 'revision' AND $src_table.{$src->cols->parent} IN ('" . implode( "','", $owner_ids[$user->ID][$object_type] ) . "')"; 
								}
								
								$where[$cap_name]['owner'] = '( ' . $scope_temp . " ) AND ( $src_table.{$src->cols->owner} = '$user->ID' $parent_clause )";
							}
						}
					}
				}
			}
			
			// all role clauses concat: user clauses [OR] [owner clauses]
			if ( ! empty($where[$cap_name]) )
				$where[$cap_name] = agp_implode(' ) OR ( ', $where[$cap_name], ' ( ', ' ) ');
			
			// if not supporting custom caps, we actually passed all reqd_caps in first iteration
			if ( ! $custom_user_blogcaps )
				break;
		}
		
		// all reqd caps concat: cap1 clauses [AND] [cap2 clauses] [AND] ...
		if ( ! empty($where) )
			$where = agp_implode(' ) AND ( ', $where, ' ( ', ' ) ');
		else
			return '1=2';
					
		return $where;
	}
	
	
	function objects_where_scope_clauses($src_name, $reqd_caps, $args ) {

		// Optional Args (will be defaulted to meaningful values)
		// Note: ignore_restrictions affects Scoper::qualify_terms() output
		$defaults = array( 'taxonomies' => '', 'use_blog_roles' => true, 'terms_query' => false,  'qualifying_object_roles' => false,
						   'skip_objscope_check' => false, 'require_full_object_role' => false, 'objrole_revisions_clause' => false, 'ignore_restrictions' => false );
							   
		// Required Args
		// NOTE: use_object_roles is a boolean for the single object_type in question, but use_term_roles is array[taxonomy] = true or false
		$required = array_fill_keys( array( 'user', 'object_type', 'qualifying_roles', 'otype_use_term_roles', 'otype_use_object_roles' ), true );
		
		if ( $missing = array_diff_key( $required, $args ) ) {
			rs_notice ( sprintf( 'Role Scoper Runtime Error (%1$s) - Missing argument(s): %2$s', 'objects_where_scope_clauses', implode( ", ", array_keys($missing) ) ) );  
			return ' 1=2 ';	
		}

		$defaults = array_merge( $defaults, $required );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		if ( ! $src = $this->scoper->data_sources->get($src_name) ) {
			rs_notice ( sprintf( 'Role Scoper Config Error (%1$s): Data source (%2$s) is not defined.', 'objects_where_scope_clauses', $src_name ) );  
			return ' 1=2 ';
		}
		
		$src_table = ( ! empty($source_alias) ) ? $source_alias : $src->table;
		
		if ( 'group' == $src_name )
			$otype_use_object_roles = true;
		elseif ( ! empty($src->no_object_roles) )
			$otype_use_object_roles = false;
		
		// primary qualifying_roles array should contain only RS roles
		$qualifying_roles = $this->scoper->role_defs->filter( $qualifying_roles, array( 'role_type' => 'rs' ), 'names_as_key' );

		if ( $otype_use_object_roles ) {
			// For object assignment, replace any "others" reqd_caps array. 
			// Also exclude any roles which have never been assigned to any object
			if ( ! is_array( $qualifying_object_roles ) )
				$qualifying_object_roles = $this->scoper->confirm_object_scope( $qualifying_roles, $user );	

			if ( $skip_objscope_check )
				$objscope_objects = array();
			else
				$objscope_objects = $this->scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name);  // this is buffered so redundant calling is not a concern
		}
		//---------------------------------------------------------------------------------

		//dump($qualifying_object_roles);
		//dump($objscope_objects);
		
		if ( $otype_use_object_roles )
			$user_qualifies_for_obj_roles = ( $user->ID || defined( 'SCOPER_ANON_METAGROUP' ) );

		$where = array();

		if ( $terms_query ) {
			$_taxonomies = $taxonomies;

		} elseif ( $otype_use_term_roles && is_array($otype_use_term_roles) ) {
			$_taxonomies = array_keys( array_intersect( $otype_use_term_roles, array( 1, '1', true ) ) );
			
			// taxonomies arg is for limiting; default is to include all associated taxonomies in where clause
			if ( $taxonomies )
				$_taxonomies = array_intersect( $_taxonomies, $taxonomies );
		} else {
			$_taxonomies = array();
		}
		
		if ( $_taxonomies && ( 'post' == $src_name ) ) {
			$enabled_taxonomies = array_keys( array_intersect( scoper_get_option( 'use_taxonomies' ), array( 1, '1', true ) ) );
			$_taxonomies = array_intersect( $_taxonomies, $enabled_taxonomies );
		}
		
		$user_blog_roles = array( '' => array() );
		
		if ( $use_blog_roles ) {
			foreach( array_keys($user->blog_roles) as $date_key )
				$user_blog_roles[$date_key] = array_intersect_key( $user->blog_roles[$date_key], $qualifying_roles );
				
			// Also include user's WP blogrole(s),
			// but via equivalent RS role(s) to support scoping requirements (strict (i.e. restricted) terms, objects)
			if ( $wp_qualifying_roles = $this->scoper->role_defs->qualify_roles($reqd_caps, 'wp') ) {
				if ( $user_blog_roles_wp = array_intersect_key( $user->blog_roles[ANY_CONTENT_DATE_RS], $wp_qualifying_roles ) ) {
					// Credit user's qualifying WP blogrole via contained RS role(s)
					// so we can also enforce "term restrictions", which are based on RS roles
					$user_blog_roles_via_wp = $this->scoper->role_defs->get_contained_roles( array_keys($user_blog_roles_wp), false, 'rs');
					$user_blog_roles_via_wp = array_intersect_key($user_blog_roles_via_wp, $qualifying_roles);
					
					$user_blog_roles[ANY_CONTENT_DATE_RS] = array_merge( $user_blog_roles[ANY_CONTENT_DATE_RS], $user_blog_roles_via_wp);
				}
			}
		}
		
		/*
		// --- optional hack to require read_private cap via blog role AND object role
		// if the required capabilities include a read_private cap but no edit caps
		$require_blog_and_obj_role = ( in_array('read_private_posts', $reqd_caps) || in_array('read_private_pages', $reqd_caps) )   &&    ( ! array_diff( $reqd_caps, array('read_private_posts', 'read_private_pages', 'read') ) );
		// --- end hack ---
		*/
		
		//dump($qualifying_roles);
		//dump($objscope_objects);
		
		foreach ( array_keys($qualifying_roles) as $role_handle ) {
			//dump($role_handle);
			
			if ( $otype_use_object_roles && empty($require_blog_and_obj_role) ) {
				if ( ! empty($objscope_objects['restrictions'][$role_handle]) ) {
					$objscope_clause = " AND $src_table.{$src->cols->id} NOT IN ('" . implode("', '", array_keys($objscope_objects['restrictions'][$role_handle])) . "')";
				}
				elseif ( isset($objscope_objects['unrestrictions'][$role_handle]) ) {
					if ( ! empty($objscope_objects['unrestrictions'][$role_handle]) )
						$objscope_clause = " AND $src_table.{$src->cols->id} IN ('" . implode("', '", array_keys($objscope_objects['unrestrictions'][$role_handle])) . "')";
					else
						$objscope_clause = " AND 1=2";  // role is default-restricted for this object type, but objects are unrestrictions are set
				} else
					$objscope_clause = '';
			} else
				$objscope_clause = '';

			//dump($objscope_clause);
				
			$all_terms_qualified = array();
			$all_taxonomies_qualified = array();
			
			if ( $_taxonomies ) {
				$args['return_id_type'] = COL_TAXONOMY_ID_RS;
				
				$strict_taxonomies = array();
				
				foreach ($_taxonomies as $taxonomy)
					if ( $this->scoper->taxonomies->member_property($taxonomy, 'requires_term') )
						$strict_taxonomies[$taxonomy] = true;

				foreach ($_taxonomies as $taxonomy) {
					// we only need a separate clause for each role if considering object roles (and therefore considering that some objects might require some roles to be object-assigned)
					if ( ! $otype_use_object_roles )
						$role_handle_arg = $qualifying_roles;
					else
						$role_handle_arg = array( $role_handle => 1 );

					// If a taxonomy does not require objects to have a term, its term role assignments
					// will be purely supplemental; there is no basis for ignoring blogrole assignments.
					//
					// So if none of the taxonomies require each object to have a term 
					// AND the user has a qualifying role via blog assignment, we can skip the taxonomies clause altogether.
					// Otherwise, will consider current user's termroles 
					if ( ! $strict_taxonomies ) {
						if ( array_intersect_key($role_handle_arg, $user->blog_roles[ANY_CONTENT_DATE_RS]) ) {
							// User has a qualifying role by blog assignment, so term_id clause is not required 	
							$all_taxonomies_qualified[ANY_CONTENT_DATE_RS] = true;
							break;
						}
					}

					// qualify_terms returns:
					// terms for which current user has a qualifying role
					// 		- AND -
					// which are non-restricted (i.e. blend in blog assignments) for a qualifying role which the user has blog-wide 
					//
					// note: $reqd_caps function arg is used; qualify_terms will ignore reqd_caps element in args array
					if ( ! isset($term_count[$taxonomy]) )
						$term_count[$taxonomy] = $this->scoper->get_terms($taxonomy, UNFILTERED_RS, COL_COUNT_RS);

					if ( ! $term_count[$taxonomy] ) {
						$all_terms_qualified[''][$taxonomy] = true;
					} elseif ( $user_terms = $this->scoper->qualify_terms_daterange($reqd_caps, $taxonomy, $role_handle_arg, $args) ) {
						foreach ( array_keys($user_terms) as $date_key ) {
							if ( count($user_terms[$date_key]) ) {
								// don't bother applying term requirements if user has cap for all terms in this taxonomy
								if ( (count($user_terms[$date_key]) >= $term_count[$taxonomy]) && $this->scoper->taxonomies->member_property($taxonomy, 'requires_term') ) {
									// User is qualified for all terms in this taxonomy; no need for any term_id clauses
									$all_terms_qualified[$date_key][$taxonomy] = true;
								} else {
									$where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] = ( isset($where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy]) ) ? array_unique( array_merge($where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy], $user_terms[$date_key]) ) : $user_terms[$date_key];
								}
							}
							
							$all_taxonomies_qualified[$date_key] = ! empty( $all_terms_qualified[$date_key] ) && ( count($all_terms_qualified[$date_key]) == count($strict_taxonomies) );
						}
					}
				} // end foreach taxonomy
			}

			foreach ( array_keys($user_blog_roles) as $date_key ) {
				if ( ! empty($all_taxonomies_qualified[$date_key]) || ( ! $_taxonomies && ! empty($user_blog_roles[$date_key][$role_handle]) ) ) {
					if ( $date_key || $objscope_clause || ! empty($require_blog_and_obj_role) ) {
						$where[$date_key][$objscope_clause][BLOG_SCOPE_RS] = "1=1";
					} else {
						return "1=1";  // no need to include other clause if user has a qualifying role blog-wide or in all terms, it is not date-limited, and that role does not require object assignment for any objects
					}
				}
			}
		
			// if object roles should be applied, populatate array key to force inclusion of OBJECT_SCOPE_RS query clauses below
			if ( $otype_use_object_roles && isset($qualifying_object_roles[$role_handle]) && $user_qualifies_for_obj_roles ) {  // want to apply objscope requirements for anon user, but not apply any obj roles
				if ( $role_spec = scoper_explode_role_handle($role_handle) )
					$where[ANY_CONTENT_DATE_RS][NO_OBJSCOPE_CLAUSE_RS][OBJECT_SCOPE_RS][$role_spec->role_type][$role_spec->role_name] = true;
			}
			
			// we only need a separate clause for each role if considering object roles (and therefore considering that some objects might require some roles to be object-assigned)
			if ( ! $otype_use_object_roles && ! empty($where[ANY_CONTENT_DATE_RS]) )
				break;
		} // end foreach role

		// also include object scope clauses for any roles which qualify only for object-assignment
		if ( $otype_use_object_roles && isset( $qualifying_object_roles ) && $user_qualifies_for_obj_roles ) {	// want to apply objscope requirements for anon user, but not apply any obj roles
			if ( $obj_only_roles = array_diff_key( $qualifying_object_roles, $qualifying_roles ) ) {
				foreach ( array_keys($obj_only_roles) as $role_handle )
					if ( $role_spec = scoper_explode_role_handle($role_handle) )
						$where[ANY_CONTENT_DATE_RS][NO_OBJSCOPE_CLAUSE_RS][OBJECT_SCOPE_RS][$role_spec->role_type][$role_spec->role_name] = true;
			}
		}
		
		// DB query perf enhancement: if any terms are included regardless of post ID, don't also include those terms in ID-specific clause
		foreach ( array_keys($where) as $date_key ) {
			foreach ( array_keys($where[$date_key]) as $objscope_clause ) {
				if ( $objscope_clause && isset($where[$date_key][$objscope_clause][TERM_SCOPE_RS]) ) {
					foreach ( $where[$date_key][$objscope_clause][TERM_SCOPE_RS] as $taxonomy => $terms ) {
						
						if ( ! empty($terms) && ! empty($where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy]) ) {
							$where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] = array_diff( $where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy], $where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy] );
						
							if ( empty( $where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] ) ) {
								unset( $where[$date_key][$objscope_clause][TERM_SCOPE_RS][$taxonomy] );
	
								// if we removed a taxonomy array, don't leave behind a term scope array with no taxonomies
								if ( empty( $where[$date_key][$objscope_clause][TERM_SCOPE_RS] ) ) {
									unset( $where[$date_key][$objscope_clause][TERM_SCOPE_RS] );
					
									// if we removed a term scope array, don't leave behind an objscope array with no scopes
									if ( empty( $where[$date_key][$objscope_clause] ) )
										unset( $where[$date_key][$objscope_clause] );
								}
							}
						}
					}
				}
			}
		}

		// since object roles are not pre-loaded prior to this call, role date limits are handled via subselect, within the date_key = '' iteration
		$object_roles_duration_clause = scoper_get_duration_clause();

				
		// implode the array of where criteria into a query as concisely as possible 
		foreach ( $where as $date_key => $objscope_clauses ) {
	
			foreach ( $objscope_clauses as $objscope_clause => $scope_criteria ) {
				
				foreach ( array_keys($scope_criteria) as $scope ) {
					
					switch ($scope) {
					case BLOG_SCOPE_RS:
						$where[$date_key][$objscope_clause][BLOG_SCOPE_RS] = $where[$date_key][$objscope_clause][BLOG_SCOPE_RS] . " $objscope_clause";
						break;
					
					case TERM_SCOPE_RS:
						$taxonomy_clauses = array();
						
						foreach ( $scope_criteria[TERM_SCOPE_RS] as $taxonomy => $terms ) {
							$is_strict = ! empty( $strict_taxonomies[$taxonomy] );
							
							if ( $objscope_clause )	
								// Avoid " term_id IN (5) OR ( term_id IN (5) AND ID NOT IN (100) )  
								// Otherwise this redundancy can occur when various qualifying roles require object role assignment for different objects
								if ( ! empty($where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy]) )
									if ( ! $terms = array_diff($terms, $where[$date_key][NO_OBJSCOPE_CLAUSE_RS][TERM_SCOPE_RS][$taxonomy]) ) {  
										//unset($scope_criteria[TERM_SCOPE_RS][$taxonomy]);  // this doesn't affect anything (removed in v1.1)
										continue;
									}

							$terms = array_unique($terms);
							if ( $qvars = $this->scoper->taxonomies->get_terms_query_vars($taxonomy) )
								if ( $terms_query && ! $otype_use_object_roles ) {
									$qtv = $this->scoper->taxonomies->get_terms_query_vars($taxonomy, true);
									$taxonomy_clauses[false] []= "{$qtv->term->alias}.{$qtv->term->col_id} IN ('" . implode("', '", $terms) . "') $objscope_clause";
								} else {
									$this_tx_clause = "{$qvars->term->alias}.{$qvars->term->col_id} IN ('" . implode("', '", $terms) . "')";

									// Use a subselect rather than adding our own LEFT JOIN.
									$terms_subselect = "SELECT {$qvars->term->alias}.{$qvars->term->col_obj_id} FROM {$qvars->term->table} {$qvars->term->as} WHERE $this_tx_clause";

									if ( defined('RVY_VERSION') && $objrole_revisions_clause )
										$revision_clause = "OR ( $src_table.{$src->cols->type} = 'revision' AND $src_table.{$src->cols->parent} IN ( $terms_subselect ) )";
									else
										$revision_clause = '';

									$taxonomy_clauses[$is_strict] []= "( $src_table.{$src->cols->id} IN ( $terms_subselect ) $revision_clause ) $objscope_clause";
								}
						}

						if ( $taxonomy_clauses ) {
							// all taxonomy clauses concat: [taxonomy 1 clauses] [OR] [taxonomy 2 clauses] [OR] ...
							//$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = agp_implode(' ) OR ( ', $taxonomy_clauses, ' ( ', ' ) ');

							// strict taxonomy clauses (if any are present, they must all be satisfied)
							if ( ! empty( $taxonomy_clauses[true] ) ) {
								$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = agp_implode(' ) AND ( ', $taxonomy_clauses[true], ' ( ', ' ) ');
							
							// non-strict taxonomy clauses	
							} elseif ( ! empty( $taxonomy_clauses[false] ) ) {
								$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = agp_implode(' ) OR ( ', $taxonomy_clauses[false], ' ( ', ' ) ');
							} else {
								$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = '1=2';
							}
								
							// all taxonomy clauses concat: ( [strict taxonomy clause 1] [AND] [strict taxonomy clause 2]... ) [OR] [taxonomy 3 clauses] [OR] ...
							//$where[$date_key][$objscope_clause][TERM_SCOPE_RS] = agp_implode(' ) OR ( ', $taxonomy_clauses, ' ( ', ' ) ');
						}

						break;
	
					case OBJECT_SCOPE_RS:	// should only exist with nullstring objscope_clause
						if ( $user_qualifies_for_obj_roles ) {
	
							global $wpdb;
							$u_g_clause = $user->get_user_clause('uro');
							
							foreach ( array_keys($scope_criteria[OBJECT_SCOPE_RS]) as $role_type ) { //should be only one
								if ( $scope_criteria[OBJECT_SCOPE_RS][$role_type] )
									ksort( $scope_criteria[OBJECT_SCOPE_RS][$role_type] );	// sort array for efficient membuffering of query results
							
								// Combine all qualifying (and applied) object roles into a single OR clause						
								$role_in = "'" . implode("', '", array_keys($scope_criteria[OBJECT_SCOPE_RS][$role_type])) . "'";

								static $cache_obj_ids = array();

								if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'press-this.php' ) ) && ! empty($_REQUEST['action']) || did_action( 'save_post' ) || ! empty($_GET['doaction']) )
									$force_refresh = true;		

								$objrole_subselect = "SELECT DISTINCT uro.obj_or_term_id FROM $wpdb->user2role2object_rs AS uro WHERE uro.role_type = '$role_spec->role_type' AND uro.scope = 'object' AND uro.assign_for IN ('entity', 'both') AND uro.role_name IN ($role_in) AND uro.src_or_tx_name = '$src_name' $object_roles_duration_clause $u_g_clause ";

								if ( ! isset( $cache_obj_ids[$objrole_subselect] ) || ! empty($force_refresh) )  
									$cache_obj_ids[$objrole_subselect] = scoper_get_col( $objrole_subselect );

								if ( $cache_obj_ids[$objrole_subselect] ) {
									$where[$date_key][$objscope_clause][OBJECT_SCOPE_RS] = "$src_table.{$src->cols->id} IN ( '" . implode( "','", $cache_obj_ids[$objrole_subselect] ) . "' )";
								} else
									$where[$date_key][$objscope_clause][OBJECT_SCOPE_RS] = "1=2";

								if ( defined('RVY_VERSION') && $objrole_revisions_clause ) 
									$where[$date_key][$objscope_clause][OBJECT_SCOPE_RS] = "( {$where[$date_key][$objscope_clause]['object']} OR ( $src_table.{$src->cols->type} = 'revision' AND $src_table.{$src->cols->parent} IN ( '" . implode( "','", $cache_obj_ids[$objrole_subselect] ) . "' ) ) )";
							}
                        }
						
						break;
					} // end scope switch
				} // end foreach scope
				
				/*
				// --- optional hack to require read_private cap via blog role AND object role
				if ( ! empty($require_blog_and_obj_role) ) {
					if ( ! isset($where[$date_key][''][BLOG_SCOPE_RS]) )
						$where[$date_key][''][BLOG_SCOPE_RS] = '1=2';
					
					if ( ! isset($where[$date_key][''][TERM_SCOPE_RS]) )
						$where[$date_key][''][TERM_SCOPE_RS] = '1=2';
						
					if ( ! isset($where[$date_key][''][OBJECT_SCOPE_RS]) )
						$where[$date_key][''][OBJECT_SCOPE_RS] = '1=2';
	
					$where[$date_key][''] = "( ( {$where[$date_key]['']['blog']} ) OR ( {$where[$date_key]['']['term']} ) ) AND ( {$where[$date_key]['']['object']} )";
				} 
				else
				// --- end hack
				*/
				// all scope clauses concat: [object roles] OR [term ids] OR [blogrole1 clause] [OR] [blogrole2 clause] [OR] ...
				// Collapse the array to a string even if it's empty
				$where[$date_key][$objscope_clause] = agp_implode(' ) OR ( ', $where[$date_key][$objscope_clause], ' ( ', ' ) ');
			} // end foreach objscope clause
			
			$date_clause = '';
			
			if ( $date_key && is_serialized($date_key) ) {
				$content_date_limits = unserialize($date_key);
				
				if ( $content_date_limits->content_min_date_gmt )
					$date_clause .= " AND $src_table.{$src->cols->date} >= '" . $content_date_limits->content_min_date_gmt . "'";
					
				if ( $content_date_limits->content_max_date_gmt )
					$date_clause .= " AND $src_table.{$src->cols->date} <= '" . $content_date_limits->content_max_date_gmt . "'";
			}
			
			foreach ( array_keys($where[$date_key]) as $objscope_clause )
				if ( empty ($where[$date_key][$objscope_clause]) )
					unset($where[$date_key][$objscope_clause]);
	
			// all objscope clauses concat: [clauses w/o objscope] [OR] [objscope 1 clauses] [OR] [objscope 2 clauses]
			$where[$date_key] = agp_implode(' ) OR ( ', $where[$date_key], ' ( ', ' ) ');
			
			if ( $date_clause && $where[$date_key] )
				$where[$date_key] = "( $where[$date_key]{$date_clause} )"; 
			
		} // end foreach datekey (set of content date limits for which role(s) apply)
		
		
		foreach ( array_keys($where) as $date_key )
			if ( empty ($where[$date_key]) )
				unset($where[$date_key]);

		// all date clauses concat: [clauses w/o content date limits] [OR] [content date range 1 clauses] [OR] [content date range 2 clauses]
		$where = agp_implode(' ) OR ( ', $where, ' ( ', ' ) ');
		
		if ( empty($where) )
			$where = '1=2';
				
		return $where;
	}
	
	function flt_objects_results($results, $src_name, $object_types, $args = array()) {
		if ( ! $object_types || ( is_array($object_types) && count($object_types) > 1 ) )
			$object_type = cr_find_post_type();
		else
			$object_type = strval( $object_types );

		if ( ( 'edit.php' == $GLOBALS['pagenow'] ) && ! is_content_administrator_rs() ) {
			$post_type_obj = get_post_type_object( $object_type );

			if ( ! empty($post_type_obj->hierarchical) ) {
				// ScoperAncestry class is loaded by hardway_rs.php
				$ancestors = ScoperAncestry::get_page_ancestors(); // array of all ancestor IDs for keyed page_id, with direct parent first
	
				$args = array( 'remap_parents' => false );
				ScoperHardway::remap_tree( $results, $ancestors, 'ID', 'post_parent', $args );
			}
		}
		
		if ( $this->scoper->is_front() && empty($this->skip_teaser) ) {
			if ( $tease_otypes = $this->_get_teaser_object_types($src_name, $object_types, $args) ) {
				require_once( dirname(__FILE__).'/teaser_rs.php');
				
				$args['force_teaser'] = true;
				return ScoperTeaser::posts_teaser_prep_results( $results, $tease_otypes, $args );
			}
			
			// won't do anything unless teaser is enabled for object type(s)
			//$results = apply_filters('objects_teaser_pre_results_rs', $results, 'post', $object_type, array('force_teaser' => true));
		}
		
		return $results;
	}
	
	// currently only used to conditionally launch teaser filtering
	function flt_the_posts( $results, $query_obj ) {	
		if ( empty($this->skip_teaser) ) {
			//$object_type = cr_find_post_type( '', false); // object type detection is problematic due to insertion of posts query into page templates on some installations
			$object_type = '';
			
			// won't do anything unless teaser is enabled for object type(s)
			$results = apply_filters( 'objects_teaser_rs', $results, 'post', $object_type, array('force_teaser' => true) );
		}

		return $results;	
	}
	
	function flt_objects_teaser($results, $src_name, $object_types = '', $args = array()) {
		$defaults = array('user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 'request' => '', 'force_teaser' => false);
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $wpdb;
		
		if ( 'post' != $src_name )
			return $results;
		
		if ( is_admin() || defined('XMLRPC_REQUEST') )
			return $results;

		$object_types = $this->_get_object_types($src_name, $object_types);
		$tease_otypes = $this->_get_teaser_object_types($src_name, $object_types, $args);
		
		if ( empty($tease_otypes) || ( empty($force_teaser) && ! array_intersect($object_types, $tease_otypes) ) )
			return $results;
		
		require_once( dirname(__FILE__).'/teaser_rs.php');
		return ScoperTeaser::posts_teaser($results, $tease_otypes, $args);
	}

} // end class


function agp_parse_after_WHERE_11( $request, &$pos_where, &$pos_suffix ) {
	$request_u = strtoupper($request);
	$pos_where = strpos( $request_u, ' WHERE 1=1');
	
	if ( ! $pos_where ) {
		if ( $pos_suffix = agp_get_suffix_pos($request) )
			$where =  substr($request, $pos_suffix); 
	} else {
		// note: this will still also contain any orderby/limit/groupby clauses ( okay since we won't append anything to the end )
		$where = substr($request, $pos_where + strlen(' WHERE 1=1 ')); 
	}
	
	return $where;	
}

function agp_get_suffix_pos( $request ) {
	$request_u = strtoupper($request);

	$pos_suffix = strlen($request) + 1;
	foreach ( array(' ORDER BY ', ' GROUP BY ', ' LIMIT ') as $suffix_term )
		if ( $pos = strrpos($request_u, $suffix_term) )
			if ( $pos < $pos_suffix )
				$pos_suffix = $pos;
				
	return $pos_suffix;
}
?>