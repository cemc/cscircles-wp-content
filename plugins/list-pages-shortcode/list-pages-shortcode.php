<?php

/*
Plugin Name: List Pages Shortcode
Plugin URI: http://wordpress.org/extend/plugins/list-pages-shortcode/
Description: Introduces the [list-pages], [sibling-pages] and [child-pages] <a href="http://codex.wordpress.org/Shortcode_API">shortcodes</a> for easily displaying a list of pages within a post or page.  Both shortcodes accept all parameters that you can pass to the <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages">wp_list_pages()</a> function.  For example, to show a page's child pages sorted by title simply add [child-pages sort_column="post_title"] in the page's content.
Author: Ben Huson, Aaron Harp
Version: 1.7.4
Author URI: http://www.aaronharp.com
*/

add_shortcode( 'child-pages', array( 'List_Pages_Shortcode', 'shortcode_list_pages' ) );
add_shortcode( 'sibling-pages', array( 'List_Pages_Shortcode', 'shortcode_list_pages' ) );
add_shortcode( 'list-pages', array( 'List_Pages_Shortcode', 'shortcode_list_pages' ) );
add_filter( 'list_pages_shortcode_excerpt', array( 'List_Pages_Shortcode', 'excerpt_filter' ) );

class List_Pages_Shortcode {

	public function __construct() {
		// @todo  Deprecate use of constructor
	}

	public function List_Pages_Shortcode() {
		// @todo  Deprecate use of PHP4 constructor
	}

	static function shortcode_list_pages( $atts, $content, $tag ) {
		global $post;

		do_action( 'shortcode_list_pages_before', $atts, $content, $tag );

		// Child Pages
		$child_of = 0;
		if ( $tag == 'child-pages' ) {
			$child_of = $post->ID;
		}
		if ( $tag == 'sibling-pages' ) {
			$child_of = $post->post_parent;
		}

		// Set defaults
		$defaults = array(
			'class'                => 'list-pages-shortcode ' . $tag,
			'depth'                => 0,
			'show_date'            => '',
			'date_format'          => get_option( 'date_format' ),
			'exclude'              => '',
			'include'              => '',
			'child_of'             => $child_of,
			'list_type'            => 'ul',
			'title_li'             => '',
			'authors'              => '',
			'sort_column'          => 'menu_order, post_title',
			'sort_order'           => '',
			'link_before'          => '',
			'link_after'           => '',
			'exclude_tree'         => '',
			'meta_key'             => '',
			'meta_value'           => '',
			'walker'               => new List_Pages_Shortcode_Walker_Page,
			'post_type'            => 'page',
			'offset'               => '',
			'post_status'          => 'publish',
			'exclude_current_page' => 0,
			'excerpt'              => 0
		);

		// Merge user provided atts with defaults
		$atts = shortcode_atts( $defaults, $atts );
		$atts['title_li'] = html_entity_decode( $atts['title_li'] );

		// Set necessary params
		$atts['echo'] = 0;
		if ( $atts['exclude_current_page'] && absint( $post->ID ) ) {
			if ( ! empty( $atts['exclude'] ) ) {
				$atts['exclude'] .= ',';
			}
			$atts['exclude'] .= $post->ID;
		}

		$atts = apply_filters( 'shortcode_list_pages_attributes', $atts, $content, $tag );

		// Catch <ul> tags in wp_list_pages()
		$atts['list_type'] = self::validate_list_type( $atts['list_type'] );
		if ( 'ul' != $atts['list_type'] ) {
			add_filter( 'wp_list_pages', array( 'List_Pages_Shortcode', 'ul2list_type' ), 10, 2 );
		}

		// Create output
		$list_pages_atts = $atts;
		if ( empty( $list_pages_atts['list_type'] ) ) {
			$list_pages_atts['list_type'] = 'ul';
		}
		$out = wp_list_pages( $list_pages_atts );
		remove_filter( 'wp_list_pages', array( 'List_Pages_Shortcode', 'ul2list_type' ), 10 );
		if ( ! empty( $out ) && ! empty( $atts['list_type'] ) ) {
			$out = '<' . $atts['list_type'] . ' class="' . $atts['class'] . '">' . $out . '</' . $atts['list_type'] . '>';
		}
		$out = apply_filters( 'shortcode_list_pages', $out, $atts, $content, $tag );

		do_action( 'shortcode_list_pages_after', $atts, $content, $tag );

		return $out;
	}

	/**
	 * UL 2 List Type
	 * Replaces all <ul> tags with <{list_type}> tags.
	 *
	 * @param string $output Output of wp_list_pages().
	 * @param array $args shortcode_list_pages() args.
	 * @return string HTML output.
	 */
	static function ul2list_type( $output, $args = null ) {

		$list_type = self::validate_list_type( $args['list_type'] );

		if ( 'ul' != $list_type ) {

			// <ul>
			$output = str_replace( '<ul>', '<' . $list_type . '>', $output );
			$output = str_replace( '<ul ', '<' . $list_type . ' ', $output );
			$output = str_replace( '</ul> ', '</' . $list_type . '>', $output );

			// <li>
			$list_type = 'span' == $list_type ? 'span' : 'div';
			$output = str_replace( '<li>', '<' . $list_type . '>', $output );
			$output = str_replace( '<li ', '<' . $list_type . ' ', $output );
			$output = str_replace( '</li> ', '</' . $list_type . '>', $output );

		}

		return $output;

	}

	/**
	 * Excerpt Filter
	 * Add a <div> around the excerpt by default.
	 *
	 * @param string $excerpt Excerpt.
	 * @return string Filtered excerpt.
	 */
	static function excerpt_filter( $text ) {
		if ( ! empty( $text ) ) {
			return ' <div class="excerpt">' . $text . '</div>';
		}
		return $text;
	}

	/**
	 * Validate List Type
	 *
	 * @param   string  $list_type  List type tag.
	 * @return  string              Valid tag.
	 */
	public static function validate_list_type( $list_type ) {

		if ( empty( $list_type ) || ! in_array( $list_type, array( 'ul', 'div', 'span', 'article', 'aside', 'section' ) ) ) {
			$list_type = 'ul';
		}

		return $list_type;

	}

}

/**
 * Create HTML list of pages.
 * A copy of the WordPress Walker_Page class which adds an excerpt.
 */
class List_Pages_Shortcode_Walker_Page extends Walker_Page {

	/**
	 * @see Walker::start_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$list_type = List_Pages_Shortcode::validate_list_type( $args['list_type'] );
		$output .= "\n$indent<" . $list_type . " class='children'>\n";
	}

	/**
	 * @see Walker::end_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$list_type = List_Pages_Shortcode::validate_list_type( $args['list_type'] );
		$output .= "$indent</" . $list_type . ">\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object.
	 * @param int $depth Depth of page. Used for padding.
	 * @param int $current_page Page ID.
	 * @param array $args
	 */
	function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		if ( $depth ) {
			$indent = str_repeat( "\t", $depth );
		} else {
			$indent = '';
		}

		extract( $args, EXTR_SKIP );
		$css_class = array( 'page_item', 'page-item-' . $page->ID );

		if ( isset( $args['pages_with_children'][ $page->ID ] ) ) {
			$css_class[] = 'page_item_has_children';
		}

		if ( ! empty( $current_page ) ) {
			$_current_page = get_page( $current_page );
			if ( in_array( $page->ID, $_current_page->ancestors ) ) {
				$css_class[] = 'current_page_ancestor';
			}
			if ( $page->ID == $current_page ) {
				$css_class[] = 'current_page_item';
			} elseif ( $_current_page && $page->ID == $_current_page->post_parent ) {
				$css_class[] = 'current_page_parent';
			}
		} elseif ( $page->ID == get_option( 'page_for_posts' ) ) {
			$css_class[] = 'current_page_parent';
		}

		$css_class = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		if ( '' === $page->post_title ) {
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$item = '<a href="' . get_permalink( $page->ID ) . '">' . $link_before . apply_filters( 'the_title', $page->post_title, $page->ID ) . $link_after . '</a>';

		if ( ! empty( $show_date ) ) {
			if ( 'modified' == $show_date ) {
				$time = $page->post_modified;
			} else {
				$time = $page->post_date;
			}

			$item .= ' ' . mysql2date( $date_format, $time );
		}

		// Excerpt
		if ( $args['excerpt'] ) {
			$item .= apply_filters( 'list_pages_shortcode_excerpt', $page->post_excerpt, $page, $depth, $args, $current_page );
		}

		$output .= $indent . '<li class="' . $css_class . '">' . apply_filters( 'list_pages_shortcode_item', $item, $page, $depth, $args, $current_page );
	}

}

/**
 * [shortcode_list_pages] Function
 * Kept for legacy reasons in case people are using it directly.
 */
function shortcode_list_pages( $atts, $content, $tag ) {
	return List_Pages_Shortcode::shortcode_list_pages( $atts, $content, $tag );
}

// @todo  Deprecate instance
global $List_Pages_Shortcode;
$List_Pages_Shortcode = new List_Pages_Shortcode();
