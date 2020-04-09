<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
// derived from http://us3.php.net/manual/en/ref.array.php#80631
function agp_array_flatten($arr_md, $go_deep = true) { //flattens multi-dim arrays (destroys keys)
    $arr_flat = array(); 
    if ( ! is_array($arr_md) ) return $arr_flat;
    
    foreach ($arr_md as $element) {
       	if ( is_array($element) ) {
       		if ( $go_deep )
           		$arr_flat = array_merge($arr_flat, agp_array_flatten($element));
           	else
           		$arr_flat = array_merge($arr_flat, $element);
        } else
            array_push($arr_flat, $element);
    }
 
    return $arr_flat;
}

function agp_implode( $delim, $arr, $wrap_open, $wrap_close, $array_unique = true, $wrap_single_item = false ) {
	if ( ! is_array($arr) )
		return $arr;

	if ( count($arr) ) {
		if ( $array_unique )
			$arr = array_unique($arr);

		/*
		if ( defined( 'RS_DEBUG' ) ) {
			$test = implode($delim, $arr);
			if ( strpos( $test, 'Array' ) ) {
				dump($test);
				agp_bt_die();
			}
		}
		*/
			
		return $wrap_open . implode($delim, $arr) . $wrap_close;
	} else {
		if ( $wrap_single_item )
			return $wrap_open . reset($arr) . $wrap_close;
		else
			return reset($arr);
	}
}

// recursive function to merge two arrays with a specified number of key dimension
// supports absent keys in either array, with arr_custom values taking precedence
function agp_merge_md_array($arr_default, $arr_custom, $key_dimensions = 1, $current_dimension = 1 ) {
	if ( $current_dimension == $key_dimensions )
		return array_merge($arr_default, $arr_custom);
	else {
		$opt_keys = array_merge( array_keys($arr_default), array_keys($arr_custom) );
		foreach ($opt_keys as $key_name) {
			if ( ! isset($arr_custom[$key_name]) ) $arr_custom[$key_name] = array();
			if ( ! isset($arr_default[$key_name]) ) $arr_default[$key_name] = array();
			$arr_custom[$key_name] = agp_merge_md_array($arr_default[$key_name], $arr_custom[$key_name], $key_dimensions, $current_dimension + 1);
		}
		
		return $arr_custom;
	}
}
// adapted from http://us.php.net/manual/en/function.array-unique.php#86210
function agp_array_unique_md($arr) {
	array_walk($arr, create_function('&$value', '$value = serialize($value);'));
	$arr = array_unique($arr);
	array_walk($arr, create_function('&$value', '$value = unserialize($value);'));
	return $arr;
}

function agp_get_lambda_argstring($num_args) {
	if ( $num_args ) {
		$args = array();
		for ( $i = 97; $i < 97 + $num_args; $i++)
			$args[] = '$' . chr($i);
		$arg_str = implode( ', ', $args);
	} else
		$arg_str = '';
		
	return $arg_str;
}

if( ! function_exists( 'agp_is_plugin_network_active' ) ) {
	function agp_is_plugin_network_active( $plugin_file ) {
		return ( array_key_exists( $plugin_file, maybe_unserialize( get_site_option( 'active_sitewide_plugins') ) ) );
	}
}

?>