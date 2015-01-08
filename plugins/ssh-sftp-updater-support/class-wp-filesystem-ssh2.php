<?php
/**
 * WordPress SSH2 Filesystem.
 *
 * @package WordPress
 * @subpackage Filesystem
 */

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/phpseclib/');

require_once('Net/SFTP.php');
require_once('Crypt/RSA.php');

/**
 * WordPress Filesystem Class for implementing SSH2.
 *
 * @since 2.7
 * @package WordPress
 * @subpackage Filesystem
 * @uses WP_Filesystem_Base Extends class
 */

//define('NET_SFTP_LOGGING', NET_SFTP_LOG_REALTIME);

class WP_Filesystem_SSH2 extends WP_Filesystem_Base {

	var $link = false;
	var $sftp_link = false;
	var $keys = false;
	var $password = false;
	var $errors = array();
	var $options = array();

	function WP_Filesystem_SSH2($opt='') {
		$this->method = 'ssh2';
		$this->errors = new WP_Error();

		if ( !function_exists('stream_get_contents') ) {
			$this->errors->add('ssh2_php_requirement', __('We require the PHP5 function <code>stream_get_contents()</code>'));
			return false;
		}

		// Set defaults:
		if ( empty($opt['port']) )
			$this->options['port'] = 22;
		else
			$this->options['port'] = $opt['port'];

		if ( empty($opt['hostname']) )
			$this->errors->add('empty_hostname', __('SSH2 hostname is required'));
		else
			$this->options['hostname'] = $opt['hostname'];

		if ( ! empty($opt['base']) )
			$this->wp_base = $opt['base'];

		if ( !empty ($opt['private_key']) ) {
			$this->options['private_key'] = $opt['private_key'];

			$this->keys = true;
		} elseif ( empty ($opt['username']) ) {
			$this->errors->add('empty_username', __('SSH2 username is required'));
		}

		if ( !empty($opt['username']) )
			$this->options['username'] = $opt['username'];

		if ( empty ($opt['password']) ) {
			if ( !$this->keys )	//password can be blank if we are using keys
				$this->errors->add('empty_password', __('SSH2 password is required'));
		} else {
			$this->options['password'] = $opt['password'];

			$this->password = true;
		}
	}

	function connect() {
		$this->link = new Net_SFTP($this->options['hostname'], $this->options['port']);

		if ( ! $this->link ) {
			$this->errors->add('connect', sprintf(__('Failed to connect to SSH2 Server %1$s:%2$s'), $this->options['hostname'], $this->options['port']));
			return false;
		}

		if ( !$this->keys ) {
			if ( ! $this->link->login($this->options['username'], $this->options['password']) ) {
				$this->errors->add('auth', sprintf(__('Username/Password incorrect for %s'), $this->options['username']));
				return false;
			}
		} else {
			$rsa = new Crypt_RSA();
			if ( $this->password ) {
				$rsa->setPassword($this->options['password']);
			}
			$rsa->loadKey($this->options['private_key']);
			if ( ! $this->link->login($this->options['username'], $rsa ) ) {
				$this->errors->add('auth', sprintf(__('Private key incorrect for %s'), $this->options['username']));
				return false;
			}
		}

		return true;
	}

	function run_command( $command, $returnbool = false) {

		if ( ! $this->link )
			return false;

		$data = $this->link->exec($command);

		if ( $returnbool )
			return ( $data === false ) ? false : '' != trim($data);
		else
			return $data;
	}

	function get_contents($file, $type = '', $resumepos = 0 ) {
		return $this->link->get($file);
	}

	function get_contents_array($file) {
		$lines = preg_split('#(\r\n|\r|\n)#', $this->link->get($file), -1, PREG_SPLIT_DELIM_CAPTURE);
		$newLines = array();
		for ($i = 0; $i < count($lines); $i+= 2)
			$newLines[] = $lines[$i] . $lines[$i + 1];
		return $newLines;
	}

	function put_contents($file, $contents, $mode = false ) {
		$ret = $this->link->put($file, $contents);

		$this->chmod($file, $mode);

		return false !== $ret;
	}

	function cwd() {
		$cwd = $this->run_command('pwd');
		if ( $cwd )
			$cwd = trailingslashit($cwd);
		return $cwd;
	}

	function chdir($dir) {
		$this->list->chdir($dir);
		return $this->run_command('cd ' . $dir, true);
	}

	function chgrp($file, $group, $recursive = false ) {
		if ( ! $this->exists($file) )
			return false;
		if ( ! $recursive || ! $this->is_dir($file) )
			return $this->run_command(sprintf('chgrp %o %s', $mode, escapeshellarg($file)), true);
		return $this->run_command(sprintf('chgrp -R %o %s', $mode, escapeshellarg($file)), true);
	}

	function chmod($file, $mode = false, $recursive = false) {
		return $mode === false ? false : $this->link->chmod($mode, $file, $recursive);
	}

	function chown($file, $owner, $recursive = false ) {
		if ( ! $this->exists($file) )
			return false;
		if ( ! $recursive || ! $this->is_dir($file) )
			return $this->run_command(sprintf('chown %o %s', $mode, escapeshellarg($file)), true);
		return $this->run_command(sprintf('chown -R %o %s', $mode, escapeshellarg($file)), true);
	}

	function owner($file, $owneruid = false) {
		if ($owneruid === false) {
			$result = $this->link->stat($file);
			$owneruid = $result['uid'];
		}

		if ( ! $owneruid )
			return false;
		if ( ! function_exists('posix_getpwuid') )
			return $owneruid;
		$ownerarray = posix_getpwuid($owneruid);
		return $ownerarray['name'];
	}

	function getchmod($file) {
		$result = $this->link->stat($file);

		return substr(decoct($result['permissions']),3);
	}

	function group($file, $gid = false) {
		if ($gid === false) {
			$result = $this->link->stat($file);
			$gid = $result['gid'];
		}

		if ( ! $gid )
			return false;
		if ( ! function_exists('posix_getgrgid') )
			return $gid;
		$grouparray = posix_getgrgid($gid);
		return $grouparray['name'];
	}

	function copy($source, $destination, $overwrite = false, $mode = false) {
		if ( ! $overwrite && $this->exists($destination) )
			return false;
		$content = $this->get_contents($source);
		if ( false === $content)
			return false;
		return $this->put_contents($destination, $content, $mode);
	}

	function move($source, $destination, $overwrite = false) {
		return $this->link->rename($source, $destination);
	}

	function delete($file, $recursive = false) {
		return $this->link->delete($file, $recursive);
	}

	function exists($file) {
		return $this->link->stat($file) !== false;
	}

	function is_file($file) {
		$result = $this->link->stat($file);
		return $result['type'] == NET_SFTP_TYPE_REGULAR;
	}

	function is_dir($path) {
		$result = $this->link->stat($path);
		return $result['type'] == NET_SFTP_TYPE_DIRECTORY;
	}

	function is_readable($file) {
		return true;

		return is_readable('ssh2.sftp://' . $this->sftp_link . '/' . $file);
	}

	function is_writable($file) {
		return true;

		return is_writable('ssh2.sftp://' . $this->sftp_link . '/' . $file);
	}

	function atime($file) {
		$result = $this->link->stat($file);
		return $result['atime'];
	}

	function mtime($file) {
		$result = $this->link->stat($file);
		return $result['mtime'];
	}

	function size($file) {
		$result = $this->link->stat($file);
		return $result['size'];
	}

	function touch($file, $time = 0, $atime = 0) {
		//Not implmented.
	}

	function mkdir($path, $chmod = false, $chown = false, $chgrp = false) {
		$path = untrailingslashit($path);
		if ( ! $chmod )
			$chmod = FS_CHMOD_DIR;
		//if ( ! ssh2_sftp_mkdir($this->sftp_link, $path, $chmod, true) )
		//	return false;
		if ( ! $this->link->mkdir($path) && $this->link->chmod($chmod, $path) )
			return false;
		if ( $chown )
			$this->chown($path, $chown);
		if ( $chgrp )
			$this->chgrp($path, $chgrp);
		return true;
	}

	function rmdir($path, $recursive = false) {
		return $this->delete($path, $recursive);
	}

	function dirlist($path, $include_hidden = true, $recursive = false) {
		if ( $this->is_file($path) ) {
			$limit_file = basename($path);
			$path = dirname($path);
		} else {
			$limit_file = false;
		}

		if ( ! $this->is_dir($path) )
			return false;

		$ret = array();
		$entries = $this->link->rawlist($path);

		foreach ($entries as $name => $entry) {
			$struc = array();
			$struc['name'] = $name;

			if ( '.' == $struc['name'] || '..' == $struc['name'] )
				continue; //Do not care about these folders.

			if ( ! $include_hidden && '.' == $struc['name'][0] )
				continue;

			if ( $limit_file && $struc['name'] != $limit_file )
				continue;

			$struc['perms'] 	= $entry['permissions'];
			$struc['permsn']	= $this->getnumchmodfromh($struc['perms']);
			$struc['number'] 	= false;
			$struc['owner']    	= $this->owner($path.'/'.$entry, $entry['uid']);
			$struc['group']    	= $this->group($path.'/'.$entry, $entry['gid']);
			$struc['size']    	= $entry['size'];//$this->size($path.'/'.$entry);
			$struc['lastmodunix']= $entry['mtime'];//$this->mtime($path.'/'.$entry);
			$struc['lastmod']   = date('M j',$struc['lastmodunix']);
			$struc['time']    	= date('h:i:s',$struc['lastmodunix']);
			$struc['type']		= $entry['type'] == NET_SFTP_TYPE_DIRECTORY ? 'd' : 'f';

			if ( 'd' == $struc['type'] ) {
				if ( $recursive )
					$struc['files'] = $this->dirlist($path . '/' . $struc['name'], $include_hidden, $recursive);
				else
					$struc['files'] = array();
			}

			$ret[ $struc['name'] ] = $struc;
		}
		return $ret;
	}
}