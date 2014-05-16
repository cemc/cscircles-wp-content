<?php


  // see plugin-main.php for where the options get their default values

add_action('admin_menu', 'cscircles_administrator');
function cscircles_administrator() {
  if (userIsAdmin()) {
    add_menu_page( 'CS Circles', 'CS Circles', 'edit_plugins',
                   'cscircles-options', 'cscircles_options_page',
                   UFILES . 'checked16.png' , 73);
    add_submenu_page('cscircles-options', "Rebuild Databases", "Rebuild Databases", 
                     "edit_plugins", "cscircles-makedb", "cscircles_makedb_page");
  }
}

function cscircles_nosanitize($x) {return $x;}

function cscircles_boolean_sanitize($x) {return $x=='on';}

add_action('admin_init', 'cscircles_admin_init');
function cscircles_admin_init() {
  register_setting('cscircles', 'cscircles_pjail', 'cscircles_nosanitize');
  register_setting('cscircles', 'cscircles_psafeexec', 'cscircles_nosanitize');
  register_setting('cscircles', 'cscircles_asst_email', 'cscircles_nosanitize');  
  register_setting('cscircles', 'cscircles_hide_help', 'cscircles_boolean_sanitize');
  register_setting('cscircles', 'cscircles_hide_ack', 'cscircles_boolean_sanitize');

  add_settings_section('cscircles_ms', 'Settings', 'cscircles_callback1', 'cscircles');
  
  add_settings_field('cscircles_pjail', 'Absolute path to python3jail with trailing slash', 
                     'cscircles_callback2', 'cscircles', 'cscircles_ms');

  add_settings_field('cscircles_psafeexec', 'Absolute path to safeexec binary executable', 
                     'cscircles_callback3', 'cscircles', 'cscircles_ms');

  add_settings_field('cscircles_asst_email', 'Notification e-mail address for Help sent to Assistant',
                     'cscircles_callback4', 'cscircles', 'cscircles_ms');

  add_settings_field('cscircles_hide_help', 'Hide "Help" and "Mail"',
                     'cscircles_callback5', 'cscircles', 'cscircles_ms');

  add_settings_field('cscircles_hide_ack', 'Hide link to original site',
                     'cscircles_callback6', 'cscircles', 'cscircles_ms');

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
  // same fallback as action-send-message.php
?><input type="text" name="cscircles_asst_email" value="<?php echo get_option('cscircles_asst_email', get_userdata(1)->user_email);?>" /><?php
}

function cscircles_callback5() {
  ?><input type="checkbox" name="cscircles_hide_help" <?php echo get_option('cscircles_hide_help')?'checked':'';?> /><?php
}

function cscircles_callback6() {
  ?><input type="checkbox" name="cscircles_hide_ack" <?php echo get_option('cscircles_hide_ack')?'checked':'';?> /><?php
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

  $updates = wp_get_translation_updates();
  if ($updates) {
    echo "<p><i>Note:</i> The following translations may need to be updated:<ul>";
    foreach ($updates as $u) {
      echo "<li>";
      foreach ($u as $f=>$v) echo "<b>$f</b>: $v, ";
    }
    echo "</ul></p>";
  }

}