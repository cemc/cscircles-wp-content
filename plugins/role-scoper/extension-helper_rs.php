<?php
function scoper_adjust_legacy_extension_cfg( &$role_defs, $cap_defs ) {
	global $scoper;

	if ( $src = $scoper->data_sources->get('ngg_gallery') ) {
		// TODO: merge this with CR_Data_Sources::process()
		$scoper->data_sources->members['ngg_gallery']->object_types = array($src->name => (object) array( 'name' => $src->name ) );
		
		if ( is_admin() ) {
			$scoper->data_sources->members['ngg_gallery']->object_types[$src->name]->labels->singular_name = $src->labels->singular_name;
			$scoper->data_sources->members['ngg_gallery']->object_types[$src->name]->labels->name = $src->labels->name;
		}

		if ( isset( $scoper->taxonomies->members['ngg_album'] ) ) {
			unset( $scoper->taxonomies->members['ngg_album']->object_source );		
			$scoper->taxonomies->members['ngg_album']->object_source = 'ngg_gallery';
		}
	}
			
	foreach( $role_defs->members as $role_handle => $role_def ) {
		if ( 'rs' == $role_def->role_type ) {
			if ( empty($role_def->object_type) ) {
				foreach( array_keys($role_defs->role_caps[$role_handle]) as $cap_name ) {
					if ( isset( $cap_defs->members[$cap_name]->object_type ) )
						$role_defs->members[$role_handle]->object_type = $cap_defs->members[$cap_name]->object_type;

					elseif ( isset( $cap_defs->members[$cap_name]->object_types[0] ) )
						$role_defs->members[$role_handle]->object_type = $cap_defs->members[$cap_name]->object_types[0];
					else
						break;
						
					$role_defs->members[$role_handle]->src_name = $cap_defs->members[$cap_name]->src_name;	
					break;	
				} 
			}	
		}
	}
}
	
?>