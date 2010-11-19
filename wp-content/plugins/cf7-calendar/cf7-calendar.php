<?php
/*
Plugin Name: Contact Form 7 Calendar
Plugin URI: http://webwoke.com/wp-plugins/calendar-for-contact-form-7.html
Description: JavaScript Calendar for Content, Widget and also work for Contact Form 7. To use it just type [datetimepicker input-name-field] for outside contact form 7 and [cf7cal input-name-field] inside contact form 7 form with input-name-field Unique!
Author: Harry Sudana, I Nym
Version: 2.0.4
Author URI: http://webwoke.com/
*/

add_action('activate_cf7-calendar/cf7-calendar.php', 'cf7activate');
add_action('deactivate_cf7-calendar/cf7-calendar.php', 'cf7deactivate');
add_action('admin_menu', 'cf7registeradminsettingmenu');
add_action('wp_head', 'FEloadjs', 1002);
add_filter('the_content', 'page_text_filter', 1003 );
add_filter('widget_text', 'page_text_filter', 1004 );

	function cf7activate(){
		global $wpdb, $blog_id;
		$table = $wpdb->prefix."options";
		$query = "INSERT INTO $table (blog_id, option_name, option_value)
					VALUES (".$blog_id.",'webwoke_cf7calendar','calendar-en;calendar-tiger;%A, %B %e, %Y;false') ";
		$result = $wpdb->query( $query );
	}
	
	function cf7deactivate(){
		global $wpdb, $blog_id;
		$table = $wpdb->prefix."options";
		
		$query = " DELETE FROM $table WHERE blog_id=".$blog_id." AND option_name='webwoke_cf7calendar' ";
		$result = $wpdb->query( $query );
		
	}

	function cf7loadsetting(){
		global $wpdb, $blog_id;
		$table = $wpdb->prefix."options";
		return $wpdb->get_row( "SELECT * FROM $table WHERE blog_id=".$blog_id." AND option_name='webwoke_cf7calendar' ");
	}
	
	function cf7updatesetting($dataupdate){
		global $wpdb, $blog_id;
		$table = $wpdb->prefix."options";
		$query = " UPDATE $table SET option_value = '".$dataupdate[0].";".$dataupdate[1].";".$dataupdate[2].";".$dataupdate[3]."'  WHERE blog_id=".$blog_id." AND option_name='webwoke_cf7calendar' ";
		$result = $wpdb->query( $query );
	}
	
	function cf7registeradminsettingmenu(){
		if (function_exists('add_options_page')) {
			add_options_page('CF7 Calendar Setting','CF7 Calendar', 10, basename(__FILE__), 'cf7adminsettinghtml');
		}	
	}
	
	function cf7readdirlang(){
		if ($handle = opendir(ABSPATH.'/wp-content/plugins/'.plugin_basename(dirname(__FILE__)).'/lang/')) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$thefile[] = substr($file,0,strlen($file)-3);
				}
			}
			closedir($handle);
			return $thefile;
		}
	}
	function cf7adminsettinghtml(){
		if(isset($_POST['cf7save'])){
			$dataupdate = array($_POST['callang'], "calendar-".$_POST['theme'], $_POST['calformat'], $_POST['calshowtime']);
			cf7updatesetting($dataupdate);
		}
		
		$loadsetting = cf7loadsetting();
		$cf7setting = explode(";",$loadsetting->option_value);
		$cf7showtime = array('true','false');
		$cf7lang = cf7readdirlang();
		$cf7themes = array('aqua', 'blue', 'green', 'system', 'tas', 'tiger', 'win2k-1', 'win2k-2', 'win2k-cold-1', 'win2k-cold-2' );
		?>
        <div class="wrap"> 
            <h2>CF7 Calendar</h2> 
         
        <form method="post"> 
         
            <table class="widefat"> 
                <tbody> 
                    <tr> 
                    <td>
                    Select Theme
                    </td>
                    <td>
                    </td>
                    </tr>
                    <tr>
                    <td>&nbsp;
                    
                    </td>
                    <td>
                    <div style="width:98%">
                    <?php
					$icount=1;
					foreach($cf7themes as $cf7theme){
						if('calendar-'.$cf7theme==$cf7setting[1])
							$checked = "checked='checked'";
						else
							$checked = "";
					?>
                    <div style="float:left">
                    <img src="<?php echo get_option('siteurl') . '/wp-content/plugins/'.plugin_basename(dirname(__FILE__)) ?>/images/sc<?php echo $cf7theme; ?>.jpg" /><br />
                    <input name="theme" type="radio" value="<?php echo $cf7theme; ?>" <?php echo $checked; ?> /><label><?php echo $cf7theme; ?></label>
                    </div>
                    
                    <?php
						if($icount%3==0)
							echo "<br style='clear:both' /><br />";
						$icount=$icount+1;
					}
					?>
                    
                    </div>

                    </td>
                    </tr>
                    <tr> 
                    <td>
                    Select Languange
                    </td>
                    <td>
                    <select name="callang">
                    <?php
					foreach($cf7lang as $row){
						if($row==$cf7setting[0])
							$selected = "selected";
						else
							$selected = "";
						echo "<option value='".$row."' ".$selected." >".$row."</option>";
					}
					?>
                    </select>
                    </td>
                    </tr>
					<tr> 
                    <td>
                    Show Time
                    </td>
                    <td>
                    <select name="calshowtime">
                    <?php
					foreach($cf7showtime as $row){
						if($row==$cf7setting[3])
							$selected = "selected";
						else
							$selected = "";
						echo "<option value='".$row."' ".$selected." >".$row."</option>";
					}
					?>
                    </select>
                    </td>
                    </tr>
                    <tr> 
                    <td>
                    Calendar Format
                    </td>
                    <td>
                    <input name="calformat" id="calformat" type="text" value="<?php echo $cf7setting[2]; ?>" />
<p>
Legend :
<br />
%a	abbreviated weekday name<br />
%A	full weekday name<br />
%b	abbreviated month name<br />
%B	full month name<br />
%C	century number<br />
%d	the day of the month ( 00 .. 31 )<br />
%e	the day of the month ( 0 .. 31 )<br />
%H	hour ( 00 .. 23 )<br />
%I	hour ( 01 .. 12 )<br />
%j	day of the year ( 000 .. 366 )<br />
%k	hour ( 0 .. 23 )<br />
%l	hour ( 1 .. 12 )<br />
%m	month ( 01 .. 12 )<br />
%M	minute ( 00 .. 59 )<br />
%n	a newline character<br />
%p	``PM'' or ``AM''<br />
%P	``pm'' or ``am''<br />
%S	second ( 00 .. 59 )<br />
%s	number of seconds since Epoch (since Jan 01 1970 00:00:00 UTC)<br />
%t	a tab character<br />
%U, %W, %V	the week number<br />
%u	the day of the week ( 1 .. 7, 1 = MON )<br />
%w	the day of the week ( 0 .. 6, 0 = SUN )<br />
%y	year without the century ( 00 .. 99 )<br />
%Y	year including the century ( ex. 1979 )<br />
%% a literal % character<br />
</p>
                    </td>
                    </tr>
                    <tr> 
                    <td>&nbsp;
                    
                    </td>
                    <td>
                    <input name="cf7save" id="cf7save" type="submit" value="Save Setting" class="button" />
                    </td>
                    </tr>
                 </tbody>
            </table>
        </form>
        
    	<?php
	}
	
	function FEloadjs(){
		$loadsetting = cf7loadsetting();
		$cf7setting = explode(";",$loadsetting->option_value);
		$plugins_url = get_option('siteurl') . '/wp-content/plugins/'.plugin_basename(dirname(__FILE__));
		
		?>
		<style type="text/css">@import url(<?php echo $plugins_url; ?>/themes/<?php echo $cf7setting[1]; ?>.css);</style>
		<script type="text/javascript" src="<?php echo $plugins_url; ?>/calendar.js"></script>
		<script type="text/javascript" src="<?php echo $plugins_url; ?>/lang/<?php echo $cf7setting[0]; ?>.js"></script>
		<script type="text/javascript" src="<?php echo $plugins_url; ?>/calendar-setup.js"></script>
		<?php
	}

	function page_text_filter($content) {
		$regex = '/\[datetimepicker\s(.*?)\]/';
		return preg_replace_callback($regex, 'page_text_filter_callback', $content);
	}

	function page_text_filter_callback($matches) {
		$loadsetting = cf7loadsetting();
		$cf7setting = explode(";",$loadsetting->option_value);
		//print_r($cf7setting);
		if($cf7setting[3]=='true')
			$htmlshowtime = "showsTime : true,";
		else
			$htmlshowtime = "showsTime : false,";
			
		$string = "<input type=\"text\" name=\"".$matches[1]."\" id=\"".$matches[1]."\" /><button type=\"reset\" id=\"f_".$matches[1]."\">...</button>
		<script type=\"text/javascript\"> 
			Calendar.setup({
				inputField     :    \"".$matches[1]."\",      // id of the input field
				ifFormat       :    \"".$cf7setting[2]."\",       // format of the input field
				button         :    \"f_".$matches[1]."\",   // trigger for the calendar (button ID)
				singleClick    :    false,           // double-click mode
				".$htmlshowtime."
				step           :    1                // show all years in drop-down boxes (instead of every other year as default)
			});
		</script> 
		";
		return($string);
    }
	
function wpcf7_cf7cal_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';
	$size_att = '';
	$maxlength_att = '';

	if ( 'cf7cal' == $type || 'cf7cal*' == $type ) {
		if ( ! function_exists( 'page_text_filter_callback' ) ) {
			return '<em>' . __( 'To use Calendar, you need <a href="http://webwoke.com/wp-content/uploads/2009/07/cf7-calendar.php.txt">Calendar Module for Contact Form 7</a> uploaded') . '</em>';
		}
	}

	if ( 'cf7cal*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];
			$maxlength_att = (int) $matches[2];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $size_att )
		$atts .= ' size="' . $size_att . '"';
	else
		$atts .= ' size="40"'; // default size

	if ( $maxlength_att )
		$atts .= ' maxlength="' . $maxlength_att . '"';

	// Value
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) && $wpcf7_contact_form->is_posted() ) {
		if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
			$value = '';
		else
			$value = $_POST[$name];
	} else {
		$value = $values[0];
	}

	//$html ='<input type="text" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' />';
$html = page_text_filter_callback(array('',$name));
	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . str_replace('<p>','',$html) . $validation_error . '</span>';

	return $html;
}

if ( ! function_exists( 'wpcf7_add_shortcode' ) ) {
	if( is_file( WP_PLUGIN_DIR."/contact-form-7/includes/shortcodes.php" ) ) {
		include WP_PLUGIN_DIR."/contact-form-7/includes/shortcodes.php";
		wpcf7_add_shortcode( 'cf7cal', 'wpcf7_cf7cal_shortcode_handler', true );
		wpcf7_add_shortcode( 'cf7cal*', 'wpcf7_cf7cal_shortcode_handler', true );
	}
}
/* Validation filter */

function wpcf7_cf7cal_validation_filter( $result, $tag ) {
	global $wpcf7_contact_form;

	$type = $tag['type'];
	$name = $tag['name'];

	$_POST[$name] = trim( strtr( (string) $_POST[$name], "\n", " " ) );

	if ( 'cf7cal*' == $type ) {
		if ( '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_required' );
		}
	}

	return $result;
}

add_filter( 'wpcf7_validate_cf7cal', 'wpcf7_cf7cal_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_cf7cal*', 'wpcf7_cf7cal_validation_filter', 10, 2 );

?>