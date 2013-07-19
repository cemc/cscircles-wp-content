<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */
?>
   
</div><!-- #main -->
<footer id="colophon" role="contentinfo">

    <?php
  /* A sidebar in the footer? Yep. You can can customize
   * your footer with three columns of widgets.
   */
get_sidebar( 'footer' );

showLocationHandler(); ?>

  <div id="site-generator">
  <a href="http://cemc.uwaterloo.ca"><div class="cemc logo"></div></a>
  <?php echo sprintf("<a href='http://cemc.math.uwaterloo.ca/copyright.html'>&copy; 2010&mdash;.</a> " . 

		     __t('Computer Science Circles is a free service of the <a %1$s>Centre for Education in Mathematics and Computing</a> at the <a %2$s>University of Waterloo</a>. <br> Total number of exercises completed by all users: %3$s'), 'href="http://www.cemc.uwaterloo.ca/"', 'href="http://www.uwaterloo.ca"', '<b>'.allSolvedCount().'</b>'); ?>

   <div id="departmentaddress">
  <?php echo __t('CEMC');?> |
  <?php echo __t('University of Waterloo, MC 5104');?> |
  <?php echo __t('200 University Avenue West');?> |
  <?php echo __t('Waterloo, Ontario, Canada N2L 3G1');?> |
  <?php echo __t('Phone: 519 888 4808');?> |
  <a href="<?php echo cscurl('contact'); ?>"><?php 
  echo __t('contact us');?></a>
   </div>

  </div> 

 </footer><!-- #colophon -->
</div><!-- #page -->


<?php wp_footer(); ?>

</body>
</html>