<?php

function scoper_get_upload_info() {
	$func = create_function( '$a', 'return false;' );
	add_filter( 'option_uploads_use_yearmonth_folders', $func, 99 );	// prevent determination and creation of date-based upload subfolders
	$upload_info = wp_upload_dir();
	remove_filter( 'option_uploads_use_yearmonth_folders', $func, 99 );
	
	return $upload_info;
}
?>