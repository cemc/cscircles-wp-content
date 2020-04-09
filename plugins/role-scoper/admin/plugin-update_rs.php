<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

class RS_Updater {
	public static function install( $plugin = '' ) {
		global $parent_file, $submenu_file;

		if ( ! current_user_can('install_plugins') )
			wp_die( __('You do not have sufficient permissions to install plugins for this site.') );

		if ( ! $plugin )
			$plugin = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']) : '';

		$slug = $_REQUEST['action'];
		$title = sprintf( __awp('Install %s'), ucwords( str_replace( '-', ' ', $slug ) ) );

		if ( $slug ) {
			global $rs_install_done;
			if ( ! empty($rs_install_done) ) { $return; }
			$rs_install_done = true;
			
			$parent_file = 'plugins.php';
			$submenu_file = 'plugin-install.php';
?>		
<style>
.scoper .updating { background-image:url('<?php echo admin_url('');?>/images/wspin_light.gif');background-repeat:no-repeat; }
.scoper button.updating { background-position:5px 1px;padding-left:25px; }
.scoper .icon32 { background:url('<?php echo SCOPER_URLPATH;?>/admin/images/pp-logo-32.png') no-repeat; width:18px;height:32px;float:left;margin-right:15px }
.scoper p { font-family: sans-serif;font-size: 12px;line-height: 1.4em; }
</style>
<?php
			$upgrader = new RS_Core_Upgrader( new RS_Installer_Skin( compact('title', 'url', 'nonce', 'plugin') ) );
			$upgrader->install_pkg( $plugin, "http://presspermit.com/index.php?PPServerRequest=download&update=$slug&version=VERSION&key=KEY&site=URL" );
		}
	}

} // end class RS_Updater


/**
 * RS_Upgrader class
 *
 * Provides foundational functionality specific to Press Permit update
 * processing classes.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class RS_Upgrader extends Plugin_Upgrader {
	function download_package($package) {
		if ( ! preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package) ) //Local file or remote?
			return $package; //must be a local file..

		if ( empty($package) )
			return new WP_Error('no_package', $this->strings['no_package']);

		$this->skin->feedback( 'downloading_package', $package );

		$vars = array('VERSION','KEY','URL');
		$values = array( '0', '', urlencode( get_option('siteurl') ) );
		$package = str_replace( $vars, $values, $package );

		$download_file = $this->download_url($package);

		if ( is_wp_error($download_file) )
			return new WP_Error('download_failed', $this->strings['download_failed'], $download_file->get_error_message());

		return $download_file;
	}

	function download_url ( $url ) {
		//WARNING: The file is not automatically deleted, The script must unlink() the file.
		if ( ! $url )
			return new WP_Error('http_no_url', __awp('Invalid URL Provided'));

		$request = parse_url($url);
		parse_str($request['query'],$query);
		$tmpfname = wp_tempnam( $query['update'] . ".zip" );
		if ( ! $tmpfname )
			return new WP_Error('http_no_file', __awp('Could not create Temporary file'));

		$handle = @fopen($tmpfname, 'wb');
		if ( ! $handle )
			return new WP_Error('http_no_file', __awp('Could not create Temporary file'));

		$response = wp_remote_get($url, array('timeout' => 300));
		
		if ( is_wp_error($response) ) {
			fclose($handle);
			unlink($tmpfname);
			return $response;
		}

		if ( $response['response']['code'] != '200' ){
			fclose($handle);
			unlink($tmpfname);
			return new WP_Error('http_404', trim($response['response']['message']));
		}

		fwrite($handle, $response['body']);
		fclose($handle);

		return $tmpfname;
	}

	function unpack_package($package, $delete_package = true, $clear_working = true) {
		global $wp_filesystem;

		$this->skin->feedback('unpack_package');

		$upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/';

		//Clean up contents of upgrade directory beforehand.
		if ($clear_working) {
			$upgrade_files = $wp_filesystem->dirlist($upgrade_folder);
			if ( !empty($upgrade_files) ) {
				foreach ( $upgrade_files as $file )
					$wp_filesystem->delete($upgrade_folder . $file['name'], true);
			}
		}

		//We need a working directory
		$working_dir = $upgrade_folder . basename($package, '.zip');

		// Clean up working directory
		if ( $wp_filesystem->is_dir($working_dir) )
			$wp_filesystem->delete($working_dir, true);

		// Unzip package to working directory
		$result = unzip_file($package, $working_dir); // @todo optimizations, Copy when Move/Rename would suffice?

		// Once extracted, delete the package if required.
		if ( $delete_package )
			unlink($package);

		if ( is_wp_error($result) ) {
			$wp_filesystem->delete($working_dir, true);
			return $result;
		}
		$this->working_dir = $working_dir;

		return $working_dir;
	}

}

/**
 * RS_Core_Upgrader class
 *
 * Adds auto-update support for the core plugin.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class RS_Core_Upgrader extends RS_Upgrader {
	function install_pkg_strings($plugin) {
		if ( $title = ucwords( str_replace( '-', ' ', $plugin ) ) ) {
			$this->strings['no_package'] = __awp('Install package not available.');
			$this->strings['downloading_package'] = sprintf(__awp('Downloading install package from <span class="code">%s</span>&#8230;'),untrailingslashit('http://presspermit.com/'));
			$this->strings['unpack_package'] = __awp('Unpacking the package&#8230;');
			$this->strings['installing_package'] = __awp('Installing the plugin&#8230;');
			$this->strings['process_failed'] = sprintf( __awp('%s install Failed.','pp'), $title );
			$this->strings['process_success'] = sprintf( __awp('%s installed successfully.','pp'), $title );
		}
	}
	
	function install_pkg( $slug, $package_url ) {
		$this->init();
		$this->install_pkg_strings($slug);

		if ( defined( 'RSU_VERSION' ) ) { 
			echo '<br />';
			_e( 'The plugin is already installed, and can be accessed using the RS Migration menu item.', 'rsu' );
		} else {
			add_filter('upgrader_source_selection', array(&$this, 'check_package') );

			$this->run(array(
						'package' => $package_url,
						'destination' => WP_PLUGIN_DIR,
						'clear_destination' => false, //Do not overwrite files.
						'clear_working' => true,
						'hook_extra' => array(),
						'plugin' => "{$slug}/{$slug}.php",
						));

			remove_filter('upgrader_source_selection', array(&$this, 'check_package') );

			if ( ! $this->result || is_wp_error($this->result) )
				return $this->result;
		}
	
		// Force refresh of plugin update information
		delete_site_transient('update_plugins');
		wp_cache_delete( 'plugins', 'plugins' );

		return true;
	}
}

/**
 * RS_Installer_Skin class
 *
 * Extensions derived from the WordPress Plugin_Upgrader_Skin class:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @author Jonathan Davis
 * @author Kevin Behrens
 **/
class RS_Installer_Skin extends Plugin_Installer_Skin {

	/**
	 * Custom heading
	 *
	 * @author Jonathan Davis
	 * @author Kevin Behrens
	 *
	 * @return void Description...
	 **/

	function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;
		echo '<div class="wrap scoper">';
		echo screen_icon();
		echo '<h2>' . $this->options['title'] . '</h2>';
	}


	/**
	 * Displays a return to plugins page button after installation
	 *
	 * @author Kevin Behrens
	 *
	 * @return void
	 **/
	function after() {
		$plugin_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$from = isset($_GET['from']) ? stripslashes($_GET['from']) : 'plugins';

		$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin') . '</a>';

		if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
			$install_actions['network_activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin for all sites in this network') . '" target="_parent">' . __('Network Activate') . '</a>';
			unset( $install_actions['activate_plugin'] );
		}

		$install_actions = apply_filters('install_plugin_complete_actions', $install_actions, $this->api, $plugin_file);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
} // END class RS_Installer_Skin

?>