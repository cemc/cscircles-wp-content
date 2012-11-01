<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
class ScoperTeaser {
	
	// Manipulate the results set in various ways to prepare it for teaser filtering
	// Determine which listed items are readabled (i.e. will not be teased). Clear private status so teased items will not be hidden completely or trigger a 404
	function posts_teaser_prep_results($results, $tease_otypes, $args = '') {
		$defaults = array('user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 'request' => '', 'object_type' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		global $wpdb, $scoper, $wp_query;
		global $query_interceptor;
		
		if ( ! $results )
			return $results;
		
		if ( did_action('wp_meta') && ! did_action('wp_head') )
			return $results;

		if ( empty($request) ) {
			// TODO: teaser logs last_request itself
			global $query_interceptor;
			
			if ( empty ($query_interceptor->last_request['post']) ) {
				// try to get it from wpdb instead
				if ( ! empty($wpdb->last_query) )
					$request = $wpdb->last_query;
				else {
					// don't risk exposing hidden content if something goes wrong with query logging
					return array();
				}
			} else
				$request = $query_interceptor->last_request['post'];
		}

		if ( strpos( $request, 'WHERE 1=2' ) ) {
			$ids = array();
			foreach( array_keys($results) as $key ) {
				$ids []= $results[$key]->ID;
			}
			$request = "SELECT * FROM $wpdb->posts WHERE ID IN ('" . implode("','", $ids) . "')";
		}
		
		// Pagination could be broken by subsequent query for filtered ids, so buffer current paging parameters
		// ( this code mimics WP_Query::get_posts() )
		if ( ! empty( $wp_query->query_vars['posts_per_page'] ) ) {
			$found_posts_query = apply_filters( 'found_posts_query', 'SELECT FOUND_ROWS()' );
			$buffer_found_posts = $wpdb->get_var( $found_posts_query );
			
			if ( $buffer_found_posts >= $wp_query->query_vars['posts_per_page'] ) {
				$restore_pagination = true;
				$buffer_found_posts = apply_filters( 'found_posts', $buffer_found_posts );
			}
		}

		$list_private = array();
		
		if ( awp_ver( '3.0' ) )
			$private_stati = get_post_stati( array( 'private' => true ) );
		else
			$private_stati = array( 'private' );

		if ( is_single() || is_page() ) {
			$maybe_fudge_private = true;
			$maybe_strip_private = false;
		} else {
			$maybe_strip_private = true;
			$maybe_fudge_private = false;
		}

		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}
		
		// don't risk exposing hidden content if there is a problem with query parsing
		if ( ! $pos = strpos(strtoupper($request), " FROM") )
			return array();
		
		$distinct = ( stripos( $request, " DISTINCT " ) ) ? 'DISTINCT' : ''; // RS does not add any joins, but if DISTINCT clause exists in query, retain it
		$request = "SELECT $distinct {$wpdb->posts}.ID " . substr($request, $pos);

		if ( $limitpos = strpos($request, ' LIMIT ') )
			$request = substr($request, 0, $limitpos);
			
		$args['skip_teaser'] = true;

		$filtered_request = $query_interceptor->flt_objects_request($request, 'post', $object_type, $args);
		
		global $scoper_teaser_filtered_ids;

		$scoper_teaser_filtered_ids = scoper_get_col($filtered_request);
		
		if ( ! isset($scoper->teaser_ids) )
			$scoper->teaser_ids = array();
	
		$hide_ungranted_private = array();
		foreach ( $tease_otypes as $object_type ) {
			$hide_ungranted_private[$object_type] = scoper_get_otype_option('teaser_hide_private', 'post', $object_type);
		}

		foreach ( array_keys($results) as $key ) {
			if ( is_array($results[$key]) )
				$id = $results[$key]['ID'];
			else
				$id = $results[$key]->ID;
				
			if ( ! $scoper_teaser_filtered_ids || ! in_array($id, $scoper_teaser_filtered_ids) ) {
				if ( isset($results[$key]->post_type) )
					$object_type = $results[$key]->post_type;
				else
					$object_type = $scoper->data_sources->get_from_db('type', 'post', $id);
						
				if ( ! in_array($object_type, $tease_otypes) )
					continue;

				// Defeat a WP core secondary safeguard so we can apply the teaser message rather than 404
				if ( in_array( $results[$key]->post_status, $private_stati ) ) {
					// don't want the teaser message (or presence in category archive listing) if we're hiding a page from listing
					$type_obj = get_post_type_object( $object_type );
					
					if ( $type_obj && $type_obj->hierarchical ) {  // TODO: review implementation of this option with custom types
						if ( ! isset($list_private[$object_type]) )
							 $list_private[$object_type] = scoper_get_otype_option( 'private_items_listable', 'post', 'page' );
					} else
						$list_private[$object_type] = true;

					if ( $hide_ungranted_private[$object_type] || ( $maybe_strip_private && ! $list_private[$object_type] ) ) {
						$need_reindex = true;
						unset ( $results[$key] );
						
						// Actually, don't do this because the current method of removing private items from the paged result set will not move items from one result page to another
						//$buffer_found_posts--;	// since we're removing this item from the teased results, decrement the paging total
		
						continue;
					} elseif ( ! empty( $maybe_fudge_private ) && $list_private[$object_type] ) {
						$results[$key]->post_status = 'publish';
					}
				}
			}
		}
		
		if ( ! empty($need_reindex) )  // re-index the array so paging isn't confused
			$results = array_values($results);
		
		// pagination could be broken by the filtered ids query performed in this function, so original paging parameters were buffered
		if ( ! empty($restore_pagination) ) {
			// WP query will apply found_posts filter shortly after this function returns.  Feed it the buffered value from original unfiltered results.
			// Static flag in created function ensures it is only applied once.
			$func_name = create_function( '$a', 'static $been_here; if ( ! empty($been_here) ) return $a; else {$been_here = true; ' . "return $buffer_found_posts;}" );
			add_filter( 'found_posts', $func_name, 1);
		}
		
		return $results;
	}
	
	// apply teaser modifications to the recordset.  Note: this is applied later than 
	function posts_teaser($results, $tease_otypes, $args = '') {
		$defaults = array('user' => '', 'use_object_roles' => -1, 'use_term_roles' => -1, 'request' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		global $wpdb, $scoper, $wp_query;

		if ( did_action('wp_meta') && ! did_action('wp_head') )
			return $results;

		if ( ! is_object($user) ) {
			global $current_user;
			$user = $current_user;
		}

		$teaser_replace = array();
		$teaser_prepend = array();
		$teaser_append = array();
		
		foreach ( $tease_otypes as $object_type ) {			
			$teaser_replace[$object_type]['post_content'] = ScoperTeaser::get_teaser_text( 'replace', 'content', 'post', $object_type, $user );
			$teaser_prepend[$object_type]['post_content'] = ScoperTeaser::get_teaser_text( 'prepend', 'content', 'post', $object_type, $user );
			$teaser_append[$object_type]['post_content'] = ScoperTeaser::get_teaser_text( 'append', 'content', 'post', $object_type, $user );
					
			$teaser_replace[$object_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'replace', 'excerpt', 'post', $object_type, $user );
			$teaser_prepend[$object_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'prepend', 'excerpt', 'post', $object_type, $user );
			$teaser_append[$object_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'append', 'excerpt', 'post', $object_type, $user );

			$teaser_prepend[$object_type]['post_title'] = ScoperTeaser::get_teaser_text( 'prepend', 'name', 'post', $object_type, $user );
			$teaser_append[$object_type]['post_title'] = ScoperTeaser::get_teaser_text( 'append', 'name', 'post', $object_type, $user );
		}

		global $scoper_teaser_filtered_ids;
		
		if ( ! isset($scoper_teaser_filtered_ids) )
			self::posts_teaser_prep_results( $results, $tease_otypes, $args );
		
		if ( ! isset($scoper->teaser_ids) )
			$scoper->teaser_ids = array();
		
		$excerpt_teaser = array();
		$more_teaser = array();
		$x_chars_teaser = array();

		foreach ( $tease_otypes as $object_type ) {
			$teaser_type = scoper_get_otype_option( 'use_teaser', 'post', $object_type );
			if ( 'excerpt' == $teaser_type )
				$excerpt_teaser[$object_type] = true;
			elseif ( 'more' == $teaser_type ) {
				$excerpt_teaser[$object_type] = true;
				$more_teaser[$object_type] = true;
			} elseif ( 'x_chars' == $teaser_type ) {
				$excerpt_teaser[$object_type] = true;
				$more_teaser[$object_type] = true;
				$x_chars_teaser[$object_type] = true;
			}
		}
	
		// strip content from all $results rows not in $items
		$args = array( 'teaser_prepend' => $teaser_prepend, 		'teaser_append' => $teaser_append, 	'teaser_replace' => $teaser_replace, 
						'excerpt_teaser' => $excerpt_teaser,		'more_teaser' => $more_teaser,		'x_chars_teaser' => $x_chars_teaser );
							
		foreach ( array_keys($results) as $key ) {
			if ( is_array($results[$key]) )
				$id = $results[$key]['ID'];
			else
				$id = $results[$key]->ID;
				
			if ( ! $scoper_teaser_filtered_ids || ! in_array($id, $scoper_teaser_filtered_ids) ) {
				if ( isset($results[$key]->post_type) )
					$object_type = $results[$key]->post_type;
				else
					$object_type = $scoper->data_sources->get_from_db('type', 'post', $id);
					
				if ( ! in_array($object_type, $tease_otypes) )
					continue;
						
				ScoperTeaser::apply_teaser( $results[$key], 'post', $object_type, $args );
			}
		}
		
		return $results;
	}
	
	
	function get_teaser_text( $teaser_operation, $variable, $src_name, $object_type, $user = '' ) {
		if ( ! is_object($user) ) {
			global $current_user;	
			$user = $current_user;
		}
			
		$anon = ( $user->ID == 0 ) ? '_anon' : '';

		if ( $msg = scoper_get_otype_option( "teaser_{$teaser_operation}_{$variable}{$anon}", 'post', $object_type, CURRENT_ACCESS_NAME_RS) ) {
			if ( defined('SCOPER_TRANSLATE_TEASER') ) {
				scoper_load_textdomain(); // otherwise this is only loaded for wp-admin

				$msg = translate( $msg, 'scoper');

				if ( ! empty($msg) && ! is_null($msg) && is_string($msg) )
					$msg = htmlspecialchars_decode( $msg );
			}
			return $msg;
		}
	}
	
	function apply_teaser( &$object, $src_name, $object_type, $args = '' ) {
		$defaults = array( 'col_excerpt' => '', 'col_content' => '', 		'excerpt_teaser' => '', 'col_id' => '',
				'teaser_prepend' => '',		 	'teaser_append' => '', 		'teaser_replace' => '', 'more_teaser' => '',
				'x_chars_teaser' => ''	);
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $scoper;
		
		if ( is_array($object) )
			$id = $object['ID'];
		else
			$id = $object->ID;

		$object->scoper_teaser = true;
		$scoper->teaser_ids['post'][$id] = true;

		if ( ! empty( $object->post_password ) ) {
			$excerpt_teaser[$object_type] = false;
			$more_teaser[$object_type] = false;
			$x_chars_teaser[$object_type] = false;
		}

		if ( ! empty($x_chars_teaser[$object_type]) )
			$num_chars = ( defined('SCOPER_TEASER_NUM_CHARS') ) ? SCOPER_TEASER_NUM_CHARS : 50;
			
		// Content replacement mode is applied in the following preference order:
		// 1. Custom excerpt, if available and if selected teaser mode is "excerpt", "excerpt or more", or "excerpt, pre-more or first x chars"
		// 2. Pre-more content, if applicable and if selected teaser mode is "excerpt or more", or "excerpt, pre-more or first x chars"
		// 3. First X Characters (defined by SCOPER_TEASER_NUM_CHARS), if total content is longer than that and selected teaser mode is "excerpt, pre-more or first x chars"
			
		$teaser_set = false;
		$use_excerpt_suffix = true;
		
		// optionally, use post excerpt as the hidden content teaser instead of a fixed replacement
		if ( ! empty($excerpt_teaser[$object_type]) && ! empty($object->post_excerpt) ) {
			$object->post_content = $object->post_excerpt;
			
		} elseif ( ! empty($more_teaser[$object_type]) && ( $more_pos = strpos($object->post_content, '<!--more-->') ) ) {
			$object->post_content = substr( $object->post_content, 0, $more_pos + 11 );
			$object->post_excerpt = $object->post_content;
			if ( is_single() || is_page() )
				$object->post_content .= '<p class="scoper_more_teaser">' . $teaser_replace[$object_type]['post_content'] . '</p>';

		// since no custom excerpt or more tag is stored, use first X characters as teaser - but only if the total length is more than that
		} elseif ( ! empty($x_chars_teaser[$object_type]) && ! empty($object->post_content) && ( strlen( strip_tags($object->post_content) ) > $num_chars ) ) {
			scoper_load_textdomain(); // otherwise this is only loaded for wp-admin

			// since we are stripping out img tag, also strip out image caption applied by WP
			$object->post_content = preg_replace( "/\[caption.*\]/", '', $object->post_content );
			$object->post_content = str_replace( "[/caption]", '', $object->post_content );
			
			$object->post_content = sprintf(_x('%s...', 'teaser suffix', 'scoper'), substr( strip_tags($object->post_content), 0, $num_chars ) );
			$object->post_excerpt = $object->post_content;
			
			if ( is_single() || is_page() )
				$object->post_content .= '<p class="scoper_x_chars_teaser">' . $teaser_replace[$object_type]['post_content'] . '</p>';
		
		} else {
			if ( isset($teaser_replace[$object_type]['post_content']) )
				$object->post_content = $teaser_replace[$object_type]['post_content'];
			else
				$object->post_content = '';

			// Replace excerpt with a user-specified fixed teaser message, 
			// but only if since no custom excerpt exists or teaser options aren't set to some variation of "use excerpt as teaser"
			if ( ! empty($teaser_replace[$object_type]['post_excerpt']) )
				$object->post_excerpt = $teaser_replace[$object_type]['post_excerpt'];
			else
				$object->post_excerpt = '';
			
			// If SCOPER_FORCE_EXCERPT_SUFFIX is defined, use the "content" prefix and suffix only when fully replacing content with a fixed teaser 
			$use_excerpt_suffix = false;
		}

		// Deal with ambiguity in teaser settings.  Previously, content prefix/suffix was applied even if RS substitutes the excerpt as displayed content.  
		// To avoid confusion with existing installations, only use excerpt prefix/suffix if a value is set or constant is defined.
		if ( $use_excerpt_suffix && defined( 'SCOPER_FORCE_EXCERPT_SUFFIX' ) ) {
			$teaser_prepend[$object_type]['post_content'] = $teaser_prepend[$object_type]['post_excerpt'];

			$teaser_append[$object_type]['post_content'] = $teaser_append[$object_type]['post_excerpt'];
		}	
			
		foreach ( $teaser_prepend[$object_type] as $col => $entry )
			if ( isset($object->$col) )
				$object->$col = $entry . $object->$col;
			
		foreach ( $teaser_append[$object_type] as $col => $entry )
			if ( isset($object->$col) ) {
				if ( ( $col == 'post_content' ) && ! empty( $more_pos ) ) {  // WP will strip off anything after the more comment
					$object->$col = str_replace( '<!--more-->', "$entry<!--more-->", $object->$col );
				} else
					$object->$col .= $entry;
			}
				
		// no need to display password form if we're blocking content anyway
		if ( ! empty( $object->post_password ) )
			$object->post_password = '';
	}
} // end class
?>