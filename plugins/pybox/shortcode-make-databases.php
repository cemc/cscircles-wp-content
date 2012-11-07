<?php

add_shortcode('makedb', 'makedb');

function makedb($content, $options) {

  $out = get_pages();
  $lessons = array();

  foreach ($out as $page) {
    $s = $page->post_title;
    $m = preg_match('/^([0-9]+)([A-Z]?)\: (.*)$/', $s, $matches);
    global $polylang;
    $lang = $polylang->get_post_language($page->ID)->slug;
    if ($m >= 1) 
      $lessons[] = array('number'=>$matches[1].$matches[2], 
			 'title'=>$matches[3], 
			 'major'=>$matches[1], 
			 'minor'=>$matches[2], 
			 'id'=>$page->ID,
			 'lang'=>$lang); 
    elseif (get_page_by_path('console')->ID == pll_get_post($page->ID, 'en'))
      $lessons[] = array('id'=>$page->ID, 'number'=>NULL, 'lang'=>$lang);
    // go through the console page too, mainly to set up the right url in history grids,
    // it does not get added to pb_lessons but its contents do get added to pb_problems
  }

  function cmp($l1, $l2) {
    $c = strcmp($l1['lang'], $l2['lang']);
    if ($c != 0) {
      if ($l1['lang']=='en') return -1; // put english first
      if ($l2['lang']=='en') return 1;
      return $c;
    }
    if ($l1['number'] == NULL ^ $l2['number'] == NULL) {
      return $l1['number'] == NULL ? 1 : -1;
    }
    $c = $l1['major'] - $l2['major'];
    if ($c != 0) return $c;
    return strcmp($l1['minor'], $l2['minor']);
  }
  usort($lessons, 'cmp');

  pyboxlog('[makedb] Rebuilding problem and lesson db', TRUE);

  global $wpdb, $SKIP_DB_REBUILD;
  $SKIP_DB_REBUILD = false; // set to true to be diagnostic

  $lesson_table_name = $wpdb->prefix . "pb_lessons";
  $problem_table_name = $wpdb->prefix . "pb_problems";

  if (!$GLOBALS['SKIP_DB_REBUILD']) {
    echo 'Truncating tables ';
    $wpdb->query("TRUNCATE TABLE $problem_table_name;");
    $wpdb->query("TRUNCATE TABLE $lesson_table_name;");
  }

  $currlang = 'xx';
  $i = -1;
  $gets = array();
  foreach ($lessons as $l) {
    echo '<pre>';
    if ($currlang != $l['lang']) {
      $currlang = $l['lang'];
      $i = 0;
    }
    if ($l['number'] == NULL) 
      $index = -1;
    else {
      $index = $i;
      $i++;
    }
    $l['ordering'] = $index;

    if ($l['number'] != NULL) {
      echo 'About to insert lesson: ' . rowSummary($l); //. json_encode($l);
      if (!$GLOBALS['SKIP_DB_REBUILD']) 
	echo ($wpdb->insert($lesson_table_name, $l)!=1?'<br>insert bad':' insert ok');
    }

    global $lesson_reg_info, $pyRenderCount, $post;
    $lesson_reg_info = array('index'=>$index, 'lang'=>$currlang, 'fullnumber'=>$l['number'],
			   'url'=>str_replace('/dev','', get_page_link($l['id'])), 'id'=>$l['id']);

    // render! the following line is not just cosmetic as it registers the problems.
    echo '<br>Snippet: ' . htmlspecialchars(substr(do_shortcode(get_page($l['id'])->post_content), 0, 50));
    $pyRenderCount = 0; // to get problem links working
    echo '</pre>';
  }
  $lesson_reg_info = 1;
}

// end of file