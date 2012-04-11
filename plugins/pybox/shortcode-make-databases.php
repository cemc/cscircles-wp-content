<?php

add_shortcode('makedb', 'makedb');

function makedb($content, $options) {

  global $wpdb;

  if (PB_DEV || strpos($_SERVER['REQUEST_URI'], '~cscircles') === true) {
    echo "<b>WARNING: You must visit from the canonical site location to get the links to be correct.</b>";
  }

  pyboxlog('[makedb] Rebuilding problem and lesson db', TRUE);

  $out = get_pages();
  $lessons = array();

  foreach ($out as $page) {
    $s = $page->post_title;
    $m = preg_match('/^([0-9]+)([A-Z]?)\: (.*)$/', $s, $matches);
    if ($m >= 1) 
      $lessons[] = array('number'=>$matches[1].$matches[2], 'title'=>$matches[3], 
			 'major'=>$matches[1], 'minor'=>$matches[2], 'id'=>$page->ID);
  }

  function cmp($l1, $l2) {
    $c = $l1['major'] - $l2['major'];
    if ($c != 0) return $c;
    return strcmp($l1['minor'], $l2['minor']);
  }
  usort($lessons, 'cmp');

  $lesson_table_name = $wpdb->prefix . "pb_lessons";
  $problem_table_name = $wpdb->prefix . "pb_problems";
  $wpdb->query("TRUNCATE TABLE $problem_table_name;");
  $wpdb->query("TRUNCATE TABLE $lesson_table_name;");

  // go through the console page too, mainly to set up the right url in history grids
  $lessons[] = array('id'=>'126', 'number'=>NULL); 

  $i=0;
  foreach ($lessons as $l) {
    $l['ordering'] = $i;    
    echo "<div id='x$i'>";
    if ($l['number'] != NULL)
      echo ($wpdb->insert($lesson_table_name, $l)!=1?'insert bad':'insert ok');
    echo ' ' . json_encode($l) . "</div>\n";
    $url = get_page_link($l['id']);
    $index = $l['number'] == NULL?-1:$i;
    echo '<script type="text/javascript">
jQuery.ajax({type:"GET",
             url:"'.$url.'",
             data:jQuery.param({"makeproblemdb":'.$index.',"lessonnumber":"'.$l['number'].'"}),
             success:function(data){jQuery("#x'.$i.'").append("<br/>retrieved, "+data.length+" bytes</br>");}});
</script>';
    $i++;
  }
  
}

// end of file