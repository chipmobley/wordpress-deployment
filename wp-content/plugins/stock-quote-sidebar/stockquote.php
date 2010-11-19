<?php
/*
Plugin Name: Stock Quote Sidebar
Plugin URI: http://andy.hillhome.org/blog/code/stockquotesidebar
Description: A plugin that displays stock quotes with popup charts in the sidebar.
Author: Andrew Hill
Author URI: http://andy.hillhome.org
Version: 2.8
Date: 2010-09-16
*/

/*  Copyright 2010  Andrew Hill  (email : andy@hillhome.org)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*if (is_plugin_page()) {
	admin_add_stockquotesidebar();
} */

//add_action('options_page_stockquote', 'admin_add_stockquotesidebar');

add_action ('wp_head', 'insert_sqsb_header_code');
add_action ('admin_menu', 'admin_add_stockquotesidebar');


//Fix cannot redeclare problem
if ( !(function_exists('insert_sqsb_header_code')) ) {
function insert_sqsb_header_code () {
	echo '
	<link rel="stylesheet" href="'.get_settings('siteurl').'/wp-content/plugins/stock-quote-sidebar/sqsbstyle.css" type="text/css" media="screen" />
    <!-- The line below starts the conditional comment -->
    <!--[if IE]>
      <style type="text/css">
        body {behavior: url('.get_settings('siteurl').'/wp-content/plugins/stock-quote-sidebar/csshover.htc);}
      </style>
    <![endif]--> <!-- This ends the conditional comment -->
    <script language="javascript" type="text/javascript">
    //<![CDATA[
    function popupChart( obj, url ) {
            placeHolder = $(obj).getElementsByClassName("chartPlaceHolder")[0];
                            if (placeHolder.nodeName != "IMG") {
                             placeHolder.replace("");
                         }
			}
//]]>
</script>';
}
}

function admin_add_stockquotesidebar() {
	// Add menu under Options:
	add_options_page('Stock Quote Sidebar Options', 'Stock Quote Sidebar', 8, __FILE__, 'admin_stockquotesidebar');	
	// Create option in options database if not there already:
	$options = array();
	//$text_to_replace = ;
    	$options['sqsbstatsymbols'] = array(
		0=>'^DJI',
        1=>'^IXIC',
        2=>'^GSPC',
	);
    	$options['sqsbsymbols'] = array(
		0=>'NOVL',
        1=>'MSFT',
        2=>'INTC',
        3=>'PFE',
        4=>'GOOG',
	);
    $options['yahoosite'] = 'finance.yahoo.com';
    $options['yahoochartsite'] = 'ichart.finance.yahoo.com';
    $options['yahoochartrange'] = '/t?s=';
    $options['dateform'] = 'Y-m-d';
    $options['timeform'] = 'H:i';
    $options['displayname'] = 'symbol';
    $options['randomquotequantity'] = 0;
		$options['pointsorpercent'] = 'points';	
add_option('stockquotesidebar', $options, 'Options for the Stock Quote Sidebar plugin');
} //end admin_add_stockquotesidebar()


function admin_stockquotesidebar() {
	
	// Uncomment for error reporting on options page
	//error_reporting(E_ALL);
	// See if user has submitted form
	if ( isset($_POST['submitted']) ) {
		$options = array();
		$store_symbols = array();
		$store_static_symbols = array();
		
		$stocksymbols = $_POST['sqsbsymbols'];
        $staticstocksymbols = $_POST['sqsbstaticsymbols'];
        $yahoosite = $_POST['yahoosite'];
        $yahoochartsite = $_POST['yahoochartsite'];
        $yahoochartrange = $_POST['yahoochartrange'];
        $dateform = $_POST['dateform'];
        $timeform = $_POST['timeform'];
        $displayname = $_POST['displayname'];
        $pointsorpercent = $_POST['pointsorpercent'];
        $randomquotequantity = $_POST['randomquotequantity'];
		if ( !empty($stocksymbols) ) {
			$itemcount = 0;
			foreach (explode("\n", $stocksymbols) AS $line) {
				if (!empty($line)) $store_symbols[$itemcount] = str_replace('\\', '', trim(strtoupper($line)));
                $itemcount++;
			}
    }

		if ( !empty($staticstocksymbols) ) {
			$itemcount = 0;
			foreach (explode("\n", $staticstocksymbols) AS $line) {
				if (!empty($line)) $store_static_symbols[$itemcount] = str_replace('\\', '', trim(strtoupper($line)));
                $itemcount++;
			}
    }

    $options['sqsbstatsymbols'] = $store_static_symbols;
    $options['sqsbsymbols'] = $store_symbols;
    $options['yahoosite'] = trim($yahoosite, '/');
    $options['yahoochartsite'] = trim($yahoochartsite, '/');
    $options['yahoochartrange'] = $yahoochartrange;
    $options['dateform'] = $dateform;
    $options['timeform'] = $timeform;
    $options['displayname'] = $displayname;
    $options['randomquotequantity'] = $randomquotequantity;
 		$options['pointsorpercent'] = $pointsorpercent;	
 		
		// Remember to put all the other options into the array or they'll get lost!
		update_option('stockquotesidebar', $options);
		echo '<div class="updated"><p>Plugin settings saved.</p></div>';
	}
	
	// Draw the Options page for the plugin.
	$options = get_option('stockquotesidebar');
	$stocksymbols = $options['sqsbsymbols'];
  $staticstocksymbols = $options['sqsbstatsymbols'];
  $yahoosite = $options['yahoosite'];
  $yahoochartsite = $options['yahoochartsite'];
  $yahoochartrange = $options['yahoochartrange'];
  $dateform = $options['dateform'];
  $timeform = $options['timeform'];
  $randomquotequantity = $options['randomquotequantity'];
  $displayname = $options['displayname'];
  $pointsorpercent = $options['pointsorpercent'];
	$symbol_list = '';
  $static_symbol_list = '';
  
  // Catch empty lists and prevent benign error message
	if(!empty($stocksymbols)) {
		foreach ($stocksymbols AS $symindex => $symbol) {
			$symbol_list .= "$symbol\n";
		}
	}
	
	// Catch empty lists and prevent benign error message
	if(!empty($staticstocksymbols)) {
		foreach ($staticstocksymbols AS $staticsymindex => $staticsymbol) {
			$static_symbol_list .= "$staticsymbol\n";
		}
	}
	$action_url = $_SERVER['PHP_SELF'] . '?page=stock-quote-sidebar/' . basename(__FILE__);

  echo <<<END
	<div class='wrap'>\n
		<h2>Stock Quote Sidebar Plugin Options</h2>\n
		<p>Stock Quote Sidebar is a WordPress plugin that allows you to put a list of stock quotes in your sidebar (or anywhere you want, really).  The stock symbol, last price, and change are displayed in a tabular format.  The full company name is displayed in a tooltip when the symbol is moused-over.  The date and time of the first quote in the list is displayed at the bottom of the list.

<form name="stockquotesidebarform" action="$action_url" method="post">
		<input type="hidden" name="submitted" value="1" />
		<p>In the textboxes below, enter the stock symbols for the companies you would like in the stock quote list.</p>
        <p>The format should be one symbol per line.</p>
        <p>If you want a horizontal rule inserted anywhere, enter "SQSBSEPARATOR" as a symbol somewhere in the first list.</p>
        <p>Be sure to use symbols that are valid on Yahoo Finance.  Symbols will automatically be converted to upper-case.</p>
		<p>You can use the plugin in several ways, to generate different lists of stocks in different places.</p>
        <ol>
        <li>Specify a list of symbols in the options page<br/>
            To use this approach, simply insert the following code where you want the quotes to appear:<br/>
            <code>&lt;?php get_stock_quote(); ?&gt;<br/></li></code><br/>
        <li>Specify a list of symbols in the php function within your WordPress template<br/>
            To use this option, call the same function with the symbols as a parameter:<br/>
            <code>&lt;?php get_stock_quote("^DJI,^IXIC,^GSPC,NOVL,ko,pfe,intc"); ?&gt;</li></code></ol>
  <br/>
        <p>There are two textboxes below that accept stock symbols.  The first list will always be displayed, while the second contains a list of symbols from which a number of quotes will be randomly displayed.  To use this option, set the "Number of Random Quotes" option greater than zero.  The "always display" list will always appear first, in the order entered, followed by the number of quotes from the random list that you specify.</p>
        <p>A usage example would be to put indexes such as ^DJI or ^FTSE, as well as stocks that you hold or want to track closely in the first list, and other less interesting stocks in the second list.</p>
	<fieldset class="option">
        <legend><b><font size="+1">Options</font></b></legend>
        <br/>
        <b>Yahoo Quote URL:</b>  Enter only the base URL such as finance.yahoo.com (e.g. uk.finance.yahoo.com for UK)<br/>
        <b>DO NOT ENTER the http:// portion of the URL!</b><br/>
        <input type="text" name="yahoosite" id="yahoosite" size="60" value="$yahoosite">
        <p/>
        <b>Yahoo Chart URL:</b>  Enter only the base URL such as http://ichart.finance.yahoo.com (e.g. http://uk.ichart.yahoo.com for UK)<br/>
        <b>DO NOT ENTER the http:// portion of the URL!</b><br/>
        <input type="text" name="yahoochartsite" id="yahoochartsite" size="60" value="$yahoochartsite">
        <p/>

        <b>Yahoo Chart Range:</b><br/>
        <select name="yahoochartrange" id="yahoochartrange">
END;

echo "      <option value='/t?s=' ";
  if($yahoochartrange == '/t?s=') { echo 'SELECTED';} 
  echo ">1 Day</option>";
echo "      <option value='/v?s=' ";
  if($yahoochartrange == '/v?s=') { echo 'SELECTED';}
  echo ">5 Days</option>";
echo "      <option value='/c/bb/n/' ";
  if($yahoochartrange == '/c/bb/n/') { echo 'SELECTED';}
  echo ">1 Year</option>";

 echo '       </select>
<p/>';
  

echo '<b>Display Change as Points or Percent:</b> Note that many sidebars may not have space for both<br/>
				<select name="pointsorpercent" id="pointsorpercent">';

         		if ($pointsorpercent == 'points') {
                echo '<option value="points" selected>Points</option>
                <option value="percent">Percent</option>
                <option value="pointsandpercent">Both Points and Percent</option></select>';
            }
            else if ($pointsorpercent == 'percent') {
                echo '<option value="points">Points</option>
                <option value="percent" selected>Percent</option>
                <option value="pointsandpercent">Both Points and Percent</option></select>';
            }
            else if ($pointsorpercent == 'pointsandpercent') {
                echo '<option value="points">Points</option>
                <option value="percent">Percent</option>
                <option value="pointsandpercent" selected>Both Points and Percent</option></select>';
            }
            else {
                echo '<option value="points" selected>Points</option>
                <option value="percent">Percent</option>
                <option value="pointsandpercent">Both Points and Percent</option></select>';
            }
echo <<<END
<p/>     
        <b>Date and Time Format:</b>  Use parameters from the php <a href="http://www.php.net/date">date()</a> function.  The defaults are Y-m-d, meaning 2005-10-25 for date, and H:i, meaning 16:25 for time.<br/>
        Date: <input type="text" name="dateform" id="dateform" size="10" value="$dateform">
        Time: <input type="text" name="timeform" id="timeform" size="10" value="$timeform"><p/>
END;
echo '  <b>Symbol Display:</b>  Whether to display the symbol or the company name in first column.<br/>
        <select name="displayname">';
            if ($displayname == 'symbol') {
                echo '<option value="symbol" selected>Stock Symbol</option>
                <option value="company">Company Name</option></select>';
            }
            else if ($displayname == 'company') {
                echo '<option value="symbol">Stock Symbol</option>
                <option value="company" selected>Company Name</option></select>';
            }
            else {
                echo '<option value="symbol" selected>Stock Symbol</option>
                <option value="company">Company Name</option></select>';
            }                
            echo <<<END
            <p/>
        <b>Stock Symbols to Always Display:</b>  One per line<br/>
		<textarea name="sqsbstaticsymbols" id="sqsbstaticsymbols" style="font-family: \"Courier New\", Courier, mono;" rows="15" cols="20">$static_symbol_list</textarea>
        <p><br/></p>

<fieldset class="option">
    <legend><b><font size="+1">Random Quote Options</font></b></legend><p/>
    <p>In addition to always displaying the stock quotes defined above, you may choose to have a certain number of quotes randomly chosen from the list below.  Enter the number of random quotes you desire, and the list of symbols to choose from.</p>
        <b>Number of Random Quotes:</b>  (zero will display none)
        <input type="text" name="randomquotequantity" id="randomquotequantity" size="5" value="$randomquotequantity">
        <p/>
		<b>Stock Symbols to Randomly Choose From:</b>  One per line<br/>
		<textarea name="sqsbsymbols" id="sqsbsymbols" style="font-family: \"Courier New\", Courier, mono;" rows="15" cols="20">$symbol_list</textarea>
    </fieldset>
<p>
	<div class="submit"><center><input type="submit" name="Submit" size="90" value="Save changes now &raquo;" /></center></div>
</p><br/>
	</fieldset>
</form>
	</div>
END;
} //end admin_stockquotesidebar()

function sqCSV2Array($data,$delim=',',$enclosure='"')
{
 $enclosed=false;
 $fldcount=0;
 $linecount=0;
 $fldval='';
 for($i=0;$i<strlen($data);$i++)
 {
  $chr=$data{$i};
  switch($chr)
  {
   case $enclosure:
    if($enclosed&&$data{$i+1}==$enclosure)
    {
     $fldval.=$chr;
     ++$i; //skip next char
    }
    else
     $enclosed=!$enclosed;
    break;
   case $delim:
    if(!$enclosed)
    {
     $ret_array[$linecount][$fldcount++]=$fldval;
     $fldval='';
    }
    else
     $fldval.=$chr;
    break;
   case "\r":
    if(!$enclosed&&$data{$i+1}=="\n")
     continue;
   case "\n":
    if(!$enclosed)
    {
     $ret_array[$linecount++][$fldcount]=$fldval;
     $fldcount=0;
     $fldval='';
    }
    else
     $fldval.=$chr;
    break;
   default:
    $fldval.=$chr;
  }
 }
 if($fldval)
  $ret_array[$linecount][$fldcount]=$fldval;
 return $ret_array;
}

function sqArray2CSV($data)
{   
    $stocksymbols = "";
    while (list($key,$value) = each($data)) {
        $stocksymbols .= $value . ',';
    }
    return trim($stocksymbols , ',');
}

function sqList2CSV($data)
{
    foreach (explode(",", $data) AS $symbol) {
		$stocksymbols .= $symbol . ',';
	}
    return trim($stocksymbols , ',');
}

function get_stock_quote($passedsymbols = "getfromdb") {
		
    //For plugin execution timing
    $sq_time_start = microtime(false);
		
    //The following line is for debug purposes only - it should usually be commented
    //error_reporting(E_ALL);
    $options = get_option('stockquotesidebar');
    $sqsbstatsymbols = $options['sqsbstatsymbols'];
    $sqsbsymbols  = $options['sqsbsymbols'];	
    $yahoosite = $options['yahoosite'];
    $yahoochartsite = $options['yahoochartsite'];
    $yahoochartrange = $options['yahoochartrange'];
    $dateform = $options['dateform'];
    $timeform = $options['timeform'];
    $displayname = $options['displayname'];
    $randomquotequantity = $options['randomquotequantity'];
    $pointsorpercent = $options['pointsorpercent'];

if($passedsymbols == "getfromdb") {

    // Shuffle the array if random is enabled
    if ($randomquotequantity > 0) {
        srand((float)microtime() * 1000000);
        shuffle($sqsbsymbols);
        $sqsbsymbols = array_slice($sqsbsymbols, 0, $randomquotequantity);
        // Merge the static and random arrays, and remove duplicates
        $sqsballsymbols = array_unique(array_merge($sqsbstatsymbols, $sqsbsymbols));
    }
    else {
        $sqsballsymbols = $sqsbstatsymbols;
    }    

    $stocksymbols = sqArray2CSV($sqsballsymbols);
}

else {
    $stocksymbols = $passedsymbols;
    }
    if($yahoosite == "finance.yahoo.com") {
    	$url = sprintf("http://download.%s/d/quotes.csv?s=%s&f=snl1d1t1c1ohgvp", $yahoosite, $stocksymbols);
    	}
    else {
        $url = sprintf("http://%s/d/quotes.csv?s=%s&f=snl1d1t1c1ohgvp", $yahoosite, $stocksymbols);
        }
	$ch = curl_init();
	$timeout = 10;
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_URL, $url);
	$fp = curl_exec($ch);
	curl_close($ch);

	if($fp == "") {
		echo "error : cannot receive stock quote information";
	}
	else {
		echo "<table border='0' cellpadding='0' cellspacing='0'>";
		$stocklist=sqCSV2Array($fp);
        
		// The date and time stamps will be derived from the updated time of the first symbol in the list
		$date = trim($stocklist[0][3], '\"');
		$time = trim($stocklist[0][4], '\"');

		for($i=0;$i<count($stocklist);$i++) {
			$stocksymbol = trim($stocklist[$i][0], '\"');
		        $company = trim($stocklist[$i][1], '\"');
			$last = sprintf("%01.2f",$stocklist[$i][2]);
			$change = sprintf("%+01.2f", $stocklist[$i][5]);
			$open = $stocklist[$i][6];
			$high = $stocklist[$i][7];
			$low = $stocklist[$i][8];
			$volume = $stocklist[$i][9];
			$prevclose = $stocklist[$i][10];
			
			if ($prevclose == "N/A") {
				$percentchange = "N/A";
			}
			
			else if ($prevclose == 0) {
				$percentchange = 0;
			}
			
			else {
				$percentchange = ($change / $prevclose) * 100;
			}
			
            //Display either the symbol or the company name in the first column, as configured
            if($displayname == 'symbol') {
                $displaystocksymbol = $stocksymbol;
            }
            else if($displayname == 'company') {
                $displaystocksymbol = ucwords(strtolower($company));
            }
			
			//Provide nicer (and short) names for common indexes
			if($stocksymbol == "^DJI") {
				$displaystocksymbol = "DJIA";
			}

			else if($stocksymbol == "^IXIC") {
				$displaystocksymbol = "NASDAQ";
			}

			else if($stocksymbol == "^GSPC") {
				$displaystocksymbol = "S&amp;P 500";
                $company = "S&amp;P 500 Index";
			}

			/* else {
				$displaystocksymbol = $stocksymbol;
			} */

            // Catch blank rows in the return results and do not display
            if($stocksymbol=="") {
                break;
            }
			if( $i%2 != 0 ) {
				echo '<tr id="sqsbevenrow">';
			}
			else {
				echo '<tr id="sqsboddrow">';
			}

            if ($stocksymbol == "SQSBSEPARATOR") {
                echo '<td colspan="9"><hr id="sqsbhr" noshade="noshade" size="1"/></td>';
            }
            else {
			
							//Click-through lookup URL
       				$sqclickurl = sprintf("http://%s/q?s=%s", $yahoosite, $stocksymbol);
              //$charturl = sprintf("%s/t?s=%s", $yahoochartsite, $stocksymbol);
              if ($yahoochartrange == "/c/bb/n/") {
              	$charturl = sprintf("http://%s%s%s", $yahoochartsite, $yahoochartrange, strtolower(str_replace('^', '_', $stocksymbol)));
              }
              else {
              	$charturl = sprintf("http://%s%s%s", $yahoochartsite, $yahoochartrange, $stocksymbol);
              }
              $chartclickurl = sprintf ("http://%s/q/bc?s=%s&amp;t=1d" , $yahoosite, $stocksymbol);
	       			echo "<td width='60'><a title='$company' href='$sqclickurl' target='_blank'>$displaystocksymbol</a></td>";
	       			echo "<td width='50' align='right'>$last</td>";
	       			echo "<td>&nbsp;&nbsp;</td>";
    					// Begin Points or percent block
							if ($pointsorpercent == "percent") {
    						if($change < 0) {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a href='$chartclickurl' class='sqsbchart'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='red'>";
	       					printf("%+8.2f%%", $percentchange);
	       					echo "</font></a></td>";
	       				}
	       				else if($change > 0) {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='green'>";
	       					printf("%+8.2f%%", $percentchange);
	       					echo "</font></a></td>";
	       				}
	       				else {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/>";
	       					if (is_numeric($percentchange)) {
	       						printf("%+8.2f%%", $percentchange);
	       					}
	       					
	       					else {
	       						echo $percentchange;
	       					}
	       						
	       					echo "</a></td>";
	       				}
	       			}	

    					else if($pointsorpercent == "pointsandpercent") {
    						if($change < 0) {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a href='$chartclickurl' class='sqsbchart'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='red'>$change</font></a></td>";
	       					echo "<td>&nbsp;&nbsp;</td>";
	       					echo "<td width='45' align='right'><a href='$chartclickurl' class='sqsbchart'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='red'>";
	       					printf("%+8.2f%%", $percentchange);
	       					echo "</font></a></td>";
	       				}
	       				else if($change > 0) {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='green'>$change</font></a></td>";
	       					echo "<td>&nbsp;&nbsp;</td>";
	       					echo "<td width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='green'>";
	       					printf("%+8.2f%%", $percentchange);
	       					echo "</font></a></td>";
	       				}
	       				else {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/>$change</a></td>";
	       					echo "<td>&nbsp;&nbsp;</td>";
	       					echo "<td width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/>";
	       					if (is_numeric($percentchange)) {
	       						printf("%+8.2f%%", $percentchange);
	       					}
	       					
	       					else {
	       						echo $percentchange;
	       					}
	       					
	       					echo "</a></td>";
	       				}
	       			}
	       		
	       			else {
	       				if($change < 0) {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a href='$chartclickurl' class='sqsbchart'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='red'>$change</font></a></td>";
	       				}
	       				else if($change > 0) {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/><font color='green'>$change</font></a></td>";
	       				}
	       				else {
	       					echo "<td style='white-space: nowrap;' width='45' align='right'><a class='sqsbchart' href='$chartclickurl'><img alt='chart' src='$charturl' border='0' width='192' height='96'/>$change</a></td>";
	       				}
	       			}	       			
            }
    		echo "</tr>";
				
		}
		echo "</table>";
        //Use the date and time formats configured in plugin options
        $sqdisplaydate = date( $dateform, strtotime( $date ) );
        $sqdisplaytime = date ( $timeform, strtotime( $time ) );
		//Below line can be changed to fit style of site.
		echo "<center><font id='stockfooter'>$sqdisplaydate $sqdisplaytime</font></center>";
		
		//Uncomment the following two lines to enable timing of this plugin's execution
		//$sq_time_run = microtime(false) - $sq_time_start;
		//echo	"<center><font id='stockfooter'>Runtime $sq_time_run seconds</font></center>";

	}
}

// Widget Support begins here

function widget_sqsidebar_init() {

    // Check to see required Widget API functions are defined...
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
        return; // ...and if not, exit gracefully from the script.

    // This function prints the sidebar widget--the cool stuff!
    function widget_sqsidebar($args) {

        // $args is an array of strings which help your widget
        // conform to the active theme: before_widget, before_title,
        // after_widget, and after_title are the array keys.
        extract($args);

        // Collect our widget's options, or define their defaults.
        $options = get_option('widget_sqsidebar');
        $title = empty($options['title']) ? 'Stock Quotes' : $options['title'];
        //$text = empty($options['text']) ? 'Hello World!' : $options['text'];

         // It's important to use the $before_widget, $before_title,
         // $after_title and $after_widget variables in your output.
        echo $before_widget;
        echo $before_title . $title . $after_title;
        echo get_stock_quote();
        echo $after_widget;
    }

    // This is the function that outputs the form to let users edit
    // the widget's title and so on. It's an optional feature, but
    // we'll use it because we can!
    function widget_sqsidebar_control() {

        // Collect our widget's options.
        $options = get_option('widget_sqsidebar');

        // This is for handing the control form submission.
        if ( $_POST['sqsidebar-submit'] ) {
            // Clean up control form submission options
            $newoptions['title'] = strip_tags(stripslashes($_POST['sqsidebar-title']));
            //$newoptions['text'] = strip_tags(stripslashes($_POST['mywidget-text']));


            // If original widget options do not match control form
            // submission options, update them.
            if ( $options != $newoptions ) {
              $options = $newoptions;
              update_option('widget_sqsidebar', $options);
            }
        }

        // Format options as valid HTML. Hey, why not.
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        //$text = htmlspecialchars($options['text'], ENT_QUOTES);

// The HTML below is the control form for editing options.
?>
        <div>
        <label for="sqsidebar-title" style="line-height:35px;display:block;">Widget title: <input type="text" id="sqsidebar-title" name="sqsidebar-title" value="<?php echo $title; ?>" /></label>
        <input type="hidden" name="sqsidebar-submit" id="sqsidebar-submit" value="1" />
        </div>
    <?php
    // end of widget_sqsidebar_control()
    }

    // This registers the widget. About time.
    register_sidebar_widget('Stock Quote Sidebar', 'widget_sqsidebar');

    // This registers the (optional!) widget control form.
    register_widget_control('Stock Quote Sidebar', 'widget_sqsidebar_control');
}

// Delays plugin execution until Dynamic Sidebar has loaded first.
add_action('plugins_loaded', 'widget_sqsidebar_init');

?>