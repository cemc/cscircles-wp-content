=== Plugin Name ===
Contributors: kevinB
Donate link: http://agapetry.net/news/introducing-role-scoper/#role-scoper-download
Tags: restrict, access, permissions, cms, user, members, admin, category, categories, pages, posts, page, Post, privacy, private, attachment, files, rss, feed
Requires at least: 3.0
Tested up to: 3.5.1
Stable Tag: 1.3.61

CMS-like permissions for reading and editing. Content-specific restrictions and roles supplement/override WordPress roles. User groups optional.

== Description ==

Role Scoper is a comprehensive access control solution, giving you CMS-like control of reading and editing permissions.  Assign restrictions and roles to specific pages, posts or categories.  For WP 2.7 to 2.9, use [Role Scoper 1.2.x](http://agapetry.net/downloads/role-scoper_legacy).

<strong>Role Scoper has a big brother!</strong> Are you interested in a friendlier UI, cleaner restriction model with WP Roles integration, custom Visibility and Moderation statuses, bbPress content roles, BuddyPress role groups and professional support? Step up to <a href='http://presspermit.com'>Press Permit</a>.

= How it works: =
Your WordPress core role definitions remain unchanged, and continue to function as default permissions.  User access is altered only as you expand it by assigning content-specific roles, or reduce it by setting content-specific restrictions.

Users of any level can be elevated to read or edit content of your choice.  Restricted content can be withheld from users lacking a content-specific role, regardless of their WP role.  Deactivation or removal of Role Scoper will return each user to their standard WordPress access (but all RS settings remain harmlessly in the database in case you change your mind).

Scoped role restrictions and assignments are reflected in every aspect of the WordPress interface, from front end content and navigation to administrative post and comment totals.  Although Role Scoper provides extreme flexibility and powerful bulk administration forms, basic usage is just a set of user checkboxes in the Post/Page Edit Form.

= Partial Feature List =
* WP roles work as is or can be limited by content-specific Restrictions
* RS roles grant additional Read or Edit access for specific Pages, Posts or Categories
* Define User Groups and give them one or more RS roles
* Can elevate Subscribers to edit desired content (ensures safe failure mode)
* Control which categories users can post to
* Control which pages users can associate sub-pages to
* Specify element(s) in Edit Form to withhold from non-Editors
* Grant Read or Edit access for a limited time duration
* Limit the post/page publish dates which a role assignment applies to
* Customizable Hidden Content Teaser (or hide posts/pages completely) 
* RSS Feed Filter with HTTP authentication option
* File Attachment filter blocks direct URL requests if user can't read corresponding post/page
* Inheritance of Restrictions and Roles to sub-categories / sub-pages
* Default Restrictions and Roles for new content
* Un-editable posts are excluded from the Edit Posts/Pages list
* Optimized to limit additional database queries
* XML-RPC support
* Integrates with the [Revisionary plugin](http://wordpress.org/extend/plugins/revisionary/) for moderated revisioning of published content.
* Supports custom Post Types and Taxonomies (when defined using WP schema by a plugin such as [Custom Post Type UI](http://wordpress.org/extend/plugins/custom-post-type-ui/) 
* Extensive WP-mu support

= Plugin API =
* Abstract architecture and API allow other plugins to define their own data/taxonomy schema and role definitions
* Author provides some [extensions to support integration with other plugins](http://agapetry.net/category/plugins/role-scoper/role-scoper-extensions/)

= Template Functions =
Theme code can utilize the is&#95;restricted&#95;rs() and is&#95;teaser&#95;rs() functions to customize front-end styling.

Other useful functions include users&#95;who&#95;can(), which accounts for all content-specific roles and restrictions.

For more information, see the [Usage Guide](http://agapetry.net/downloads/RoleScoper_UsageGuide.htm) or [Support Forum](http://agapetry.net/forum/).

= Support =
* Most Bug Reports and Plugin Compatibility issues addressed promptly following your [support forum](http://agapetry.net/forum/) submission.
* Author is available for professional consulting to meet your configuration, troubleshooting and customization needs.

== Installation ==

Role Scoper can be installed automatically via the Plugins tab in your blog administration panel.

= To install manually instead: =
1. Upload `role-scoper&#95;?.zip` to the `/wp-content/plugins/` directory
1. Extract `role-scoper&#95;?.zip` into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How can I prevent low-level users from seeing the Roles/Restrictions menus and Edit boxes? =

In your blog admin, navigate to Roles > Options.  In the "Content Maintenance" section, set the option "Roles and Restrictions can be set" to "by blog-wide Editors and Administrators" or "by Administrators only".  Click the Update button.

= How does Role Scoper compare to Role Manager or [Capability Manager](http://wordpress.org/extend/plugins/capsman/)? =

Role Scoper's functionality is entirely different and complementary to RM and CM.  RM/CM do little more than alter WordPress' definition of the capabilities included in each role.  That's a valuable task, and in many cases will be all the role customization you need.  Since RM/CM modifications are stored in the main WordPress database, they remain even if RM/CM is deactivated.

Role Scoper is useful when you want to customize access to specific content, not just blog-wide.  It will work with the WP roles as a starting point, whether customized by RM/CM or not.  To see how Role Scoper's role definitions correlate to your WordPress roles, navigate to Roles > Options > RS Role Definitions in your blog admin.  Role Scoper's modifications remain only while it stays active.

= Why are there so many options? Do I really need Role Scoper? =

It depends on what you're trying to accomplish with your WordPress installation.  Role Scoper is designed to be functionally comprehensive and flexible.  Great pains were taken to maintain performance and user-friendliness.  Yet there are simpler permission plugins out there, particularly if you only care about read access.  Review Role Scoper's feature list and decide what's important to you.

= Why doesn't Role Scoper limit direct access to files that I've uploaded via FTP? =

Role Scoper only filters files in the WP uploads folder (or a subfolder).  The uploads folder must be a branch of the WordPress directory tree.  The files must be formally attached to a post / page via the WordPress uploader or via the RS Attachments Utility.

In your blog admin, navigate to Roles > Options > Features > Attachments > Attachments Utility.

= Where does Role Scoper store its settings?  How can I completely remove it from my database? =

Role Scoper creates and uses the following tables: groups&#95;rs, user2group&#95;rs, role&#95;scope&#95;rs, user2role2object&#95;rs.  All RS-specific options stored to the WordPress options table have an option name prefixed with "scoper&#95;".

Due to the potential damage incurred by accidental deletion, no automatic removal is currently available.  You can use a SQL editing tool such as phpMyAdmin to drop the tables and delete the scoper options.

= With the launch of Press Permit as a professional equivalent, is Role Scoper still supported? =

Yes, at this point I plan to keep Role Scoper compatible with upcoming WP versions and address future bug reports. However, Press Permit will receive support priority and most new functionality will be built around that plugin. This has proven to be a necessary move to fund development at this scale. 

== Screenshots ==

1. Admin menus
2. Role boxes in Edit Post Form
3. Role boxes in Edit Page Form
4. Category Restrictions
5. Category Roles
6. [View an html sample of the Category Roles bulk admin form](http://agapetry.net/demos/category_roles/index.html)
7. [View an html sample of Role Scoper Options](http://agapetry.net/demos/rs-options_demo.htm)
8. [View more screenshots](http://agapetry.net/news/introducing-role-scoper/)

== Changelog ==

= 1.3.61 - 26 Apr 2013 =
* Fixed : If a custom post type is hierarchical, non-Administrators could not create new post if "Lock Top Pages" enabled for any roles
* Fixed : RS roles were ineffective in some situations
* Fixed : Assignment of term to a Media item did not cause it to be included in get_terms query results
* Fixed : Multisite - Database error under some configurations when deleting or removing a user
* Fixed : Multisite - Database error under some configurations when viewing user profile
* Fixed? : PHP warning "mysql_real_escape_string() expects parameter 1 to be string" on post creation / update, under some configurations
* Fixed : PHP warnings on activation
* Lang : Dropped Italian translation due to reported inaccuracy

= 1.3.60 - 23 Jan 2013 =
* Fixed : Non-Administrators could not edit posts unless create_posts capability is defined for post type and included in their role (since 1.3.58)

= 1.3.59 - 14 Dec 2012 =
* Fixed : Could not add/edit media library items on WP 3.5 (since 1.3.58)

= 1.3.58 - 12 Dec 2012 =
* Compat : WP 3.5 - Custom posts could not be created or edited when post type enabled for RS filtering
* Compat : WP 3.5 - edit_posts capability was required to access Edit menu for any post type
* Compat : WP 3.5 - Support usage of the new create_posts capability (new checkbox in Roles > Options > Features > Content Maintenance)
* Fixed : Posts were unreadable if associated with a taxonomy which has no terms, under some configurations
* Fixed : RS Roles assigned to [Anonymous] group were ineffective
* Fixed : XML-RPC post editing by users who are not sitewide Editors failed or caused categories to be dropped
* Fixed : Multisite: when activated on a single site but not network activated, some RS options could not be edited

= 1.3.57 - 10 Oct 2012 =
* Fixed : Fatal error on first-time installation (Call to undefined function cr_role_caps)
* Fixed : XML-RPC publishing error with Windows Live Writer, under some configurations
* Fixed : XML-RPC connection failure with some external apps (including Lightbox NGG Exporter)
* Fixed : RS Roles and Restrictions were not effective for post types or taxonomies with long names (>13 characters for Private Reader role, 20 to 24 for others)
* Fixed : Default Roles were not assigned when post was created by a non-Editor
* Fixed : Incorrect comment counts for non-Editors
* Fixed : PHP 5.4 - warning "Creating default object from empty value" on front end access (sporadically or when internal cache not enabled)
* Fixed : PHP warning for improper is_404() call
* Fixed : Various PHP warnings
* Change : If constant SCOPER_NO_HTACCESS is defined, eliminate all .htaccess file manipulations and rewrite rules insertions
* Change : When deactivating or turning off file filtering, don't alter the .htaccess file if it does not contain a Role Scoper block
* Compat : Relevanssi - teaser text always displayed for all search results (when Hidden Content Teaser enabled)
* Compat : NextGen Gallery - Flash Uploader failure for non-Administrators
* Compat : Revisionary - "Edit Pages" search results were displayed as uneditable under some configurations
* Compat : Revisionary and other plugins which call users_who_can() - invalid empty array response under some configurations

= 1.3.56 - 3 Aug 2012 =

= WP 3.4 / PHP 5.4 =
* Fixed : PHP 5.4 - warnings "Creating default object from empty value" (various symptoms including failed installations)
* Fixed : WP 3.4 - stop using deprecated functions

= Front End =
* Feature : Filter post terms listing (function get_the_terms)
* Fixed : Posts of inappropriate type were included in front-end posts listing under some configurations
* Fixed : Calendar widget was not filtered

= Category Roles / Media Upload =
* Fixed : Files could not be uploaded when editing roles are category-specific
* Fixed : When post authoring capabilities are from category role, files could not be uploaded to a new post prior to saving it
* Fixed : Quick Press did not work when posting capabilities are via Category Role

= Other Fixes =
* Fixed : Other user's unattached uploads were always available in Media Library, regardless of RS option setting
* Fixed : XML-RPC (ScribeFire) posting failure in some configurations, due to PHP warning
* Fixed : SCOPER_NO_ATTACHMENT_COMMENTS constant definition was ineffective for Administrators
* Fixed : If "Users CSV Entry" option enabled, Group Members could not be added via rs_group_members > edit
* Fixed : Categories / terms were filtered even if removed from Realm > Taxonomy Usage
* Fixed : Multisite - Database errors (usually non-displayed and harmless PHP warnings) on first access of any site following creation or RS install
* Fixed : Database errors (usually non-displayed and harmless PHP warnings) on first-time RS installation

= Links / Link Categories =
* Fixed : Link Editor restrictions did not limit category selection
* Fixed : Link Editor role did not permit viewing widget on front end
* Fixed : Non-Editors could not see newly added link in link-manager.php until RS cache is flushed

= Page Editing =
* Feature : New option Roles > Options > Advanced > Page Structure > "no Page Parent filter", means anyone who can edit submit or publish a page can select any parent

= Obscure Changes =
* Feature : SCOPER_PUBLIC_ATTACHMENT_COMMENTS constant definition forces get_comments() inclusion of all comments on attachments to public posts (but not private posts)
* Feature : SCOPER_AUTHORS_ASSIGN_ANY_ROLE constant definition allows authors to assign Editor role for their own posts
* Fixed : Improper filtering of manually constructed post queries that have multiple WHERE clause criteria with one of the criteria "post_status ="

= Plugin Compatibility =
* Compat : Relevanssi - filtering failed due to modified plugin filter API in Relevanssi Free 2.9.15 and Premium 1.8
* Compat : Relevanssi - PHP Warning for non-array under some conditions
* Compat : WPML - improper comment filtering in wp-admin when WPML activated
* Compat : CMS Page Tree View - could not create subpages based on propagating object roles
* Advanced Custom Fields : ACF custom fields were not displed on term edit screen, RS roles and restrictions display wasted space
* Subscribe2 : Subscription category selection checklist was not filtered

= 1.3.55 - 22 Feb 2012 =
* Feature : If jQuery is loaded on front end, hide titles of menus which have no readable items
* Fixed : With Internal Cache enabled, manual calls to ScoperAdminLib::add_group_user() do not fully clear applicable caches
* Fixed : SCOPER_NO_ATTACHMENT_COMMENTS constant definition was ineffective for Administrators
* Compat : Relevanssi - long delay (and site downtime) during post update (since 1.3.53)

= 1.3.54 - 8 Feb 2012 =
* Compat : Events Manager - "Add Recurring Event" button was hidden if Event type not enabled for RS filtering
* Compat : Contact Form 7 - post categories reset to Uncategorized under some configurations
* Compat : Relevanssi - reduce queries by memcaching post data (requires new Relevanassi filter 'relevanssi_results' on $doc_weight)
* Compat : Revisionary - Fatal Error on revision submission for Posts and other types that have related taxonomies (since 1.3.52)
* Compat : Subscribe2 - Subscription categories were not filtered for read access with s2 version 7+

= 1.3.53 - 27 Jan 2012 =
* Compat : Relevanssi - Readable Private pages were not included in search results until Relevanssi re-indexed
* Compat : Relevanssi - RS Reader Restrictions were not applied to search results unless posts also have Private Visibility 
* Compat : Relevanssi - When Hidden Content Teaser enabled and set to "fixed teaser", teased posts had a portion of their content displayed in search results
* Fixed : PHP Warnings on WP 3.3 Front End when logged in as a non-Administrator

= 1.3.52 - 18 Jan 2012 =
* Fixed : Editors could not see other user's Media uploads unless "non-editors see" options were enabled
* Change : Simplify function of SCOPER_NO_ATTACHMENT_COMMENTS constant (For performance, disable filtering for attachment comments.  This filtering normally enables a logged user to see comments on files which are attached to a post he can access based on an RS role assignment.)
* Compat : Revisionary - RS Roles were not applied, under some configurations (related fix in Revisionary to 1.1.9)
* Compat : Revisionary - members of Pending Revision Monitors group do not receive email notifications even though they can publish the post (since 1.3.47)

= 1.3.51 - 20 Dec 2011 =
* Compat : NextGEN Gallery - non-Administrators could not upload images with NGG 1.9.x
* Fixed : Default category is always stored when Category Restrictions prevent user from selecting some categories
* Fixed : "Add New" buttons in wp-admin were not removed under some configurations
* Perf : When Role Date Limits or Content Date Limits enabled, supplemental query caused full scan of roles table

= 1.3.50 - 15 Nov 2011 =
* Fixed : Fatal error in wp-admin when Revisionary active but not initializes after Role Scoper

= 1.3.49 - 15 Nov 2011 =
* Fixed : Fatal error in wp-admin when activated alongside an old version of Revisionary

= 1.3.48 - 13 Nov 2011 =
* Fixed : Fatal error "Call to undefined function rvy_get_option" when updating a page under some conditions

= 1.3.47 - 11 Nov 2011 =

= Front End =
* Fixed : Tag filtering did not support hide_empty=0 argument in get_tags()
* Fixed : Don't block access to attachment templates (?attachment_id=) for unattached uploads unless SCOPER_BLOCK_UNATTACHED_UPLOADS is defined

= Admin - Misc. =
* Fixed : Nav Menu editing based on Nav Menu Manager role cleared theme locations menu selections for uneditable menus
* Fixed : Media Library items could not be deleted based on a Page-specific role assignment
* Fixed : Bulk Role Editor only deleted roles for one selected user/group on each update
* Fixed : Invalid edit link for "DEFAULTS for new" in bulk Roles / Restrictions admin form
* Fixed : Non-Administrators can't access /wp-admin/, under some configurations

= Multisite =
* Fixed : Appearance menu items hidden from super administrator on Multisite installations
* Fixed : On Multisite installations with enabled, removal of a user from a site also removed them from network-wide groups

= Post Edit Form =
* Fixed : Javascript on "Edit Posts" / "Edit Pages" was broken for non-Administrators under some configurations
* Fixed : Default Object Roles (as specified in Roles > Pages, etc.) were not retained upon saving a new post
* Fixed : Page Associate role for specific pages did not allow Page Authors to save a new post with specified parent
* Fixed : Unfiltered html capability could not be granted via content role

= Edit Posts Listing =
* Fixed : Posts/Pages could not be bulk-edited or bulk-trashed by non-Administrator when access is based on a Post-assigned editing role
* Fixed : Pages and custom post types could not be Quick-edited by non-Administrator when user relies on a Page-assigned editing role

= User Groups =
* Fixed : When creating or editing a Role Group, user search always returned all users
* Fixed : Group membership cache was not cleared after removal of user from group
* Fixed : Group membership requests and recommendations could not be removed by non-Administrators
* Fixed : Group-specific Managers could not add/remove Group Moderators

= PHP Warnings =
* Fixed : PHP Warning for DB error on User Profile
* Fixed : PHP Warning when saving a post with non-hierarchical terms
* Fixed : PHP Notices in Dashboard QuickPress, Quick Edit and Revisionary "Publishers to Notify" metabox

= Plugin Compat =
* BuddyPress - Filter Activity Stream for Reader-restricted posts
* Revisionary 1.1.7 compatibility
* Revisionary - Support option to prevent Revisors from editing other users' revisions
* Revisionary - better error message when a Revisor attempts to Quick-Edit a page which they cannot fully edit
* Role Scoping for NextGEN Gallery : non-Editors could change Gallery Author
* Role Scoping for NextGEN Gallery : DB error when a non-Administrator adds new gallery, if Revisionary also active

= 1.3.46 - 18 Aug 2011 =
* Fixed : Non-Administrators could not edit/delete attached uploads (since 1.3.43)
* Fixed : Non-Administrators could not edit Navigation Menus based on Nav Menu Manager role assignment
* Fixed : Add New Post link not displayed if all editing Post Roles default-restricted and user has a qualifying Default Post Role assignment
* Fixed : If Media Library filtering is disabled via SCOPER_ALL_UPLOADS_EDITABLE constant, listing in Post Edit Form popup was still filtered
* Fixed : Various PHP Warnings / Notices
* Compat : WPML 2.3.x - Post Edit Form showed duplicate and/or foreign checkboxes in some situations (for non-Administrator) 
* Feature : Support 'required_operation' argument in get_terms(), forces term filtering to require 'read' or 'edit' meta capability on one or more related posts
* Feature : Support 'is_term_admin' argument in get_terms(), forces requirement / non-requirement of $taxonomy->manage_terms capability for term filtering
* Feature : Support 'rs_no_filter' argument in get_terms()

= 1.3.45 - 10 Aug 2011 =
* Fixed : Propagated object roles were lost on autosave
* Fixed : Propagated roles were not deleted when parent role assignment changed from "for page and subpages" to "for page"
* Fixed : Propagated restrictions were not deleted when parent restriction changed from "for page and subpages" to "for page"
* Fixed : Comments were editable / approvable by any user who could edit the associated post, regardless of moderate_comments capability
* Fixed : Terms could not be managed based on term-assigned Manager role.
* Fixed : Dashboard "Right Now" category count was not filtered
* Fixed : Image / attachment access fails due to corrupted uploads/.htaccess file, under unusual editing conditions
* Change: Further safeguard against corruption of main .htaccess
* Perf  : Avoid redundant regeneration of uploads/.htaccess when attaching a file in Post Edit Form
* Perf  : Now fully utilizing WP_Comment_Query hooks introduced in WP 3.1
* Perf  : Now fully utilizing WP_Term_Query hooks introduced in WP 3.1. To revert to previous filtering, define('SCOPER_LEGACY_TERMS_FILTER');
* Perf  : Eliminated several redundant DB queries
* Perf  : Removed some extra wp-admin query filtering which is no longer necessary with WP > 3.1.  To revert to previous filtering, define('SCOPER_LEGACY_HW_FILTERS');

= 1.3.44 - 22 July 2011 =
* Fixed : Template calls to get_comments() were not filtered to match post visibility (since 1.3.43)
* Fixed : Hidden Content Teaser triggered a PHP Notice

= 1.3.43 - 19 July 2011 =
* Compat : WP 3.2 - Recent Comments widget was not filtered based on RS Restrictions / Roles
* Fixed : "Add New" menu links were not displayed for Subscribers with Category-assigned Contributor/Author/Editor roles (since 1.3.42)
* Fixed : Dashboard Screen Options were not applied (since 1.3.41)
* Fixed : Default term selection was forced for hierarchical custom taxonomies even if taxonomy not enabled for RS filtering
* Fixed : Default term selection was not forced for hierarchical custom taxonomies, when taxonomy enabled for RS filtering and post edited by logged Administrator
* Fixed : Hierchical custom taxonomy terms unselectable for non-Administrator when taxonomy is not enabled for RS filtering
* Fixed : Users could not add/edit Nav Menus based on custom addition of edit_theme_options capability into WP role definition
* Fixed : File Attachments Utility non-functional on Multisite
* Fixed : In Multisite Role Options, bad link to Attachments Utility
* Fixed : Bad links to General Roles from Realm tab in Multisite Network Role Options
* Fixed : Hidden Content Teaser options form forced focus to different textbox on Firefox
* Compat : Revisionary - Revisors could not upload images

= 1.3.42 - 29 June 2011 =
* Compat : WP 3.2 - Current comments not displayed for single custom post on front end
* Fixed : If a file or image is attached to more than one protected post, File Filtering may prevent qualified users from viewing it
* Fixed : "Add New" menu item was displayed even if Default Restrictions prevent logged user from editing new post following creation (to restore previous behavior, define constant 'SCOPER_LEGACY_MENU_FILTERING'
* Fixed : Long delay / timeout when adding a new post/page, under some configurations
* Fixed : When a custom post has attachments, Gallery tab is not displayed in "Add Media" popup
* Fixed : On Multisite, site Administrator role does not enable Nav Menu creation
* Fixed : Attachment editing failed when logged user is not a sitewide Editor or Administrator (since 1.3.41)

= 1.3.41 - 23 June 2011 =
* Fixed : Editors could not edit/attach other users' unattached uploads
* Fixed : Custom Posts not editable by Editors if RS filtering disabled in Roles > Options > Realm
* Fixed : Script recursion and timeout on post creation by non-Administrator under some configurations
* Compat : WP 3.2 - Work around permissions issue when Posts/Pages menu contains only one submenu item
* Compat : WP 3.2 - "Add New" buttons were not hidden when appropriate
* Compat : NextGen Gallery - Flash uploader did not work when RS activated (since 1.3.35)
* Compat : Advanced Custom Fields - metaboxes not visible in post edit forms
* Compat : Revisionary - private pages could be directly edited by users with a Page Revisor role assigned directly for the page
* Change : some changes to jQuery syntax (for forward compat), for disabling category checkboxes in the Edit Post when user can't remove a currently stored category

#### 1.3.40 - 6 June 2011

= Category / Tag Editing =
* Fixed : Non-Administrators could not edit categories or tags (since 1.3.35)
* Fixed : Tags were not displayed in Edit Posts listing for non-Administrators
* Fixed : New child terms in custom taxonomies were excluded from terms listing until RS re-activation, in some situations

= Plugin Compatibility =
* Compat : W3 Total Cache: .htaccess corruption (and 500 Error) when "HTTP Authentication Request in RSS Feed Links" option enabled
* Compat : Mingle plugin (and others which force setting of user object before all plugins are loaded) made RS inoperative
* Compat : WPML - category selection checkboxes from other languages included in Post Edit form for non-Administrators

= File Filtering =
* Fixed : File Filtering - on Multisite, adding a new site created a security loophole to protected files, until plugin reactivated or File Filtering toggled off/on
* Fixed : Invalid .htaccess contents (and 500 Error) when "HTTP Authentication Request in RSS Feed Links" option enabled and default .htaccess has <IfModule> statement above default WP block

= Page / Post Editing =
* Fixed : When Page Editor and Author roles are Restricted by default, new pages saved by non-Administrator set to "Pending Review" on first Publish attempt
* Fixed : When all Page editing roles are Restricted by default, new pages are not editable by non-Administrator creator
* Fixed : Could not create a new post based on an editing role assigned for a non-hierarchical custom taxonomy

= Role Groups =
* Fixed : Default Groups could not be defined on Multisite installations
* Fixed : Default Groups Edit Form did not refresh following update
* Fixed : Bad link to Role Groups edit form from group selection checklist when Sitewide Groups enabled on a Multisite Installation
* Fixed : "Eligible Groups" count above groups checklists wrong under some configurations

= Miscellaneous =
* Fixed : Fatal Error "undefined method stdClass::merge_scoped_blogcaps()" under some configurations 
* Fixed : Needless ALTER TABLE statements, PHP warnings on plugin activation
* Fixed : PHP Warning "Invalid argument supplied for foreach()" with some custom taxonomy configurations
* Change : Change all require and include statements to absolute path to work around oddball servers that can't handle relative paths

= 1.3.39 - 16 May 2011 =
* Fixed : Another one-line change to eliminate a Fatal Error on WP 3.2 Beta

= 1.3.38 - 13 May 2011 =
* Fixed : One-line change to eliminate a Fatal Error on WP 3.2 Beta

= 1.3.37 - 11 May 2011 =
* Fixed : Fatal Error on front end access when a Post Tag is present in displayed Nav Menu
* Change : Force version update to correct erroneous directory structure in 1.3.36 zip archive

= 1.3.36 - 11 May 2011 =
* Compat : Role Scoping for NextGEN Gallery and custom template code using $current_user->groups, $current_user->blog_roles or $current_user->term_roles was broken by 1.3.35

= 1.3.35 - 11 May 2011 =
* Fixed : Empty Categories/Terms were hidden from Nav Manus for non-Administrators (unless they had a non-empty sub-term)
* Fixed : Non-Administrators could not edit Navigation Menus based on Nav Menu Manger role assignment
* Feature : Navigation Menus option "List only user-editable content as available items" also filters editing/deletion/ordering of existing menu items 
* Fixed : Role Duration Limits were not applied to General Roles or Term Roles if Internal Cache enabled
* Compat : Don't define pluggable function set_current_user(), to avoid conflict with other plugins that define it
* Compat : Don't cast global $current_user as a custom subclass, to avoid conflict with other plugins that do so
* Compat : Put RS menu links in Users menu if OZH Admin Menus plugin active or SCOPER_FORCE_USERS_MENU constant defined

= 1.3.34 - 28 Apr 2011 =
* Compat : Edit Flow - if 'post_status' taxonomy enabled for RS Filtering (Roles > Options > Realm), editing a Private post forced it to Public visibility
* Fixed : Non-Administrators could not edit categories if Post Tags enabled for RS filtering
* Fixed : Links were not displayed to non-Administrators if multiple sort fields specified in get_bookmarks() call

= 1.3.33 - 26 Apr 2011 =
* Fixed : Links Widgets and other get_bookmarks() output was hidden from non-Administrators on the front end (since 1.3.30)

= 1.3.32 - 26 Apr 2011 =
* Fixed : Filtering could not be disabled for some Custom Post Types / Taxonomies (via Roles > Options > Realm)

= 1.3.31 - 25 Apr 2011 =
* Fixed : Custom taxonomies could not be fully disabled, causing various access failures (including non-display of Edit Flow post status in Publish metabox)
* Change : Default to late initialization, for compatibility with plugins/themes which register post types / taxonomies without specifying early execution priority.
* Change : When a custom type/taxonomy is auto-enabled for RS filtering, display a dashboard warning to Administrator, explaining the need to assign type-specific roles.

= 1.3.30 - 25 Apr 2011 =
* Compat : Fatal error when another plugin defines pluggable function 'set_current_user' (call to undefined function plural_name_from_cap_rs)

#### 1.3.29 - 21 Apr 2011

= Misc. Fixedes =
* Fixed : non-Administrators cannot modify moderate/edit comments with WP 3.1
* Fixed : Role Groups edit form unavailable from WP 3.0 Multisite "Super Admin" menu (since 1.3.28)
* Fixed : Grammatically customized capability names were not mirrored in RS Role Definitions
* Fixed : Internal Cache was unavailable for new WP 3.1 installations
* Fixed : Nav Menu Managers could not add new menu items if they lacked a site-wide edit_posts capability
* Fixed : Bulk Admin forms for Taxonomy Roles / Restrictions did not include roles for associated post types if association is defined by both register_taxonomy() and register_taxonomy_for_object_type()
* Fixed : PHP Warnings on admin dashboard (since 1.3.28)
* Change : Convert all RS database tables to utf8 collation to eliminate query errors on some servers
* Lang : Translation string for Assigner roles

= File Attachment Filtering =
* Fixed : File Attachment Filtering rules were not updated for new attachments, under some configurations
* Fixed : Manually resized images were not protected by File Filtering

= Media Library =
* Fixed : Disabling Media Library filtering via SCOPER_ALL_UPLOADS_EDITABLE constant definition broke Media Library paging for Contributors
* Fixed : Media Library filtering was not totally disabled by SCOPER_ALL_UPLOADS_EDITABLE constant definition

= Link Access =
* Feature : Front-end visibility of Links can be restricted or assigned per-category
* Fixed : When creating or editing a link, no selectable link categories for non-administrators (since 1.3.28)
* Fixed : Link Category Manager role assignments were ineffective
* Fixed : Link Category listing in edit-tags.php did not refresh after editing a name / description

= Plugin Compatibility =
* Compat : Relevanssi - Fatal Error on tag search under some configurations
* Compat : CMS Tree Page View (and other plugins which create posts) - propagating role assignments were not applied to new pages
* Compat : WP-SNAP (and other plugins which apply the posts_request filter without passing wp_query object) - PHP Warning for missing argument 2
* Compat : Revisionary - users who can edit pages but not posts were not available for membership in Pending Revision Monitors group
* Compat : Role Scoping for NGG - Nuisance error message when installing / updating any plugin

#### 1.3.28 - 18 Mar 2011

= WP 3.1 =
* Fixed : Role Options, Role Defaults menu items were not available on 3.1 multisite
* Feature : Filter "Add New" links out of WP Admin bar if user lacks site-wide capability

= Navigation Menus =
* Feature : Nav Menu Manager role can be assigned to users who do not have edit_theme_options capability
* Feature : Nav Menu Manager role can be assigned site-wide
* Feature : For Nav Menu Management, option to list only user-editable content as available items
* Fixed : Nav Menus displayed categories even if no posts readable

= Miscellaneous Fixedes =
* Fixed : When editing is based on category, could not upload files into edit form prior to saving post 
* Fixed : Non-administrators could not add a non-hierarchical custom term to post if taxonomy is included in post type registration
* Fixed : Internal Cache (and therefore permissions) did not refresh when an existing user's role is changed
* Fixed : Default roles were not applied at Page / Post creation
* Fixed : In wp-admin, Page menu not visible while editing a post if page editing access is not site-wide
* Fixed : Duplicate entries in Author dropdown if RS editing roles have been assigned to WP role groups
* Fixed : Did not support meta_key without meta_value in get_pages call (or vice versa)

= Category Listing (front end) =
* Fixed : New categories were not listed until Role Scoper re-activation, under some configurations
* Change : Support post_type argument in get_terms / wp_list_terms function call

= Category Management =
* Feature : Category Assigner role does not grant post creation/editing capabilities but specifies categories which are assignable to any user-editable post regardless of post ownership or status
* Fixed : Term-specific management roles did not grant editing access
* Change : Term-specific management role also grants ability to create child terms
* Fixed : Non-administrators could not delete categories

= Plugin Compatibility =
* Compat : NextGEN Gallery - with versions 1.7+, error when uploading images as a non-Administrator
* Compat : Grand Flash Gallery - error when uploading images as a non-Administrator
* Compat : More Types: support late registration of post types by automatically forcing RS to initalize later
* Compat : Simple Fields plugin - non-Administrators could not use custom field file uploader
* Compat : Quick Post Widget - categories were not filtered
* Compat : When plugin or theme code forces autologin, RS filtering does not reflect it until the next http request

= 1.3.27 - 19 Jan 2011 =
* Fixed : Hidden Content Teaser - private pages were not included for teasing to anonymous reader
* Fixed : Hidden Content Teaser - pages with Reader restriction were not flagged in page listing

= 1.3.26 - 17 Jan 2011 =
* Fixed : User Search on Role Group creation/edit form did not work (since 1.3.23)
* Fixed : Group Search on User Profile form did not work (since 1.3.23)

= 1.3.25 - 14 Jan 2011 =
* Fixed : Hidden Content Teaser did not include private posts or pages, regardless of "hide private" setting (since 1.3.2)

= 1.3.24 - 14 Jan 2011 =
* Fixed : On Page Edit form, invalid blank item in Page Parent dropdown for Non-Editors with a General Role of Page Contributor / Author, caused new pages to be saved as top level 

= 1.3.23 - 12 Jan 2011 =
* Fixed : Under some configurations, Database Error when attempting to update a subpage
* Fixed : Find Posts results in Media Library were not filtered
* Fixed : Media Library count was not filtered
* Fixed : Various PHP warnings/notices (visible in wp-admin when running with WP_DEBUG enabled)
* Compat : Replace function calls deprecated by WP 3.1

= 1.3.22 - 7 Jan 2011 =
* Fixed : Under some configurations, Database Error when attempting to update a subpage
* Compat : Simple:Press - PHP warning (database error) on forum page for logged non-Editors
* Fixed : Private pages were still accessible by direct URL (with teaser imposed) if Hidden Content Teaser enabled with "hide private posts" option enabled
* Fixed : PHP Notice "Undefined property: stdClass::$src_name" under some configurations
* Lang : Revised Italian Translation (Alberto Ramacciotti - http://obertfsp.com)

= 1.3.21 - 23 Dec 2010 =
* Fixed : Role assignment metaboxes in post edit form did not indicate implicit role assignments based on Category Roles

= 1.3.20 - 18 Dec 2010 =
* Fixed : Bulk assignment of Category or Taxonomy Retrictions failed when specified "for selected and sub-categories" or "for sub-categories"

= 1.3.19 - 17 Dec 2010 =
* Compat : NextGEN Gallery - fatal error on file upload attempt

= 1.3.18 - 15 Dec 2010 =
* Fixed : Users designated as Group Administrator for a specific group could not edit the group
* Fixed : Currently assigned Group Administrators did were not indicated by checkbox
* Fixed : Custom post types were included in front-end Pages listing under some configurations

= 1.3.17 - 11 Dec 2010 =
* Fixed : Template functions is_teaser_rs() and is_restricted_rs() caused fatal error
* Fixed : Comments on teased posts were included in Recent Comments widget

= 1.3.16 - 8 Dec 2010 =
* Fixed : Roles, Restrictions menus not visible on Plugins page

= 1.3.15 - 8 Dec 2010 =
* Fixed : Spam-tagged comments were linked in Recent Comments widget

= 1.3.14 - 6 Dec 2010 =
* Fixed : Page Associate role assignments failed to make pages available for selection as Page Parent 
* Fixed : Trashed posts were included in Post/Page Restrictions bulk editor
* Change : Include Role Scoper help links within wp-admin for Users, Media and RS Options pages

= 1.3.13 - 3 Dec 2010 =
* Fixed : Activation of "Sync WP Editor" option in RS Role Defs caused capabilites of other post types to be stripped out of WP Editor role definition 
* Compat : Revisionary - If another plugin (Events Manager) triggers a secondary edit_posts cap check when a Revisor attempts to edit another user's unpublished post, a pending revision is generated instead of just updating the unpublished post

= 1.3.12 - 3 Dec 2010 =
* Fixed : Page Parent automatically changed (possibly to an invalid selection) when a page is edited by a limited user who cannot fully edit current parent
* Fixed : Category Manager restrictions were not applied for WP Editors
* Fixed : "Navigation Menus" checkbox displayed inappropriately in Roles > Options > Realm > Taxonomy Usage
* Fixed : Invalid filtering results after other template/plugin code manually changed current user via call to wp_set_current_user
* Change : Default to requiring site-wide Editor or Administrator role for role/restriction assignment
* Compat : Revisionary - Was causing duplicate checkboxes for Pending Revision Notification in some cases
* Compat : Revisionary - Some qualifying users were not included in Pending Revision Notification checkboxes if internal cache was disabled
* Compat : Revisionary - All authors to see and edit revisions submitted on their posts (unless HIDE_REVISIONS_FROM_AUTHOR is defined)

= 1.3.11 - 26 Nov 2010 =
* Fixed : Page editing by user lacking site-wide Page Editor role caused page parent to revert to Main (since 1.2.9)
* Fixed : Non-administrators could not modify or request group membership via Ajax UI (since 1.2.9)

= 1.3.10 - 25 Nov 2010 =
* Compat : More Taxonomies - Category Roles could not be managed because of bug in More Taxonomies (and possibly other plugins) where category taxonomy is overriden without setting it public

= 1.3.9 - 24 Nov 2010 =
* Fixed : Non-administrators could delete users with a higher role level
* Fixed : When viewing a page, hidden categories are listed
* Fixed : Site-wide edit_theme_options capability was not honored for Nav Menu management by non-Administrators
* Feature: Support menu-specific restrictions for Nav Menus
* Feature: Constant definition 'SCOPER_NO_COMMENT_FILTERING' honored in backend for users lacking site-wide moderate_comments capability
* Fixed : Scheduled posts included in front-end listing for logged Administrators, Editors
* Fixed : "Add New" button was displayed on Edit Posts form even for users lacking a qualifying WP / General role
* Fixed : Category Restrictions were not applied to a post if it also had a tag in a non-hierarchical (tag-type) taxonomy 

= 1.3.8 - 19 Nov 2010 =
* Fixed : Improper blocking of content for custom post types not selected for RS filtering (Roles > Options > Realm > Post Type Usage)

= 1.3.7 - 18 Nov 2010 =
* Fixed : On PHP 4 sites, logged non-administrators had no read/edit access based on WP role
* Fixed : Various PHP Notices / Warnings

= 1.3.6 - 17 Nov 2010 =
* Fixed : Post previews for qualified users failed with "Not Found" error
* Fixed : For template calls to get_terms() / get_categories() / wp_list_categories(), include argument was not handled correctly (since 1.0)
* Fixed : Fatal Error for logged Administrators (undefined method merge_scoped_blogcaps) in some cases
* Fixed : Reader role restrictions not applied in some situations

= 1.3.5 - 13 Nov 2010 =
* Fixed : Post Author dropdown was limited to Editors and Administrators if "Filter Users Dropdown" option enabled
* Fixed : Category Manager role was not applied to new subcategories when assigned for "parent and sub-categories"
* Fixed : Invalid posts filtering when template invokes two or more query_posts calls with category_name argument
* Fixed : Front-end attachment queries returned only attachments authored by logged user
* Fixed : With "default new posts to private visibility" enabled, existing posts also forced to private when edited
* Fixed : Link Admin roles / restrictions were not correctly applied per-category
* Change : If a limited Link Editor submits new link without selecting a category, default to a selectable category
* Change : Separate role definitions for link editing, link category management for per-category assignment
* Fixed : Posts / Comments menu sometimes displayed inappropriately for content-specific editors
* Change : Display user_login for role assignment and group membership administration, even if user has set a different display name
* Compat : Revisionary - Dashboard Right Now count did not include revisable posts/pages (since 1.3.3)
* Compat : Revisionary - Revisor role now available by default for direct post/page assignment; allows editing others' revisions

= 1.3.4 - 5 Nov 2010 =
* Compat : Revisionary - Posts were blocked from front-end display if both Role Scoper and Revisionary enabled (since 1.3.3)

= 1.3.3 - 5 Nov 2010 =
* Compat : Smart YouTube (and other plugins that execute a posts query joined to comments table) - database error
* Compat : Revisionary - Pending count and links were not displayed in Dashboard Right Now or Edit Posts listing if revisor capability is by term or object role assignment
* Compat : Revisionary - Non-Administrators receive Not Found error for revision preview

= 1.3.2 - 3 Nov 2010 =
* Fixed : Post counts and other dashboard items were not filtered for non-Administrators (since 1.3.1)
* Compat : Revisionary - users could not submit or edit revisions based on Contributor role direct-assigned for post

= 1.3.1 - 2 Nov 2010 =
* Compat : Role Scoping for NextGEN Gallery - Gallery Authors could not manage a gallery after creating it

#### 1.3 - 2 Nov 2010

= File Attachment Filtering =
* Feature : For File Filtering, ability to force regeneration of access keys and rewrite rules via utility URL
* Fixed : Infinite redirection if file keys in .htaccess (uploads folder) were manually modified

= Multisite =
* Fixed : With Multisite, some Default Site Options could not be modified
* Fixed : On WP 3.0 Multisite installations, all files in wp-content/cache get deleted, clashing with other plugins such as WP Super Cache
* Fixed : On Multisite installations, could not save changes to Default Sitewide Options

= Post Editing =
* Change : In Post Edit Form, currently assigned categories and other hierarchical terms shown with disabled checkboxes if current cannot edit in the term
* Fixed : Default Post/Page Roles were assigned to existing posts at post edit (since 1.1.3, now assigned only to new posts)
* Fixed : Page Parent dropdown filtering hid published pages from Contributors if none editable
* Fixed : Page Structure option "Page Authors, Editors and Administrators" did not work (prevented all non-Administrators from editing top-level pages)
* Fixed : Uploaded files could not be edited / deleted in some cases
* Fixed : With "Users CSV Entry" enabled, checkboxes for existing role assignments were not displayed in some cases 
* Fixed : Display of edit link in Edit Posts/Pages listing did not reflect capability requirements imposed by other plugins 

= Custom Post Types / Taxonomies =
* Fixed : Previous, Next links on single post page for custom types included unpublished posts (since 1.0.0)
* Compat : More Taxonomies, More Types now supported by automatically forcing RS to initalize later
* Compat : Late registration of custom types/taxonomies can be supported by forcing RS to initialize later: define( 'SCOPER_LATE_INIT', true );
* Change : Dropped support for "WP" Role Type (means scoper-assigned roles and restrictions must be object type-specific)

= Performance Enhancement =
* Perf : Eliminate extensive delays on some sites using Page Roles on hundreds of pages (when filtering posts, pre-execute a problematic subquery) 
* Perf : Eliminate superfluous query clauses for better wp-admin/edit.php performance
* Fixed : Dozens of PHP Notices / Warnings for undeclared variables or missing array indexes
* Perf : Extensive code refactoring to reduce memory usage

= Category / Term Management =
* Fixed : Category-specific assignment of Category Manger role (and management roles for custom terms) was not applied correctly with WP 3.0
* Fixed : Bulk Administration of Term Roles / Restrictions was too narrowly limited for non-Administrators (since 1.2.?)
* Fixed : Category Manager restrictions were not enforced against users with site-wide manage_categories capability
* Fixed : Category creation was not appropriately restricted with WP 3.0
* Feature : Hide the "Add New Category" UI if logged user does not have term management role site-wide

= User Groups =
* Fixed : Administrators could not modify User Group name or description (since 1.2)
* Change : User search results for group membership show user display name (rather than login)

= Plugin Compatibility =
* Compat : Revisionary plugin - Pending Revisions were not included in Edit Posts listing for non-Administrators when editing access to published post is affected by category-specific roles or restrictions (since 1.0)
* Compat : Store persistent cache to a subdirectory to avoid clashing with other plugin use of wp-cache (Multisite usage was wiping WP Super Cache .htaccess file)
* Compat : New "scoper_access_name" hook to allow 3rd party plugins to force a custom URI to be treated with wp-admin / front-end filtering

= Misc. =
* Feature : Nav Menu Manager role assignments per-menu
* Change : For Role Date Limits settings, show / hide the entry boxes if "keep stored setting" checkbox is toggled 
* Change : Auto-flush the persistent cache more aggressively on role / restriction modification
* Lang : updated .pot file
* Change : Raise minimum WP version to 3.0

#### 1.2.8 RC9 - 6 Sep 2010

= Custom Types / Taxonomies =
* Feature : New simple checklist enables/disables RS usage of each defined post type and taxonomy 
* Fixed : Hidden Content Teaser could not be enabled for custom post types
* Fixed : Category Role usage was not available for custom post types
* Fixed : Role assignment metaboxes did not display for custom types
* Fixed : Custom Post Type menus were not displayed based on Object Role assignment
* Fixed : Newly enabled custom roles were not handled correctly by RS because initial save following activation de-associated their role capabilities (under RS Role Defs tab)
* Fixed : Some RS Options (including custom post type / taxonomy role usage) did not store correctly with WP Multisite
* Fixed : Was not requiring type-specific editing capabilities for term selection in post edit form for custom types
* Fixed : Post Types and Taxonomies disabled via new option checkboxes were not removed from Roles, Restrictions menus
* Fixed : Error when using custom post types with WP 2.9 
* Change : Force type-specific capability_type and caps for all custom post types
* Change : Enable new Post Types and Taxonomies for RS Roles & Restrictions by default
* Change : Support get_pages / list_pages filtering of hierarchical custom post types
* Change : Better support for nonstandard capabilities in custom post type definitions

= File Attachment Filtering =
* Fixed : File Attachment Filter was inactive for installations upgraded to WP 3.0 multisite and still using wp-content/uploads folder
* Fixed : On failed direct file access attempt, any page / term listings on 404 page were not filtered for RS restrictions / roles

= Front End =
* Fixed : Fatal error when manage_categories capability is checked from the front end by template or plugin code

= Post Edit Form =
* Fixed : Post submission categories not filtered when user had category-specific Post Editor role but a General Role of Page Author / Editor (since 1.2)
* Fixed : Implicit role ownership (indicated by coloring in role metaboxes) was not indicacted correctly under some configurations

= Role / Restriction Maintenance =
* Fixed : Category Roles, Category Restrictions bulk admin forms had invalid category edit links
* Fixed : Roles, Restrictions were not displayed on single term edit form
* Fixed : Invalid Roles > Roles submenu displayed if logged user has edit_users capability but not manage_settings capability
* Change : Suppress scroll links in Term Roles / Restrictions bulk admin form if terms total over 300

= Admin - Misc. =
* Feature : Media Library option, for non-Editors, to prevent the inclusion of files uploaded by other users (even if logged user can edit the related post)
* Perf : Unnecessary DB query on post save added needless overhead, caused out of memory error on some configurations (since 1.2)
* Fixed : In admin menus, "Add New" was not properly suppressed in some configurations
* Fixed : Comment listing in wp-admin was not filtered to match post editing access
* Fixed : Custom-defined WP Nav menus were not filtered for RS restrictions / roles
* Fixed : XML-RPC submissions failed for users lacking blog-wide edit_posts capability
* Fixed : On version upgrade from RS < 1.2, groups_rs db table update failed under certain conditions
* Lang : Removed ASCII HTML character codes from Spanish translation (David Gómez Becerril - www.desarrollowebdequeretaro.com)

= Plugin Compatibility =
* Compat : WPML plugin - category names with @lang suffix did not have suffix filtered off
* Compat : Verve Metaboxes - Internal server error when Administrator attempted to add a new custom post type
* Compat : Revisionary - Images attached to published content were not listed in Media Library based on Contributor / Revisor role
* Compat : Edit Flow plugin (Edit Posts / Pages listing filtered for custom status)
* Change : (with Revisionary plugin) Revisor role does not satisfy "Roles and Restrictions can be set" requirement of "site-wide Editor"

= 1.2.7 - 30 June 2010 =
* Fixed : Conflict with Tag Cloud (since 1.2.6) 

= 1.2.6 - 30 June 2010 =
* Fixed : Multibyte string functions used in Role Scoper admin forms caused fatal errors on servers lacking that PHP module
* Fixed : Page Parent filtering was broken for new pages with WP 3.0
* Fixed : If multiple sticky posts exists, all except one were dropped down to non-sticky display position
* Fixed : Various PHP warnings (harmless to normal installations)
* Fixed : "Add New" links were included in Posts, Pages menu if user has an object-specific editing role but lacks the sitewide role required for object creation
* Fixed : Database error when filtering Recent Comments widget (ambiguous reference to post_status)
* Fixed : Query parsing become confused by queries which included a tab character before or after WHERE instead of a space
* Fixed : Auto-drafts were listed in Page Roles, Page Restrictions administration forms with WP 3.0
* Fixed : Category Roles / Restrictions were applied regardless of Realm settings (causing overly restricted read access under some configurations)

= 1.2.5 - 19 June 2010 =
* Fixed : .htaccess file became corrupted on WP-MU versions < 3.0 on plugin re-activation with File Filtering enabled, causing inaccessable site
* Fixed : On WP 3.0, File Filtering was not automatically re-enabled following plugin de-activation, re-activation

= 1.2.4 - 18 June 2010 =
* Fixed : Category or Page listing with include / exclude argument caused PHP error with WP 3.0
* Fixed : Limited users could not use the Media Library based on a Post Author Category Role, and could not upload into the Edit Form until after saving the post
* Compat : My Category Order plugin (and any other plugins or custom queries which pass a child_of argument with nullstring value)
* Fixed : User group could not be removed via User Profile, using jQuery interface
* Fixed : Category Roles link on User Profile was invalid
* Lang : Partial French translation (thanks to Chryjs - http://chryjs.free.fr)
* Lang : Update English .po file via poEdit, add .pot file as generated by wordpress.org

= 1.2.3 - 17 June 2010 =
* Fixed : Non-administrators could not assign post categories correctly with WP 3.0
* Fixed : Custom object types and taxonomies were not recognized (for RS roles and restrictions) under some configurations

= 1.2.2 - 2 June 2010 =
* Fixed : Category filtering for some widgets and plugins (Subscribe2) was broken (since 1.2) 

= 1.2.1 - 2 June 2010 =
* Fixed : Syntax error when attempting to access RS Options (since 1.2)
* Fixed : Blank options area when attempting to access General Options (since 1.2)

#### 1.2 - 2 June 2010

= WordPress 3.0 Compatibility =
* Compat : WP 3.0 elimination of page.php, edit-pages.php, page-new.php broke many aspects of page filtering
* Compat : Support RS Roles, Restrictions for Custom Post Types created via WP 2.9 / 3.0 framework
* Compat : Support RS Roles for Custom Taxonomies created via WP 2.9 / 3.0 framework
* Compat : WP 3.0 Multisite menu items had invalid link
* Fixed : File Filtering did not work on WP 3.0 Multisite
* Fixed : File Filtering did not work on new MU blogs until plugin re-activation or File Filtering re-enable

= New Features =
* Feature : Ajax interface for group membership selection
* Feature : Group membership requests
* Feature : Group membership recommendations (2-tier membership moderation)

= Major Fixedes =
* Fixed : File Filtering was not imposed based on Post/Page Restrictions or Default Category Roles (also required Private visibility)
* Fixed : RS Restrictions and Roles were not applied to Sticky Posts
* Fixed : Attachment filenames with spaces, parenthesis and other special chars caused corrupt or ineffective .htaccess (possibly resulting in Internal Server Error)
* Fixed : Last blog paging link sometimes hidden when Hidden Content Teaser enabled (also caused WP-PageNavi conflict)
* Fixed : With Revisionary (or possibly other plugins) enabled, posts are inappropriately forced into default category in logged user cannot post there.
* Fixed : Custom calls to wp_dropdown_pages (in template or other plugin code) were sometimes filtered inappropriately
* Fixed : On abnormally configured web servers, RS menu links did not work
* Fixed : Private Posts were excluded from Recent Posts widget if Hidden Content Teaser enabled, even if logged user can read the post
* Fixed : On some installations, Page Roles could not be updated correctly following upgrade from older Role Scoper version

= Minor Fixedes =
* Fixed : When previewing a post, non-editors don't see Page or Post listings in sidebar / topbar
* Fixed : Recent Comments widget included comments on unreadable posts, with WP 2.9
* Fixed : Custom WP_PLUGIN_DIR was not supported
* Fixed : In Bulk Object Roles Edit forms, links to edit roles of individual object were broken
* Fixed : RS addition to wp-admin footer forced horizontal scroll bar in IE7
* Fixed : Role Basis settings (User Roles and Group Roles enable / disable) were hidden and unalterable
* Fixed : If Page Reader is enabled as an "Additional Object Role", Private Page Reader also remains captioned as "Page Reader"
* Fixed : If Post Reader is enabled as an "Additional Object Role", Private Post Reader also remains captioned as "Post Reader"
* Fixed : Bad edit link on User Profile where user is a Group Manager for specific group(s)
* Fixed : When scanning Posts/Pages for unregistered attachments, File Attachment Utility did not distinguish broken links
* Fixed : Roles and Restricions menu did not remain collapsed
* Fixed : If redundant Page / Post / Category roles were stored to database, they could only be deleted one at a time (giving the appearance and effect of a failed role deletion)
* Fixed : Javascript error in Page Edit form, failed to set tooltip caption for Page Role checkboxes

= Plugin Compatibility =
* Compat : WP-PageNavi - conflict with paging links, see above
* Compat : Amember - PHP Warning (array_diff_key) after importing users
* Compat : QTranslate - unparsed page titles in Page Parent dropdown
* Compat : Simple Section Nav - children of excluded pages bubbled up to the page menu
* Compat : Reveal IDs plugin wiped out "Groups" column in Edit Users page
* Compat : Role Scoper potentially wiped out other plugin custom columns on Edit Users page

= Other Changes =
* Change : Apply Excerpt Teaser Prefix,Suffix whenever excerpt, pre-more, or first X chars replace content, if SCOPER_FORCE_EXCERPT_SUFFIX is defined.
* Change : When running with WP 3.0, use "Network / Site" terminology in captions
* Change : On WP < 2.9, Roles and Restrictions menus will appear at the bottom of the navigation sidebar
* Lang : Use mb_strtolower() for better multibyte support in translated captions
* Perf : Don't load and initialize Role Scoper on asynchronous dashboard feed calls (WP dev blog, etc.)

Note: Role Scoper was first released as a public beta on 14 May 2008.  Stable release 1.0.0 debuted on wordpress.org on 21 March 2009. 
For an archived change log, see [http://agapetry.net/downloads/RS-readme-archive.txt](http://agapetry.net/downloads/RS-readme-archive.txt)

== Upgrade Notice ==

= 1.3.46 =
Compat: Category selection with WPML 2.3.x; Fixes: Media Library editing for non-Administrators; Menu editing via Nav Menu Manager role; Missing Posts > "Add New" under some configurations; Various PHP Warnings

= 1.3.45 =
Fixes: Propagated Post/Page Roles lost on autosave; Propagated Roles / Restrictions not deleted when propagation turned off; Comments editable by any post Editors w/o moderate_comments cap; Term-assigned Manager role ineffective; Image / Attachment access failure under some configs

= 1.3.43 =
Fixes: Recent Comments widget filtering w/ WP 3.2; "Add New" Post menu links for Subscribers with Category Roles; Dashboard Screen Options; Term filtering and default selection issues; Nav Menu filtering with customized WP roles; File uploads with Revisionary

= 1.3.42 =
Fixes: In WP 3.2, current comments not displayed under custom post; File Filtering: no access when file attached to multiple protected posts; "Add New" menu item sometimes displayed inappropriately; no Gallery tab in "Add Media" popup for custom posts;

= 1.3.41 =
WP 3.2: inability to edit Posts/Pages when user can't add new posts; Fixes: Editors can't edit others' unattached uploads; Custom Posts not editable by Editors if RS filtering disabled; Plugin Compat: Advanced Custom Fields metaboxes in post edit forms; NextGEN Gallery image uploader

= 1.3.40 =
Fixes: Non-Admins can't edit cats/tags or see tags in Edit Posts; New custom tx child terms not in terms listing; 500 Err w/ W3 Total Cache; RS self-disables w/ Mingle plugin; WPML cat checkboxes for other langs; 500 Err with HTTP Auth; File Filtering on Multisite security patch; Default Groups

= 1.3.39 =
Another one-line change to eliminate a Fatal Error on WP 3.2 Beta

= 1.3.38 =
One-line change eliminates a Fatal Error on WP 3.2 Beta

= 1.3.35 =
Fixes conflict w/ plugins which define pluggable functions; Nav Menu filtering Fixedes and enhancements; better OZH Admin Menus compatibility

= 1.3.34 =
Fixes conflict w/ Edit Flow plugin: if 'post_status' taxonomy was enabled for RS Filtering (Roles > Options > Realm), editing a Private post forced it to Public visibility

= 1.3.33 =
Fixed : Links Widgets and other get_bookmarks() output was hidden from non-Administrators on the front end (since 1.3.30)

= 1.3.32 =
Fixed : Filtering could not be disabled for some Custom Post Types / Taxonomies

= 1.3.31 =
All changes relate to Custom Post Types / Taxonomies: better compat with CPT / Taxonomy registrations; Dashboard hint on type-specific Role assignments for RS-filtered Post Types; Custom Taxonomies could not be fully disabled, so various access failures (including conflict with Edit Flow plugin)

= 1.3.30 =
Fatal error when another plugin defines pluggable function 'set_current_user' (call to undefined function plural_name_from_cap_rs).  If you are not seeing this error, you don't need the update.

= 1.3.29 =
Comment editing for non-Admins in 3.1; DB errors from collation mismatch; Per-category Link visiblity; File Filtering not applied to new attachments; File Filtering for manually resized images; Manage Nav Menus w/o siteside edit; Role Groups menu in WP 3.0 MS; CMS Tree and 4 other plugin conflicts

= 1.3.28 =
Improves Nav Menu Manager and Category Manager role assignment; filters "Add New" links out of admin bar as appropriate; fixes RS Options menus in 3.1 Multisite, fixes 6 plugin conflicts including NextGEN Gallery, and 9 other Fixedes. 

== Documentation ==

* A slightly outdated [Usage Guide](http://agapetry.net/downloads/RoleScoper_UsageGuide.htm) is available.  It includes both an overview of the permissions model and a How-To section with step by step directions.  Volunteer contributions to expand, revise or reformat this document are welcome.
* Role Scoper's menus, onscreen captions and inline descriptive footnotes [can be translated using poEdit](http://weblogtoolscollection.com/archives/2007/08/27/localizing-a-wordpress-plugin-using-poedit/).  I will gladly include any user-contributed languages!.

== Plugin Compatibility Issues ==

**WP Super Cache** : set WPSC option to disable caching for logged users (unless you only use Role Scoper to customize editing access).

**WPML Multilingual CMS** : plugin creates a separate post / page / category for each translation.  Role Scoper does not automatically synchronize role assignments or restrictions for new translations, but they can be set manually by an Administrator.  

**QTranslate** : Role Scoper ensures compatibility by disabling the caching of page and category listings.  To enable caching, change QTranslate get&#95;pages and get&#95;terms filter priority to 2 or higher, then add the following line to wp-config.php: `define('SCOPER_QTRANSLATE_COMPAT', true);`

**Get Recent Comments** : not compatible due to direct database query. Use WP Recent Comments widget instead.

**The Events Calendar** : Not compatible as of TEV 1.6.3.  For unofficial workaround, change the-events-calendar.class.php as follows :

change:

    add_filter( 'posts_join', array( $this, 'events_search_join' ) );
    add_filter( 'posts_where', array( $this, 'events_search_where' ) );
    add_filter( 'posts_orderby',array( $this, 'events_search_orderby' ) );
    add_filter( 'posts_fields',	array( $this, 'events_search_fields' ) );
    add_filter( 'post_limits', array( $this, 'events_search_limits' ) );
  
    
to:

    if( ! is_admin() ) {
      add_filter( 'posts_join', array( $this, 'events_search_join' ) );
      add_filter( 'posts_where', array( $this, 'events_search_where' ) );
      add_filter( 'posts_orderby',array( $this, 'events_search_orderby' ) );
      add_filter( 'posts_fields',	array( $this, 'events_search_fields' ) );
      add_filter( 'post_limits', array( $this, 'events_search_limits' ) );
    }
  
    
**PHP Execution** : as of v1.0.0, mechanism to limit editing based on post author capabilities is inherently incompatible w/ Role Scoper. Edit php-execution-plugin/includes/class.php_execution.php as follows :

change:

    add_filter('user_has_cap', array(&$this,'action_user_has_cap'),10,3);
  
    
to:

    add_filter( 'map_meta_cap', array( &$this,'map_meta_cap' ), 10, 4 );
    
replace function action_user_has_cap with :

    function map_meta_cap( $caps, $meta_cap, $user_id, $args ) {
        $object_id = ( is_array($args) ) ? $args[0] : $args;
        if ( ! $post = get_post( $object_id ) )
            return $caps;

        if ( function_exists( 'get_post_type_object' ) ) {
            $type_obj = get_post_type_object( $post->post_type );
            $is_edit_cap = ( ( $type_obj->cap->edit_post == $meta_cap ) && in_array( $type_obj->cap->edit_others_posts, $caps ) );
        } else {
            $is_edit_cap = in_array( $meta_cap, array( 'edit_post', 'edit_page' ) ) && array_intersect( $caps, array( 'edit_others_posts', 'edit_others_pages' ) );
        }

        if ( $is_edit_cap ) {
            $id = $post->post_author;

            if ( isset( $this->cap_cache[$id] ) ) {
                $author_can_exec_php = $this->cap_cache[$id];
            } else {
                $author = new WP_User($id);
                $author_can_exec_php = ! empty( $author->allcaps[PHP_EXECUTION_CAPABILITY] );
                $this->cap_cache[$id] = $author_can_exec_php;
            }

            if ( $author_can_exec_php ) 
                $caps []= PHP_EXECUTION_CAPABILITY;
        }

        return $caps;	
    }
    
== Attachment Filtering ==

Read access to uploaded file attachments is normally filtered to match post/page access.

To disable this attachment filtering, disable the option in Roles > Options or copy the following line to wp-config.php:
    define('DISABLE&#95;ATTACHMENT&#95;FILTERING', true);


To reinstate attachment filtering, remove the definition from wp-config.php and re-enable File Filtering via Roles > Options.

To fail with a null response when file access is denied (no WP 404 screen, but still includes a 404 in response header), copy the folling line to wp-config.php: 

    define ('SCOPER&#95;QUIET&#95;FILE&#95;404', true);
  
    
Normally, files which are in the uploads directory but have no post/page attachment will not be blocked.  To block such files, copy the following line to wp-config.php: 

    define('SCOPER&#95;BLOCK&#95;UNATTACHED&#95;UPLOADS', true);

== Hidden Content Teaser ==

The Hidden Content Teaser may be configured to display the first X characters of a post/page if no excerpt or more tag is available.

To specify the number of characters (default is 50), copy the following line to wp-config.php:
 
    define('SCOPER&#95;TEASER&#95;NUM&#95;CHARS', 100); // set to any number of your choice