<?php

require_once("include-me.php");
require_once(PWP_LOADER);

function showLocationHandler() {

  global $wpdb, $post;
  $table_name = $wpdb->prefix . "pb_lessons";

  if (!isset($post)) return '';//not in a single page

  $here = $post->ID;

  $thisrow = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $here");
  
  if ($thisrow == NULL) 
    return '';// not a numbered lesson

  $lo = $thisrow->ordering - 1;
  $hi = $thisrow->ordering + 2;

  $results = $wpdb->get_results("SELECT * FROM $table_name WHERE ordering >= $lo AND ordering <= $hi ORDER BY ordering ASC");
  
  echo '<div class="locator">';
  echo '<table class="locator"><tr>';
  //echo '<td style="text-align: center;">Navigation</td>';
  echo '<td class="locator">';
  foreach ($results as $row) {
    if ($row->ordering < $thisrow->ordering) $s = 'l';
    elseif ($row->ordering > $thisrow->ordering) $s = 'r';
    else $s = 'c';
    $factor = $row->ordering - $thisrow->ordering;
    if ($factor > 0) $factor = $factor - 1;
    $factor = abs($factor);
    $factor = 100-10*$factor;
    $longname = $row->number.': '.$row->title;

    echo '<a style="font-size:'.$factor.'%" class="open-same-window locator locator-'.$s.'" ';
    
    if ($s != 'c') 
      echo ' title="'.$longname.'" href="'.get_page_link($row->id).'">'; 
    else 
      echo ' title="'.$longname.' (goes to top of current page)" onclick="scrollToTop()">';
    echo "<span class='buttn'>";
    if ($thisrow->ordering == $row->ordering-1) 
      echo "<span class='nextlesson'>Next</span> ";
    echo $longname;
    echo '</span></a>';
  }
  echo '</td>';
  echo '</tr></table></div>';
}


// end of file