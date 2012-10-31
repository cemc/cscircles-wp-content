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
    if ($c != 0) return $c;
    if ($l1['number'] == NULL ^ $l2['number'] == NULL) {
      return $l1['number'] == NULL ? 1 : -1;
    }
    $c = $l1['major'] - $l2['major'];
    if ($c != 0) return $c;
    return strcmp($l1['minor'], $l2['minor']);
  }
  usort($lessons, 'cmp');

  pyboxlog('[makedb] Rebuilding problem and lesson db', TRUE);

  global $wpdb;

  $lesson_table_name = $wpdb->prefix . "pb_lessons";
  $problem_table_name = $wpdb->prefix . "pb_problems";
  $wpdb->query("TRUNCATE TABLE $problem_table_name;");
  $wpdb->query("TRUNCATE TABLE $lesson_table_name;");

  $currlang = 'xx';
  $i = -1;
  $gets = array();
  foreach ($lessons as $l) {
    if ($currlang != $l['lang']) {
      $currlang = $l['lang'];
      $i = 0;
    }
    $l['ordering'] = $i;    
    echo "<div id='x$currlang$i'>";
    if ($l['number'] != NULL)
      echo ($wpdb->insert($lesson_table_name, $l)!=1?'insert bad':'insert ok');
    echo ' ' . json_encode($l) . "</div>\n";
    $url = get_page_link($l['id']);
    $url = str_replace('/dev', '', $url);
    $index = $l['number'] == NULL?-1:$i;
    $gets[] = array('url'=>$url, 'makeproblemdb'=>$index, 'lessonlang'=>$currlang, 'lessonnumber'=>$l['number'],
		    'id'=>'#x'.$currlang .$i);
    $i++;
  }
  echo '<script type="text/javascript">
var gets = '.json_encode($gets).';
foo = function(a, asynch) {
   jQuery.ajax({type:"GET",
               url:a["url"],
               async:asynch,
               data:jQuery.param({"makeproblemdb":a["makeproblemdb"],
                                  "lessonlang":a["lessonlang"],
                                  "lessonnumber":a["lessonnumber"]}),
               success:function(data){jQuery(a["id"]).append("<br/>retrieved, "+data.length+" bytes</br>");}});
}
for (var i=0; i<gets.length; i++)
  if (gets[i]["lessonlang"]=="en")
    foo(gets[i], false);  
for (var i=0; i<gets.length; i++)
  if (gets[i]["lessonlang"]!="en")
    foo(gets[i], true);  
</script>';

}

// end of file