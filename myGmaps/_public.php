<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of myGmaps, a plugin for Dotclear 2.
#
# Copyright (c) 2014 Philippe aka amalgame
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

$core->addBehavior('publicEntryAfterContent',array('myGmapsPublic','publicMapContent'));
$core->addBehavior('publicPageAfterContent',array('myGmapsPublic','publicMapContent'));
$core->addBehavior('publicHeadContent',array('myGmapsPublic','publicHeadContent'));

$core->tpl->addValue('myGmaps',array('myGmapsPublic','publicTagMapContent'));

class myGmapsPublic
{
	public static function hasMap ($post_id)
	{
		global $core;
		$meta =& $core->meta;
		$my_params['post_id'] = $post_id;
		$my_params['no_content'] = true;
		$my_params['post_type'] = array('post','page');
					
		$rs = $core->blog->getPosts($my_params);
		return $meta->getMetaStr($rs->post_meta,'map_options');
	}
	public static function thisPostMap ($post_id)
	{
		global $core;
		$meta =& $core->meta;
		$my_params['post_id'] = $post_id;
		$my_params['no_content'] = true;
		$my_params['post_type'] = array('post','page');
					
		$rs = $core->blog->getPosts($my_params);
		return $meta->getMetaStr($rs->post_meta,'map');
	}
	public static function thisPostMapType ($post_id)
	{
		global $core;
		$meta =& $core->meta;
		$my_params['post_id'] = $post_id;
		$my_params['no_content'] = true;
		$my_params['post_type'] = 'map';
					
		$rs = $core->blog->getPosts($my_params);
		return $meta->getMetaStr($rs->post_meta,'map');
	}
	public static function publicHeadContent($core,$_ctx)
	{
		# Settings
		global $core;
		$s =& $core->blog->settings->myGmaps;
		$url = $core->blog->getQmarkURL().'pf='.basename(dirname(__FILE__));
		if ($s->myGmaps_enabled) {
			echo 
				'<script type="text/javascript" src="http://maps.google.com/maps/api/js?libraries=weather&amp;sensor=false"></script>'."\n".
				'<link rel="stylesheet" type="text/css" href="'.$url.'/css/public.css" />'."\n";
		}
	}
	public static function publicMapContent($core,$_ctx,$aElements = array())
	{
		# Settings
		global $core;
		$s =& $core->blog->settings->myGmaps;
		$url = $core->blog->getQmarkURL().'pf='.basename(dirname(__FILE__));
		
		$sTemplate = '';

		// Appel depuis un billet, ou depuis une balise de template
		$sPostId = (! empty($aElements) ) ? $aElements['id'] : $_ctx->posts->post_id ;

		if ($s->myGmaps_enabled /*&& self::hasMap($sPostId) != ''*/) {
			
			// Map styles. Get more styles from http://snazzymaps.com/
			
			$public_path = $core->blog->public_path;
			$public_url = $core->blog->settings->system->public_url;
			$blog_url = $core->blog->url;
			
			$map_styles_dir_path = $public_path.'/myGmaps/styles/';
			$map_styles_dir_url = http::concatURL($core->blog->url,$public_url.'/myGmaps/styles/');

			if(is_dir($map_styles_dir_path)) {
				$map_styles = glob($map_styles_dir_path."*.js");
				$map_styles_list = array();
				foreach($map_styles as $map_style){
					$map_style = basename($map_style);
					array_push($map_styles_list,$map_style);
				}
				$map_styles_list = implode(",",$map_styles_list);
				$map_styles_base_url = $map_styles_dir_url;
			} else {
				$map_styles_list = '';
				$map_styles_base_url = '';
			}
			
			// Map type
			$custom_style = false;
			
			// Appel depuis un billet, ou depuis une balise de template
			$aOptions = array();
			if (! empty($aElements) ) {
				$aOptions = $aElements;
			}
			else {
				$meta =& $GLOBALS['core']->meta;
				$post_map_options = explode(",",$meta->getMetaStr($_ctx->posts->post_meta,'map_options'));
				$aOptions = array(
		 			'center' => $post_map_options[0].','.$post_map_options[1],
		 			'zoom' => $post_map_options[2],
		 			'style' => $post_map_options[3],
		 			'map_elements' => $aElements,
		 			'map_element_category' => $aCategories
		 		);
			}
			
			
			if ($aOptions['style'] == 'roadmap') {
				$aOptions['style'] = 'google.maps.MapTypeId.ROADMAP';
			} elseif ($aOptions['style'] == 'satellite') {
				$aOptions['style'] = 'google.maps.MapTypeId.SATELLITE';
			} elseif ($aOptions['style'] == 'hybrid') {
				$aOptions['style'] = 'google.maps.MapTypeId.HYBRID';
			} elseif ($aOptions['style'] == 'terrain') {
				$aOptions['style'] = 'google.maps.MapTypeId.TERRAIN';
			}  elseif ($aOptions['style'] == 'OpenStreetMap') {
				$aOptions['style'] = 'OpenStreetMap';
			} else {
				$aOptions['style'] = $aOptions['style'];
				$custom_style = true;
			}
			
			// Create map and listener
			
			$sTemplate .=  
			'<script type="text/javascript">'."\n".
			"//<![CDATA[\n".
			'$(function () {'."\n";
			
			if ($aOptions['style'] == 'neutral_blue') {
				$sTemplate .= 
				'var neutral_blue_styles = [{"featureType":"water","elementType":"geometry","stylers":[{"color":"#193341"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#2c5a71"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#29768a"},{"lightness":-37}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#406d80"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#406d80"}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#3e606f"},{"weight":2},{"gamma":0.84}]},{"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"weight":0.6},{"color":"#1a3541"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#2c5a71"}]}];'."\n".
				'var neutral_blue = new google.maps.StyledMapType(neutral_blue_styles,{name: "Neutral Blue"});'."\n";
			
			} elseif ($aOptions['style'] != 'neutral_blue' && $custom_style) {
				if(is_dir($map_styles_dir_path)) {
					
					$map_style_content = file_get_contents($map_styles_dir_path.'/'.$aOptions['style'].'_styles.js');
					$var_styles_name = $aOptions['style'].'_styles';
					$var_name = preg_replace('/_styles/s', '', $var_styles_name);
					$nice_name = ucwords(preg_replace('/_/s', ' ', $var_name));
					$sTemplate .= 
					'var '.$var_styles_name.' = '.$map_style_content.';'."\n".
					'var '.$var_name.' = new google.maps.StyledMapType('.$var_styles_name.',{name: "'.$nice_name.'"});'."\n";
					
				}
			}
			
				$sTemplate .= 
				'var myOptions = {'."\n".
					'zoom: parseFloat('.$aOptions['zoom'].'),'."\n".
					'center: new google.maps.LatLng('.$aOptions['center'].'),'."\n".
					'scrollwheel: false,'."\n".
					'mapTypeControl: false,'."\n".
					'mapTypeControlOptions: {'."\n".
						'mapTypeIds: ["'.$aOptions['style'].'"]'."\n".
					'}'."\n".
				'};'."\n".
				'var map_'.$sPostId.' = new google.maps.Map(document.getElementById("map_canvas_'.$sPostId.'"), myOptions);'."\n";
				
				if ($custom_style) {
					$sTemplate .= 
					'map_'.$sPostId.'.mapTypes.set("'.$aOptions['style'].'", '.$aOptions['style'].');'."\n".
					'map_'.$sPostId.'.setMapTypeId("'.$aOptions['style'].'");'."\n";

				} elseif ($custom_style == false && $aOptions['style'] == 'OpenStreetMap') {
					$sTemplate .= 
					'var credit = \'<a href="http://www.openstreetmap.org/copyright">© OpenStreetMap Contributors</a>\';'."\n".
					'var creditNode = document.createElement(\'div\');'."\n".
					'creditNode.id = \'credit-control\';'."\n".
					'creditNode.index = 1;'."\n".
					'map_'.$sPostId.'.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(creditNode);'."\n".
					'creditNode.innerHTML = credit;'."\n".
					'map_'.$sPostId.'.mapTypes.set("OpenStreetMap", new google.maps.ImageMapType({'."\n".
						'getTileUrl: function(coord, zoom) {'."\n".
							'return "http://tile.openstreetmap.org/" + zoom + "/" + coord.x + "/" + coord.y + ".png";'."\n".
						'},'."\n".
						'tileSize: new google.maps.Size(256, 256),'."\n".
						'name: "OpenStreetMap",'."\n".
						'maxZoom: 18'."\n".
					'}));'."\n".
					'map_'.$sPostId.'.setMapTypeId("'.$aOptions['style'].'");'."\n";
				} else {
					$sTemplate .= 
					'map_'.$sPostId.'.setOptions({mapTypeId: '.$aOptions['style'].'});'."\n";
				}
				
				$sTemplate .= 
				'var infowindow_'.$sPostId.' = new google.maps.InfoWindow({});'."\n".
				'google.maps.event.addListener(map_'.$sPostId.', "click", function (event) {'."\n".
					'infowindow_'.$sPostId.'.close();'."\n".
				'});'."\n";
			
			// Get map elements
			
			$maps_array = explode(",",self::thisPostMap($sPostId));
			
			$params['post_type'] = 'map';
			$params['post_status'] = '1';
			$maps = $core->blog->getPosts($params);
			
			$has_marker = false;
			$has_poly = false;
			
			while ($maps->fetch()) {
				if (in_array($maps->post_id,$maps_array)) {
					
					// Common element vars
					
					$list = explode("\n",html::clean($maps->post_excerpt_xhtml));
					$content = str_replace("\\", "\\\\", $maps->post_content_xhtml);
					$content = str_replace(array("\r\n", "\n", "\r"),"\\n",$content);
					$content = str_replace(array("'"),"\'",$content);
					
					$meta =& $core->meta;
					$description = $meta->getMetaStr($maps->post_meta,'description');
					
					
					$type = self::thisPostMapType($maps->post_id);
					
					if ($description == 'none') {
						$content = '';
					}
					
					$sTemplate .= 
					
					'var title_'.$maps->post_id.' = "'.html::escapeHTML($maps->post_title).'";'."\n".
					'var content_'.$maps->post_id.' = \''.$content.'\';'."\n";
					
					// Place element
					
					
					
					if ($type == 'point of interest') {
						$marker = explode("|",$list[0]);
						$sTemplate .= 
						'marker = new google.maps.Marker({'."\n".
							'icon : "'.$marker[2].'",'."\n".
							'position: new google.maps.LatLng('.$marker[0].','.$marker[1].'),'."\n".
							'title: title_'.$maps->post_id.','."\n".
							'map: map_'.$sPostId."\n".
						'});'."\n".
						'google.maps.event.addListener(marker, "click", function() {'."\n".
							'openmarkerinfowindow(this,title_'.$maps->post_id.',content_'.$maps->post_id.');'."\n".
						'});'."\n";
						$has_marker = true;		
						
					} elseif ($type == 'polyline') {
												
						$parts = explode("|",array_pop($list));
						$coordinates = '';
						$points = $list;
						
						foreach($points as $point){
							$coord = explode("|",$point);
							$coordinates .= 'new google.maps.LatLng('.$coord[0].','.$coord[1].'),';
						}
						$path = substr($coordinates, 0, -1);

						$sTemplate .= 
						'var polyline = new google.maps.Polyline({'."\n".
							'path: ['.$path.'],'."\n".
							'strokeColor: "'.$parts[2].'",'."\n".
							'strokeOpacity: '.$parts[1].','."\n".
							'strokeWeight: '.$parts[0].''."\n".
						'});'."\n".
						'polyline.setMap(map_'.$sPostId.');'."\n".
						'google.maps.event.addListener(polyline, "click", function(event) {'."\n".
							'var pos = event.latLng;'."\n".
							'openpolyinfowindow(title_'.$maps->post_id.',content_'.$maps->post_id.',pos);'."\n".
						'});'."\n";
						$has_poly = true;
					
					} elseif ($type == 'polygon') {
						
						$parts = explode("|",array_pop($list));
						$coordinates = '';
						$points = $list;
						
						foreach($points as $point){
							$coord = explode("|",$point);
							$coordinates .= 'new google.maps.LatLng('.$coord[0].','.$coord[1].'),';
						}
						$path = substr($coordinates, 0, -1);
						
						$sTemplate .= 
						'var polygon = new google.maps.Polygon({'."\n".
							'path: ['.$path.'],'."\n".
							'strokeColor: "'.$parts[2].'",'."\n".
							'strokeOpacity: '.$parts[1].','."\n".
							'strokeWeight: '.$parts[0].','."\n".
							'fillColor: "'.$parts[3].'",'."\n".
							'fillOpacity: '.$parts[4].''."\n".
						'});'."\n".
						'polygon.setMap(map_'.$sPostId.');'."\n".						
						'google.maps.event.addListener(polygon, "click", function(event) {'."\n".
							'var pos = event.latLng;'."\n".
							'openpolyinfowindow(title_'.$maps->post_id.',content_'.$maps->post_id.',pos);'."\n".
						'});'."\n";
						$has_poly = true;

					} elseif ($type == 'rectangle') {
						
						$parts = explode("|",array_pop($list));
						$coordinates = explode("|",$list[0]);
						
						
						$sTemplate .= 
						'var bounds = new google.maps.LatLngBounds('."\n".
						  'new google.maps.LatLng('.$coordinates[0].', '.$coordinates[1].'),'."\n".
						  'new google.maps.LatLng('.$coordinates[2].', '.$coordinates[3].'));'."\n".
						'var rectangle = new google.maps.Rectangle({'."\n".
							'strokeColor: "'.$parts[2].'",'."\n".
							'strokeOpacity: '.$parts[1].','."\n".
							'strokeWeight: '.$parts[0].','."\n".
							'fillColor: "'.$parts[3].'",'."\n".
							'fillOpacity: '.$parts[4].''."\n".
						'});'."\n".
						'rectangle.setBounds(bounds);'."\n".
						'rectangle.setMap(map_'.$sPostId.');'."\n".						
						'google.maps.event.addListener(rectangle, "click", function(event) {'."\n".
							'var pos = event.latLng;'."\n".
							'openpolyinfowindow(title_'.$maps->post_id.',content_'.$maps->post_id.',pos);'."\n".
						'});'."\n";
						$has_poly = true;
					
					} elseif ($type == 'circle') {
						
						$parts = explode("|",array_pop($list));
						$coordinates = explode("|",$list[0]);
						
						$sTemplate .= 
						
						'var circle = new google.maps.Circle({'."\n".
							'center: new google.maps.LatLng('.$coordinates[0].', '.$coordinates[1].'),'."\n".
							'radius: '.$coordinates[2].','."\n".
							'strokeColor: "'.$parts[2].'",'."\n".
							'strokeOpacity: '.$parts[1].','."\n".
							'strokeWeight: '.$parts[0].','."\n".
							'fillColor: "'.$parts[3].'",'."\n".
							'fillOpacity: '.$parts[4].''."\n".
						'});'."\n".
						'circle.setMap(map_'.$sPostId.');'."\n".						
						'google.maps.event.addListener(circle, "click", function(event) {'."\n".
							'var pos = event.latLng;'."\n".
							'openpolyinfowindow(title_'.$maps->post_id.',content_'.$maps->post_id.',pos);'."\n".
						'});'."\n";
						$has_poly = true;
						
					} elseif ($type == 'included kml file') {
						
						$layer = html::clean($maps->post_excerpt_xhtml);
						$sTemplate .= 
						'layer = new google.maps.KmlLayer("'.$layer.'", {'."\n".
							'preserveViewport: true'."\n".
						'});'."\n".
						'layer.setMap(map_'.$sPostId.');'."\n";
						
					} elseif ($type == 'GeoRSS feed') {
						
						$layer = html::clean($maps->post_excerpt_xhtml);
						$sTemplate .= 
						'layer = new google.maps.KmlLayer("'.$layer.'", {'."\n".
							'preserveViewport: true'."\n".
						'});'."\n".
						'layer.setMap(map_'.$sPostId.');'."\n";
					
					} elseif ($type == 'directions') {
						
						$parts = explode("|",$list[0]);
						
						$sTemplate .= 						
						'var routePolyline;'."\n".
						'var routePolylineOptions = {'."\n".
							'strokeColor: \'#555\','."\n".
							'strokeOpacity: 0,'."\n".
							'strokeWeight: 20,'."\n".
							'zIndex: 1'."\n".
						'};'."\n".
						
						'routePolyline = new google.maps.Polyline(routePolylineOptions);'."\n".
						'var routePolylinePath = routePolyline.getPath();'."\n".
						
						'var directionsService = new google.maps.DirectionsService();'."\n".
						
						'var polylineRendererOptions = {'."\n".
							'strokeColor: "'.$parts[4].'",'."\n".
							'strokeOpacity: parseFloat('.$parts[3].'),'."\n".
							'strokeWeight: parseFloat('.$parts[2].')'."\n".
						'}'."\n".
						
						'var rendererOptions = {'."\n".
							'polylineOptions: polylineRendererOptions'."\n".
						'}'."\n".
						
						'var directionsDisplay = new google.maps.DirectionsRenderer(rendererOptions);'."\n".
						
						'var request = {'."\n".
						  'origin: "'.$parts[0].'",'."\n".
						  'destination: "'.$parts[1].'",'."\n".
						  'travelMode: google.maps.TravelMode.DRIVING'."\n".
						'};'."\n".
						
						'$("#map_box_'.$sPostId.'").addClass( "directions" );'."\n".
						
						'directionsService.route(request, function(result, status) {'."\n".
							'if (status == google.maps.DirectionsStatus.OK) {'."\n".
								'var routePath = result.routes[0].overview_path;'."\n".
								'routePolyline.setPath(routePath);'."\n".
								'directionsDisplay.setPanel(document.getElementById(\'panel_'.$sPostId.'\'));'."\n".
								'directionsDisplay.setOptions({options: rendererOptions});'."\n".
								'directionsDisplay.setDirections(result);'."\n".
								'directionsDisplay.setMap(map_'.$sPostId.');'."\n".
								'routePolyline.setMap(map_'.$sPostId.');'."\n".								
							'} else {'."\n".
								'alert(status);'."\n".
							'}'."\n".
							
						'});'."\n".
						
						'google.maps.event.addListener(routePolyline, "click", function(event) {'."\n".
							'var pos = event.latLng;'."\n".
							'openpolyinfowindow(title_'.$maps->post_id.',content_'.$maps->post_id.',pos);'."\n".
						'});'."\n";
						$has_poly = true;
					
					} elseif ($type == 'weather') {
						
						$sTemplate .= 
						'var weatherLayer = new google.maps.weather.WeatherLayer({'."\n".
							'temperatureUnits: google.maps.weather.TemperatureUnit.CELSIUS,'."\n".
							'windSpeedUnits: google.maps.weather.WindSpeedUnit.KILOMETERS_PER_HOUR'."\n".
						'});'."\n".
						'weatherLayer.setMap(map_'.$sPostId.');'."\n";
					}
				}
			}
			
			if ($has_marker) {
				$sTemplate .= 
					'function openmarkerinfowindow(marker,title,content) {'."\n".
						'infowindow_'.$sPostId.'.setContent('."\n".
							'"<h3>"+title+"</h3>"+'."\n".
							'"<div class=\"post-infowindow\" id=\"post-infowindow_'.$sPostId.'\">"+content+"</div>"'."\n".
						');'."\n".
						'infowindow_'.$sPostId.'.open(map_'.$sPostId.', marker);'."\n".
						'$("#post-infowindow_'.$sPostId.'").parent("div", "div#map_canvas_'.$sPostId.'").css("overflow","hidden");'."\n".
					'}'."\n";
			}
			
			if ($has_poly) {
				$sTemplate .= 
					'function openpolyinfowindow(title,content,pos) {'."\n".
						'infowindow_'.$sPostId.'.setPosition(pos);'."\n".
						'infowindow_'.$sPostId.'.setContent('."\n".
							'"<h3>"+title+"</h3>"+'."\n".
							'"<div class=\"post-infowindow\" id=\"post-infowindow_'.$sPostId.'\">"+content+"</div>"'."\n".
						');'."\n".
						'infowindow_'.$sPostId.'.open(map_'.$sPostId.');'."\n".
						'$("#post-infowindow_'.$sPostId.'").parent("div", "div#map_canvas_'.$sPostId.'").css("overflow","hidden");'."\n".
					'}'."\n";
			}
			
			$sTemplate .= 	
				'});'."\n".
				"\n//]]>\n".
				"</script>\n".
				'<noscript>'."\n".
				'<p>'.__('Sorry, javascript must be activated in your browser to see this map.').'</p>'."\n".
				'</noscript>'."\n".
				'<div id="map_box_'.$sPostId.'"><div id="map_canvas_'.$sPostId.'" class="map_canvas"></div><div id="panel_'.$sPostId.'" class="panel"></div></div>'."\n";
			
		}

		if (is_null($_ctx) ) { return $sTemplate; }
		else { echo $sTemplate; }
	}
	public static function publicTagMapContent($attr, $content)
	{
		global $core;

		// id="home" center="latlng" zoom="x" style="style_name" elements="id,id,id,id" category="id,id,id"
		// Récupérer tous les filtres
		$sId = isset($attr['id']) ? $attr['id'] : substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
		$sCenter = isset($attr['center']) ? addslashes($attr['center']) : '';
		$iZoom = isset($attr['zoom']) ? (integer) $attr['zoom'] : '12';
		$sStyle = isset($attr['style']) ? $attr['style'] : '';
		$aElements = isset($attr['elements']) ? explode(',', $attr['elements']) : array();
		$aCategories = isset($attr['category']) ? explode(',', $attr['category']) : array();

		// Récupérer tous les éléments de cartes à afficher
		// Retourner le code JS pour afficher la carte sous forme de PHP (pour ne pas le mettre en cache)
		//$sTemplate = '';

 		return self::publicMapContent($core, null, array(
 			'id' => $sId,
 			'center' => $sCenter,
 			'zoom' => $iZoom,
 			'style' => $sStyle,
 			'map_elements' => $aElements,
 			'map_element_category' => $aCategories
 		));
	}
}

?>
