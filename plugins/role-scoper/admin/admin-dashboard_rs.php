<?php
//add_action( 'wp_dashboard_setup', 'scoper_add_dashboard_widgets' );
add_action ( 'right_now_table_end', 'scoper_right_now_pending' );

function scoper_right_now_pending() {
	
	$post_types = array_diff_key( get_post_types( array( 'public' => true ), 'object' ), array( 'attachment' => true ) );
	
	foreach ( $post_types as $post_type => $post_type_obj ) {
		if ( $num_posts = wp_count_posts( $post_type ) ) {
			if ( ! empty($num_posts->pending) ) {
				echo "\n\t".'<tr>';
		
				$num = number_format_i18n( $num_posts->pending );
				
				//$text = _n( 'Pending Page', 'Pending Pages', intval($num_pages->pending), 'scoper' );
				
				if ( intval($num_posts->pending) <= 1 )
					$text = sprintf( __('Pending %1$s', 'scoper'),$post_type_obj->labels->singular_name);
				else
					$text = sprintf( __('Pending %1$s', 'scoper'), $post_type_obj->labels->name);
					
				$type_clause = ( 'post' == $post_type ) ? '' : "&post_type=$post_type";
					
				$url = "edit.php?post_status=pending{$type_clause}";
				$num = "<a href='$url'><span class='pending-count'>$num</span></a>";
				$text = "<a class='waiting' href='$url'>$text</a>";
		
				$type_class = ( $post_type_obj->hierarchical ) ? 'b-pages' : 'b-posts';
				
				echo '<td class="first b ' . $type_class . ' b-waiting">' . $num . '</td>';
				echo '<td class="t posts">' . $text . '</td>';
				echo '<td class="b"></td>';
				echo '<td class="last t"></td>';
				echo "</tr>\n\t";
			}
		}
	}
}

/*
function scoper_add_dashboard_widgets() {
	wp_add_dashboard_widget( 'scoper_dashboard_stuff', __('Role Scoper', 'scoper'), 'scoper_dashboard_stuff' );	
}

function scoper_dashboard_stuff() {

}
*/

?>