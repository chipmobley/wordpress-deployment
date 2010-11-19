<?php
/*
Plugin Name: Quick Page/Post Redirect
Plugin URI: http://fischercreativemedia.com/wordpress-plugins/quick-pagepost-redirct-plugin/
Description: Redirect Pages or Posts to another location quickly. Adds a redirect box to the page or post edit page where you can specify the redirect Location and type which can be to another WordPress page/post or an external URL. Additional 301 Redirects can also be added for non-existant posts or pages - helpful for sites converted to WordPress. Version 1.9 and up allows for redirects to open in a new window as well as giving you the ability to add the rel=nofollow to redirected links.
Author: Don Fischer
Author URI: http://www.fischercreativemedia.com/
Version: 3.1

Version info:
3.1 - Re-issue of 2.1 for immediate fix of issue with the 3.0 version.(6/21/2010)
3.0 - Enhance Filter to reduce number of DB calls. (06/20/10)
2.1 - Fix Bug - Open in New Window would not work unless Show Link URL was also selected. (3/12/2010)
	- Fix Bug - Add rel=nofollow would not work if Open in a New Window was not selected. (3/13/2010)
	- Fix Bug - Show Link, Add nofollow and Open in New Window would still work when redirect not active. (3/13/2010)
	- Added new preg_match_all and preg_replace calls to add target and nofollow links - more effecient and accuarte - noticed some cases where old funtion would add the items if a redirect link had the same URL. (3/13/2010) 
2.0 - Code Cleanup. (2/28/2010)
    - Fix WARNING and NOTICE messages that some people may have been receiving about objects and undefined variables. (2/28/2010)
1.9 - Add 'Open in New Window' Feature. (2/20/2010)
	- Add 'rel=nofollow' option for links that will redirect. (2/20/2010)
	- Add 'rewrite url/permalink' option to hide the regular link and replace it with the new re-write link. (2/20/2010)
	- Hide Custom Field Meta Data to clean up the custom fields.
1.8 - Added a new 301 Redirect Page to allow adding of additional redirects that do not have Pages or Posts created for them. Based on Scott Nelle's Simple 301 Redirects plugin.(12/28/2009)
1.7 - Small fix to correct the Meta Redirect - moved "exit" command to end of "addmetatohead_theme" function. And also fix Page redirect. (9/8/2009)
1.6.1 - Small fix to correct the same problem as 1.6 for Category and Archive pages (9/1/2009) 
1.6 - Fix wrongful redirect when the first blog post on home page (main blog page) has a redirect set up - this was redirecting the 
	  entire page incorrectly. (9/1/2009)
1.5 - Re-Write plugin core function to hook WP at a later time to take advantage of the POST function - no sense re-creating the wheel. 
	  Can have page/post as draft and still redirect - but ONLY after the post/page has first been published and 
	  then re-saved as draft (this will hopefully be a fix for a later version). (8/31/2009)
1.4 - Add exit after header redirect function - needed on some servers and browsers. (8/19/2009)
1.3 - Add Meta Re-fresh option (7/26/2009)
1.2 - Add easy Post/Page Edit Box (7/25/2009)
1.1 - Fix redirect for off site links (7/7/2009)
1.0 - Plugin Release (7/1/2009)

    Copyright (C) 2009/2010 Donald J. Fischer

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//=======================================
// Redirect Class (for non oage post redirects).
// Original Simple 301 Redirects Class created by Scott Nelle (http://www.scottnelle.com/)
//=======================================
	if (!class_exists("quick_page_post_redirects")) {
		class quick_page_post_redirects {
		
			//generate the link to the options page under settings
			function create_menu(){
			  add_options_page('Quick Redirects', 'Quick Redirects', 10, 'redirects-options', array($this,'options_page'));
			}
			
			//generate the options page in the wordpress admin
			function options_page(){
			?>
			<div class="wrap">
			<h2>Quick 301 Redirects</h2>
			<br/>This section is useful if you have links from an old site and need to have them redirect to a new location on the current site, or if you have an existing URL that you need to send some place else and you don't want to have a Page or Post created to use the other Page/Post Redirect option.
			<br/>
			<br/>To add additional 301 redirects, put the URL you want to redirect in the Request field and the Place you want it to redirect to, in the Destination field.
			<br/>To delete a redirect, empty both fields and save the changes.
			<br/>
			<br/><b>PLEASE NOTE:</b> The <b>Request</b> field MUST be relative to the ROOT directory and contain the <code>/</code> at the beginning. The <b>Destination</b> field can be any valid URL or relative path (from root).<br/><br/>
			<form method="post" action="options-general.php?page=redirects-options">
			<table>
				<tr>
					<th align="left">Request</th>
					<th align="left">Destination</th>
				</tr>
				<tr>
					<td><small>example: <code>/about.htm</code> or <code>/test-directory/landing-zone/</code></small></td>
					<td><small>example: <code><?php echo get_option('home'); ?>/about/</code> or  <code><?php echo get_option('home'); ?>/landing/</code></small></td>
				</tr>
				<?php echo $this->expand_redirects(); ?>
				<tr>
					<td><input type="text" name="quickppr_redirects[request][]" value="" style="width:35em" />&nbsp;&raquo;&nbsp;</td>
					<td><input type="text" name="quickppr_redirects[destination][]" value="" style="width:35em;" /></td>
				</tr>
			</table>
			
			<p class="submit">
			<input type="submit" name="submit_301" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
			</form>
			</div>
			<?php
			} // end of function options_page
			
			//utility function to return the current list of redirects as form fields
			function expand_redirects(){
				$redirects = get_option('quickppr_redirects');
				$output = '';
				if (!empty($redirects)) {
					foreach ($redirects as $request => $destination) {
						$output .= '
						<tr>
							<td><input type="text" name="quickppr_redirects[request][]" value="'.$request.'" style="width:35em" />&nbsp;&raquo;&nbsp;</td>
							<td><input type="text" name="quickppr_redirects[destination][]" value="'.$destination.'" style="width:35em;" /></td>
						</tr>
						';
					}
				} // end if
				return $output;
			}
			
			//save the redirects from the options page to the database
			function save_redirects($data){
				$redirects = array();
				
				for($i = 0; $i < sizeof($data['request']); ++$i) {
					$request = trim($data['request'][$i]);
					$destination = trim($data['destination'][$i]);
				
					if ($request == '' && $destination == '') { continue; }
					else { $redirects[$request] = $destination; }
				}
	
				update_option('quickppr_redirects', $redirects);
			}
			
			//Read the list of redirects and if the current page is found in the list, send the visitor on her way
			function redirect(){
				// this is what the user asked for
				$userrequest = str_replace(get_option('home'),'',$this->getAddress());
				$userrequest = rtrim($userrequest,'/');
				
				$redirects = get_option('quickppr_redirects');
				if (!empty($redirects)) {
					foreach ($redirects as $storedrequest => $destination) {
						// compare user request to each 301 stored in the db
						if($userrequest == rtrim($storedrequest,'/')) {
							header ('HTTP/1.1 301 Moved Permanently');
							header ('Location: ' . $destination);
							exit();
						}
						else { unset($redirects); }
					}
				}
			} // end funcion redirect
			
			//utility function to get the full address of the current request - credit: http://www.phpro.org/examples/Get-Full-URL.html
			function getAddress(){
				if(!isset($_SERVER['HTTPS'])){$_SERVER['HTTPS']='';}
				$protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http'; //check for https
				return $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; //return the full address
			}
			
		} // end class quick_page_post_redirects
	} // end check for existance of class

// Call the 301 class (for non-existant 301 redirects)
	$redirect_plugin = new quick_page_post_redirects();

// Actions
//----------
	add_action('admin_menu', 'add_edit_box_ppr');
	add_action('save_post', 'ppr_save_postdata', 1, 2); // save the custom fields
	//add_action('wp','ppr_do_redirect', 1, 2);
	add_action('template_redirect','ppr_do_redirect', 1, 2);
	add_action('init', 'ppr_init_metaclean', 1);
	//add_action( 'template_redirect', 'ppr_redirecto');
	add_filter('page_link','ppr_filter_pages',20, 2 );
	add_filter('post_link','ppr_filter_pages',20, 2 );
	add_filter('wp_list_pages','ppr_fix_targetsandrels', 9);
	
	if (isset($redirect_plugin)) {
		add_action('init', array($redirect_plugin,'redirect'), 1); // add the redirect action, high priority
		add_action('admin_menu', array($redirect_plugin,'create_menu')); // create the menu
		if (isset($_POST['submit_301'])) {$redirect_plugin->save_redirects($_POST['quickppr_redirects']);} //if submitted, process the data
	}


// Variables
//----------
	global $wpdb;
	global $wp_query;
	
// Functions
//----------
	// For WordPress < 2.8 function compatibility
	if (!function_exists('esc_attr')) {
		function esc_attr($attr){return attribute_escape( $attr );}
		function esc_url($url){return clean_url( $url );}
	}
	
	// used to get the meta_value and ID of a post with specific meta_key.
	function ppr_get_metavalfromkey($key) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key=%s and post_id in (SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_pprredirect_active')", $key ) );
	}	

	//clean up the Custom Fields to make it prettier on the Edit Page
	function ppr_init_metaclean() {
		$thepprversion = get_option('ppr_version');
		if ($thepprversion=='3.1') {
			//nothing;
		}else if ($thepprversion == '1.9' || $thepprversion == '2.0' || $thepprversion == '2.1' || $thepprversion == '3.0') {
			update_option( 'ppr_version', '3.1' );
		}else{
			global $wpdb;
			//want to make sure older version upgrading still 
				$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = '_pprredirect_active' WHERE meta_key = 'pprredirect_active'" );
				$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = '_pprredirect_newwindow' WHERE meta_key = 'pprredirect_newwindow'" );
				$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = '_pprredirect_relnofollow' WHERE meta_key = 'pprredirect_relnofollow'" );
				$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = '_pprredirect_type' WHERE meta_key = 'pprredirect_type'" );
				$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = '_pprredirect_url' WHERE meta_key = 'pprredirect_url'" );
			wp_cache_flush();
			//end remove
			update_option( 'ppr_version', '3.1' );
		}
	}
	
	function ppr_redirectto() {//alternate redirect function - old version
		global $wp_query;
		if (!is_single()&&!is_page()){
			return;
		}
		$link = get_post_meta($wp_query->post->ID, '_pprredirect_url', true);
		if(!$link){
			return;
		}
		$redirect_type = get_post_meta( $wp_query->post->ID, '_pprredirect_type', true );
		$redirect_type = ( $redirect_type = '302' ) ? '302' : '301';
		wp_redirect( $link, $redirect_type );
		exit;
	}
	
	function ppr_filter_pages ($link, $post) {
		$ppr_cache = ppr_linktometa();
		if(isset($post->ID)){	
			$id = $post->ID;
		}else{
			$id = $post;
		}
		$ppr_rewrite = get_post_meta($id,'_pprredirect_rewritelink',true);
		if (isset($ppr_cache[$id]) ){
			if($ppr_rewrite==='1'){ //only change the link if they want - otherwise, we will redirect the old way.
				if(strpos($ppr_cache[$id],get_bloginfo('url'))>=0 || strpos($ppr_cache[$id],'www.')>=0 || strpos($ppr_cache[$id],'http://')>=0 || strpos($ppr_cache[$id],'https://')>=0){
					$link = esc_url( $ppr_cache[$id] );
				}else{
					$link = esc_url( get_bloginfo('url').'/'. $ppr_cache[$id] );
				}
			}
		}
		return $link;
	}

	function ppr_linktometa() {
		global $wpdb, $ppr_cache, $blog_id;
		if(isset($ppr_cache[$blog_id])){
			return $ppr_cache[$blog_id];
		}else{
			$links_to = ppr_get_metavalfromkey('_pprredirect_url');
			if(count($links_to)>0){
				foreach((array) $links_to as $link){
					//if(get_post_meta($link->post_id,'_pprredirect_active',true)!=''){
						$ppr_cache[$blog_id][$link->post_id] = $link->meta_value;
					//}
				}
				return $ppr_cache[$blog_id];
			}else{
				$ppr_cache[$blog_id] = false;
				return false;
			}
		}
	}
	
	function ppr_linktotarget () {
		global $wpdb, $ppr_target, $blog_id;
		if (!isset( $ppr_target[$blog_id])){
			$links_to = ppr_get_metavalfromkey('_pprredirect_newwindow');
		}else{
			return $ppr_target[$blog_id];
		}
		if (!$links_to) {
			$ppr_target[$blog_id] = false;
			return false;
		}
		foreach ((array) $links_to as $link){
			$ppr_target[$blog_id][$link->post_id] = $link->meta_value;
		}
		return $ppr_target[$blog_id];
	}
	
	function ppr_linktonorel () {
		global $wpdb, $ppr_norel, $blog_id;
		if (!isset($ppr_norel[$blog_id])){
			$links_to = ppr_get_metavalfromkey('_pprredirect_relnofollow');
		}else{
			return $ppr_norel[$blog_id];
		}
		if (!$links_to) {
			$ppr_norel[$blog_id] = false;
			return false;
		}
		foreach((array) $links_to as $link){
			$ppr_norel[$blog_id][$link->post_id] = $link->meta_value;
		}
		//print_r($ppr_norel[$blog_id]);
		return $ppr_norel[$blog_id];
	}
	
	function addmetatohead_theme(){
		global $ppr_url;
		$meta_code = '<meta http-equiv="refresh" content="0; URL='.$ppr_url.'" />';
		echo $meta_code;
		exit;//stop loading page so meta can do it's job without rest of page loading.
	}

	function ppr_do_redirect(){
		global $post,$wp_query,$ppr_url,$ppr_active,$ppr_url,$ppr_type;
		if($wp_query->is_single || $wp_query->is_page ):
			$thisidis_current= $post->ID;
			$ppr_active	= get_post_meta($thisidis_current,'_pprredirect_active',true);
			$ppr_url	= get_post_meta($thisidis_current,'_pprredirect_url',true);
			$ppr_type	= get_post_meta($thisidis_current,'_pprredirect_type',true);
			
			if( $ppr_active==1 && $ppr_url!='' ):
				if($ppr_type===0){$ppr_type='200';}
				if($ppr_type===''){$ppr_type='302';}
					if($ppr_type=='meta'):
						//metaredirect
						add_action('wp_head', "addmetatohead_theme",1);
					elseif($ppr_url!=''):
						//check for http:// - as full url - then we can just redirect if it is //
						if( strpos($ppr_url, 'http://')=== 0 || strpos($ppr_url, 'https://')=== 0){
							$offsite=$ppr_url;
							header('Status: '.$ppr_type);
							header('Location: '.$offsite, true, $ppr_type);
							exit; //stop loading page
						}elseif(strpos($ppr_url, 'www')=== 0){ // check if they have full url but did not put http://
							$offsite='http://'.$ppr_url;
							header("Status: $ppr_type");
							header("Location: $offsite", true, $ppr_type);
							exit; //stop loading page
						}elseif(is_numeric($ppr_url)){ // page/post number
							if($ppr_postpage=='page'){ //page
								$onsite=get_bloginfo('url').'/?page_id='.$ppr_url;
								header("Status: $ppr_type");
								header("Location: $onsite", true, $ppr_type);
								exit; //stop loading page
							}else{ //post
								$onsite=get_bloginfo('url').'/?p='.$ppr_url;
								header("Status: $ppr_type");
								header("Location: $onsite", true, $ppr_type);
								exit; //stop loading page
							}
						//check permalink or local page redirect
						}else{	// we assume they are using the permalink / page name??
							$onsite=get_bloginfo('url'). $ppr_url;
							header("Location: $onsite", true, $ppr_type);
							exit; //stop loading page
						}
						
					endif;
			endif;
		endif;
	}
	
	
//=======================================
// Add options to post/page edit pages
//=======================================
	// Adds a custom section to the Post and Page edit screens
	function add_edit_box_ppr() {
		if( function_exists( 'add_meta_box' )) {
			add_meta_box( 'edit-box-ppr', __( 'Quick Page/Post Redirect', 'ppr_plugin' ), 'edit_box_ppr_1', 'page', 'normal', 'high' ); //for page
			add_meta_box( 'edit-box-ppr', __( 'Quick Page/Post Redirect', 'ppr_plugin' ), 'edit_box_ppr_1', 'post', 'normal', 'high' ); //for post
		}
	}

	// Prints the inner fields for the custom post/page section 
	function edit_box_ppr_1() {
		global $post;
		$ppr_option1='';
		$ppr_option2='';
		$ppr_option3='';
		$ppr_option4='';
		$ppr_option5='';
		// Use nonce for verification ... ONLY USE ONCE!
		wp_nonce_field( 'pprredirect_noncename', 'pprredirect_noncename', false, true );

		// The actual fields for data entry
		echo '<label for="pprredirect_active" style="padding:2px 0;"><input type="checkbox" name="pprredirect_active" value="1" '. checked('1',get_post_meta($post->ID,'_pprredirect_active',true),0).' />'. __(" Make Redirect <b>Active</b>. (check to turn on)", 'ppr_plugin' ) . '</label><br />';
		echo '<label for="pprredirect_newwindow" style="padding:2px 0;"><input type="checkbox" name="pprredirect_newwindow" id="pprredirect_newwindow" value="_blank" '. checked('_blank',get_post_meta($post->ID,'_pprredirect_newwindow',true),0).'>'. __(" Open redirect link in a <b>new window</b>. <span style=\"color:#800000;\"><i>NEW in version 1.9</i></span>", 'ppr_plugin' ) . '</label><br />';
		echo '<label for="pprredirect_relnofollow" style="padding:2px 0;"><input type="checkbox" name="pprredirect_relnofollow" id="pprredirect_relnofollow" value="1" '. checked('1',get_post_meta($post->ID,'_pprredirect_relnofollow',true),0).'>'. __(" Add <b>rel=\"nofollow\"</b> to redirect link. <span style=\"color:#800000;\"><i>NEW in version 1.9</i></span>", 'ppr_plugin' ) . '</label><br />';
		echo '<label for="pprredirect_rewritelink" style="padding:2px 0;"><input type="checkbox" name="pprredirect_rewritelink" id="pprredirect_rewritelink" value="1" '. checked('1',get_post_meta($post->ID,'_pprredirect_rewritelink',true),0).'>'. __(" <b>Show</b> the Redirect URL below in the link instead of this page URL. <b>NOTE: Use <u>FULL</u> URL below!</b>  <span style=\"color:#800000;\"><i>NEW in version 1.9</i></span>", 'ppr_plugin' ) . '</label><br /><br />';
		echo '<label for="pprredirect_url">' . __(" <b>Redirect URL:</b>", 'ppr_plugin' ) . '</label><br />';
		if(get_post_meta($post->ID, '_pprredirect_url', true)!=''){$pprredirecturl=get_post_meta($post->ID, '_pprredirect_url', true);}else{$pprredirecturl="";}
		echo '<input type="text" style="width:75%;margin-top:2px;margin-bottom:2px;" name="pprredirect_url" value="'.$pprredirecturl.'" /><br />(i.e., <code>http://example.com</code> or <code>/somepage/</code> or <code>p=15</code> or <code>155</code>. Use <b>FULL URL</b> <i>including</i> <code>http://</code> for all external <i>and</i> meta redirects. )<br /><br />';
	
		echo '<label for="pprredirect_type">' . __(" Type of Redirect:", 'ppr_plugin' ) . '</label> ';
		if(get_post_meta($post->ID, '_pprredirect_type', true)!=''){$pprredirecttype=get_post_meta($post->ID, '_pprredirect_type', true);}else{$pprredirecttype="";}
		switch($pprredirecttype):
			case "":
				$ppr_option2=" selected";//default
				break;
			case "301":
				$ppr_option1=" selected";
				break;
			case "302":
				$ppr_option2=" selected";
				break;
			case "307":
				$ppr_option3=" selected";
				break;
			case "meta":
				$ppr_option5=" selected";
				break;
		endswitch;
		
		echo '<select style="margin-top:2px;margin-bottom:2px;width:40%;" name="pprredirect_type"><option value="301" '.$ppr_option1.'>301 Permanent</option><option value="302" '.$ppr_option2.'>302 Temporary</option><option value="307" '.$ppr_option3.'>307 Temporary</option><option value="meta" '.$ppr_option5.'>Meta Redirect</option></select> (Default is 302)<br /><br />';
		echo '<b>NOTE:</b> For This Option to work, the page or post needs to be published for the redirect to happen <i><b>UNLESS</b></i> you publish it first, then save it as a Draft. If you want to add a redirect without adding a page/post or having it published, use the <a href="./options-general.php?page=redirects-options">Quick Redirects</a> method.';
	}
	
	// When the post is saved, saves our custom data
	function ppr_save_postdata($post_id, $post) {
	
		if(isset($_REQUEST['pprredirect_noncename']) && (isset($_POST['pprredirect_active']) || isset($_POST['pprredirect_url']) || isset($_POST['pprredirect_type']) || isset($_POST['pprredirect_newwindow']) || isset($_POST['pprredirect_relnofollow']))):
			// verify authorization
			if(isset($_POST['pprredirect_noncename'])){
				//if ( !wp_verify_nonce( $_POST['pprredirect_noncename'], plugin_basename(__FILE__) )) {
				if ( !wp_verify_nonce( $_REQUEST['pprredirect_noncename'], 'pprredirect_noncename' )) {
					return $post->ID;
				}
			}
		
			// check allowed to editing
			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post->ID ))
					return $post->ID;
			} else {
				if ( !current_user_can( 'edit_post', $post->ID ))
					return $post->ID;
			}
		
			// find & save the form data & put it into an array
			$mydata['_pprredirect_active'] 		= $_POST['pprredirect_active'];
			$mydata['_pprredirect_newwindow'] 	= $_POST['pprredirect_newwindow'];
			$mydata['_pprredirect_relnofollow'] = $_POST['pprredirect_relnofollow'];
			$mydata['_pprredirect_type'] 		= $_POST['pprredirect_type'];
			$mydata['_pprredirect_rewritelink'] = $_POST['pprredirect_rewritelink'];
			$mydata['_pprredirect_url']    		= stripslashes( $_POST['pprredirect_url']);
			
			if ( 0 === strpos($mydata['_pprredirect_url'], 'www.'))
				$mydata['_pprredirect_url'] = 'http://' . $mydata['_pprredirect_url'] ; // Starts with www., so add http://

			if($mydata['_pprredirect_url'] === ''){
				$mydata['_pprredirect_type'] = NULL; //clear Type if no URL is set.
				$mydata['_pprredirect_active'] = NULL; //turn it off if no URL is set
			}
			// Add values of $mydata as custom fields
			foreach ($mydata as $key => $value) { //Let's cycle through the $mydata array!
				if( $post->post_type == 'revision' ) return; //don't store custom data twice
				$value = implode(',', (array)$value); //if $value is an array, make it a CSV (unlikely)
				
				if(get_post_meta($post->ID, $key, FALSE)) { //if the custom field already has a value
					update_post_meta($post->ID, $key, $value);
				} else { //if the custom field doesn't have a value
					add_post_meta($post->ID, $key, $value);
				}
				
				if(!$value) delete_post_meta($post->ID, $key); //delete if blank
			}
		endif;
	}
	
	function ppr_fix_targetsandrels($pages) {
	global $post;
		$ppr_cache 	= ppr_linktometa();
		$ppr_target = ppr_linktotarget();
		$ppr_norel 	= ppr_linktonorel();
		if (!$ppr_cache && !$ppr_target && !$ppr_rel){
			return $pages;
		}
		
		$this_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$targets = array();
		$norels = array();
		
		foreach ((array) $ppr_cache as $id => $page ) {
			if (isset( $ppr_target[$id])){
				$targets[$id] = $ppr_cache[$id];
			}
			if (isset( $ppr_norel[$id])){
				$norels[$id] = $ppr_cache[$id];
			}
		}

		if(count($norels)>=1) {
			foreach($norels as $relid => $rel){
			$validexp="/page-item-".$relid."\"><a(?:.*)rel=\"nofollow\"(?:.*?)>/i";
			$found = preg_match_all($validexp, $pages, $matches);
				//if(strpos($pages,'page-item-'.$relid.'">a href="'.$rel.'" rel="nofollow" ')>0) {
				if($found!=0){
					$pages = $pages; //do nothing 'cause it is already a rel=nofollow.
				}else{
					$pages = preg_replace('/page-item-'.$relid.'"><a(.*?)>/i', 'page-item-'.$relid.'"><a\1 rel="nofollow">', $pages);
					//$pages = str_replace(' href="'.$rel.'"',' href="'.$rel.'" rel="nofollow" ', $pages); //add no follow.
				}
			}
		}
		
		if(count($targets)>=1) {
			foreach($targets as $p => $t){
				$p = esc_attr($p);
				$t = esc_url($t);
				$validexp="/page-item-".$p."\"><a(?:.*)target=\"_blank\"(?:.*?)>/i";
				$found = preg_match_all($validexp, $pages, $matches);
				//if(strpos($pages,' href="'.$t.'" target="'.$p.'" ')>0){
					//$pages = $pages; //already has a target
				if($found!=0){
					$pages = $pages; //do nothing 'cause it is already a rel=nofollow.
				}else{
					$pages = preg_replace('/page-item-'.$p.'"><a(.*?)>/i', 'page-item-'.$p.'"><a\1 target="_blank">', $pages);
					//$pages = str_replace(' href="'.$p.'" ',' href="'.$p.'" target="'.$t.'" ', $pages);
				}
			}
		}
	
		return $pages;
	}
?>