<?php
if( did_action('init') )
	_scoper_eo_register();
else
	add_action( 'init', '_scoper_eo_register' );

function _scoper_eo_register() {
	sseo_register_parameter( 'rs_group', __( 'Role Scoper Group', 'scoper' ) ); 
}

add_filter( 'eo_shortcode_matched', '_scoper_flt_eo_shortcode_matched', 10, 3 );
function _scoper_flt_eo_shortcode_matched( $matched, $params, $shortcode_content ) {
	if ( ! empty( $params['rs_group'] ) ) {
		global $current_rs_user;
		
		if ( array_intersect( $params['rs_group'], array_keys( $current_rs_user->groups ) ) ) {
			return true;
		}
	}

	return $matched;
}