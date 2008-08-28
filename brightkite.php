<?php
/*
Plugin Name: Brightkite Location
Version: 1.0 Beta 3a
Plugin URI: http://evansims.com/projects/brightkite-location
Description: Displays a map of your last known location at Brightkite.com
Author: Evan Sims
Author URI: http://evansims.com
*/

	function bkUpdateFeed($user, $path, $critical = false) {

		$_user = sprintf("%u", crc32(md5($user . get_bloginfo('wpurl')))) . '.cache';

		if(!@is_writable("{$path}{$_user}")) {
			if(!@chmod("{$path}{$_user}", 0777)) {
				if($critical) echo("<p><strong>Brightkite Location Plugin</strong><br />The file {$_user} is not writable. Please ensure that your Brighkite Location plugin folder is writable, or create the file manually and apply appropriate write permissions.");
				return;
			}
		}

		$headers = "GET /people/{$user}/objects.rss HTTP/1.0\r\n";
		$headers .= "Host: brightkite.com\r\n";
		$headers .= "Connection: Close\r\n\r\n";

		$socket = @fsockopen("brightkite.com", 80, $errno, $errstr, 30);
		if(!$socket) {
			if($critical) echo '<p><strong>Brightkite Location Plugin</strong><br />Unable to reach to the Brightkite servers. Try again later.</p>';
			return false;
		} else {
			@fwrite($socket, $headers);
			$data = ''; while (!feof($socket)) $data .= @fgets($socket, 128);
			@fclose($socket);

			$data = trim(substr($data, strpos($data, "\r\n\r\n")));

			if(!$data) {
				if($critical) echo '<p><strong>Brightkite Location Plugin</strong><br />There was an error retrieving your feed. Brightkite may be down for maintenance. Please try again shortly.</p>';
				return false;
			}

			if(!function_exists('xmlize')) include("{$path}xml.php");
			if(!function_exists('xmlize')) echo '<p><strong>Brightkite Location Plugin</strong><br />Unable to load XML processing library.</p>';

			$xml = xmlize($data);

			if(!$xml || !isset($xml['rss']['#']['channel'][0]['#']['item'][0]['#']['title'])) {
				if($critical) echo '<p><strong>Brightkite Location Plugin</strong><br />Brightkite returned an unexpected message. Please check your username and try again.</p>';
				return false;
			} unset($data);

			$event = $xml['rss']['#']['channel'][0]['#']['item'][0]['#'];

			$data['link'] = $event['link'][0]['#'];

			$data['lat'] = $event['geo:lat'][0]['#'];
			$data['long'] = $event['geo:long'][0]['#'];

			if(isset($event['bk:placeAddress'][0]['#'])) {
				$data['place'] = $event['bk:placeAddress'][0]['#'];
				if(substr($data['place'], -4) == ' USA') {
					// Strip out zip if in the United States.
					$data['place'] = trim(substr($data['place'], 0, -4));
					if(substr($data['place'], -1) == ',' && is_numeric(substr($data['place'], -6, 5))) {
						$data['place'] = trim(substr($data['place'], 0, -7));
					}
				}
			}

			if($event['bk:eventType'][0]['#'] == 'photo' && isset($event['media:thumbnail'])) {
				$photo = $event['media:thumbnail'][0]['@'];

				$data['description'] = '<img src="' . $photo['url'] . '" width="' . $photo['width'] . '" height="' . $photo['height'] . '" alt="My most recent Brightkite checkin." />';
				if($event['bk:photoCaption'][0]['#']) $data['description'] .= '<br />' . htmlentities($event['bk:photoCaption'][0]['#']);
			} else {
				$data['description'] = htmlentities($event['description'][0]['#']);
			}

			unset($xml);

			$socket = @fopen("{$path}{$_user}", 'w');
			if(!$socket) {
				echo("<p><strong>Brightkite Location Plugin</strong><br />The file {$_user} is not writable. Please ensure that your Brighkite Location plugin folder is writable, or create the file manually and apply appropriate write permissions.");
				return;
			}

			@fwrite($socket, '<' . '?php $bkRenderDate = \'' . time() . '\'; $bkRenderData = \'' . addslashes(serialize($data)) . '\'; ?' . '>');
			@fclose($socket);

			return $data;
		}

	}

	function bkWordpressHeaders() {

		if(strpos(__FILE__, '\\wp-content')) {
			$path['css'] = substr(__FILE__, strpos(__FILE__, '\\wp-content'));
			$path['css'] = substr($path['css'], 0, strrpos($path['css'], '\\'));
		} else {
			$path['css'] = substr(__FILE__, strpos(__FILE__, '/wp-content'));
			$path['css'] = substr($path['css'], 0, strrpos($path['css'], '/'));
		}

		echo "\t" . '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . str_replace('\\', '/', $path['css']) . '/style.css" />' . "\n";

	}

	function bkRenderLocation($user, $api_key, $settings = array()) {

		$_user = sprintf("%u", crc32(md5($user . get_bloginfo('wpurl')))) . '.cache';

		if(strpos(__FILE__, '\\wp-content')) {
			$path['physical']  = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '\\'));
			$path['physical'] .= substr(__FILE__, strpos(__FILE__, '\\wp-content'));
			$path['physical'] = substr($path['physical'], 0, strrpos($path['physical'], '\\')) . '/';

			$path['css'] = substr(__FILE__, strpos(__FILE__, '\\wp-content'));
			$path['css'] = substr($path['css'], 0, strrpos($path['css'], '\\'));
		} else {
			$path['physical']  = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/'));
			$path['physical'] .= substr(__FILE__, strpos(__FILE__, '/wp-content'));
			$path['physical'] = substr($path['physical'], 0, strrpos($path['physical'], '/')) . '/';

			$path['css'] = substr(__FILE__, strpos(__FILE__, '/wp-content'));
			$path['css'] = substr($path['css'], 0, strrpos($path['css'], '/'));
		}
		if(!$path['physical']) { echo '<p><strong>Brightkite Location Plugin</strong><br />Unable to determine physical location.</p>'; return; }
		if(!$path['css']) { echo '<p><strong>Brightkite Location Plugin</strong><br />Unable to determine stylesheet location.</p>'; return; }

		$path['css'] = str_replace('\\', '/', $path['css']) . '/style.css';
		$data = null;
		$bkRenderDate = null;

		//if(!file_exists("{$path['physical']}{$_user}")) {
			$data = bkUpdateFeed($user, $path['physical'], true);
		//} else {
		//	include("{$path['physical']}{$_user}");
		//	if(!isset($bkRenderDate) || $bkRenderDate == null || (time() - $bkRenderDate > 600)) $data = bkUpdateFeed($user, $path['physical']);
		//	if(!$data) $data = unserialize(stripslashes($bkRenderData));
		//}

		if($data) {

			if(!isset($settings['map_width'])) $settings['map_width'] = 223;
			if(!isset($settings['map_height'])) $settings['map_height'] = 134;
			if(!isset($settings['map_zoom'])) $settings['map_zoom'] = 14;
			if(!isset($settings['map_beacon_color'])) $settings['map_beacon_color'] = 'red';
			if(!isset($settings['map_beacon_size'])) $settings['map_beacon_size'] = 'mid';

			if(!isset($settings['map_provider'])) $settings['map_provider'] = 'static';
			if($settings['map_provider'] != 'static' && $settings['map_provider'] != 'dynamic') $settings['map_provider'] = 'static';

			if($settings['map_provider'] == 'static') {

				if(!isset($settings['map_style'])) $settings['map_style'] = 'roadmap';
				if($settings['map_style'] != 'roadmap' && $settings['map_style'] != 'mobile') $settings['map_style'] = 'roadmap';

				echo '<p id="brightkite-map"><a href="' . $data['link'] . '"><img class="google-map-static" src="http://maps.google.com/staticmap?center=' . $data['lat'] . ',' . $data['long'] . '&zoom='  . $settings['map_zoom'] . '&size=' . $settings['map_width'] . 'x' . $settings['map_height'] . '&maptype=' . $settings['map_style'] . '&markers=' . $data['lat'] . ',' . $data['long'] . ',' . $settings['map_beacon_size'] . $settings['map_beacon_color'] . '&key=' . $api_key . '" alt="Map" /></a></p>';

			} elseif($settings['map_provider'] == 'dynamic') {

				if(!isset($settings['map_style'])) $settings['map_style'] = 'terrain';
				if($settings['map_style'] != 'terrain' && $settings['map_style'] != 'roadmap' && $settings['map_style'] != 'satellite' && $settings['map_style'] != 'hybrid') $settings['map_style'] = 'terrain';

				if($settings['map_style'] == 'terrain') $settings['map_style'] = 'G_PHYSICAL_MAP';
				elseif($settings['map_style'] == 'roadmap') $settings['map_style'] = 'G_NORMAL_MAP';
				elseif($settings['map_style'] == 'satellite') $settings['map_style'] = 'G_SATELLITE_MAP';
				elseif($settings['map_style'] == 'hybrid') $settings['map_style'] = 'G_HYBRID_MAP';

				echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=' . $api_key . '" type="text/javascript"></script>';
				echo '<div id="brightkite-map"><div id="brightkite-map-' . $user . '" style="width: ' . $settings['map_width']. 'px; height: ' . $settings['map_height']. 'px;"></div></div>';
				echo '<script type="text/javascript">if (GBrowserIsCompatible()) { var map = new GMap2(document.getElementById("brightkite-map-' . $user . '")); map.enableScrollWheelZoom(); map.setMapType(G_PHYSICAL_MAP); map.addControl(new GSmallZoomControl()); map.setCenter(new GLatLng(' . $data['lat'] . ', ' . $data['long'] . '), ' . $settings['map_zoom'] . '); var point = new GLatLng(' . $data['lat'] . ', ' . $data['long'] . '); map.addOverlay(new GMarker(point)); }</script>';

			}

			echo '<p id="brightkite-loc"><a href="' . $data['link'] . '">' . $data['place'] . '</a></p>';

			if(!strpos($data['description'], 'checked in @')) echo '<p id="brightkite-status">' . $data['description'] . '</p>';

		} else {

			echo("<p><strong>Brightkite Location Plugin</strong><br />Your feed could not be retrieved at this time. Try again shortly.");

		}

	}

	add_action('wp_head', 'bkWordpressHeaders');

?>
