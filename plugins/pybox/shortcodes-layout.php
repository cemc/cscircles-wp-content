<?php

add_shortcode('br', 'printbr');
function printbr($o,$c){
  return '<br/>';
}

add_shortcode('collapsible', 'print_collapsible');
function print_collapsible($options, $content) {
  $caption = $options['caption'];
  echo "<div class='collapseContain hiding'>
<div class='collapseHead'><span class='icon'></span>
$caption
</div>
<div class='collapseBody'>";
  echo do_shortcode($content);
  echo '</div></div>';
}

add_shortcode('accordion', 'print_accordion');
function print_accordion($options, $content) {
  echo '<div class="accordion">';
  echo do_shortcode($content);
  echo '</div>';
}
