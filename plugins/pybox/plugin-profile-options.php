<?php

function user_pb_options_fields( $user ) { 
  $checkd = "";
  if (get_the_author_meta( 'pbplain', $user->ID )=='true') $checkd = ' checked="yes"  ';
  $oh = "";
  if (get_the_author_meta( 'pboldhistory', $user->ID )=='true') $oh = ' checked="yes"  ';
  $guru_login = get_the_author_meta( 'pbguru', $user->ID );
  $gurucheck = '';
  if (trim($guru_login) != '') {
    global $wpdb;
    $guruid = $wpdb->get_var($wpdb->prepare('SELECT ID from wp_users WHERE user_login = %s', $guru_login));
    if ($guruid === NULL) 
      $gurucheck = "<b>The username <code>" . htmlspecialchars($guru_login) . "</code> does not exist.</b>";
    else
      $gurucheck = "<code>" . htmlspecialchars($guru_login) . "</code> exists! They are your guru.";
  }
  else 
    $gurucheck = "Enter the username of your guru. After you press <i>Update profile</i> we'll see if they exist.";
  
?>
  <h3>Computer Science Circles Options</h3>
     <table class="form-table">
     <tr><th><label for="pbguru">Guru's <i>Username</i> (blank for none)</label></th>
       <td>
    <input type="text" name="pbguru" id="pbguru" value="<?php echo htmlspecialchars($guru_login) . '"> ' . $gurucheck; ?><br/>
     Any other person with a CS Circles account (such as your teacher) can be your guru. You can ask them direct questions when you get stuck, and they can view your progress.
	 </input>
       </td>
       </tr>
       <tr>
       <th><label for="plain">Disable Rich Editor</label></th>
       <td>
     <input type="checkbox" name="pbplain" id="pbplain"<?php echo $checkd; ?> >
     (default: unchecked) Check to disable auto-loading of the rich editor. Good for some older browsers.</input>
       </td>
       </tr>
       <tr>
       <th><label for="pboldhistory">Disable History Table</label></th>
       <td>
     <input type="checkbox" name="pboldhistory" id="pboldhistory"<?php echo $oh; ?> >
       (default: unchecked) Make history opens in new window in ugly table. Good for some older browsers.</input>
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
  update_user_meta( $user_id, 'pboldhistory', ($_POST['pboldhistory']=='on')?'true':'false' );
  update_user_meta( $user_id, 'pbguru', ($_POST['pbguru']));
}
add_action( 'personal_options_update', 'user_pb_options_fields_save' );
add_action( 'edit_user_profile_update', 'user_pb_options_fields_save' );

// end of file