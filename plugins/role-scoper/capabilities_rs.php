<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/lib/agapetry_config_items.php');

class CR_Capabilities extends AGP_Config_Items {
	// defining_module could be a plugin name, theme name, etc.
	// args: status, base_cap, anon_user_has, is_taxonomy_cap
	function &add($name, $defining_module, $src_name, $object_type, $op_type, $args = array()) {
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a capability (%2$s).', $defining_module, $name)
					. '<br /><br />' . 'This must be done via the define_capabilities_rs hook.';
			rs_notice($notice);
			return;
		}
	
		if ( isset($this->members[$name]) )
			unset($this->members[$name]);
		
		$this->members[$name] = new CR_Capability($name, $defining_module, $src_name, $object_type, $op_type, $args);
		$this->process($this->members[$name]);

		return $this->members[$name];
	}
		
	function process( &$cap_def ) {
		if ( ! isset($cap_def->status) )
			$cap_def->status = '';
	}

	// legacy API
	function object_types_from_caps($reqd_caps) {
		if ( ! is_array($reqd_caps) )
			$reqd_caps = ($reqd_caps) ? array($reqd_caps) : array();
	
		$object_types = array();
		foreach( $reqd_caps as $cap_name) {
			if ( isset($this->members[$cap_name]) ) {
				$cap_def = $this->members[$cap_name];
				
				if ( isset($cap_def->src_name) && isset($cap_def->object_type) )
					$object_types[$cap_def->src_name][$cap_def->object_type] = 1;
			}
		}
		
		return $object_types;
	}

	//returns array[src_name][object_type] = 1
	function src_otypes_from_caps( $reqd_caps, &$src_name ) {
		if ( ! is_array($reqd_caps) )
			$reqd_caps = ($reqd_caps) ? array($reqd_caps) : array();
	
		$object_types = array();

		foreach( $reqd_caps as $cap_name)
			if ( $cap_def = $this->get($cap_name) )
				if ( ! empty($cap_def->src_name) && ! empty($cap_def->object_types) ) {
					foreach( $cap_def->object_types as $type )
						$object_types[$cap_def->src_name] [] = $type;
				}

		// If multiple sources were identified from these caps, the reqd_caps set is at least partially invalid.
		// If post data source was among those identified, return its post types.  Otherwise, fail.
		if ( count( $object_types ) > 1 ) {
			if ( isset( $object_types['post'] ) )
				$object_types = array_intersect( $object_types, array( 'post' ) );
			else {
				$src_name = '';
				return array();	
			}
		}
		
		reset($object_types);
		$src_name = key($object_types);
				
		if ( isset(	$object_types[$src_name] ) )
			return $object_types[$src_name];
		else
			return array();
	}
	
	function get_cap_ops($caps, $src_name = '', $object_type = '') {
		$ops = array();
		
		foreach ( $caps as $cap_name )
			if ( ! empty($this->members[$cap_name]->op_type) )
				$ops[ $this->members[$cap_name]->op_type ] = 1;

		return $ops;
	}
	
	// returns caps array[op_type] = array of cap names
	function organize_caps_by_op($caps, $include_undefined_caps = false ) {
		$opcaps = array();
		
		foreach ($caps as $cap_name) {
			if ( isset($this->members[$cap_name]) ) {
				$op_type = ( isset( $this->members[$cap_name]->op_type ) ) ? $this->members[$cap_name]->op_type : '';
				$opcaps[$op_type] []= $cap_name;
		
			} elseif ( $include_undefined_caps )
				$opcaps[''] []= $cap_name;
		}
				
		return $opcaps;
	}
	
	// returns caps array[src_name][object_type] = array of cap names
	function organize_caps_by_otype( $caps, $include_undefined_caps = false, $required_src_name = '', $default_object_type = '' ) {
		$otype_caps = array();
		
		foreach ($caps as $cap_name) {
			if ( isset($this->members[$cap_name]) ) {
				$src_name = $this->members[$cap_name]->src_name;
				
				if ( $required_src_name && ( $src_name != $required_src_name ) ) {
					$src_name =  '';
					$otype_caps[''][''] []= $cap_name;
				} else {
					if ( ! empty($this->members[$cap_name]->object_types) ) {
						foreach( $this->members[$cap_name]->object_types as $_object_type ) {
							$otype_caps[$src_name][$_object_type] []= $cap_name;
						}
					} else {
						$otype_caps[$src_name][$default_object_type] []= $cap_name;
					}
				}
			} elseif ( $include_undefined_caps )
				$otype_caps[''][''] []= $cap_name;
		}
		
		// TODO: is this still necessary?
		// if an otype-indeterminate cap is present alongside otype-specific caps, combine them into the otype-specfic array(s) 
		foreach ( array_keys($otype_caps) as $src_name ) {
			if ( (count($otype_caps[$src_name]) > 1) && isset($otype_caps[$src_name]['']) ) {
				foreach ( array_keys($otype_caps[$src_name]) as $otype ) {
					if ( $otype )
						$otype_caps[$src_name][$otype] = array_merge($otype_caps[$src_name][$otype], $otype_caps[$src_name][''] );
				}
				unset ($otype_caps[$src_name]['']);
			}
		} 
		
		return $otype_caps;
	}
	
	// Remove "others" caps from array
	// If $substitute is set true, replace with corresponding base cap
	function get_base_caps($caps, $substitute = true ) {
		$caps = (array) $caps;

		foreach ( $caps as $key => $cap_name )
			if ( ! empty($this->members[$cap_name]->base_cap) ) {
				if ( $substitute )
					$caps[$key] = $this->members[$cap_name]->base_cap;
				else
					unset( $caps[$key] );
			}
				
		return array_unique($caps);
	}
	
	// Remove "others" caps from array
	function remove_owner_caps($caps) {
		return $this->get_base_caps($caps, false);
	}
	
	function get_matching($src_name, $object_types = '', $op_type = '', $status = '', $base_caps_only = false, $args = array() ) {
		$arr = array();
	
		$defaults = array( 'strict_op_match' => false, 'strict_otype_match' => false);
		$args = array_intersect_key( array_merge( $defaults, (array) $args ), $defaults );
		extract($args);
		
		$object_types = ( $object_types ) ? (array) $object_types : array();

		// disregard a status arg which is not present in any cap
		if ( $status && ( $status != STATUS_ANY_RS ) ) {
			$status_present = false;
			foreach ( $this->members as $cap_name => $capdef )
				if ( isset($capdef->status) && ( $capdef->status == $status ) ) {
					$status_present = true;
					break;
				}
			
			if ( ! $status_present )
				$status = '';
		}

		// first narrow to specified source name, object type, status and baseness
		foreach ( $this->members as $cap_name => $capdef ) {
			if ( ( isset($capdef->src_name) && $capdef->src_name == $src_name )
			&& ( empty($object_types) || ( isset($capdef->object_types) && array_intersect( $object_types, $capdef->object_types ) ) || ( empty($strict_otype_match) && empty($capdef->object_types) ) )
			&& ( (STATUS_ANY_RS == $status) || ( ! $status && empty($capdef->status) ) || ( isset($capdef->status) && ($status == $capdef->status) ) )
			&& ( ! $base_caps_only || empty($capdef->base_cap) ) 
			)
				$arr[$cap_name] = $capdef;
		}		
				
		// Narrow to specified op type.  
		// But if no cap of this op type is defined, sustitute a cap of higher op level (which also met the other criteria)
		if ( $arr && $op_type ) {
			$sustitute_ops = array( OP_EDIT_RS, OP_PUBLISH_RS, OP_DELETE_RS, OP_ADMIN_RS );
			if ( ! $op_level = array_search($op_type, $sustitute_ops) )
				$op_level = -1;
			
			$op_caps = array();
			do {
				if ( $op_level >= 0 )
					$op_type = $sustitute_ops[$op_level];
			
				foreach ( $arr as $cap_name => $capdef )
					if ( $capdef->op_type == $op_type )
						$op_caps[$cap_name] = $capdef;
				
				$op_level++;
			} while ( ! $op_caps && ($op_level < count($sustitute_ops) ) && empty($strict_op_match) );
			
			return $op_caps;	
		} else
			return $arr;
	}
}

class CR_Capability extends AGP_Config_Item {
	var $src_name;			// required
	var $object_types = array();	// array[object_type] = true : populated based on cap usage in RS roles
	var $op_type;			// required
	var $status = '';			
	var $base_cap;			// documentation not finished - see scoper_core_cap_defs()
	var $anon_user_has;		// 		''
	var $is_taxonomy_cap;	// 		''
	
	// args: status, base_cap, anon_user_has, is_taxonomy_cap 
	function CR_Capability($name, $defining_module, $src_name, $object_type = '', $op_type = '', $args) {
		$this->AGP_Config_Item($name, $defining_module, $args);
		
		$this->src_name = $src_name;
		
		if ( $object_type )
			$this->object_types[] = $object_type;

		$this->op_type = $op_type;
	}
}
?>