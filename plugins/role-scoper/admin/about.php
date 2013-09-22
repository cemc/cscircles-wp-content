<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

?>
<ul class="masthead"><li id="masthead-main" title="Agapetry Creations"><a class="agp-toplink" href="http://agapetry.net"> </a></li></ul>
<div id="wrap" style="height: 100%">

<div class="rc-about-dialog">
<p>
<a title="agape" href="http://www.merriam-webster.com/cgi-bin/audio.pl?agape002.wav=agape" target="_blank">a<small><small>&#8226;</small></small>ga<small><small>&#8226;</small></small>p&eacute;</a> (&alpha;&gamma;&alpha;&pi;&eta;): 
<?php 
_e('unselfish, benevolent love, born of the Spirit.', 'scoper');
?>
</p><p>
<?php 
_e('Agap&eacute; discerns needs and meets them unselfishly and effectively.', 'scoper');
?>
</p><p>
<?php
printf(__('These WordPress plugins are part of my agap&eacute; try, a lifelong effort to love God and love people by rightly using the time and abilities He has leant me. As a husband, father, engineer, farmer and/or software developer, I have found this stewardship effort to be often fraught with contradiction. A wise and sustainable balancing of roles has seemed to elude me. Yet I want to keep trying, trusting that if God blesses and multiplies the effort, it will become agapetry, a creative arrangement motivated by benevolent love.  A fleeting childlike sketch of the beautiful %1$s chain-breaking agap&eacute;%2$s which %3$s Jesus Christ unleashed%4$s so %5$s freely%6$s and aptly on an enslaving, enslaved world.', 'scoper'), '<a href="http://www.biblegateway.com/passage/?search=Isaiah%2059:1-60:3;Matthew%203:1-12;Luke%204:5-8;Matthew%205:1-48;Matthew%206:9-15;&version=50;" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=Matthew%2020:20-28;Matthew%2026:36-49;John%2018:7-12;John%2019:1-30;1%20John%202:1-6;&version=47;" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=Isaiah%2055;John%207:37-51;&version=47;" target="_blank">', '</a>');
?>
</p><div>
<?php
printf( __('This ongoing permissions quest has been supported by:', 'scoper') );
?>
</div><ul id="rs-thanks" class="rs-notes">
<li>
<?php
printf( __('The excellent and ever-improving WordPress code base and the %1$score contributors%2$s who make it happen', 'scoper' ), '<a href="http://trac.wordpress.org">', '</a>' );
?>
</li><li>
<?php
printf( __('Translations by %1$s, %2$s (Spanish) and %3$s (French)', 'scoper'), "<a href='http://www.rafo.cl/' target='_blank'>Rafo&copy; Computaci&oacute;n & Web</a>", "<a href='http://www.desarrollowebdequeretaro.com' target='_blank'>David G&oacute;mez Becerril</a>", "Chryjs" );
?>
</li><li>
<?php
_e('The patience of my family with what has been an unending and frequently unrewarding enterprise', 'scoper' );
?>
</li><li>
<?php
_e('Gracious and descriptive support forum participants', 'scoper' );
?>
</li><li>
<?php
_e('Repeat clients who forgive my sporadic availability. <span style="font-weight:bold;color:#FFC430">This is being fixed too!</span>', 'scoper' );
?>
</li></ul>
<p>
<?php
printf(__( 'Role Scoper has been available free of charge since May 2008, with continuous improvement into 2011.  At that time, the bulk of development and support shifted to a successor plugin, %1$s.  May 2013 marks Press Permit&apos;s production release and the availability of Press Permit Core as a free download.', 'scoper' ), '<a href="http://presspermit.com">Press&nbsp;Permit</a>' );
?>
<p class="agp-proceed">
<?php
printf(__( 'Press Permit introduces a more straightforward permissions model, %1$skeeping Role Scoper&apos;s best features and adding many more%2$s while gutting most of its inefficiencies. New features include Custom Post Statuses, BuddyPress Role Groups, bbPress permissions and group membership scheduling and expiration. If you would like to steer these development efforts and share in the fruit, there are several ways to proceed:', 'scoper' ), '<a href="http://presspermit.com/pp-rs-feature-grid">', '</a>' );
?>
</p><ul class="rs-notes agp-proceed">

<?php if ( ! defined( 'RSU_VERSION' ) ) :?>
<li>
<?php
$slug = 'role-scoper-migration-advisor';
$use_network_admin = is_multisite() && ( is_network_admin() || agp_is_plugin_network_active(SCOPER_FILE) ) && is_super_admin();
$_url = "update.php?action=$slug&amp;plugin=$slug&pp_install=1&TB_iframe=true&height=400";
$install_url = ( $use_network_admin ) ? network_admin_url($_url) : admin_url($_url);
$rs_migration_url = wp_nonce_url($install_url, "{$slug}_$slug");
//$install_link =  "<span> &bull; <a href='$url' class='thickbox' target='_blank'>" . __awp('install', 'pp') . '</a></span>';

printf(__('Install %1$s to learn which elements of your RS installation can be imported or configured.', 'scoper'), '<span class="plugins update-message"><a href="' . $rs_migration_url . '" class="thickbox" title="install Role Scoper Migration Advisor">Role Scoper Migration Advisor</a></span>');
?>
</li>
<?php endif; ?>

<li>
<?php
printf(__('Install %1$s to manage type-specific roles and content-specific read access.', 'scoper'), '<span class="plugins update-message"><a href="' . awp_plugin_info_url('press-permit-core') . '" class="thickbox" title=" Press Permit Core">Press&nbsp;Permit&nbsp;Core</a></span>');
?>
</li><li>
<?php
printf(__('%1$s by creating a 30 day evaluation site %2$sfor a nominal fee%3$s.', 'scoper'), '<a href="http://try.presspermit.com/">Try Press&nbsp;Permit&nbsp;Pro</a>', '<a href="http://presspermit.com/purchase/press-permit-evaluation/">', '</a>');
?>
</li><li>
<?php
printf(__('%1$sBuy%2$s %3$s for 12 months of access to %4$sextension plugins%5$s and professional support.', 'scoper'), '<a href="http://presspermit.com/purchase/">', '</a>', '<a href="http://presspermit.com/">Press&nbsp;Permit&nbsp;Pro</a>', '<a href="http://presspermit.com/pp-extensions/">', '</a>');
?>
</li><li>
<?php
printf(__('View Kevin&apos;s %1$s and submit a request.', 'scoper'), '<a href="http://agapetry.net/availability/">Consulting&nbsp;Availability</a>');
?>
</li><li>
<?php
$paypal_button = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="donate"><input type="hidden" name="cmd" value="_s-xclick" /> <input type="image" style="background:none" src="http://agapetry.net/btn_donate_SM.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" /> <img alt="" border="0" src="http://agapetry.net/pixel.gif" width="1" height="1" style="opacity:0.01;" /> <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
" /></form>';
printf(__('If you just want to say thanks for past development and support, %s', 'scoper'), $paypal_button);
?>
</li></ul>

<p>
<?php
_e('I&apos;m looking forward to some excellent and mutually sustainable software initiatives, and would be pleased if our development goals overlap.', 'scoper');
?>
</p>

<div>
<?php
_e('Sincerely,', 'scoper');
?>
</div>

<div class="agp-signature">
<?php
_e('Kevin Behrens', 'scoper');
?>
</div>

</div> <?php /*rc-about-dialog*/?>

<a href="http://presspermit.com"><div class="pp-logo">&nbsp;</div></a>
<div class="madein">&nbsp;</div>
<div style="height: 150px;">&nbsp;</div>

</div> <?php /*wrap*/?>