<?php
define( 'HEADER_IMAGE_WIDTH', apply_filters( 'twentyeleven_header_image_width', 1000 ) );
define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'twentyeleven_header_image_height', 150 ) );
define( 'HEADER_IMAGE', UFILES . 'header.jpg' );

//functions.php
add_theme_support( 'admin-bar', array( 'callback' => 'my_adminbar_cb') );
function my_adminbar_cb(){
  //empty function
}

add_action('init', 'add_the_dang_es');
function add_the_dang_es() {
  add_editor_style("editor-pybox-style.css");
}


// removes the `profile.php` admin color scheme options
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

if ( ! function_exists( 'cor_remove_personal_options' ) ) {

  // remove unnecessary parts of profile page
  function cor_remove_personal_options( $subject ) {
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
    $subject = preg_replace ( '#id="profile-page">#', 'id="profile-page"><style>h3, #contextual-help-link-wrap {display: none} </style>', $subject);

    $subject = preg_replace_callback( '#<div id="wpbody-content".*>#Us',
                                      function ($match) {return $match[0] . returnfromprofile();},
                                  $subject);
    /*    $subject = preg_replace_callback( '#type="submit".*>#Us',
                                      function ($match) {return $match[0] . returnfromprofile();},
                                      $subject);*/
  
    return $subject;
  }

  function cor_profile_subject_start() {
    ob_start( 'cor_remove_personal_options' );
  }

  function cor_profile_subject_end() {
    ob_end_flush();
  }
 }
add_action( 'admin_head-profile.php', 'cor_profile_subject_start' );
add_action( 'admin_footer-profile.php', 'cor_profile_subject_end' );


// end of file