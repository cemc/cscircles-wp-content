=== Honeypot for Contact Form 7 ===
Tags: honeypot, antispam, captcha, spam, form, forms, contact form 7, contactform7, contact form, cf7, cforms, Contact Forms 7, Contact Forms, contacts
Requires at least: 3.5
Tested up to: 5.3
Contributors: DaoByDesign
Donate link: http://www.nocean.ca/buy-us-a-coffee/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Honeypot for Contact Form 7 - Adds honeypot anti-spam functionality to CF7 forms.

== Description ==

This simple addition to the wonderful <a href="http://wordpress.org/extend/plugins/contact-form-7/">Contact Form 7</a> (CF7) plugin adds basic honeypot anti-spam functionality to thwart spambots without the need for an ugly captcha.

The principle of a honeypot is simple -- <em>bots are stupid</em>. While some spam is hand-delivered, the vast majority is submitted by bots scripted in a specific (wide-scope) way to submit spam to the largest number of form types. In this way they somewhat blindly fill in fields, regardless of whether the field should be filled in or not. This is how a honeypot catches the bot -- it introduces an additional field in the form that if filled out will cause the form not to validate.

Follow us on [Twitter](https://twitter.com/NoceanCA) and on [Facebook](https://www.facebook.com/nocean.ca/) for updates and news.

<strong>Support can be found [here](http://wordpress.org/support/plugin/contact-form-7-honeypot).</strong>

Visit the [Honeypot for Contact Form 7 plugin page](http://www.nocean.ca/plugins/honeypot-module-for-contact-form-7-wordpress-plugin/) for additional information or to [buy us a coffee](http://www.nocean.ca/buy-us-a-coffee/) to say thanks.

= Localization/Translation =
If you'd like to translate this plugin, please visit the plugin's [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/contact-form-7-honeypot) page. As of v1.10, all translation is handled there. Thank you to the polyglots that contribute!

= IMPORTANT NOTES: =
If you are using CF7 3.6+, use the latest version of this plugin. If you are using an older version of CF7, you will need to use [CF7 Honeypot v1.3](http://downloads.wordpress.org/plugin/contact-form-7-honeypot.1.3.zip).

== Installation ==

1. Install using the Wordpress "Add Plugin" feature -- just search for "Honeypot for Contact Form 7".
1. Confirm that [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) is installed and activated. Then activate this plugin.
1. Edit a form in Contact Form 7.
1. Choose "Honeypot" from the CF7 tag generator. <em>Recommended: change the honeypot element's ID.</em>
1. Insert the generated tag anywhere in your form. The added field uses inline CSS styles to hide the field from your visitors.

= Installation & Usage Video =
[youtube https://www.youtube.com/watch?v=yD2lBrU0gA0]
For the more visually-minded, here is a [short video showing how to install and use CF7 Honeypot](https://www.youtube.com/watch?v=yD2lBrU0gA0) from the fine folks at RoseApple Media. **Note:** This video was not produced by the CF7 Honeypot developer.

= Altering the Honeypot Output HTML [ADVANCED] =
While the basic settings should keep most people happy, we've added several filters for you to further customize the honeypot field. The three filters available are:

* `wpcf7_honeypot_accessibility_message` - Adjusts the default text for the (hidden) accessibility message.
* `wpcf7_honeypot_container_css` - Adjusts the CSS that is applied to the honeypot container to keep it hidden from view.
* `wpcf7_honeypot_html_output` - Adjusts the entire HTML output of the honeypot element.

For examples of the above, please see this [recipe Gist](https://gist.github.com/nocean/953b1362b63bd3ecf68c).

== Frequently Asked Questions == 

= Will this module stop all my contact form spam? =

* Probably not. But it should reduce it to a level whereby you don't require any additional spam challenges (CAPTCHA, math questions, etc.).

= Are honeypots better than CAPTCHAs? =

* This largely depends on the quality of the CAPTCHA. Unfortunately the more difficult a CAPTCHA is to break, the more unfriendly it is to the end user. This honeypot module was created because we don't like CAPTCHAs cluttering up our forms. Our recommendation is to try this module first, and if you find that it doesn't stop enough spam, then employ more challenging anti-spam techniques.

= Can I modify the HTML this plugin outputs? =

* Yep! See the **Installation** section for more details and [this Gist](https://gist.github.com/nocean/953b1362b63bd3ecf68c) for examples.

= My form is not validating with a W3C validation tool =

* This is by design, and we recommend leaving this validation error for enhanced improvement of the plugin. However, there is a simple work around. See [here](https://wordpress.org/support/topic/w3c-validation-in-1-11-explanation-and-work-arounds/) for details.

== Changelog ==
= 1.14.1 =
Minor update to change name to comply with CF7 copyright notice.

= 1.14 =
Added do-not-store for when forms are stored in the DB (i.e. Flamingo). Improved wrapper ID masking and customization.

= 1.13 =
Additional functionality to improve spam-stopping power.

= 1.12 =
Introduces ability to force W3C compliance. See [here](https://wordpress.org/support/topic/w3c-validation-in-1-11-explanation-and-work-arounds/) for details.

= 1.11 =
Addresses accessibility concerns regarding a missing label and disables autocomplete to prevent browser autocomplete functions from filling in the field.

= 1.10 =
Updates for Function/Class changes related to CF7 4.6. Removed plugin local language support, instead use translate.wordpress.org.

= 1.9 =
Added i18n support, French language pack. Thx chris-kns

= 1.8 =
Added wpcf7_honeypot_accessibility_message and wpcf7_honeypot_container_css filters, i18n support.

= 1.7 =
Provides backwards compatibility for pre-CF7 4.2, introduces ability to remove accessibility message.

= 1.6.4 =
Quick fix release to fix PHP error introduced in 1.6.3.

= 1.6.3 =
Updates to accommodate changes to the CF7 editor user interface.

= 1.6.2 =
Small change to accommodate validation changes made in CF7 4.1.

= 1.6.1 =
Small change to accommodate changes made in CF7 3.9.

= 1.6 =
Quite a lot of code clean-up. This shouldn't result in any changes to the regular output, but it's worth checking your forms after updating. Also, you'll note that you now have the ability to add a custom CLASS and ID attributes when generating the Honeypot shortcode (in the CF7 form editor).

= 1.5 =
Added filter hook for greater extensibility. See installation section for more details.

= 1.4 =
Update to make compatible with WordPress 3.8 and CF7 3.6. Solves problem of unrendered honeypot shortcode appearing on contact forms.

= 1.3 =
Update to improve outputted HTML for better standards compliance when the same form appears multiple times on the same page.

= 1.2 =
Small update to add better i18n and WPML compatibility.

= 1.1 =
Small update for W3C compliance. Thanks [Jeff](http://wordpress.org/support/topic/plugin-contact-form-7-honeypot-not-w3c-compliant)</a>.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.8 =
Recommended update for all users using CF7 3.6 and above.

= 1.7 =
Recommended update for all users using CF7 3.6 and above.

= 1.6.3 =
Must update if running CF7 4.2 or above. If using less than CF7 4.2, use the v1.6.2 of this plugin.

= 1.6.2 =
Must update if running CF7 4.1 or above. Update also compatible with CF7 3.6 and above. If using less than CF7 3.6, use the v1.3 of this plugin.

= 1.6.1 =
Must update if running CF7 3.9 or above. Update also compatible with CF7 3.6 and above. If using less than CF7 3.6, use the v1.3 of this plugin.

= 1.6 =
New custom "class" and "id" attributes. Upgrade recommended if you are using CF7 3.6+, otherwise use v1.3 of this plugin.

= 1.5 =
Includes "showing shortcode" fix from version 1.4 and also includes new filter hook. Upgrade recommended.

= 1.4 =
Solves problem of unrendered honeypot shortcode appearing on contact forms. Upgrade immediately.