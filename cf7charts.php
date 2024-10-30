<?php
/*
Plugin Name: Contact Form 7 Charts
Plugin URI: http://tuckerhall.com/2013/contact-form-7-charts-for-wordpress/
Description: This plugin generates automatic dashboard charts from Contact Form DB and Contact Form 7 forms activity.  Useful for visually tracking the performance of content marketing campaign conversions, surveys, feedback and contact forms, and visitor activity on your Wordpress website. 
Version: 1.0 8/1/13
Author: Guy Hagen, Tucker/Hall Public Relations and Strategic Communications
Author URI: http://tuckerhall.com
License: GPLv2 or later
*/


/* ###################################################################### */
/* Hooks and Enqueues */
/* ###################################################################### */



function cf7charts_plugin_check()
{
	if (!is_plugin_active('contact-form-7/wp-contact-form-7.php') || !is_plugin_active('contact-form-7-to-database-extension/contact-form-7-db.php'))
	{
		add_action( 'admin_notices', 'cf7charts_admin_notice' );
		deactivate_plugins("cf7charts/cf7charts.php");
	}
}
add_action( 'admin_init', 'cf7charts_plugin_check' );



function cf7charts_enqueue() {
		wp_register_style( 'cf7charts-style', plugins_url('cf7charts.css', __FILE__) );
		wp_enqueue_style( 'cf7charts-style' );
	
		// for google charts
		wp_register_script('google-charts', 'https://www.google.com/jsapi');
		wp_enqueue_script('google-charts');
}
add_action( 'admin_enqueue_scripts', 'cf7charts_enqueue' );
  
  
function cf7charts_add_dashboard_widgets() {	
	if (is_admin())
	{
		wp_add_dashboard_widget('cf7charts_monthly_dashboard_widget', 'Contact Forms 7: 6-Month Campaign Activity', 'cf7charts_monthly_stats');	
		wp_add_dashboard_widget('cf7charts_daily_dashboard_widget', 'Contact Forms 7: Daily Campaign Activity', 'cf7charts_daily_stats');
		wp_add_dashboard_widget('cf7charts_piechart_dashboard_widget', 'Contact Forms 7: Most Recent One Month Activity by Form', 'cf7charts_piechart');
	}
} 
add_action('wp_dashboard_setup', 'cf7charts_add_dashboard_widgets' );


/* ###################################################################### */
/* Dashboard Widgets */
/* ###################################################################### */
function cf7charts_daily_stats()
{
	cf7charts_generate_areachart("daily");
}

function cf7charts_monthly_stats()
{
	cf7charts_generate_areachart("monthly");
}

function cf7charts_piechart()
{
	cf7charts_generate_piechart("onemonth");
}



/* ###################################################################################################### */
function cf7charts_generate_linechart($which="daily")
{
	
	global $wpdb;
	$tablename=$wpdb->base_prefix."cf7dbplugin_submits";
	
	if ($which=="daily")
	{
		$period_interval="1";
		$period_label="daily";
		$interval = 60*60*24 ;// one day
		$date_format="M. d";
		$additionaloptions='gridlines:{color: "#333333", count:5}, showTextEvery:7,';
	}
	else
	{
		$period_interval="6";
		$period_label="monthly";
		$interval = 60*60*24*(365/12) ;// one month
		$date_format="M. y";
	}


	$start_unixtime= mktime(0, 0, 0, date("m", strtotime("-".$period_interval." month", time())), date ("d", strtotime("-".$period_interval." month", time())), date ("Y", strtotime("-".$period_interval." month", time())));
	$end_unixtime=time();
	
	$x=0;

	$q= 'select form_name  from '.$tablename.'
	where submit_time > unix_timestamp(CURRENT_TIMESTAMP - INTERVAL '.$period_interval.' MONTH)
	group by form_name';
	
	
	$r=$wpdb->get_results($q);
	if (!$r)
	{
		echo "<h4>Sorry! You haven't had any conversions over this period!</h4>";
	}
	else
	{
		foreach ($r as $row)
		{
			$forms[$row->form_name]=$row->form_name;
		}



		echo '
		<script type="text/javascript">
			  google.load("visualization", "1", {packages:["corechart"]});
			  google.setOnLoadCallback(drawChart_'.$period_label.');
			  function drawChart_'.$period_label.'() {
				var data_'.$period_label.' = google.visualization.arrayToDataTable([
				  ["Date", ';
			  
		foreach ($forms as $form)
		{
			if ($i++>0) {echo ","; }
			echo '"'.$form.'"';
		}        
		echo "]";
		$i=0;
	
	
		while ($start_unixtime < $end_unixtime) {
	
			$echodate=date($date_format,$start_unixtime);
			echo ",\n[".'"'.$echodate.'"';
		
			foreach ($forms as $form) {
				$q= 'select count(*) as itemcount from '.$tablename.'
					where field_name="Submitted From"
					and submit_time >= '.$start_unixtime.'
					and submit_time <  '.($start_unixtime+$interval).'
					and form_name= "'.$form.'"';
					$r=$wpdb->get_var($q);
					$mv=max($r, $mv);
				
					if ($r) {
						echo ', '.(0+$r).'';
					}
					else
					{
						echo ', 0';
					}
				
			}
			echo "]";
			$start_unixtime+=$interval;	
		}	
		echo '
			]);

			var options_'.$period_label.' = {
			  hAxis: '.$additionaloptions.'{minorGridlines:{color: "#333333" }, textStyle:{fontSize:9}},
			  vAxis: {title: "Conversions", minValue: 0, textStyle:{fontSize:9, color:"#333333"}},
			  legend: {position: "bottom"}
			};

			var chart_'.$period_label.' = new google.visualization.LineChart( document.getElementById("cf7charts_'.$period_label.'_chart"));
			chart_'.$period_label.'.draw(data_'.$period_label.', options_'.$period_label.');
		  }
		</script>
		<div id="cf7charts_'.$period_label.'_chart"></div>
		';
		social_buttons();
	}
}


/* ###################################################################################################### */
function cf7charts_generate_areachart($which="daily")
{
	global $wpdb;
	$tablename=$wpdb->base_prefix."cf7dbplugin_submits";
	
	if ($which=="daily")
	{
		$period_interval="1";
		$period_label="daily";
		$interval = 60*60*24 ;// one day
		$date_format="M. d";
		$additionaloptions='gridlines:{color: "#333333", count:5}, showTextEvery:7,';
	}
	else
	{
		$period_interval="6";
		$period_label="monthly";
		$interval = 60*60*24*(365/12) ;// one month
		$date_format="M. y";
	}


	$start_unixtime= mktime(0, 0, 0, date("m", strtotime("-".$period_interval." month", time())), date ("d", strtotime("-".$period_interval." month", time())), date ("Y", strtotime("-".$period_interval." month", time())));
	$end_unixtime=time();
	
	$x=0;

	$q= 'select form_name  from '.$tablename.'
	where submit_time > unix_timestamp(CURRENT_TIMESTAMP - INTERVAL '.$period_interval.' MONTH)
					and field_name="Submitted From"
	group by form_name';
	
	
	$r=$wpdb->get_results($q);
	if (!$r)
	{
		echo "<h4>Sorry! You haven't had any conversions over this period!</h4>";
	}
	else
	{
		foreach ($r as $row)
		{
			$forms[$row->form_name]=$row->form_name;
		}



		echo '	
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart'.$period_label.');
      
      
      
      function drawChart'.$period_label.'() {
            	
		var data_'.$period_label.' = google.visualization.arrayToDataTable([
		  ["Date", ';
			  
		foreach ($forms as $form)
		{
			if ($i++>0) {echo ","; }
			echo '"'.$form.'"';
		}        
		echo "]";
		$i=0;
	
	
		while ($start_unixtime < $end_unixtime) {
	
			$echodate=date($date_format,$start_unixtime);
			echo ",\n[".'"'.$echodate.'"';
		
			foreach ($forms as $form) {
				$q= 'select count(*) as itemcount from '.$tablename.'
					where field_name="Submitted From"
					and submit_time >= '.$start_unixtime.'
					and submit_time <  '.($start_unixtime+$interval).'
					and form_name= "'.$form.'"';
					$r=$wpdb->get_var($q);
					$mv=max($r, $mv);
				
					if ($r) {
						echo ', '.(0+$r).'';
					}
					else
					{
						echo ', 0';
					}
				
			}
			echo "]";
			$start_unixtime+=$interval;	
		}	
		echo '
			]);

			var options_'.$period_label.' = {
        	  height: "300",
        	  backgroundColor: "none",
			  hAxis: {'.$additionaloptions.'minorGridlines:{color: "#333333" }, textStyle:{fontSize:9}},
			  vAxis: {title: "Conversions", minValue: 0, textStyle:{fontSize:9, color:"#333333"}},
			  legend: {position: "bottom"}, isStacked:true 
			};

			var chart_'.$period_label.' = new google.visualization.AreaChart( document.getElementById("cf7charts_'.$period_label.'_chart"));
			chart_'.$period_label.'.draw(data_'.$period_label.', options_'.$period_label.');
        
			function resizechart_'.$period_label.'() {
				var chart_'.$period_label.' = new google.visualization.AreaChart( document.getElementById("cf7charts_'.$period_label.'_chart"));
				chart_'.$period_label.'.draw(data_'.$period_label.', options_'.$period_label.');
			 }
		  }
   		
   		
   		window.onresize = drawChart'.$period_label.';
   		
		</script>
		<div id="cf7charts_'.$period_label.'_chart"></div>
		';
		social_buttons();
	}
}


/* ###################################################################################################### */
function cf7charts_generate_piechart($which="onemonth")
{
	
	global $wpdb;
	$tablename=$wpdb->base_prefix."cf7dbplugin_submits";
	
	if ($which=="onemonth")
	{
		$period_interval="1";
		$period_label="monthly";
		$interval = 60*60*24*(365/12) ;// one month
		$date_format="M. d";
		$additionaloptions='gridlines:{color: "#333333", count:5}, showTextEvery:7,';
	}
	else
	{
		$period_interval="6";
		$period_label="monthly";
		$interval = 60*60*24*(365/12) ;// one month
		$date_format="M. y";
	}


	$start_unixtime= mktime(0, 0, 0, date("m", strtotime("-".$period_interval." month", time())), date ("d", strtotime("-".$period_interval." month", time())), date ("Y", strtotime("-".$period_interval." month", time())));
	$end_unixtime=time();
	
	$x=0;

	$q= 'select form_name, count(*) as conversions from '.$tablename.'
	where submit_time > unix_timestamp(CURRENT_TIMESTAMP - INTERVAL '.$period_interval.' MONTH)
	and field_name="Submitted From"
	group by form_name';
	
	
	$r=$wpdb->get_results($q);
	if (!$r)
	{
		echo "<h4>Sorry! You haven't had any conversions over this period!</h4>";
	}
	else
	{


		echo '
		<script type="text/javascript">
			  google.load("visualization", "1", {packages:["corechart"]});';
 		

 		echo ' 
			var piechart_'.$period_label.' = new google.visualization.PieChart( document.getElementById("cf7charts_'.$period_label.'_piechart"));
			//piechart_'.$period_label.'.draw(piedata_'.$period_label.', pieoptions_'.$period_label.');
			piechart_'.$period_label.'.draw.draw(data, options);
		  }
		</script>
		<div id="cf7charts_'.$period_label.'_piechart"></div>
		';
		echo '   <script type="text/javascript">
      google.setOnLoadCallback(drawPieChart);
      
      
      function drawPieChart() {
        var data = google.visualization.arrayToDataTable([
          ["Form", "Conversions"]
          ';
          
          		  
		foreach ($r as $row)
		{
			echo ', ';
			echo '["'.$row->form_name.'", '.($row->conversions+0).']';
		}
          
          
        echo '
        ]);

        var options = {
        	height: "400",
        	backgroundColor: "none"
        };

        var chart = new google.visualization.PieChart(document.getElementById(\'cf7charts_'.$period_label.'_piechart\'));
        chart.draw(data, options);
      }
      
      
      
     
    function resizeAllCharts()
    {
    	drawPieChart();
    	drawChartmonthly();
    	drawChartdaily();
    	
    } 
    window.onresize = resizeAllCharts;
    
    </script>';
		social_buttons();
	}
}

/* ###################################################################### */
/* Support Functions */
/* ###################################################################### */

function social_buttons()
{
	$accounts=array("tuckerhall", "guyhagen");
	//$twitter=$accounts[array_rand($accounts)];
	echo '<div class="cf7social"><span class="cf7socialtext">Like the plugin?  Like us on Twitter and G+!</span>';
	foreach ($accounts as $account)
	{
		echo '<a href="https://twitter.com/'.$account.'" class="twitter-follow-button" data-show-count="false" data-lang="en">@'.$account.'</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


';
	}

echo "<!-- Place this tag where you want the widget to render. -->
<div class='g-follow' data-annotation='bubble' data-height='20' data-href='//plus.google.com/101068183642853459944' data-rel='author'></div>

<!-- Place this tag after the last widget tag. -->
<script type='text/javascript'>
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>";


	echo '</div>';
}


function cf7charts_admin_notice()
{
	echo '<div id="message" class="error">
        <p>I\'m sorry, the <b>Contact Form 7 Charts</b> plugin could not be activated due to missing plugins!  This plugin requires that both the <b><a href="http://wordpress.org/plugins/contact-form-7">Contact Form 7</a></b> and the <b><a href="http://wordpress.org/plugins/contact-form-7-to-database-extension/">Contact Form 7 to Database Extension</a></b> plugins be installed and active!</p>
    </div>';

}

?>
