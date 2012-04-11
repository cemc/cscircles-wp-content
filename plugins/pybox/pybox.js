function stylePybox(id, modeCharacter) {
    B = jQuery("#pybox"+id);
    B.removeClass("modeNeutral modeCorrect modeInternalError");
    if (modeCharacter == 'E')
	B.addClass("modeInternalError");
    else if (modeCharacter == 'Y')
	B.addClass("modeCorrect");
    else // typically 'y' for correct in facultative, or 'N' for wrong
	B.addClass("modeNeutral");
}

function testingSI(fac, tni) {
    if (fac) return "Hide input box";
    return "Go back to grading";
}
function gradingSI(fac, tni) { 
    if (fac) return "Enter input";
    if (tni == "Y") return "Enter test statements";
    return "Enter test input";
}

function pbInputSwitch(id, tni) {
    $ = jQuery;
    var fac = $('#pybox'+id).hasClass('facultative');
    $('#pyinput'+id).toggle();
    if ($('#inputInUse'+id).val()=='N') {
	setCommandLabel(id, 'submit', "Run test");
	setCommandLabel(id, 'switch', testingSI(fac, tni));
	$('#inputInUse'+id).val('Y');
    }
    else {
	setCommandLabel(id, 'submit', "Run program");
	setCommandLabel(id, 'switch', gradingSI(fac, tni));
	$('#inputInUse'+id).val('N');
    }
}

function fixWeirdBug(msg) {
//    while (10==msg.charCodeAt(0)) 
//	msg = msg.substring(1);
    return msg;
}

function pbSetText(id, txt) { //should not be called on scrambles.
    w = jQuery('#pybox'+id+" .pyboxCodewrap");
    if (w.hasClass('CM')) 
	cmed[id].setValue(txt);
    else 
	jQuery('#usercode'+id).val(txt);		
}

function pbGetText(id) {
    $ = jQuery;
    if ($('#pybox'+id).hasClass('scramble')) 
	return $('#pyscramble'+id)
	.sortable()
	.children()
	.map(function(){return $(this).text();})
	.get()
	.join('\n');
    w = jQuery('#pybox'+id+" .pyboxCodewrap");
    if (w.hasClass('CM')) 
	return cmed[id].getValue();
    else 
	return jQuery('#usercode'+id).val();		
}	

function getID(event) {
    elt = event.target;
    id = elt.id.replace(/[^0-9]/g, '');
    return id;
}

function pbFormSubmit(event) {
    $ = jQuery;
    var id = getID(event);
    if ($('#pybox'+id).hasClass('multiscramble')) {
	pbMSFormSubmit(id);
	event.preventDefault(); 
	return false;
    }
    var values = {};
    var timeoutMS = DEFAULTTIMEOUTMS;
    $.each($('#pbform'+id).serializeArray(), function(i, field) {
	if (field.name == 'timeout')
	    timeoutMS = field.value;
	else
	    values[field.name] = field.value;
    });
    values['usercode'+id]=pbGetText(id);

    $('#submit'+id).attr('disabled', true);
    
    jQuery('#pbresults'+id).html("<p>Running...</p>");
    jQuery.ajax({
	type: "POST",
	url: SUBMITURL,
	data: $.param(values), 
	timeout: timeoutMS,
	success: function(data) {
	    data=fixWeirdBug(data);
 	    jQuery('#pbresults'+id).html(data.substring(1));
	    stylePybox(id, data.charAt(0));
            //jQuery("#pybox"+id).css("background-color", returnColours[data.charAt(0)]);
	    if (data.charAt(0)=="Y")
		happyFace(id);
	    $('#submit'+id).attr('disabled', false);
        },
        error: function(xhr, textStatus, thrownError) {
	    $('#submit'+id).attr('disabled', false);
	    stylePybox(id, 'E');
	    if (textStatus == "timeout") {
		alert('timed out!' + timeoutMS);
	    }
	   
 	    jQuery('#pbresults'+id).html("Could not grade program because communication to the server was not possible. Ajax information: "+xhr.statusText+" "+xhr.status+" "+thrownError);
        }
    });
    event.preventDefault(); 
//    return false;
}

/*function pbSave(id) {
    $ = jQuery;
    var values = {};
    $.each($('#pbform'+id).serializeArray(), function(i, field) {values[field.name] = field.value;});
    values['usercode'+id]=pbGetText(id);
    values['justsave']='';

    jQuery.ajax({
	type: "POST",
	url: SUBMITURL,
	data: $.param(values), 
  	success: function(msg) {pbSaveCallback(id, msg);},
	error: function(jqXHR, text){alert( "Error: " + text );}
    });
}

function pbSaveCallback(id, msg) {
    msg=fixWeirdBug(msg);
    alert(msg.substring(1)); //kill the 'S'
}*/

function happyFace(id) {
    jQuery("#pybox" + id + " .pycheck").attr({
	'title':'You have completed this problem at least once.',
	'src':FILESURL+'checked.png'
    });
}

function setCompleted(name) {
    if (name != 'NULL') {
	jQuery.ajax({
	    type: "POST",
	    url: SETCOMPLETEDURL,
	    data: {"problem":name},
	    error: function() {alert("Warning: unable to talk to server. Could not set 'completed' status for this problem.")}
	});
    }
}

// three types of short answer question: short answer, multiple choice, scramble
// all are client-side exercises not requiring execution on the server
function pbNoncodeShowResults(id, correct) { 
    $ = jQuery;
    name = $('#pybox'+id).find('input[name="slug"]').val();
    stylePybox(id, correct?"Y":"N");
    $('#pybox'+id+' .pbresults').html((!correct)?"Incorrect, try again.":$('#pybox'+id+' .epilogue').html());
    if (correct) {
	happyFace(id);
	setCompleted(name);
    }
}

function pbShortCheck(id) {
    $ = jQuery;
    ans = document.getElementById("pyShortAnswer"+id).value;    
    // old version before IE8 hackery: type = jQuery('#pybox'+id).find('input[name="type"]').val();
    JQtype = jQuery('#pybox'+id).find('input[name="type"]');
    thetype = JQtype.get(0).value;
    JQcorrect = jQuery('#pybox'+id).find('input[name="correct"]');
    lecorrect = JQcorrect.get(0).value;
    if (thetype=="number")
    { ok = parseFloat(ans) == parseFloat(lecorrect); }
    else
    { ok = ans == lecorrect;}
    pbNoncodeShowResults(id, ok);
}

function pbMultiCheck(id) {
    ok = (document.getElementById("pyselect"+id).value == 'r');
    pbNoncodeShowResults(id, ok);
}

function pbMultiscrambleCheck(id) { //NB: does not yet work if there are multiple identical lines
    $ = jQuery;
    lines = $('#pybox'+id+' li.pyscramble');
    len = lines.size();
    name = $('#pybox'+id+' input[name="name"]').val();
    x = lines.map(function(){x = $(this).attr('id'); return x.substr(x.lastIndexOf('_')+1);}).get().join();
    y = '0';
    for (i=1; i<len; i++) y = y + ',' + i;
    pbNoncodeShowResults(id, x == y);
}

// end of client-side-evaluated exercise types.

function pbCodeMirror(id) {
    var $ = jQuery;
    var cmwrap = $('#pybox'+id+" .pyboxCodewrap");
    var ro = cmwrap.hasClass("RO");
    cmwrap.addClass('autoCMsize');
    cmwrap.addClass('CM');

    cmed[id] = CodeMirror.fromTextArea
    (document.getElementById("usercode"+id), //"cmta"
     {
	 mode: 
	 {name: "python", 
	  version: 3, 
	  singleLineStringErrors: false
	 }, 
	 lineNumbers: true, 
	 indentUnit: 3,
	 tabSize: 3,
	 tabMode: "shift", 
	 matchBrackets: true,
	 readOnly: ro   
     }
    );
    
    var cs = cmwrap.find('.CodeMirror-scroll');
    var cg = cmwrap.find('.CodeMirror-gutter');
    

    jQuery(function() {
	cmwrap.resizable({
	    handles:'s', 
	    minHeight: 50,
	    resize: function(){
		cmwrap.removeClass('autoCMsize');
		cs.height(cmwrap.height());
		cg.css('min-height', cmwrap.height()+'px');
	    }}
			);
    });
    
}
 
function pbUndoCodeMirror(id) {
    cmed[id].toTextArea();
    w = jQuery('#pybox'+id+" .pyboxCodewrap");
    w.removeClass('autoCMsize');
    w.removeClass('CM');
}

function pbToggleCodeMirror(id) {
    $ = jQuery;
    w = jQuery('#pybox'+id+" .pyboxCodewrap");
    if (w.hasClass('CM')) {
	$('#toggleCM'+id).val('Rich editor');
	pbUndoCodeMirror(id);
    }
    else {
	$('#toggleCM'+id).val('Simple editor');
	pbCodeMirror(id);
    }
}

function pbConsoleCopy(id) {
    var code = pbGetText(id);
    var ecode = encodeURIComponent(code);
    var xurl = CONSOLEURL+"?consolecode="+ecode;
    window.open(xurl);
}

function pbSelectChange(event) {
    id = getID(event);
    act = jQuery('#pbSelect'+id+' :selected').attr('pbonclick');
    eval(act);
    jQuery('#pbSelect'+id).val('More actions...').blur();
}

function stayHere(event) {
    if (!jQuery(event.target).hasClass('open-same-window')) 
	jQuery(event.target).attr('target', '_blank');
    return true;
}

function scrollToTop() {
    jQuery('html,body').animate({scrollTop:0}, 1000);
}
	
cmed = {}; //list of code mirror instances, indexed by pyid

hflex = {}; //history flexigrid instances
hflexhelp = {};

jQuery(".hintlink").live("click", function(e) {
    //	    console.log("hintlink clicked" + e.pageX + " " + e.pageY);
    n = jQuery(this).attr("id").substring(8);
    o = jQuery("#hintbox" + n);
    o.insertBefore('#page');
    o.css({"display":"block","top": e.pageY,"left": e.pageX});
    o.draggable({ cancel: ".hintboxlink" });
});

jQuery(".hintboxlink").live("click", function(e) {
    n = jQuery(this).attr("id").substring(11);
    jQuery("#hintbox"+n).css("display","none");
});   

jQuery(".pbform").live("submit", pbFormSubmit);
jQuery(".selectmore").live("change", pbSelectChange);
jQuery('.entry-content a').live('click', stayHere);
jQuery('.hintbox a').live('click', stayHere);
jQuery('.pyflexClose').live('click', function (e) {historyClick(jQuery(e.target).closest('.pybox').find('input[name="pyId"]').val(),"");});
jQuery('.flexigrid pre').live('dblclick', function (e) {
    jq = jQuery(e.target);
    id = jq.closest('.pybox').find('input[name="pyId"]').val();
    code = jq.html();
    var div = document.createElement('div');
    div.innerHTML = code;
    var decoded = div.firstChild.nodeValue;
    pbSetText(id, decoded);
});
jQuery('div.hintLink a').live('click', function (e) {
    jQuery(e.target).closest('.hintOuter').find('.hintContent').dialog({'dialogClass' : 'wp-dialog','width':800});
});

function vtabby(wptabsdiv) {
    $ = jQuery;
    f = function(index, elt) {
	e = $(elt);
	e.height(e.height()-45);
    }
    $.each($(wptabsdiv).find(".ui-tabs-nav"), f);
    $.each($(wptabsdiv).find(".ui-tabs-panel"), f);
}

function tabby(index, wptabsdiv) {
    $ = jQuery;
    if ($(wptabsdiv).hasClass('ui-tabs-vertical')) return vtabby(wptabsdiv);
    tabs = $(wptabsdiv).data('tabs');
    L = jQuery('<span class="wptabnav wtnleft">&lt;</span>')
	.click(function (event) {
	    curr = tabs.option('selected');
	    if (curr > 0) tabs.select(curr-1);
	});
    R = jQuery('<span class="wptabnav wtnright">&gt;</span>')
	.click(function (event) {
	    curr = tabs.option('selected');
	    tabs.select(curr+1);
	});
    $(wptabsdiv).prepend(L).prepend(R);
}

function flexfix(index, flexigrid) {
    $ = jQuery;
    G = $(flexigrid);
    i = 0;
    sum = 0;
    G.find("th").each(function(index, th) {
	w = $(G.find("div.bDiv tbody tr:first-child td")[i]).width();
	if (w > $(th).width()) {
	    //	console.log(w);
	    $(th).find('div').width(w-10);
	    sum += 2+w;
	}
	else {
	    G.find("div.bDiv tbody tr td:nth-child("+(i+1)+") div").width($(th).width()-10);
	    sum += 2+$(th).width();
	}
	$(G.find(".cDrag > div")[i]).css("left", sum);
	i++;
    });
}

function flexfixall() {
    jQuery('.flexigrid').each(flexfix);
}

function pyflex(options) {
    //options['id']       : div id which will be filled with the flexibox
    //options['url']      : url that performs the database call
    //options['dbparams'] : extra arguments to send in database call
    //options['flparams'] : extra arguments for flexigrid, overwriting defaults
    //console.log(options);
    jQuery.ajax
    ({type:"POST",
      url:options['url'],
      data:jQuery.param('dbparams' in options ? options['dbparams'] : []),
      success:function(data){pyflexSuccess(options, data);},
      failure:function(){jQuery("#"+options['id']).html('Error: could not connect to database.');}
     });
}
function pyflexSuccess(options, data) {
    jQuery('#'+options['id']+' .pyflexerror').remove();

    if (!(data instanceof Object) || !("rows" in data) || data["rows"].length==0) {
	hflexhelp[options['id']] = options;
	msg = (!(data instanceof Object) || !("rows" in data)) ? data : 'The database connected but found no data.'; 
	info = "<a onclick='pyflex(hflexhelp[\""+options['id']+"\"])'>Click to try again.</a>";
	jQuery('#'+options['id']).html('<span class="pyflexerror">' + msg + ' ' + info + '</span>');
	alert(msg);
	return;
    }

    firstRow = data['rows'][0]['cell'];
    //console.log(firstRow);
    model = new Array();
    for (colname in firstRow) {
	colModel = {display: colname, name: colname, sortable: true};
	if (colname == 'user code' && jQuery('#'+options['id']).parents('.pybox').length > 0) 
	    colModel['attrs'] = {'class': 'usercodecol', 'title': 'double-click to reload version'};
	model.push(colModel);
    }
    xp = new Array();
    if ('dbparams' in options)
	for (paramname in options['dbparams']) 
	    xp.push({name: paramname, value: options['dbparams'][paramname]});
    jQuery('#' + options['id']).prepend('<span class="pyflex"></span>');

    jQuery(function() {
	opts = {
	    url: options['url'], 
	    dataType: 'json',
	    colModel: model, 
	    usepager: true,
	    resizableVForce: true,
	    //	    height: 'auto',
	    useRp: true,
	    unselectable: true,
	    showToggleBtn: false,
	    rp: 4, 
	    rpOptions: [1, 2, 4, 8, 16, 32, 64], 
	    onSuccess: flexfixall,
	    onDragCol: flexfixall,
	    params: xp,
	    canRearrange: false
	};
	if ('flparams' in options) 
	    for (optname in options['flparams'])
		opts[optname] = options['flparams'][optname];
	//console.log(opts);
	hflex[options['id']] = jQuery('#' + options['id'] + ' span.pyflex').flexigrid(opts);
	jQuery('#' + options['id']).resizable({handles:'e'});
    });
}

function historyClick(id,thename) {
    $ = jQuery;
    $('#pbhistory'+id).toggle();
    createNow = !$('#pbhistory'+id).is(":hidden") && ($('#pbhistory' + id + ' .flexigrid').length == 0);
    if (createNow) {
	url = HISTORYURL;
	pyflex({'id':'pbhistory'+id, 'url':url, 'dbparams':{'p': thename}, 'flparams':{'showCloseBtn':true}});
    }
    if ($('#pbhistory'+id).is(":hidden")) 
	setCommandLabel(id, 'history', 'History');
    else {
	setCommandLabel(id, 'history', 'Hide history');
	if (!createNow) hflex['pbhistory'+id].flexReload();
    }
}

function setCommandLabel(id, name, label) {
    $ = jQuery;
    $('#pybox'+id+' input[name="'+name+'"]').attr('value', label);
    $('#pybox'+id+' option[name="'+name+'"]').html(label);
}

jQuery( // this call to jQuery makes it delay until the DOM is loaded
    function() {   
	jQuery('ul.pyscramble').sortable();
	jQuery('.resizy').resizable({handles:'s',minHeight:50});
	jQuery('.wp-tabs > div').each(tabby);

	if (window.location.hash) {
	    setTimeout("window.scrollBy(0, -60)", 10); // so direct links aren't hidden by adminbar
	} 

	flexfixall();
    }
);
