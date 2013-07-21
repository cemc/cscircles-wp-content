Computer Science Circles
========================
This repository contains what you need to run an open-source
version of the Computer Science Circles website,

 http://cscircles.cemc.uwaterloo.ca

on your own computer.

Specifically, this folder plays the role of the wp-content
directory in your WordPress installation.


Note:
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
- delete wp-content or rename to wp-content-backup
- `git clone https://github.com/cemc/cscircles-wp-content.git wp-content`
  which copies tour git repo into a new wp-content directory

Activating new content, on WordPress admin dashboard:
 - go to Plugins and enable "python in a box"
 - go to Appearance/Themes and pick "Pybox 2011 Child Theme"
 - go to Appearance/Theme Options and pick one-column layout
 - go to Settings/Reading, set Front Page Displays to a static page
 - go to Settings/Discussion, turn off Show Avatars

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

 wp-content/plugins/pybox/plugin-constants.php

and reference the locations of the safeexec binary and the jail directory.

Note: in its current form, installing safeexec requires super-user (admin)
permissions, in order to keep user code on separate accounts, which is
how we keep different submissions from interfering with each other.


Options other than drop-in replacing
------------------------------------
This package is distributed as a replacment for wp-content since the main
two directories are 

 wp-content/plugins/pybox and 

 wp-content/themes/pybox2011childTheme

and it seems easier than distributing two separate repos at the moment.
As well, lesson data files are stored in

 wp-content/lesson_files

If you would like to copy a specific set of files please read on below.


Contained Files Listing
-----------------------
- plugins/pybox/ 
  - the plugin with all the shortcodes and everything else
themes/pybox2011childTheme/ 
  - theme for our site. Uses twentyeleven theme (installed by default and 
    included in this repo)

Currently, we haven't tried to separate the theme information 
  from the plugin. In a future version, the goal is for all of the 
  shortcodes and core functionality to lie in the plugin, with the
  theme consisting of superficial things only.

- plugins/*
  - other plugins we use on the main site. none are required for our
    plugin or theme to work.

- languages/
  - reference WordPress admin translation files (we did not make them).
    not related to translating the lessons themselves, which is done
    with the polylang plugin.

- lesson_files/
  - files for @include directives in shortcodes and images in lessons

