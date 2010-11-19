<?php
/*
Plugin Name: WP Google Weather
Plugin URI: http://imwill.com/wp-google-weather/
Description: Displays a weather widget using the Google weather API.
Version: 0.5
Author: Hendrik Will
Author URI: http://imwill.com
*/

require_once(ABSPATH . WPINC . '/formatting.php');

#defining default variables
static $hw_wpgw_city = 'Braunschweig,Germany';
static $hw_wpgw_lang = 'en';
static $hw_wpgw_forecast = TRUE;

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'load_hw_wpgw' );

/**
 * Register our widget.
 * 'hw_wpgw' is the widget class used below.
 *
 * @since 0.1
 */
function load_hw_wpgw() {
	register_widget( 'hw_wpgw' );
}

/**
 * WP Google Weather Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update. Awesome!
 *
 * @since 0.1
 */
class hw_wpgw extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function hw_wpgw() {
		#Widget settings
		$widget_ops = array( 'description' => __('A widget that displays the weather.', 'hw_wpgw') );

		#Widget control settings
		$control_ops = array( 'width' => 200, 'height' => 350, 'id_base' => 'hw_wpgw' );

		#Create the widget
		$this->WP_Widget( 'hw_wpgw', __('WP Google Weather', 'hw_wpgw'), $widget_ops, $control_ops );
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['city'] = strip_tags( $new_instance['city'] );
		$instance['lang'] = strip_tags( $new_instance['lang'] );
		$instance['temp'] = $new_instance['temp']['select_value'];
		$instance['forecast'] = $new_instance['forecast'];
		$instance['alignment'] = $new_instance['alignment'];
		
		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		#Set up some default widget settings
		$defaults = array( 'title' => __('Example', 'example'), 'city' => __('Braunschweig, Germany', 'city'), 'lang' => 'en', 'temp' => 'f', 'forecast' => true, 'alignment' => false );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" size="30" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'city' ); ?>"><?php _e('City, Country(<i>optional</i>):', 'city'); ?></label>
			<input id="<?php echo $this->get_field_id( 'city' ); ?>" name="<?php echo $this->get_field_name( 'city' ); ?>" value="<?php echo $instance['city']; ?>" size="30" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'temp' ); ?>"><?php _e('Temperature:', 'temp'); ?></label>
			<select id="<?php echo $this->get_field_id( 'temp' ); ?>" name="<?php echo $this->get_field_name( 'temp' ); ?>[select_value]">
      			<option value="c" <?php if ($instance['temp'] == 'c') echo 'selected'; ?>>Celsius</option>
      			<option value="f" <?php if ($instance['temp'] == 'f') echo 'selected'; ?>>Fahrenheit</option>
    		</select>			
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'lang' ); ?>"><?php _e('Language:', 'lang'); ?></label>
			<input id="<?php echo $this->get_field_id( 'lang' ); ?>" name="<?php echo $this->get_field_name( 'lang' ); ?>" value="<?php echo $instance['lang']; ?>" size="2" />
		</p>


		<p>
			<input class="checkbox" type="checkbox" <?php if($instance['forecast'] == true) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'forecast' ); ?>" name="<?php echo $this->get_field_name( 'forecast' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'forecast' ); ?>"><?php _e('Display forecast?', 'forecast'); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php if($instance['alignment'] == true) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'alignment' ); ?>" name="<?php echo $this->get_field_name( 'alignment' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'alignment' ); ?>"><?php _e('Alignment centered?', 'alignment'); ?></label>
		</p>
	
		
	<?php
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		#Our variables from the widget settings
		$title = apply_filters('widget_title', $instance['title'] );
		$city = $instance['city'];
		$temp = $instance['temp'];
		$lang = $instance['lang'];
		$forecast = $instance['forecast'];
		$alignment = $instance['alignment'];

		#Before widget (defined by themes)
		echo $before_widget;

		#Display the widget title if one was input (before and after defined by themes)
		if ( $title )
			echo $before_title . $title . $after_title;

		#Display name from widget settings if one was input		
		$this->buildWidget($city, $temp, $lang, $forecast, $alignment);
			
		#After widget (defined by themes)
		echo $after_widget;
	}
	function getData($city,$temp,$lang,$forecast) {
		
		$cleancity = $city;
		$stripped = $this->replaceChars($cleancity);
		$city = remove_accents($stripped);
		
		#added UTF8 param @since 0.5
		$xmlUrl = 'http://www.google.com/ig/api?weather='.$city.'&oe=utf-8&hl='.$lang;
		
		#grab URL and pass it to the browser
		$output = wp_remote_fopen($xmlUrl);
	 		
		#load resource into a xmldom
		$xmlData  = simplexml_load_string($output);
		
		$unit_system = $xmlData->weather->forecast_information->unit_system['data'];
		
		#define conditions array
		$conditions = array();
		
		#get current conditions
		$conditions['current']['condition'] = $xmlData->weather->current_conditions->condition['data'];
		$conditions['current']['icon'] = $xmlData->weather->current_conditions->icon['data'];
		if($temp == 'c') {
			$conditions['current']['temp'] = $xmlData->weather->current_conditions->temp_c['data'].'&deg;C';	
		}	else if($temp == 'f') {
			$conditions['current']['temp'] = $xmlData->weather->current_conditions->temp_f['data'].'&deg;F';	
		}
		
		#get forecast conditions
		for($i=0; $i<=3; $i++) {		
			$conditions['forecast'][$i]['day'] = $xmlData->weather->forecast_conditions[$i]->day_of_week['data'];
			$conditions['forecast'][$i]['icon'] = $xmlData->weather->forecast_conditions[$i]->icon['data'];
			$conditions['forecast'][$i]['high'] = $xmlData->weather->forecast_conditions[$i]->high['data'];
			$conditions['forecast'][$i]['low'] = $xmlData->weather->forecast_conditions[$i]->low['data'];
			$conditions['forecast'][$i]['condition'] = $xmlData->weather->forecast_conditions[$i]->condition['data'];
		}
		
		#convert Fahrenheit to Celsius if needed
		if($unit_system=='US' AND ($temp=='c')) {
			for($i=0; $i<=3; $i++) {
				$conditions['forecast'][$i]['high'] = intval(($conditions['forecast'][$i]['high']-32)*5/9);
				$conditions['forecast'][$i]['low'] = intval(($conditions['forecast'][$i]['low']-32)*5/9);
			}
		}
		
		return $conditions;
		
	}
	
	function buildWidget($city, $temp, $lang, $forecast, $alignment) {
		$conditions = $this->getData($city, $temp, $lang, $forecast);	
		
		if($alignment == true) {
			$alignmentclass = 'centered';
		}
		
		echo '<div class="hw_wpgw">
		<dl class="'.$alignmentclass.'">
			<dd class="today">
				<span class="condition">'.$conditions['current']['condition'].'</span>
				<span class="temperature">'.$conditions['current']['temp'].'</span>
				<img src="http://www.google.com/'.$conditions['current']['icon'].'" alt="'.$conditions['current']['condition'].'" />
			</dd>
		';	
		
		if($forecast == true) {
			echo '
				<dd class="day1">
					<span class="day">'.$conditions['forecast'][1]['day'].'</span>
					<img src="http://www.google.com'.$conditions['forecast'][1]['icon'].'" alt="'.$conditions['forecast'][1]['condition'].'" title="'.$conditions['forecast'][1]['condition'].'" /><br />
					<span class="temperature">'.$conditions['forecast'][1]['high'].'/'.$conditions['forecast'][1]['low'].'</span><br /> 
				</dd>
				<dd class="day2">
					<span class="day">'.$conditions['forecast'][2]['day'].'</span>
					<img src="http://www.google.com'.$conditions['forecast'][2]['icon'].'" alt="'.$conditions['forecast'][2]['condition'].'" title="'.$conditions['forecast'][2]['condition'].'" /><br />
					<span class="temperature">'.$conditions['forecast'][2]['high'].'/'.$conditions['forecast'][2]['low'].'</span><br /> 
				</dd>
				<dd class="day3">
					<span class="day">'.$conditions['forecast'][3]['day'].'</span>
					<img src="http://www.google.com'.$conditions['forecast'][3]['icon'].'" alt="'.$conditions['forecast'][3]['condition'].'" title="'.$conditions['forecast'][3]['condition'].'" /><br />
					<span class="temperature">'.$conditions['forecast'][3]['high'].'/'.$conditions['forecast'][3]['low'].'</span><br /> 
				</dd>
				
				';	
			}
		echo '
		</dl>
		</div>
		<div style="clear: both;"></div>
		';
	}
	
	function replaceChars($data){
		$search = array('Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', ' ');
		$replace = array('Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', '%20');
		$output = str_replace($search, $replace, $data);
		
		return $output;
	}
}

function wpgw_css() {
	echo '<link rel="stylesheet" type="text/css" media="screen" href="'. WP_PLUGIN_URL . '/wp-google-weather/wp-google-weather.css"/>';	
}

add_action('wp_head', 'wpgw_css');

/**
 * Add function to load weather shortcode.
 * @since 0.4
 */

add_shortcode('wp_google_weather', 'hw_wp_gw_shortcode');

function hw_wp_gw_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		"city" => 'Braunschweig, Germany',
		"temperature" => 'c',
		"language" => 'en',
		"forecast" => 'true'
		), $atts)
	);
	$hw_wpgw = new hw_wpgw();
	ob_start();
	$hw_wpgw->buildWidget($city, $temperature, $language, $forecast, $alignment);
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

?>