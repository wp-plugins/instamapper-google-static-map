<?php
/*
Plugin Name: InstaMapper Google Static Map
Plugin URI: http://www.geekant.co.uk/2008/08/17/wordpress-instamapper-google-maps-plugin
Description: Displays Google static map based on latest updates to www.instamapper.com. Config can be found under Design; Widgets.
Version: 1.1
Author: Andy Whitlock
Author URI: http://www.andywhitlock.co.uk/


CHANGE LOG
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
	$debug=1
    ) {


	$url='http://www.instamapper.com/api?action=getPositions&key='.$imapi.'&num=1';


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
		$gmurl='http://maps.google.co.uk/staticmap?center='.$lat.','.$long.'&zoom='.$zoom.'&size='.$width.'x'.$height.'&maptype=mobile'.$marker.'&key='.$gmapi;
		echo '<img src="'.$gmurl.'" />';

	}else{
		echo '<p>An error occured while returning the latest map, please update your location</p>';
	}

	if($debug){
		echo "\n\n<!-- DEBUG -->";
		echo "\n<!-- Instamapper Req:\t".$url." -->";
		echo "\n<!-- Google Req:\t".$gmurl." -->";
		echo "\n<!-- ----Instamapper Return---- -->";
		echo "\n<!-- \n".$rawdata." -->";
		echo "\n<!-- END DEBUG -->";
	}
}


function widget_gmt_init() {
	if (!function_exists('register_sidebar_widget')) return;

	function widget_gmt($args) {

		$options = get_option('gmtrack');
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
			$options['debug']
		);
		echo $after_widget;
	}

	function widget_gmt_control() {
		$options = get_option('gmtrack');
		if ( !is_array($options) )
			$options = array(
			'title'=>'GM Track',
			'imapi'=>'',
			'gmapi'=>'',
			'width'=>250,
			'height'=>250,
			'pincolour'=>'red',
			'pinletter'=>'a',
			'exact'=>0,
			'zoom'=>'10',
			'debug'=>1
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

			update_option('gmtrack', $options);
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

		$zoptions='';
		for($i=1; $i<15; $i++){
			$zoptions.='<option value="'.$i.'"';
			if($i==$options['zoom']){
				$zoptions.=' selected="selected"';
			}
			$zoptions.='>'.$i.'</option>';
		}
		
		echo '<p style="text-align:right;"><label for="gmt-title">Title: <input style="width: 200px;" id="gmt-title" name="gmt-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-imapi"><a href="https://www.instamapper.com/fe?page=register" target="_blank" title="You need an InstaMapper API key">InstaMapper API</a>: <input style="width: 200px;" id="gmt-api" name="gmt-imapi" type="text" value="'.$imapi.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-gmapi"><a href="http://code.google.com/apis/maps/signup.html" target="_blank" title="You need a Google Maps API key">GoogleMaps API</a>: <input style="width: 200px;" id="gmt-api" name="gmt-gmapi" type="text" value="'.$gmapi.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-pxsize">Map Width: <input style="width: 200px;" id="gmt-width" name="gmt-width" type="text" value="'.$width.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-height">Map Height: <input style="width: 200px;" id="gmt-height" name="gmt-height" type="text" value="'.$height.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-pincolour">Pin Colour: <select style="width: 200px;" id="gmt-pincolour" name="gmt-pincolour">'.$pcoptions.'</select></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-pinletter">Pin Letter: <select style="width: 200px;" id="gmt-pinletter" name="gmt-pinletter">'.$ploptions.'</select></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-exact">Show Pin: <input style="width: 200px;" id="gmt-exact" name="gmt-exact" type="checkbox" '.$exact.' value="1" /></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-zoom">Zoom Level: <select style="width: 200px;" id="gmt-zoom" name="gmt-zoom">'.$zoptions.'</select></label></p>';
		echo '<p style="text-align:right;"><label for="gmt-debug">Debug: <input style="width: 200px;" id="gmt-debug" name="gmt-debug" type="checkbox" '.$debug.' value="1" /></label></p>';

		echo '<input type="hidden" id="gmt-submit" name="gmt-submit" value="1" />';
	}		

	register_sidebar_widget('GM Track', 'widget_gmt');
	register_widget_control('GM Track', 'widget_gmt_control', 300, 100);
}

add_action('plugins_loaded', 'widget_gmt_init');