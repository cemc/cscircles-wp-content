<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

function scoper_purge_cache_files( $dir ) {
	$top_dir = $dir;
	$stack = array($dir);
	$index = 0;
	$errors = 0;
	
	while ($index < count($stack)) {
		# Get indexed directory from stack
		$dir = $stack[$index];

		if ( ! @ is_dir($dir) ) {
			$index++;
			continue;
		}
		
		$dh = @ opendir($dir);
		if (!$dh) {
			return false;
		}
		
		while ( ($file = @ readdir($dh) ) !== false) {
			if ( $file == '.' or $file == '..')
				continue;

			if ( @ is_dir( $dir . DIRECTORY_SEPARATOR . $file ) )
				$stack[] = $dir . DIRECTORY_SEPARATOR . $file;
			elseif ( '.htaccess' != $file ) {  // sanity check and allows for last-time flushing of MU/MS cache folder without wiping WP Super Cache again
				if ( @ is_file( $dir . DIRECTORY_SEPARATOR . $file ) ) {
					if ( file_exists( $dir . DIRECTORY_SEPARATOR . $file ) ) {
						if ( ! @ unlink( $dir . DIRECTORY_SEPARATOR . $file ) ) {
							$errors++;
						}
					}
				}
			}
		}

		$index++;
	}

	$stack = array_reverse($stack);  // Last added dirs are deepest
	foreach($stack as $dir) {
		if ( ! @ rmdir($dir) ) {
			$errors++;
		}
	}
}