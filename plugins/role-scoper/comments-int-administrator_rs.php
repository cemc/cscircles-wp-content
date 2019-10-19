<?php
add_filter( 'comments_clauses', array( 'CommentsInterceptor_Administrator_RS', 'flt_comments_clauses' ), 10, 2 );

class CommentsInterceptor_Administrator_RS {
	public static function flt_comments_clauses( $clauses, &$qry_obj ) {
		global $wpdb;
		
		if ( is_content_administrator_rs() ) {
			$stati = array_merge( get_post_stati( array( 'public' => true ) ), get_post_stati( array( 'private' => true ) ) );
			
			if ( ! defined( 'SCOPER_NO_ATTACHMENT_COMMENTS' ) )
				$stati []= 'inherit';

			$status_csv = "'" . implode( "','", $stati ) . "'";
			$clauses['where'] = preg_replace( "/\s*AND\s*{$wpdb->posts}.post_status\s*=\s*[']?publish[']?/", "AND {$wpdb->posts}.post_status IN ($status_csv)", $clauses['where'] );
		}

		return $clauses;
	}
}
?>