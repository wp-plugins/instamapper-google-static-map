<?php
/*
Plugin Name: iPhone Google Map Tracking
Plugin URI: http://www.geekant.co.uk/2008/08/17/wordpress-instamapper-google-maps-plugin
Description: Displays Google static map based on latest updates to www.instamapper.com. Config can be found under Design; Widgets.
Version: 1.3.1
Author: Andy Whitlock
Author URI: http://www.andywhitlock.co.uk/


CHANGE LOG
	- Version 1.3.1
		* Made plugin XHTML Strict compliant
	- Version 1.3
		* Cache Google Map (static) locally (to protect exact location when pins are disabled)
		* Added further debug features
		* Added a link back to creator
		* Added ability to enable / disable link back
	- Version 1.2
		* Added a debug feature to display HTML comments on live sites to assist with debugging
		* Added a debug enable / disable option to the widget
*/

function gmt_pp(
    $title,
    $imapi=NULL,
	$gmapi=NULL,
    $width=250,
	$height=250,
    $pincolour='red',
    $pinletter='a',
	$exact=0,
    $zoom=10,
	$debug=1,
	$controls=0,
	$link=1
    ) {
 

$mapNumber=isset($_GET['imap'])?intval($_GET['imap']):1;

$last=$mapNumber+1;
$next=$mapNumber>1?$mapNumber-1:false;

$url='http://www.instamapper.com/api?action=getPositions&key='.$imapi.'&num='.$mapNumber;


$ch = curl_init();
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$rawdata = curl_exec ($ch);
curl_close ($ch);


	if($rawdata=='Bad request'){
		$valid=false;
	}else{
		//convert the new line return to BR for exploding
		$data=nl2br($rawdata);
		$lines=explode('<br />', $data);
	
		$valid=false;
		//go through each return
		foreach($lines as $data){
			//if a valid CSV
			if(stristr($data, ',') && !$valid){
				list($key, $label, $timestamp, $lat, $long, $alt, $speed, $deg)=explode(',', $data);
				
				if(!$valid){
					$glat=$lat;
					$glong=$long;
					$valid=true;
				}
			}
		}
	}
		
	if($valid){
		$marker=($exact)?'&markers='.$lat.','.$long.','.$pincolour.$pinletter:'';
		$gmurl='http://maps.google.com/staticmap?center='.$lat.','.$long.'&amp;zoom='.$zoom.'&amp;size='.$width.'x'.$height.'&amp;maptype=mobile'.$marker.'&amp;key='.$gmapi;
		
//		$file='./gm-map'.$mapNumber.'.gif';
//		$tmp=file_get_contents($gmurl);
//		file_put_contents($file, $tmp);

		echo '<div id="gmap-container">';
			$file=is_file($file)?'/gm-map'.$mapNumber.'.gif':$gmurl;
			
			echo '<img src="'.$file.'" id="iphone-map" alt="Latest mapped location" />';
			if($controls){
			$page=($_SERVER['QUERY_STRING']=='')?$_SERVER['PHP_SELF'].'?':$_SERVER['PHP_SELF'].'?'.preg_replace("/imap=+[0-9]/", '', $_SERVER['QUERY_STRING']);


			echo '<div id="controls" style="width:100%; margin-top:3px; font-size:0.9em;">';
				echo '<div id="left" style="float:left; width:20%; text-align:left; font-weight:bold;">';

					echo ($last)?'<a href="'.$page.'imap='.$last.'#gmap-container" id="left-arrow">&lt;&nbsp;&lt;</a>':'&lt;&nbsp;&lt;';
				echo '</div>';
				echo '<div id="date" style="float:left; width:60%">'.date('d M Y', $timestamp).'</div>';
				echo '<div id="right" style="float:left; width:20%; text-align:right; font-weight:bold;">';
					echo ($next)?'<a href="'.$page.'imap='.$next.'#gmap-container" id="right-arrow">&gt;&nbsp;&gt;</a>':'&gt;&nbsp;&gt;';
				echo '</div>';
			echo '</div>';
			}
			echo ($link)?'<p style="font-size: 0.7em; float:left; clear:both; width:100%;"><a href="http://www.geekant.co.uk/2008/08/17/wordpress-instamapper-google-maps-plugin/">iPhone Google maps plugin</a> provided by <a href="http://www.geekant.co.uk/" title="Technology and web related blog">Geek Ant</a></p>':'';
		echo '</div>';
	}else{
		echo '<p>An error occured while returning the latest map, please update your location</p>';
	}

	if($debug){
		echo "\n\n<!-- DEBUG -->";
		echo "\n<!-- Instamapper Request:\t".$url." -->";
		echo "\n<!-- Google Request:\t".$gmurl." -->";
		echo "\n<!-- Instamapper Return -->";
		echo "\n<!-- \n".$rawdata." -->";
		echo "\n<!-- END DEBUG -->";
	}
}


function widget_gmt_init() {
	if (!function_exists('register_sidebar_widget')) return;

	function widget_gmt($args) {
		$before_widget='';
		$before_title='';
		$after_title='';
		$after_widget='';

		extract($args);


		$options = get_option('iphone-map');
		$title = $options['title'];
		
		echo $before_widget . $before_title . $title . $after_title;
		gmt_pp(
		    $options['title'],
		    $options['imapi'],
			$options['gmapi'],
			$options['width'],
			$options['height'],
		    $options['pincolour'],
		    $options['pinletter'],
			$options['exact'],
		    $options['zoom'],
			$options['debug'],
			$options['controls'],
			$options['link']
		);
		echo $after_widget;
	}

	function widget_gmt_control() {
		$options = get_option('iphone-map');
		if ( !is_array($options) )
			$options = array(
			'title'=>'iPhone Map',
			'imapi'=>'',
			'gmapi'=>'',
			'width'=>250,
			'height'=>250,
			'pincolour'=>'red',
			'pinletter'=>'a',
			'exact'=>0,
			'zoom'=>'10',
			'debug'=>1,
			'controls'=>0,
			'link'=>1
			);

		if ($_POST['gmt-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['gmt-title']));
			$options['imapi'] = strip_tags(stripslashes($_POST['gmt-imapi']));
			$options['gmapi'] = strip_tags(stripslashes($_POST['gmt-gmapi']));
			$options['width'] = strip_tags(stripslashes(intval($_POST['gmt-width'])));
			$options['height'] = strip_tags(stripslashes(intval($_POST['gmt-height'])));
			$options['pincolour'] = strip_tags(stripslashes($_POST['gmt-pincolour']));
			$options['pinletter'] = strip_tags(stripslashes($_POST['gmt-pinletter']));
			$options['exact'] = (isset($_POST['gmt-exact']))?1:0;
			$options['zoom'] = intval(strip_tags(stripslashes($_POST['gmt-zoom'])));
			$options['debug'] = (isset($_POST['gmt-debug']))?1:0;
			$options['controls'] = (isset($_POST['gmt-controls']))?1:0;
			$options['links'] = (isset($_POST['gmt-link']))?1:0;

			update_option('iphone-map', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$imapi = htmlspecialchars($options['imapi'], ENT_QUOTES);
		$gmapi = htmlspecialchars($options['gmapi'], ENT_QUOTES);
		$width = htmlspecialchars($options['width'], ENT_QUOTES);
		$height = htmlspecialchars($options['height'], ENT_QUOTES);
		
		$colours=array('red', 'orange', 'yellow', 'green', 'blue');
		$pcoptions='';
		foreach($colours as $colour){
			$pcoptions.='<option value="'.$colour.'"';
			if($colour==$options['pincolour']){
				$pcoptions.=' selected="selected"';
			}
			$pcoptions.='>'.$colour.'</option>';
		}
		
		$letters=array('', 'a', 'b', 'c', 'd', 'e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		$ploptions='';
		foreach($letters as $letter){
			$ploptions.='<option value="'.$letter.'"';
			if($letter==$options['pinletter']){
				$ploptions.=' selected="selected"';
			}
			$ploptions.='>'.$letter.'</option>';
		}

		$exact  = ($options['exact'])?' checked="checked"' : '';
		$debug  = ($options['debug'])?' checked="checked"' : '';
		
		$controls  = ($options['controls'])?' checked="checked"' : '';
		$link  = ($options['link'])?' checked="checked"' : '';

		$zoptions='';
		for($i=1; $i<15; $i++){
			$zoptions.='<option value="'.$i.'"';
			if($i==$options['zoom']){
				$zoptions.=' selected="selected"';
			}
			$zoptions.='>'.$i.'</option>';
		}
		
		echo '<p style="text-align:right; display: block;"><label for="gmt-title">Title: <input style="width: 200px;" id="gmt-title" name="gmt-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-imapi"><a href="https://www.instamapper.com/fe?page=register" target="_blank" title="You need an InstaMapper API key">InstaMapper API</a>: <input style="width: 200px;" id="gmt-api" name="gmt-imapi" type="text" value="'.$imapi.'" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-gmapi"><a href="http://code.google.com/apis/maps/signup.html" target="_blank" title="You need a Google Maps API key">GoogleMaps API</a>: <input style="width: 200px;" id="gmt-api" name="gmt-gmapi" type="text" value="'.$gmapi.'" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-pxsize">Map Width: <input style="width: 200px;" id="gmt-width" name="gmt-width" type="text" value="'.$width.'" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-height">Map Height: <input style="width: 200px;" id="gmt-height" name="gmt-height" type="text" value="'.$height.'" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-pincolour">Pin Colour: <select style="width: 200px;" id="gmt-pincolour" name="gmt-pincolour">'.$pcoptions.'</select></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-pinletter">Pin Letter: <select style="width: 200px;" id="gmt-pinletter" name="gmt-pinletter">'.$ploptions.'</select></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-exact">Show Pin: <input style="width: 200px;" id="gmt-exact" name="gmt-exact" type="checkbox" '.$exact.' value="1" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-zoom">Zoom Level: <select style="width: 200px;" id="gmt-zoom" name="gmt-zoom">'.$zoptions.'</select></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-controls">Show Controls: <input style="width: 200px;" id="gmt-controls" name="gmt-controls" type="checkbox" '.$controls.' value="1" /></label></p>';
		echo '<p style="text-align:right; display: block;"><label for="gmt-link">Link Back: <input style="width: 200px;" id="gmt-link" name="gmt-link" type="checkbox" '.$link.' value="1" /></label></p>';
		
		echo '<p style="text-align:right; display: block;"><label for="gmt-debug">Debug: <input style="width: 200px;" id="gmt-debug" name="gmt-debug" type="checkbox" '.$debug.' value="1" /></label></p>';

		echo '<input type="hidden" id="gmt-submit" name="gmt-submit" value="1" />';
	}		

	register_sidebar_widget('iPhone Map', 'widget_gmt');
	register_widget_control('iPhone Map', 'widget_gmt_control', 300, 100);
}



function iphone_map_wp_head() {
	echo '<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/iPhone-Map/jquery.js"></script>'."\n";
}
//inject requirements into wordpress header
add_action('wp_head', 'iphone_map_wp_head');

add_action('plugins_loaded', 'widget_gmt_init');