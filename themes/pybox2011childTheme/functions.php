<?php
define( 'HEADER_IMAGE_WIDTH', apply_filters( 'twentyeleven_header_image_width', 1000 ) );
define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'twentyeleven_header_image_height', 150 ) );
define( 'HEADER_IMAGE', content_url('/themes/pybox2011childTheme/images/header.jpg') );

//functions.php
add_theme_support( 'admin-bar', array( 'callback' => 'my_adminbar_cb') );
function my_adminbar_cb(){
  //empty function
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

function my_function_admin_bar(){ return true; }
add_filter( 'show_admin_bar' , 'my_function_admin_bar');


add_filter( 'twentyeleven_default_theme_options' , 'set_layout');
function set_layout($options) {
  $options['theme_layout'] = 'content';
  return $options;
}


add_action('get_header', 'my_filter_head');

function my_filter_head() {
  remove_action('wp_head', '_admin_bar_bump_cb');
}

// end of file