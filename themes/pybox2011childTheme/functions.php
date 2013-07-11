<?php
define( 'HEADER_IMAGE_WIDTH', apply_filters( 'twentyeleven_header_image_width', 1000 ) );
define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'twentyeleven_header_image_height', 150 ) );
define( 'HEADER_IMAGE', UFILES . 'header.jpg' );

//functions.php
add_theme_support( 'admin-bar', array( 'callback' => 'my_adminbar_cb') );
function my_adminbar_cb(){
  //empty function
}

// editor style
add_action('init', 'add_the_dang_es');
function add_the_dang_es() {
  add_editor_style("editor-pybox-style.css");
}

// removes the profile.php admin color scheme options
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

// remove unnecessary parts of profile page, add "return" button
add_action( 'admin_head-profile.php', 'do_tweak_profile_page');
function do_tweak_profile_page() {ob_start('tweak_profile_page');}
function tweak_profile_page( $subject ) {
  foreach (array("rich_editing", 
                 "comment_shortcuts",
                 "description", 
                 "url", 
                 "admin_bar_front", 
                 "display_name",
                 "nickname") 
           as $label
           ) {
    $subject = preg_replace_callback( "#<tr.*label for=\"".$label."\".*</tr>#Us",
                                      function ($match) {
                                        $pos = strrpos($match[0], "<tr>");
                                        return substr($match[0], 0, $pos);
                                      },
                                      $subject);
  }
  // add return link, and hide unnecessary section headers
  $subject = preg_replace ( '#id="profile-page">#', 'id="profile-page">
<style>h3 {display: none}</style>' . returnfromprofile(), $subject);
  
  return $subject;
}

add_action( 'admin_head-index.php', 'do_tweak_dashboard_page');
function do_tweak_dashboard_page() {ob_start('tweak_dashboard_page');}
function tweak_dashboard_page( $subject ) {
  return preg_replace ( '/<div class="wrap">/', returnfromprofile() . '<div class="wrap">', $subject);
}

// end of file