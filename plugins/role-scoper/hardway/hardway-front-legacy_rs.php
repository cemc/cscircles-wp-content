<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

add_filter('query', array('ScoperHardwayFrontLegacy', 'flt_recent_comments') );

class ScoperHardwayFrontLegacy
{	
	function flt_recent_comments($query) {
		// Due to missing get_comments hook prior to WP 3.1, this filter operates on every front-end query.
		// If query doesn't pertain to comments, skip out with as little overhead as possible.
		if ( strpos($query, 'comment')
		&& strpos($query, "ELECT") && ! strpos($query, 'posts as parent') && ! strpos($query, "COUNT") && strpos($query, "comment_approved") )
		{
			if ( ! is_attachment() && ! is_content_administrator_rs() ) {
				
				global $wpdb;

				if ( strpos($query, " $wpdb->posts " ) )
					return $query;

				if ( awp_is_plugin_active( 'wp-wall') ) {
					$options = WPWall_GetOptions();
				
					if ( strpos( $query, 'comment_post_ID=' . $options['pageId'] ) )
						return $query;
				}
				
				if ( strpos($query, $wpdb->comments) ) {
					$query = str_replace( " post_status = 'publish'", " $wpdb->posts.post_status = 'publish'", $query );

					// theoretically, a slight performance enhancement if we can simplify the query to skip filtering of attachment comments
					if ( defined('SCOPER_NO_ATTACHMENT_COMMENTS') || ( false !== strpos( $query, 'comment_post_ID =') ) ) {
						
						if ( ! strpos( $query, "JOIN $wpdb->posts" ) )
							$query = preg_replace( "/FROM\s*{$wpdb->comments}\s*WHERE /", "FROM $wpdb->comments INNER JOIN $wpdb->posts ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID WHERE ", $query);
						
						$query = apply_filters('objects_request_rs', $query, 'post', '', array('skip_teaser' => true) );
					} else {
						$query = str_replace( "user_id ", "$wpdb->comments.user_id ", $query);

						$query = str_replace( "SELECT $wpdb->comments.* FROM $wpdb->comments", "SELECT DISTINCT $wpdb->comments.* FROM $wpdb->comments", $query);
						
						if ( ! strpos( $query, ' DISTINCT ' ) )
							$query = str_replace( "SELECT ", "SELECT DISTINCT ", $query);
						
						$post_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
						$post_type_in = "'" . implode( "','", $post_types ) . "'";
	
						$join = "LEFT JOIN $wpdb->posts as parent ON parent.ID = {$wpdb->posts}.post_parent AND parent.post_type IN ($post_type_in) AND $wpdb->posts.post_type = 'attachment'";
						
						$use_post_types = scoper_get_option( 'use_post_types' );
						
						$where = array();
						foreach( $post_types as $type ) {
							if ( ! empty( $use_post_types[$type] ) )
								$where_post = apply_filters('objects_where_rs', '', 'post', $type, array('skip_teaser' => true) );
							else
								$where_post = "AND 1=1";
							
							$where[]= "$wpdb->posts.post_type = '$type' $where_post";
							$where[]= "$wpdb->posts.post_type = 'attachment' AND parent.post_type = '$type' " . str_replace( "$wpdb->posts.", "parent.", $where_post );
						}
						
						$where = agp_implode( ' ) OR ( ', $where, ' ( ', ' ) ' );
						
						if ( ! strpos( $query, "JOIN $wpdb->posts" ) )	
							$query = str_replace( "WHERE ", "INNER JOIN $wpdb->posts ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID $join WHERE ( $where ) AND ", $query);
						else
							$query = str_replace( "WHERE ", "$join WHERE $where AND ", $query);
					}
				}
			}
		}
		
		return $query;
	}
}