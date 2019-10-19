=== List Pages Shortcode ===
Contributors: husobj, aaron_guitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=aaron%40freshwebs%2enet&item_name=Fotobook%20Donation&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: shortcodes, pages, list pages, sibling pages, child pages, subpages
Requires at least: 3.5
Tested up to: 4.4.2
Stable tag: 1.7.4
License: GPLv2 or later

Introduces the [list-pages], [sibling-pages] and [child-pages] shortcodes for easily displaying a list of pages within a post or page.

== Description ==

Introduces the [list-pages], [sibling-pages] and [child-pages] [shortcodes](http://codex.wordpress.org/Shortcode_API) for easily displaying a list of pages within a post or page.  Both shortcodes accept all parameters that you can pass to the [`wp_list_pages()`](http://codex.wordpress.org/Template_Tags/wp_list_pages) function with the addition of a class parameter.

= Example Usage =

*List pages sorted by title*

`[list-pages sort_column="post_title"]`

*List pages but exclude certain IDs and set the class of the list to "my-page-list"*

`[list-pages exclude="17,38" class="my-page-list"]`

*Show excerpt (for pages excerpt support will need adding manually or via the [Page Excerpt](https://wordpress.org/plugins/page-excerpt/) plugin)*

`[list-pages excerpt="1"]`

*List the current page's children, but only show the top level*

`[child-pages depth="1"]`

*List the current page's siblings and their subpages*

`[sibling-pages depth="2"]`

= Default Arguments =

The default values are the same as for the [wp_list_pages()](http://codex.wordpress.org/Template_Tags/wp_list_pages) function except for title_li which defaults to nothing.  If a class is not specified, a default class of either "list-pages", "sibling-pages" or "child-pages" is given to the UL tag.  In addition, the echo parameter has no effect.

In addition to the [wp_list_pages()](http://codex.wordpress.org/Template_Tags/wp_list_pages) arguments, you can also specify:

* **list_type** *(string)* List tag. Defaults to `<ul>`.
* **exclude_current_page** *(int)* Exclude the current page. Defaults to `0`.
* **excerpt** *(int)* Show the page excerpt. Defaults to `0`.

== Installation ==

1. Download and unzip the most recent version of this plugin
2. Upload the list-pages-shortcode folder to /path-to-wordpress/wp-content/plugins/
3. Login to your WP Admin panel, click Plugins, and activate "List Pages Shortcode"

== Frequently Asked Questions ==

= How do I include a page excerpt? =

Firstly you will need to add support for excerpt for your pages. You can either you this by using the [add_post_type_support()](http://codex.wordpress.org/Function_Reference/add_post_type_support) function or using a plugin like [Page Excerpt](http://wordpress.org/extend/plugins/page-excerpt/).

You can also use the 'list_pages_shortcode_excerpt' filter to return or customize the excerpt for specific pages. The following example:
`<?php
function my_list_pages_shortcode_excerpt( $excerpt, $page, $depth, $args ) {
	return $excerpt . '...';
}
add_filter( 'list_pages_shortcode_excerpt', 'my_list_pages_shortcode_excerpt', 10, 4 );
?>`

You can then include the excerpt via your shortcode.
`[list-pages excerpt="1"]`

== Changelog ==

= 1.7.4 =

* Fix fatal error: validate_list_type() needs to be public!

= 1.7.3 =

* Use PHP7 constructors.
* Validate list type and convert `<li>` tags if not `<ul>` list type.
* Checked WordPress 4.4.2 compatibility.

= 1.7.2 =

* Add short code arguments to the shortcode_list_pages_before/after actions.
* Checked WordPress 4.2 compatibility.

= 1.7.1 =

* When no list type specified don't wrap in list tags.
* Update List_Pages_Shortcode_Walker_Page class with changes made to the WordPress Walker_Page class.
* Checked WordPress 3.9 compatibility.

= 1.7 =

* Add 'list-pages-shortcode' class to all lists.

= 1.6 =

* Add default arg values to start_el() Walker method. Props eceleste.
* Added `shortcode_list_pages_before` action.
* Added `shortcode_list_pages_after` action.
* Added `list_pages_shortcode_item` filter.
* Allow specifying of `post_type`.

= 1.5 =

* Added support for showing excerpt `[list-pages excerpt="1"]`.
* Allow filtering of excerpt output using 'list_pages_shortcode_excerpt' filter.
* Added support for outputting as ordered list `[list-pages list_type="ol"]`.
* Allow HTML in 'title_li' attribute.

= 1.4 =

* Added support for 'post_status'.

= 1.3 =

* Added 'shortcode_list_pages_attributes' filter. Useful if you need to tweak any attributes based on context or current post type.
* Allow 'child_of' to be overridden by shortcode parameter.

= 1.2 =

* Added shortcode support for 'exclude_current_page' parameter.
* Added support for extra wp_list_pages() parameters: include, sort_order, meta_key, meta_value and offset.

= 1.1 =

* Added 'shortcode_list_pages' filter.
* Added [sibling-pages] shortcode.

= 1.0 =

* First release.

== Upgrade Notice ==

= 1.7.2 =
Add short code arguments to the shortcode_list_pages_before/after actions.

= 1.7.1 =
Update List_Pages_Shortcode_Walker_Page class with changes made to the WordPress Walker_Page class.

= 1.7 =
Add 'list-pages-shortcode' class to all lists.

= 1.6 =
Added `shortcode_list_pages_before` and `shortcode_list_pages_after` actions and `list_pages_shortcode_item` filter.

= 1.5 =
Added support for showing excerpt and filtering of excerpt output using 'list_pages_shortcode_excerpt' filter. Added support for outputting as ordered list.

= 1.4 =
Added support for 'post_status'.

= 1.3 =
Added 'shortcode_list_pages_attributes' filter and allow 'child_of' to be overridden by shortcode parameter.

= 1.2 =
Added support for extra wp_list_pages() parameters: include, sort_order, meta_key, meta_value and offset.

= 1.1 =
Added 'shortcode_list_pages' filter and [sibling-pages] shortcode.
