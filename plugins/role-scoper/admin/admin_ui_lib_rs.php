<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
/**
 * ScoperAdminUI PHP class for the WordPress plugin Role Scoper
 * scoper_admin_ui_lib.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 * Used by Role Scoper Plugin as a container for statically-called functions
 * These function can be used during activation, deactivation, or other 
 * scenarios where no Scoper or WP_Scoped_User object exists
 *
 */
class ScoperAdminUI {
	function set_agent_formatting( $date_limits, &$title, &$limit_class, &$link_class, &$limit_style, $title_wrap = true ) {
		
		static $current_gmt, $default_gmt_time, $gmt_seconds, $datef_no_time, $datef_time;
		static $starts_caption, $started_caption, $expired_caption, $expires_caption, $content_range_caption, $content_min_caption, $content_max_caption;
		
		if ( ! isset( $current_gmt ) ) {
			$current_gmt = agp_time_gmt();
			$gmt_offset = get_option( 'gmt_offset' );
			$gmt_seconds = $gmt_offset * 3600;
			
			$default_gmt_hour = - intval( $gmt_offset );
			if ( $default_gmt_hour < 0 )
				$default_gmt_hour = 24 + $default_gmt_hour;
			
			$default_gmt_time = "{$default_gmt_hour}:00";	// comparison string to determine whether date limit entry has a non-default time value
			if ( $gmt_offset < 10 )
				$default_gmt_time = '0' . $default_gmt_time;	
				
			$datef_no_time = __awp( 'M j, Y' );
			$datef_time = __awp( 'M j, Y G:i' );
			
			$starts_caption = __( 'TO START on %s', 'scoper' );
			$started_caption = __( 'started on %s', 'scoper' );
			$expired_caption = __( 'EXPIRED on %s', 'scoper' );
			$expires_caption = __( 'expire on %s', 'scoper' );
			
			$content_range_caption = __( '(for content %1$s to %2$s)', 'scoper' );
			$content_min_caption = __( '(for content after %1$s)', 'scoper' );
			$content_max_caption = __( '(for content before %1$s)', 'scoper' );
		}
		
		$title_captions = array();
		$content_title_caption = '';
		
		if ( ! empty($date_limits['date_limited']) ) {
			if ( $date_limits['start_date_gmt'] != SCOPER_MIN_DATE_STRING ) {
				$limit_class .= ' rs-has_start';
				
				$start_date_gmt = strtotime( $date_limits['start_date_gmt'] );
				$datef = ( strpos( $date_limits['start_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
				
				if ( $start_date_gmt > $current_gmt ) {
					//$limit_class .= ' rs-future';
					$limit_style = 'style="background-color: #cfc" ';
					$title_captions []= sprintf( $starts_caption, agp_date_i18n( $datef, $start_date_gmt + $gmt_seconds ) );
				} else
					$title_captions []= sprintf( $started_caption, agp_date_i18n( $datef, $start_date_gmt + $gmt_seconds ) );
			}
				
			if ( $date_limits['end_date_gmt'] != SCOPER_MAX_DATE_STRING ) {
				$limit_class .= ' rs-has_end';
				
				$end_date_gmt = strtotime( $date_limits['end_date_gmt'] );
				$datef = ( strpos( $date_limits['end_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
				
				if ( strtotime( $date_limits['end_date_gmt'] ) < $current_gmt ) {
					//$limit_class .= ' rs-expired';	
					$limit_style = 'style="background-color: #fcc" ';
					$title_captions []= sprintf( $expired_caption, agp_date_i18n( $datef, $end_date_gmt + $gmt_seconds ) );
				} else
					$title_captions []= sprintf( $expires_caption, agp_date_i18n( $datef, $end_date_gmt + $gmt_seconds ) );
			} 
		}
		
		if ( ! empty($date_limits['content_date_limited']) ) {
			if ( $date_limits['content_min_date_gmt'] != SCOPER_MIN_DATE_STRING ) {
				$limit_class .= ' rs-has_cmin';
				$link_class = 'rs-custom_link';
				
				$content_min_date_gmt = strtotime( $date_limits['content_min_date_gmt'] );
				$datef_min = ( strpos( $date_limits['content_min_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
			}

			if ( $date_limits['content_max_date_gmt'] != SCOPER_MAX_DATE_STRING ) {
				$limit_class .= ' rs-has_cmax';
				
				$content_max_date_gmt = strtotime( $date_limits['content_max_date_gmt'] );
				$datef_max = ( strpos( $date_limits['content_max_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
				
				if ( $date_limits['content_min_date_gmt'] != SCOPER_MIN_DATE_STRING ) {
					$content_title_caption = sprintf( $content_range_caption, agp_date_i18n( $datef_min, $content_min_date_gmt + $gmt_seconds ), agp_date_i18n( $datef_max, $content_max_date_gmt + $gmt_seconds ) );
				} else
					$content_title_caption = sprintf( $content_max_caption, agp_date_i18n( $datef_max, $content_max_date_gmt + $gmt_seconds ) );
			} else
				$content_title_caption = sprintf( $content_min_caption, agp_date_i18n( $datef_min, $content_min_date_gmt + $gmt_seconds ) );
		}
		
		if ( $title_captions || $content_title_caption )
			$title = implode(", ", $title_captions) . ' ' . $content_title_caption;
	}
	
	function restriction_captions( $scope, $tx = '', $item_label_singular = '', $item_label = '') {
		$table_captions = array();
	
		if ( TERM_SCOPE_RS == $scope ) {
			if ( ! $item_label ) 
				$item_label = ( 'link_category' == $tx->name ) ? agp_strtolower( __('Categories') ) : agp_strtolower( $tx->labels->name );
			if ( ! $item_label_singular ) 
				$item_label_singular = ( 'link_category' == $tx->name ) ? agp_strtolower( __('Category') ) : agp_strtolower( $tx->labels->singular_name );
		}

		$table_captions = array();
		$table_captions['restrictions'] = array(	// captions for roles which are NOT default strict
			ASSIGN_FOR_ENTITY_RS => sprintf(__('Restricted for %s', 'scoper'), $item_label_singular), 
			ASSIGN_FOR_CHILDREN_RS => sprintf(__('Unrestricted for %1$s, Restricted for sub-%2$s', 'scoper'), $item_label_singular, $item_label), 
			ASSIGN_FOR_BOTH_RS => sprintf(__('Restricted for selected and sub-%s', 'scoper'), $item_label),
			false => sprintf(__('Unrestricted by default', 'scoper'), $item_label_singular),
			'default' => sprintf(__('Unrestricted', 'scoper'), $item_label_singular)
		);
		$table_captions['unrestrictions'] = array( // captions for roles which are default strict
			ASSIGN_FOR_ENTITY_RS => sprintf(__('Unrestricted for %s', 'scoper'), $item_label_singular), 
			ASSIGN_FOR_CHILDREN_RS => sprintf(__('Unrestricted for sub-%s', 'scoper'), $item_label), 
			ASSIGN_FOR_BOTH_RS => sprintf(__('Unrestricted for selected and sub-%s', 'scoper'), $item_label),
			false => sprintf(__('Restricted by default', 'scoper'), $item_label_singular),
			'default' => sprintf(__('Restricted', 'scoper'), $item_label_singular)
		);
		
		return $table_captions;
	}
	
	function role_owners_key($tx_or_otype, $args = array()) {
		$defaults = array( 'display_links' => true, 'display_restriction_key' => true, 'restriction_caption' => '',
							'role_basis' => '', 'agent_caption' => '', 'single_item' => false );
		$args = array_merge( $defaults, (array) $args);
		extract($args);
	
		$item_label = agp_strtolower( $tx_or_otype->labels->name );
		$item_label_singular = agp_strtolower( $tx_or_otype->labels->singular_name );

		if ( $role_basis ) {
			if ( ! $agent_caption && $role_basis )
				$agent_caption = ( ROLE_BASIS_GROUPS == $role_basis ) ? __('Group', 'scoper') : __('User', 'scoper');
				$generic_name = ( ROLE_BASIS_GROUPS == $role_basis ) ? __('Groupname', 'scoper') : __('Username', 'scoper');
		} else
			$generic_name = __awp('Name');
			
		$agent_caption = agp_strtolower($agent_caption);
		
		if ( $single_item ) {
			echo '<div id="single_item_roles_key" style="display:none">';
			echo '<div style="margin-bottom:0.1em;margin-top:0"><strong><a name="scoper_key"></a>' . __("Users / Groups Key", 'scoper') . ':</strong></div><ul class="rs-agents_key">';	
		} else {
			echo '<h4 style="margin-bottom:0.1em"><a name="scoper_key"></a>' . __("Users / Groups Key", 'scoper') . ':</h4><ul class="rs-agents_key">';	
		}
		
		$link_open = ( $display_links ) ? "<a class='rs-link_plain' href='javascript:void(0)'>" : '';
		$link_close = ( $display_links ) ? '</a>' : '';
		
		echo '<li>';
		echo "{$link_open}$generic_name{$link_close}: ";
		printf (__('%1$s has role assigned for the specified %2$s.', 'scoper'), $agent_caption, $item_label_singular);
		echo '</li>';
		
		echo '<li>';
		echo "<span class='rs-bold'>{$link_open}$generic_name{$link_close}</span>: ";
		printf (__('%1$s has role assigned for the specified %2$s and, by default, for all its sub-%3$s. (Propagated roles can also be explicitly removed).', 'scoper'), $agent_caption, $item_label_singular, $item_label);
		echo '</li>';
		
		echo '<li>';
		echo "<span class='rs-bold rs-gray'>{$link_open}$generic_name{$link_close}</span>: ";
		printf (__('%1$s does NOT have role assigned for the specified %2$s, but has it by default for sub-%3$s.', 'scoper'), $agent_caption, $item_label_singular, $item_label);
		echo '</li>';
		
		echo '<li>';
		echo '<span class="rs-bold">{' . "{$link_open}$generic_name{$link_close}" . '}</span>: ';
		printf (__('%1$s has this role via propagation from parent %2$s, and by default for sub-%3$s.', 'scoper'), $agent_caption, $item_label_singular, $item_label);
		echo '</li>';
		
		if ( $display_restriction_key ) {
			echo '<li>';
			echo "<span class='rs-bold rs-backylw' style='border:1px solid #00a;padding-left:0.5em;padding-right:0.5em'>" . __('Role Name', 'scoper') . "</span>: ";
			echo "<span>" . sprintf(__('role is restricted for specified %s.', 'scoper'), $item_label_singular) . "</span>";
			echo '</li>';
		}
		
		echo '</ul>';
		
		if ( $single_item ) {
			echo '</div><br />';
		}
	}
	
	// Role Scoping for NGG calls ScoperAdminUI::dropdown_pages
	function dropdown_pages($object_id = '', $stored_parent_id = '') {
		require_once( SCOPER_ABSPATH . '/hardway/hardway-parent-legacy_rs.php');
		return ScoperHardwayParentLegacy::dropdown_pages( $object_id, $stored_parent_id );
	}
} // end class ScoperAdminUI
?>