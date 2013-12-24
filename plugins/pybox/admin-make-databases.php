<?php

function cscircles_makedb_page() {

  echo "<div class='wrap'>
<h2>Rebuild CS Circles Databases</h2>
<div>This page will rebuild the lesson database and the problem database. 
(If you're writing your own lessons, it assumes a structure like 99X-lessontitle
on the lesson slugs; contact us for help if needed.)</div>";

  if (!array_key_exists('submitted', $_REQUEST)) {
    echo "<form method='get' action='admin.php'>
   <input type='hidden' name='page' value='cscircles-makedb'>
   <input type='hidden' name='submitted' value='true'>
   <button class='button-primary' id='submit'>Rebuild Databases</button></form>";
  }
  else{

  $out = get_pages();
  $lessons = array();

  foreach ($out as $page) {
    $s = $page->post_title;
    $m = preg_match('/^([0-9]+)([A-Z]?)\: (.*)$/', $s, $matches);
    if (class_exists('PLL_Base')) {
      global $polylang;
      $lang = $polylang->get_post_language($page->ID);
      if ($lang != NULL) $lang = $lang->slug;
      else $lang = substr(get_bloginfo("language"), 0, 2);
    }
    else {
      // use default language
      $lang = currLang2();
    }
    if ($m >= 1) 
      $lessons[] = array('number'=>$matches[1].$matches[2], 
			 'title'=>$matches[3], 
			 'major'=>$matches[1], 
			 'minor'=>$matches[2], 
			 'id'=>$page->ID,
			 'lang'=>$lang); 
    elseif ((class_exists('PLL_Base') && (get_page_by_path('console')->ID == pll_get_post($page->ID, 'en')))
            || get_page_by_path('console')->ID == $page->ID)
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
    echo '<b>Truncating tables: ';
    $wpdb->query("TRUNCATE TABLE $problem_table_name;");
    $wpdb->query("TRUNCATE TABLE $lesson_table_name;");
    echo 'done</b>';
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

    // pyboxlog($currlang . ' ' . $lesson_reg_info['url'], FALSE);  // was used Apr. 7 to help transl.
    
    // render! the following line is not just cosmetic as it registers the problems.
    echo '<br>Snippet: ' . htmlspecialchars(substr(do_sweetcode(get_page($l['id'])->post_content), 0, 50));
    $pyRenderCount = 0; // to get problem links working
    echo '</pre>';
  }
  $lesson_reg_info = 1;
}
echo '</div>';
}

// end of file