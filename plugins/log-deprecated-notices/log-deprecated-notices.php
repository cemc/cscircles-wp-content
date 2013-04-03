<?php
/**
 * @package Deprecated_Log
 */
/*
 * Plugin Name: Log Deprecated Notices
 * Plugin URI: http://wordpress.org/extend/plugins/log-deprecated-notices/
 * Description: Logs the usage of deprecated files, functions, hooks, and function arguments, offers the alternative if available, and identifies where the deprecated functionality is being used. WP_DEBUG not required (but its general use is strongly recommended).
 * Version: 0.2
 * Author: Andrew Nacin
 * Author URI: http://nacin.com/
 * License: GPLv2 or later
 */

if ( ! class_exists( 'Deprecated_Log' ) ) :

/**
 * Base class.
 *
 * @package Deprecated_Log
 *
 * @todo Plugin ID. Also, notice on plugins page next to said plugin.
 * @todo Support closures as hook callbacks.
 */
class Deprecated_Log {

	/**
	 * Instance.
	 *
	 * @var object
	 */
	static $instance;

	/**
	 * DB version.
	 *
	 * @var int
	 */
	const db_version = 4;

	/**
	 * Options.
	 *
	 * @var array
	 */
	var $options = array();

	/**
	 * Option name in DB.
	 *
	 * @var string
	 */
	const option_name = 'log_deprecated_notices';

	/**
	 * Custom post type.
	 *
	 * @var string
	 */
	const pt = 'deprecated_log';

	/**
	 * Logging queue, to be inserted on shutdown.
	 *
	 * @var array
	 */
	var $queued_posts = array();

	/**
	 * Constructor. Adds hooks.
	 */
	function Deprecated_Log() {
		self::$instance = $this;

		// Bail without 3.0.
		if ( ! function_exists( '__return_false' ) )
			return;

		// Registers the uninstall hook.
		register_activation_hook( __FILE__, array( &$this, 'on_activation' ) );

		// Registers post type.
		add_action( 'init', array( &$this, 'action_init' ) );

		// Log on shutdown.
		add_action( 'shutdown', array( &$this, 'shutdown' ) );
		// For testing, so the queries are picked up by the Debug Bar:
		// add_action( 'admin_footer', array( &$this, 'shutdown' ), 999 );

		// Silence E_NOTICE for deprecated usage.
		if ( WP_DEBUG ) {
			foreach ( array( 'deprecated_function', 'deprecated_file', 'deprecated_argument', 'doing_it_wrong', 'deprecated_hook' ) as $item )
				add_action( "{$item}_trigger_error", '__return_false' );
		}

		// Log deprecated notices.
		add_action( 'deprecated_function_run',  array( &$this, 'log_function' ), 10, 3 );
		add_action( 'deprecated_file_included', array( &$this, 'log_file'     ), 10, 4 );
		add_action( 'deprecated_argument_run',  array( &$this, 'log_argument' ), 10, 4 );
		add_action( 'doing_it_wrong_run',       array( &$this, 'log_wrong'    ), 10, 3 );
		add_action( 'deprecated_hook_used',     array( &$this, 'log_hook'     ), 10, 4 );

		if ( ! is_admin() )
			return;

		$this->options = get_option( self::option_name );

		// Textdomain and upgrade routine.
		add_action( 'admin_init',                       array( &$this, 'action_admin_init' ) );
		// Basic CSS.
		add_action( 'admin_print_styles',               array( &$this, 'action_admin_print_styles' ), 20 );
		// Column handling.
		add_action( 'manage_posts_custom_column',       array( &$this, 'action_manage_posts_custom_column' ), 10, 2 );
		// Column headers.
		add_filter( 'manage_' . self::pt . '_posts_columns', array( &$this, 'filter_manage_post_type_posts_columns' ) );
		// Filters and 'Clear Log'.
		add_action( 'restrict_manage_posts',            array( &$this, 'action_restrict_manage_posts' ) );
		// Modify Bulk Actions.
		add_action( 'bulk_actions-edit-' . self::pt,    array( &$this, 'filter_bulk_actions' ) );
		// Basic JS (changes Bulk Actions options).
		add_action( 'admin_footer-edit.php',            array( &$this, 'action_admin_footer_edit_php' ) );
		// Add/Edit permissions handling, make 'Clear Log' work, and other actions.
		foreach ( array( 'edit.php', 'post.php', 'post-new.php' ) as $item )
			add_action( "load-{$item}",                 array( &$this, 'action_load_edit_php' ) );

		// Handle special edit.php filters.
		add_filter( 'request',                          array( &$this, 'filter_request' ) );
		// Don't have the 'New Post' favorites action.
		add_filter( 'favorite_actions',                 array( &$this, 'favorite_actions' ) );
	}

	/**
	 * Attached to admin_init. Loads the textdomain and the upgrade routine.
	 */
	function action_admin_init() {
		if ( false === $this->options || ! isset( $this->options['db_version'] ) || $this->options['db_version'] < self::db_version ) {
			if ( ! is_array( $this->options ) )
				$this->options = array();
			$current_db_version = isset( $this->options['db_version'] ) ? $this->options['db_version'] : 0;
			$this->upgrade( $current_db_version );
			$this->options['db_version'] = self::db_version;
			update_option( self::option_name, $this->options );
		}
		load_plugin_textdomain('log-deprecated', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Upgrade routine.
	 */
	function upgrade( $current_db_version ) {
		global $wpdb;
		// Change post type name and the meta key prefix, also set up a new option.
		if ( $current_db_version < 1 ) {
			$wpdb->update( $wpdb->posts, array( 'post_type' => 'deprecated_log' ), array( 'post_type' => 'nacin_deprecated' ) );
			$wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_deprecated_log_meta' ), array( 'meta_key' => '_nacin_deprecated_meta' ) );
			$this->options['last_viewed'] = current_time( 'mysql' );
		}
		// We're gonna go with publish as the default now and also leverage Trash.
		if ( $current_db_version < 2 )
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'post_type' => 'deprecated_log' ) );
		// Split the original meta key into individual keys.
		if ( $current_db_version < 4 ) {
			$meta_rows = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_deprecated_log_meta'" );
			foreach ( $meta_rows as $meta_row ) {
				$meta = maybe_unserialize( $meta_row->meta_value );
				foreach ( array_keys( $meta ) as $key ) {
					add_post_meta( $meta_row->post_id, '_deprecated_log_' . $key, $meta[ $key ], true );
				}
			}
			// Remove bad data caused by an undefined index.
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_deprecated_log_'" );
		}
	}

	/**
	 * Attached to deprecated_function_run action.
	 */
	function log_function( $function, $replacement, $version ) {
		$backtrace = debug_backtrace();
		$deprecated = $function . '()';
		$hook = null;
		$bt = 4;
		// Check if we're a hook callback.
		if ( ! isset( $backtrace[4]['file'] ) && 'call_user_func_array' == $backtrace[5]['function'] ) {
			$hook = $backtrace[6]['args'][0];
			$bt = 6;
		}
		$in_file = $this->strip_abspath( $backtrace[ $bt ]['file'] );
		$on_line = $backtrace[ $bt ]['line'];
		$this->log( 'function', compact( 'deprecated', 'replacement', 'version', 'hook', 'in_file', 'on_line'  ) );
	}

	/**
	 * Attached to deprecated_hook_used action.
	 */
	function log_hook( $hook, $replacement, $version, $message ) {
		global $wp_filter;

		$backtrace = debug_backtrace();
		/*
		echo '<pre>';
		var_dump( $GLOBALS['wp_filter'][ $hook ] );
		$this->queued_posts = array();
		var_dump( $backtrace );
		echo '</pre>';
		//*/
		$callbacks_attached = array();
		foreach ( $wp_filter[ $hook ] as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$callbacks_attached[] = $this->callback_to_string( $callback['function'] );
			}
		}

		// For actions fired within a function.
		$in_file = $this->strip_abspath( $backtrace[3]['file'] );
		$on_line = $backtrace[3]['line'] + 1; // _deprecated_file() is one line before do_action()

		$deprecated = $hook;
		foreach ( $callbacks_attached as $callback )
			$this->log( 'hook', compact( 'deprecated', 'replacement', 'version', 'in_file', 'on_line', 'callback' ) );
	}

	/**
	 * Returns a string representation of a callback.
	 */
	function callback_to_string( $callback ) {
		if ( is_array( $callback ) ) {
			if ( is_object( $callback[0] ) )
				return get_class( $callback[0] ) . '::' . $callback[1];
			else
				return $callback[0] . '::' . $callback[1];
		}
		return $callback;
	}

	/**
	 * Attached to doing_it_wrong_run action.
	 *
	 * @todo Support hook callbacks, I guess.
	 */
	function log_wrong( $function, $message, $version ) {
		$backtrace = debug_backtrace();
		$deprecated = $function . '()';
		$in_file = $this->strip_abspath( $backtrace[ 4 ]['file'] );
		$on_line = $backtrace[ 4 ]['line'];
		$this->log( 'wrong', compact( 'deprecated', 'message', 'version', 'in_file', 'on_line' ) );
	}

	/**
	 * Attached to deprecated_argument_run action.
	 */
	function log_argument( $function, $message, $version ) {
		$backtrace = debug_backtrace();
		$deprecated = $function . '()';
		$menu = $in_file = $on_line = null;
		// @todo [core] Introduce _deprecated_message() or something.
		switch ( $function ) {
			case 'options.php' :
				$deprecated = __( 'Unregistered Setting', 'log-deprecated' );
				$this->log( 'functionality', compact( 'deprecated', 'message', 'version' ) );
				return;
			case 'has_cap' :
				if ( 0 === strpos( $backtrace[7]['function'], 'add_' ) && '_page' == substr( $backtrace[7]['function'], -5 ) ) {
					$bt = 7;
					if ( 0 === strpos( $backtrace[8]['function'], 'add_' ) && '_page' == substr( $backtrace[8]['function'], -5 ) )
						$bt = 8;
					$in_file = $this->strip_abspath( $backtrace[ $bt ]['file'] );
					$on_line = $backtrace[ $bt ]['line'];
					$deprecated = $backtrace[ $bt ]['function'] . '()';
				} elseif ( '_wp_menu_output' == $backtrace[7]['function'] ) {
					$deprecated = 'current_user_can()';
					$menu = true;
				} else {
					$in_file = $this->strip_abspath( $backtrace[6]['file'] );
					$on_line = $backtrace[6]['line'];
					$deprecated = 'current_user_can()';
				}
				break;
			case 'get_plugin_data' :
				$in_file = $this->strip_abspath( $backtrace[4]['args'][0] );
				break;
			case 'define()' :
			case 'define' :
				if ( 'ms_subdomain_constants' == $backtrace[4]['function'] ) {
					$deprecated = 'VHOST';
					$this->log( 'constant', compact( 'deprecated', 'message', 'menu', 'version' ) );
					return;
				}
				// Fall through.
			default :
				$in_file = $this->strip_abspath( $backtrace[4]['file'] );
				$on_line = $backtrace[4]['line'];
				break;
		}
		$this->log( 'argument', compact( 'deprecated', 'message', 'menu', 'version', 'in_file', 'on_line' ) );
	}

	/**
	 * Attached to deprecated_file_included action.
	 */
	function log_file( $file, $replacement, $version, $message ) {
		$backtrace = debug_backtrace();
		$deprecated = $this->strip_abspath( $backtrace[3]['file'] );
		$in_file = $this->strip_abspath( $backtrace[4]['file'] );
		$on_line = $backtrace[4]['line'];
		$this->log( 'file', compact( 'deprecated', 'replacement', 'message', 'version', 'in_file', 'on_line' ) );
	}

	/**
	 * Strip ABSPATH from an absolute filepath. Also, Windows is lame.
	 */
	function strip_abspath( $path ) {
		return ltrim( str_replace( array( untrailingslashit( ABSPATH ), '\\' ), array( '', '/' ), $path ), '/' );
	}

	/**
	 * Used to log deprecated usage.
	 *
	 * @todo Logging what I end up displaying is probably a bad idea.
	 */
	function log( $type, $args ) {
		global $wpdb;

		extract( $args, EXTR_SKIP );

		switch ( $type ) {
			case 'functionality' :
				$deprecated = sprintf( __( 'Functionality: %s', 'log-deprecated' ), $deprecated );
				break;
			case 'constant' :
				$deprecated  = sprintf( __( 'Constant: %s', 'log-deprecated' ), $deprecated );
				break;
			case 'function' :
				$deprecated = sprintf( __( 'Function: %s', 'log-deprecated' ), $deprecated );
				break;
			case 'file' :
				$deprecated = sprintf( __( 'File: %s', 'log-deprecated' ), $deprecated );
				break;
			case 'argument' :
				$deprecated = sprintf( __( 'Argument in %s', 'log-deprecated' ), $deprecated );
				break;
			case 'wrong' :
				$deprecated = sprintf( __( 'Incorrect Use of %s', 'log-deprecated' ), $deprecated );
				break;
			case 'hook' :
				$deprecated = sprintf( __( 'Hook: %s', 'log-deprecated' ), $deprecated );
				break;
		}

		$content = '';
		if ( ! empty( $replacement ) )
			// translators: %s is name of function.
			$content = sprintf( __( 'Use %s instead.', 'log-deprecated' ), $replacement );
		if ( ! empty( $message ) )
			$content .= ( strlen( $content ) ? ' ' : '' ) . (string) $message;
		if ( empty( $content ) )
			$content = __( 'No alternative available.', 'log-deprecated' );
		if ( 'wrong' == $type )
			$content .= "\n" . sprintf( __( 'This message was added in version %s.', 'log-deprecated' ), $version );
		else
			$content .= "\n" . sprintf( __( 'Deprecated in version %s.', 'log-deprecated' ), $version );

		if ( 'hook' == $type ) {
			$excerpt = sprintf( __( 'The callback %3$s() is attached to this hook, which is fired in %1$s on line %2$d.' ), $in_file, $on_line, $callback );
		} elseif ( ! empty( $hook ) ) {
			$excerpt = sprintf( __( 'Attached to the %1$s hook, fired in %2$s on line %3$d.', 'log-deprecated' ), $hook, $in_file, $on_line );
		} elseif ( ! empty( $menu ) ) {
			$excerpt = __( 'An admin menu page is using user levels instead of capabilities. There is likely a related log item with specifics.', 'log-deprecated' );
		} elseif ( ! empty( $on_line ) ) {
			$excerpt = sprintf( __( 'Used in %1$s on line %2$d.', 'log-deprecated' ), $in_file, $on_line );
		} elseif ( ! empty( $in_file ) ) {
			// translators: %s is file name.
			$excerpt = sprintf( __( 'Used in %s.', 'log-deprecated' ), $in_file );
		} else {
			$excerpt = '';
		}

		$post_name = md5( $type . implode( $args ) );

		$meta_pairs = array(
			'_deprecated_log_meta' => array_merge( array( 'type' => $type ), $args ),
			'_deprecated_log_type' => $type,
		);

		foreach ( array_keys( $args ) as $meta_key ) {
			$meta_pairs[ '_deprecated_log_' . $meta_key ] = $args[ $meta_key ];
		}

		$post_data = array(
			'post_date'    => current_time( 'mysql' ),
			'post_excerpt' => $excerpt,
			'post_type'    => self::pt,
			'post_status'  => 'publish',
			'post_title'   => $deprecated,
			'post_content' => $content . "\n<!--more-->\n" . $excerpt, // searches
			'post_name'    => $post_name,
		);

		$this->queued_post( $post_name, $post_data, $meta_pairs );
	}

	/**
	 * Queues a post for final submission to the database.
	 *
	 * @param string $post_name
	 * @param array $post_data
	 * @param array $meta_pairs
	 */
	function queued_post( $post_name, $post_data, $meta_pairs ) {
		if ( isset( $this->queued_posts[ $post_name ] ) ) {
			$this->queued_posts[ $post_name ]->count++;
		} else {
			$this->queued_posts[ $post_name ] = (object) array(
				'post'  => $post_data,
				'count' => 1,
				'meta'  => $meta_pairs,
			);
		}
	}

	/**
	 * Logs all queued posts and counts on shutdown.
	 */
	function shutdown() {
		global $wpdb;
		$existing = (array) $wpdb->get_results( $wpdb->prepare( "SELECT post_name, ID, comment_count FROM $wpdb->posts WHERE post_type = %s", self::pt ), OBJECT_K );
		foreach ( $this->queued_posts as $post_name => $queued_post ) {
			if ( isset( $existing[ $post_name ] ) ) {
				$new_count = $existing[ $post_name ]->comment_count + $queued_post->count;
				$wpdb->update(
					$wpdb->posts,
					array( 'comment_count' => $new_count, 'post_date' => current_time( 'mysql' ) ),
					array( 'ID' => $existing[ $post_name ]->ID )
				);
			} else {
				$post_id = wp_insert_post( $queued_post->post );
				foreach ( $queued_post->meta as $meta_key => $meta_value )
					update_post_meta( $post_id, $meta_key, $meta_value );
				$wpdb->update( $wpdb->posts, array( 'comment_count' => 1 ), array( 'ID' => $post_id ) );
			}
		}
	}

	/**
	 * Attached to manage_posts_custom_column action.
	 *
	 * @todo [core] Custom post types should be able to disable/enable trash or change
	 *  the length of EMPTY_TRASH_DAYS. Thinking empty_trash_days() and is_trash_enabled()
	 *  based on the constant originally, and each can be drilled down by post type.
	 */
	function action_manage_posts_custom_column( $col, $post_id ) {
		global $wp_list_table;
		switch ( $col ) {
			case 'deprecated_title' :
				$post = get_post( $post_id );
				$post_type_object = get_post_type_object( $post->post_type );
				echo '<strong>' . esc_html( $post->post_title ) . '</strong>';
				echo '<br/>' . esc_html( $post->post_excerpt );
				echo '<div class="row-actions">';
				if ( EMPTY_TRASH_DAYS && $wp_list_table->is_trash )
					echo "<span class='untrash'><a title='" . esc_attr__( 'Unmute', 'log-deprecated' ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post_id ) ), 'untrash-' . $post->post_type . '_' . $post_id ) . "'>" . __( 'Unmute', 'log-deprecated' ) . '</a></span> | ';
				elseif ( EMPTY_TRASH_DAYS )
					echo "<span class='mute'><a class='submitdelete' title='" . esc_attr__( 'Mute', 'log-deprecated' ) . "' href='" . get_delete_post_link($post->ID) . "'>" . __( 'Mute', 'log-deprecated' ) . '</a></span> | ';
				echo '<span class="delete"><a class="submitdelete" title="' . esc_attr__( 'Delete', 'log-deprecated' ) . '" href="' . get_delete_post_link( $post_id, '', true ) . '">' . __( 'Delete', 'log-deprecated' ) . '</a></span></div>';
				break;
			case 'deprecated_count' :
				$post = get_post( $post_id );
				$count = $post->comment_count ? $post->comment_count : 1; // Caching. Don't want 0
				echo number_format_i18n( $count );
				break;
			case 'deprecated_modified' :
				echo get_the_date( __('Y/m/d g:i:s A', 'log-deprecated' ) );
				break;
			case 'deprecated_version' :
				$meta = get_post_meta( $post_id, '_deprecated_log_meta', true );
				echo $meta['version'];
				break;
			case 'deprecated_alternative':
				$post = get_post( $post_id );
				echo nl2br( preg_replace( '/<!--more(.*?)?-->(.*)/s', '', $post->post_content ) );
				break;
		}
	}

	/**
	 * Attached to manage_{post_type}_posts_columns filter.
	 *
	 * @todo Is a separate version column desirable?
	 */
	function filter_manage_post_type_posts_columns( $cols ) {
		$cols = array(
			'cb' => '<input type="checkbox" />',
			'deprecated_title'       => __( 'Deprecated Call', 'log-deprecated' ),
			'deprecated_version'     => __( 'Version',         'log-deprecated' ),
			'deprecated_alternative' => __( 'Alternative',     'log-deprecated' ),
			'deprecated_count'       => __( 'Count',           'log-deprecated' ),
			'deprecated_modified'    => __( 'Last Used',       'log-deprecated' ),
		);
		unset( $cols['deprecated_version'] ); // We're not using it, but get it translated.
		return $cols;
	}

	/**
	 * Prints basic CSS.
	 *
	 * Hides Add New button, sets some column widths.
	 */
	function action_admin_print_styles() {
		global $current_screen;
		if ( 'edit-' . self::pt != $current_screen->id )
			return;
	?>
<style type="text/css">
.add-new-h2, .view-switch, body.no-js .tablenav select[name^=action], body.no-js #doaction, body.no-js #doaction2 { display: none }
.widefat .column-deprecated_modified, .widefat .column-deprecated_version { width: 10%; }
.widefat .column-deprecated_count { width: 10%; text-align: right }
.widefat .column-deprecated_cb { padding: 0; width: 2.2em }
</style>
	<?php
	}

	/**
	 * Basic JS -- changes Bulk Action options.
	 */
	function action_admin_footer_edit_php() {
		global $current_screen;
		if ( 'edit-' . self::pt != $current_screen->id )
			return;
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($) {
	var s = $('div.actions select[name^=action]');
	s.find('option[value=delete]').remove();
	s.find('option[value=trash]').text('<?php echo addslashes( __( 'Mute', 'log-deprecated' ) ); ?>');
	s.find('option[value=untrash]').text('<?php echo addslashes( __( 'Unmute', 'log-deprecated' ) ); ?>');
	s.append('<option value="delete"><?php echo addslashes( __( 'Delete', 'log-deprecated' ) ); ?></option>');
});
//]]>
</script>
<?php
	}

	/**
	 * Modifies bulk actions.
	 *
	 * We're allowed to use this filter in 3.1, but only to remove.
	 */
	function filter_bulk_actions( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Modifies 'Empty Trash' to 'Clear Log'.
	 */
	function filter_gettext_bulk_actions( $translation, $text ) {
		switch ( $text ) {
			case 'Empty Trash' :
				global $wp_list_table, $is_trash;
				if ( isset( $wp_list_table ) )
					$wp_list_table->is_trash = $this->_is_trash;
				else
					$is_trash = $this->_is_trash;

				return __( 'Clear Log', 'log-deprecated' );
			case 'Move to Trash' :
				return __( 'Mute', 'log-deprecated' );
			case 'Restore' :
				return __( 'Unmute', 'log-deprecated' );
			case 'Delete Permanently' :
				return __( 'Delete', 'log-deprecated' );
		}
		return $translation;
	}

	/**
	 * Post filters.
	 *
	 * Also, a cheap hack to show a 'Clear Log' button.
	 * Somehow, there is not a decent hook anywhere on edit.php (but there is for edit-comments.php).
	 */
	function action_restrict_manage_posts() {
		global $wpdb, $typenow, $wp_list_table, $is_trash;
		if ( self::pt != $typenow )
			return;

		if ( isset( $wp_list_table ) ) {
			$this->_is_trash = $wp_list_table->is_trash;
			$wp_list_table->is_trash = true;
		} else {
			$this->_is_trash = $is_trash;
			$is_trash = true;
		}

		add_filter( 'gettext', array( &$this, 'filter_gettext_bulk_actions' ), 10, 2 );

		$types = array(
			'constant'      => __( 'Constant',        'log-deprecated' ),
			'function'      => __( 'Function',        'log-deprecated' ),
			'argument'      => __( 'Argument',        'log-deprecated' ),
			'functionality' => __( 'Functionality',   'log-deprecated' ),
			'file'          => __( 'File',            'log-deprecated' ),
			'wrong'         => __( 'Incorrect Usage', 'log-deprecated' ),
			'hook'          => __( 'Hook',            'log-deprecated' ),
		);
		$types_used = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_deprecated_log_type'" );
		if ( count( $types_used ) > 1 ) {
			echo '<select name="deprecated_type">';
			echo '<option value="">' . esc_html__( 'Show all types', 'log-deprecated' ) . '</option>';
			foreach ( $types_used as $type ) {
				$selected = ! empty( $_GET['deprecated_type'] ) ? selected( $_GET['deprecated_type'], $type, false ) : '';
				echo '<option' . $selected . ' value="' . esc_attr( $type ) . '">' . esc_html( $types[ $type ] ) . '</option>';
			}
			echo '</select>';
		}

		$versions = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_deprecated_log_version'" );
		if ( count( $versions ) > 1 ) {
			echo '<select name="deprecated_version">';
			echo '<option value="">' . esc_html__( 'Since', 'log-deprecated' ) . '</option>';
			usort( $versions, 'version_compare' );
			foreach ( array_reverse( $versions ) as $version ) {
				$selected = ! empty( $_GET['deprecated_version'] ) ? selected( $_GET['deprecated_version'], $version, false ) : '';
				echo '<option' . $selected . ' value="' . esc_attr( $version ) . '">' . esc_html( '&#8804; ' . $version ) . '</option>';
			}
			echo '</select> ';
		}

		return; // @disable

		$files = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_deprecated_log_in_file'" );
		if ( count( $files > 1 ) ) {
			echo '<select name="deprecated_file">';
			echo '<option value="">' . esc_html__( 'Show all files', 'log-deprecated' ) . '</option>';
			foreach ( array_filter( $files ) as $file ) {
				$selected = '';
				if ( ! empty( $_GET['deprecated_file'] ) )
					$selected = selected( stripslashes( $_GET['deprecated_file'] ), $file, false );
				echo '<option' . $selected . ' value="' . esc_attr( $file ) . '">' . esc_html( $file ) . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * Filters the request based on additional post filters.
	 *
	 * Adds posts_where and posts_join filters as necessary, if more than one meta key/value pair is queried.
	 *
	 * @todo use the new meta_query in 3.1.
	 */
	function filter_request( $qv ) {
		if ( ! is_admin() )
			return;
		$pairs = array();
		if ( ! empty( $_GET['deprecated_file'] ) )
			$pairs[] = array( '_deprecated_log_in_file', stripslashes( $_GET['deprecated_file'] ), '=' );
		if ( ! empty( $_GET['deprecated_type'] ) )
			$pairs[] = array( '_deprecated_log_type', $_GET['deprecated_type'], '=' );
		if ( ! empty( $_GET['deprecated_version'] ) )
			$pairs[] = array( '_deprecated_log_version', ( $_GET['deprecated_version'] + 0 ), '<=' );
		if ( ! empty( $pairs ) ) {
			$pair = array_shift( $pairs );
			list( $qv['meta_key'], $qv['meta_value'], $qv['meta_compare'] ) = $pair;
			if ( ! empty( $pairs ) ) {
				add_filter( 'posts_where', array( &$this, 'filter_posts_where' ), 10, 2 );
				add_filter( 'posts_join',  array( &$this, 'filter_posts_join'  ), 10, 2 );
				$this->_additional_filters = $pairs;
			}
		}
		return $qv;
	}

	/**
	 * Handles additional meta key/value filters in the WHERE clause.
	 */
	function filter_posts_where( $where, $object ) {
		global $wpdb;
		foreach ( $this->_additional_filters as $pair )
			$where .= $wpdb->prepare(" AND postmeta{$pair[0]}.meta_key = %s AND postmeta{$pair[0]}.meta_value {$pair[2]} %s ", $pair[0], $pair[1] );
		return $where;
	}

	/**
	 * Creates additional joins to handle additional meta key/value filters.
	 */
	function filter_posts_join( $join, $object ) {
		global $wpdb;
		foreach ( $this->_additional_filters as $pair )
			$join .= " LEFT JOIN $wpdb->postmeta AS postmeta{$pair[0]} ON ($wpdb->posts.ID = postmeta{$pair[0]}.post_id) ";
		return $join;
	}

	/**
	 * Changes status filter and updated strings.
	 *
	 * 'All' and 'Trash' becomes 'Log' and 'Muted'.
	 * 'Item moved/deleted/restored' are modified as well.
	 */
	function filter_ngettext( $translation, $single, $plural, $number ) {
		switch ( $single ) {
			case 'All <span class="count">(%s)</span>' :
				return _n( 'Log <span class="count">(%s)</span>', 'Log <span class="count">(%s)</span>', $number, 'log-deprecated' );
			case 'Trash <span class="count">(%s)</span>' :
				return _n( 'Muted <span class="count">(%s)</span>', 'Muted <span class="count">(%s)</span>', $number, 'log-deprecated' );
			case 'Item moved to the Trash.' :
				return _n( 'Entry muted.', 'Entries muted.', $number, 'log-deprecated' );
			case 'Item permanently deleted.' :
				return _n( 'Entry deleted.', 'Entries deleted.', $number, 'log-deprecated' );
			case 'Item restored from the Trash.' :
				return _n( 'Entry unmuted.', 'Entries unmuted.', $number, 'log-deprecated' );
		}
		return $translation;
	}

	/**
	 * Cheap hacks when we're in the post type UI.
	 *
	 * First, it locks out post.php and post-new.php, since our permissions
	 * don't cover that.
	 *
	 * Then it gets our 'Clear Log' button to work on the 'All' page, as it
	 * uses the 'Empty Trash' functionality and requires a post status to work
	 * off of, not the 'all' status. Don't try this at home, folks.
	 *
	 * We're using 'All' because we can't remove that, but by directly modifying
	 * the show_in_admin_status_list property of the publish post status (also in
	 * this function), we can hide that one.
	 *
	 * This function also sets the last_viewed option, for the unread menu bubble.
	 *
	 * This function adds a filter to _n() and _nx() to change the text of status links.
	 *
	 * @todo [core] Filter on $status_links in edit.php
	 * @todo [core] Custom post stati should have granular properties per post type.
	 */
	function action_load_edit_php() {
		$screen = get_current_screen();
		if ( self::pt == $screen->id && ( $screen->action == 'add' || $_GET['action'] == 'edit' ) )
			wp_die( __( 'Invalid post type.', 'log-deprecated' ) );
		if ( self::pt != $screen->post_type )
			return;

		if ( ( empty( $_GET['post_status'] ) || 'all' == $_GET['post_status'] )
			&& ( isset( $_GET['delete_all'] ) || isset( $_GET['delete_all2'] ) )
		)
			$_GET['post_status'] = $_REQUEST['post_status'] = 'publish';

		$this->options['last_viewed'] = current_time('mysql');
		update_option( self::option_name, $this->options );

		global $wp_post_statuses;
		// You didn't see this.
		$wp_post_statuses['publish']->show_in_admin_status_list = false;

		foreach ( array( 'ngettext', 'ngettext_with_context' ) as $filter )
			add_filter( $filter, array( &$this, 'filter_ngettext' ), 10, 4 );

		add_filter( 'gettext', array( &$this, 'filter_gettext_bulk_actions' ), 10, 2 );
	}

	/**
	 * Registers the custom post type.
	 */
	function action_init() {
		global $typenow, $wpdb;

		$labels = array(
			'name' => __( 'Deprecated Calls', 'log-deprecated' ),
			'singular_name' => __( 'Deprecated Call', 'log-deprecated' ),
			// add_new, add_new_item, edit_item, new_item, view_item
			'search_items' => __( 'Search Logs', 'log-deprecated' ),
			'not_found' => __( 'Nothing in the log! Your plugins are oh so fine.', 'log-deprecated' ),
			'not_found_in_trash' => __( 'Nothing muted.', 'log-deprecated' ),
		);

		if ( is_admin()
			&& ( ! isset( $_REQUEST['post_type'] ) || self::pt != $_REQUEST['post_type'] )
			&& $this->options && ! empty( $this->options['last_viewed'] ) )
		{
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = %s AND post_date > %s AND post_status = %s", self::pt, $this->options['last_viewed'], 'publish' ) );
			if ( $count )
				$labels['menu_name'] = sprintf( __( 'Deprecated Calls %s', 'log-deprecated' ), "<span class='update-plugins count-$count'><span class='update-count'>" . number_format_i18n( $count ) . '</span></span>' );
		}

		$args = array(
			'labels' => $labels,
			'show_in_menu' => 'tools.php',
			'show_ui' => true,
			'public' => false,
			'capabilities' => array(
				'edit_post'          => 'activate_plugins',
				'edit_posts'         => 'activate_plugins',
				'edit_others_posts'  => 'activate_plugins',
				'publish_posts'      => 'do_not_allow',
				'read_post'          => 'activate_plugins',
				'read_private_posts' => 'do_not_allow',
				'delete_post'        => 'activate_plugins',
			),
			'rewrite'      => false,
			'query_var'    => false,
		);
		register_post_type( self::pt, $args );
	}

	/**
	 * Runs on activation. Simply registers the uninstall routine.
	 */
	function on_activation() {
		register_uninstall_hook( __FILE__, array( 'Deprecated_Log', 'on_uninstall' ) );
	}

	/**
	 * Runs on uninstall. Removes all log data.
	 */
	function on_uninstall() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE post_type = %s", self::pt ) );
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '\_deprecated\_log\_%'" );
		delete_option( self::option_name );
	}

	/**
	 * Don't have the 'New Posts' favorite action.
	 *
	 * @since 0.2
	 */
	function favorite_actions( $actions ) {
		if ( isset( $actions['post-new.php?post_type=deprecated_log'] ) )
			unset( $actions['post-new.php?post_type=deprecated_log'] );
		return $actions;
	}

}
/** Initialize. */
new Deprecated_Log;

endif;