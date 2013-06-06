<?php

add_shortcode('adminuserlist', 'adminuserlist');

function adminuserlist($options, $content) {
  if (!userIsAdmin()) return;

  resendEmails();

  global $wpdb;
  $rows = $wpdb->get_results('select id, user_login, user_email from wp_users');
  $r = '<table><tr><th>id</th><th>login</th><th>email</th></tr>';
  foreach ($rows as $row) {
    $r .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>", 
		  $row->id, $row->user_login, $row->user_email);
  }
  $r .= '</table>';
  return $r;
}

