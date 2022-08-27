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

    // Load teacher's previous state
    $newhidelist = explode(',', get_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', true));
    $newhidemap = array();
    foreach ($newhidelist as $id) {$newhidemap[$id]=true;};
    $newnicks = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentnicks', true), true);
    $newgroups = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentgroups', true), true);

    // Process groups first b/c showonlyclass depends on it. Also nicks.
    foreach ($_REQUEST as $key => $val) {
      if (substr($key, 0, 1)=='n' && is_numeric(substr($key, 1))) {
        $id = substr($key, 1);
        $nick = $val;
        $nick = preg_replace('_[<>&\"\\\\]_', "", trim($nick));
        if ($nick != '') {
          $newnicks[$id]=$nick;
	} else {
	  unset($newnicks[$id]);
	}
      }
      if (substr($key, 0, 1)=='g' && is_numeric(substr($key, 1))) {
        $id = substr($key, 1);
        $group = $val;
        $group = preg_replace('_[<>&\"\\\\]_', "", trim($group));
        if ($group != '') {
          $newgroups[$id]=$group;
	} else {
	  unset($newgroups[$id]);
	}
      }
    }

    // Process batch command that doesn't require explicit GET args
    if (getSoft($_REQUEST, 'showonlyclass', '')!='') {
      if ($_REQUEST['showonlyclass']=='<show all students>') $newhidemap = array();
      else {
        $newhidemap = array();
        foreach($newgroups as $id=>$g) {
	  if ($g != $_REQUEST['showonlyclass']) $newhidemap[$id] = true;
	}
      }
    }

    // If user manually modified visibility after showonlyclass, apply them
    foreach ($_REQUEST as $key => $val) {
      if (substr($key, 0, 1)=='s' && is_numeric(substr($key, 1))) {
        $id = substr($key, 1);
        if ($val == 'hide') {
          $newhidemap[$id]=true;
        }
        else if ($val == 'remove') {
          if (trim(Normalizer::normalize(strtolower(get_user_meta($id, 'pbguru', true))))==
              Normalizer::normalize(strtolower(wp_get_current_user()->user_login)))
            update_user_meta($id, 'pbguru', '');
          $d++;
        }
        else if ($val == 'unhide') {
	  unset($newhidemap[$id]);
        }
      }
    }

    // Encode and finish.
    update_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', implode(',', array_keys($newhidemap)));
    update_user_meta(wp_get_current_user()->ID, 'pb_studentnicks', json_encode($newnicks));
    update_user_meta(wp_get_current_user()->ID, 'pb_studentgroups', json_encode($newgroups));
    echo "<div class='updated'>Deleted $d students.</div>";
  }

echo "<script type='text/javascript'>classlist = function() {
 var classes={};
 jQuery('.grouplabel').each(function(i, elt) {classes[elt.value] = true;});
 var keys = [];
 for(var key in classes) {
   if (classes.hasOwnProperty(key)) {
     keys.push(key);
   }
 }
 keys.sort();
 jQuery('#classlist-select').html('');
 jQuery('#classlist-select').append(new Option('', ''));
 jQuery('#classlist-select').append(new Option('<show all students>', '<show all students>'));
 for (var i = 0; i < keys.length; i++) {
   if (keys[i] != '')
     jQuery('#classlist-select').append(new Option(keys[i], keys[i]));
 } 
}
selectClass = function() {
  theclass = jQuery('#classlist-select')[0].value;
  if (theclass=='') return;
  jQuery('.student-row').each(function(i, row){
    if (jQuery(row).find('.grouplabel')[0].value == theclass || theclass == '<show all students>')
     jQuery(jQuery(row).find('.unhide')[0]).prop('checked',true);
    else
     jQuery(jQuery(row).find('.hide')[0]).prop('checked',true);
  });
}
jQuery(classlist); // call once on load

// To avoid massive URLs (limit is several hundred chars, and WP doesn't support POST in this context)
// we only send info about students that were actually edited. This implements that.
jQuery(document).on('change', 'tr.student-row input', function() { 
// Remove the fake 'sparse' form tag
jQuery(this.closest('tr')).find('input').removeAttr('form'); });
</script>";

echo "<p>Students who are hidden or removed won't show up
on the Progress page. You still have access to 
messages between you and them in the Mail page histories.</p>

<p>If you hide a student, you can unhide them later.</p>
<p>If you remove a student, their guru is cleared. They would have to re-add you if you want them back.</p>
<p>Students are listed in order of registration on CS Circles (earliest first).</p>
<!--<p>Nicknames are optional. Otherwise, students appear by their e-mail and/or userid.</p>-->
";


  if (count(getStudents(true))==0) {
    echo "<div class='error'>You have no students. This page will disappear, and reappear if you get more students.</div>";
  }
  else {

  echo "<form method='get' action='users.php'>
   <input type='hidden' name='page' value='cscircles-students'>
   <input type='hidden' name='submitted' value='true'>
<table style='text-align:center'><tr><th>Unhidden</th><th>Hidden</th><th>Remove</th><th>Username</th><th>Name</th><th>E-mail</th><th>Optional nickname <br>(appears just to you, throughout site)</th><th>Optional class/section label</th></tr>";

  $hidden = get_user_meta(wp_get_current_user()->ID, 'pb_hidestudents', true);
  $nicks = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentnicks', true), true);
  $groups = json_decode(get_user_meta(wp_get_current_user()->ID, 'pb_studentgroups', true), true);

  if (!is_array($nicks)) $nicks = array();
  if (!is_array($groups)) $groups = array();
  if ($hidden == '') $hidden = '';
  $hidden = explode(",", $hidden);

  foreach (getStudents(true) as $index=>$id) {
    $user = get_userdata($id);
    
    $hid = in_array($id, $hidden);
    
    $c1 = $hid?"":"checked='true'";
    $c2 = $hid?"checked='true'":"";

    $nick = getSoft($nicks, $id, '');
    $group = getSoft($groups, $id, '');

    echo "<tr class='student-row'>
<td><input form='sparse' type='radio' class='unhide' name='s$id' $c1 value='unhide'/></td>
<td><input form='sparse' type='radio' class='hide' name='s$id' $c2 value='hide'/></td>
<td><input form='sparse' type='radio' name='s$id' value='remove'/></td>
<td>{$user->user_login}</td>
<td>{$user->user_firstname} {$user->user_lastname}</td>
<td>{$user->user_email}</td>
<td><input form='sparse' style='width:100%' type='text' name = 'n$id' value=\"$nick\"></td>
<td><input form='sparse' style='width:100%' type='text' class = 'grouplabel' name = 'g$id' value=\"$group\" onkeyup=\"javascript:classlist()\" onchange=\"javascript:classlist()\"></td>
</tr>";
  }
  echo "<tr id='classlist-tr'>
   <td colspan='7' style='text-align:right'>
   <div id='classlist-label'>Show a class and hide all other students? (Select and press <b>Submit</b> to activate.)</div>
   </td>
   <td style='text-align:left'><select name='showonlyclass' id='classlist-select' onchange='javascript:selectClass()'></select></td></tr>";

  echo "</table>
   <button class='button-primary' id='submit'>Submit</button></form>";

  echo "</div>";
  }
}