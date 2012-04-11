=== New User Email Set Up ===
Contributors: epicalex
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=aquacky%40gmail%2ecom&item_name=Thanks%20For%20My%20New%20User%20Email%20Plugin%21&buyer_credit_promo_code=&buyer_credit_product_category=&buyer_credit_shipping_method=&buyer_credit_user_address_change=&no_shipping=1&no_note=1&tax=0&currency_code=GBP&lc=GB&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: New User, Registration Email, Welcome Email, Admin, User Management 
Requires at least: 2.0.2
Tested up to: 2.8.5
Stable tag: trunk

A plugin to set up the email sent to new users when they register.

== Description ==

This Plugin defines the email that is sent to new users when they first register on your blog. You can define the subject, message body and from email address and name.
It also defines the message body and subject for the email sent to the blog administrator. The plugin allows HTML in the emails.

== Installation ==

1. Download and Unzip
2. Upload to your wp-content/plugins directory
3. Go to the Plugins page of your admin screen and look for 'New User Email Setup' and press 'activate'
4. Now go to Settings (Options if pre WP2.5)>New User Email and change the default text to what you want, remembering to use the variables!
5. Now make sure to test your new email, and you're done!!
6. If updating from an older version, please make sure your old options are still saved. The options structure was changed at v0.5 and updated to automatically delete old-style options at v0.5.1.

== Changelog =

= Version 0.5.2 =
1. Fixed issue with final letter of From name being stripped
1. Fixed jQuery issues with box toggle
1. Added option to send admin notification to a different address than the user receives their email from.
1. Changed layout, Yoast style...!
1. General code tidying.

= Version 0.5.1 =
1. Added Option to not send admin email
1. Added some jquery to disable certain fields on certain answers
1. Added automatic update from old style options to new array style

= Version 0.5 =
1. Added security with wp_nonce
1. Functionality if admin has altered the root to wp_content
1. Added Class structure to avoid any possible conflicts
1. Changed options to be saved in an array, making a smaller footprint on the database
1. Added 2.7+ compatibility.
1. Added the possibility to use a From Name along with the From Address
1. Added a link in the WordPress footer on the admin page to give a quick link to the plugin homepage if support is needed. Thanks to Striderweb

= Version 0.2.5 =
1. Admin page style updated ready for WordPress 2.5 release

= Version 0.2.1 =
1. Fixed some HTML issues by adding stripslashes()

= Version 0.2 =
1. Added HTML email functionality 
1. Removed some plugin conflicts by renaming some variables

= Version 0.1 = 
1. Initial Release

== Frequently Asked Questions ==

= The email isn't being sent from the address I define =

Are you sure that that address exists on your host? If you use MX records, you have to set up the email address on your host as well for it to work correctly.

= My variables aren't being passed to the email =

Make sure they are in the format %variable% with no spaces between the % and the variable. 

= I'm not getting any emails sent, to me or the user! =

The best thing to do is search in the <a href="http://wordpress.org/forum/">WordPress Forum</a>, since there are countless different reasons and solutions, but the problem is likely with your server setup.

= I get the following error on activation "Parse error: syntax error, unexpected T_STRING, expecting T_OLD_FUNCTION or T_FUNCTION or T_VAR or '}' in /wp-content/plugins/newuseremailsetup.php on line 44" =

You are using PHP 4 and the plugin uses functions introduced in PHP 5. Please either upgrade to PHP 5, or download the alternative PHP 4 Branch of this plugin from <a href="http://plugins.trac.wordpress.org/export/118896/new-user-email-set-up/branches/PHP4/newuseremailsetupphp4.php">here</a>.

= Can you implement function X? =

That depends on what that function is, and whether I'm free to do it/able to do it. It will also depend on whether the custom version will be paid work. Email me at alex at epicalex dot com for more on this.

== Screenshots ==

See <a href="http://epicalex.com/new-user-email-setup-update/">New User Email Setup</a> Plugin Page.