Computer Science Circles
========================
This repository contains what you need to run an open-source
version of the Computer Science Circles website,

 http://cscircles.cemc.uwaterloo.ca

on your own computer.

This repository is at https://github.com/cemc/cscircles-wp-content

For authoring information, see http://cscircles.cemc.uwaterloo.ca/authoring/

This code is released under the GPLv3 license.


Installation
-------------------------------------------
This folder plays the role of the wp-content
directory in your WordPress installation.

This plugin teaches programming by executing and grading
arbitrary user code. However, this is not a good idea unless
done securely. Currently, only one option works: you must
install "safeexec" and "python3jail" on your server, which
is harder than installing a wordpress plugin. We are working
on developing other options like sending code to a Waterloo
server and executing it there as long as it does not exceed
our demand.

For now, your options are:
 1. follow Installing "safeexec" and "python3jail" directions below
 2. install without ability to execute user code


Using as a wp-content drop-in replacement
-----------------------------------------
If you are starting from scratch, you can use this approach.

Installing Wordpress:
- get database permissions from a database admin
- unzip http://wordpress.org/latest.zip in your public_html
- copy wp-config-sample.php to wp-config.php and edit
- visit WordPress in your browser and follow account setup
- for more info see: http://codex.wordpress.org/Installing_WordPress

Replacing wp-content:
- in a shell, enter the wordpress directory
- delete wp-content or rename it to wp-content-backup
- `git clone https://github.com/cemc/cscircles-wp-content.git wp-content`
  which copies this git repo into a new wp-content directory

Activating new content, on WordPress admin dashboard:
 - go to Plugins and enable "python in a box"

If you want the appearance to be the same as CS Circles:
 - go to Appearance/Themes and activate "Pybox 2011 Child Theme"
 - go to Settings/Reading, set Front Page Displays to a static page
   (currently we don't have theming for posts figured out)
 - go to Settings/Discussion, turn off Show Avatars
   (or else there will be a big blank spot in the user menu)

Then you are good to go as far as WordPress is concerned! To test
it out, create a WordPress page and enter some shortcodes like

[pyWarn]This is a warning[/pyWarn]

This is an [pyHint hint='the popup text']embedded[/pyHint] hint.

[pyExample code='print("Hello, World!")']Sample text[/pyExample]

You should see these when you view the page, although the Run program
button won't work.


Installing "safeexec" and "python3jail"
---------------------------------------
Please visit

 https://github.com/cemc/safeexec

and

 https://github.com/cemc/python3jail

then follow the directions listed there. After installation, edit

 wp-content/plugins/pybox/plugin-config.php

and reference the locations of the safeexec binary and the jail directory.

Note: in its current form, installing safeexec requires super-user (admin)
permissions, in order to keep user code on separate accounts, which is
how we keep different submissions from interfering with each other.


Writing content
---------------
See http://cscircles.cemc.uwaterloo.ca/authoring/

You can look at samples of existing content by using the icons on
the bottom-left of exercises, examples and lessons.

Files listing / alternative to drop-in replacement for power users
------------------------------------------------------------------
You need not copy all of wp-content. If you install the theme

 wp-content/plugins/pybox

then you will get all of the shortcode functionality, although
the look and feel won't look great with every theme.

Use the theme

 wp-content/themes/pybox2011childTheme

to get our look and feel, including customizations to the admin bar.
The theme will not work if the plugin is not installed and activated.

Lesson data files are stored in

 wp-content/lesson_files

Specifically this the source location for @include directives in
shortcodes, as well as images in the lessons we wrote.

The remaining files in

 wp-content/plugins/{not pybox}

are the other plugins we use on the main site. Some of them will
be necessary to display content we wrote, e.g. LaTeX shortcodes.

The directory

 wp-content/languages

references WordPress admin translation files (we did not make them).
Note that our own translation is done with the .{mo,po,pot} files
in pybox and the Polylang plugin.
