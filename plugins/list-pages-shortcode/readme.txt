=== Plugin Name ===
Contributors: aaron_guitar, husobj
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=aaron%40freshwebs%2enet&item_name=Fotobook%20Donation&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: shortcodes, pages, list pages, sibling pages, child pages, subpages
Requires at least: 2.5
Tested up to: 3.1.3
Stable tag: 1.3

Introduces the [list-pages], [sibling-pages] and [child-pages] shortcodes for easily displaying a list of pages within a post or page.

== Description ==

Introduces the [list-pages], [sibling-pages] and [child-pages] [shortcodes](http://codex.wordpress.org/Shortcode_API) for easily displaying a list of pages within a post or page.  Both shortcodes accept all parameters that you can pass to the [`wp_list_pages()`](http://codex.wordpress.org/Template_Tags/wp_list_pages) function with the addition of a class parameter.

= Usage =

*List pages sorted by title*

`[list-pages sort_column="post_title"]`

*List pages but exclude certain IDs and set the class of the list to "my-page-list"*

`[list-pages exclude="17,38" class="my-page-list"]`

*List the current page's children, but only show the top level*

`[child-pages depth="1"]`

*List the current page's siblings and their subpages*

`[sibling-pages depth="2"]`

= Please Note =

The default values are the same as for the [`wp_list_pages()`](http://codex.wordpress.org/Template_Tags/wp_list_pages) function except for title_li which defaults to nothing.  If a class is not specified, a default class of either "list-pages", "sibling-pages" or "child-pages" is given to the UL tag.  In addition, the echo parameter has no effect.

== Changelog ==

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

== Installation ==

1. Download and unzip the most recent version of this plugin
2. Upload the list-pages-shortcode folder to /path-to-wordpress/wp-content/plugins/
3. Login to your WP Admin panel, click Plugins, and activate "List Pages Shortcode"