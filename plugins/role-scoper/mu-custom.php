<?php

class ScoperMU_Custom {
	function current_user_logged_into_main( $wp_blogcaps, $orig_reqd_caps ) {
		global $current_site, $current_blog;
		
		$prev_blog = $current_blog->blog_id;

		switch_to_blog( $current_site->blog_id );
		
		global $current_user;
		
		$is_logged = ( $current_user->ID > 0 );
		
		switch_to_blog( $prev_blog );
		
		if ( $is_logged )
			return array_merge( $wp_blogcaps, array_fill_keys( $orig_reqd_caps, true ) );
	}
} // end class

?>