<?php

add_shortcode('style', 'style');

function style($options, $content) {
  return "<style> " . $content . "</style>";
}

