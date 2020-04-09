<?php

  require_once("include-to-load-wp.php");

  function csvify($s) {
    return '"'.str_replace('"', "'", strip_tags($s)).'"';
  }

  $hidden = explode(",", get_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', true));
  $nicks = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentnicks', true), true);
  $groups = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentgroups', true), true);

  $my_students = getStudents();
  $completed_table = $wpdb->prefix . "pb_completed";
  if (count($my_students) == 0) {
    echo "You have no students, or they are all hidden.";
    return;
  }
  $completed = $wpdb->get_results
    ("SELECT * FROM $completed_table WHERE userid in (".implode(',', $my_students).')', ARRAY_A);
  $completed_map = array();
  foreach ($completed as $crow) {
    $completed_map[$crow['userid']][$crow['problem']] = TRUE;
  }

  $num_found = 0;
  $problem_table = $wpdb->prefix . "pb_problems";
  $problems = $wpdb->get_results
    ("SELECT * FROM $problem_table WHERE facultative = 0 AND lang = '".currLang2()."' AND lesson IS NOT NULL ORDER BY lesson ASC, boxid ASC", ARRAY_A);
  foreach ($my_students as $id) {
    $hid = in_array($id, $hidden);
    if ($hid) continue;
    if ($num_found == 0) {
      header("Content-Type: text/plain");
      echo "id,email,name,group";
      foreach ($problems as $prow) {
        echo ','.csvify($prow['publicname']);
      }
      echo "\n";
    }
    $num_found += 1;
    $user = get_userdata($id);
    $group = getSoft($groups, $id, '[no group]');
    $desc = "{$user->user_firstname} {$user->user_lastname} " . userString($id);
    echo "$id,".csvify($user->user_email).",".csvify($desc).",".csvify($group);
    foreach ($problems as $prow) {
      echo ',';
      echo getSoft(getSoft($completed_map, $id, array()), $prow['slug'], 0);
    }
    echo "\n";
  }

  if ($num_found == 0) {
    echo "You have no students, or they are all hidden.";
  }