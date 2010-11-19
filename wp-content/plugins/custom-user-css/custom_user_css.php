<?php
/*
Plugin Name: Custom User CSS
Plugin URI: http://blog.oremj.com/custom-user-css-wordpress-plugin/
Description: Allows users to apply CSS to their blogs.
Version: 0.2
Author: Jeremiah Orem
Author URI: http://blog.oremj.com/
*/

/*  Copyright 2009  Jeremiah Orem  (email : jeremy.orem@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('admin_menu', 'custom_user_css_menu');
add_action('wp_head', 'custom_user_css_addcss', 100);

function custom_user_css_addcss() {
	echo '<style type="text/css">';	
	echo htmlspecialchars(stripslashes(get_option('custom_user_css_css')), ENT_NOQUOTES);
	echo '</style>';

}

function custom_user_css_menu() {
	add_theme_page('Custom User CSS Options', 'Custom User CSS', 'switch_themes', __FILE__, 'custom_user_css_options');
}

function custom_user_css_options() {
	$updated = false;
	$opt_name = 'custom_user_css_css';
	
	$css_val = htmlspecialchars(stripslashes(get_option( $opt_name )), ENT_NOQUOTES);

	if( $_POST['action'] == 'update' ) {
		$css_val = $_POST[ $opt_name ];

		update_option( $opt_name, $css_val );
		$updated = true;
	}

	include_once(dirname(__FILE__) . '/custom_user_css_options.php');
}

?>
