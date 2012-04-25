$ = jQuery;

function stylePybox(id, modeCharacter) {
    B = $("#pybox"+id);
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

function pbSetText(id, txt) { //should not be called on scrambles.
    w = $('#pybox'+id+" .pyboxCodewrap");
    if (w.hasClass('CM')) 
	cmed[id].setValue(txt);
    else 
	$('#usercode'+id).val(txt);		
}

function pbGetText(id) {
    if ($('#pybox'+id).hasClass('scramble')) 
	return $('#pyscramble'+id)
	.sortable()
	.children()
	.map(function(){return $(this).text();})
	.get()
	.join('\n');
    w = $('#pybox'+id+" .pyboxCodewrap");
    if (w.hasClass('CM')) 
	return cmed[id].getValue();
    else 
	return $('#usercode'+id).val();		
}	

function getID(event) {
    elt = event.target;
    id = elt.id.replace(/[^0-9]/g, '');
    return id;
}

function pbFormSubmit(event) {
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
    
    $('#pbresults'+id).html("<p>Running...</p>");
    $.ajax({
	type: "POST",
	url: SUBMITURL,
	data: $.param(values), 
	timeout: timeoutMS,
	success: function(data) {
 	    $('#pbresults'+id).html(data.substring(1));
	    stylePybox(id, data.charAt(0));
            //$("#pybox"+id).css("background-color", returnColours[data.charAt(0)]);
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
	   
 	    $('#pbresults'+id).html("Could not grade program because communication to the server was not possible. Ajax information: "+xhr.statusText+" "+xhr.status+" "+thrownError);
        }
    });
    event.preventDefault(); 
}

function happyFace(id) {
    $("#pybox" + id + " .pycheck").attr({
	'title':'You have completed this problem at least once.',
	'src':FILESURL+'checked.png'
    });
}

function setCompleted(name) {
    if (name != 'NULL') {
	$.ajax({
	    type: "POST",
	    url: SETCOMPLETEDURL,
	    data: {"problem":name},
	    error: function() {alert("Warning: unable to talk to server. Could not set 'completed' status for this problem.")}
	});
    }
}

function helpClick(id) {
    $("#pybox"+id+" .helpOuter").toggle();
}

function sendMessage(id, slug) {
    recipient = $("#pybox"+id+" .recipient").val();
    message = $("#pybox"+id+" .helpInner textarea").val();
    code = pbGetText(id);
    if (recipient==0) {
	alert('Please select a recipient for the message.');
    }
    else if (message.replace('\s', '')=='') {
	alert('Please enter a non-empty message.');
    }
    else if (code.replace('\s', '')=='') {
	alert('The code box is empty. It should instead contain your best partial solution so far.');
    }
    else {
	$.ajax({
	    type: "POST",
	    url : MESSAGEURL,
	    data: {"slug":slug,"recipient":recipient,"message":message,"code":code},
	    error: function() {alert("Unable to process 'send message' request. You might have lost your internet connection.");}
	});
	alert("Your message is being sent. You will also recieve a copy by e-mail.");
	helpClick(id);
    }
}

// three types of short answer question: short answer, multiple choice, scramble
// all are client-side exercises not requiring execution on the server
function pbNoncodeShowResults(id, correct) { 
    name = $('#pybox'+id).find('input[name="slug"]').val();
    stylePybox(id, correct?"Y":"N");
    $('#pybox'+id+' .pbresults').html((!correct)?"Incorrect, try again.":$('#pybox'+id+' .epilogue').html());
    if (correct) {
	happyFace(id);
	setCompleted(name);
    }
}

function pbShortCheck(id) {
    ans = document.getElementById("pyShortAnswer"+id).value;    
    JQtype = $('#pybox'+id).find('input[name="type"]');
    thetype = JQtype.get(0).value;
    JQcorrect = $('#pybox'+id).find('input[name="correct"]');
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
    

    $(function() {
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
    w = $('#pybox'+id+" .pyboxCodewrap");
    w.removeClass('autoCMsize');
    w.removeClass('CM');
}

function pbToggleCodeMirror(id) {
    w = $('#pybox'+id+" .pyboxCodewrap");
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

function pbVisualize(id, tni) {
    
    var form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("target", "_blank");
    form.setAttribute("action", VISUALIZEURL);

    usercode = pbGetText(id);

    params = {};

    if ($('#inputInUse'+id).val()=='Y') {
	extrainput = $('#pybox'+id+' textarea.pyboxInput').val();
	if (tni == 'Y') {
	    usercode += '\n# end of main program\n\n# start of tests\n' + extrainput
	}
	else {
	    params["userinput"] = extrainput;
	}
    }

    params["usercode"] =  usercode;

    for(var key in params) {
        if(params.hasOwnProperty(key)) {
	    var hiddenField = document.createElement("input");
	    hiddenField.setAttribute("type", "hidden");
	    hiddenField.setAttribute("name", key);
	    hiddenField.setAttribute("value", params[key]);
	    
	    form.appendChild(hiddenField);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

function pbSelectChange(event) {
    id = getID(event);
    act = $('#pbSelect'+id+' :selected').attr('pbonclick');
    eval(act);
    $('#pbSelect'+id).val('More actions...').blur();
}

function stayHere(event) {
    if (!$(event.target).hasClass('open-same-window')) 
	$(event.target).attr('target', '_blank');
    return true;
}

function scrollToTop() {
    $('html,body').animate({scrollTop:0}, 1000);
}
	
cmed = {}; //list of code mirror instances, indexed by pyid

hflex = {}; //history flexigrid instances
hflexhelp = {};

$(".hintlink").live("click", function(e) {
    n = $(this).attr("id").substring(8);
    o = $("#hintbox" + n);
    o.insertBefore('#page');
    o.css({"display":"block","top": e.pageY,"left": e.pageX});
    o.draggable({ cancel: ".hintboxlink" });
});

$(".hintboxlink").live("click", function(e) {
    n = $(this).attr("id").substring(11);
    $("#hintbox"+n).css("display","none");
});   

$(".pbform").live("submit", pbFormSubmit);
$(".selectmore").live("change", pbSelectChange);
$('.entry-content a').live('click', stayHere);
$('.hintbox a').live('click', stayHere);
$('.pyflexClose').live('click', function (e) {historyClick($(e.target).closest('.pybox').find('input[name="pyId"]').val(),"");});
$('.flexigrid pre').live('dblclick', function (e) {
    jq = $(e.target);
    id = jq.closest('.pybox').find('input[name="pyId"]').val();
    code = jq.html();
    var div = document.createElement('div');
    div.innerHTML = code;
    var decoded = div.firstChild.nodeValue;
    pbSetText(id, decoded);
});
$('div.hintLink a').live('click', function (e) {
    $(e.target).closest('.hintOuter').find('.hintContent').dialog({'dialogClass' : 'wp-dialog','width':800});
});

function vtabby(wptabsdiv) {
    f = function(index, elt) {
	e = $(elt);
	e.height(e.height()-45);
    }
    $.each($(wptabsdiv).find(".ui-tabs-nav"), f);
    $.each($(wptabsdiv).find(".ui-tabs-panel"), f);
}

function tabby(index, wptabsdiv) {
    if ($(wptabsdiv).hasClass('ui-tabs-vertical')) return vtabby(wptabsdiv);
    tabs = $(wptabsdiv).data('tabs');
    L = $('<span class="wptabnav wtnleft">&lt;</span>')
	.click(function (event) {
	    curr = tabs.option('selected');
	    if (curr > 0) tabs.select(curr-1);
	});
    R = $('<span class="wptabnav wtnright">&gt;</span>')
	.click(function (event) {
	    curr = tabs.option('selected');
	    tabs.select(curr+1);
	});
    $(wptabsdiv).prepend(L).prepend(R);
}

function flexfix(index, flexigrid) {
    G = $(flexigrid);
    i = 0;
    sum = 0;
    G.find("th").each(function(index, th) {
	w = $(G.find("div.bDiv tbody tr:first-child td")[i]).width();
	if (w > $(th).width()) {
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
    $('.flexigrid').each(flexfix);
}

function pyflex(options) {
    //options['id']       : div id which will be filled with the flexibox
    //options['url']      : url that performs the database call
    //options['dbparams'] : extra arguments to send in database call
    //options['flparams'] : extra arguments for flexigrid, overwriting defaults
    $.ajax
    ({type:"POST",
      url:options['url'],
      data:$.param('dbparams' in options ? options['dbparams'] : []),
      success:function(data){pyflexSuccess(options, data);},
      failure:function(){$("#"+options['id']).html('Error: could not connect to database.');}
     });
}
function pyflexSuccess(options, data) {
    $('#'+options['id']+' .pyflexerror').remove();

    if (!(data instanceof Object) || !("rows" in data) || data["rows"].length==0) {
	hflexhelp[options['id']] = options;
	msg = (!(data instanceof Object) || !("rows" in data)) ? data : 'The database connected but found no data.'; 
	info = "<a onclick='pyflex(hflexhelp[\""+options['id']+"\"])'>Click to try again.</a>";
	$('#'+options['id']).html('<span class="pyflexerror">' + msg + ' ' + info + '</span>');
	alert(msg);
	return;
    }

    firstRow = data['rows'][0]['cell'];
    model = new Array();
    for (colname in firstRow) {
	colModel = {display: colname, name: colname, sortable: true};
	if (colname == 'user code' && $('#'+options['id']).parents('.pybox').length > 0) 
	    colModel['attrs'] = {'class': 'usercodecol', 'title': 'double-click to reload version'};
	model.push(colModel);
    }
    xp = new Array();
    if ('dbparams' in options)
	for (paramname in options['dbparams']) 
	    xp.push({name: paramname, value: options['dbparams'][paramname]});
    $('#' + options['id']).prepend('<span class="pyflex"></span>');

    $(function() {
	opts = {
	    url: options['url'], 
	    dataType: 'json',
	    colModel: model, 
	    usepager: true,
	    resizableVForce: true,
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
	hflex[options['id']] = $('#' + options['id'] + ' span.pyflex').flexigrid(opts);
	$('#' + options['id']).resizable({handles:'e'});
    });
}

function historyClick(id,thename) {
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
    $('#pybox'+id+' input[name="'+name+'"]').attr('value', label);
    $('#pybox'+id+' option[name="'+name+'"]').html(label);
}

$( // this call to $ makes it delay until the DOM is loaded
    function() {   
	$('ul.pyscramble').sortable();
	$('.resizy').resizable({handles:'s',minHeight:50});
	$('.wp-tabs > div').each(tabby);

	if (window.location.hash) {
	    setTimeout("window.scrollBy(0, -60)", 10); // so direct links aren't hidden by adminbar
	} 

	flexfixall();
    }
);
