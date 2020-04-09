/* 	Paging Listbox v1.0
	Date: 2009-07-12
	Project: http://code.google.com/p/paging-listbox/
	Copyright 2009 [Mike @ moretechtips.net] 
	Licensed under the Apache License, Version 2.0 
	(the "License"); you may not use this file except in compliance with the License. 
	You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 
*/

(function($){ 
$.fn.DynamicListbox = function(allOptions) {  
	var defaults = {
	    text_id: 'item_search_text'
	    ,button_id: 'item_submit'
		,list_id: 'item_results'
		,target_status: 'active'
		,source:''	
		,vars:null
		,debug:false
		,uri_key: 'key'
		,agent_id: ''
	};
	allOptions = $.extend({}, defaults, allOptions);
		
	return this.each(function() {  
		var lastKey= '';
		var startup= true;
		
		//override passed options by element embedded options if any
		var op = allOptions;

		// AJAX call
		var load= function(key) {
			if(key==null || key=='undefined') key='';

			sel = $('#' + op.list_id); 
			sel.html('<option value="0">please wait...</option>');

			// TODO: syntax to use variable in object construction
			if ( 'rs_user_search' == op.uri_key )
				var data = { 'rs_user_search': key, 'rs_agent_id' : op.agent_id, 'rs_target_status' : op.target_status };
			else
				var data = { 'rs_group_search': key, 'rs_agent_id' : op.agent_id, 'rs_target_status' : op.target_status };
				
			$('#' + op.list_id + ' option').each(function(i, option){ $(option).remove(); });

			$.ajax({url:op.source,type:"GET",data:data,dataType:"html",success:loaded,error:loadError});
		}

		// AJAX is loaded
		var loaded = function(data,txtStatus) {
			//Set Inner html with response of Ajax request
			sel = $('#' + op.list_id); 
			sel.html(data); 
		}
		// Error on AJAX
		var loadError = function(XMLHttpRequest, textStatus, errorThrown) {
		    if(!op.debug) return;
		    
		    sel = $('#' + op.list_id); 
		    sel.html('<option value="0"><b style="color:red">'+
		             XMLHttpRequest.status+':'+
		             (textStatus?textStatus:'')+ 
		             (errorThrown?errorThrown:'')+'</b></option>');
        }
        var submit_search = function() {
			search_text = $('#' + op.text_id );
			load(search_text.val());
		}
		// on keydown handler
		var keydown = function(e) {
			// this will catch pressing enter and call find function
			var intKey = e.keyCode;
			if(intKey==13) { 
				submit_search();

				//Stop propagation to prevent container form submission
				return false;
			}
		}
		//init
		var init= function() {
			// bind onkeydown handler for 'Key' input 
			$('#' + op.text_id).bind('keydown',null,keydown);
			
			$("#" + op.button_id).click(function() {
				submit_search();
			});
			
		}
		init();
	});  
}
})(jQuery); 
