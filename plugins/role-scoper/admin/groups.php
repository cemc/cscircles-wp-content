<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/* this file adapted from:
 Group Restriction plugin
 http://code.google.com/p/wp-group-restriction/
 Tiago Pocinho, Siemens Networks, S.A.
 */

/*************************************************************
 * This File loads the "Groups -> Groups" Tab
 * It allows to manage Groups by editing, adding or deleting
 ************************************************************/

require_once( dirname(__FILE__).'/groups-support.php');

global $wpdb;
global $scoper;

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
$cancel = isset($_REQUEST['cancel']) ? $_REQUEST['cancel'] : '';

$success_msg = '';
$errorMessage = "";
$all_users = array();
$suppress_groups_list = false;

$can_manage_all_groups = is_user_administrator_rs() || awp_user_can('manage_groups', BLOG_SCOPE_RS);

switch ($mode) {
	case "add":
		if( ! $can_manage_all_groups )
			wp_die(__awp('Cheatin&#8217; uh?'));
	
		check_admin_referer( 'scoper-edit-groups' );
		
		if ( ! empty($_POST['groupName']) ) {
			$_POST['groupName'] = str_replace( '[', '', $_POST['groupName'] );
			$_POST['groupName'] = str_replace( ']', '', $_POST['groupName'] );
		}
			
		if( ! UserGroups_tp::isValidName($_POST['groupName'])) {
			if($_POST['groupName'] == "")
				$errorMessage = __("Please specify a name for the group.", 'scoper');
			else
				$errorMessage = sprintf( __("A group with the name <strong>%s</strong> already exists.", 'scoper'), $_POST['groupName']);
		} else {
			if( UserGroups_tp::createGroup ($_POST['groupName'], $_POST['groupDesc'])){
				$success_msg = sprintf( __("Group <strong>%s</strong> created successfuly.", 'scoper'), $_POST['groupName']);
				
				$group = UserGroups_tp::getGroupByName($_POST['groupName']);
			}
		}
		//$suppress_groups_list = true;
		break;
		
	case "edit":
		$id = $_REQUEST['id'];
		
		if( ! is_user_administrator_rs() && ! current_user_can('recommend_group_membership', $id) && ! current_user_can('manage_groups', $id) )
			wp_die(__awp('Cheatin&#8217; uh?'));
		
		$group = ScoperAdminLib::get_group($id);
		
		if ( $group->meta_id && ! strpos($group->meta_id, '_ed_') )
			die( __('This meta group is automatically populated. You cannot manually edit it.', 'scoper') );
			
		$group->prev_name = $group->display_name;
		$suppress_groups_list = true;
		break;
		
	case "editSubmit":
		$group->ID = $_POST['groupID'];
		
		if ( $can_manage_all_groups || current_user_can( 'manage_groups', $_POST['groupID'] ) ) {
			if ( ! empty($_POST['groupName']) ) {
				$_POST['groupName'] = str_replace( '[', '', $_POST['groupName'] );
				$_POST['groupName'] = str_replace( ']', '', $_POST['groupName'] );
			}
			
			//to continue edit
			$group->display_name = $_POST['groupName'];
			$group->prev_name = $_POST['prevName'];
			$group->descript = $_POST['groupDesc'];
			
			if ( $get_group = ScoperAdminLib::get_group($group->ID) )
				$group->meta_id = $get_group->meta_id;

			check_admin_referer( 'scoper-edit-group_' . $group->ID );
				
			if ( ! $group->meta_id ) {  // editable metagroups can have users edited but not group name\
				if(! UserGroups_tp::isValidName($_POST['groupName']) && $_POST['groupName'] != $_POST['prevName']){
					if($_POST['groupName'] == ""){
						$errorMessage = __("Please specify a name for the group.", 'scoper');
						$mode = "edit";
					} else {
						$errorMessage = sprintf( __("A group with the name <strong>%s</strong> already exists.", 'scoper'), $_POST['groupName']);
						$mode = "edit";
					}
				} else {
					if ( UserGroups_tp::updateGroup ($_POST['groupID'], $_POST['groupName'], $_POST['groupDesc']) ) {
						$success_msg = sprintf( __("Group <strong>%s</strong> updated successfuly.", 'scoper'), $_POST['groupName']);
					} else {
						$errorMessage = sprintf( __("Group <strong>%s</strong> was not updated successfuly.", 'scoper'), $_POST['prevName']);
						$mode = "edit";
					}
				}
			} else
				$success_msg = sprintf( __("Group <strong>%s</strong> updated successfuly.", 'scoper'), '');
		} else { // endif logged user is a group manager / administrator
			$success_msg = __("Group members updated successfuly.", 'scoper');
		}
		
		break;
		
	case "delete":
		$idDel = $_REQUEST['id'];
		if($idDel != ""){
			if( ! $can_manage_all_groups && ! current_user_can('manage_groups', $idDel) )
				wp_die(__awp('Cheatin&#8217; uh?'));
		
			check_admin_referer( 'scoper-edit-group_' . $idDel );
				
			if(UserGroups_tp::deleteGroup($idDel)){
				UserGroups_tp::write( __("Group Deleted.", 'scoper') );
			}else{
				UserGroups_tp::write( __("Invalid group. No groups were deleted.", 'scoper'), false);
			}
			$_REQUEST['id'] = "";
		}
		break;
		
	case "approve":
		if( awp_ver('2.8') && scoper_get_option( 'group_ajax' ) ) {	
			if ( $can_manage_all_groups || current_user_can( 'manage_groups', $_GET['id'] ) )
				$status = 'active';
			elseif ( current_user_can( 'recommend_group_membership', $_GET['id'] ) )
				$status = 'recommended';
			else
				break;

			$update_user = new WP_User( $_GET['user'] );
			$update_group = ScoperAdminLib::get_group( $_GET['id'] );
			
			if ( $update_user->ID && $update_group->ID ) {
				ScoperAdminLib::update_group_user( $_GET['id'], $_GET['user'], $status );
				$success_msg = __("Group members updated successfuly.", 'scoper');
			} else {
				$errorMessage =  __("Error updating group membership.", 'scoper');
			}
		}
			
		break;
		
	default:
		switch($cancel){
			case 1:
				UserGroups_tp::write( __("Group edit cancelled.", 'scoper') );
				break;
			default:
				break;
		}
		break;
}

if ( ! $errorMessage && ( ('editSubmit' == $mode) || ('add' == $mode) || ('approve' == $mode) ) ) {
	// -----  add/delete group members or managers ----

	// members
	$current_members = array();
	
	if( scoper_get_option( 'group_ajax' ) ) {
		$current_members['active'] = ScoperAdminLib::get_group_members($group->ID, COL_ID_RS);
		UserGroups_tp::update_group_members_multi_status( $group->ID, $current_members );
	} else {
		$current_members = ScoperAdminLib::get_group_members($group->ID, COL_ID_RS);
		$posted_members = ( isset($_POST['member']) ) ? $_POST['member'] : array();
		
		if ( ! empty($_POST['member_csv']) ) {
			if ( $csv_for_item = ScoperAdminLib::agent_ids_from_csv( 'member_csv', 'user' ) )
				$posted_members = array_merge($posted_members, $csv_for_item);
		}
		
		if ( $delete_members = array_diff($current_members, $posted_members) ) {
			ScoperAdminLib::remove_group_user($group->ID, $delete_members);
			$success_msg .= ' ' . sprintf( _n('%d member deleted.', '%d members deleted.', count($delete_members), 'scoper'), count($delete_members) ); 
		}
			
		if ( $new_members = array_diff($posted_members, $current_members) ) {
			ScoperAdminLib::add_group_user($group->ID, $new_members);
			$success_msg .= ' ' . sprintf( _n('%d member added.', '%d members added.', count($new_members), 'scoper'), count($new_members) ); 
		}
	}
	
	if ( $can_manage_all_groups || current_user_can('manage_groups', $group->ID) ) {
		$role_assigner = init_role_assigner();
		
		if ( $can_manage_all_groups ) {
			// group managers
			$users = ( isset($_POST['manager']) ) ? array_fill_keys( $_POST['manager'], 'entity' ) : array();
			
			if ( ! empty($_POST['manager_csv']) ) {
				if ( $csv_for_item = ScoperAdminLib::agent_ids_from_csv( 'manager_csv', 'user' ) ) {
					foreach ( $csv_for_item as $id )
						$users[$id] = 'entity';
				}
			}
			
			$role_arg = array( 'rs_group_manager' => $users );
			
			$args = array( 'implicit_removal' => true );
			$role_assigner->assign_roles( OBJECT_SCOPE_RS, 'group', $group->ID, $role_arg, ROLE_BASIS_USER, $args );
		}
		
		if ( scoper_get_option( 'group_ajax' ) ) {
			// group moderators
			$users = ( isset($_POST['moderator']) ) ? array_fill_keys( $_POST['moderator'], 'entity' ) : array();
			
			if ( ! empty($_POST['moderator_csv']) ) {
				if ( $csv_for_item = ScoperAdminLib::agent_ids_from_csv( 'moderator_csv', 'user' ) ) {
					foreach ( $csv_for_item as $id )
						$users[$id] = 'entity';
				}
			}
			
			$role_arg = array( 'rs_group_moderator' => $users );
			$args = array( 'implicit_removal' => true, 'user_has_role' => array_fill_keys( array( 'rs_group_manager','rs_group_moderator' ), true ) );

			$role_assigner->assign_roles( OBJECT_SCOPE_RS, 'group', $group->ID, $role_arg, ROLE_BASIS_USER, $args );
		}
	}
	// -------- end member / manager update ----------

	UserGroups_tp::write( $success_msg );
	
	if ( 'add' == $mode )
		$group = '';
}
?>

<?php if ( ! $suppress_groups_list) :?>
	<div class="wrap">
	<h2><?php 
	$groups_caption = ( defined( 'GROUPS_CAPTION_RS' ) ) ? GROUPS_CAPTION_RS : __('Role Groups', 'scoper');
	echo( $groups_caption );

	if( $can_manage_all_groups ) {
		if ( MULTISITE && scoper_get_option('mu_sitewide_groups') )
			$url = ( awp_ver('3.1') ) ? 'users.php' : 'ms-admin.php';
		else
			$url = 'admin.php';
		
		$url_def = "$url?page=rs-default_groups'";
		$url_members = "$url?page=rs-group_members'";
		echo ' <span style="font-size: 0.6em; font-style: normal">( ';
		echo '<a href="#new">' . __('add new') . '</a>';
		echo " &middot; <a href='$url_def'>" . __('set defaults') . '</a>';
		echo " &middot; <a href='$url_members'>" . __('browse members') . '</a>';
		echo ' )</span>';
	}
	
	echo '</h2>';
	
	if ( scoper_get_option('display_hints') ) {
		echo '<div class="rs-hint">';
		
		if ( defined( 'GROUPS_HINT_RS' ) )
			echo( GROUPS_HINT_RS );
		else
			_e( 'By creating User Groups, you can assign RS roles to multiple users.  Note that group membership itself has no effect on the users until you assign roles to the group.', 'scoper' );

		echo '</div><br />';
	}
	
	$results = ScoperAdminLib::get_all_groups(FILTERED_RS, COLS_ALL_RS, array( 'include_norole_groups' => true, 'reqd_caps' => 'recommend_group_membership' ) );
	
	$i = 0;
	if ( isset($results) && count($results) ) {
		$msg = __( 'You are about to delete the group %s. Do you wish to continue?', 'scoper');
		$msg = sprintf( $msg, '"\'+name+\'"' );
		?> <script type="text/javascript">
		function DelConfirm(name){
			var message= '<?php echo($msg);?>';
			return confirm(message);
		}
		</script>
		<table class="rs-member_table" width="100%" border="0" cellspacing="3" cellpadding="3">
		<tr class="thead">
			<th><?php echo __awp('Name'); ?>
			</th>
			<th><?php echo __awp('Description'); ?></th>
			<th style="width:5em;">&nbsp;</th>
		</tr>
		<?php
		foreach ($results as $result) {
			if ( ! defined( 'RVY_VERSION' ) || defined('SCOPER_DEFAULT_MONITOR_GROUPS') )
				if ( ( $result->meta_id == 'rv_pending_rev_notice_ed_nr_' ) || ( $result->meta_id == 'rv_scheduled_rev_notice_ed_nr_' ) )
					continue;

			if($i%2 == 0)
				$style = 'class=\'alternate\'';
			else
				$style = '';
		 ?>
		<tr <?php echo $style; ?>>
			<td><?php 
			$name = ( $result->meta_id ) ? ScoperAdminLib::get_metagroup_name( $result->meta_id, $result->display_name ) : $result->display_name;

			if ( ( ! $result->meta_id || strpos($result->meta_id, '_ed_') ) && ( is_user_administrator_rs() || current_user_can('recommend_group_membership', $result->ID) ) ) {
				$url = "admin.php?page=rs-groups&amp;mode=edit&amp;id={$result->ID}";
				echo "<a class='edit' href='$url'>$name</a>";
			} else
				echo $name;
			?>
			</td>
			<td><?php 
			if ( $result->meta_id )
				echo ScoperAdminLib::get_metagroup_descript( $result->meta_id, $result->descript );
			else
				echo $result->descript;
			?></td>
			<td>
			<?php if ( ! $result->meta_id && ( $can_manage_all_groups || current_user_can('manage_groups', $result->ID) ) ):?>
			<?php 
			$url = "admin.php?page=rs-groups&amp;mode=delete&amp;id={$result->ID}";
			$url = wp_nonce_url( $url, 'scoper-edit-group_' . $result->ID );
			?>
			<a class="delete"
				href="<?php echo($url);?>"
				onClick="return DelConfirm('<?php echo $result->display_name; ?>');"><?php echo __awp('Delete');?></a>
			<?php endif;?>
			</td>
		</tr>
	
		<?php
			$i++;
		} // end foreach ($results)
	?>
	</table>
	<?php
	}//close if(isset($results) && count($results)>0)
	else {
		echo "<p><strong>" . __('No groups available.', 'scoper') . "</strong></p>";
	}
	?>
	</div>
<?php endif; /* endif showing groups list */ ?>

<?php
if ( $errorMessage ) {
	UserGroups_tp::write($errorMessage,false, "msg");
}
if ( ($mode != "edit") && ( ('editSubmit' == $mode) || empty($_POST['prevName']) ) ) {
?>
	<?php
	if ( $can_manage_all_groups ):
	?>
		<br /><br />
		<div class="agp-width97 rs-newgroup" id="new">
		<h2><?php _e('Create New Group', 'scoper');?></h2>
		<form action="admin.php?page=rs-groups&amp;mode=add#msg" method="post">
		<?php
		$group_id = 0;
		if ( isset($group) )
			unset($group);
		wp_nonce_field( 'scoper-edit-groups' );
		
		$submitName = "Create";
	else:
		$suppress_form = true;
	endif;
} else {
	$submitName = "Update";
	?>
	<div class="wrap">
	<h2><?php printf(__('Edit Group: %s', 'scoper'), $group->display_name);?></h2>
	<form action="admin.php?page=rs-groups&amp;mode=editSubmit#msg" method="post">
	<input type="hidden" name="prevName" value="<?php echo $group->prev_name; ?>" />
	<input type="hidden" name="groupID" value="<?php echo $group->ID; ?>" />
	<?php 
	wp_nonce_field( 'scoper-edit-group_' . $group->ID );
}

if ( empty($suppress_form) ):
?>
	<span class="submit" style='border:none;float:right'>
	<input type="submit" value="<?php echo $submitName; ?>"/>
	</span>

<fieldset>

<?php 
if( empty($group->meta_id) ) :?>

<?php
	$can_manage_this_group = $can_manage_all_groups || current_user_can('manage_groups', $group->ID);
?>

<table style="width: 100%;">
	<tr>
		<td style="width:0.7em;"><strong>*</strong></td>
		<td style="width:4em;"><strong><?php echo __awp('Name');?>:</strong></td>
		<td><input style="width: 250px;" type="text" name="groupName" <?php if ( ! $can_manage_this_group ) echo "disabled=disabled";?>
		<?php if(isset($group) && is_object($group)) echo 'value="' . $group->display_name . '"';?>/></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td style="vertical-align:top;"><strong><?php echo __awp('Description');?>:</strong></td>
		<td><textarea style="width: 90%;" name="groupDesc" id="groupDesc" rows="2" cols="30" <?php if ( ! $can_manage_this_group ) echo "disabled=disabled";?> ><?php if(isset($group) && is_object($group)) echo $group->descript; ?></textarea>
		</td>
	</tr>
</table>
<?php endif;?>

<div class="rs-group_members">
<h3><?php _e('Group Members', 'scoper');?></h3>
<?php
$group_id = ( ! empty($group) ) ? $group->ID : 0;

// force_all_users arg is a temporary measure to ensure that any user can be viewed / added to a sitewide MU group regardless of what blog backend it's edited through 
$sitewide_groups = IS_MU_RS && scoper_get_site_option( 'mu_sitewide_groups' );
$_args = ( $sitewide_groups ) ? array( 'force_all_users' => true ) : array();

$all_users = $scoper->users_who_can('', COLS_ID_NAME_RS, '', '', $_args );  // need this for group managers checklist even if using Ajax UI for members

if ( scoper_get_option( 'group_ajax' ) ) {
	require_once( dirname(__FILE__).'/user_search_ui_rs.php' );

	$arr_display_names = array();

	$status_users = array();
	$status_users['active'] = ScoperAdminLib::get_group_members( $group_id, COLS_ID_NAME_RS, false, array( 'status' => 'active' ) );
	$status_users['recommended'] = ScoperAdminLib::get_group_members( $group_id, COLS_ID_NAME_RS, false, array( 'status' => 'recommended' ) );
	$status_users['requested'] = ScoperAdminLib::get_group_members( $group_id, COLS_ID_NAME_RS, false, array( 'status' => 'requested' ) );

	foreach ( $status_users as $key => $users )
		foreach ( $users as $user )
			$arr_display_names [$key][$user->ID]= $user->display_name;
	
	global $scoper_user_search;
	
	$scoper_user_search->output_html( $arr_display_names, 'users' );
	
} else {
	UserGroups_tp::group_members_checklist( $group_id, 'member', $all_users );
}
?>
</div>

<?php 
if ( $can_manage_all_groups || current_user_can('manage_groups', $group->ID) ):

	// blog_path will be used in caption for Group Administrator and Group Moderator listing
	if ( $sitewide_groups ) {
		global $blog_id;
	
		$list = scoper_get_blog_list( 0, 'all' );
		
		$blog_path = '';
		foreach ( $list as $blog ) {
			if ( $blog['blog_id'] == $blog_id ) {
				$blog_path = $blog['path'];
				break;
			}
		}
	}
?>

<?php if ( $can_manage_all_groups ) : ?>
<div style="clear:both;"></div>
<div class="rs-group_admins">
<h3><?php 
if ( $sitewide_groups )
	printf( __('Group Administrators %1$s(via login to %2$s)%3$s', 'scoper'), '<span style="font-weight: normal">', rtrim($blog_path, '/'), '</span>' );
else
	_e('Group Administrators', 'scoper');
?>
</h3>
<?php
UserGroups_tp::group_members_checklist( $group_id, 'manager', $all_users );
?>
</div>
<?php endif; ?>


<?php if ( scoper_get_option( 'group_ajax' ) ) : ?>
<div style="clear:both;"></div>
<div class="rs-group_admins">
<h3><?php 
if ( $sitewide_groups )
	printf( __('Group Moderators %1$s(via login to %2$s)%3$s', 'scoper'), '<span style="font-weight: normal">', rtrim($blog_path, '/'), '</span>' );
else
	_e('Group Moderators', 'scoper');
?>
</h3>
<?php
	UserGroups_tp::group_members_checklist( $group_id, 'moderator', $all_users );
?>
</div>
<?php endif; ?>


<div style="clear:both;"></div>

<div class="rs-scoped_role_profile">
<?php 
do_action('edit_group_profile_rs', $group_id);
?>
</div>

<?php
endif; // current user is a group administrator (otherwise we suppress Administrators, Moderators and Group Roles UI)
?>


<?php if ( 'edit' == $mode ):?>
<a href="javascript:void(0)" class="button" style="padding:0.35em; margin-right: 1em;" onclick="javascript:location.href='admin.php?page=rs-groups&amp;cancel=1'">
<?php echo __awp('Cancel');?>
</a>
<?php endif;?>

<span class="submit" style='border:none;'>
<input type="submit" value="<?php echo $submitName; ?>"/>
</span>

</fieldset>

</form>
</div>
<?php endif;?>