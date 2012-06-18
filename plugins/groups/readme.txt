=== Groups ===
Contributors: itthinx
Donate link: http://www.itthinx.com/plugins/groups
Tags: access, access control, capability, capabilities, content, download, downloads, file, file access, files, group, groups, member, members, membership, permission, permissions
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 1.2.1
License: GPLv3

Groups provides group-based user membership management, group-based capabilities and content access control.

== Description ==

Groups provides group-based user membership management, group-based capabilities and content access control.
It integrates standard WordPress capabilities and application-specific capabilities along with an extensive API.

### Extensions ###

- [Groups File Access](http://www.itthinx.com/plugins/groups-file-access/) Groups File Access is an extension that allows to provide file download links for authorized users. Access to files is restricted to users by their group membership.

### Features ###

#### User groups ####

- Supports an unlimited number of groups
- Provides a Registered group which is automatically maintained
- Users can be assigned to any group
- Users are added automatically to the Registered group

#### Groups hierarchy ####

- Supports group hierarchies with capability inheritance

#### Group capabilities ####

- Integrates standard WordPress capabilities which can be assigned to groups and users
- Supports custom capabilities: allows to define new capabilities for usage in plugins and web applications
- Users inherit capabilities of the groups they belong to
- Groups inherit capabilities of their parent groups

#### Access control ####

- Built-in access control that allows to restrict access to posts, pages and custom content types to specific groups and users only
- control access to content by groups: shortcodes allow to control who can access content on posts, show parts to members of certain groups or to those who are not members
  Shortcodes: [groups_member], [groups_non_member]
- control access to content by capabilities: show (or do not show) content to users who have certain capabilities
  Shortcodes: [groups_can], [groups_can_not]

#### Easy user interface ####

- integrates nicely with the standard WordPress Users menu
- provides an intuitive Groups menu
- conceptually clean views showing the essentials
- quick filters
- bulk-actions where needed, for example apply capabilities to groups, bulk-add users to groups, bulk-remove users from groups

#### Sensible options ####

- administrator overrides can be turned off
- optional tree view for groups can be shown only when needed
- provides its own set of permissions
- cleans up after testing with a "delete all plugin data" option 

#### Access Control ####

Access to posts and pages can be restricted by capability.

Any capability can be used to restrict access, including new capabilities.

If access to a post is restricted, only users who belong to a group with that
capability may access the post.

Groups defines the groups_read_post capability by default, which can be
used to restrict access to certain posts or pages to groups
with that capability only. Any other capability (including new ones) can be
used to limit access as well.

#### Framework ####

- Solid and sound data-model with a complete API that allows developers to create group-oriented web applications and plugins

#### Multisite ####

- All features are supported independently for each blog in multisite installations

### Feedback ###

Feedback is welcome!

If you need help, have problems, want to leave feedback or want to provide constructive criticism, please do so here at the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

Please try to solve problems there before you rate this plugin or say it doesn't work. There goes a _lot_ of work into providing you with free quality plugins! Please appreciate that and help with your feedback. Thanks!

#### Twitter ####

[Follow @itthinx on Twitter](http://twitter.com/itthinx) for updates on this and other plugins.

### Translations ###

* Lithuanian translation provided by Vincent G from [Host1Free](http://www.Host1Free.com) - Many thanks for your help!


### Introduction ###

#### Content Access Control ####

##### Access restrictions on posts ####

On posts an pages (and custom content types) a new meta box titled *Access restrictions* appears.
By checking a capability under *Enforce read access*, you can restrict access to the post to groups and users who are members of a group with that capability.
You need to assign this capability to a group and make users members of that group to allow them to see those posts.

#### Content visibility for members and non-members ####

The [groups_member] and [groups_non_member] shortcodes are used to limit visibility of content to users who *are* members of a group or users who *are not* members of a group. Multiple comma-separated groups can be specified.

Example: Limiting visibility of enclosed content to registered users.

[groups_member group="Registered"]

Only registered users can see this text.

[/groups_member]

#### Content visibility based on capabilities ####

The [groups_can] and [groups_can_not] shortcodes limit visibility of enclosed content to those users who *have* the capability or those who *do not have* it. Multiple capabilities can be given.

Example: Showing enclosed content to users who can edit_posts (standard WordPress capability).

[groups_can capability="edit_posts"]

You can see this only if you have the edit_posts capability.

[/groups_can]

### Integration in the 'Users' menu: ###

Users - group membership is managed from the standard Users admin view.
Users are automatically added to the _Registered_ group. You can add multiple users to other groups here and also remove them.

### Sections in the 'Groups' menu: ###

#### Groups ####

Here you can:

- add groups
- remove groups
- assign capabilities to groups

#### Capabilities ####

This is where you add, remove and manage capabilities.

Capabilities can be assigned to groups and users (1). These capabilities include
the *standard WordPress capabilities* but you can also define additional
capabilities for your web-application.

Groups defines the *groups_read_post* capability by default which can be
used to restrict access to certain posts or pages to groups (and users)
with that capability only. Additional capabilities can be identified on the
*Groups > Options* admin screen that may be used to limit access.

(1) Assigning capabilities to users is not integrated in the user interface yet but can be done through API calls.

#### Options ####

##### Administrator override #####

Administrator overrides can be turned off.

##### Permissions #####

For each role these permissions can be set:

* Access Groups: see information related to Groups.
* Administer Groups: complete control over everything related to Groups.
* Administer Groups plugin options: grants access to make changes on the *Groups > Options* admin section.

##### Testing the plugin #####

A convenient option is provided to delete all data that has been stored by the Groups plugin.
This option is useful if you just need to start from fresh after you have been testing the plugin.

### Shortcodes ###

#### Limit content visibility ####

These shortcodes are used to limit the visibility of the content they enclose:

- [groups_member]
- [groups_non_member]
- [groups_can]
- [groups_can_not]

See above for examples and descriptions.

#### Show group information ####

- [groups_group_info]

This shortcode takes the following attributes to show information about a group:

- _group_ : (required) the group ID or name
- _show_ : (required) what to show, accepted values are: _name_, _description_, _count_
- _single_ : (optional) used when show="count" and there is 1 member in the group
- _plural_ : (optional) used when show="count" and there is more than 1 member in the group, must contain %d to show the number of members
 
Examples:

* [groups_group_info group="Registered" show="count"]

* There [groups_group_info group="1" show="count" single="is one member" plural="are %d members"] in the [groups_group_info group="1" show="name"] group.

#### Show user groups ####

- [groups_user_groups]

This shortcode lists the current user's or a specific user's groups.

For detailed information about this shortcode, please refer to the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

#### Show site groups ####

- [groups_groups]

This shortcode lists the site's groups.

For detailed information about this shortcode, please refer to the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

== Installation ==

1. Upload or extract the `groups` folder to your site's `/wp-content/plugins/` directory. You can also use the *Add new* option found in the *Plugins* menu in WordPress.  
2. Enable the plugin from the *Plugins* menu in WordPress.

== Frequently Asked Questions ==

= Where is the documentation? =

Most of the features are currently documented at the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

The official Groups documentation root is at the [Groups Documentation](http://www.itthinx.com/documentation/groups/) page.
The documentation is a work in progress, if you don't find anything there yet but want to know about the API, please look at the code as it provides useful documentation on all functions.

= I have a question, where do I ask? =

You can leave a comment at the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

= I want Advanced and Premium members, where the Premium members can access everything that Advanced members can access. How can I do that? =

Example: Advanced and Premium members

1. Go to *Groups > Capabilities* and define two new capabilities, let's call them *advanced* and *premium*.
2. Go to *Groups > Groups* and define two new  groups, let's call them *Advanced Members* and *Premium Members* - select *Advanced Members* as the *Parent* for the *Premium Members* group.
3. Assign the *advanced* capability to the *Advanced Members* group and the *premium* capability to the *Premium Members* group.
4. Go to *Groups > Options* and tick the checkboxes for *advanced* and *premium* under _Access restrictions_ and hit the *Save* button at the end of the page.
5. Now create an example post that only members of the *Advanced Members* group should be able to see and tick the *advanced* checkbox under _Access restrictions_.
6. Create another post for *Premium Members* and tick the *premium* checkbox for that post.
7. Assign test users to both groups, log in as each user in turn and see which posts will be accessible. 

= How do I limit access to posts so that users in group A can not read the same as those in group B and vice-versa? =

Example: Green and Red members

1. Go to *Groups > Capabilities* and define two new capabilities, call them *green* and *red*.
2. Go to *Groups > Groups* and define two new  groups, let's call them *Green Members* and *Red Members*
3. Assign the *green* capability to the *Green Members* group and the *red* capability to the *Red Members* group.
4. Go to *Groups > Options* and tick the checkboxes for *green* and *red* under _Access restrictions_ and hit the *Save* button at the end of the page.
5. Now create an example post that only members of the *Green Members* group should be able to see and tick the *green* checkbox under _Access restrictions_.
6. Create another post for *Red Members* and tick the *red* checkbox for that post.
7. Assign a test user to any of the above groups, log in as that user and the post will be accessible.

== Screenshots ==

See also [Groups](http://www.itthinx.com/plugins/groups/)

1. Groups - this is where you add and remove groups and assign capabilities to groups.
2. Capabilities - here you get an overview of the capabilities that are defined and you can add and remove capabilities as well.
3. Users - group membership is managed from the standard Users admin view.
4. Access restrictions meta box - on pages and posts (or custom content types) you can restrict access to users who are part of a group with capabilities.
5. Usage of the [groups_member] and [groups_non_member] shortcodes to limit visibility of content to users who are members of a group or users who are not members of a group. Multiple comma-separated groups can be specified.
6. Usage of the [groups_can] and [groups_can_not] shortcodes. Limits visibility of enclosed content to those users who have the capability or those who do not. Multiple capabilities can be given.
7. Options - you can adjust the plugin's settings here.
8. More options.

== Changelog ==

= 1.2.1 =
* Reduced files loaded on non-admin pages.
* Added Lithuanian translation.
* Changed help to use tabs.

= 1.2.0 =
* Access control is no longer restricted to the groups_read_post capability: now any capability can be used to limit access to posts so that different groups can be granted access to different sets of posts.

= 1.1.5 =
* Added shortcode & API functions [groups_user_group] / [groups_user_groups] that allows to show the list of groups the current user or a specific user belongs to
* Added shortcode & API functions [groups_groups]to show the site's list of groups
* Class comments.

= 1.1.4 =
* Reduced plugin admin footer.

= 1.1.3 =
* Added safety & warning to test page.

= 1.1.2 =
* Tested on WP 3.3.2

= 1.1.1 =
* Multisite: Fixed (removed) conditions that would only make Groups act on public and non-mature sites
* Multisite: Adding add/remove to group only on sites', not network users admin screen
* Multisite: Added constraint in user_register hook checking if the user is a member of the blog
 
= 1.1.0 =
* Added Groups menu to network admin
* Added option to delete plugin data for all sites on multisite installations; removed option for individual sites
* Improved activation and deactivation for network installs
* Increases column sizes on capabilities table and fixes cut-off capabilities delete_published_pages and delete_published_posts

= 1.0.0-beta-3d =
* Fixed issues caused by an excessively long index for the capability DB table.
Some installations wouldn't work correctly, showing no capabilities and making it impossible to add new ones.  
* Taking into account blog charset/collation on newly created tables.

= 1.0.0-beta-3c =
* Groups shortcodes now allow nesting.

= 1.0.0-beta-3b =
* Fixed admin override option not being updated
* DB tables checked individually to create (motivated by case of all but capability table not being created)

= 1.0.0-beta-3 =
* Groups wouldn't activate due to a fatal error on WP <= 3.2.1 : is_user_member_of_blog() is defined in ms-functions.php
* Added [groups_group_info] shortcode 

= 1.0.0-beta-2 =
* Increased length of capability.capability, capability.class, capability.object columns to support long capabilities.
* Improved admin CSS.

= 1.0.0-beta-1 =
* This is the first public beta release.

== Upgrade Notice ==

= 1.2.1 =
* Added Lithuanian translation.
* Slight performance improvement.
* Improved the way help sectiosn are handled.

= 1.2.0 =
* New: Different groups can be granted access to different sets of pages or posts: Any capability - including custom capabilities - can be used to limit access.

= 1.1.5 =
* New shortcodes.

= 1.1.4 =
* Several bug fixes and improvements.

= 1.0.0-beta-3d =
* The capability DB table had a ridiculously long index, this update fixes it.

= 1.0.0-beta-3c =
* Groups shortcodes now allow nesting: [groups_member], [groups_non_member], [groups_can], [groups_can_not]

= 1.0.0-beta-3b =
* Admin override fix and DB tables checked individually to create them.

= 1.0.0-beta-3 =
* New shortcode to show group info & WP <= 3.2.1 compatibility fix.

= 1.0.0-beta-2 =
* Increased length of capability.capability, capability.class and capability.object columns from to 255 => you need to update your DB manually if you want that updated.
* Improved some admin CSS.

= 1.0.0-beta-1 =
* This is the first public beta release.

== API ==

The Groups plugin provides an extensive framework to handle memberships, group-based capabilities and access control.
Read more on the official [Groups](http://www.itthinx.com/plugins/groups/) page and the [Groups documentation](http://www.itthinx.com/documentation/groups/) page.


