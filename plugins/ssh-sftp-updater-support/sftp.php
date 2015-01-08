<?php
/*
Plugin Name: SSH SFTP Updater Support
Plugin URI: http://phpseclib.sourceforge.net/wordpress.htm
Description: Update your Wordpress blog / plugins via SFTP without libssh2
Version: 0.4
Author: TerraFrost
Author URI: http://phpseclib.sourceforge.net/
*/

// see http://adambrown.info/p/wp_hooks/hook/<filter name>
add_filter('filesystem_method', 'phpseclib_filesystem_method', 10, 2); // since 2.6 - WordPress will ignore the ssh option if the php ssh extension is not loaded
add_filter('request_filesystem_credentials', 'phpseclib_request_filesystem_credentials', 10, 6); // since 2.5 - Alter some strings and don't ask for the public key
add_filter('fs_ftp_connection_types', 'phpseclib_fs_ftp_connection_types'); // since 2.9 - Add the SSH2 option to the connection options
add_filter('filesystem_method_file', 'phpseclib_filesystem_method_file', 10, 2); // since 2.6 - Direct WordPress to use our ssh2 class

function phpseclib_filesystem_method_file($path, $method) {
	return $method == 'ssh2' ?
		dirname(__FILE__) . '/class-wp-filesystem-ssh2.php' :
		$path;
}

function phpseclib_filesystem_method($method, $args) {
	return ( isset($args['connection_type']) && 'ssh' == $args['connection_type'] ) ? 'ssh2' : $method;
}

function phpseclib_fs_ftp_connection_types($types) {
	$types['ssh'] = __('SSH2');
	return $types;
}

// this has been pretty much copy / pasted from wp-admin/includes/file.php
function phpseclib_request_filesystem_credentials($value, $form_post, $type = '', $error = false, $context = false, $extra_fields = null) {
	if ( empty($type) )
		$type = get_filesystem_method(array(), $context);

	if ( 'direct' == $type )
		return true;

	if ( is_null( $extra_fields ) )
		$extra_fields = array( 'version', 'locale' );

	$credentials = get_option('ftp_credentials', array( 'hostname' => '', 'username' => ''));

	// If defined, set it to that, Else, If POST'd, set it to that, If not, Set it to whatever it previously was(saved details in option)
	$credentials['hostname'] = defined('FTP_HOST') ? FTP_HOST : (!empty($_POST['hostname']) ? stripslashes($_POST['hostname']) : $credentials['hostname']);
	$credentials['username'] = defined('FTP_USER') ? FTP_USER : (!empty($_POST['username']) ? stripslashes($_POST['username']) : $credentials['username']);
	$credentials['password'] = defined('FTP_PASS') ? FTP_PASS : (!empty($_POST['password']) ? stripslashes($_POST['password']) : '');

	// Check to see if we are setting the private key for ssh
	if (defined('FTP_PRIKEY') && file_exists(FTP_PRIKEY)) {
		$credentials['private_key'] = file_get_contents(FTP_PRIKEY);
	} else {
		$credentials['private_key'] = (!empty($_POST['private_key'])) ? stripslashes($_POST['private_key']) : '';
		if (isset($_FILES['private_key_file']) && file_exists($_FILES['private_key_file']['tmp_name'])) {
			$credentials['private_key'] = file_get_contents($_FILES['private_key_file']['tmp_name']);
		}
	}
	

	//sanitize the hostname, Some people might pass in odd-data:
	$credentials['hostname'] = preg_replace('|\w+://|', '', $credentials['hostname']); //Strip any schemes off

	if ( strpos($credentials['hostname'], ':') ) {
		list( $credentials['hostname'], $credentials['port'] ) = explode(':', $credentials['hostname'], 2);
		if ( ! is_numeric($credentials['port']) )
			unset($credentials['port']);
	} else {
		unset($credentials['port']);
	}

	if ( (defined('FTP_SSH') && FTP_SSH) || (defined('FS_METHOD') && 'ssh' == FS_METHOD) )
		$credentials['connection_type'] = 'ssh';
	else if ( (defined('FTP_SSL') && FTP_SSL) && 'ftpext' == $type ) //Only the FTP Extension understands SSL
		$credentials['connection_type'] = 'ftps';
	else if ( !empty($_POST['connection_type']) )
		$credentials['connection_type'] = stripslashes($_POST['connection_type']);
	else if ( !isset($credentials['connection_type']) ) //All else fails (And its not defaulted to something else saved), Default to FTP
		$credentials['connection_type'] = 'ftp';

	if ( ! $error &&
			(
				( !empty($credentials['password']) && !empty($credentials['username']) && !empty($credentials['hostname']) ) ||
				( 'ssh' == $credentials['connection_type'] && !empty($credentials['private_key']) )
			) ) {
		$stored_credentials = $credentials;
		if ( !empty($stored_credentials['port']) ) //save port as part of hostname to simplify above code.
			$stored_credentials['hostname'] .= ':' . $stored_credentials['port'];

		unset($stored_credentials['password'], $stored_credentials['port'], $stored_credentials['private_key'], $stored_credentials['public_key']);
		update_option('ftp_credentials', $stored_credentials);
		return $credentials;
	}
	$hostname = '';
	$username = '';
	$password = '';
	$connection_type = '';
	if ( !empty($credentials) )
		extract($credentials, EXTR_OVERWRITE);
	if ( $error ) {
		$error_string = __('<strong>Error:</strong> There was an error connecting to the server, Please verify the settings are correct.');
		if ( is_wp_error($error) )
			$error_string = esc_html( $error->get_error_message() );
		echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
	}

	$types = array();
	if ( extension_loaded('ftp') || extension_loaded('sockets') || function_exists('fsockopen') )
		$types[ 'ftp' ] = __('FTP');
	if ( extension_loaded('ftp') ) //Only this supports FTPS
		$types[ 'ftps' ] = __('FTPS (SSL)');

	$types = apply_filters('fs_ftp_connection_types', $types, $credentials, $type, $error, $context);

?>
<script type="text/javascript">
<!--
jQuery(function($){
	jQuery("#ssh").click(function () {
		jQuery(".ssh_keys").show();
	});
	jQuery("#ftp, #ftps").click(function () {
		jQuery(".ssh_keys").hide();
	});
	jQuery("#private_key_file").change(function (event) {
		if (window.File && window.FileReader) {
			var reader = new FileReader();
			reader.onload = function(file) {
				jQuery("#private_key").val(file.target.result);
			};
			reader.readAsBinaryString(event.target.files[0]);
		}
	});
	jQuery("form").submit(function () {
		if(typeof(Storage)!=="undefined") {
			localStorage.privateKeyFile = jQuery("#private_key").val();
		}
		jQuery("#private_key_file").attr("disabled", "disabled");
	});
	if(typeof(Storage)!=="undefined" && localStorage.privateKeyFile) {
		jQuery("#private_key").val(localStorage.privateKeyFile);
	}
	jQuery('form input[value=""]:first').focus();
});
-->
</script>
<form action="<?php echo $form_post ?>" method="post" enctype="multipart/form-data">
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e('Connection Information') ?></h2>
<p><?php
	$label_user = __('Username');
	$label_pass = __('Password');
	_e('To perform the requested action, WordPress needs to access your web server.');
	echo ' ';
	if ( ( isset( $types['ftp'] ) || isset( $types['ftps'] ) ) ) {
		if ( isset( $types['ssh'] ) ) {
			_e('Please enter your FTP or SSH credentials to proceed.');
			$label_user = __('FTP/SSH Username');
			$label_pass = __('FTP/SSH Password');
		} else {
			_e('Please enter your FTP credentials to proceed.');
			$label_user = __('FTP Username');
			$label_pass = __('FTP Password');
		}
		echo ' ';
	}
	_e('If you do not remember your credentials, you should contact your web host.');
?></p>
<table class="form-table">
<tr valign="top">
<th scope="row"><label for="hostname"><?php _e('Hostname') ?></label></th>
<td><input name="hostname" type="text" id="hostname" value="<?php echo esc_attr($hostname); if ( !empty($port) ) echo ":$port"; ?>"<?php disabled( defined('FTP_HOST') ); ?> size="40" /></td>
</tr>

<tr valign="top">
<th scope="row"><label for="username"><?php echo $label_user; ?></label></th>
<td><input name="username" type="text" id="username" value="<?php echo esc_attr($username) ?>"<?php disabled( defined('FTP_USER') ); ?> size="40" /></td>
</tr>

<tr valign="top">
<th scope="row"><label for="password"><?php echo $label_pass; ?></label></th>
<td><input name="password" type="password" id="password" value="<?php if ( defined('FTP_PASS') ) echo '*****'; ?>"<?php disabled( defined('FTP_PASS') ); ?> size="40" /></td>
</tr>

<?php if ( isset($types['ssh']) ) : ?>
<tr class="ssh_keys" valign="top" style="<?php if ( 'ssh' != $connection_type ) echo 'display:none' ?>">
<th scope="row" colspan="2"><?php _e('SSH Authentication Keys') ?>
<div><?php _e('If a passphrase is needed, enter that in the password field above.') ?></div></th></tr>
<tr class="ssh_keys" valign="top" style="<?php if ( 'ssh' != $connection_type ) echo 'display:none' ?>">
<th scope="row">
<div class="key-labels textright">
<label for="private_key"><?php _e('Copy / Paste Private Key:') ?></label>
</div>
</th>
<td><textarea name="private_key" id="private_key" cols="58" rows="10" value="<?php echo esc_attr($private_key) ?>"<?php disabled( defined('FTP_PRIKEY') ); ?>></textarea>
</td>
</tr>
<tr class="ssh_keys" valign="top" style="<?php if ( 'ssh' != $connection_type ) echo 'display:none' ?>">
<th scope="row">
<div class="key-labels textright">
<label for="private_key_file"><?php _e('Upload Private Key:') ?></label>
</div>
</th>
<td><input name="private_key_file" id="private_key_file" type="file" <?php disabled( defined('FTP_PRIKEY') ); ?>/>
</td>
</tr>
<?php endif; ?>

<tr valign="top">
<th scope="row"><?php _e('Connection Type') ?></th>
<td>
<fieldset><legend class="screen-reader-text"><span><?php _e('Connection Type') ?></span></legend>
<?php
	$disabled = disabled( (defined('FTP_SSL') && FTP_SSL) || (defined('FTP_SSH') && FTP_SSH), true, false );
	foreach ( $types as $name => $text ) : ?>
	<label for="<?php echo esc_attr($name) ?>">
		<input type="radio" name="connection_type" id="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($name) ?>"<?php checked($name, $connection_type); echo $disabled; ?> />
		<?php echo $text ?>
	</label>
	<?php endforeach; ?>
</fieldset>
</td>
</tr>
</table>

<?php
foreach ( (array) $extra_fields as $field ) {
	if ( isset( $_POST[ $field ] ) )
		echo '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . esc_attr( stripslashes( $_POST[ $field ] ) ) . '" />';
}
submit_button( __( 'Proceed' ), 'button', 'upgrade' );
?>
</div>
</form>
<?php
	return false;
}