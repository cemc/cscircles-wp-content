<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperAdminBulkLib {
	function role_assignment_list($roles, $agent_names, $checkbox_base_id = '', $role_basis = 'user') {
		$agent_grouping = array();
		$agent_list = array();
		$role_propagated = array();

		 if ( ! $checkbox_base_id )
			$link_end = '';
		
		$date_limits = array();

		// This would sort entire list (currently grouping by assign_for and alphabetizing each grouping)
		//$sorted_roles = array();
		//uasort($agent_names, 'strnatcasecmp');
		//foreach ( $agent_names as $agent_id => $agent_name )
		//	$sorted_roles[$agent_id] = $roles[$agent_id];
		foreach( $roles as $agent_id => $val ) { 
			if ( $limitation_type = $val['date_limited'] + ( 2 * $val['content_date_limited'] ) )
				$date_limits[ $agent_id ] = $val;
			
			if ( is_array($val) && ! empty($val['inherited_from']) )
				$role_propagated[$agent_id] = true;
		
			if ( is_array($val) && ( 'both' == $val['assign_for'] ) )
				$agent_grouping[$limitation_type][ASSIGN_FOR_BOTH_RS] [$agent_id]= $agent_names[$agent_id];
			
			elseif ( is_array($val) && ( 'children' == $val['assign_for'] ) )
				$agent_grouping[$limitation_type][ASSIGN_FOR_CHILDREN_RS] [$agent_id]= $agent_names[$agent_id];
				
			else
				$agent_grouping[$limitation_type][ASSIGN_FOR_ENTITY_RS] [$agent_id]= $agent_names[$agent_id];
		}
		
		
		// display for_entity assignments first, then for_both, then for_children
		$assign_for_order = array( 'entity', 'both', 'children');
		
		$use_agents_csv = scoper_get_option("{$role_basis}_role_assignment_csv");
		
		foreach ( array_keys($agent_grouping) as $limitation_type ) {
			
			foreach ( $assign_for_order as $assign_for ) {
				if ( ! isset($agent_grouping[$limitation_type][$assign_for]) )
					continue;
					
				// sort each assign_for grouping alphabetically
				uasort($agent_grouping[$limitation_type][$assign_for], 'strnatcasecmp');
				
				foreach ( $agent_grouping[$limitation_type][$assign_for] as $agent_id => $agent_name ) {
					// surround rolename with bars to indicated it was inherited
					$pfx = ( isset($role_propagated[$agent_id]) ) ? '{' : '';
					$sfx = '';
					
					if ( $checkbox_base_id ) {
						if ( $use_agents_csv )
							$js_call = "agp_append('{$role_basis}_csv', ', $agent_name');";
						else
							$js_call = "agp_check_it('{$checkbox_base_id}{$agent_id}');";
						
						$link_end = " href='javascript:void(0)' onclick=\"$js_call\">";
						$sfx = '</a>';
					}
						
					// surround rolename with braces to indicated it was inherited
					if ( $pfx )
						$sfx .= '}';
					
					$limit_class = '';
					$limit_style = '';
					$link_class = 'rs-link_plain';
					$title_text = '';
					
					if ( $limitation_type ) {
						ScoperAdminUI::set_agent_formatting( $date_limits[$agent_id], $title_text, $limit_class, $link_class, $limit_style );
						$title = "title='$title_text'";
					} else
						$title = "title='select'";

					switch ( $assign_for ) {
						case ASSIGN_FOR_BOTH_RS:
							//roles which are assigned for entity and children will be bolded in list
							$link = ( $link_end ) ? "<a {$title}{$limit_style}class='{$link_class}{$limit_class}'" . $link_end : '';
							$agent_list[$limitation_type][ASSIGN_FOR_BOTH_RS] [$agent_id]= $pfx . $link . $agent_name . $sfx;
					
						break;
						case ASSIGN_FOR_CHILDREN_RS:
							//roles with are assigned only to children will be grayed
							$link = ( $link_end ) ? "<a {$title}{$limit_style}class='{$link_class} rs-gray{$limit_class}'" . $link_end : '';
							$agent_list[$limitation_type][ASSIGN_FOR_CHILDREN_RS] [$agent_id]= $pfx . "<span class='rs-gray'>" . $link . $agent_names[$agent_id] . $sfx . '</span>';
							
						break;
						case ASSIGN_FOR_ENTITY_RS:
							$link = ( $link_end ) ? "<a {$title}{$limit_style}class='{$link_class}{$limit_class}'" . $link_end : '';
							$agent_list[$limitation_type][ASSIGN_FOR_ENTITY_RS] [$agent_id]= $pfx . $link . $agent_names[$agent_id] . $sfx;
					}
				} // end foreach agents

				$agent_list[$limitation_type][$assign_for] = implode(', ', $agent_list[$limitation_type][$assign_for]);
					
				if ( ASSIGN_FOR_ENTITY_RS != $assign_for )
					$agent_list[$limitation_type][$assign_for] = "<span class='rs-bold'>" .  $agent_list[$limitation_type][$assign_for] . '</span>';
			} // end foreach assign_for
			
			$agent_list[$limitation_type] = implode(', ', $agent_list[$limitation_type]);
		}
			
		if ( $agent_list )
			return implode(', ', $agent_list);
	}
	
	function taxonomy_scroll_links($tx, $terms, $admin_terms = '') {
		$max_terms = ( defined( 'SCOPER_MAX_TAXONOMY_SCROLL_LINKS' ) ) ? SCOPER_MAX_TAXONOMY_SCROLL_LINKS : 100;

		if ( empty($terms) || ( is_array($admin_terms) && empty($admin_terms) ) || ( count($terms) > $max_terms ) )
			return;
		
		echo '<strong>' . __('Scroll to current settings:','scoper') . '</strong><br />';	
			
		if ( $admin_terms && ! is_array($admin_terms) )
			$admin_terms = '';
	
		global $scoper;
		$tx_src = $scoper->data_sources->get( $tx->source );

		$col_id = $tx_src->cols->id;
		$col_name = $tx_src->cols->name;
		$col_parent = ( ! empty($tx_src->cols->parent) ) ? $tx_src->cols->parent : '';

		$font_ems = 1.2;
		$text = '';
		$term_num = 0;

		$parent_id = 0;
		$last_id = -1;
		$last_parent_id = -1;
		$parents = array();
		$depth = 0;
		
		foreach( $terms as $term ) {
			$term_id = $term->$col_id;
			
			if ( isset($term->$col_parent) )
				$parent_id = $term->$col_parent;

			if ( ! $admin_terms || ! empty($admin_terms[$term_id]) ) {
				if ( $parent_id != $last_parent_id ) {
					if ( ($parent_id == $last_id) && $last_id ) {
						$parents[] = $last_id;
						$depth++;
					} elseif ($depth) {
						do {
							array_pop($parents);
							$depth--;
						} while ( $parents && ( end($parents) != $parent_id ) && $depth);
					}
					
					$last_parent_id = $parent_id;
				}

				//echo "term {$term->$col_name}: depth $depth, current parents: ";
				//dump($parents);
				
				if ( $term_num )
					$text .= ( $parent_id ) ? ' - ' : ' . ';
					
				if ( ! $parent_id )
					$depth = 0;
				
				$color_level_b = ($depth < 4) ? 220 - (60 * $depth) : 0;
				$hexb = dechex($color_level_b);
				if ( strlen($hexb) < 2 )
					$hexb = "0" . $hexb;
				
				$color_level_g = ($depth < 4) ? 80 + (40 * $depth) : 215;
				$hexg = dechex($color_level_g);
				
				$font_ems = ($depth < 5) ? 1.2 - (0.12 * $depth) : 0.6; 
				$text .= "<span style='font-size: {$font_ems}em;'><a class='rs-link_plain' href='#item-$term_id'><span style='color: #00{$hexg}{$hexb};'>{$term->$col_name}</span></a></span>";
			}
			
			$last_id = $term_id;
			$term_num++;
		}
		
		$text .= '<br />';
		
		return $text;
	}

	function display_date_limit_inputs( $role_duration = true, $content_date_limits = true ) {
		echo '
		<div id="poststuff" class="metabox-holder">
		<div id="post-body">
		<div id="post-body-content" class="rs-date-limit-inputs">
		';
		
		if ( $role_duration || $content_date_limits && scoper_get_option( 'display_hints' ) )
			if ( scoper_get_option('display_hints') ) {
				echo '<div class="rs-optionhint" style="margin: 0 0 1em 2em">';
				if ( $role_duration ) {
					_e("Role Duration specifies the time period in which a role is active.", 'scoper');
					echo ' ';
				}
				
				if ( $content_date_limits )
					_e("Content Date Limits narrow the content which the role applies to.", 'scoper');
				
				echo '<br />';
				echo '</div>';
			}
		
		if ( $role_duration ) {
			echo '<div style="margin: 0 0 1em 2em">';
			
			if ( ! empty($_POST['set_role_duration']) ) {
				$checked = "checked='checked'";
				$hide_class = '';
			} else {
				$checked = '';
				$hide_class = " hide-if-js";
			} 
			
			$js_call = "agp_display_if('role_duration_inputs', 'set_role_duration')";
			echo "<label for='set_role_duration'><input type='checkbox' id='set_role_duration' name='set_role_duration' value='1' $checked onclick=\"$js_call\" /> <strong>";
			_e( 'Modify Role Duration', 'scoper' );
			echo '</strong></label><br />';
			
			echo "<ul class='rs-list_horiz rs-role_date_entry{$hide_class}' id='role_duration_inputs'>";
			
			// TODO: make these horizontal li
			
			echo '<li>';
			_e('Grant Role on:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'start_date_gmt_' );
			echo '</li>';
			
			echo '<li>';
			_e('Expire Role on:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'end_date_gmt_' );
			echo '</li>';
			
			echo '</ul>';
			
			echo '</div>';
// jQuery to hide the show/hide the date selection UI based on "Keep current setting" checkbox toggle			
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
<?php foreach( array( 'start_date_gmt', 'end_date_gmt', 'content_min_date_gmt', 'content_max_date_gmt' ) as $topic ) : ?>
$('#<?php echo $topic;?>_keep-timestamp').click(function() {
	if ( $('#<?php echo $topic;?>_keep-timestamp').attr( 'checked' ) ) {
		$('#<?php echo $topic;?>_timestamp_ui').hide();
	} else {
		$('#<?php echo $topic;?>_timestamp_ui').show();
	}
});
<?php endforeach; ?>
});
/* ]]> */
</script>
<?php
		}
		
		if ( $content_date_limits ) {
			echo '<div style="margin: 0 0 1em 2em">';
			
			if ( ! empty($_POST['set_content_date_limits']) ) {
				$checked = "checked='checked'";
				$hide_class = '';
			} else {
				$checked = '';
				$hide_class = " hide-if-js";
			} 
			
			$js_call = "agp_display_if('role_date_limit_inputs', 'set_content_date_limits')";
			echo "<label for='set_content_date_limits'><input type='checkbox' id='set_content_date_limits' name='set_content_date_limits' value='1' $checked onclick=\"$js_call\" /><strong>";
			_e( 'Modify Content Date Limits', 'scoper' );
			echo '</strong></label>';
			
			echo "<ul class='rs-list_horiz rs-role_date_entry{$hide_class}' id='role_date_limit_inputs'>";
			
			echo '<li>';
			_e('Min Content Date:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'content_min_date_gmt_' );
			echo '</li>';
			
			echo '<li>';
			_e('Max Content Date:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'content_max_date_gmt_' );
			echo '</li>';

			echo '</ul>';
			
			echo '</div>';
		}
		
		if ( $role_duration || $content_date_limits && scoper_get_option( 'display_hints' ) )
			if ( scoper_get_option('display_hints') ) {
				echo '<div class="rs-optionhint" style="margin: 2em 0 1em 2em">';
				_e('This controls what limits to apply to the User / Group roles you select for creation or modification. <strong>Currently stored limits</strong> are indicated by a dotted border around the User or Group name.  For details, hover over the name or view the User or Group Profile.', 'scoper' );
				echo '</div>';
			}
	
		echo '
		</div>
		</div>
		</div>
		';
	}
	
	function display_touch_time( $stamp, $date, $id_prefix = '', $class = 'curtime', $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0, $suppress_hidden_inputs = true, $suppress_current_inputs = true, $use_js = false, $empty_month_option = true ) {  // todo: move to $args array, default suppress to false
		if ( $use_js ) {
			echo '<span id="' . $id_prefix . 'timestamp">';
			printf($stamp, $date);
			echo '</span>';
			
			echo ' <a href="' . '#' . $id_prefix . 'edit_timestamp" id="' . $id_prefix . 'edit-timestamp" class="rs_role_edit-timestamp hide-if-no-js" tabindex="4">';
			echo __awp('Edit');
			echo '</a>';
			
			$class = 'hide_if_js ';
		} else
			$class = '';
			
		echo '<div id="' . $id_prefix . 'timestampdiv" class="' . $class . 'clear" style="clear:both;">';
	
		ScoperAdminBulkLib::touch_time( $edit, $for_post, $tab_index, $multi, $id_prefix, $suppress_hidden_inputs, $suppress_current_inputs, $use_js, $empty_month_option );
		
		echo '</div>';
	}
	
	// from WP 2.8.4 core, add id_prefix argument
	function touch_time( $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0, $id_prefix = '', $suppress_hidden_inputs = false, $suppress_current_inputs = false, $use_js = true, $empty_month_option = false ) {
		global $wp_locale, $post, $comment;
	
		if ( $for_post ) {
			if ( empty($post) ) {
				$edit = true;
				$current_post_date = 0;
			} else {
				$edit = ( in_array($post->post_status, array('draft', 'pending') ) && (!$post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt ) ) ? false : true;
				$current_post_date = $post->post_date;
			}
		} else {
			$edit = true;
			$current_comment_date = ( empty($comment) ) ? 0 : $comment->comment_date;
		}	
			
		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 )
			$tab_index_attribute = " tabindex='$tab_index'";
	
		// echo '<label for="timestamp" style="display: block;"><input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp"'.$tab_index_attribute.' /> '.__( 'Edit timestamp' ).'</label><br />';
	
		if ( ! empty($_POST) ) {
			$jj = ( ! empty( $_POST[$id_prefix . 'jj'] ) ) ? $_POST[$id_prefix . 'jj'] : '';
			$mm = ( ! empty( $_POST[$id_prefix . 'mm'] ) ) ? $_POST[$id_prefix . 'mm'] : '';
			$aa = ( ! empty( $_POST[$id_prefix . 'aa'] ) ) ? $_POST[$id_prefix . 'aa'] : '';
			$hh = ( ! empty( $_POST[$id_prefix . 'hh'] ) ) ? $_POST[$id_prefix . 'hh'] : '';
			$mn = ( ! empty( $_POST[$id_prefix . 'mn'] ) ) ? $_POST[$id_prefix . 'mn'] : '';
			$ss = ( ! empty( $_POST[$id_prefix . 'ss'] ) ) ? $_POST[$id_prefix . 'ss'] : '';
			
		} else { 
			$time_adj = time() + (get_option( 'gmt_offset' ) * 3600 );

			$post_date = ($for_post) ? $current_post_date : $current_comment_date;
			
			$jj = ($edit) ? mysql2date( 'd', $post_date, false ) : gmdate( 'd', $time_adj );
			$mm = ($edit) ? mysql2date( 'm', $post_date, false ) : gmdate( 'm', $time_adj );
			$aa = ($edit) ? mysql2date( 'Y', $post_date, false ) : gmdate( 'Y', $time_adj );
			$hh = ($edit) ? mysql2date( 'H', $post_date, false ) : gmdate( 'H', $time_adj );
			$mn = ($edit) ? mysql2date( 'i', $post_date, false ) : gmdate( 'i', $time_adj );
			$ss = ($edit) ? mysql2date( 's', $post_date, false ) : gmdate( 's', $time_adj );
		}
			
		if ( ! $suppress_current_inputs ) {
			$cur_jj = gmdate( 'd', $time_adj );
			$cur_mm = gmdate( 'm', $time_adj );
			$cur_aa = gmdate( 'Y', $time_adj );
			$cur_hh = gmdate( 'H', $time_adj );
			$cur_mn = gmdate( 'i', $time_adj );
		}
		
				
		$month = "<select " . ( $multi ? '' : "id='{$id_prefix}mm' " ) . "name='{$id_prefix}mm' $tab_index_attribute>\n";
		
		if ( $empty_month_option )
			$month .= "\t\t\t" . '<option value=""></option>';
		
		for ( $i = 1; $i < 13; $i = $i +1 ) {
			$month .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
			if ( $i == $mm )
				$month .= ' selected="selected"';
			$month .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
		}
		$month .= '</select>';
	
		$day = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'jj" ' ) . 'name="' . $id_prefix . 'jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$year = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'aa" ' ) . 'name="' . $id_prefix . 'aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" />';
		$hour = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'hh" ' ) . 'name="' . $id_prefix . 'hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$minute = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'mn" ' ) . 'name="' . $id_prefix . 'mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		/* translators: 1: month input, 2: day input, 3: year input, 4: hour input, 5: minute input */
		printf(__('%1$s%2$s, %3$s @ %4$s : %5$s'), $month, $day, $year, $hour, $minute);
	
		echo '<input type="hidden" id="' . $id_prefix . 'ss" name="' . $id_prefix . 'ss" value="' . $ss . '" />';
	
		if ( $multi ) return;
	
		echo "\n\n";
		foreach ( array('mm', 'jj', 'aa', 'hh', 'mn') as $timeunit ) {
			if ( ! $suppress_hidden_inputs )
				echo '<input type="hidden" id="' . $id_prefix . 'hidden_' . $timeunit . '" name="' . $id_prefix . 'hidden_' . $timeunit . '" value="' . $$timeunit . '" />' . "\n";
			
			if ( ! $suppress_current_inputs ) {
				$cur_timeunit = 'cur_' . $timeunit;
				echo '<input type="hidden" id="' . $id_prefix . ''. $cur_timeunit . '" name="' . $id_prefix . $cur_timeunit . '" value="' . $$cur_timeunit . '" />' . "\n";
			}
		}
		
	?>
		<p>
		<?php if ( $use_js ) :?>
		<a href="#<?php echo($id_prefix)?>edit_timestamp" id="<?php echo($id_prefix)?>save-timestamp" class="rs_role_save-timestamp hide-if-no-js button"><?php echo __awp('OK'); ?></a>
		<a href="#<?php echo($id_prefix)?>edit_timestamp" id="<?php echo($id_prefix)?>cancel-timestamp" class="rs_role_cancel-timestamp hide-if-no-js"><?php echo __awp('Cancel'); ?></a>
		<?php else:?>
		<a href="#<?php echo($id_prefix)?>edit_timestamp" id="<?php echo($id_prefix)?>clear-timestamp" class="rs_role_clear-timestamp"><?php echo __awp('Clear'); ?></a>
		<?php endif;?>
		<?php
		$checked = ( ! empty($_POST["{$id_prefix}keep-timestamp"]) ) ? "checked='checked'" : '';
		?>
		&nbsp;<input type="checkbox" id="<?php echo($id_prefix)?>keep-timestamp" name="<?php echo($id_prefix)?>keep-timestamp" <?php echo $checked;?> />&nbsp;<?php _e('keep stored setting', 'scoper'); ?>
		</p>
	<?php
	}

	
	function process_role_date_entries() {
		$return = array();
		$prefixes = array( 'start_date_gmt_', 'end_date_gmt_', 'content_min_date_gmt_', 'content_max_date_gmt_' );
						
		foreach ( $prefixes as $pfx ) {
			$key = str_replace( 'gmt_', 'gmt', $pfx );
			
			$aa = ( isset($_POST[$pfx . 'aa']) ) ? $_POST[$pfx . 'aa'] : 0;
			$mm = ( isset($_POST[$pfx . 'mm']) ) ? $_POST[$pfx . 'mm'] : 0;
			$jj = ( isset($_POST[$pfx . 'jj']) ) ? $_POST[$pfx . 'jj'] : 0;
			$hh = ( isset($_POST[$pfx . 'hh']) ) ? $_POST[$pfx . 'hh'] : 0;
			$mn = ( isset($_POST[$pfx . 'mn']) ) ? $_POST[$pfx . 'mn'] : 0;
			$ss = ( isset($_POST[$pfx . 'ss']) ) ? $_POST[$pfx . 'ss'] : 0;
			
			if ( ! empty($_POST[$pfx . 'keep-timestamp']) ) {
				$return[$key] = -1;
				continue;	
			
			} elseif ( ! $jj ) {
				if( in_array( $key, array( 'end_date_gmt', 'content_max_date_gmt' ) ) )
					$return[$key] = SCOPER_MAX_DATE_STRING;
				else
					$return[$key] = 0;	// if no day entered, treat as a non-entry
				
				continue;
				
			}

			// account for limitations in PHP strtotime() function - at least when running on a 32-bit server
			if ( $aa > 2035 )
				$aa = 2035;

			if ( ( $aa > 99 ) && ( $aa < 1902 ) )
				$aa = '1902';

			$aa = ($aa <= 0 ) ? date('Y') : $aa;
			$mm = ($mm <= 0 ) ? date('n') : $mm;
			$jj = ($jj > 31 ) ? 31 : $jj;
			$jj = ($jj <= 0 ) ? date('j') : $jj;
			$hh = ($hh > 23 ) ? $hh -24 : $hh;
			$mn = ($mn > 59 ) ? $mn -60 : $mn;
			$ss = ($ss > 59 ) ? $ss -60 : $ss;
			
			$return[$key] = get_gmt_from_date( sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss ) );
			
			if ( ! $return[$key] )
				$return[$key] = '0';
		}
		
		return (object) $return;
	}
	
	
	function date_limits_js() {
		$ajax_url = site_url( 'wp-admin/admin-ajax.php' );
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	function clearDateEdit_rs( pfx ) {
		$('#' + pfx + 'mm').val('');
		$('#' + pfx + 'jj').val('');
		$('#' + pfx + 'aa').val('');
		$('#' + pfx + 'hh').val('');
		$('#' + pfx + 'mn').val('');
	}
	$('.rs_role_clear-timestamp').click(function() {
		id = this.id;
		pos = id.indexOf( 'clear-timestamp' );
		pfx = id.substr( 0, pos );

		clearDateEdit_rs( pfx );

		return false;
	});

});
/* ]]> */
</script>
<?php
// these js functions would be needed to support slide-down date entry, updating of caption from entries 
/* 
	function updateDateLimit_rs( pfx ) {
		if ( $('#' + pfx + 'jj').val() !== '' ) {
			$('#' + pfx + 'timestamp').html(
				' <b>' +
				$( '#' + pfx + 'mm option[value=' + $('#' + pfx + 'mm').val() + ']' ).text() + ' ' +
				$('#' + pfx + 'jj').val() + ', ' +
				$('#' + pfx + 'aa').val() + ' @ ' +
				$('#' + pfx + 'hh').val() + ':' +
				$('#' + pfx + 'mn').val() + '</b> '
			);
		} else
			$('#' + pfx + 'timestamp').html('');
	}
	function editDateLimit_rs( pfx ) {	
		if ($('#' + pfx + 'timestampdiv').is(":hidden")) {
			$('#' + pfx + 'timestampdiv').slideDown("normal");
			$('.' + pfx + 'edit-timestamp').hide();
		}
	}
	function setDateLimit_rs( pfx ) {
		$('#' + pfx + 'timestampdiv').slideUp("normal");
		$('.' + pfx + 'edit-timestamp').show();
		
		updateDateLimit_rs( pfx );
	}
	$('.rs_role_edit-timestamp').click(function () {
		id = this.id;
		pos = id.indexOf( 'edit-timestamp' );
		pfx = id.substr( 0, pos );

		editDateLimit_rs( pfx );
		return false;
	});
	$('.rs_role_cancel-timestamp').click(function() {
		id = this.id;
		pos = id.indexOf( 'cancel-timestamp' );
		pfx = id.substr( 0, pos );
		
		$('#' + pfx + 'timestampdiv').slideUp("normal");
		clearDateEdit_rs( pfx );
		$('.' + pfx + 'edit-timestamp').show();
		updateDateLimit_rs( pfx );
		
		return false;
	});
	$('.rs_role_save-timestamp').click(function () {
		id = this.id;
		pos = id.indexOf( 'save-timestamp' );
		pfx = id.substr( 0, pos );
		
		setDateLimit_rs( pfx );
		return false;
	});
*/

	} // end function date_limits_js
		
} // end class
?>