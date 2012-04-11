<?php
/*
Plugin Name: Google Custom Search Plugin
Plugin URI: http://aleembawany.com/projects/wordpress/google-custom-search-plugin/
Description: Integrate Google Custom Search into WordPress
Version: 1.3
Author: Aleem Bawany
Author URI: http://aleembawany.com/about/aleem-bawany/
*/

class GoogleCSE
{
	private static $version = '1.3';
	private static $pluginurl = 'http://aleembawany.com/projects/wordpress/google-search-plugin/';

	protected static $google_search_box;
	protected static $google_search_results;
	protected static $google_search_results_width;
	protected static $google_search_results_margin;
	protected static $google_search_show_sidebar;

	protected static $search_url;

	public static function run()
	{
		add_filter('get_search_form', array('GoogleCSE', 'get_form'));
		add_action('template_redirect', array('GoogleCSE', 'template_redirect'), 1);
		add_action('admin_menu', array('GoogleCSE', 'admin_config_page'));

		// setup user options
		self::$search_url = get_option('home') . '/search/';
		self::$google_search_box = stripslashes(get_option('cse-search-box-code'));
		self::$google_search_results = stripslashes(get_option('cse-search-results-code'));
		self::$google_search_results_width = stripslashes(get_option('cse-search-results-width'));
		self::$google_search_results_margin = stripslashes(get_option('cse-search-results-margin'));
		self::$google_search_show_sidebar = get_option('cse-search-show-sidebar');

		// setup google search form for wordpress
      $q = isset($_GET['q']) ? $_GET['q'] : '';
		self::$google_search_box = preg_replace('/size="31"/is', 'id="s" value="'.$q.'" ', self::$google_search_box);
		self::$google_search_box = preg_replace('/cse-search-box/is', 'searchform', self::$google_search_box);
		self::$google_search_box = preg_replace('/action="([^"]*)"/s', 'action="'.self::$search_url.'"', self::$google_search_box);
	}

	public static function admin_config_page()
	{
		add_options_page(__('Google&nbsp;Search'), __('Google&nbsp;Search'), 'manage_options', 'google-search-config', Array('GoogleCSE', 'google_search_conf') );
	}

	public static function google_search_conf()
	{
		if (isset($_POST['submit']) || isset($_POST['submit2'])):
			$box_code = $_POST['cse-search-box-code'];
			$results_code = $_POST['cse-search-results-code'];
			$results_width = $_POST['cse-search-results-width'];
			$results_margin = $_POST['cse-search-results-margin'];
			$results_show_sidebar = $_POST['cse-search-show-sidebar'];

			update_option('cse-search-box-code', $box_code);
			update_option('cse-search-results-code', $results_code);
			update_option('cse-search-results-width', $results_width);
			update_option('cse-search-results-margin', $results_margin);
			update_option('cse-search-show-sidebar', $results_show_sidebar);
		endif;
		?>
		<div class="wrap">
			<h2>Google Custom Search Configuration <small>ver <?php echo self::$version ?></small></h2>
			<p><a href="<?php echo self::$pluginurl ?>" title="Google Search Plugin">Google Search Plugin</a> by <em><a href="http://aleembawany.com/">Aleem Bawany</a></em></p>
			<form method="post">
				<p>
					<a class="button" href="<?php echo self::$search_url ?>" target="search-preview">Preview</a>
					<input type="submit" name="submit" value="<?php echo __('Save Changes') ?>" class="button-primary" />
				</p>

				<h3>Settings</h3>

				<label for="cse-search-results-width">Width: </label><input type="text" id="cse-search-results-width" name="cse-search-results-width" size="5" value="<?php
					echo htmlentities(stripslashes(get_option('cse-search-results-width')));
				?>"> (CSS for width, example: <strong>800px</strong> or <strong>80%</strong>)
				<br /><br />
				<label for="cse-search-results-margin">Margin: </label><input type="text" id="cse-search-results-width" name="cse-search-results-margin" size="20" value="<?php
					echo htmlentities(stripslashes(get_option('cse-search-results-margin')));
				?>"> (CSS for margin example: <strong>20px</strong> or <strong>10px 20px 0 20px</strong> for top/right/bottom/left margins)
				<br /><br />
				<label for="cse-search-show-sidebar">Sidebar: </label><input type="checkbox" id="cse-search-show-sidebar" name="cse-search-show-sidebar" size="20" <?php
					echo (get_option('cse-search-show-sidebar')) == true ? 'checked="checked"' : '';
				?>"> (Show or hide sidebar on results page)

				<h3>Paste code for CSE Search Box:</h3>
				<textarea style="font-size:11px;font-family:'Courier New'" name="cse-search-box-code" rows="14" cols="120"><?php
					echo htmlspecialchars(stripslashes(get_option('cse-search-box-code')));
				?></textarea>

				<h3>Paste Code for CSE Search Results:</h3>
				<textarea style="font-size:11px;font-family:'Courier New'" name="cse-search-results-code" rows="14" cols="120"><?php
					echo htmlspecialchars(stripslashes(get_option('cse-search-results-code')));
				?></textarea>


				<p>
					<a class="button" href="<?php echo self::$search_url ?>" target="search-preview">Preview</a>
					<input type="submit" name="submit" value="<?php echo __('Save Changes') ?>" class="button-primary" />
				</p>
			</form>
		</div>
		<?php
	}


	public static function template_redirect()
	{
		// not a search page; don't do anything and return
		if ( stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)
		{
			return;
		}

		add_action('wp_title', array('GoogleCSE', 'get_title'));
		add_action('wp_head', Array('GoogleCSE', 'search_css'));

		get_header();

		if (self::$google_search_show_sidebar)
		{
			echo '<div id="content" class="narrowcolumn">';
		}
		else
		{
			echo '<div id="content">';
		}
		echo self::$google_search_box;
		echo self::$google_search_results;
		echo '</div>';

		if (self::$google_search_show_sidebar)
		{
			echo get_sidebar();
		}

		get_footer();
		exit;
	}

	public static function get_title()
	{
		// if(is_404())
		if (isset($_GET['q']))
		{
			// change status code to 200 OK since /search/ returns status code 404
			@header("HTTP/1.1 200 OK",1);
			@header("Status: 200 OK", 1);
			return $_GET['q'].' -';
		}
	}

	public static function search_css()
	{
		echo '<style type="text/css">';
		echo '#cse-search-results iframe { width:'.self::$google_search_results_width.'; margin:'.self::$google_search_results_margin.'; }';
		echo '</style>';
	}



	public static function get_form($form)
	{
		if ($form == '') return;

		$form = preg_replace('/type="submit"/', 'type="submit" id="searchsubmit"', self::$google_search_box);

		return $form;
	}

}

GoogleCSE::run();




?>
