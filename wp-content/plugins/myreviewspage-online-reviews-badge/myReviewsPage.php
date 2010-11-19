<?php
/*
Plugin Name: MyReviewsPage
Plugin URI: http://www.myreviewspage.com/blog/wordpress-plugin
Description: Display your online reviews in WordPress.
Author: Don Campbell, Ricky Brent
Version: 1.3f
Author URI: http://www.expand2web.com/blog/
*/ 

add_action("admin_menu", "myreviewspage_on_admin_menu");
add_action('admin_head', 'myreviewspage_on_admin_head');
add_action('wp_head',    'myreviewspage_on_wp_head');

// On admin menu, add this page:
function myreviewspage_on_admin_menu()
{
	//add_theme_page(__('myReviewsPage Options'), __('myReviewsPage'), 'switch_themes', 'myreviewspage', 'myreviewspage_plugin_option_page','http://www.myreviewspage.com/favicon.ico',10);
	if(function_exists("add_plugins_page")){
	    add_plugins_page(__('myReviewsPage Options'), __('myReviewsPage'), 'switch_themes', 'myreviewspage', 'myreviewspage_plugin_option_page','http://www.myreviewspage.com/favicon.ico',10);
	} else {
	    add_submenu_page('plugins.php', __('myReviewsPage Options'), __('myReviewsPage'), 'switch_themes', 'myreviewspage','myreviewspage_plugin_option_page');
	}
}

// On admin, add this to the head (CSS and Javascript?):
function myreviewspage_on_admin_head()
{?>
  <link rel="stylesheet" href="http://www.myreviewspage.com/styles/default25jul2010.css?v=1.3f" type="text/css" media="screen" />  
  <link href="<?php echo myreviewspage_get_url(); ?>/page.css" rel="stylesheet" type="text/css" media="screen,print" />
  <link href="<?php echo myreviewspage_get_url(); ?>/admin.css" rel="stylesheet" type="text/css" media="screen,print" />    
  <?php 
}

// On non-admin head, add this to the head (CSS and Javascript?):
function myreviewspage_on_wp_head()
{
  ?>
  <link rel="stylesheet" href="http://www.myreviewspage.com/styles/default25jul2010.css?v=1.3f" type="text/css" media="screen,print" />  
  <link href="<?php echo myreviewspage_get_url(); ?>/page.css?v=1.3f" rel="stylesheet" type="text/css" media="screen,print" />
  <?php 
}

function myReviewsPage_poweredByBadge(){    
    $output =
	 '<div class="myReviewsPage_poweredByBadge" onmouseup="javascript:window.open(\'http://www.myreviewspage.com/\');">
	      powered by
	      <div class="myReviewsPage_badgeLink">
	          <span class="myReviewsPage_red">myReviews</span><span class="myReviewsPage_black">Page.com</span>
	      </div>
	  </div>
	';
	return $output;
}

// Helper function to get the URL for our files:
function myreviewspage_get_url(){
    $plugin_name = plugin_basename(__FILE__);
    $plugin_name = substr($plugin_name, 0, strpos($plugin_name,"myReviewsPage.php"));
    //	    $myreviewspage_url = plugins_url('myReviewsPage');
    // plugins_url does not exist in Wp2.x and has a bug if the directory name is different, so:
    $myreviewspage_url = get_bloginfo('template_url')."/../../plugins/".$plugin_name;
	
	return $myreviewspage_url;
}

function myreviewspage_plugin_option_page(){
	global $wpdb;

	include("form_handlers.php");
	
	if(isset($_POST["phone"])){
	    ?>
	    <div id="myReviewsFeedback">
	        Reviews settings updated.
	    </div>
	    <?	    
	}
	
	if(isset($_REQUEST["settings"])){
	    include("settings.page.php");
	} else {
	    include("options.page.php");
	}
}

// Helper function for getting URL
function myreviewspage_url_get($URL){
  $content = "";
  if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);     
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $content = curl_exec($ch);
    curl_close($ch);  
  } else {
    $content = file_get_contents($URL); 
  }
  return $content;
}

// Helper functions for encoding/decoding data:
function myreviewspage_get_external_url(){
  	if(get_option('myreviewspage_phone') || get_option('myreviewspage_bizname')){
  	    
  	}
}

function myreviewspage_base64UrlEncode($data)
{
  return strtr(rtrim(base64_encode($data), '='), '+/', '-_');
}

function myreviewspage_base64UrlDecode($base64)
{
  return base64_decode(strtr($base64, '-_', '+/'));
}


include("shortcodes.php");
add_shortcode('myreviewspage', 'myreviewspage_func');
?>
