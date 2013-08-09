<?php

add_shortcode('style', 'style');

function style($options, $content) {
  return "<style> " . html_entity_decode($content) . "</style>";
}

