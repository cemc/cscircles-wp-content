<?php

add_shortcode('youtube', 'youtube');

function youtube($options, $content) {
  $id = $options[0];
  return "<div style='text-align: center;'>
<iframe width='560' height='315' src='https://www.youtube.com/embed/" . $id . 
    "?rel=0' frameborder='0' allowfullscreen>
</iframe>
</div>";
}

