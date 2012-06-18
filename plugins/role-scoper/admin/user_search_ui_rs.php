<?php

class ScoperUserSearch {
	var $status = array();
	var $list_ids = array();
	var $list_captions = array();
	var $removal_ids = array();
	var $removal_captions = array();
	var $remove_button = array();
	var $remove_all_button = array();
	var $restore_button = array();
	var $restore_all_button = array();
	
	function ScoperUserSearch( $agent_type = 'users' ) {	
		$remove = __( 'Remove', 'scoper' );
		$remove_btn = __( 'Remove&nbsp;>', 'scoper' );
		$remove_all = __( '>>', 'scoper' );
		$restore = __( '<&nbsp;Restore', 'scoper' );
		$restore_all = __( '<<', 'scoper' );
		$approve = __( 'Approve&nbsp;^', 'scoper' );
		$activate = __( 'Activate&nbsp;^', 'scoper' );
		$recommend = __( 'Recommend&nbsp;^', 'scoper' );
		
		if ( 'groups' == $agent_type ) {
			$can_admin = is_user_administrator_rs();
		
			if ( $can_admin ) {
				$this->status []= 'active';
				$this->list_ids [] = 'current_agents_rs';
				$this->removal_ids [] = 'uncurrent_agents_rs';
				$this->list_captions[] = __( 'Active Groups', 'scoper' );
				$this->removal_captions[] = __( 'Remove', 'scoper' );
				$this->remove_button []= $remove_btn;
				$this->remove_all_button []= $remove_all;
				$this->restore_button []= $restore;
				$this->restore_all_button []= $restore_all;
				$this->approval_button_id []= '';
				$this->approval_caption []= '';
			}

			if ( scoper_get_option( 'group_recommendations' ) && ( $can_admin || ( $can_moderate = current_user_can( 'recommend_group_membership' ) ) ) ) {
				$this->status []= 'recommended';
				$this->list_ids [] = 'recommended_agents_rs';
				$this->removal_ids [] = 'unrecommended_agents_rs';
				$this->list_captions[] = __( 'Recommended Groups', 'scoper' );
				$this->removal_captions[] = $remove;
				$this->remove_button []= $remove_btn;
				$this->remove_all_button []= $remove_all;
				$this->restore_button []= $restore;
				$this->restore_all_button []= $restore_all;
				
				if ( $can_admin ) {
					$this->approval_button_id []= 'recommended_to_active_rs';
					$this->approval_caption []= $activate;
				} else {
					$this->approval_button_id []= '';
					$this->approval_caption []= '';
				}	
			}

			if ( scoper_get_option( 'group_requests' ) && ( $can_admin || current_user_can( 'request_group_membership' ) ) ) {
				$this->status []= 'requested';
				$this->list_ids [] = 'requested_agents_rs';
				$this->removal_ids [] = 'unrequested_agents_rs';
				$this->list_captions[] = __( 'Requested Groups', 'scoper' );
				$this->removal_captions[] = $remove;
				$this->remove_button []= $remove_btn;
				$this->remove_all_button []= $remove_all;
				$this->restore_button []= $restore;
				$this->restore_all_button []= $restore_all;
				
				if ( $can_admin ) {
					$this->approval_button_id []= 'requested_to_active_rs';
					$this->approval_caption []= $activate;
				} elseif ( $can_moderate ) {
					$this->approval_button_id []= 'requested_to_recommended_rs';
					$this->approval_caption []= $recommend;
				} else {
					$this->approval_button_id []= '';
					$this->approval_caption []= '';
				}
			}
		} else {
			if ( ! empty( $_GET['page'] ) && ( 'rs-groups' == $_GET['page'] ) && ! empty( $_GET['id'] ) )
				$group_id = $_GET['id'];
			else
				$group_id = 0;
	
			$can_admin = is_user_administrator_rs() || current_user_can( 'manage_groups', $group_id );
			
			if ( $can_admin ) {
				$this->status []= 'active';
				$this->list_ids [] = 'current_agents_rs';
				$this->removal_ids [] = 'uncurrent_agents_rs';
				$this->list_captions[] = __( 'Active Users', 'scoper' );
				$this->removal_captions[] = $remove;
				$this->remove_button []= $remove_btn;
				$this->remove_all_button []= $remove_all;
				$this->restore_button []= $restore;
				$this->restore_all_button []= $restore_all;
				$this->approval_button_id []= '';
				$this->approval_caption []= '';
			}

			if ( scoper_get_option( 'group_recommendations' ) && ( $can_admin || ( $can_moderate = current_user_can( 'recommend_group_membership' ) ) ) ) {
				$this->status []= 'recommended';
				$this->list_ids [] = 'recommended_agents_rs';
				$this->removal_ids [] = 'unrecommended_agents_rs';
				$this->list_captions[] = __( 'Recommended Users', 'scoper' );
				$this->removal_captions[] = $remove;
				$this->remove_button []= $remove_btn;
				$this->remove_all_button []= $remove_all;
				$this->restore_button []= $restore;
				$this->restore_all_button []= $restore_all;
				
				if ( $can_admin ) {
					$this->approval_button_id []= 'recommended_to_active_rs';
					$this->approval_caption []= $activate;
				} else {
					$this->approval_button_id []= '';
					$this->approval_caption []= '';	
				}
			}

			if ( scoper_get_option( 'group_requests' ) && ( $can_admin || current_user_can( 'request_group_membership' ) ) ) {
				$this->status []= 'requested';
				$this->list_ids [] = 'requested_agents_rs';
				$this->removal_ids [] = 'unrequested_agents_rs';
				$this->list_captions[] = __( 'Requested Users', 'scoper' );
				$this->removal_captions[] = $remove;
				$this->remove_button []= $remove_btn;
				$this->remove_all_button []= $remove_all;
				$this->restore_button []= $restore;
				$this->restore_all_button []= $restore_all;
				
				if ( $can_admin ) {
					$this->approval_button_id []= 'requested_to_active_rs';
					$this->approval_caption []= $activate;
				} elseif ( $can_moderate ) {
					$this->approval_button_id []= 'requesed_to_recommended_rs';
					$this->approval_caption []= $recommend;
				} else {
					$this->approval_button_id []= '';
					$this->approval_caption []= '';	
				}
			}
		}
	}

	function output_js( $agent_type = 'users', $agent_id ) {
		echo "\n" . "<script type='text/javascript' src='" . SCOPER_URLPATH . "/admin/listbox.js'></script>";
		echo "\n" . "<script type='text/javascript' src='" . SCOPER_URLPATH . "/admin/dualListBox.js'></script>";
		
		$site_url = admin_url('');

		if ( ! $this->list_ids )
			return;

		$list_id = $this->list_ids[0];	// only the logged user's highest authority listbox will receive new selections from search results
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
	   var onSort = function(a, b) {
	        var aVal = a.text.toLowerCase();
	        var bVal = b.text.toLowerCase();
	        if (aVal < bVal) { return -1; }
	        if (aVal > bVal) { return 1; }
	        return 0;
	    };
		
	    function do_option_sort( list_id ) {
	    	var $toSortOptions = $( '#' + list_id + ' option');
			$toSortOptions.sort(onSort);
			$(list_id).empty().append($toSortOptions);
			
			build_selection_csv( list_id );
		}
			
		function maybe_add( list_id, opt ) {
			var selected_text = opt.text
	
		    var itemExists = false;
	        
	        $("#" + list_id + " option").each(function(j) {
	            if (this.text == selected_text) {
	                itemExists = true;                        
	            }
	        });
	        
	        build_selection_csv( list_id );
	        
	        return !itemExists;
		}
		
		function build_selection_csv( list_id ) {
			var s = '';
	
		    $("#" + list_id + " option").each(function(){
			    s = s + $(this).attr("value") + ',';
		    }) ;
		    
		    $("#" + list_id + "_csv").attr("value", s);
		}
		
		$("#add_item").click(function(e) {
			$("#item_results option:selected").each(function(i){
				e.preventDefault();
				if ( maybe_add( '<?php echo $list_id;?>', this ) ) {
					$(this).appendTo('#<?php echo $list_id;?>');
					do_option_sort( '<?php echo $list_id;?>' );
				}
			});
		});
		
		$("#item_results").dblclick(function() {
			$("#item_results option:selected").each(function(i){
				if ( maybe_add( '<?php echo $list_id;?>', this ) ) {
					$(this).appendTo('#<?php echo $list_id;?>');
					$('#<?php echo $list_id;?>').sort;
					do_option_sort( '<?php echo $list_id;?>' );
				}
			});
		});
		
		$("#requested_to_active_rs").click(function() {
			$("#requested_agents_rs option:selected").each(function(i){
				$(this).appendTo('#current_agents_rs');
				build_selection_csv( 'current_agents_rs' );
				do_option_sort( 'current_agents_rs' );
			});
		});
		
		$("#recommended_to_active_rs").click(function() {
			$("#recommended_agents_rs option:selected").each(function(i){
				$(this).appendTo('#current_agents_rs');
				build_selection_csv( 'current_agents_rs' );
				do_option_sort( 'current_agents_rs' );
			});
		});
		
		$("#requesed_to_recommended_rs").click(function() {
			$("#requested_agents_rs option:selected").each(function(i){
				$(this).appendTo('#recommended_agents_rs');
				build_selection_csv( 'recommended_agents_rs' );
				do_option_sort( 'recommended_agents_rs' );
			});
		});
		
		<?php
		foreach ( $this->list_ids as $key => $id ) :
		?>
			$("#<?php echo $id;?>").blur(function() {
				build_selection_csv( this.id );
			});
			
			$.configureBoxes({box1View:'<?php echo $id;?>',box2View:'<?php echo $this->removal_ids[$key];?>',to1:'to1_<?php echo $key?>',to2:'to2_<?php echo $key?>',allTo1:'allTo1_<?php echo $key?>',allTo2:'allTo2_<?php echo $key?>',box1Counter:'box1Counter_<?php echo $key?>',box2Counter:'box2Counter_<?php echo $key?>',box1Storage:'box1Storage_<?php echo $key?>',box2Storage:'box2Storage_<?php echo $key?>',useFilters:false,useCounters:false});
			
			build_selection_csv( '<?php echo $id;?>' );
	
			$("#to1_<?php echo $key?>").click(function() {build_selection_csv( '<?php echo $id;?>' );});
			$("#to2_<?php echo $key?>").click(function() {build_selection_csv( '<?php echo $id;?>' );});
			$("#allTo1_<?php echo $key?>").click(function() {build_selection_csv( '<?php echo $id;?>' );});
			$("#allTo2_<?php echo $key?>").click(function() {build_selection_csv( '<?php echo $id;?>' );});
			$("#<?php echo $this->list_ids[$key]?>").dblclick(function() {build_selection_csv( '<?php echo $id;?>' );});
			$("#<?php echo $this->removal_ids[$key]?>").dblclick(function() {build_selection_csv( '<?php echo $id;?>' );});
		<?php
		endforeach; // list_ids
		
		$uri_key = ( 'groups' == $agent_type ) ? 'rs_group_search' : 'rs_user_search';
		?>

		$('#item_results').DynamicListbox( { source:'<?php echo $site_url;?>', uri_key:'<?php echo $uri_key;?>', agent_id:'<?php echo $agent_id;?>', target_status:'<?php echo $this->status[0];?>' }  );
	});
	/* ]]> */
	</script>
	<?php
	}
	
	function output_html( $agents, $agent_type = 'users' ) {

		if ( 'groups' == $agent_type ) {
			$reqd_caps = 'manage_groups';
			
			if ( ! empty( $this->status[0] ) ) {
				if ( 'requested' == $this->status[0] )
					$reqd_caps = 'request_group_membership';
				elseif ( 'recommended' == $this->status[0] )
					$reqd_caps = 'recommend_group_membership';
			}
				
			$editable_group_ids = ScoperAdminLib::get_all_groups( FILTERED_RS, COL_ID_RS, array( 'reqd_caps' => $reqd_caps ) );
		}
		
if ( ! empty($this->list_ids) ) :
	?>
	<div id="agents_selection_rs">

	<?php
	if ( defined ( strtoupper($agent_type) . '_SEARCH_CAPTION_RS' ) )
		echo constant( strtoupper($agent_type) . '_SEARCH_CAPTION_RS' );
	elseif ( 'groups' == $agent_type )
		_e( 'Search for Groups', 'scoper' );
	else
		_e( 'Search for Users', 'scoper' );
	?>	
	<br />

	<table style="width: 100%"><tr>
	<td style="vertical-align: top; padding-right:1em;">
	
	<input id="item_search_text" type="text" size="12" />
	<button type="button" id="item_submit">Search</button>
	<br /><br />
	<select id="item_results" multiple="multiple" style="height:160px;width:200px;"></select>
	</td>
	
	<td>
	<span id="item_msg"></span>
	<button type="button" id="add_item" class="rs_add">Add&nbsp;></button>
	</td>
	
	<td>
	<?php
	echo $this->list_captions[0];
	?>
	<br />
	<select name="<?php echo $this->list_ids[0]?>[]" id="<?php echo $this->list_ids[0]?>" multiple="multiple" style="height:100px;width:200px;">
	<?php
	if ( ! empty($agents[ $this->status[0] ]) ) {
		foreach ( $agents[ $this->status[0] ] as $value => $caption )
			if ( ( 'users' == $agent_type ) || ( in_array( $value, $editable_group_ids ) ) )
				echo "<option value='$value'>$caption</option>";
	}
	?>
	</select>
	<br /> 
	<span id="box1Counter_0" class="countLabel"></span>
	<select id="box1Storage_0" style='display:none'> </select>
	</td>
	
	<td>
	<button id="to2_0" type="button" class="rs_remove"><?php echo ( esc_html($this->remove_button[0]) ) ?></button> <button id="allTo2_0" type="button" class="rs_remove"><?php echo ( esc_html($this->remove_all_button[0]) ) ?></button> <br /><br /> <button id="allTo1_0" type="button"><?php echo( esc_html($this->restore_all_button[0]) ) ?></button>  <button id="to1_0" type="button"><?php echo( esc_html($this->restore_button[0]) )?></button></td>
	<td>
	<?php
	echo $this->removal_captions[0];
	?>
	<br />
	<select id="<?php echo $this->removal_ids[0]?>" multiple="multiple" style="height:100px;width:100px;"></select>
	<br/>
	<span id="box2Counter_0" class="countLabel"></span>
	<select id="box2Storage_0" style='display:none'> </select>
	</td>
	</tr>
	
	<?php
	// now that the search box and highest accessable selection box is outputted, also display recommended / requested items
	if ( count( $this->list_ids ) > 1 ) :
	for( $key=1; $key < count($this->list_ids); $key++ ) :
	?>
		<tr>
		<td></td>	
		<td></td>
		
		<td>
		<?php
		echo $this->list_captions[$key];
		?>
		<br />
		<select name="<?php echo $this->list_ids[$key]?>[]" id="<?php echo $this->list_ids[$key]?>" multiple="multiple" style="height:100px;width:200px;">
		<?php
		if ( ! empty($agents[ $this->status[$key] ] ) ) {
			foreach ( $agents[ $this->status[$key] ] as $value => $caption )
				if ( ( 'users' == $agent_type ) || ( in_array( $value, $editable_group_ids ) ) )
					echo "<option value='$value'>$caption</option>";
		}
		?>
		</select>
		<br /> 
		<span id="box1Counter_<?php echo $key?>" class="countLabel"></span>
		<select id="box1Storage_<?php echo $key?>" style='display:none'> </select>
		<br />
		</td>
		
		<td>

		<?php
		if ( ! empty( $this->approval_button_id[$key] ) )
			echo "<button id='{$this->approval_button_id[$key]}' type='button' class='rs_approve'>{$this->approval_caption[$key]}</button><br /><br />";
		?>

		<button id="to2_<?php echo $key?>" type="button" class="rs_remove"><?php echo $this->remove_button[$key]?></button> <button id="allTo2_<?php echo $key?>" type="button" class="rs_remove"><?php echo $this->remove_all_button[$key]?></button> <br /> <button id="allTo1_<?php echo $key?>" type="button"><?php echo $this->restore_all_button[$key]?></button>  <button id="to1_<?php echo $key?>" type="button"><?php echo $this->restore_button[$key]?></button></td>
		<td>
		<?php
		echo $this->removal_captions[$key];
		?>
		<br />
		<select id="<?php echo $this->removal_ids[$key]?>" multiple="multiple" style="height:100px;width:100px;"></select>
		<br/>
		<span id="box2Counter_<?php echo $key?>" class="countLabel"></span>
		<select id="box2Storage_<?php echo $key?>" style='display:none'> </select>
		</td>
		
		</tr>
	<?php
	endfor;
	endif; // more than one listbox to display
	?>
	
	</table>
	</div>
	<?php
		foreach ( $this->list_ids as $key => $list_id ) {
			if ( $agents && ! empty( $agents[ $this->status[$key] ] ) )
				$csv = implode( ',', array_keys($agents[ $this->status[$key] ] ) );
			else
				$csv = '';
				
			echo "<input type='hidden' id='{$list_id}_csv' name='{$list_id}_csv' value='" . $csv . "' />";
		}

endif; // any list_ids set (logged user can submit groups for any status)
		
		$captions = array();
		$captions['active'] = __( 'Active Membership', 'scoper' );
		$captions['recommended'] = __( 'Recommended Membership', 'scoper' );
		$captions['requested'] = __( 'Requested Membership', 'scoper' );

		if ( 'groups' == $agent_type ) {
			// display uneditable groups
			$uneditable_groups = array();

			foreach ( array_keys($agents) as $status ) {
				$uneditable_groups[$status] = array();
				
				$status_editable = ( false !== array_search( $status, $this->status ) );

				foreach ( $agents[ $status ] as $group_id => $group_name )
					if ( ( ! $status_editable || ! in_array( $group_id, $editable_group_ids ) ) && ( false === strpos( $group_name, '[' ) ) )
						$uneditable_groups[$status] []= $group_name;
						
				if ( $uneditable_groups[$status] ) {
					$groups_csv = implode( ", ", $uneditable_groups[$status] );
					printf( __( '<b>%1$s</b>: %2$s', 'scoper' ), $captions[$status], $groups_csv );
					echo '<br /><br />';
				}
			}
		} else {
			foreach ( array_keys($agents) as $status ) {
				if ( false === array_search( $status, $this->status ) ) {
					$users_csv = implode( ", ", $agents[$status] );
					printf( __( '<b>%1$s</b>: %2$s', 'scoper' ), $captions[$status], $users_csv );
					echo '<br /><br />';
				}
			}	
		}
		
	} // end function
	
} // end class
	
?>