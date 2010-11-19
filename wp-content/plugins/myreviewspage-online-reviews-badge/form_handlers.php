<?php
/**
 * Check for and update form data from the options page.
 *
 * @package WordPress
 * @subpackage myReviewsPage
 * @since myReviewsPage 1.0
 */
 
 // quick helper function:
 function myreviewspage_updateOption($name){
   // does nothing if it already exists:
   add_option('myreviewspage_'.$name,'');
   // if it's set, add it:
   if(isset($_POST[$name])){
	  update_option('myreviewspage_'.$name, $_POST[$name]); 
   }
 }

 // our options:
 myreviewspage_updateOption('bizname');	
 myreviewspage_updateOption('phone');	
 myreviewspage_updateOption('address');	
 myreviewspage_updateOption('bizemail');	 
 
 // if phone is being updated, check if we are going to hide the link:
 if(isset($_POST["phone"])){
     if(isset($_POST["hide_poweredlink"])){
          update_option('myreviewspage_hide_poweredlink', "true"); 
     } else {
          update_option('myreviewspage_hide_poweredlink', false); 
     }
 }
 
?>

