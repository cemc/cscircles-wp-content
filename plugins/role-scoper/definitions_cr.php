<?php
require_once( dirname(__FILE__).'/wp-cap-helper_cr.php' );

function cr_access_types() {
	$arr = array( 'front' => (object) array(), 'admin' => (object) array() );
	
	if ( is_admin() ) {
		$arr['front']->labels = (object) array( 'name' => __('front-end', 'scoper') );
		$arr['admin']->labels = (object) array( 'name' => __('admin', 'scoper') );
	}

	return $arr;	
}

function cr_data_sources() {
	global $wpdb;

	$arr = array();
	
	$is_admin = is_admin();
	
	$name = 'post';		
	$arr[$name] = (object) array(
	'table_basename' => 'posts',		
	'cols' => (object) array( 
		'id' => 'ID', 					'name' => 'post_title', 		'type' => 'post_type', 
		'owner' => 'post_author', 		'content' => 'post_content', 	'parent' => 'post_parent',
		'status' => 'post_status', 		'excerpt' => 'post_excerpt',	'date' => 'post_date_gmt'
		),
	'http_post_vars' => (object) array( 'id' => 'post_ID', 'category' => 'post_category' ),
	'uri_vars' => (object) array( 'id' => 'post' ),
	'uri_vars_alt' => (object) array( 'id' => array('post_id', 'p') ),
	'http_post_vars_alt' => (object) array( 'id' => array('post_id') ),

	'collections' => array ( 'type' => 'object_types' ),
	'object_types' => array(),

	'query_hooks' => (object) array( 'results' => 'posts_results', 'listing' => 'the_posts' ),
	
	'query_replacements' => array( "OR post_author = [user_id] AND post_status = 'private'" => "OR post_status = 'private'" )
	
	); // end outer array		NOTE: posts_request is manually hooked to take advantage of passed wp_query object reference
			
	// populate object_types property based on registered WP post types
	cr_add_post_types( $arr );
		
	// populate statuses property based on registered WP post statuses
	$arr[$name]->statuses = array_merge( get_post_stati( array( 'internal' => null ) ), array( 'trash' ) );
	
	$arr[$name]->query_replacements = array( "OR $wpdb->posts.post_author = [user_id] AND $wpdb->posts.post_status = 'private'" => "OR $wpdb->posts.post_status = 'private'" );
	
	$arr[$name]->admin_actions = (object) array(	
		'save_object' => 'save_post',	'edit_object' => 'edit_post', 
		'create_object' => '',			'delete_object' => 'delete_post',
		'object_edit_ui' => '' );  // post data source defines an object_type-specific object_edit_ui hook
		
	$arr[$name]->admin_filters = (object) array();
	$arr[$name]->admin_filters->pre_object_status = 'pre_post_status';
	
	// define html inserts for object role administration only if this is an admin URI
	if ( $is_admin ) {
		$arr['post']->labels = (object) array( 'name' => __('Posts'), 'singular_name' => __('Post') );
		$arr['post']->edit_url = 'post.php?action=edit&amp;post=%d';  // xhtml validation fails with &post=
	}
	
	$name = 'link';		
	$arr[$name] = (object) array(
	'table_basename' => 'links',		
	'cols' => (object) array(
		'id' => 'link_id', 				'name' => 'link_name', 			'type' => '', 
		'owner' => 'link_owner',		'status' => ''
		),

	'no_object_roles' => true
	); // end outer array

	$arr[$name]->admin_actions = (object) array(	
	'save_object' => '',			'edit_object' => 'edit_link', 
	'create_object' => 'add_link',	'delete_object' => 'delete_link' );

	if ( $is_admin ) {
		$arr['link']->labels = (object) array( 'name' => __('Links'), 'singular_name' => __('Link') );
		$arr['link']->edit_url = 'link.php?action=edit&amp;link_id=%s';
	}
		
	if ( $is_admin || defined('XMLRPC_REQUEST') ) {
		//groups table 
		// scoper-defined table can be customized via db-config_rs.php
		$name = 'group';
		$arr[$name] = (object) array(
		'table_no_prefix' => true,
		'table_basename' => $wpdb->groups_rs,
		'cols' => (object) array(
			'id' => $wpdb->groups_id_col,	'name' => $wpdb->groups_name_col, 'owner' => '', 'status' => '', 'type' => ''
			),
		'uri_vars' => array( 'id' => 'id'),
		'http_post_vars' => array( 'id' => 'id'),
		
		'query_hooks' => (object) array( 'request' => 'groups_request' ),
		
		); // end outer array
		
		if ( $is_admin ) {
			$arr['group']->labels = (object) array( 'name' => __('Groups', 'scoper'), 'singular_name' => __('Group', 'scoper') );
			$arr['group']->edit_url = 'admin.php?page=rs-groups&amp;mode=edit&amp;id=%d';
		}
	}

	return $arr;
}

function cr_add_post_types( &$data_source_members ) {
	$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );

	foreach ( $post_types as $name => $post_type_obj ) {
		$post_type_clause = ( 'post' == $name ) ? '' : "?post_type=$name";
			
		$arr = array(
			'name' => $name,
			'uri' => array( "wp-admin/post.php{$post_type_clause}", "wp-admin/post-new.php{$post_type_clause}", "wp-admin/edit.php{$post_type_clause}" ),
			'labels' => (object) array(  // possible TODO: reference to post type object labels object
				'singular_name' => $post_type_obj->labels->singular_name,
				'name' => $post_type_obj->labels->name
			)
		);

		if ( ! $post_type_obj->hierarchical ) {
			$arr['admin_default_hide_empty'] = true;
			$arr['admin_max_unroled_objects'] = 100;
			$arr['ignore_object_hierarchy'] = true;
		}	

		$data_source_members['post']->object_types[$name] = (object) $arr;
	}
}

function cr_taxonomies() {
	$arr = array();

	$is_admin = is_admin();
	
	// Sample code: use the following syntax to define custom taxonomies which use a custom source (not the wp_term_taxonomy table)
	/*
		$tx =& $arr['your_taxonomy_name'];
		$tx->requires_term = 1;
		$tx->uses_standard_schema = 0;	// this would also be set false by WP_Taxonomies::process
		
		$tx->source = 'category';
	
		$tx->table_term2obj_basename = 'post2cat';
		$tx->table_term2obj_alias = '';
		
		$tx->cols = (object) array( 
			'count' => 'category_count', 		'term2obj_tid' => 'category_id', 	'term2obj_oid' => 'post_id',
			'require_zero' => 'link_count', 	'require_nonzero' => '' );
			
		$tx->admin_actions->pre_object_terms = 'category_save_pre';
	*/
	
	$arr = array_merge( $arr, cr_wp_taxonomies() );
	
	$name = 'link_category';  // note: also requires 'data_sources' definition
	$arr[$name] = (object) array(
		'requires_term' => true, 'uses_standard_schema' => true, 'hierarchical' => false, 'default_term_option' => 'default_link_category', 'object_source' => 'link'
	);
	
	$arr[$name]->admin_actions = (object) array( 'save_term' => "save_term", 	'edit_term' => "edit_term", 			'create_term' => "created_term", 
												'delete_term' => "delete_term", 'term_edit_ui' => 'edit_link_category_form' );

	$arr[$name]->admin_filters = (object) array( 'pre_object_terms' => 'pre_link_category' );		// not actually applied as of WP 3.0
	
	if ( $is_admin ) {
		// WP displays as "Category"
		$arr[$name]->labels = (object) array( 'name' => __('Link Categories'), 'singular_name' => __('Link Category') );
	
		if ( awp_ver( '3.1' ) ) {
			$arr[$name]->edit_url = 'edit-tags.php?action=edit&amp;taxonomy=link_category&amp;tag_ID=%d';
			$arr[$name]->uri_vars = (object) array( 'id' => 'tag_ID' );
			$arr[$name]->http_post_vars = (object) array( 'id' => 'tag_ID' );
		} else {
			$arr[$name]->edit_url = 'link-category.php?action=edit&amp;cat_ID=%d';
			$arr[$name]->uri_vars = (object) array( 'id' => 'cat_ID' );
			$arr[$name]->http_post_vars = (object) array( 'id' => 'cat_ID' );
		}	
	}
	
	return $arr;
}

function cr_wp_taxonomies() {
	$arr = array();

	$arr_use_wp_taxonomies = array_intersect( (array) scoper_get_option( 'use_taxonomies' ), array( true, 1, '1' ) );

	$taxonomies = get_taxonomies( array( 'public' => true ), 'object' );
	$taxonomies ['nav_menu']= get_taxonomy( 'nav_menu' );
	
	$post_types = get_post_types( array( 'public' => true ) );
	$post_types []= 'nav_menu_item';

	$support_retrictions = apply_filters( 'restrictable_taxonomies_rs', array( 'post_status' => true ) );	// for Edit Flow plugin
	
	// Detect and support additional WP taxonomies (just require activation via Role Scoper options panel)
	global $scoper;

	foreach ( $taxonomies as $taxonomy => $wp_tax ) {
		if ( ! isset( $arr_use_wp_taxonomies[$taxonomy] ) && ! in_array( $GLOBALS['plugin_page_cr'], array( 'rs-options', 'rs-site_options' ) ) )  // always load taxonomy ID data for Realm Options display
			continue;

		// taxonomy must be approved for scoping and have a Scoper-defined object type
		$tx_otypes = (array) $wp_tax->object_type;

		foreach ( $tx_otypes as $wp_tax_object_type ) {
			if ( in_array($wp_tax_object_type, $post_types) ) {
				$src_name = 'post';
				break;
			} elseif ( $scoper->data_sources->is_member($wp_tax_object_type) ) {
				$src_name = $wp_tax_object_type;
				break;
			} elseif ( $src_name = $scoper->data_sources->is_member_alias($wp_tax_object_type) )  // in case the 3rd party plugin uses a taxonomy->object_type property different from the src_name we use for RS data source definition
				break;
		}
		
		// create taxonomies definition (additional properties will be set later)
		$arr[$taxonomy] = (object) array(
			'name' => $taxonomy,								
			'uses_standard_schema' => 1,	'autodetected_wp_taxonomy' => 1,
			'default_term_option' => "default_{$taxonomy}",
			'hierarchical' => $wp_tax->hierarchical,
			'object_source' => $src_name,
			'labels' => (object) array( 'name' => $wp_tax->labels->name, 'singular_name' => $wp_tax->labels->singular_name  ),
			'requires_term' => $wp_tax->hierarchical || isset( $support_retrictions[$taxonomy] )
		);

		if ( is_admin() ) {
			// temporary hardcode
			if ( 'nav_menu' == $taxonomy )
				$arr[$taxonomy]->requires_term = true;
			
			$arr[$taxonomy]->admin_actions = array( 'save_term' => "save_term", 		'edit_term' => "edit_term", 			'create_term' => "created_term", 
													'delete_term' => "delete_term", 	'term_edit_ui' => "{$taxonomy}_edit_form" );
			
			$arr[$taxonomy]->admin_filters = array( 'pre_object_terms' => "pre_post_{$taxonomy}" );
			
			if ( 'nav_menu' == $taxonomy )
				$arr[$taxonomy]->edit_url = "nav-menus.php?action=edit&menu=%d";
		}
	} // end foreach taxonomy known to WP core

	return $arr;
}


function cr_cap_defs() {
	
	// add standard caps for all post types
	$arr = cr_taxonomy_cap_defs();
	$arr = array_merge( $arr, cr_post_cap_defs() );
	
	$_arr = array(
	'read' =>  						(object) array( 'src_name' => 'post',  'op_type' => OP_READ_RS,	 'anon_user_has' => true, 'no_custom_remove' => true  ),
	
	'upload_files' => 				(object) array( 'src_name' => 'post',  'op_type' => '',			 'ignore_restrictions' => true ),
	'moderate_comments' => 			(object) array( 'src_name' => 'post',  'op_type' => '' ),
	'unfiltered_html' => 			(object) array( 'src_name' => 'post',  'op_type' => '' ),

	'manage_links' =>  				(object) array( 'src_name' => 'link',  'op_type' => OP_ADMIN_RS, 'no_custom_remove' => true, 'object_types' => array( 'link' ) ),	/* set object_type explicitly for link due to complication with manage_categories cap association */
	
	'manage_groups' =>   			(object) array( 'src_name' => 'group', 'op_type' => OP_ADMIN_RS, 'no_custom_remove' => true, 'defining_module' => 'role-scoper' ),
	'recommend_group_membership' => (object) array( 'src_name' => 'group', 'op_type' => OP_EDIT_RS,  'no_custom_remove' => true, 'defining_module' => 'role-scoper' ),
	'request_group_membership' =>  	(object) array( 'src_name' => 'group', 'op_type' => OP_EDIT_RS,  'no_custom_remove' => true, 'defining_module' => 'role-scoper' )
	
	); // CapDefs array
	
	$arr = array_merge( $arr, $_arr );
	
	// important: any rs-introduced caps in standard post / page roles must not be included in core role caps and must have no_custom_add, no_custom_remove properties set (otherwise would need to add code in get_contained_roles to disregard such caps) 

	return $arr;
}

function cr_post_cap_defs() {	
	$arr = array();
	
	$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );
	
	$use_post_types = scoper_get_option( 'use_post_types' );

	foreach ( $post_types as $name => $post_type_obj ) {
		if ( empty( $use_post_types[$name] ) )
			continue;
		
		$cap = $post_type_obj->cap;
		
		$post_caps = array(
			$cap->read_private_posts =>		(object) array( 'src_name' => 'post', 'op_type' => OP_READ_RS, 		'base_cap' => 'read', 				'status' => 'private' ),	
			$cap->edit_posts => 			(object) array( 'src_name' => 'post', 'op_type' => OP_EDIT_RS,		'no_custom_remove' => true ),
			$cap->edit_others_posts =>  	(object) array( 'src_name' => 'post', 'op_type' => OP_EDIT_RS, 		'base_cap' => $cap->edit_posts, 	'no_custom_remove' => true  ),
			$cap->edit_private_posts =>  	(object) array( 'src_name' => 'post', 'op_type' => OP_EDIT_RS,		'base_cap' => $cap->edit_posts, 	'status' => 'private' ),
			$cap->edit_published_posts => 	(object) array( 'src_name' => 'post', 'op_type' => OP_EDIT_RS,		'status' => 'publish' ),
			$cap->delete_posts =>  			(object) array( 'src_name' => 'post', 'op_type' => OP_DELETE_RS ),
			$cap->delete_others_posts =>  	(object) array( 'src_name' => 'post', 'op_type' => OP_DELETE_RS, 	'base_cap' => $cap->delete_posts ),
			$cap->delete_private_posts =>  	(object) array( 'src_name' => 'post', 'op_type' => OP_DELETE_RS,	'base_cap' => $cap->delete_posts, 	'status' => 'private' ),
			$cap->delete_published_posts =>	(object) array( 'src_name' => 'post', 'op_type' => OP_DELETE_RS,	'status' => 'publish' ),
			$cap->publish_posts => 			(object) array( 'src_name' => 'post', 'op_type' => OP_PUBLISH_RS )
		);

		if ( awp_ver( '3.5-beta' ) && isset($cap->create_posts) && ( $cap->create_posts != $cap->edit_posts ) )
			$post_caps[ $cap->create_posts ] = (object) array( 'src_name' => 'post', 'op_type' => OP_EDIT_RS );

		if ( $post_type_obj->hierarchical ) {
			$plural_name = plural_name_from_cap_rs( $post_type_obj );
			$post_caps["create_child_{$plural_name}"] = (object) array( 'src_name' => 'post', 'op_type' => OP_ASSOCIATE_RS, 'no_custom_add' => true, 'no_custom_remove' => true, 'defining_module' => 'role-scoper', 'src_name' => 'post', 'object_types' => array( $name ) );
		}
			
		$arr = array_merge( $arr, $post_caps );
	}
	
	return $arr;
}

function cr_taxonomy_cap_defs() {
	$arr = array();
	
	$taxonomies = get_taxonomies( array( 'public' => true ), 'object' );
	$taxonomies ['nav_menu']= get_taxonomy( 'nav_menu' );
	
	foreach ( $taxonomies as $name => $taxonomy_obj ) {	
		$arr[ $taxonomy_obj->cap->manage_terms ] = 	(object) array( 'src_name' => 'post', 'op_type' => OP_ADMIN_RS, 'is_taxonomy_cap' => true );  // possible TODO: set src_name as taxonomy source instead?
		
		// in case these have been customized to a different cap name...
		$arr[ $taxonomy_obj->cap->edit_terms ] = 	(object) array( 'src_name' => 'post', 'op_type' => OP_ADMIN_RS, 'is_taxonomy_cap' => true );
		$arr[ $taxonomy_obj->cap->delete_terms ] = 	(object) array( 'src_name' => 'post', 'op_type' => OP_ADMIN_RS, 'is_taxonomy_cap' => true );

		$arr[ "assign_{$name}" ] = (object) array( 'src_name' => 'post', 'op_type' => OP_ASSIGN_RS, 'is_taxonomy_cap' => true );
	}
	
	// workaround to support scoped Nav Menu Manager role
	$arr['edit_theme_options'] = (object) array( 'object_types' => array( 'nav_menu' ) );
	
	return $arr;
}


//note: rs_ is a role type prefix which is required for array key, but will be stripped off for name property
function cr_role_caps() {
	// separate array is friendlier to php array function
	$arr = array(
		'rs_link_reader' => array(
			'read' => true
		),
		'rs_link_editor' => array(
			'read' => true,
			'manage_links' => true
		),
		'rs_link_category_manager' => array(
			'manage_categories' => true
		),
		'rs_group_manager' => array(
			'manage_groups' => true,
			'recommend_group_membership' => true,
			'request_group_membership' => true
		)
	); // end role_caps array
	
	//if ( defined( 'USER_QUERY_RS' ) ) {
		$arr['rs_group_moderator'] = array(
			'recommend_group_membership' => true,
			'request_group_membership' => true
		);
		
		$arr['rs_group_applicant'] = array(
			'request_group_membership' => true
		);
	//}
	
	$arr = array_merge( $arr, cr_post_role_caps() );
	$arr = array_merge( $arr, cr_taxonomy_role_caps() );
	
	return $arr;
}

function cr_post_role_caps() {
	$arr = array();
	
	$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );

	$use_post_types = scoper_get_option( 'use_post_types' );

	$force_create_posts_cap = awp_ver( '3.5-beta' ) && scoper_get_option( 'define_create_posts_cap' );
	
	foreach ( $post_types as $name => $post_type_obj ) {
		if ( empty( $use_post_types[$name] ) )
			continue;
				
		$cap = $post_type_obj->cap;
		
		$arr["rs_{$name}_reader"] = array(
			"read" => true
		);
		$arr["rs_private_{$name}_reader"] = array(
			$cap->read_private_posts => true,
			"read" => true
		);
		
		$arr["rs_{$name}_contributor"] = array(
			$cap->edit_posts => true,
			$cap->delete_posts => true,
			"read" => true
		);
		if ( $force_create_posts_cap )
			$arr["rs_{$name}_contributor"][$cap->create_posts] = true;
		
		if ( defined( 'RVY_VERSION' ) ) {
			$arr["rs_{$name}_revisor"] = array(
				$cap->edit_posts => true,
				$cap->delete_posts => true,
				"read" => true,
				$cap->read_private_posts => true,
				$cap->edit_others_posts => true
			);
		}
		if ( $force_create_posts_cap )
			$arr["rs_{$name}_revisor"][$cap->create_posts] = true;
	
		$arr["rs_{$name}_author"] = array(
			"upload_files" => true,
			$cap->publish_posts => true,
			$cap->edit_published_posts => true,
			$cap->delete_published_posts => true,
			$cap->edit_posts => true,
			$cap->delete_posts => true,
			"read" => true
		);
		if ( $force_create_posts_cap )
			$arr["rs_{$name}_author"][$cap->create_posts] = true;
		
		$arr["rs_{$name}_editor"] = array(
			"moderate_comments" => true,
			$cap->delete_others_posts => true,
			$cap->edit_others_posts => true,
			"upload_files" => true,
			"unfiltered_html" => true,
			$cap->publish_posts => true,
			$cap->delete_private_posts => true,
			$cap->edit_private_posts => true,
			$cap->delete_published_posts => true,
			$cap->edit_published_posts => true,
			$cap->delete_posts => true,
			$cap->edit_posts => true,
			$cap->read_private_posts => true,
			"read" => true
		);
		if ( $force_create_posts_cap )
			$arr["rs_{$name}_editor"][$cap->create_posts] = true;
		
		// Note: create_child_pages should only be present in associate role, which is used as an object-assigned alternate to blog-wide edit role
		// This way, blog-assignment of author role allows user to create new pages, but only as subpages of pages they can edit (or for which Associate role is object-assigned)
		if ( $post_type_obj->hierarchical ) {
			$plural_name = plural_name_from_cap_rs( $post_type_obj );
		
			$arr["rs_{$name}_associate"] = array( 
				"create_child_{$plural_name}" => true,
				'read' => true
			);
		}
	}

	return $arr;
}

function cr_taxonomy_role_caps() {
	$arr = array();
	
	$taxonomies = get_taxonomies( array( 'public' => true ), 'object' );
	$taxonomies ['nav_menu']= get_taxonomy( 'nav_menu' );
	
	$use_taxonomies = scoper_get_option( 'use_taxonomies' );
	
	foreach ( $taxonomies as $name => $taxonomy_obj ) {	
		if ( empty( $use_taxonomies[$name] ) )
			continue;
			
		$arr["rs_{$name}_manager"][$taxonomy_obj->cap->manage_terms] = true;
		
		// in case these have been customized to a different cap name...
		$arr["rs_{$name}_manager"][$taxonomy_obj->cap->edit_terms] = true;
		$arr["rs_{$name}_manager"][$taxonomy_obj->cap->delete_terms] = true;
		//$arr["rs_{$name}_manager"]["assign_$name"] = true;  // this prevents crediting of Category Manager role from WP Editor caps
		
		$arr["rs_{$name}_assigner"]["assign_$name"] = true;
	}
	
	return $arr;	
}



//
//note: rs_ is a role type prefix which is required for array key, but will be stripped off for name property
function cr_role_defs() {
	$arr = array(
		'rs_link_reader' =>			(object) array( 'src_name' => 'link', 'anon_user_blogrole' => true ),
		'rs_link_editor' =>			(object) array( 'src_name' => 'link' ),
		'rs_group_manager' =>		(object) array( 'src_name' => 'group' )
	); // end role_defs array

	//if ( defined( 'USER_QUERY_RS' ) ) {
		$arr['rs_group_moderator'] = (object) array( 'src_name' => 'group' );
		$arr['rs_group_applicant'] = (object) array( 'src_name' => 'group' );
	//}
	
	$arr = array_merge( $arr, cr_post_role_defs() );
	$arr = array_merge( $arr, cr_taxonomy_role_defs() );
	
	foreach ( array_keys($arr) as $key )
		$arr[$key]->role_type = 'rs';

	return $arr;
}

function cr_post_role_defs() {
	$arr = array();
	
	$custom_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );
	
	$use_post_types = scoper_get_option( 'use_post_types' );

	foreach ( $custom_types as $name => $post_type_obj ) {	
		if ( empty( $use_post_types[$name] ) )
			continue;
			
		// note: object scope converts 'others' cap requirements to owner base cap, so for object scope assignment with default role caps, 'Authors' and 'Editors' are equivalent.  
		// Define 'Editors' roles for object assignment to avoid ambiguity with WP 'Post Author' / 'Page Author', who may have fewer caps on his object than the scoped "Authors".
			
		$arr["rs_{$name}_reader"] = 		(object) array( 'src_name' => 'post', 'object_type' => $name, 'valid_scopes' => array( 'blog' => true, 'term' => true ), 'anon_user_blogrole' => true );
		$arr["rs_private_{$name}_reader"] =	(object) array( 'src_name' => 'post', 'object_type' => $name, 'objscope_equivalents' => array("rs_{$name}_reader") );
	
		$arr["rs_{$name}_contributor"] =	(object) array( 'src_name' => 'post', 'object_type' => $name );
		$arr["rs_{$name}_author"] =			(object) array( 'src_name' => 'post', 'object_type' => $name, 'valid_scopes' => array( 'blog' => true, 'term' => true ) );
		
		if ( defined( 'RVY_VERSION' ) )
			$arr["rs_{$name}_revisor"] = 	(object) array( 'src_name' => 'post', 'object_type' => $name );
		
		$arr["rs_{$name}_editor"] = 		(object) array( 'src_name' => 'post', 'object_type' => $name, 'objscope_equivalents' => array("rs_{$name}_author") );
		

		if ( $post_type_obj->hierarchical ) {												// TODO: review this
			$arr["rs_{$name}_associate"] =	(object) array( 'src_name' => 'post', 'object_type' => $name ); //including this confuses determination of equiv. RS roles from WP blogrole (see CR_Role_Defs::qualify_roles()    
			
			if ( is_admin() )
				$arr["rs_{$name}_associate"]->no_custom_caps = true;
		}
			
		if ( is_admin() )
			$arr["rs_private_{$name}_reader"]->other_scopes_check_role = array( 'private' => "rs_private_{$name}_reader", '' => "rs_{$name}_reader" );
	}
	
	return $arr;
}

function cr_taxonomy_role_defs() {
	$arr = array(
		'rs_link_category_manager' =>	(object) array( 'src_name' => 'link', 'object_type' => 'link_category', 'valid_scopes' => array( 'term' => true ), 'no_custom_caps' => true )
	);
	
	$taxonomies = get_taxonomies( array( 'public' => true ), 'object' );
	$taxonomies ['nav_menu']= get_taxonomy( 'nav_menu' );

	$use_taxonomies = scoper_get_option( 'use_taxonomies' );
	
	foreach ( $taxonomies as $name => $taxonomy_obj ) {	
		if ( empty( $use_taxonomies[$name] ) )
			continue;

		$arr["rs_{$name}_manager"] = (object) array( 'src_name' => 'post', 'object_type' => $name, 'no_custom_caps' => true );
		
		if ( 'nav_menu' != $name )
			$arr["rs_{$name}_assigner"] = (object) array( 'src_name' => 'post', 'object_type' => $name, 'no_custom_caps' => true );
	}

	return $arr;	
}


function cr_get_reqd_caps( $src_name, $op, $object_type = -1, $status = -1, $base_caps_only = false, $preview_future = false ) {
	require_once( dirname(__FILE__).'/reqd_caps_cr.php' );
	return _cr_get_reqd_caps( $src_name, $op, $object_type, $status, $base_caps_only, $preview_future );
}

?>