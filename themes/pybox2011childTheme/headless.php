<?php
/**
 * Template Name: Headless Page
 *
 * Description: A Page Template that showcases Sticky Posts, Asides, and Blog Posts.
 *
 * The showcase template in Twenty Eleven consists of a featured posts section using sticky posts,
 * another recent posts area (with the latest post shown in full and the rest as a list)
 * and a left sidebar holding aside posts.
 *
 * We are creating two queries to fetch the proper posts and a custom widget for the sidebar.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

// Enqueue showcase script for the slider


  //get_header(); ?>

<?php
/**
 * The Header for our theme.
 * (derived for pybox from the standard 'twentyeleven' theme)
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */
?><!DOCTYPE html> 
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head> 
<?php
if (stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE 8')!==FALSE ||
    stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE 7')!==FALSE)
    echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" >'."\n";
 else
  echo '<meta http-equiv="X-UA-Compatible" content="IE=edge" >'."\n";
//echo "<!-- ".$_SERVER["HTTP_USER_AGENT"]." -->";
?>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 */
	global $page, $paged;

	wp_title( '|', true, 'right' );

	// Add the blog name.
	bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'twentyeleven' ), max( $paged, $page ) );

	?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<?php //<link rel="pingback" href="<question-markphp bloginfo( 'pingback_url' ); question-mark>" /> ?>
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<?php
	/* We add some JavaScript to pages with the comment form
	 * to support sites with threaded comments (when in use).
	 */
	    //	if ( is_singular() && get_option( 'thread_comments' ) )
	    //wp_enqueue_script( 'comment-reply' );

	/* Always have wp_head() just before the closing </head>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to add elements to <head> such
	 * as styles, scripts, and meta tags.
	 */
	wp_head();
?>
</head>

<body <?php body_class(); ?>>
<style>
.entry-content {padding:0px !important}
.page {margin:0px !important}
#page {pbToggleCodeMirror(0);}
#main {padding-bottom: 0px;}
html {margin: 0px !important; height: auto}
#content {margin: 0px !important}
#wpadminbar {display: none !important}
</style>
<div id="page" class="hfeed">
    <div id="main" class="hfeed">
		<div id="primary" class="showcase">
			<div id="content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

				<?php
					/*
					 * We are using a heading by rendering the_content
					 * If we have content for this page, let's display it.
					 */
					if ( '' != get_the_content() )
						get_template_part( 'content', 'intro' );
				?>

				<?php endwhile; ?>


			</div><!-- #content -->
		</div><!-- #primary -->
</div>
</div>

                        <?php wp_footer(); ?>
                        
</body>
</html>