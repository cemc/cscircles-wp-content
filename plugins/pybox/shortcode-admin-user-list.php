<?php

add_shortcode('adminuserlist', 'adminuserlist');

function adminuserlist($options, $content) {
  if (!userIsAdmin()) return;

  //resendEmails();

  // 10000 per page
  $page = intval($_GET['chunk']);
  $lo = $page*10000;
  $hi = ($page+1)*10000;

  global $wpdb;
  $rows = $wpdb->get_results('select id, user_login, user_email from '.$wpdb->prefix."users where id >= $lo and id < $hi ");
  $r = '<a class="open-same-window" href="?chunk=' . ($page-1) . '">prev 10k</a> <a class="open-same-window" target="_self" href="?chunk=' . ($page+1) . '">next 10k</a>'; 
  $r .= '<table><tr><th>id</th><th>login</th><th>email</th></tr>';
  foreach ($rows as $row) {
    $r .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>", 
		  $row->id, $row->user_login, $row->user_email);
  }
  $r .= '</table>';
  return $r;
}

