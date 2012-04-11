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
  Computer Science Circles is a free service of the <a href="http://www.cemc.uwaterloo.ca/">Centre for Education in Mathematics and Computing</a> at the <a href="http://www.uwaterloo.ca">University of Waterloo</a>. &copy; 2010&ndash;2012

   <div id="departmentaddress">
    CEMC |
    University of Waterloo, MC 5104 |
    200 University Avenue West |
    Waterloo, Ontario, Canada N2L 3G1 |
    Phone: 519 888 4808 |
    Fax: 519 746 6592
   </div>

  </div> 

 </footer><!-- #colophon -->
</div><!-- #page -->


<?php wp_footer(); ?>

</body>
</html>