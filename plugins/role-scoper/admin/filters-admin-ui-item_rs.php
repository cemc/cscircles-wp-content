<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperAdminFiltersItemUI {
	var $scoper;
	var $scoper_admin;

	var $meta_box_ids = array();
	var $item_roles_ui;
	
	function __construct () {
		$this->scoper =& $GLOBALS['scoper'];
		$this->scoper_admin =& $GLOBALS['scoper_admin'];

		add_action('admin_menu', array(&$this, 'add_meta_boxes'));
		add_action('do_meta_boxes', array(&$this, 'act_tweak_metaboxes') );
		
		add_action('object_edit_ui_rs', array(&$this, 'ui_object_roles'), 10, 2);
		
		add_action('term_edit_ui_rs', array(&$this, 'ui_single_term_roles'), 10, 3);
		
		$object_type = cr_find_post_type();

		if ( $object_type && scoper_get_otype_option( 'default_private', 'post', $object_type ) )
			add_action('admin_footer', array(&$this, 'default_private_js') );			

		if ( $object_type && scoper_get_otype_option( 'sync_private', 'post', $object_type ) )
			add_action('admin_head', array(&$this, 'sync_private_js') );
			
		add_action( 'admin_head', array(&$this, 'deactive_term_checkboxes') );
		add_action( 'admin_print_footer_scripts', array(&$this, 'force_autosave_before_upload') );
	}
	
	function force_autosave_before_upload() {  // under some configuration, it is necessary to pre-assign categories. Autosave accomplishes this by triggering save_post action handlers.
		if ( ! is_content_administrator_rs() ) : ?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$( '#wp-content-media-buttons a').click( function() {
		if ( $('#post-status-info span.autosave-message').html() == '&nbsp;' ) {
			autosave();
		}
	});
});
/* ]]> */
</script>
		<?php endif;
	}

	function deactive_term_checkboxes() {
		if ( is_content_administrator_rs() )
			return;
		
		global $post;
		
		if ( empty($post) )
			return;	

		require_once(dirname(__FILE__).'/filters-admin-term-selection_rs.php');	
		
		$use_taxonomies = scoper_get_option( 'use_taxonomies' );
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
<?php foreach( get_taxonomies( array( 'public' => true, 'hierarchical' => true ) ) as $taxonomy ) :
	if ( empty( $use_taxonomies[$taxonomy] ) )
		continue;
	
	$user_terms = array();
	scoper_filter_terms_for_status( $taxonomy,  array(), $user_terms, array( 'object_id' => $post->ID, 'object_type' => $post->post_type, 'status' => $post->post_status ) );

	// jQuery selector will disable all term checkboxes which are not in our okay_terms array
	if ( $user_terms ) {
		$id_not_equal = "[id!='in-$taxonomy-" . implode( "'][id!='in-$taxonomy-", $user_terms ) . "']";
		$id_not_equal_popular = "[id!='in-popular-$taxonomy-" . implode( "'][id!='in-popular-$taxonomy-", $user_terms ) . "']";
	} else {
		$id_not_equal = '';
		$id_not_equal_popular = '';
	}
?>		
	$("#<?php echo $taxonomy;?>checklist input<?php echo $id_not_equal;?>").attr( 'disabled', 'disabled' );
	$("#<?php echo $taxonomy;?>checklist-pop input<?php echo $id_not_equal_popular;?>").attr( 'disabled', 'disabled' );
<?php endforeach;?>
});
/* ]]> */
</script>
<?php
}

	function default_private_js() {
		global $post;
		
		if ( 'post-new.php' != $GLOBALS['pagenow'] ) {
			$stati = get_post_stati( array( 'public' => true, 'private' => true ), 'names', 'or' );
			
			if ( in_array( $post->post_status, $stati ) )
				return;
		}
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#visibility-radio-private').click();
	
	$('#post-visibility-display').html(
		postL10n[$('#post-visibility-select input:radio:checked').val()]
	);
});
/* ]]> */
</script>
<?php
	}
	
	
	function sync_private_js() {
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$("#objscope_r1").click(function() {
		if ( this.checked ) {
			$('#visibility-radio-private').click();
			
			$('#post-visibility-display').html(
				postL10n[$('#post-visibility-select input:radio:checked').val()]
			);
		}
	});
});
/* ]]> */
</script>
<?php
	}
	
	
	function init_item_roles_ui() {
		if ( empty($this->item_roles_ui) ) {
			include_once( dirname(__FILE__).'/item_roles_ui_rs.php');
			$this->item_roles_ui = new ScoperItemRolesUI();
		}
	}
	
	function add_meta_boxes() {
		/*
		// optional hack to prevent role assignment boxes for non-Editors
		//
		//	This is now handled as a Role Scoper Option. 
		//	On the Advanced tab, Hidden Editing Elements section: select "Role administration requires a blog-wide Editor role"
		//
		// end optional hack
		*/
		
		// ========= register WP-rendered metaboxes ============
		$src_name = 'post';

		// TODO: different handling for edit-tags.php
		$object_type = cr_find_post_type();
		
		$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');

		if ( ( 'admin' == $require_blogwide_editor ) && ! is_user_administrator_rs() )
			return;

		if ( ( 'admin_content' == $require_blogwide_editor ) && ! is_content_administrator_rs() )
			return;

		if ( ! scoper_get_otype_option('use_object_roles', $src_name, $object_type) )
			return;
	
		if ( $require_blogwide_editor ) {
			if ( ! $this->scoper->user_can_edit_blogwide( $src_name, $object_type, array( 'require_others_cap' => true ) ) )
				return;
		}

		$role_defs = $this->scoper->role_defs->get_matching('rs', $src_name, $object_type);

		foreach ( $role_defs as $role_handle => $role_def ) {
			if ( ! isset($role_def->valid_scopes[OBJECT_SCOPE_RS]) )
				continue;

			$box_id = $role_handle;

			add_meta_box( $box_id, $this->scoper->role_defs->get_abbrev( $role_handle, OBJECT_UI_RS ), array(&$this, 'draw_object_roles_content'), $object_type );
			$this->meta_box_ids[$role_handle] = $box_id;
		}
	}
	
	function act_tweak_metaboxes() {
		static $been_here;

		if ( isset($been_here) )
			return;

		$been_here = true;
		
		global $wp_meta_boxes;
		
		if ( empty($wp_meta_boxes) )
			return;
		
		$object_type = cr_find_post_type();

		if ( empty($wp_meta_boxes[$object_type]) )
			return;

		$object_id = scoper_get_object_id();
		
		$is_administrator = is_user_administrator_rs();
		$can_admin_object = $is_administrator || $this->scoper_admin->user_can_admin_object('post', $object_type, $object_id);
		
		if ( $can_admin_object ) { 
			$this->init_item_roles_ui();
			$this->item_roles_ui->load_roles('post', $object_type, $object_id);
		}

		foreach ( $wp_meta_boxes[$object_type] as $context => $priorities ) {
			foreach ( $priorities as $priority => $boxes ) {
				foreach ( array_keys($boxes) as $box_id ) {
					if ( $role_handle = array_search( $box_id, $this->meta_box_ids ) ) {
						// eliminate metabox shells for roles which will be suppressed for this user
						if ( ! $is_administrator && ( ! $can_admin_object || ! $this->scoper_admin->user_can_admin_role($role_handle, $object_id, 'post', $object_type) ) ) {
							unset( $wp_meta_boxes[$object_type][$context][$priority][$box_id] );
						}
						
						// update metabox titles with role counts, restriction indicator
						elseif ( $can_admin_object )
							if ( $title_suffix = $this->item_roles_ui->get_rolecount_caption($role_handle) )
								if ( ! strpos($wp_meta_boxes[$object_type][$context][$priority][$box_id]['title'], $title_suffix) )
									$wp_meta_boxes[$object_type][$context][$priority][$box_id]['title'] .= $title_suffix;
					}
				}
			}
		}
				
	}
	
	// wrapper function so we don't have to load item_roles_ui class just to register the metabox
	function draw_object_roles_content( $object, $box ) {
		if ( empty($box['id']) )
			return;

		// id format: src_name:object_type:role_handle (As of WP 2.7, this is only safe way to transfer these parameters)
		//$role_attribs = explode( ':', $box['id'] );
		
		//if ( count($role_attribs) != 3 )
		//	return;

		$object_id = ( isset($object->ID) ) ? $object->ID : 0;
		
		$object_type = cr_find_post_type();
		
		$this->init_item_roles_ui();
		$this->item_roles_ui->draw_object_roles_content('post', $object_type, $box['id'], $object_id, false, $object);
	}
	
	function ui_single_term_roles($taxonomy, $args, $term) {
		$this->init_item_roles_ui();
		$this->item_roles_ui->single_term_roles_ui($taxonomy, $args, $term);
	}
	
	// This is now called only by non-post data sources which define admin_actions->object_edit_ui
	function ui_object_roles($src_name, $args = array()) {
		$defaults = array( 'object_type' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
	
		if ( ! $src = $this->scoper->data_sources->get($src_name) )
			return;
		
		if ( ! $object_type )
			if ( ! $object_type = cr_find_object_type( $src_name ) )
				return;
				
		$object_id = scoper_get_object_id( $src_name, $object_type );
		
		if ( ! $this->scoper_admin->user_can_admin_object($src_name, $object_type, $object_id) )
			return;
		
		$this->init_item_roles_ui();
		$this->item_roles_ui->single_object_roles_ui($src_name, $object_type, $object_id);
	} // end function ui_object_roles


} // end class

?>