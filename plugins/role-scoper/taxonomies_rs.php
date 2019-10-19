<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/lib/agapetry_config_items.php');

class CR_Taxonomies extends AGP_Config_Items {
	// creates related data source and taxonomy objects
	function &add_item( $name, $defining_module, $label_singular, $label_name, $uses_standard_schema = true, $default_strict = true, $args = array() ) {	
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a taxonomy (%2$s).', $defining_module, $name)
					. '<br /><br />' . 'This must be done via the define_taxonomies_rs hook.';
			rs_notice($notice);
			return;
		}
		
		if ( isset($this->members[$name]) )
			unset($this->members[$name]);
		
		$this->members[$name] = new CR_Taxonomy($name, $defining_module, $label_singular, $label_name, $uses_standard_schema, $default_strict, $args);

		$this->process( $this->members[$name] );
		return $this->members[$name];
	}
	
	// $tx = reference to CR_Taxonomy object (must pass object so we can call base class function statically)
	function process( &$tx ) {
		global $wpdb;
		
		$taxonomy = $tx->name;	
		
		// Apply default / derived properties to Taxonomy definitions
		if ( $tx->uses_standard_schema ) {
			$tx->source = 'term';
			
			// default WP schema properties	
			if ( ! isset( $tx->cols ) )
				$tx->cols = (object) array();
			
			$tx->cols->count = 'count';
			
			$tx->table_term2obj_basename = 'term_relationships';
			$tx->table_term2obj_alias = 'tr';
			$tx->cols->term2obj_oid = 'object_id';
			$tx->cols->term2obj_tid = 'term_taxonomy_id';
			
			if ( empty($tx->edit_url) )
				$tx->edit_url = "edit-tags.php?action=edit&taxonomy={$tx->name}&tag_ID=%d";
			
			if ( empty($tx->uri_vars) )
				$tx->uri_vars = (object) array( 'id' => 'tag_ID' );
			
			if ( empty($tx->http_post_vars) )
				$tx->http_post_vars = (object) array( 'id' => 'tag_ID', 'parent' => 'parent' );	
		}

		// term2obj table: add prefix
		$pfx = ( empty($tx->table_term2obj_noprefix) ) ? $wpdb->prefix : '';
		$tx->table_term2obj = $pfx . $tx->table_term2obj_basename;
		
		// term2obj taxonomy table: if no alias, set alias property to tablename
		if ( empty($tx->table_term2obj_alias) )
			$tx->table_term2obj_alias = $tx->table_term2obj;
			
		// if requires_term property was not explicitly set, default to true for hierarchical terms with default terms enabled and stored
		if ( ! isset( $tx->requires_term ) ) {
			if ( $tx->hierarchical && ! empty($tx->default_term_option) && get_option($tx->default_term_option) )   // Author term roles don't work right if requires_term is set false
				$tx->requires_term = true;
			else
				$tx->requires_term = false;
		}
	}
	
	// standard taxonomy query variables using WP taxonomy schema with objects filtering or term id filtering
	function standard_query_vars($terms_only = false) {
		global $wpdb;
		$arr = array();
		
		if ( $terms_only ) {
			$tmp = array();
			$tmp['table'] = $wpdb->term_taxonomy;
			$tmp['alias'] = 'tt';
			$tmp['as'] = 'AS tt';
			$tmp['col_id'] = 'term_taxonomy_id';
			$arr['term'] = (object) $tmp;
		} else {
			$tmp = array();
			$tmp['table'] = $wpdb->term_relationships;
			$tmp['alias'] = 'tr';
			$tmp['as'] = 'AS tr';
			$tmp['col_id'] = 'term_taxonomy_id';
			$tmp['col_obj_id'] = 'object_id';
			$arr['term'] = (object) $tmp;
			
			$tmp = array();
			$tmp['table'] = $wpdb->posts;
			$tmp['alias'] = $tmp['table'];
			$tmp['as'] = '';
			$tmp['col_id'] = 'ID'; // posts ID column
			$arr['obj'] = (object) $tmp;
		}
		
		return (object) $arr;
	}
	
	// standard get_terms query using WP taxonomy schema
	function standard_query( $taxonomy, $cols, $object_id, $terms_only ) {
		global $wpdb;
		
		$join = $orderby = '';
		
		if ( $object_id || ! $terms_only ) {
			$join = " INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id"; 
			if ( $object_id )
				$join .= " AND tr.object_id = '$object_id'";
		}

		switch ( $cols ) {
			case COL_ID_RS:
				$qcols = 'tt.term_id';
				break;
			case COL_TAXONOMY_ID_RS:
				$qcols = 'tt.term_taxonomy_id';
				break;
			case COL_COUNT_RS:
				$qcols = 'COUNT(tt.term_id)';
				break;
			default: // COLS_ALL
				$qcols = 't.*, tt.*';
				
				$orderby = 'ORDER BY t.name';
				$join .= " INNER JOIN $wpdb->terms AS t ON t.term_id = tt.term_id";
		}

		$distinct = ( $join ) ? 'DISTINCT ' : '';

		return "SELECT {$distinct}$qcols FROM $wpdb->term_taxonomy AS tt $join WHERE 1=1 AND tt.taxonomy = '$taxonomy' $orderby";
	}
	
	// taxonomy query variables for use with objects filtering or term id filtering
	function get_terms_query_vars($tx, $terms_only = false) {
		if ( ! is_object($tx) ) {
			if ( ! $tx = $this->get($tx) )
				return array();
		}
		
		$arr = array();

		if ( ! empty($tx->uses_standard_schema) )
			return $this->standard_query_vars($terms_only);

		require_once( dirname(__FILE__).'/taxonomies-custom_rs.php');
		return ScoperCustomTaxonomyHelper::get_terms_query_vars($tx, $terms_only);
	}
	
	// called by Scoper::get_terms
	function get_terms_query($taxonomy, $cols = COLS_ALL_RS, $object_id = 0, $terms_only = true) {
		if ( ! isset($this->members[$taxonomy]) )
			return;

		$tx = $this->members[$taxonomy];

		if ( ! empty($tx->uses_standard_schema) )
			return $this->standard_query($taxonomy, $cols, $object_id, $terms_only);  //this is a required child method
		
		require_once( dirname(__FILE__).'/taxonomies-custom_rs.php');
		return ScoperCustomTaxonomyHelper::get_terms_query($tx, $cols, $object_id, $terms_only);
	}
}

class CR_Taxonomy extends AGP_Config_Item {
	//var $display_name;	 	// use labels->singular_name instead
	//var $display_name_plural; // use labels->name instead
	var $labels;
	var $source;						// object reference to a CR_Data_Source (REQUIRED but auto-set for WP taxonomies)
	var $object_source;					// auto-generated upon ScoperConfig::load_config
	
	var $requires_term = 0;				// must every object type using this category relate every object to at least one term?
	var $uses_standard_schema = 0;		// child classes may set this true if corresponding _Taxonomies class has standard_query_vars, standard_query methods
	
	var $cols = array();
	
	// Term to Object schema properties ( i.e. post2cat for WP < 2.3, term_relationships for WP > 2.3 )
	var $table_term2obj_basename = '';	// table basename (without prefix) for table relating terms to objects (and taxonomies, if multiple taxonomies are sharing the same DB schema)
	var $table_term2obj_noprefix = 0;
	var $table_term2obj;				// auto-generated upon ScoperConfig::load_config
	var $table_term2obj_alias = '';
	
	function __construct($name, $defining_module, $label_singular, $label_name, $uses_standard_schema = true, $requires_term = false, $args = array() ) {
		parent::__construct( $name, $defining_module, $args );
		
		$this->labels->name = $label_name;
		$this->labels->singular_name = $label_singular;

		$this->uses_standard_schema = $uses_standard_schema;
		$this->requires_term = $requires_term;
	}
}

?>