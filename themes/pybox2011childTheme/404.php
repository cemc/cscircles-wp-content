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
					<h1 class="entry-title"><?php _e( 'Error 404. Not enough internets.', 'twentyeleven' ); ?></h1>
				</header>

				<div class="entry-content">
					<p>
<?php _e( "We cannot find anything at the address $niceurl Try a different address, ".
	  'a <a href="'.site_url('search/').'">search</a>, '.
	  'or <a href="'.site_url('contact/').'">contact us</a> '.
	  'if the website sent you here via a broken link.', 'twentyeleven' ); ?></p>

<form id="searchform" action="<?php echo USEARCH; ?>">
  <input type="hidden" name="cx" value= "007230231723983473694:r0-95non7ri" />
     <input type="hidden" name="cof" value="FORID:9" />
     <input type="hidden" name="ie" value="UTF-8" />
     <input type="text" name="q" id="s"  />
     <input type="submit" name="sa" value="Search" />
     <input type="hidden" name="nojs" value="1" />
  <img src="http://www.google.com/cse/images/google_custom_search_smwide.gif">
  </form>

	  <?php /*get_search_form();*/ ?>

				</div><!-- .entry-content -->
			</article><!-- #post-0 -->

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>