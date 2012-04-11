<?php
/*
Plugin Name: TwentyTen: No Max Editor Width
Plugin URI: http://dd32.id.au/2010/06/01/introducing-twentyten-remove-max-editor-width/
Description: Removes the maximum width imposed on the TinyMCE editor by the TwentyTen theme
Author: Dion Hulse
Version: 0.1
Author URI: http://dd32.id.au/
*/

add_filter('mce_css', 'ttmew_add_style');
function ttmew_add_style($content) {
	return $content .= ',' . plugins_url('editor-style.css?v=0.1', __FILE__);
}