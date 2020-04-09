<?php
class RS_Plugin_Admin {
	function __construct() {
		add_action( 'plugins_api', array( &$this, 'disable_plugins_api' ), 10, 3 );
	}
	
	function disable_plugins_api( $val, $action, $args ) {
		if ( $args && ! empty($args->slug) ) {
			if ( 'role-scoper-migration-advisor' == $args->slug )
				return (object) array( 'body' => '' );
		}

		return $val;
	}
} // end class
?>