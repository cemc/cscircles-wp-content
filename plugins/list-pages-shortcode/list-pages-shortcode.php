<?php

/*
Plugin Name: List Pages Shortcode
Plugin URI: http://wordpress.org/extend/plugins/list-pages-shortcode/
Description: Introduces the [list-pages], [sibling-pages] and [child-pages] <a href="http://codex.wordpress.org/Shortcode_API">shortcodes</a> for easily displaying a list of pages within a post or page.  Both shortcodes accept all parameters that you can pass to the <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages">wp_list_pages()</a> function.  For example, to show a page's child pages sorted by title simply add [child-pages sort_column="post_title"] in the page's content.
Author: Aaron Harp, Ben Huson
Version: 1.3
Author URI: http://www.aaronharp.com
*/

function shortcode_list_pages( $atts, $content, $tag ) {
	
	global $post;
	
	// Child Pages
	$child_of = 0;
	if ( $tag == 'child-pages' )
		$child_of = $post->ID;
	if ( $tag == 'sibling-pages' )
		$child_of = $post->post_parent;
	
	// Set defaults
	$defaults = array(
		'class'       => $tag,
		'depth'       => 0,
		'show_date'   => '',
		'date_format' => get_option( 'date_format' ),
		'exclude'     => '',
		'include'     => '',
		'child_of'    => $child_of,
		'title_li'    => '',
		'authors'     => '',
		'sort_column' => 'menu_order, post_title',
		'sort_order'  => '',
		'link_before' => '',
		'link_after'  => '',
		'exclude_tree'=> '',
		'meta_key'    => '',
		'meta_value'  => '',
		'offset'      => '',
		'exclude_current_page' => 0
	);
	
	// Merge user provided atts with defaults
	$atts = shortcode_atts( $defaults, $atts );
	
	// Set necessary params
	$atts['echo'] = 0;
	if ( $atts['exclude_current_page'] && absint( $post->ID ) ) {
		if ( !empty( $atts['exclude'] ) )
			$atts['exclude'] .= ',';
		$atts['exclude'] .= $post->ID;
	}
	
	$atts = apply_filters( 'shortcode_list_pages_attributes', $atts, $content, $tag );
	
	// Create output
	$out = wp_list_pages( $atts );
	if ( !empty( $out ) )
		$out = '<ul class="' . $atts['class'] . '">' . $out . '</ul>';
	
	return apply_filters( 'shortcode_list_pages', $out, $atts, $content, $tag );
	
}

add_shortcode( 'child-pages', 'shortcode_list_pages' );
add_shortcode( 'sibling-pages', 'shortcode_list_pages' );
add_shortcode( 'list-pages', 'shortcode_list_pages' );

?>