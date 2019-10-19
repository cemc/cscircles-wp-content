<?php
class RS_UpdateWatch {
	public static function update_watch() {
		$action = ( isset($_REQUEST['action']) ) ? sanitize_key($_REQUEST['action']) : '';
		
		if ( ! empty($_REQUEST['pp_install']) && ( 'role-scoper-migration-advisor' == $action ) ) {
			add_action( "update-custom_{$action}", array( 'RS_UpdateWatch', 'plugin_install' ) );
		}
	}
	
	public static function plugin_install( $plugin = '' ) {
		require_once( dirname(__FILE__).'/plugin-update_rs.php' );
		RS_Updater::install( $plugin );
	}
} // end class

?>