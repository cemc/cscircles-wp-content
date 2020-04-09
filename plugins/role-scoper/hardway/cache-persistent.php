<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// derived from cache.php in WP 2.3, now stripped down to be per-request memcache only
function wpp_suffix_flag( $flag ) {
	if ( IS_MU_RS ) {
		global $wpp_object_cache;
		
		if ( is_array( $wpp_object_cache->global_groups ) && ! in_array( $flag, $wpp_object_cache->global_groups ) ) {
			global $blog_id;
			$flag .= "_$blog_id";
		}
	}
	
	return $flag;
}

function wpp_cache_add($key, $data, $flag = '', $expire = 0, $append_blog_suffix = true) {
	if ( ! empty($_POST) )	// kevinB: reduce elusive anomolies and allow flushing optimization by disabling cache updates during POST operation
		return;
	
	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( is_serialized( $data ) )
		$data = unserialize($data);
	
	if ( empty($wpp_object_cache) )
		return;
	
	return $wpp_object_cache->add($key, $data, $flag, $expire);
}

function wpp_cache_close() {}

function wpp_cache_delete($id, $flag = '', $append_blog_suffix = true) {
	global $wpp_object_cache;

	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( empty($wpp_object_cache) )
		return;
		
	return $wpp_object_cache->delete($id, $flag);
}

function wpp_cache_flush_all_sites() {return true;}
function wpp_cache_flush( $all_sites = false ) {return true;}
function wpp_cache_flush_group($flag, $append_blog_suffix = true) {return true;}

function wpp_cache_get($id, $flag = '', $append_blog_suffix = true) {
	global $wpp_object_cache;

	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( empty($wpp_object_cache) )
		return;
		
	return $wpp_object_cache->get($id, $flag);
}

function wpp_cache_init( $sitewide_groups = true, $use_cache_subdir = true ) {
	global $wpp_object_cache;
	
	if ( isset($wpp_object_cache) )
		$wpp_object_cache->save();
		
	$GLOBALS['wpp_object_cache'] = new Scoper_Nonpersistent_Object_Cache( $use_cache_subdir );
	
	if ( IS_MU_RS && $sitewide_groups )
		$GLOBALS['wpp_object_cache']->global_groups = array_merge( $GLOBALS['wpp_object_cache']->global_groups, array( 'all_usergroups', 'group_members' ) );
}

function wpp_cache_replace($key, $data, $flag = '', $expire = 0, $append_blog_suffix = true) {
	if ( ! empty($_POST) )	// kevinB: reduce elusive anomolies and allow flushing optimization by disabling cache updates during POST operation
		return;

	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( is_serialized( $data ) )
		$data = unserialize($data);

	if ( empty($wpp_object_cache) )
		return;
	
	return $wpp_object_cache->replace($key, $data, $flag, $expire);
}

function wpp_cache_set($key, $data, $flag = '', $expire = 0, $append_blog_suffix = true, $force_update = false) {
	if ( ! empty($_POST) && ! $force_update )	// kevinB: reduce elusive anomolies and allow flushing optimization by disabling cache updates during POST operation
		return;

	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( is_serialized( $data ) )
		$data = unserialize($data);

	if ( empty($wpp_object_cache) )
		return;
	
	return $wpp_object_cache->set($key, $data, $flag, $expire);
}

function wpp_cache_force_set( $key, $data, $flag = '', $expire = 0, $append_blog_suffix = true ) {
	return wpp_cache_set( $key, $data, $flag, $expire, $append_blog_suffix, true );
}

function wpp_cache_test( &$err_msg, $text_domain = '' ) {return false;}

if ( ! class_exists( 'WP_Persistent_Object_Cache' ) ) {
	// maintain API back-compat in case anyone is calling this directly
	class WP_Persistent_Object_Cache {
		var $cache_dir;				
		var $cache_enabled = false;
		var $expiration_time = 0;
		var $flock_filename = '';
		var $mutex;
		var $cache = array();
		var $dirty_objects = array();
		var $non_existant_objects = array();
		var $global_groups = array();
		var $non_persistent_groups = array();
		var $blog_id;
		var $cold_cache_hits = 0;
		var $warm_cache_hits = 0;
		var $cache_misses = 0;
		var $secret = '';
		var $is_404;
		
		function acquire_lock() {return false;}
		function add($id, $data, $group = 'default', $expire = '') {return false;}
		function delete($id, $group = 'default', $force = true) {return false;}
		function flush( $group = '', $all_sites = false ) {return true;}
		function get($id, $group = 'default', $count_hits = true) {return false;}
		function get_group_dir($group) {return '';}
		function hash($data) {return '';}
		function load_group_from_db($group) {}
		function make_group_dir($group, $perms) {return '';}
		function rm_cache_dir( $group = '', $all_sites = false ) {return true;}
		function release_lock() {}
		function replace($id, $data, $group = 'default', $expire = '') {return false;}
		function set($id, $data, $group = 'default', $expire = '') {return false;}
		function save() {return false;}
		function stats() {}
	}
}

if ( ! class_exists( 'Scoper_Nonpersistent_Object_Cache' ) ) {
	class Scoper_Nonpersistent_Object_Cache extends WP_Persistent_Object_Cache {
		/*
		var $cache = array ();
		var $non_existant_objects = array ();
		var $warm_cache_hits = 0;
		var $cache_misses = 0;
		var $is_404;
		*/
		
		function add($id, $data, $group = 'default', $expire = '') {
			if (empty ($group))
				$group = 'default';

			if (false !== $this->get($id, $group, false))
				return false;

			return $this->set($id, $data, $group, $expire);
		}

		function delete($id, $group = 'default', $force = true) {
			if (empty ($group))
				$group = 'default';

			if (!$force && false === $this->get($id, $group, false))
				return false;

			$this->non_existant_objects[$group][$id] = true;
			
			if ( isset($this->cache[$group][$id]) ) {
				unset ($this->cache[$group][$id]);
			}
		
			return true;
		}
		
		function get($id, $group = 'default', $count_hits = true) {
			if (empty ($group))
				$group = 'default';

			// return any memcached results from this http session
			if (isset ($this->cache[$group][$id])) {
				if ($count_hits)
					$this->warm_cache_hits += 1;
				return $this->cache[$group][$id];
			}

			if (isset ($this->non_existant_objects[$group][$id]))
				return false;
			
			$this->non_existant_objects[$group][$id] = true;
			$this->cache_misses += 1;
			return false;
		}

		function replace($id, $data, $group = 'default', $expire = '') {
			if (empty ($group))
				$group = 'default';

			if (false === $this->get($id, $group, false))
				return false;

			return $this->set($id, $data, $group, $expire);
		}

		function set($id, $data, $group = 'default', $expire = '') {
			if (empty ($group))
				$group = 'default';

			if ( ! is_array($data) && ( NULL == $data ) )
				$data = '';

			// is_404() function is no longer available at the execution of this wpp_cache_close, so check it here
			if ( ! empty( $GLOBALS['wp_query'] ) && did_action('parse_query') && function_exists( 'is_404' ) && is_404() && empty( $this->is_404 ) )
				$this->is_404 = true;
			
			if ( ! empty( $this->is_404 ) )
				return true;
				
			$this->cache[$group][$id] = $data;
			
			if ( isset($this->non_existant_objects[$group][$id]) )
				unset ($this->non_existant_objects[$group][$id]);
			
			return true;
		}
		
		function stats() {
			echo "<p>";
			echo "<strong>Warm Cache Hits:</strong> {$this->warm_cache_hits}<br />";
			echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
			echo "</p>";

			foreach ($this->cache as $group => $cache) {
				echo "<p>";
				echo "<strong>Group:</strong> $group<br />";
				echo "<strong>Cache:</strong>";
				echo "<pre>";
				print_r($cache);
				echo "</pre>";
			}
		}
	}
}