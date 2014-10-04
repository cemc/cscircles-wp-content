=== Log Deprecated Notices ===
Contributors: nacin
Tags: deprecated, logging, admin, WP_DEBUG, E_NOTICE, developer
Requires at least: 3.0
Tested up to: 4.1-alpha
Stable tag: 0.3

Logs the usage of deprecated files, functions, and function arguments, and identifies where the deprecated functionality is being used.

== Description ==

This plugin logs the usage of deprecated files, functions, and function arguments. It identifies where the deprecated functionality is being used and offers the alternative if available.

This is a plugin for developers. WP_DEBUG is not needed, though its general usage is strongly recommended. Deprecated notices normally exposed by WP_DEBUG will be logged instead.

This plugin also logs incorrect function usage, which WordPress started reporting in 3.1.

Please report any bugs to plugins at [andrewnacin.com](http://andrewnacin.com/), or find me in IRC #wordpress-dev or @[nacin](http://twitter.com/nacin) on Twitter.

This is young software. It works, but there's a lot left on the todo (check out the Other Notes tab). Have an idea? Let me know.

== Installation ==

For an automatic installation through WordPress:

1. Go to the 'Add New' plugins screen in your WordPress admin area
1. Search for 'Log Deprecated Notices'
1. Click 'Install Now' and activate the plugin
1. View the log in the 'Tools' menu, under 'Deprecated Calls'

For a manual installation via FTP:

1. Upload the `log-deprecated-notices` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' screen in your WordPress admin area

To upload the plugin through WordPress, instead of FTP:

1. Upload the downloaded zip file on the 'Add New' plugins screen (see the 'Upload' tab) in your WordPress admin area and activate.

This plugin is will remove log entries when it is uninstalled and deleted.

== Screenshots ==

1. Log screen.

== Ideas ==

These are the various things on the @todo:

 * Plugin identification. Also, an unobstrusive note on plugins page next to said plugins.
 * Perhaps the ability to auto-purge the log.
 * Ability to filter on file or plugin in which the deprecated functionality is used.
 * Offer some kind of better multisite support.

Want to add something here? I'm all ears. plugins at [andrewnacin.com](http://andrewnacin.com/) or @[nacin](http://twitter.com/nacin) on Twitter.

I will prioritize these tasks based on feedback, so let me know what you'd like to see.

== Upgrade Notice ==

= 0.3 =
Updated to handle a new deprecated message in WordPress 4.0.

= 0.2 =
Initial compatibility for WordPress 3.3.