<?php

add_shortcode('br', 'printbr');
function printbr($o,$c){
  return '<br/>';
}

add_shortcode('collapsible', 'print_collapsible');
function print_collapsible($options, $content) {
  $caption = $options['caption'];
  return "<div class='collapseContain hiding'>
<div class='collapseHead'><span class='icon'></span>
$caption
</div>
<div class='collapseBody'>"
  . do_shortcode($content)
  . '</div></div>';
}

add_shortcode('accordion', 'print_accordion');
function print_accordion($options, $content) {
  return '<div class="accordion">'
  . do_shortcode($content)
  . '</div>';
}
