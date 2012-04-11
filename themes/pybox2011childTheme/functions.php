<?php
define( 'HEADER_IMAGE_WIDTH', apply_filters( 'twentyeleven_header_image_width', 1000 ) );
define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'twentyeleven_header_image_height', 150 ) );
define( 'HEADER_IMAGE', UFILES . 'header.jpg' );

//functions.php
add_theme_support( 'admin-bar', array( 'callback' => 'my_adminbar_cb') );
function my_adminbar_cb(){
  //empty function
}



// end of file