<?php
/*
Plugin Name: CV-Clicky
Description: Add Clicky Code to site
Version: 1.0
Author: Chip @ CityVoice
Author URI: http://
License: GPL
*/
add_option('cv_clicky_id', 'null'); 
add_action('admin_menu', 'cv_clicky_menu');
add_action('wp_footer', 'cv_clicky');
add_action('admin_init', 'cv_clicky_register_options');

function cv_clicky() {
	$site_id = get_option('cv_clicky_id');
	echo <<<EOD
	<!-- Clicky Code -->
	<script src="http://stats.cityvoice.com/js" type="text/javascript"></script>
	<script type="text/javascript">citystats.init($site_id);</script>
	<noscript><p><img alt="CityStats" width="1" height="1" src="http://stats.cityvoice.com/{$site_id}ns.gif" /></p></noscript>
	
EOD;
}

function cv_clicky_menu(){
	add_submenu_page('options-general.php', 'CV-Clicky Options', 'CV-Clicky', 'administrator', 'cv-clicky-edit', 'cv_clicky_options');
}

function cv_clicky_options(){
?>
<div class="wrap">
<h2>CV-Clicky Plugin Options</h2>
<form method='post' action='options.php'>
	<?php settings_fields('cv-clicky-settings'); ?>
	<table class='form-table'>
		<tr valign='top'>
		<th scope='row'>Clicky Site ID: </th>
		<td><input type='text' name='cv_clicky_id' value='<?php echo get_option('cv_clicky_id'); ?>' /></td>
		</tr>
	</table>
	<p class='submit'>
		<input type='submit' class='button-primary' value='<?php _e('Save Changes'); ?>' />
	</p>
</form>	
</div>
<?php
}

function cv_clicky_register_options(){
	register_setting('cv-clicky-settings', 'cv_clicky_id');
}

?>
 