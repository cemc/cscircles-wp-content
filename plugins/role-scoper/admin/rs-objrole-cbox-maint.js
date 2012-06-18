addLoadEvent(function(){scoper_rig_role_checkboxes();});function scoper_rig_role_checkboxes(){if(!role_for_object_title||!role_for_children_title)
return;var elems=document.getElementsByTagName('input');for(var i=0;i<elems.length;i++){if(elems[i].type!='checkbox')
continue;if(elems[i].id.match("^r[0-9]+[g,u][0-9]+")){elems[i].title=role_for_object_title;continue;}
if(elems[i].id.match("^p_r[0-9]+[g,u][0-9]+"))
elems[i].title=role_for_children_title;}}