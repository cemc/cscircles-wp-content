
(function($){$.fn.DynamicListbox=function(allOptions){var defaults={text_id:'item_search_text',button_id:'item_submit',list_id:'item_results',target_status:'active',source:'',vars:null,debug:false,uri_key:'key',agent_id:''};allOptions=$.extend({},defaults,allOptions);return this.each(function(){var lastKey='';var startup=true;var op=allOptions;var load=function(key){if(key==null||key=='undefined')key='';sel=$('#'+op.list_id);sel.html('<option value="0">please wait...</option>');if('rs_user_search'==op.uri_key)
var data={'rs_user_search':key,'rs_agent_id':op.agent_id,'rs_target_status':op.target_status};else
var data={'rs_group_search':key,'rs_agent_id':op.agent_id,'rs_target_status':op.target_status};$('#'+op.list_id+' option').each(function(i,option){$(option).remove();});$.ajax({url:op.source,type:"GET",data:data,dataType:"html",success:loaded,error:loadError});}
var loaded=function(data,txtStatus){sel=$('#'+op.list_id);sel.html(data);}
var loadError=function(XMLHttpRequest,textStatus,errorThrown){if(!op.debug)return;sel=$('#'+op.list_id);sel.html('<option value="0"><b style="color:red">'+
XMLHttpRequest.status+':'+
(textStatus?textStatus:'')+
(errorThrown?errorThrown:'')+'</b></option>');}
var submit_search=function(){search_text=$('#'+op.text_id);load(search_text.val());}
var keydown=function(e){var intKey=e.keyCode;if(intKey==13){submit_search();return false;}}
var init=function(){$('#'+op.text_id).bind('keydown',null,keydown);$("#"+op.button_id).click(function(){submit_search();});}
init();});}})(jQuery);