<?php

function user_pb_options_fields( $user ) { 
  $checkd = "";
  if (get_the_author_meta( 'pbplain', $user->ID )=='true') $checkd = ' checked="yes"  ';
  /* $oh = "";
  if (get_the_author_meta( 'pboldhistory', $user->ID )=='true') $oh = ' checked="yes"  ';*/
  $nocc = "";
  if (get_the_author_meta( 'pbnocc', $user->ID )=='true') $nocc = ' checked="yes"  ';
  $optout = "";
  if (get_the_author_meta( 'pboptout', $user->ID )=='true') $optout = ' checked="yes"  ';
  $guru_login = get_the_author_meta( 'pbguru', $user->ID );
  $gurucheck = '';
  if (trim($guru_login) != '') {
    global $wpdb;
    $guruid = $wpdb->get_var($wpdb->prepare('SELECT ID from '.$wpdb->prefix.'users WHERE user_login = %s', $guru_login));
    if ($guruid === NULL) 
      $gurucheck = 
	"<b>".sprintf(__t("The username %s does not exist."), "<code>" . htmlspecialchars($guru_login) . "</code>")."</b>";
    else
      $gurucheck = 
	sprintf(__t("%s exists! They are your guru."), "<code>" . htmlspecialchars($guru_login) . "</code> ");
  }
  else 
    $gurucheck = __t("Enter the username of your guru. After you press <i>Update profile</i> we'll see if they exist.");
  
  $guruinput = '<input type="text" name="pbguru" id="pbguru" value="'. htmlspecialchars($guru_login) . '"> ' . $gurucheck .'<br/>';
  
?>
  <h3>Computer Science Circles Options</h3>
     <table class="form-table">
     <tr><th><label for="pbguru"><?php echo __t('Guru&apos;s <i>Username</i> (blank for none)'); ?></label></th>
				<td>
		     <?php echo $guruinput . __t("Any other person with a CS Circles account (such as your teacher) can be your guru. You can ask them direct questions when you get stuck, and they can view your progress.");
?>
	 </input>
       </td>
       </tr>
       <tr>
	     <th><label for="pbplain"><?php echo __t("Disable Rich Editor");?></label></th>
       <td>
     <input type="checkbox" name="pbplain" id="pbplain"<?php echo $checkd ." > ".
     __t("(default: unchecked) If checked, the rich editor (see Lesson 7) is always replaced by a plain editor."); ?></input>
       </td>
       </tr>
       <tr>
	     <th><label for="pbnocc"><?php echo __t("Don&apos;t Send Mail Copies");?></label></th>
       <td>
     <input type="checkbox" name="pbnocc" id="pbnocc"<?php echo $nocc ." > ".
     __t("(default: unchecked) If checked, you will not receive a carbon copy when you send a message."); ?></input>
       </td>
       </tr>

       <tr>
														<th><label for="pboptout"><?php echo __t("Opt Out of Mass Emails"); ?></label></th>
       <td>
     <input type="checkbox" name="pboptout" id="pboptout"<?php echo $optout . " > ".
 __t("(default: unchecked) If checked, you will not receive announcements from CS Circles. They are pretty infrequent, about once per year.");?></input>
       </td>
       </tr>

       <tr>
														<th><label for="pbprogress"><?php echo __t("Hide/Restore Progress?"); ?></label></th>
                                                                                                                                                                                            <td><?php echo __t("If you want to restart from scratch, select an option below before updating your profile.");?>
 <br><input type="radio" name="pbprogress" value="none" checked="true">
   <?php echo __t("Do nothing");?></input>
 <br><input type="radio" name="pbprogress" value="hide">
   <?php echo __t("Hide all my progress and past submissions.");?></input>
 <br><input type="radio" name="pbprogress" value="restore">
 <?php echo __t("Restore all progress and past submissions I have ever hidden.");?></input>
       </td>
       </tr>
       </table>
    <?php }

add_action( 'show_user_profile', 'user_pb_options_fields' );
add_action( 'edit_user_profile', 'user_pb_options_fields' );

// store pb_options
function user_pb_options_fields_save( $user_id ) {
  //pyboxlog('save' . print_r($_POST, TRUE));
  if ( !current_user_can( 'edit_user', $user_id ) )
    return false;
  update_user_meta( $user_id, 'pbplain', ($_POST['pbplain']=='on')?'true':'false' );
  echo $_POST['pbplain'];
  /* update_user_meta( $user_id, 'pboldhistory', ($_POST['pboldhistory']=='on')?'true':'false' );*/
  $guruname = $_POST['pbguru'];
  global $wpdb;
  $guru_fixedname = $wpdb->get_var($wpdb->prepare('SELECT user_login from '.$wpdb->prefix.'users WHERE lower(trim(user_login)) = lower(trim(%s))', $guruname));
  if ($guru_fixedname !== NULL) $guruname = $guru_fixedname;

  update_user_meta( $user_id, 'pbguru', ($guruname));
  update_user_meta( $user_id, 'pbnocc', ($_POST['pbnocc']=='on')?'true':'false' );
  update_user_meta( $user_id, 'pboptout', ($_POST['pboptout']=='on')?'true':'false' );

  if ($_POST['pbprogress']=='hide' || $_POST['pbprogress']=='restore') {
    // Too lazy to care that this fails for admin (user 0).
    $old_id = $_POST['pbprogress']=='hide' ? $user_id : -$user_id;
    $new_id = $_POST['pbprogress']=='hide' ? -$user_id : $user_id;
    global $wpdb;
    $update = array("userid" => $new_id);
    $where = array("userid" => $old_id);
    $wpdb->update("wp_pb_submissions", $update, $where);
    $wpdb->update("wp_pb_completed", $update, $where);
  }
}
add_action( 'personal_options_update', 'user_pb_options_fields_save' );
add_action( 'edit_user_profile_update', 'user_pb_options_fields_save' );

// end of file