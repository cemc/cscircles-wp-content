<?php

add_action( 'admin_head', 'scoper_admin_terms_js' );

// In "Add New Term" form, hide the "Main" option from Parent dropdown if the logged user doesn't have manage_terms cap site-wide
function scoper_admin_terms_js() {
	if ( ! empty($_REQUEST['action']) && ( 'edit' == $_REQUEST['action'] ) )
		return;
	
	if ( ! empty( $_REQUEST['taxonomy'] ) ) {  // using this with edit-link-categories
		if ( $tx_obj = get_taxonomy( $_REQUEST['taxonomy'] ) )
			$cap_name = $tx_obj->cap->manage_terms;
	}

	if ( empty($cap_name) )
		$cap_name = 'manage_categories';

	if ( cr_user_can( $cap_name, BLOG_SCOPE_RS ) )
		return;
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#parent option[value="-1"]').remove();
});
/* ]]> */
</script>
<?php
}

?>