<?php
/**
 * The template for displaying 404 pages (Not Found).
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

$niceurl = '<pre>'."\n".htmlspecialchars($_SERVER["REQUEST_URI"]).'</pre>';

get_header(); ?>

	<div id="primary">
		<div id="content" role="main">

			<article id="post-0" class="post error404 not-found">
				<header class="entry-header">
					<h1 class="entry-title"><?php echo __t( 'Error 404. Not enough internets.' ); ?></h1>
				</header>

				<div class="entry-content">
					<p>
<?php      echo sprintf(__t("We cannot find anything at the address %s"), $niceurl)." ".
	   sprintf(__t('Try a different address, a search, or <a %1$s>contact us</a> if the website sent you here via a broken link.'), 'href="'.cscurl('contact').'"');
?></p>

<?php echo get_search_form(); 
// pulls from google search plugin if installed, default search otherwise
 ?>

				</div><!-- .entry-content -->
			</article><!-- #post-0 -->

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>