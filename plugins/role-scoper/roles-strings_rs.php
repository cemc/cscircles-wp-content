<?php

class ScoperRoleStrings {

	function get_display_name( $role_handle, $context = '' ) {
		switch( $role_handle ) {
			case 'rs_post_reader' :
				return __('Post Reader', 'scoper');
				
			case 'rs_private_post_reader' :
				// We want the object-assigned reading role to enable the user/group regardless of post status setting.
				// But we don't want the caption to imply that assigning this object role MAKES the post_status private
				// Also want the "role from other scope" indication in post edit UI to reflect the post's current status
				return ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) ? __('Post Reader', 'scoper') : __('Private Post Reader', 'scoper');
				
			case 'rs_post_contributor' :
				return __('Post Contributor', 'scoper');
				
			case 'rs_post_author' :
				return __('Post Author', 'scoper');
				
			case 'rs_post_revisor' :
				return __('Post Revisor', 'scoper');
				
			case 'rs_post_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					return __('Post Publisher', 'scoper');
				else
					return __('Post Editor', 'scoper');
				
			case 'rs_page_reader' :
				return __('Page Reader', 'scoper');
				
			case 'rs_private_page_reader' :
				return ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ? __('Page Reader', 'scoper') : __('Private Page Reader', 'scoper');
				
			case 'rs_page_associate' :
				return __('Page Associate', 'scoper');
				
			case 'rs_page_contributor' :
				return __('Page Contributor', 'scoper');
				
			case 'rs_page_author' :
				return __('Page Author', 'scoper');
				
			case 'rs_page_revisor' :
				return __('Page Revisor', 'scoper');
				
			case 'rs_page_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					return __('Page Publisher', 'scoper');
				else
					return __('Page Editor', 'scoper');
				
			case 'rs_link_editor' :
				return __('Link Editor', 'scoper');

			case 'rs_category_link_manager' :
				return __('Category Link Manager', 'scoper');

			case 'rs_category_manager' :
				return __('Category Manager', 'scoper');
				
			case 'rs_group_manager' :
				return __('Group Manager', 'scoper');
				
			case 'rs_group_moderator' :
				return __('Group Moderator', 'scoper');
				
			case 'rs_group_applicant' :
				return __('Group Applicant', 'scoper');
				
			default :				
				$custom_types = get_post_types( array( '_builtin' => false, 'public' => true ), 'object' );

				foreach( $custom_types as $custype ) {
					if ( strpos( $role_handle, "_{$custype->name}_" ) ) {
						$label = $custype->labels->singular_name;
						
						if ( ! $label )
							$label = $custype->name;
						
						if ( strpos( $role_handle, '_editor' ) )
							return ( defined( 'SCOPER_PUBLISHER_CAPTION' ) ) ? sprintf( __( '%s Publisher', 'scoper' ), $label ) : sprintf( __( '%s Editor', 'scoper' ), $label );
						elseif ( strpos( $role_handle, '_revisor' ) )
							return sprintf( __( '%s Revisor', 'scoper' ), $label );
						elseif ( strpos( $role_handle, '_author' ) )
							return sprintf( __( '%s Author', 'scoper' ), $label );
						elseif ( strpos( $role_handle, '_contributor' ) )
							return sprintf( __( '%s Contributor', 'scoper' ), $label );
						elseif ( false !== strpos( $role_handle, 'private_' ) && strpos( $role_handle, '_reader' ) )
							return ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) ? sprintf( __( '%s Reader', 'scoper' ), $label ) : sprintf( __( 'Private %s Reader', 'scoper' ), $label );
						elseif ( strpos( $role_handle, '_reader' ) )
							return sprintf( __( '%s Reader', 'scoper' ), $label );
					}
				}

				$taxonomies = get_taxonomies( array( '_builtin' => false, 'public' => true ), 'object' );
				
				foreach( $taxonomies as $name => $tx_obj ) {
					if ( strpos( $role_handle, "_{$name}_" ) ) {
						$label = $tx_obj->labels->singular_name;
						
						if ( ! $label )
							$label = $tx_obj->name;
					
						if ( strpos( $role_handle, '_manager' ) )
							return sprintf( __( '%s Manager', 'scoper' ), $label );
							
						if ( strpos( $role_handle, '_assigner' ) )
							return sprintf( __( '%s Assigner', 'scoper' ), $label );
					}
				}
				
				return ucwords( trim( str_replace( '_', ' ', substr($role_handle, 2 ) ) ) );
				
		} // end switch
		
		//return apply_filters( 'role_display_name_rs', $str, $role_handle );			
	}
	
	function get_abbrev( $role_handle, $context = '' ) {
		if ( strpos( $role_handle, '_reader' ) ) {
			if ( ( false === strpos( $role_handle, 'private_' ) ) || ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) )
				return __('Readers', 'scoper');
			else
				return __('Private Readers', 'scoper');

		} elseif ( strpos( $role_handle, '_contributor' ) )
			return __('Contributors', 'scoper');
			
		elseif ( strpos( $role_handle, '_author' ) )
			return __('Authors', 'scoper');
			
		elseif ( strpos( $role_handle, '_revisor' ) )
			return __('Revisors', 'scoper');
			
		elseif ( strpos( $role_handle, '_editor' ) ) {
			if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
				return __('Publishers', 'scoper');
			else
				return __('Editors', 'scoper');
				
		} elseif ( strpos( $role_handle, '_associate' ) )
			return __('Associates', 'scoper');
			
		elseif ( strpos( $role_handle, '_manager' ) )
			return __('Managers', 'scoper');

		if ( $post_type = $GLOBALS['scoper']->role_defs->member_property( $role_handle, 'object_type' ) )
			$role_handle = str_replace( "{$post_type}_", '', $role_handle ); 
	
		return ucwords( trim( str_replace( '_', ' ', substr($role_handle, 2 ) ) ) );
	}
	
	function get_micro_abbrev( $role_handle, $context = '' ) {
		switch( $role_handle ) {
			case 'rs_post_reader' :
			case 'rs_page_reader' :
				return __('Reader', 'scoper');
				
			case 'rs_private_post_reader' :
			case 'rs_private_page_reader' :
				return ( ( OBJECT_UI_RS == $context ) && ! defined( 'DISABLE_OBJSCOPE_EQUIV_' . $role_handle ) ) ? __('Reader', 'scoper') : __('Pvt Reader', 'scoper');
				
			case 'rs_post_contributor' :
			case 'rs_page_contributor' :
				return __('Contrib', 'scoper');
				
			case 'rs_post_author' :
			case 'rs_page_author' :
				return __('Author', 'scoper');
				
			case 'rs_post_revisor' :
			case 'rs_page_revisor' :
				return __('Revisor', 'scoper');
				
			case 'rs_post_editor' :
			case 'rs_page_editor' :
				if ( defined( 'SCOPER_PUBLISHER_CAPTION' ) )
					return __('Publisher', 'scoper');
				else
					return __('Editor', 'scoper');
				
			case 'rs_page_associate' :
				return __('Assoc', 'scoper');
				
			
			case 'rs_link_editor' :
				return __('Admin', 'scoper');
				
			case 'rs_category_manager' :
			case 'rs_group_manager' :
				return __('Manager', 'scoper');
				
			default :
				if ( $post_type = $GLOBALS['scoper']->role_defs->member_property( $role_handle, 'object_type' ) )
					$role_handle = str_replace( "{$post_type}_", '', $role_handle ); 
	
				return ucwords( trim( str_replace( '_', ' ', substr($role_handle, 2 ) ) ) );
		} // end switch
	}
} // end class
?>