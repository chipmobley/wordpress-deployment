<?php
/**
 * The options page in the admin settings.
 *
 * @package WordPress
 * @subpackage myReviewsPage
 * @since myReviewsPage 1.0
 */
?>
<div class="wrap">
	<h2>myReviewsPage Advanced Settings</h2>

	<div class="myReviewsPage_options_subheader">
	  <p class="myReviewsPage_options_subheader_links">
	    Click here for <a href="http://www.myreviewspage.com/blog/online-reviews-videos" target="_blank">Help and Tutorials</a>
	  </p>
	  <?php echo myReviewsPage_poweredByBadge(); ?>
	</div>
	<div class="clear"></clear>
	
	<hr />
    <div class="myReviewsPage_advancedSettingsContainer myReviewPageCSS">
	  <?php
	     $wp_plugin_url = $_SERVER["REQUEST_URI"];
	     $wp_plugin_url = substr( $wp_plugin_url, 0, strrpos( $wp_plugin_url, '?') )."?page=myreviewspage";
         $output = (myreviewspage_url_get("http://www.myreviewspage.com/wp-plugin-support.php?v=2"
             ."&wpsite=".myreviewspage_base64UrlEncode(get_bloginfo("url"))
             ."&edit_page=".myreviewspage_base64UrlEncode($wp_plugin_url)));
         $output = str_replace($wp_plugin_url."/images","http://www.myreviewspage.com/images",$output);
         $output = str_replace($wp_plugin_url."/\"",$wp_plugin_url."\"",$output);
         echo $output;
	  ?>
    </div><!--advancedSettingsContainer-->
</div><!--wrap-->
	

