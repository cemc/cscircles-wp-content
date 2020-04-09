(function(){
 
    tinymce.create('tinymce.plugins.pybuttonsPlugin', {
 
        init : function(ed, url){
            ed.addCommand('pbDoPre', function(){
                S = tinyMCE.activeEditor.selection.getContent();
		if (S.substring(0, 5)=='<pre>' && S.substring(S.length-6)=='</pre>')
			tinyMCE.activeEditor.selection.setContent(S.substr(5, S.length-11))
		else	
                	tinyMCE.activeEditor.selection.setContent('<pre>' + S + '</pre>');
            });
            ed.addButton('pbbPre', {
                title: 'add/remove "pre" tag',
                image: url + '/icons/pre.png',
                cmd: 'pbDoPre'
            });
            ed.addCommand('pbDoCode', function(){
                S = tinyMCE.activeEditor.selection.getContent();
	        tinyMCE.activeEditor.selection.setContent('<code>' + S + '</code>');
            });
            ed.addButton('pbbCode', {
                title: 'add <code> tag',
                image: url + '/icons/code.png',
                cmd: 'pbDoCode'
            });
            ed.addCommand('pbDoAQuo', function(){
                S = tinyMCE.activeEditor.selection.getContent();
	        tinyMCE.activeEditor.selection.setContent('&laquo;' + S + '&raquo;');
            });
            ed.addButton('pbbAQuo', {
                title: 'add angle quotes',
                image: url + '/icons/aquo.png',
                cmd: 'pbDoAQuo'
            });
            ed.addCommand('pbDoBox', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyBox]' + S + '[/pyBox]');
            });
            ed.addButton('pbbBox', {
                title: 'add [pyBox] shortcode',
                image: url + '/icons/pybox.png',
                cmd: 'pbDoBox'
            });
            ed.addCommand('pbDoLink', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyLink code=""]' + S + '[/pyLink]');
            });
            ed.addButton('pbbLink', {
                title: 'add [pyLink] shortcode',
                image: url + '/icons/pylink.png',
                cmd: 'pbDoLink'
            });
            ed.addCommand('pbDoHint', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyHint hint=""]' + S + '[/pyHint]');
            });
            ed.addButton('pbbHint', {
                title: 'add [pyHint] shortcode',
                image: url + '/icons/pyhint.png',
                cmd: 'pbDoHint'
            });
            ed.addCommand('pbDoWarn', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyWarn]' + S + '[/pyWarn]');
            });
            ed.addButton('pbbWarn', {
                title: 'add [pyWarn] shortcode',
                image: url + '/icons/pywarn.png',
                cmd: 'pbDoWarn'
            });
            ed.addCommand('pbDoMulti', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyMulti right="" wrong="1\\n2\\n3" epilogue="Correct!"]' + S + '[/pyMulti]');
            });
            ed.addButton('pbbMulti', {
                title: 'add [pyMulti] shortcode',
                image: url + '/icons/pymulti.png',
                cmd: 'pbDoMulti'
            });
            ed.addCommand('pbDoMultiScramble', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyMultiScramble answer="1\\n2\\n3" {epilogue="Correct!"}]' + S + '[/pyMultiScramble]');
            });
            ed.addButton('pbbMultiScramble', {
                title: 'add [pyMultiScramble] shortcode',
                image: url + '/icons/pymultiscramble.png',
                cmd: 'pbDoMultiScramble'
            });
            ed.addCommand('pbDoShort', function(){
                S = tinyMCE.activeEditor.selection.getContent();
                tinyMCE.activeEditor.selection.setContent('[pyShort answer="" {type="number"}]' + S + '[/pyShort]');
            });
            ed.addButton('pbbShort', {
                title: 'add [pyShort] shortcode',
                image: url + '/icons/pyshort.png',
                cmd: 'pbDoShort'
            });
            ed.addCommand('pbDoPlain', function(){
                S = tinyMCE.activeEditor.selection.getContent({format:'text'});
                tinyMCE.activeEditor.selection.setContent(S);
            });
            ed.addButton('pbbPlain', {
                title: 'remove tags in selection',
                image: url + '/icons/plain.png',
                cmd: 'pbDoPlain'
            });
//        },
//        createControl : function(n, cm){
//            return null;
//        },
//        getInfo : function(){
//            return {
//                longname: 'PyBox Buttons',
//                author: 'CEMC',
//                authorurl: 'http://cemc.uwaterloo.ca/',
//                infourl: "http://cemc.uwaterloo.ca/",
//                version: "1.0"
//            };
        }
    });
    tinymce.PluginManager.add('pybuttons', tinymce.plugins.pybuttonsPlugin);
})();
