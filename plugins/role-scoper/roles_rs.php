<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( dirname(__FILE__).'/lib/agapetry_config_items.php');

class CR_Roles extends AGP_Config_Items {
	var $role_caps = array();
	var $role_types = array();
	var $display_names = array();	// display_names, abbrevs necessary for WP roles (supported for use by RS Extensions, but default role defs call ScoperRoleStrings functions instead
	var $abbrevs = array();
	var $micro_abbrevs = array();
	
	function __construct( $arr = '', $action_hook = '') {
		$this->role_types = array( 'rs', 'wp' );
		parent::__construct( $arr, $action_hook );
	}

	function get_display_name( $role_handle, $context = '' ) {
		if ( isset( $this->display_names[$role_handle] ) )
			return $this->display_names[$role_handle];
		
		require_once( dirname(__FILE__).'/roles-strings_rs.php' );
		return ScoperRoleStrings::get_display_name( $role_handle, $context );
	}
	
	function get_abbrev( $role_handle, $context = '' ) {
		if ( isset( $this->abbrevs[$role_handle] ) )
			return $this->abbrevs[$role_handle];
		
		require_once( dirname(__FILE__).'/roles-strings_rs.php' );
		return ScoperRoleStrings::get_abbrev( $role_handle, $context );
	}
	
	function get_micro_abbrev( $role_handle, $context = '' ) {
		if ( isset( $this->micro_abbrevs[$role_handle] ) )
			return $this->micro_abbrevs[$role_handle];

		require_once( dirname(__FILE__).'/roles-strings_rs.php' );
		
		if( ! $return = ScoperRoleStrings::get_micro_abbrev( $role_handle, $context ) )
			$return = ScoperRoleStrings::get_abbrev( $role_handle, $context );
		
		return $return;
	}
	
	function &add_item( $name, $defining_module, $display_name = '', $abbrev = '', $role_type = 'rs', $args = array()) {
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a role (%2$s).', $defining_module, $name)
					. '<br /><br />' . 'This must be done via the define_roles_rs hook.';
			rs_notice($notice);
			return;
		}
		
		$key = ( $name == ANON_ROLEHANDLE_RS ) ? $name : scoper_get_role_handle($name, $role_type);
		
		if ( 'wp' == $role_type ) {
			if ( ! $display_name )
				$display_name = ucwords( str_replace('_', ' ', $name) );
								
			if ( ! $abbrev )
				$abbrev = $display_name;
		}
			
		if ( $display_name )
			$this->display_names[$key] = $display_name;
			
		if ( $abbrev )
			$this->abbrevs[$key] = $abbrev;

		if ( isset($this->members[$key]) )
			unset($this->members[$key]);
			
		$this->members[$key] = new CR_Role($name, $defining_module, $role_type, $args);
		$this->process($this->members[$key]);
		
		return $this->members[$key];
	}

	function process( &$role_def ) {
		// role type was prefixed for array key, but should remove for name property
		foreach ( $this->role_types as $role_type ) {
			$role_def->name = str_replace("{$role_type}_", '', $role_def->name);
		}
			
		if ( ! isset($role_def->valid_scopes) )
			$role_def->valid_scopes = array('blog' => 1, 'term' => 1, 'object' => 1);
			
		if ( ! isset($role_def->src_name) )
			$role_def->src_name = '';
			
		if ( ! isset($role_def->object_type) )
			$role_def->object_type = ( 'rs' == $role_def->role_type ) ? $role_def->src_name : '';
	}
	
	function add_role_caps( $user_role_caps ) {
		if ( ! is_array( $user_role_caps ) )
			return;
			
		foreach( array_keys( $user_role_caps ) as $role_handle ) {
			if ( $user_role_caps[$role_handle] ) {
				if( isset( $this->role_caps[$role_handle] ) )
					$this->role_caps[$role_handle] = array_merge($this->role_caps[$role_handle], $user_role_caps[$role_handle]);
				else
					$this->role_caps[$role_handle] = $user_role_caps[$role_handle];
			}
		}
	}
	
	function remove_role_caps( $disabled_role_caps ) {
		if ( ! is_array( $disabled_role_caps ) )
			return;
		
		foreach ( array_keys($this->role_caps) as $role_handle )
			if ( ! empty($disabled_role_caps[$role_handle]) )
				$this->role_caps[$role_handle] = array_diff_key($this->role_caps[$role_handle], $disabled_role_caps[$role_handle]);
	}
	
	function get_for_taxonomy($src, $taxonomy = '', $args = array()) {
		$defaults = array( 'one_otype_per_role' => true, 'ignore_usage_settings' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
	
		if ( ! is_object($src) )
			$src = $GLOBALS['scoper']->data_sources->get($src);
		
		if ( ! $src)
			return;
	
		$otype_roles = array();
		
		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ) ) && $one_otype_per_role ) {
			if ( $tx = get_taxonomy( $taxonomy ) )
				if ( ! empty( $tx->object_type ) )
					$use_otypes = array_unique( (array) $tx->object_type );
		}

		if ( empty( $use_otypes ) )
			$use_otypes = array_keys($src->object_types);

		foreach ( $use_otypes as $object_type ) {
			$use_term_roles = scoper_get_otype_option('use_term_roles', $src->name, $object_type);
			
			if ( ! $ignore_usage_settings && empty($use_term_roles[$taxonomy]) )
				continue;

			if ( $roles = $this->get_matching( 'rs', $src->name, $object_type ) ) {
				if ( $one_otype_per_role )
					foreach ( array_keys($otype_roles) as $existing_object_type )
						$roles = array_diff_key($roles, $otype_roles[$existing_object_type]);
				
				$otype_roles[$object_type] = $roles;
			}
		}
		
		//note: term roles are defined with src_name property corresponding to their object source (i.e. manage_categories has src_name 'post')
		if ( $taxonomy ) {
			if ( $roles = $this->get_matching( 'rs', $src->name, $taxonomy ) ) {
				if ( $one_otype_per_role )
					foreach ( array_keys($otype_roles) as $object_type )
						$roles = array_diff_key($roles, $otype_roles[$object_type]);
				
				if ( $roles )
					$otype_roles[$taxonomy] = $roles;	
			}
		}

		return $otype_roles;
	}

	function add_containing_roles($roles, $role_type = '') {
		$return_roles = $roles;
	
		foreach ( array_keys($roles) as $role_handle )
			if ( $containing = $this->get_containing_roles($role_handle, $role_type) )
				$return_roles = array_merge($return_roles, $containing);
				
		return $return_roles;
	}
	
	// returns array with role handles as keys
	function get_containing_roles($role_handle, $role_type = '') {
		if ( ! isset($this->role_caps[$role_handle]) || ! is_array($this->role_caps[$role_handle]) )
			return array();

		$containing_roles = array();
		foreach ( array_keys($this->role_caps) as $other_role_handle )
			if ( $other_role_handle != $role_handle )
				if ( ! array_diff_key($this->role_caps[$role_handle], $this->role_caps[$other_role_handle]) )
					$containing_roles[$other_role_handle] = 1;
			
		if ( $containing_roles && $role_type )
			$containing_roles = $this->filter_keys( array_keys($containing_roles), array( 'role_type' => $role_type ), 'names_as_key' );

		return $containing_roles;
	}
	
	// returns array with role handles as key
	function get_contained_roles($role_handles, $include_this_role = false, $role_type = '') {
		if ( ! $role_handles )
			return array();
			
		$role_handles = (array) $role_handles;

		$contained_roles = array();

		foreach ( $role_handles as $role_handle ) {
			if ( ! isset($this->role_caps[$role_handle]) )
				continue;

			$role_attributes = $this->get_role_attributes( $role_handle );
				
			foreach ( array_keys($this->role_caps) as $other_role_handle ) {
				if ( ( ($other_role_handle != $role_handle) || $include_this_role ) ) {					
					if ( $this->role_caps[$other_role_handle] ) { // don't take credit for including roles that have no pertinent caps
						if ( ! array_diff_key($this->role_caps[$other_role_handle], $this->role_caps[$role_handle]) ) {
							// role caps qualify, but only count RS roles of matching object type
							if ( 'rs' == $role_attributes->role_type ) {
								if ( $role_attributes->object_type != $this->member_property( $other_role_handle, 'object_type' ) )
									continue;
							}
							
							$contained_roles[$other_role_handle] = 1;
						}
					}
				}
			}
		}
		
		if ( $role_type && $contained_roles )
			$contained_roles = $this->filter_keys( array_keys($contained_roles), array( 'role_type' => $role_type ), 'names_as_key' );

		if ( $contained_roles && ! $include_this_role )
			$contained_roles = array_diff_key( $contained_roles, array_flip($role_handles) );

		return $contained_roles;
	}

	// returns array of role objects
	function add_contained_roles($assigned) {
		if ( empty($assigned) )
			return array();
	
		$assigned = (array) $assigned;

		$roles = $assigned;
		foreach ( array_keys($assigned) as $assigned_role_handle ) {
			if ( $contained_roles = $this->get_contained_roles($assigned_role_handle) )
				$roles = array_merge( $roles, $contained_roles );
		}
		
		return $roles;
	}
	
	// returns array[role_handle] = array of term ids
	function add_contained_term_roles($assigned) {
		if ( empty($assigned) )
			return array();
	
		$assigned = (array) $assigned;

		$role_terms = $assigned;

		// $assigned[role_key] = array of terms for which the role is assigned.
		// Add contained roles directly into the provided assigned_roles array
		foreach ( $assigned as $assigned_role_handle => $terms ) {
			
			// if a user has role assigned for term(s), he also effectively has all its contained roles assigned for same term(s)  	
			foreach ( array_keys( $this->get_contained_roles($assigned_role_handle, true) ) as $contained_role_handle ) {
				
				// may or may not already have roles assigned explicitly or via containment in another assigned role
				if ( ! isset($role_terms[$contained_role_handle]) )
					$role_terms[$contained_role_handle] = $terms;
				else
					$role_terms[$contained_role_handle] = array_unique( array_merge($role_terms[$contained_role_handle], $terms) );
			}
		}
		
		return $role_terms;
	}
	
	// reqd_caps: array of cap names and/or role handles.  Role handle format is {$role_type}_{role_name}
	function role_handles_to_caps($reqd_caps, $find_unprefixed_wproles = false) {
		foreach ( $reqd_caps as $role_handle ) {
			if ( isset($this->role_caps[$role_handle]) ) {
				$reqd_caps = array_merge( $reqd_caps, array_keys($this->role_caps[$role_handle]) );	
				$reqd_caps = array_diff( $reqd_caps, array($role_handle) );
			}
		}
			
		if ( $find_unprefixed_wproles ) {
			global $wp_roles;
			foreach ( $reqd_caps as $role_name ) {
				if ( isset($wp_roles->role_objects[$role_name]->capabilities ) ) {
					$reqd_caps = array_merge( $reqd_caps, array_keys($wp_roles->role_objects[$role_name]->capabilities) );	
					$reqd_caps = array_diff( $reqd_caps, array($role_name) );
				}
			}
		}
				
		return array_unique($reqd_caps);
	}

	function get_role_attributes( $role_handle ) {
		$attribs = (object) array( 'role_type' => '', 'src_name' => '', 'object_type' => '' );
		
		if ( isset( $this->members[$role_handle] ) ) {
			$attribs->role_type = $this->members[$role_handle]->role_type;

			if ( 'rs' == $attribs->role_type ) {
				$attribs->src_name = $this->members[$role_handle]->src_name;
				$attribs->object_type = $this->members[$role_handle]->object_type;
			}
		}
		
		return $attribs;
	}
	
	// returns array of Role_Defs objects which match the specified parameters
	function get_matching($role_types = '', $src_names = '', $object_types = '' ) {
		if ( $role_handles = $this->qualify_roles( '', $role_types, $object_types, array( 'src_name' => $src_names ) ) )
			return array_intersect_key( $this->members, $role_handles );
		else
			return array();
	}
	
	// $reqd_caps = single cap name string OR array of cap name strings
	// returns array of role_handles
	function qualify_roles($reqd_caps, $role_type = 'rs', $object_type = '', $args = array()) {		
		$defaults = array( 'src_name' => '', 'all_wp_caps' => false );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		$good_roles = array();
		
		if ( $reqd_caps )
			$reqd_caps = $this->role_handles_to_caps( (array) $reqd_caps, true); // arg: also check for unprefixed WP rolenames
		
		if ( $role_type )
			$role_handles = $this->filter_keys( array_keys($this->members), array( 'role_type' => $role_type ) );
		else
			$role_handles = array_keys($this->members);

		foreach ( $role_handles as $role_handle ) {
			if ( $reqd_caps ) {
				if ( $all_wp_caps && ( 0 === strpos( $role_handle, 'wp_' ) ) ) {
					if ( empty( $GLOBALS['wp_roles']->role_objects[ substr($role_handle, 3) ]->capabilities ) 
					|| array_diff( $reqd_caps, array_keys( $GLOBALS['wp_roles']->role_objects[ substr($role_handle, 3) ]->capabilities ) ) )	// note: this does not observe "false" value in capabilities array
						continue;
				} else {
					if ( empty( $this->role_caps[$role_handle] ) || array_diff( $reqd_caps, array_keys( $this->role_caps[$role_handle] ) ) )
						continue;
				}
			} 

			// The required caps test passed or was not applied.  Now verify data source and/or object type, if specified...
			if ( 'rs' == $this->members[$role_handle]->role_type ) {
				// data source and object type matching only apply to RS roles, which always have a single data source and object type defined
				if ( $src_name && ! in_array( $this->members[$role_handle]->src_name, (array) $src_name ) )
					continue;
					
				// the role qualifies unless its object type is a mismatch
				if ( $object_type && ! in_array( $this->members[$role_handle]->object_type, (array) $object_type ) )
					continue;	
			}
			
			$good_roles[$role_handle] = 1;
		}

		return $good_roles;
	}
	
	
	// Currently, new custom-defined post, page or link roles are problematic because objects or categories with all roles restricted 
	// will suddenly be non-restricted to users whose WP role contains the newly defined RS role.
	//
	// TODO: make all custom-defined roles default restricted
	function remove_invalid() {
		return;
		
		/*
			if ( $custom_members = array_diff_key( $this->members, array_fill_keys( array( 'rs_post_reader', 'rs_private_post_reader', 'rs_post_contributor', 'rs_post_author', 'rs_post_revisor', 'rs_post_editor', 'rs_page_reader', 'rs_private_page_reader', 'rs_page_contributor', 'rs_page_author', 'rs_page_revisor', 'rs_page_editor', 'rs_page_associate', 'rs_link_editor', 'rs_category_manager', 'rs_group_manager' ), true ) ) ) {
				foreach ( $custom_members as $role_handle => $role_def ) {
					if ( ( 'post' == $role_def->src_name ) && in_array( $role_def->object_type, array( 'post', 'page' ) )
					|| ( ( 'link' == $role_def->src_name ) && ( 'link' == $role_def->object_type ) ) )
						unset( $this->members[$role_handle] );
				}
			}
		*/
	}
} // end class CR_Roles

class CR_Role extends AGP_Config_Item {
	var $role_type;
	var $src_name;
	var $object_type;
	var $valid_scopes;
	var $objscope_equivalents;
	
	function __construct($name, $defining_module, $role_type = 'rs', $args = array() ) {
		parent::__construct( $name, $defining_module, $args );
		
		$this->role_type = $role_type;
	}
}
?>