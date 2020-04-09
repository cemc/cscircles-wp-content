<?php

add_shortcode('pyDebug', 'pyDebug');
function pyDebug($o, $c){
  if (WP_DEBUG)
    return do_short_and_sweetcode($c);
}

add_shortcode('authorship', 'authorship');
function authorship($o, $c){
  // this is treated only like a comment, let's include it anyway
  return "<!-- " . $o['info'] . "-->";
}

add_shortcode('newuserwelcome', 'newuserwelcome');
function newuserwelcome($options, $content) {
  if ( ! is_user_logged_in() ) {
    $ulog = wp_login_url( cscurl('homepage') );
    $uuse = cscurl('usage');

    return "<em>".sprintf(__t('New around here? Read <a %1$s>Using this Website</a>. You can also <a %2$s>click here to create an account or log in</a>.'), 
			  "class='open-same-window' href='$uuse'", 
			  "class='open-same-window' href='$ulog'")."</em><br/>";
  }
  return "";
}

// two helper functions for exporting and list_pybox_pages
function translator_template_helper($matches) {
  $attr = shortcode_parse_atts( $matches[3] );

  // anything which is not a slug is not an exercise and passes through unchanged.
  if (!array_key_exists('slug', $attr)) 
    return $matches[0]; 
    
  if (!array_key_exists('title', $attr)) {
    pyboxlog('[in translator_template_helper] Warning: ' . $attr['slug'] . ' has no title!');
    $attr['title'] = "";
  }

  $r = '[pyRecall slug="' . $attr['slug'] . '"';

  $to_translate = array('title', 'epilogue', 'right', 'wrong', 'defaultcode');
  foreach ($to_translate as $key) 
    if (array_key_exists($key, $attr)) {
      $r .= ' ' . $key .'="';
      $value = $attr[$key];
      $value = str_replace('"', '""', $value);
      $value = str_replace("\n", '\n', $value);
      $r .= $value . '"';
    }

  $r .= ']' . $matches[5] . '[/pyRecall]';

  return $r;
}

function translator_template($page) {
  $page = str_replace("\r", "", $page);
  $regex = '(\[?)\[(pyExample|pyShort|pyMulti|pyMultiScramble|pyBox)\b((?:[^\'"\\]]|' . "'[^']*'|" . '"[^"]*")*)(?:(\/))?\](?:(.+?)\[\/\2\])?(\]?)';
  return preg_replace_callback( "_$regex"."_s", 'translator_template_helper', $page);
}

add_shortcode('list-pybox-pages', 'list_pybox_pages');
// for the navigation page
function list_pybox_pages($options, $content) {
  $out = get_pages(array('parent'=>0, 'status'=>'publish'));
  $links = array();
  foreach ($out as $page) {

    if (PLL()->model->get_translation('post', $page->ID, 'en') == $page->ID) { //english only

      $links[] = array("url"=>get_page_link( $page->ID), "title"=>$page->post_title);
      if (isSoft($_GET, 'export', 'Y')) {
	$p = get_page($page->ID);
	$slug = $p->post_name;
	$f = fopen(PEXPORT . $slug . ".txt", 'w');
	fwrite($f, 'Title: ' . $p->post_title . "\n\nContent:\n\n");
	fwrite($f, translator_template($p->post_content));
	fclose($f);
      }
      elseif (isSoft($_GET, 'export', 'raw')) {
	$p = get_page($page->ID);
	$slug = $p->post_name;
	$f = fopen(PEXPORT . $slug . ".txt", 'w');
	fwrite($f, 'Title: ' . $p->post_title . "\n\nContent:\n\n");
	fwrite($f, $p->post_content);
	fclose($f);
      }
    }
  }

  $user_id      = get_current_user_id();
  $current_user = wp_get_current_user();
  $profile_url  = get_edit_profile_url( $user_id );

  if ( $user_id ) {
    $links[] = array("url"=>$profile_url, "title"=>__t('Edit my profile'));
    $links[] = array("url"=>wp_logout_url(), 'title'=>__t('Log out now'));
  } 
  else
    $links[] = array("url"=>wp_logout_url(), 'title'=>__t('Log in or create a new account'));


  foreach ($links as $i=>$page) {
    $s = $page['title'];
    $m = preg_match('/^([0-9]+)([A-Z])?\: (.*)$/', $s, $matches);
    if ($m <= 0)
      $k = '9999---' . $s;
    else {
      $sub = $matches[2];
      if ($sub=="") $sub="-";
      $k = sprintf("%04d", $matches[1]) . '-' . $sub . '-'.$matches[3];
    }
    $links[$i]['key']=$k;
  }
  //  print_r($links);
  function cmp($p1, $p2) {
    return strcmp($p1['key'], $p2['key']);
  }
  usort($links, 'cmp');
  echo '<ul>';
  foreach ($links as $page) {
    echo "<li><a class='open-same-window' href='".$page['url'] ."'>".$page['title']."</a></li>";
  }
  echo '</ul>';
}

// end of file