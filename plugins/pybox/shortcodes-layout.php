<?php

add_shortcode('br', 'printbr');
function printbr($o,$c){
  return '<br/>';
}

add_shortcode('collapsible', 'print_collapsible');
function print_collapsible($options, $content) {
  $caption = $options['caption'];
  $style = "hiding";
  if (array_key_exists('show', $options) || in_array('show', $options))
    $style = "showing";
  return "<div class='collapseContain $style'>
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
