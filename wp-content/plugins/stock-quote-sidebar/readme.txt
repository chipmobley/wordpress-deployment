=== Stock Quote Sidebar ===
Contributors: anhill
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=andy%40hillhome%2eorg&item_name=Andrew%20Hill&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: financial, stocks, stock quotes
Requires at least: 2.0.2
Tested up to: 3.0.1
Stable tag: 2.8

Stock Quote Sidebar is a WordPress plugin/widget that allows you to put a list of stock quotes in your sidebar (or anywhere you want, really).  

== Description ==

Stock Quote Sidebar is a WordPress plugin/widget that allows you to put a list of stock quotes in your sidebar (or anywhere you want, really).

The stock symbol, last price, and change (in dollars and/or percent) are displayed in a tabular format.  The full company name is displayed in a tooltip when the symbol is moused-over.  The date and time of the first quote in the list is displayed at the bottom of the list.

When a user hovers over the day's change, a tooltip displays a chart of the
stock's performance over a configurable time period.

Other functions include placing a separator bar between subsets of stock
quotes, changing the date/time format, and displaying both static quotes and
random selections from a list of symbols.

[Change Log](http://svn.wp-plugins.org/stock-quote-sidebar/trunk/change.log)

== Screenshots ==

1. Screen shot of the plugin in action, including the mouseover stock chart.

== Installation ==

Requirements:

* PHP 5
* cURL support must be enabled

Steps:

1. Extract the plugin's zip file in your '/wp-content/plugins' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin in the 'Options', 'Stock Quote Sidebar'
1. Place the widget, or if you prefer, Place the plugin's PHP code where you want it in the sidebar PHP file.

You can use the plugin in two ways, to generate different lists of stocks in different places.

* Specify a list of symbols in the options page
>To use this approach, simply enable the widget, or manually insert the following code where you want the quotes to appear:
> `<?php get_stock_quote(); ?>`

* Specify a list of symbols in the php function within your WordPress template
>To use this option, call the same function with the symbols as a parameter:
> `<?php get_stock_quote("^DJI,^IXIC,^GSPC,NOVL,ko,pfe,intc"); ?>`
>This approach is not currently supported with the widget.

==Frequently Asked Questions ==

= How do I use the plugin with the K2 theme? =

Stock Quote Sidebar will now show up as an option in the K2 Sidebar Manager!
If you wish, you can also add a 'Text, HTML and PHP' module to your sidebar called 'Stock Quotes'.  In the
code area, enter `<?php get_stock_quote(); ?>`.

The latest revisions contain some CSS fixes to ensure compatibility with the
CSS in K2.

== ContactInfo ==

For updates to code and documentation, see http://andy.hillhome.org/code/stockquotesidebar.

Report problems to andy@hillhome.org.
