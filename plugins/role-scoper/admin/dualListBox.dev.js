/*
* Developed by Justin Mead
* Copyright (c) 2011 MeadMiracle
* www.meadmiracle.com / meadmiracle@gmail.com
* Licensed under the MIT License http://opensource.org/licenses/MIT
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
* to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
* sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
* As this Dual Listbox jQuery Plug-in was initially released with GPL v2 License which was quite restrictive, I got the following explicit permission
* to release it under MIT License from the original author and copyright holder.
* From: Justin Mead <xxxxxxxxxxx@gmail.com>
* Date: December 19, 2012, 4:11:28 PM PST
* To: Parvez <xxxxxxxxxxx@gmail.com>
* Cc: web@meadmiracle.com
* Subject: Re: Dual Listbox jQuery Plug-in
* Happy to help! You have my permission to release my code under the MIT license. Good luck with your project!
* Justin
*
* Version 1.3.KB
* Modifications by Kevin Behrens for naroow use within Role Scoper plugin
*
* OPTIONS LISTING:
* *box1View, box2View - the id attributes of the VISIBLE listboxes
* *to1, to2 - the id attributes of the elements used to transfer only selected items between boxes
* *allTo1, allTo2 - the id attributes of the elements used to transfer ALL (visible) items between boxes
*
* TRANSFER MODES:
* * 'move' - In this mode, items will be removed from the box in which they currently reside and moved to the other box. This is the default.
*/

(function($) {
    $.configureBoxes = function(op) {
        $('#' + op.box2View).dblclick(function() {
            MoveSelected(op.box2View, op.box1View);
        });
        $('#' + op.to1).click(function() {
            MoveSelected(op.box2View, op.box1View);
        });
        $('#' + op.allTo1).click(function() {
            MoveAll(op.box2View, op.box1View);
        });

        $('#' + op.box1View).dblclick(function() {
            MoveSelected(op.box1View, op.box2View);
        });
        $('#' + op.to2).click(function() {
            MoveSelected(op.box1View, op.box2View);
        });
        $('#' + op.allTo2).click(function() {
            MoveAll(op.box1View, op.box2View);
        });
		
        $('#' + op.box1Storage + ',#' + op.box2Storage).css('display', 'none');
    };

    function MoveSelected(from_view, to_view) {
        $('#' + from_view + ' option:selected').appendTo('#' + to_view);
        $('#' + from_view + ' option,#' + to_view + ' option').removeAttr('selected');
    }

    function MoveAll(from_view, to_view) {
        $('#' + from_view + ' option').appendTo('#' + to_view);
        $('#' + from_view + ' option,#' + to_view + ' option').removeAttr('selected');
    }
})(jQuery);