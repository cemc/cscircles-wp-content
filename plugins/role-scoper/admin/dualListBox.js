
(function($){var onSort;$.configureBoxes=function(op){op.box1Filter='';op.box2Filter='';onSort=function(a,b){var aVal=a.text.toLowerCase();var bVal=b.text.toLowerCase();if(aVal<bVal){return-1;}
if(aVal>bVal){return 1;}
return 0;};$('#'+op.box2View).dblclick(function(){MoveSelected(op.box2View,op.box1View);Filter(op.box1View,op.box1Storage,op.box1Filter);});$('#'+op.to1).click(function(){MoveSelected(op.box2View,op.box1View);Filter(op.box1View,op.box1Storage,op.box1Filter);});$('#'+op.allTo1).click(function(){MoveAll(op.box2View,op.box1View);Filter(op.box1View,op.box1Storage,op.box1Filter);});$('#'+op.box1View).dblclick(function(){MoveSelected(op.box1View,op.box2View);Filter(op.box2View,op.box2Storage,op.box2Filter);});$('#'+op.to2).click(function(){MoveSelected(op.box1View,op.box2View);Filter(op.box2View,op.box2Storage,op.box2Filter);});$('#'+op.allTo2).click(function(){MoveAll(op.box1View,op.box2View);Filter(op.box2View,op.box2Storage,op.box2Filter);});Sortop(op.box1View);Sortop(op.box2View);$('#'+op.box1Storage+',#'+op.box2Storage).css('display','none');};function Filter(g_view,g_storage,g_filter){var filterLower;filterLower='';$('#'+g_view+' option').filter(function(i){var toMatch=$(this).text().toString().toLowerCase();return toMatch.indexOf(filterLower)==-1;}).appendTo('#'+g_storage);$('#'+g_storage+' option').filter(function(i){var toMatch=$(this).text().toString().toLowerCase();return toMatch.indexOf(filterLower)!=-1;}).appendTo('#'+g_view);try{$('#'+g_view+' option').removeAttr('selected');}
catch(ex){}
Sortop(g_view);}
function Sortop(g_view){var $toSortop=$('#'+g_view+' option');$toSortop.sort(onSort);$('#'+g_view).empty().append($toSortop);}
function MoveSelected(from_view,to_view){$('#'+from_view+' option:selected').appendTo('#'+to_view);try{$('#'+from_view+' option,#'+to_view+' option').removeAttr('selected');}
catch(ex){}}
function MoveAll(from_view,to_view){$('#'+from_view+' option').appendTo('#'+to_view);try{$('#'+from_view+' option,#'+to_view+' option').removeAttr('selected');}
catch(ex){}}
function ClearFilter(group){$('#'+group.filter).val('');$('#'+group.storage+' option').appendTo('#'+group.view);try{$('#'+group.view+' option').removeAttr('selected');}
catch(ex){}
Sortop(group);}})(jQuery);