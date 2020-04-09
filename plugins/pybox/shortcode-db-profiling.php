<?php

add_shortcode('db_profiling', 'db_profiling');

function db_profiling($options, $content) {

  $r = '<form>';
  $r .= optionsHelper(array('4'=>'Year', '7'=>'Month','10'=>'Day', '13'=>'Hour', '16'=>'Minute'), 'frequency');
  $r .= optionsHelper(array('all'=>'All', 'noDatabases'=>'No databases', 'databases'=>'Databases'), 'activity'); 
  $r .= '<input type="submit"/></form>';

  $url = UPYBOX . 'db-profiling.php';
  $period = getSoft($_GET, 'period','');
  $F = getSoft($_GET, 'frequency', '');
  $A = getSoft($_GET, 'activity', '');
  $r .= <<<EOT
<script type='text/javascript'>
  $ = jQuery;
  $('.entry-content').append('<div id="zzz"></div>');
  $(function(){pyflex({'id':'zzz','url': '$url', 'dbparams': {'frequency':'$F','activity':'$A'}})});
</script>
EOT;
  return $r;
  
}

// paranoid against newlines