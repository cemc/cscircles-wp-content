<?php

function _rs_can_edit_menu_item( $menu_item_id ) {
	$object_type = get_post_meta( $menu_item_id, '_menu_item_object', true );
	$object_id = get_post_meta( $menu_item_id, '_menu_item_object_id', true );

	if ( post_type_exists($object_type) ) {		
		return current_user_can( 'edit_post', $object_id );
	} elseif ( taxonomy_exists($object_type) ) {
		if ( $tx_obj = get_taxonomy( $object_type ) )
			return current_user_can( $tx_obj->cap->manage_terms, $object_id );
	}

	// for menu item types we don't filter
	return true;
}

function _rs_mnt_modify_nav_menu_item( $menu_item_id, $menu_operation ) {
	if ( $menu_item = get_post( $menu_item_id ) ) {
		if ( 'nav_menu_item' == $menu_item->post_type ) {
			//$item_type = get_post_meta( $menu_item_id, '_menu_item_type', true );
			$object_type = get_post_meta( $menu_item_id, '_menu_item_object', true );
			$object_id = get_post_meta( $menu_item_id, '_menu_item_object_id', true );

			if ( ! $is_post_type = post_type_exists($object_type) )
				$is_taxonomy = taxonomy_exists($object_type);
	
			// WP performs update on every item even if no values have changed
			if ( 'edit' == $menu_operation ) {
				$posted_vals = array();
				foreach ( array( 'title' => 'menu-item-title', 'attribute' => 'menu-item-attr-title', 'description' => 'menu-item-description', 'target' => 'menu-item-target', 
								 'classes' => 'menu-item-classes', 'xfn' => 'menu-item-xfn', 'menu_order' => 'menu-item-position', 'menu_parent' => 'menu-item-parent-id' ) as $property => $col ) {
					if ( isset( $_POST[$col][$menu_item_id] ) )
						$posted_vals[$property] = $_POST[$col][$menu_item_id];
				}
				
				if ( isset( $posted_vals['classes'] ) )
					$posted_vals['classes'] = array_map( 'sanitize_html_class', explode( ' ', $posted_vals['classes'] ) );

				$stored_vals = array();
				foreach( array( 'title' => 'post_title', 'attribute' => 'post_excerpt', 'description' => 'post_content', 'menu_order' => 'menu_order' ) as $property => $col ) {
					$stored_vals[$property] = $menu_item->$col;
				}
				
				$stored_vals['menu_parent'] = get_post_meta( $menu_item_id, '_menu_item_menu_item_parent', true );
				$stored_vals['target'] = get_post_meta( $menu_item_id, '_menu_item_target', true );
				$stored_vals['classes'] = (array) get_post_meta( $menu_item_id, '_menu_item_classes', true );
				$stored_vals['xfn'] = get_post_meta( $menu_item_id, '_menu_item_xfn', true );
				
				if ( empty($stored_val['title']) )
					$stored_vals['title'] = ( $is_post_type ) ? get_post_field( 'post_title', $object_id ) : get_term_field( 'name', $object_id, $object_type );
					
				$changed = false;
				foreach( array_keys($posted_vals) as $property ) {
					if ( $posted_vals[$property] != $stored_vals[$property] ) {
						$changed = true;
						break;
					}
				}
				
				if ( ! $changed )
					return;
			}
			
			if ( $is_post_type ) {	
				$deny_menu_operation = ! current_user_can( 'edit_post', $object_id );
			} elseif ( $is_taxonomy ) {
				if ( $tx_obj = get_taxonomy( $object_type ) ) {
					$deny_menu_operation = ! current_user_can( $tx_obj->cap->manage_terms, $object_id );
				}
			}

			if ( ! empty($deny_menu_operation) ) {
				if ( empty( $stored_vals['title'] ) )
					$stored_vals['title'] = $menu_item->post_title;
				
				if ( ! $stored_val['title'] )
					$stored_vals['title'] = ( $is_post_type ) ? get_post_field( 'post_title', $object_id ) : get_term_field( 'name', $object_id, $object_type );

				$link = admin_url( 'nav-menus.php' );

				switch( $menu_operation ) {
				case 'move':
					wp_die( sprintf( __( 'You do not have permission to move the menu item "%1$s". <br /><br /><a href="%2$s">Return to Menu Editor</a>', 'scoper' ), $stored_vals['title'], $link ) );
				break;
				case 'delete':
					wp_die( sprintf( __( 'You do not have permission to delete the menu item "%1$s". <br /><br /><a href="%2$s">Return to Menu Editor</a>', 'scoper' ), $stored_vals['title'], $link ) );
				break;
				default:
					wp_die( sprintf( __( 'You do not have permission to edit the menu item "%1$s". <br /><br /><a href="%2$s">Return to Menu Editor</a>', 'scoper' ), $stored_vals['title'], $link ) );
				} // end switch
			}
		}
	}
}

// transplanted from nav-menus.php
function _rs_determine_selected_menu() {
	$nav_menus = wp_get_nav_menus( array('orderby' => 'name') );

	// Get recently edited nav menu
	$recently_edited = (int) get_user_option( 'nav_menu_recently_edited' );

	$nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;
	
	// If there was no recently edited menu, and $nav_menu_selected_id is a nav menu, update recently edited menu.
	if ( !$recently_edited && is_nav_menu( $nav_menu_selected_id ) ) {
		$recently_edited = $nav_menu_selected_id;

	// Else if $nav_menu_selected_id is not a menu and not requesting that we create a new menu, but $recently_edited is a menu, grab that one.
	} elseif ( 0 == $nav_menu_selected_id && ! isset( $_REQUEST['menu'] ) && is_nav_menu( $recently_edited ) ) {
		$nav_menu_selected_id = $recently_edited;

	// Else try to grab the first menu from the menus list
	} elseif ( 0 == $nav_menu_selected_id && ! isset( $_REQUEST['menu'] ) && ! empty($nav_menus) ) {
		$nav_menu_selected_id = $nav_menus[0]->term_id;
	}

	return $nav_menu_selected_id;
}

function _rs_disable_uneditable_items_ui() {
	if ( ! $menu_id = _rs_determine_selected_menu() )
		return;

	$menu_items = wp_get_nav_menu_items( $menu_id, array('post_status' => 'any') );

	$uneditable_items = array();
	foreach( array_keys($menu_items) as $key ) {
		if ( ! _rs_can_edit_menu_item( $menu_items[$key]->ID ) )
			$uneditable_items[]= $menu_items[$key]->ID;
	}
	
	if ( ! $uneditable_items )
		return;
		
	?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
<?php foreach( $uneditable_items as $id ) :?>
$('#delete-<?php echo($id);?>').hide();
$('#cancel-<?php echo($id);?>').hide();
//$('#edit-menu-item-title-<?php echo($id);?>').attr('disabled','disabled');
//$('#edit-menu-item-attr-title-<?php echo($id);?>').attr('disabled','disabled');
<?php endforeach; ?>
});
/* ]]> */
</script>
<?php
}
?>