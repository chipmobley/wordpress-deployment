=== WP Google Weather ===
Plugin URI: http://imwill.com/wp-google-weather/
Contributors: Hendrik Will
Tags: google, weather, forecast, api, widget
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 0.5

Displays a weather widget via widget or shortcode using the unofficial Google weather API.

== Description ==

WP Google Weather displays a Weather Widget in your sidebar or on single pages/posts

= Features =

* show today's weather
* show weather forecast for next 3 days
* specifiy output language
* choose between Celsius or Fahrenheit
* supports shortcodes for single pages or posts
* comes with predefined CSS style
* valid XHTML output

== Credits ==

Copyright 2010 by Hendrik Will

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


== Installation ==

1. Unzip and upload files the files to `/wp-content/plugins/wp-google-weather/`
2. Activate the plugin
3. Go to Themes > Widgets and drag WP Google Weather widget to your sidebar
4. Specify title, city, country (optional), temperature as Celsius or Fahrenheit and language (ISO 639-1 code)
5. decide whether it should display only today's weather or also a 3 day forecast

To add the widget to posts and pages use the shortcode [wp_google_weather].
Example: [wp_google_weather city="new york" temperature="f" language="en" forecast="1"]

== Screenshots ==

1. Widget options 
2. Frontend view


== Frequently Asked Questions ==

Feel free to ask!

Q: What are ISO 639-1 codes?
A: Two letter codes for the country.
Some examples:
USA: us
England: en
Germany: de
France: fr

A complete list of the codes can be found at http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes

Q: Fatal error: Call to undefined function: simplexml_load_string()
A: Upgrade to PHP5

== Changelog ==
= 0.5 - 12.08.2010 =
* added UTF-8 param (thanks to Caroig)
* added centered alignment
* fixed shortcode output (thanks to Nico)
* fixed checked checkbox settings in widget options

= 0.4 - 13.02.2010 =
* added shortcode support to use the widget on any post or page
* added wp_remote_fopen to fix curl problems (thanks ncrawford)
* fixed CSS issues

= 0.3 - 09.08.2009 =
* added an extra div wrapper with additional css class to fix layout problems depending on the template (thanks Danny and Joe)

= 0.2 - 28.07.2009 =
* checking unit_system param of Google weather API output to convert Fahrenheit to Celsius (thanks Mike) 

= 0.1 - 25.07.2009  =
* initial release
* fixed include path (thanks Dietmar)

== Todos ==

* add admin_notices after install, direct link to widget page
* add translation for backend
* choose from different styles
* add caching
* add tinyMCE button