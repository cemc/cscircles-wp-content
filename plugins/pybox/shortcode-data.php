<?php

add_shortcode('pbgraph', 'pbgraph');

function pbgraph($options, $content) {
  global $wpdb;
  $res = $wpdb->get_results('SELECT ustudent, problem, time FROM `wp_pb_mail` where ustudent > 2 and ufrom = ustudent group by ustudent order by count(1) desc');
  //  foreach ($res as $ask) {
  //    $replyTime = $wpdb->
  //  }
}

//for each mail (M)
//if that mail is from a student
//find the first reply (R)
//     graph F-R (finite or infinite) and R-M

//make a 3d manhattan chart?
//EOF