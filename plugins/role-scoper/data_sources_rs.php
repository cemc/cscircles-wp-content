<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/lib/agapetry_config_items.php');

class CR_Data_Sources extends AGP_Config_Items {

	// optionally, populate with StdObject objects by passing in array of members
	function __construct( $arr = '', $action_hook = '' ) {  
		global $wpdb;

		parent::__construct( $arr, $action_hook );
		
		// add default WP taxonomy data source
		// note: term_taxonomy must be registered as the source table to support abstract descendant retrieval function.
		// 		 code will account for location of name column in related terms table
		$args = array(
			'is_taxonomy' => 1,		'taxonomy_only' => 1,	
			'table_basename' => 'term_taxonomy',  'table' => $wpdb->prefix . 'term_taxonomy',
			'table_alias' => 'tt',
			'cols' => (object) array( 'id' => 'term_id', 'name' => 'name', 'parent' => 'parent' ),			// NOTE on ID col: DB queries actually use term_taxonomy_id based on attributes returned by get_terms_query_vars.  term_id here is used for categories queries.  Possible Todo: resolve this discrepancy and potential bug source
			'http_post_vars' => (object) array( 'id' => 'tag_ID' ),
			'uri_vars' => (object) array( 'id' => 'tag_ID' )
		); // end outer array
		
		// $src_name, $table_basename, $label_singular, $label_name, $col_id, $col_name
		$this->add_item('term', 'role-scoper', __('Term', 'scoper'), __('Terms', 'scoper'), 'terms', 'term_id', 'name', $args );
	}
	
	function &add_item( $name, $defining_module, $label_singular, $label_name, $table_basename, $col_id, $col_name, $args) {	
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a data source (%2$s).', $defining_module, $name)
					. '<br /><br />' . 'This must be done via the define_data_sources_rs hook.';
			rs_notice($notice);
			return;
		}
	
		if ( isset($this->members[$name]) )
			unset($this->members[$name]);
			
		$this->members[$name] = new CR_Data_Source($name, $defining_module, $label_singular, $label_name, $table_basename, $col_id, $col_name, $args);

		$this->process( $this->members[$name] );
		return $this->members[$name];
	}
	
	
	// accepts reference to WP_Data_Source object (must pass object so we can call base class function statically)
	function process( &$src ) {
		global $wpdb;
		
		// apply wp prefix to tablename
		if ( ! isset($src->table) && ! empty($src->table_basename) ) {  // the prefix was already applied, or is unnecessary
			if ( empty($src->table_no_prefix) )
				$src->table = $wpdb->prefix . $src->table_basename;
			else
				$src->table = $src->table_basename;
		}
		
		// if no alias specified, set alias property to table name
		if ( empty($src->table_alias) )
			$src->table_alias = $src->table;
	
		// default object_types array to single member matching source name
		if ( empty($src->object_types) ) {
			$src->object_types = array( $src->name => (object) array() );
			
			if ( is_admin() ) {
				$src->object_types[$src->name]->labels = $src->labels;
			}
		}

		foreach ( array_keys($src->object_types) as $name )
			$src->object_types[$name]->name = $name;
	}
	
	function get_object($src_name, $object_id, $cols = '') {
		// special cases to take advantage of cached post/link
		if ( ('post' == $src_name) ) {
			if ( $cols && ! strpos( $cols, ',' ) )
				return get_post_field( $cols, $object_id, 'raw' );
			else
				return get_post($object_id);
				
		} elseif ( 'link' == $src_name ) {
			return get_bookmark($object_id);
		
		} else {
			if ( ! $src = $this->get($src_name) )
				return;

			global $wpdb;
			
			if ( ! $cols )
				$cols = '*';

			if ( empty($object_id) )
				return array();

			global $wpdb;
			return scoper_get_row( $wpdb->prepare( "SELECT $cols FROM $src->table WHERE {$src->cols->id} = %d LIMIT 1", $object_id ) );
		} // end switch
	}
	

	function detect($what, $src, $object_id = 0, $object_type = '', $query = '') {
		// so we can pass in $src object or $src_name string
		if ( ! $src = $this->get($src) )
			return;
	
		// if there is only one possible answer, give it
		if ( 'id' != $what ) {
			if ( ( 'type' == $what ) && ( 'post' == $src->name ) ) 
				return cr_find_post_type();

			if ( $it = $this->get_the_only($what, $src) )
				return $it;
			
			if ( $it = $this->get_from_func($what, $src) )
				return $it;
		} else {
			if ( defined('XMLRPC_REQUEST') ) {
				if ( ! empty( $GLOBALS['xmlrpc_post_id_rs'] ) )
					return $GLOBALS['xmlrpc_post_id_rs'];
				else {
					global $wp_xmlrpc_server;
					
					if ( ! empty( $wp_xmlrpc_server->message->params) ) {
						if ( in_array( $wp_xmlrpc_server->message->methodName, array( 'mt.setPostCategories', 'metaWeblog.editPost', 'metaWeblog.getPost' ) ) ) {
							if ( ! empty( $wp_xmlrpc_server->message->params[0] ) ) {
								return $wp_xmlrpc_server->message->params[0];
							}
						}
					}
					
					return 0;
				}
			}
				
			if ( 'post' == $src->name && ! empty($GLOBALS['post']) ) {
				if ( ! is_object($GLOBALS['post']) )
					$GLOBALS['post'] = get_post( $GLOBALS['post'] );

				if ( 'auto-draft' == $GLOBALS['post']->post_status )
					return 0;
				else
					return $GLOBALS['post']->ID;
			}
		}
	
		// Is it set as a $_POST variable?
		if ( $it = $this->get_from_http_post($what, $src, $object_type) )
			return $it;
		
		/*
		// TODO: test this (it would eliminate unnecessary clauses in some queries where object type can be determined)
		// Is it one of the query variables in current WP query?
		if ( 'post' == $src->name ) {
			global $wp_query;
			if ( ! empty($wp_query->query) ) {
				if ( $it = $this->get_from_queryvars($what, $src, $wp_query->query, $object_type) )
					return $it;
			}
		}
		*/
		
		// If we have the object ID, go to the source
		if ( $object_id )
			if ( $it = $this->get_from_db($what, $src, $object_id) )
				return $it;
					
		// Is it one of the query variables in current URI?
		if ( $it = $this->get_from_uri($what, $src, $object_type) )
			return $it;
			
		// Does the last database query include a helpful equality clause?
		if ( $it = $this->get_from_query($what, $src, $query) )
			return $it;
			
		// if detection failed and the desired quanity is a member of a config array, default to first array element
		if ( $it = $this->get_the_only($what, $src, true) )
			return $it;
		
		return ( in_array( $what, array( 'id', 'parent' ) ) ) ? 0 : '';
	}
	
	function get_the_only($what, $src, $force_first = false) {
		if ( ! $src = $this->get($src) )
			return;
		
		if ( isset( $src->collections[$what] ) ) {
			$collection_property = $src->collections[$what];
			
			if ( isset( $src->$collection_property ) ) {
				// If only one object type is defined, we have a winner.
				if ( $force_first || ( 1 == count( $src->$collection_property ) ) ) {
					reset( $src->$collection_property );
					return key( $src->$collection_property );
				}
			}
		} elseif ( ('type' == $what) && ( ! isset($src->object_types) || ( count($src->object_types) < 2 ) ) )
			return $src->name;
	}
	
	function get_from_func($what, $src) {
		if ( ! $src = $this->get($src) )
			return;
	
		if ( ! isset( $src->collections[$what] ) )
			return;
			
		$collection_property = $src->collections[$what];
		if ( isset( $src->$collection_property ) )
			foreach ( $src->$collection_property as $it => $prop )
				if ( isset( $prop->function ) )
					if ( call_user_func($prop->function) )
						return $it;	
	}
	
	function get_from_db($what, $src, $object_id) {
		if ( ! method_exists($this, 'get_object') )
			return;
	
		if ( ! $src = $this->get($src) )
			return;
	
		if ( ! isset($src->cols->$what) )
			return;
			
		$col = $src->cols->$what;
		
		if ( $object = $this->get_object($src->name, $object_id, $col) ) {
			if ( is_object( $object ) ) {
				if ( isset( $object->$col ) )
					return $object->$col;
			} else
				return $object;
		}
	}
	
	function get_from_http($what, $src) {
		if ( $val = $this->get_from_http_post($what, $src) )
			return $val;
	
		if ( $val = $this->get_from_urivars($what, $src) )
			return $val;
	}
	
	// determines, using cfg->data_sources config, the http POST variable for desired information, then returns its value if present
	function get_from_http_post($what, $src, $object_type = '') {
		if ( empty($_POST) ) {
			if ( defined( 'XMLRPC_REQUEST' ) ) {
				if ( ! empty($GLOBALS['scoper_xmlrpc_post_status'] ) ) {
					return $GLOBALS['scoper_xmlrpc_post_status'];
				} 
				else {
					global $wp_xmlrpc_server;
					
					if ( ! empty( $wp_xmlrpc_server->message->params) ) {
						if ( 'metaWeblog.newPost' == $wp_xmlrpc_server->message->methodName ) {	 // TODO: move to function
							return ( ! empty( $wp_xmlrpc_server->message->params[4] ) ) ? 'publish' : 'draft';
						}
					}
				}
			} 

			return;
		}
		
		if ( ! $src = $this->get($src) )
			return;
			
		/*
		rs_errlog('');
		rs_errlog("get $what from_http_post");
		rs_errlog( serialize($_POST) );
		*/
		
		$varname = $this->get_varname('http_post', $what, $src, $object_type);
			
		//rs_errlog('varname: '. $varname);
		
		if ( isset($_POST[$varname]) ) {
			return $_POST[$varname];
		} else {
			if ( isset($src->http_post_vars_alt->$what) ) {
				$vars_alt = (array) $src->http_post_vars_alt->$what;
				foreach ( $vars_alt as $varname_alt ) {
					if ( isset($_POST[$varname_alt]) ) {
						return $_POST[$varname_alt];
					}
				}
			}
		}
	}
	
	// determines, using cfg->data_sources config, the URI query variable for desired information, then returns its value if present
	function get_from_uri($what, $src, $object_type = '') {
		// workaround for WP 3.0, which uses post.php, edit.php, post-new.php for all post types
		if ( ( 'type' == $what ) && ( 'post' == $src->name ) )
			return cr_find_post_type();

		if ( ! $src = $this->get($src) )
			return;
		
		/*
		rs_errlog('');
		rs_errlog("get $what from_uri");
		rs_errlog( 'URI: '. $_SERVER['REQUEST_URI'] );
		*/
	
		// Try to pull the desired value from URI variables, 
		// using data_sources definition to convert the abstract $what into URI variable name 
		$varname = $this->get_varname('uri', $what, $src, $object_type);

		//rs_errlog('varname: '. $varname);

		if ( isset($_GET[$varname]) ) {
			return $_GET[$varname];
		} else {
			if ( isset($src->uri_vars_alt->$what) ) {
				$vars_alt = (array) $src->uri_vars_alt->$what;
				foreach ( $vars_alt as $varname_alt ) {
					if ( isset($_GET[$varname_alt]) ) {
						return $_GET[$varname_alt];
					}
				}
			}
		}
	}
	
	function get_from_query($what, $src, $query) {
		if ( ! $query ) 
			return;
	
		if ( ! $src = $this->get($src) )
			return;
		
		if ( empty($src->cols->$what) )
			return;
	
		$col = $src->cols->$what;
			
		// force standard query padding
		$query = preg_replace("/$col\s*=\s*'/", "$col = '", $query);
		
		$found = array();
		$search = "$col = '";
		$pos = -1;
		do {
			$pos = strpos($query, $search, $pos + 1);
			if ( false !== $pos ) {
				$startpos = strpos($query, "'", $pos + strlen($search) );
				$val = substr($query, $pos + strlen($search), $startpos - $pos - strlen($search) );
				$found[$val] = 1;
			}
		} while ( false !== $pos );
		
		if ( ! $found || ( count($found) > 1 ) )
			return;	// query contains zero or multiple equality clauses for requested variable (currently not considering IN clauses)
		
		return $val;
	}

	function get_varname( $var_type, $what_for, $src, $object_type = '') {
		if ( ! $src = $this->get($src) )
			return;
		
		$vars = "{$var_type}_vars";
		
		// return otype-specific variable, if defined
		if ( ! empty($object_type) )
			if ( isset($src->object_types[$object_type]->$vars->{$what_for}[CURRENT_ACCESS_NAME_RS] ) ) 
				return $src->object_types[$object_type]->$vars->{$what_for}[CURRENT_ACCESS_NAME_RS];

		if ( isset($src->$vars) && is_object($src->$vars) && isset($src->$vars->$what_for) )
			return $src->$vars->$what_for;
		elseif ( isset($src->cols->$what_for) )
			return $src->cols->$what_for;
		else
			return $what_for;
	}
}


// Note: These classes are currently for API support only;
// Internal usage (below) mirrors this interface but instantiates via stdObject cast from array
class CR_Data_Source extends AGP_Config_Item {
	var $table_basename; // REQUIRED:  database table name, without wp prefix
	var $table; 		 			// generated from table_basename
	var $table_alias = '';			// database table alias, if any, for use in queries
	
	//var $display_name;	 		// use labels->singular_name instead
	//var $display_name_plural = '';// use labels->name instead
	
	var $cols;						// database column names.   required keys: id, name, content  
									//							optional keys: type, owner, parent, status, excerpt
	
	var $http_post_vars = array();	// http_post_vars[agp_key] = POST variable name for data indicated by agp_key, if it differs from cols[agp_key]
	var $uri_vars = array();		// uri_vars[agp_key] = URI variable name for data indicated by agp_key, if it differs from cols[agp_key]
	var $uri_vars_alt;				// (object) array( 'id' => array('post_id') )
	var $http_post_vars_alt;		// (object) array( 'id' => array('post_id') )

	var $object_types = array();	// array[obj type name] = various optional properties for object types included in this data source 
									//	(if not set, default to single object type with same name as source)  
									// 	 valid props: 
									//	object_types[obj type name]->uri_vars = array of otype-specific uri query variable names for source id : array( 'id' => array( 'front' => 'page_id' ) )	
									//							   ->uri = array of uri substrings which indicate this object type

	var $statuses = array();		// statuses = array[access_name] = array( status_name => status_type_val )
									//	(indicates statuses valid for display in the specified access type, and the values representing them in DB record and POST vars)

	var $collections = array();		// collections[ agp_key ] = property name, indicating array property
									// ( i.e. collections['type'] = 'object_types' relates cols->type to object_types[object type name] )
														
	var $is_taxonomy = 0;			// This data source stores taxonomy terms (may be WP core "taxonomy" or other)
	var $taxonomy_only = 0;			// This data source is significant only as a taxonomy for other data sources
	var $uses_taxonomies = array();
	
	var $query_hooks;				// (object) array( 'request' => 'posts_request', 'results' => 'posts_results', 'listing' => 'the_posts' ),
	var $query_replacements = array();

	var $no_object_roles = 0;
	var $edit_url = '';				// URL to object editor, includes [id] placeholder
	
	function __construct( $name, $defining_module, $label_singular, $label_name, $table_basename, $col_id, $col_name, $args = array() ) {
		$this->cols = (object) array( 'id' => $col_id, 'name' => $col_name );
	
		parent::__construct($name, $defining_module, $args);	
	
		$this->labels->name = $label_name;
		$this->labels->singular_name = $label_singular;

		$this->table_basename = $table_basename;
	}
}

?>