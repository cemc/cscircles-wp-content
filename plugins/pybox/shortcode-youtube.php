<?php

add_shortcode('youtube', 'youtube');

function youtube($options, $content) {
  $id = $options[0];
  return "<div style='text-align: center;'>
<iframe width='560' height='315' src='http://www.youtube.com/embed/" . $id . "' frameborder='0' allowfullscreen>
</iframe>
</div>";
}

