<?php

add_action('template_redirect', 'visualizer_tr');

function visualizer_tr() {
  $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
  $homeurl = trim(parse_url(home_url(), PHP_URL_PATH), '/');
  if ($homeurl != '') $homeurl .= '/';
  if (! preg_match("@^".preg_quote($homeurl)."visualize(/|$)@", $url, $matchdummy))
    return;

  //$wp_query->is_404 = false;
  header("HTTP/1.1 200 OK");
  include("visualize.php-include");
  exit;
}