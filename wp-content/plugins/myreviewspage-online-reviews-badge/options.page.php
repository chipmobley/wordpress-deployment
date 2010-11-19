<?php
/**
 * The options page in the admin settings.
 *
 * @package WordPress
 * @subpackage myReviewsPage
 * @since myReviewsPage 1.2
 */ 
?>
<div class="wrap myReviewPageCSS">

	<h2>myReviewsPage Options</h2>

	<div class="myReviewsPage_options_subheader">
	  <p class="myReviewsPage_options_subheader_links">
	    Click here for <a href="http://www.myreviewspage.com/blog/online-reviews-videos" target="_blank">Help and Tutorials</a>
	  </p>
	  <div class="myReviewsPage_poweredByBadge" onmouseup="javascript:window.open('http://www.myreviewspage.com/');">
	      powered by
	      <div class="myReviewsPage_badgeLink">
	          <span class="myReviewsPage_red">myReviews</span><span class="myReviewsPage_black">Page.com</span>
	      </div>
	  </div>
	</div>
	<div class="clear"></clear>
	
	<hr />
	
	<p>
	    Enter your business phone number and we'll try to find your online reviews and create your badge. 
	    If we can't find your profile for one of the review sites, you can override them by providing the 
	    direct URL in the <a href="<?php
	        echo $_SERVER["REQUEST_URI"]."&settings=1";
	    ?>">Settings</a>.
	</p>
    
	<div id="myReviewsPage_options_cols">
	    <div id="myReviewsPage_options_leftcol" class="myReviewsPage_options_col">
	      <form enctype="multipart/form-data" action="" method="post">
            <p>
            <!-- Use a table since we have a dynamic width (for the button at the end to center):-->
                <table>
                  <tr>
                    <td class="left">
                      <label for="phone" class="myReviewsPage_label">
                        Business Phone
                      </label>
                    </td>
                    <td class="right">
                        <input type="text" name="phone" 
                         value="<?php echo get_option('myreviewspage_phone'); ?>" />
                    </td>
                  </tr>
                  <tr>
                    <td class="left">
                      <label for="bizname" class="myReviewsPage_label">
                        Business Name
                      </label>
                    </td>
                    <td class="right">
                        <input type="text" name="bizname" 
                         value="<?php echo get_option('myreviewspage_bizname'); ?>" />
                    </td>
                  </tr>      
                  <tr>
                    <td colspan="2">
                        <br />Enter your email address below to recieve an email <br />alert whenever you get a new review (optional)
                    </td>
                  </tr>
                  <tr>
                    <td class="left">
                      <label for="bizemail" class="myReviewsPage_label">
                        Business Email
                      </label>
                    </td>
                    <td class="right">
                        <input type="text" name="bizemail" 
                         value="<?php echo get_option('myreviewspage_bizemail'); ?>" />
                    </td>
                  </tr>      
                  <tr>
                    <td colspan="2">
                        <div> <br />
                            <input type="checkbox" name="hide_poweredlink" 
                            <?php
                            if(get_option("myreviewspage_hide_poweredlink")){
                                echo "checked='checked'";
                            }
                            ?>
                            />
                            Turn off "powered by myReviewsPage" on badge <br />
                            (but we'd appreciate it if you would help us spread the word!)
                        </div>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                        <div class="myReviewsPage_createBadgeButtonDiv">
                            <input type="submit" id="myReviewsPage_createBadgeButton" value="Create Badge" />
                        </div>                    
                        
                        <div class="myReviewsPage_addShortcodeText">
                            Add this shortcode to any page or post to display your Online Reviews:            
                        </div>
                        <div class="myReviewsPage_addShortcodeShortcode">
                            [myreviewspage]            
                        </div>
                    </td>
                  </tr>
                </table>	
            </p>
          </form>
        </div><!--myReviewsPage_options_leftcol-->


	    <div id="myReviewsPage_options_rightcol" class="myReviewsPage_options_col">
	        <?php 
  	         $opts = array('update'=>'true','type'=>'dashboard', 'admin'=>"true", 'settings_page'=>($_SERVER["REQUEST_URI"].'&settings=1'));
	         $badgeHTML =  myreviewspage_func($opts); 
	         if($badgeHTML){
	        ?>
              <?php echo $badgeHTML; ?>
	        <?php } else { ?>
	            
	        <?php } ?>    
        </div><!--myReviewsPage_options_rightcol-->

        
        </div><!--myReviewsPage_options_cols-->
    <div class="clear"></div>
</div><!--wrap-->

