<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

function scoper_admin_section_restrictions($taxonomy) {
global $scoper, $scoper_admin;

$tx = $scoper->taxonomies->get($taxonomy);
if ( empty($tx) || empty($tx->requires_term) )
	wp_die(__('Invalid taxonomy', 'scoper'));

$is_administrator = is_administrator_rs($tx, 'user');

if ( ! $scoper_admin->user_can_admin_terms($taxonomy) )
	wp_die(__awp('Cheatin&#8217; uh?'));

require_once( dirname(__FILE__).'/admin-bulk_rs.php' );
$role_assigner = init_role_assigner();

$nonce_id = 'scoper-assign-roles';

$role_codes = ScoperAdminBulk::get_role_codes();

echo '<a name="scoper_top"></a>';


// retrieve all terms to track hierarchical relationship, even though some may not be adminable by current user
$val = ORDERBY_HIERARCHY_RS;
$args = array( 'order_by' => $val );
$all_terms = $scoper->get_terms($taxonomy, UNFILTERED_RS, COLS_ALL_RS, 0, $args);

// =========================== Submission Handling =========================
if ( isset($_POST['rs_submit']) ) {
	$err = ScoperAdminBulk::role_submission(TERM_SCOPE_RS, ROLE_RESTRICTION_RS, '', $taxonomy, $role_codes, '', $nonce_id);
	
	if ( scoper_get_option( 'file_filtering' ) )
		scoper_flush_file_rules();
} else
	$err = 0;


// =========================== Prepare Data ===============================
$tx_src = $scoper->data_sources->get( $tx->source );

if ( $col_id = $tx_src->cols->id ) {
	// determine which terms current user can admin
	if ( $admin_terms = $scoper->get_terms($taxonomy, ADMIN_TERMS_FILTER_RS, COL_ID_RS) ) {
		$admin_terms = array_fill_keys( $admin_terms, true );
	}
} else
	$admin_terms = array();

// =========================== Display UI ===============================
?>

<div class="wrap agp-width97">
<?php 
$tx_label = $tx->labels->singular_name;
$src_label = $scoper->data_sources->member_property( $tx->object_source, 'labels', 'singular_name' );

echo '<h2>' . sprintf(__('%s Restrictions', 'scoper'), $tx_label ); 
echo '&nbsp;&nbsp;<span style="font-size: 0.6em; font-style: normal">(<a href="#scoper_notes">' . __('see notes', 'scoper') . '</a>)</span></h2>';
if ( scoper_get_option('display_hints') ) {
	echo '<div class="rs-hint">';
	if ( 'category' == $taxonomy && scoper_get_otype_option('use_object_roles', 'post', 'post') )
		printf(__('Reduce access by requiring some role(s) to be %1$s%2$s-assigned%3$s (or %4$s-assigned). Corresponding General Roles (whether assigned by WordPress or Role Scoper) are ignored.', 'scoper'), "<a href='admin.php?page=rs-$taxonomy-roles_t'>", $tx_label, '</a>', $src_label );
	else
		printf(__('Reduce access by requiring some role(s) to be %1$s%2$s-assigned%3$s. Corresponding General Role assignments are ignored.', 'scoper'), "<a href='admin.php?page=rs-$taxonomy-roles_t'>", $tx_label, '</a>');
	echo '</div>';
}

if ( ! $role_defs_by_otype = $scoper->role_defs->get_for_taxonomy($tx->object_source, $taxonomy) ) {
	echo '<br />' . sprintf (__( 'Role definition error (taxonomy: %s).', 'scoper'), $taxonomy);
	echo '</div>';
	return;
}

if ( empty($admin_terms) ) {
	echo '<br />' . sprintf(__( 'Either you do not have permission to administer any %s, or none exist.', 'scoper'),  $tx->labels->name );
	echo '</div>';
	return;
}

?>
<form action="" method="post" name="role_scope" id="role_assign">
<?php
wp_nonce_field( $nonce_id );

echo '<br /><div id="rs-term-scroll-links">';
echo ScoperAdminBulkLib::taxonomy_scroll_links($tx, $all_terms, $admin_terms);
echo '</div><hr />';

// ============ Assignment Mode Selection Display ================
// TODO: is Link Category label handled without workaround now?
$tx_label = agp_strtolower( $tx->labels->name );
$tx_label_singular = agp_strtolower( $tx->labels->singular_name );

$parent_col = $tx_src->cols->parent;
if ( ! $parent_col || ( ! empty($tx->uses_standard_schema) && empty($tx->hierarchical) ) )
	$assignment_modes = array( 
		ASSIGN_FOR_ENTITY_RS => sprintf(__('for selected %s', 'scoper'), $tx_label)
	);
else
	$assignment_modes = array(
		ASSIGN_FOR_ENTITY_RS => sprintf(__('for selected %s', 'scoper'), $tx_label), 
		ASSIGN_FOR_CHILDREN_RS => sprintf(__('for sub-%s of selected', 'scoper'), $tx_label), 
		ASSIGN_FOR_BOTH_RS => sprintf(__('for selected and sub-%s', 'scoper'), $tx_label)
	);

$max_scopes = array( 'term' => __('Restrict selected roles', 'scoper'), 'blog' => __('Unrestrict selected roles', 'scoper')  );
$args = array( 'max_scopes' => $max_scopes, 'scope' => TERM_SCOPE_RS );
ScoperAdminBulk::display_inputs(ROLE_RESTRICTION_RS, $assignment_modes, $args);

ScoperAdminBulk::item_tree_jslinks(ROLE_RESTRICTION_RS);

// IE (6 at least) won't obey link color directive in a.classname CSS
$ie_link_style = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) ? ' style="color:white;"' : '';

$args = array( 'include_child_restrictions' => true, 'return_array' => true, 'role_type' => 'rs', 'force_refresh' => true );
$strict_terms = $scoper->get_restrictions(TERM_SCOPE_RS, $taxonomy, $args );
//strict_terms[taxonomy][role name][term_id] = array: terms which require Role Scoper assignment for specified role (user blog roles ignored, required caps may be supplied by scoper term role or object-specific assignment)
											// (for other terms, Role Scoper role assignment is optional (term role assignments will supplement blog caps)
										
$editable_roles = array();
foreach ( $all_terms as $term ) {
	$id = $term->$col_id;

	foreach ( $role_defs_by_otype as $object_type => $role_defs )
		foreach ( array_keys($role_defs) as $role_handle )
			if ( $role_assigner->user_has_role_in_term( $role_handle, $taxonomy, $id, '', array('src_name' => $tx->object_source, 'object_type' => $object_type) ) )
				$editable_roles[$id][$role_handle] = true;
}

$default_restrictions = $scoper->get_default_restrictions(TERM_SCOPE_RS);

$default_strict_roles = ( ! empty($default_restrictions[$taxonomy] ) ) ? array_flip(array_keys($default_restrictions[$taxonomy])) : array();

$table_captions = ScoperAdminUI::restriction_captions(TERM_SCOPE_RS, $tx, $tx_label_singular, $tx_label);

$args = array( 
'admin_items' => $admin_terms, 	'editable_roles' => $editable_roles,	'default_strict_roles' => $default_strict_roles,
'ul_class' => 'rs-termlist', 	'ie_link_style' => $ie_link_style,		'err' => $err,
'table_captions' => $table_captions
);

ScoperAdminBulk::item_tree( TERM_SCOPE_RS, ROLE_RESTRICTION_RS, $tx_src, $tx, $all_terms, '', $strict_terms, $role_defs_by_otype, $role_codes, $args);

echo '<a href="#scoper_top">' . __('top', 'scoper') . '</a>';
echo '<hr />';
echo '<h4 style="margin-bottom:0.1em"><a name="scoper_notes"></a>' . __("Notes", 'scoper') . ':</h4><ul class="rs-notes">';	
	
$osrc = $scoper->data_sources->get( $tx->object_source );
if ( empty($osrc->no_object_roles) ) {
	echo '<li>';
	printf( __( 'Any %1$s Restriction causes the specified role to be granted only via %1$s Role assignment, regardless of these %2$s settings.', 'scoper'), $osrc->labels->singular_name, $tx->labels->singular_name );
	echo '</li></ul>';
}
?>
</form>
</div>
<?php
} // end wrapper function scoper_admin_section_roles
?>