<?php


  // see plugin-main.php for where the options get their default values

add_action('admin_menu', 'cscircles_admin_menu');
add_action('admin_init', 'cscircles_admin_init');
function cscircles_admin_menu() {
  if (userIsAdmin())
    add_options_page("CS Circles", "CS Circles", "read", "cscircles-options", "cscircles_options_page");
}

function cscircles_nosanitize($x) {return $x;}

function cscircles_admin_init() {
  register_setting('cscircles', 'cscircles_pjail', 'cscircles_nosanitize');
  register_setting('cscircles', 'cscircles_psafeexec', 'cscircles_nosanitize');
  register_setting('cscircles', 'cscircles_asst_email', 'cscircles_nosanitize');

  add_settings_section('cscircles_ms', 'Settings', 'cscircles_callback1', 'cscircles');
  
  add_settings_field('cscircles_pjail', 'Absolute path to python3jail with trailing slash', 
                     'cscircles_callback2', 'cscircles', 'cscircles_ms');

  add_settings_field('cscircles_psafeexec', 'Absolute path to safeexec binary executable', 
                     'cscircles_callback3', 'cscircles', 'cscircles_ms');

  add_settings_field('cscircles_asst_email', 'Notification e-mail address for Help sent to Assistant',
                     'cscircles_callback4', 'cscircles', 'cscircles_ms');

}

function cscircles_callback1() {
  ?><style> input[type=text] {width: 400px; font-family: Consolas, Monaco, Menlo, 'Ubuntu Mono', 'Droid Sans Mono', monospace;} </style> <?php
}

function cscircles_callback2() {
?><input type="text" name="cscircles_pjail" value="<?php echo get_option('cscircles_pjail');?>" /><?php
}

function cscircles_callback3() {
?><input type="text" name="cscircles_psafeexec" value="<?php echo get_option('cscircles_psafeexec');?>" /><?php
}

function cscircles_callback4() {
?><input type="text" name="cscircles_asst_email" value="<?php echo get_option('cscircles_asst_email');?>" /><?php
}

function cscircles_options_page() {

  echo "<div class='wrap'>
<h2>CS Circles Options</h2>
<form method='post' action='options.php'>";
  
  settings_fields('cscircles');
  do_settings_sections('cscircles');


  submit_button();
  echo "</form>
</div>";   

}