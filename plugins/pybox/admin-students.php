<?php
add_action('admin_menu', 'manage_students_link');
function manage_students_link() {
  if (count(getStudents(true)) > 0 || isSoft($_REQUEST, 'submitted', 'true')) {
    add_users_page("Manage CS Circles Students", "Manage Students", "read", "cscircles-students", "cscircles_students_page");
  }
}

function cscircles_students_page() {

  echo "<div class='wrap'>
<h2>Manage CS Circles Students</h2>";

  if (isSoft($_REQUEST, 'submitted', 'true')) {
    $d = 0;
    $h = 0;
    $newhide = "";
    foreach ($_REQUEST as $key => $val) {
      if (substr($key, 0, 1)=='s' && is_numeric(substr($key, 1))) {
        $id = substr($key, 1);
        if ($val == 'hide') {
          if ($newhide != '') $newhide .= ",";
          $newhide .= substr($key, 1);
          $h++;
        }
        else if ($val == 'remove') {
          if (get_user_meta(substr($key, 1), 'pbguru', true)==
              wp_get_current_user()->user_login)
            update_user_meta( substr($key, 1), 'pbguru', '');
          $d++;
        }
      }
    }
    update_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', $newhide);
    echo "<div class='updated'>Deleted $d students. You have $h hidden students.</div>";
  }

echo "<p>Students who are hidden or removed won't show up
on the Progress page. You still have access to 
messages between you and them in the Mail page histories.</p>

<p>If you hide a student, you can unhide them later.</p>
<p>If you remove a student, their guru is cleared. They would have to re-add you if you want them back.</p>
<p>Students are listed in order of registration on CS Circles (earliest first).</p>";

  if (count(getStudents(true))==0) {
    echo "<div class='error'>You have no students. This page will disappear, and reappear if you get more students.</div>";
  }
  else {

  echo "<form method='get' action='users.php'>
   <input type='hidden' name='page' value='cscircles-students'>
   <input type='hidden' name='submitted' value='true'>
<table style='text-align:center'><tr><th>Unhidden</th><th>Hidden</th><th>Remove</th><th>Username</th><th>Name</th><th>E-mail</th></tr>";

  $hidden = get_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', true);
  if ($hidden == '') $hidden = '';
  $hidden = explode(",", $hidden);

  foreach (getStudents(true) as $index=>$id) {
    $user = get_userdata($id);
    
    $hid = in_array($id, $hidden);
    
    $c1 = $hid?"":"checked='true'";
    $c2 = $hid?"checked='true'":"";

    echo "<tr>
<td><input type='radio' name='s$id' $c1 value='unhide'/></td>
<td><input type='radio' name='s$id' $c2 value='hide'/></td>
<td><input type='radio' name='s$id' value='remove'/></td>
<td>{$user->user_login}</td>
<td>{$user->user_firstname} {$user->user_lastname}</td>
<td>{$user->user_email}</td>
</tr>";
  }
  echo "</table>
   <button class='button-primary' id='submit'>Submit</button></form>";

  echo "</div>";
  }
}