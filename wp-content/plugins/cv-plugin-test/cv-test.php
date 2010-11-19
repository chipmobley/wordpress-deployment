<?php
/*
Plugin Name: cv-plugin-test
Description: A cv-plugin test.
Version: 1.0
Author: Chip @ CityVoice
License: GPL
*/
add_option('cv_test_greeting', 'CityVoice ');
#add_filter('the_title', 'cv_test');
add_action('admin_menu', 'cv_test_menu');
add_action('admin_init', 'register_options');
add_shortcode('cvt','cv_test');

function cv_test($content){
	$title_mod	= get_option('cv_test_greeting');
	$address_mod 	= get_option('cv_test_address');
	$city_mod	= get_option('cv_test_city');
	$state_mod 	= get_option('cv_test_state');
	$zip_mod 	= get_option('cv_test_zip');
	
	return "<div class=\"vcard\">
		\n\t<span class=\"org\">$title_mod</span>
		\n\t<span class=\"adr\">
		\n\t\t<span class=\"street-address\">$address_mod</span>
		\n\t\t<span class=\"locality\">$city_mod</span>
		\n\t\t<span class=\"region\">$state_mod</span
		\n\t\t<span class=\"postal-code\">$zip_mod</span>
		\n\t</span>
		</div>"; 	
}

function cv_test_menu(){
	add_submenu_page('options-general.php',	'Edit cv-test Plugin', 'CV-Test Plugin', 'administrator', 'cv-test-plugin-edit', 'cv_test_options');
}

function cv_test_options(){
?>
	<div class="wrap">
	<h2>CV-Test Plugin Options</h2>
	<form method="post" action='options.php'>
		<?php settings_fields('cv-test-plugin-settings'); ?>
		<table class='form-table'>
			<tr valign='top'>
				<th scope='row'>Company Name:</th>
				<td><input type='text' name='cv_test_greeting' value='<?php echo get_option('cv_test_greeting'); ?>' /></td>
			</tr>			
			<tr valign='top'>
				<th scope='row'>Address:</th>
				<td><input type='text' name='cv_test_address' value='<?php echo get_option('cv_test_address'); ?>' /></td>
			</tr>
			<tr valign='top'>
				<th scope='row'>City:</th>
				<td><input type='text' name='cv_test_city' value='<?php echo get_option('cv_test_city'); ?>' /></td>
			</tr>
			<tr valign='top'>
				<th scope='row'>State:</th>
				<td><input type='text' name='cv_test_state' value='<?php echo get_option('cv_test_state'); ?>' /></td>
			</tr>
			<tr valign='top'>
				<th scope='row'>Zip Code:</th>
				<td><input type='text' name='cv_test_zip' value='<?php echo get_option('cv_test_zip'); ?>' /></td>
			</tr>
		</table>
		<p class='submit'>
			<input type='submit' class='button-primary' value='<?php _e('Save Changes') ?>' />
		</p>
	</form>
	</div>
<?php
}

function register_options(){
	register_setting('cv-test-plugin-settings', 'cv_test_greeting');
	register_setting('cv-test-plugin-settings', 'cv_test_address');
	register_setting('cv-test-plugin-settings', 'cv_test_city');
	register_setting('cv-test-plugin-settings', 'cv_test_state');
	register_setting('cv-test-plugin-settings', 'cv_test_zip');   	
}
?>

