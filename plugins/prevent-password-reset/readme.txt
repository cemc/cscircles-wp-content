=== Prevent Password Reset ===

Contributors: greenshady
Donate link: http://themehybrid.com/donate
Tags: admin, password
Requires at least: 3.3
Tested up to: 3.7
Stable tag: 0.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Prevents password reset for select users via the WordPress "lost password" form.

== Description ==

Prevents password reset for select users via the WordPress "lost password" form. This plugin adds a checkbox to each user's profile in the admin. If selected, it prevents the user's password from being reset.  If a user selects to prevent their password from being reset, no one can try to reset the password.  It stops it completely. 

Things to keep in mind:

* If you lose your password, you won't be able to reset it either unless you remove the plugin via FTP, go into the database, or have an administrator on the site change your password.
* This plugin does not disable the ability to edit/change a password from the user profile page. It merely blocks password resetting from the "lost password" form.

### Professional Support

If you need professional plugin support from me, the plugin author, you can access the support forums at [Theme Hybrid](http://themehybrid.com/support), which is a professional WordPress help/support site where I handle support for all my plugins and themes for a community of 40,000+ users (and growing).

### Plugin Development

If you're a theme author, plugin author, or just a code hobbyist, you can follow the development of this plugin on it's [GitHub repository](https://github.com/justintadlock/prevent-password-reset). 

### Donations

Yes, I do accept donations.  If you want to buy me a beer or whatever, you can do so from my [donations page](http://themehybrid.com/donate).  I appreciate all donations, no matter the size.  Further development of this plugin is not contingent on donations, but they are always a nice incentive.

== Installation ==

1. Upload `prevent-password-reset` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to your user profile in the admin to select whether to prevent password resetting.

== Frequently Asked Questions ==

### Why was this plugin created?

I needed a way to disable password resets for specific administrators on my site but leave this functionality open for the other 1,000s of users.  So, I chose to make this an option on a per-user basis.

### How do I use it?

Once installed, a checkbox gets added to the "Personal Options" section of each user's profile page in the WordPress admin.  Anyone who can edit the user may check this box to disable password resets for that individual user.

### I forgot my password. What should I do?

The easiest way to do this is to log into your hosting account via FTP.  Proceed to your plugins directory and delete the `prevent-password-reset` folder.  This should allow you to reset passwords for any user account via the "lost password" form.

If you don't have this type of access, you need to talk to a site administrator who does to have them update your password.

== Screenshots ==

1. Disabled password reset
2. Password reset checkbox on user profile page

== Changelog ==

### Version 0.2.0

* Changed license from GPL v2-only to GPL v2+.
* Added plugin row meta for the plugin.
* Dropped the MO and PO files in favor of a POT file for translators.
* Added a `readme.md` file since this plugin is now on GitHub.
* Updated this `readme.txt` file.
* Updated this plugin for the mere purpose of updating. WordPress.org has created a stigma around plugins that don't get updates every so often by putting a big warning box at the top of the plugin's page.  Basically, it's telling users to beware!  Of course, this is just ridiculous, particularly when talking about a plugin that most likely will never need an update (like this one).  Some plugins are simply "set and forget" forever.  But, because of how WordPress.org treats these types of plugins, lumping them into a "bad plugin" pool with plugins that actually do need updates, I'm forced to create a rather useless update to this plugin.  Update if you wish; it's not actually necessary.  It's just necessary for me to update the plugin in order for it to not be de-listed from the WordPress.org search results.  Good day.

### Version 0.1.0

* Plugin launch.  Everything's new!