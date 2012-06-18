/*
*       Developed by Justin Mead
*       ©2009 MeadMiracle
*		www.meadmiracle.com / meadmiracle@gmail.com
*       Version 1.0
*       Testing: IE7/Windows XP
*                Firefox/Windows XP
*       Licensed under the Creative Commons GPL http://creativecommons.org/licenses/GPL/2.0/
*
*       op LISTING:
*           *box1View, box2View         - the id attributes of the VISIBLE listboxes
*           *box1Storage, box2Storage   - the id attributes of the HIDDEN listboxes (used for filtering)
*           *box1Filter, box2Filter     - the id attributes of the textboxes used to filter the lists
*           *box1Clear, box2Clear       - the id attributes of the elements used to clear/reset the filters
*           *box1Counter, box2Counter   - the id attributes of the elements used to display counts of visible/total items (used when filtering)
*           *to1, to2                   - the id attributes of the elements used to transfer only selected items between boxes
*           *allTo1, allTo2             - the id attributes of the elements used to transfer ALL (visible) items between boxes
*           *transferMode               - the type of transfer to perform on items (see heading TRANSFER MODES)
*           *sortBy                     - the attribute to use when sorting items (values: 'text' or 'value')
*           *useFilters                 - allow the filtering of items in either box (true/false)
*           *useCounters                - use the visible/total counters (true/false)
*           *useSorting                 - sort items after executing a transfer (true/false)
*
*       All op have default values, and as such, are optional.  Check the 'settings' JSON object below to see the defaults.
*
*       TRANSFER MODES:
*           * 'move' - In this mode, items will be removed from the box in which they currently reside and moved to the other box. This is the default.
*           * 'copy' - In this mode, items in box 1 will ALWAYS remain in box 1 (unless they are hidden by filtering).  When they are selected for transfer
*                      they will be copied to box 2 and will be given the class 'copiedOption' in box 1 (my default styling for this class is yellow background
*                      but you may choose whatever styling suits you).  If they are then transferred from box 2, they disappear from box 2, and the 'copiedOption'
*                      class is removed from the corresponding option in box 1.
*
*/

(function($) {
    var onSort;
    
    //the main method that the end user will execute to setup the DLB
    $.configureBoxes = function(op) {
        //define default settings
        /*
        settings = {
            box1View: 'box1View',
            box1Storage: 'box1Storage',
            box1Filter: 'box1Filter',
            box1Clear: 'box1Clear',
            box1Counter: 'box1Counter',
            box2View: 'box2View',
            box2Storage: 'box2Storage',
            box2Filter: 'box2Filter',
            box2Clear: 'box2Clear',
            box2Counter: 'box2Counter',
            to1: 'to1',
            allTo1: 'allTo1',
            to2: 'to2',
            allTo2: 'allTo2',
            sortBy: 'text',
            useFilters: true,
            useCounters: true,
            useSorting: true
        };
        */
        
        //merge default settings w/ user defined settings (with user-defined settings overriding defaults)
        //$.extend(settings, op);

        op.box1Filter = '';
        op.box2Filter = '';
        
        //define sort function
       // if (settings.sortBy == 'text') {
            onSort = function(a, b) {
                var aVal = a.text.toLowerCase();
                var bVal = b.text.toLowerCase();
                if (aVal < bVal) { return -1; }
                if (aVal > bVal) { return 1; }
                return 0;
            };

        $('#' + op.box2View).dblclick(function() {
            MoveSelected(op.box2View, op.box1View);
            Filter(op.box1View, op.box1Storage, op.box1Filter);
        });
        $('#' + op.to1).click(function() {
            MoveSelected(op.box2View, op.box1View);
            Filter(op.box1View, op.box1Storage, op.box1Filter);
        });
        $('#' + op.allTo1).click(function() {
            MoveAll(op.box2View, op.box1View);
            Filter(op.box1View, op.box1Storage, op.box1Filter);
        });

        $('#' + op.box1View).dblclick(function() {
            MoveSelected(op.box1View, op.box2View);
            Filter(op.box2View, op.box2Storage, op.box2Filter);
        });
        $('#' + op.to2).click(function() {
            MoveSelected(op.box1View, op.box2View);
            Filter(op.box2View, op.box2Storage, op.box2Filter);
        });
        $('#' + op.allTo2).click(function() {
            MoveAll(op.box1View, op.box2View);
            Filter(op.box2View, op.box2Storage, op.box2Filter);
        });

        //initialize the counters
        //if (settings.useCounters) {
        //    UpdateLabel(group1);
       //     UpdateLabel(group2);
       // }

        //pre-sort item sets
       // if (settings.useSorting) {
            Sortop(op.box1View);
            Sortop(op.box2View);
       // }

        //hide the storage boxes
        $('#' + op.box1Storage + ',#' + op.box2Storage).css('display', 'none');
    };

    /*
    function UpdateLabel(group) {
        var showingCount = $("#" + group.view + " option").size();
        var hiddenCount = $("#" + group.storage + " option").size();
        $("#" + group.counter).text('Showing ' + showingCount + ' of ' + (showingCount + hiddenCount));
    }
    */

    function Filter( g_view, g_storage, g_filter ) {
        var filterLower;
       // if (settings.useFilters) {
       //     filterLower = $('#' + g_filter).val().toString().toLowerCase();
       // } else {
            filterLower = '';
       // }
        $('#' + g_view + ' option').filter(function(i) {
            var toMatch = $(this).text().toString().toLowerCase();
            return toMatch.indexOf(filterLower) == -1;
        }).appendTo('#' + g_storage);
        $('#' + g_storage + ' option').filter(function(i) {
            var toMatch = $(this).text().toString().toLowerCase();
            return toMatch.indexOf(filterLower) != -1;
        }).appendTo('#' + g_view);
        try {
            $('#' + g_view + ' option').removeAttr('selected');
        }
        catch (ex) {
            //swallow the error for IE6
        }
       // if (settings.useSorting) {
	         Sortop(g_view);   
	   // }
        //if (settings.useCounters) { UpdateLabel(group); }
    }

    function Sortop(g_view) {
        var $toSortop = $('#' + g_view + ' option');
        $toSortop.sort(onSort);
        $('#' + g_view).empty().append($toSortop);
    }

    function MoveSelected(from_view, to_view) {
        $('#' + from_view + ' option:selected').appendTo('#' + to_view);

        try {
            $('#' + from_view + ' option,#' + to_view + ' option').removeAttr('selected');
        }
        catch (ex) {
            //swallow the error for IE6
        }
        //Filter(toGroup);
        //if (settings.useCounters) { UpdateLabel(fromGroup); }
    }

    function MoveAll(from_view, to_view) {
        $('#' + from_view + ' option').appendTo('#' + to_view);

        try {
            $('#' + from_view + ' option,#' + to_view + ' option').removeAttr('selected');
        }
        catch (ex) {
            //swallow the error for IE6
        }
        //Filter(toGroup);
        //if (settings.useCounters) { UpdateLabel(fromGroup); }
    }

    function ClearFilter(group) {
        $('#' + group.filter).val('');
        $('#' + group.storage + ' option').appendTo('#' + group.view);
        try {
            $('#' + group.view + ' option').removeAttr('selected');
        }
        catch (ex) {
            //swallow the error for IE6
        }
       // if (settings.useSorting) { 
	        Sortop(group); 
	   // }
        //if (settings.useCounters) { UpdateLabel(group); }
    }
})(jQuery);