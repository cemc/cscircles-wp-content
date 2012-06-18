<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
require_once( dirname(__FILE__).'/agapetry_lib.php');

class AGP_Config_Items {
	var $members = array();		// collection array used by each base class	
	var $locked = 0;			// used to prevent inappropriate calls to the add method
	
	function AGP_Config_Items( $arr = '', $action_hook = '' ) {
		$this->init( $arr, $action_hook );
	}

	function init( $arr = '', $action_hook = '' ) {
		if ( $arr ) {
			$this->members = $arr;
			$this->process_added_members($arr);
		}

		//if ( $action_hook ) {
			//do_action_ref_array( $action_hook, array(&$this) );	
		//}
	}

	function &add( $name, $defining_module, $args = array() ) {
		if ( ! empty($this->locked) ) {
			$notice = sprintf(__('%1$s attempted to define a configuration item (%2$s) after the collection was locked.'), $defining_module, $name)
			. '<br /><br />' . sprintf(__('The calling function probably needs to be registered to a hook.  Consult %s developer documentation.', 'scoper'), $defining_module);
			rs_notice($notice);
			return;
		}
		
		// Restrict characters in member key / object name.  A display_name property is available where applicable.
		$name = preg_replace( '/[^0-9_a-zA-Z]/', '_', $name );
	
		if ( ! isset($this->members[$name]) )
			$this->members[$name] = new AGP_Config_Item($name, $defining_module, $args);
		
		return $this->members[$name];
	}

	function remove($name) {
		if ( isset($this->members[$name]) )
			unset ($this->members[$name]);
	}
	
	// accepts array of objects - either an instance the class collected by calling child class, or stdObject objects with matching properties 
	function add_member_objects($arr) {
		if ( ! is_array($arr) )
			return;
			
		if ( ! empty($this->locked) ) {
			rs_notice('Config items cannot not be added at this time.  Maybe the calling function must be registered to a hook.  Consult developer documentation.');
			return;
		}

		$this->members = array_merge($this->members, $arr);
		
		$this->process_added_members($arr);
	}
	
	function process( &$src ) {		
		return;
	}

	function process_added_members(&$arr) {
		$will_process = method_exists($this, 'process');
		
		foreach ( array_keys($arr) as $name ) {
			// copy key into name property
			if ( empty($this->members[$name]->name) )
				$this->members[$name]->name = $name;

			if ( $will_process )
				$this->process( $this->members[$name] );
		}
	}
	
	// accepts object or name as argument, returns valid object or null
	function get($obj_or_name) {
		if ( is_object($obj_or_name) )
			return $obj_or_name;
		
		//if ( ! is_string($obj_or_name) )
		//	agp_bt_die();
			
		// $obj_or_name must actually be the object name
		if ( isset($this->members[$obj_or_name]) )
			return $this->members[$obj_or_name];
	}
	
	// accepts object or name as argument, returns valid object or null
	function &get_ref($obj_or_name) {
		if ( is_object($obj_or_name) )
			return $obj_or_name;
		
		// $obj_or_name must actually be the object name
		if ( isset($this->members[$obj_or_name]) )
			return $this->members[$obj_or_name];
	}
	
	function get_all() {
		return $this->members;
	}
	
	function get_all_keys() {
		return array_keys($this->members);
	}
	
	// $subset_keys: array of keys corresponding to member objects
	function filter_keys( $subset_keys = '', $args = array(), $output = 'keys', $operator = 'and' ) {
		if ( -1 === $subset_keys )
			$subset_keys = array_keys($this->members);

		$filtered = array();
		
		$count = count($args);

		if ( ! $subset_keys )
			$subset_keys = array_keys($this->members);

		foreach ( $subset_keys as $key ) {
			$matched = 0;
			foreach( $args as $check_property => $check_val ) {
				if ( isset($this->members[$key]->$check_property) && ( $this->members[$key]->$check_property == $check_val ) )
					$matched++;
			}
			
			if ( ( ( 'and' == $operator ) && ( $matched == $count ) ) || ( ( 'or' == $operator ) && $matched ) ) {
				if ( ( 'keys' == $output ) || ( 'names' == $output ) )
					$filtered[] = $key;
				elseif ( 'names_as_key' == $output )
					$filtered[$key] = 1;
				elseif ( $output && ( 'objects' != $output ) )
					$filtered[] = $this->members[$key]->$output;
				else
					$filtered[$key] = $this->members[$key];
			}
		}

		return $filtered;
	}
	
	// $subset: array of objects which are a subset of members array
	function filter( $subset = '', $args = array(), $output = 'objects', $operator = 'and' ) {
		$subset_keys = ( ! empty( $subset ) ) ? array_keys( $subset ) : array();

		return $this->filter_keys( $subset_keys, $args, $output, $operator );
	}
	
	function is_member($name) {
		return isset($this->members[$name]);
	}
	
	// Potential use of alias property in RS Data Source definition to indicate where 
	// a 3rd party plugin uses a taxonomy->object_type property different from the src_name we define
	function is_member_alias($alias) {
		foreach ( array_keys($this->members) as $name )
			if ( isset($this->members[$name]->alias) && ( $alias == $this->members[$name]->alias ) )
				return $name;
	}
	
	function member_property() { // $name, $property, $key1 = '', $key2 = '', $key3 = '' ...
		$args = func_get_args();
		
		if ( ! is_string($args[0]) ) {
			// todo: confirm this isn't needed anymore
			return;
		}
		
		if ( ! isset( $this->members[$args[0]] ) )
			return;
			
		if ( ! isset( $this->members[$args[0]]->$args[1] ) )
			return;
			
		$val = $this->members[$args[0]]->$args[1];
		
		// if additional args were passed in, treat them as array or object keys
		for ( $i = 2; $i < count($args); $i++ ) {
			if ( is_array($val) ) {
				if ( isset($val[ $args[$i] ]) )
					$val = $val[ $args[$i] ];
				else
					return;
			
			} elseif ( is_object($val) ) {
				if ( isset($val->$args[$i]) )
					$val = $val->$args[$i];
				else
					return;
			}
		}
		
		return $val;
	}

	function remove_members_by_key($disabled, $require_value = false) {
		if ( ! is_array($disabled) )
			return;
		
		if ( $require_value ) { 
			foreach ( array_keys($disabled) as $key )
				if ( ! $disabled[$key] )
					unset($disabled[$key]);
			
			if ( ! $disabled )
				return;
		}
					
		$this->members = array_diff_key($this->members, $disabled);
	}
	
	function remove_members($disabled) {
		$this->members = array_diff_key($this->members, array_flip($disabled) );
	}
		
	function lock() {
		$this->locked = true;
	}
}

class AGP_Config_Item {
	var $name;
	var $defining_module;
	var $labels;
	
	function AGP_Config_Item ( $name, $defining_module, $args = array() ) {
		$this->name = $name;
		$this->defining_module = $defining_module;
		
		$this->labels = (object) array( 'name' => '', 'singular_name' => '' );

		if ( is_array($args) )
			foreach($args as $key => $val)
				$this->$key = $val;
	}
}
?>