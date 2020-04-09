/* 
This is a chunk of code to change how the TinyMCE editor deals with 
repeated spaces. By default, entering multiple spaces in the visual
editor actually inserts an alternating sequence of <space> and 
&nbsp; so that the spaces don't collapse. (If you switch to the HTML
editor they'll still be there as \u00a0 characters.) 

Entering multiple spaces into the HTML editor is not directly problematic
(even saving and loading preserves these spaces). But when you switch
from the HTML editor to the visual editor, repeated spaces are collapsed.

We use repeated spaces a lot on our site, like in shortcode arguments
that represent Python code.

This code changes the behaviour of the HTML editor -> visual editor 
transition, so that before anything else happens, we replace repeated
spaces by alternating spaces and \u00a0 characters. So, basically,
spaces are preserved.

(Of course, with or without this code, any functions that process
page contents needs to be aware that a space may be represented by
&nbsp; or \u00a0 in addition to a normal space.)
*/

function fix_ws(i) {
  // this can't be done until after editor.js is loaded
  if (typeof switchEditors === "undefined") {
    // try again soon, at most 20 times, maybe editor.js wasn't loaded?
    window.setTimeout("fix_ws("+(i+1)+");", 250); 
  }
  else if (i < 20) { // shadow the logic in editor.js
    switchEditors.original_go = switchEditors.go;
    switchEditors.go = function(id, mode) {
      var ed = tinyMCE.get(id); 
      // are we doing an HTML -> Visual transition?
      if ( (!ed || ed.isHidden()) 
           && ('|toggle|tmce|tinymce|'.indexOf('|'+mode+'|') >= 0)) {
        var txtarea_el = tinymce.DOM.get(id);
        // do the replacement
        txtarea_el.value = txtarea_el.value.replace(/  /g, " \u00a0");
      }
      this.original_go(id, mode);
    }
  }
}
fix_ws(0);

// similar, but for wp-fullscreen.js instead of editor.js
function fix_ws_fullscreen(i) {
  if (typeof fullscreen === "undefined") {
    window.setTimeout("fix_ws_fullscreen("+(i+1)+");", 250);
  }
  else if (i < 20) {
    fullscreen.original_switchmode = fullscreen.switchmode;
    fullscreen.switchmode = function(to) {
      if (to == 'tinymce' && fullscreen.settings.mode == 'html') {
        var el = jQuery('#wp_mce_fullscreen')[0];
        el.value = el.value.replace(/  /g, " \u00a0");
      }
      this.original_switchmode(to);
    }
  }
}
fix_ws_fullscreen(0);