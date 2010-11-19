<?php
/**
 * Shortcodes for myreviewspage
 *
 * @package WordPress
 * @subpackage myReviewsPage
 * @since myReviewsPage 1.0
 */
 
// Shortcode handler. Called directly on the options page for testing.
// todo: actually use border and sites abilities.
function myreviewspage_func($atts) {
    $output = "";
	extract(shortcode_atts(array(
		'border' => 'true',
		'sites' => 'all',
		'type'  => 'dashboard',
		'update'=> 'false',
		'admin' => 'false',
		'settings_page' => ''
	), $atts));	
	if(get_option('myreviewspage_phone') || get_option('myreviewspage_bizname')){
	    $optStr = "&wpsite=".myreviewspage_base64UrlEncode((get_bloginfo("url")));
	    $optStr .= "&type=".$type;
	    if($type == "dashboard"){
	        $optStr .= "&settings_page=".myreviewspage_base64UrlEncode($settings_page);
	    }
	    if($update == "true"){
	       $optStr .= "&bizemail=".get_option('myreviewspage_bizemail'); // even if it doesn't exist
   	       $optStr .= "&update=1";
            foreach ($_POST as $key => $value){
                $optStr .= "&".$key."=".myreviewspage_base64UrlEncode($value);
            }            
	    }
	    $output = myreviewspage_url_get("http://www.myreviewspage.com/wp-plugin-support.php?v=2.2c".$optStr);
	    //$output = myreviewspage_url_get("http://localhost/don-review/wp-plugin-support.php?v=2.2bc".$optStr);
	} else {
	    $output = "";
	} 
	// currently unused script caching option:
	if(get_option("myreviewspage_scriptcache")){
	    $output = str_replace("<script",get_option("myreviewspage_scriptcache")."<script",$output);
	}
    	
    $label ='<div class="myReviewsPage_label">Online Reviews';
    if(get_option('myreviewspage_bizname')){ 
        $label .= " For ".get_option('myreviewspage_bizname'); 
    }
    $label .= '</div>';
    if($admin == "true"){
	  $output ='<div class="myReviewPageCSS">'.$label.
	           "<div class='myReviewPage_dashboard pageCol'>"
	          .$output
	          ."<div class='myReviewPage_clickHereForSettings'>"
	          ."Reviews not displaying? <br />"
	          ."<a href='".$settings_page."'>Click here</a> for settings."
	          ."</div>"
	          ."</div>".'</div>';
    } else {
      $badgeHTML = "";
      if(!get_option('myreviewspage_hide_poweredlink')){
          $badgeHTML = myReviewsPage_poweredByBadge();
      }
	  $output =$label.
	           "<div class='myReviewPage_dashboard pageCol'>"
	          .$output
	          ."<div class='myReviewPage_thanksForVisiting'>"
	          ."Thanks for visiting. Click any of the links above to see our online reviews, or to leave us a review!"
	          ."</div>"
	          .$badgeHTML
	          ."</div>";
	}
	//$output = str_replace('/edit/', $_SERVER["REQUEST_URI"]."&settings=1", $output);
	return '<div class="myReviewPageCSS">'.$output.'</div>';
}

?>
