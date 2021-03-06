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

require_once DC_ROOT.'/inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$p_url	= 'plugin.php?p='.basename(dirname(__FILE__));

$default_tab = isset($_GET['tab']) ? $_GET['tab'] : 'entries-list';

$s =& $core->blog->settings->myGmaps;

$page_title = __('Google Maps');

$__autoload['adminMapsList'] = dirname(__FILE__).'/inc/lib.pager.php';
$__autoload['dcMapsActionsPage'] = dirname(__FILE__).'/inc/class.dcactionmaps.php';

# Custom map styles

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

# Getting categories
try {
	$categories = $core->blog->getCategories(array('post_type'=>'map'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting authors
try {
	$users = $core->blog->getPostsUsers();
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting dates
try {
	$dates = $core->blog->getDates(array('type'=>'month','post_type'=>'map'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting langs
try {
	$langs = $core->blog->getLangs();
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Creating filter combo boxes
if (!$core->error->flag())
{
	# Filter form we'll put in html_block
	$users_combo = array_merge(
		array('-' => ''),
		dcAdminCombos::getUsersCombo($users)
	);
	
	$categories_combo = array_merge(
		array(
			new formSelectOption('-',''),
			new formSelectOption(__('(No cat)'),'NULL')),
		dcAdminCombos::getCategoriesCombo($categories,false)
	);
	$categories_values = array();
	foreach ($categories_combo as $cat) {
		if (isset($cat->value)) {
			$categories_values[$cat->value]=true;
		}
	}

	$status_combo = array_merge(
		array('-' => ''),
		dcAdminCombos::getPostStatusesCombo()
	);
	
	$selected_combo = array(
	'-' => '',
	__('Selected') => '1',
	__('Not selected') => '0'
	);
	
	$attachment_combo = array(
	'-' => '',
	__('With attachments') => '1',
	__('Without attachments') => '0'
	);
	
	$element_type_combo = array(
	'-' => '',
	__('none') => 'none',
	__('point of interest') => 'point of interest',
	__('polyline') => 'polyline',
	__('polygon') => 'polygon',
	__('rectangle') => 'rectangle',
	__('circle') => 'circle',
	__('included kml file') => 'included kml file',
	__('GeoRSS feed') => 'GeoRSS feed',
	__('directions') => 'directions',
	__('weather') => 'weather'
	);
	
	# Months array
	$dt_m_combo = array_merge(
		array('-' => ''),
		dcAdminCombos::getDatesCombo($dates)
	);

	$lang_combo = array_merge(
		array('-' => ''),
		dcAdminCombos::getLangsCombo($langs,false)
	);

	$sortby_combo = array(
	__('Date') => 'post_dt',
	__('Title') => 'post_title',
	__('Category') => 'cat_title',
	__('Author') => 'user_id',
	__('Status') => 'post_status',
	__('Selected') => 'post_selected',
	__('Number of comments') => 'nb_comment',
	__('Number of trackbacks') => 'nb_trackback'
	);

	$order_combo = array(
	__('Descending') => 'desc',
	__('Ascending') => 'asc'
	);
}

# Actions combo box

$posts_actions_page = new dcMapsActionsPage($core,'plugin.php',array('p'=>'myGmaps','do' => 'list'));

if ($posts_actions_page->process()) {
	return;
}
/* Get posts
-------------------------------------------------------- */
$id = !empty($_GET['id']) ?	$_GET['id'] : '';
$user_id = !empty($_GET['user_id']) ?	$_GET['user_id'] : '';
$cat_id = !empty($_GET['cat_id']) ?	$_GET['cat_id'] : '';
$status = isset($_GET['status']) ?	$_GET['status'] : '';
$selected = isset($_GET['selected']) ?	$_GET['selected'] : '';
$attachment = isset($_GET['attachment']) ?	$_GET['attachment'] : '';
$lang = !empty($_GET['lang']) ?		$_GET['lang'] : '';
$month = !empty($_GET['month']) ?		$_GET['month'] : '';
$sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'post_dt';
$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';
$element_type = !empty($_GET['element_type']) ?		$_GET['element_type'] : '';

$show_filters = false;

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	if ($nb_per_page != $_GET['nb']) {
		$show_filters = true;
	}
	$nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;
$params['post_type'] = 'map';

# - User filter
if ($user_id !== '' && in_array($user_id,$users_combo)) {
	$params['user_id'] = $user_id;
	$show_filters = true;
} else {
	$user_id='';
}

# - Categories filter
if ($cat_id !== '' && isset($categories_values[$cat_id])) {
	$params['cat_id'] = $cat_id;
	$show_filters = true;
} else {
	$cat_id='';
}

# - Status filter
if ($status !== '' && in_array($status,$status_combo)) {
	$params['post_status'] = $status;
	$show_filters = true;
} else {
	$status='';
}

# - Selected filter
if ($selected !== '' && in_array($selected,$selected_combo)) {
	$params['post_selected'] = $selected;
	$show_filters = true;
} else {
	$selected='';
}

# - Selected filter
if ($attachment !== '' && in_array($attachment,$attachment_combo)) {
	$params['media'] = $attachment;
	$params['link_type'] = 'attachment';
	$show_filters = true;
} else {
	$attachment='';
}

# - Month filter
if ($month !== '' && in_array($month,$dt_m_combo)) {
	$params['post_month'] = substr($month,4,2);
	$params['post_year'] = substr($month,0,4);
	$show_filters = true;
} else {
	$month='';
}

# - Lang filter
if ($lang !== '' && in_array($lang,$lang_combo)) {
	$params['post_lang'] = $lang;
	$show_filters = true;
} else {
	$lang='';
}

# - Sortby and order filter
if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
	if ($order !== '' && in_array($order,$order_combo)) {
		$params['order'] = $sortby.' '.$order;
	} else {
		$order='desc';
	}

	if ($sortby != 'post_dt' || $order != 'desc') {
		$show_filters = true;
	}
} else {
	$sortby='post_dt';
	$order='desc';
}

# - Map type filter
if ($element_type != '' && in_array($element_type,$element_type_combo)) {
	$params['sql'] = "AND post_meta LIKE '%".$element_type."%' ";
	$show_filters = true;
} else {
	$element_type='';
}

# Get posts
try {
	$posts = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$post_list = new adminMapsList($core,$posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Save activation 
$myGmaps_enabled = $s->myGmaps_enabled;
$myGmaps_center = $s->myGmaps_center;
$myGmaps_zoom = $s->myGmaps_zoom;
$myGmaps_type = $s->myGmaps_type;

if (!empty($_POST['saveconfig'])) {
  try {
    $s->put('myGmaps_enabled',!empty($_POST['myGmaps_enabled']));

	$s->put('myGmaps_center',$_POST['myGmaps_center']);
	$s->put('myGmaps_zoom',$_POST['myGmaps_zoom']);	
	$s->put('myGmaps_type',$_POST['myGmaps_type']);
	
	http::redirect($p_url.'&do=list&tab=settings&upd=1');
	
  } catch (Exception $e) {
    $core->error->add($e->getMessage());
  }
  
}

/* DISPLAY
-------------------------------------------------------- */
?>
<html>
	<head>
		<title><?php echo $page_title; ?></title>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?libraries=places&amp;sensor=false"></script>
		<?php 
		$form_filter_title = __('Show filters and display options');
		$starting_script  = dcPage::jsLoad(DC_ADMIN_URL.'?pf=myGmaps/js/maps.list.js');
		$starting_script .= dcPage::jsLoad(DC_ADMIN_URL.'?pf=myGmaps/js/filter-controls.js');
		$starting_script .= dcPage::jsLoad(DC_ADMIN_URL.'?pf=myGmaps/js/config.map.js');
		$starting_script .= dcPage::jsPageTabs($default_tab);
		$starting_script .=
		'<script type="text/javascript">'."\n".
		"//<![CDATA["."\n".
		dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false')."\n".
		dcPage::jsVar('dotclear.msg.filter_posts_list',$form_filter_title)."\n".
		dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel filters and display options'))."\n".
		dcPage::jsVar('id',$id)."\n".
		"//]]>".
		"</script>";
		
		echo $starting_script;
		
		// Add default and user map styles
	
		echo
	'<script type="text/javascript">'."\n".
		'//<![CDATA['."\n";
	
	echo
		'var neutral_blue_styles = [{"featureType":"water","elementType":"geometry","stylers":[{"color":"#193341"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#2c5a71"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#29768a"},{"lightness":-37}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#406d80"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#406d80"}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#3e606f"},{"weight":2},{"gamma":0.84}]},{"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"weight":0.6},{"color":"#1a3541"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#2c5a71"}]}];'."\n".
		'var neutral_blue = new google.maps.StyledMapType(neutral_blue_styles,{name: "Neutral Blue"});'."\n";
		
	if(is_dir($map_styles_dir_path)) {
		$list = explode(',',$map_styles_list);
		foreach($list as $map_style){
			$map_style_content = file_get_contents($map_styles_dir_path.'/'.$map_style);
			$var_styles_name = pathinfo($map_style, PATHINFO_FILENAME);
			$var_name = preg_replace('/_styles/s', '', $var_styles_name);
			$nice_name = ucwords(preg_replace('/_/s', ' ', $var_name));
			echo
			'var '.$var_styles_name.' = '.$map_style_content.';'."\n".
			'var '.$var_name.' = new google.maps.StyledMapType('.$var_styles_name.',{name: "'.$nice_name.'"});'."\n";
		}
	}
		
	echo	
		'//]]>'."\n".
	'</script>';
		?>
		<link rel="stylesheet" type="text/css" href="<?php echo 'index.php?pf=myGmaps/css/admin.css' ?>" />
	</head>
	<body>
<?php

if (!$core->error->flag())
{

	echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Google Maps') => $p_url,
			$page_title => ''
		));	
		
	# Display messages

	if (isset($_GET['upd']))
	{

		$p_msg = '<p class="message">%s</p>';
		
		$a_msg = array(
			__('Configuration has been saved.'),
			__('Elements status has been successfully updated'),
			__('Elements have been successfully marked as selected'),
			__('Elements have been successfully marked as deselected'),
			__('Elements have been successfully deleted'),
			__('Elements category has been successfully changed'),
			__('Elements author has been successfully changed'),
			__('Elements language has been successfully changed')
		);
		
		$k = (integer) $_GET['upd']-1;
		
		if (array_key_exists($k,$a_msg)) {
			dcPage::success($a_msg[$k]);
		}
	}
	echo '<div class="multi-part" id="entries-list" title="'.__('Map elements').'">';
	
	echo '<p class="top-add"><strong><a class="button add" href="'.$p_url.'&amp;do=edit">'.__('New element').'</a></strong></p>';
	
	echo
	'<form action="'.$p_url.'" method="get" id="filters-form">'.
	'<h3 class="out-of-screen-if-js">'.$form_filter_title.'</h3>'.
	'<div class="table">'.
	'<div class="cell">'.
	'<h4>'.__('Filters').'</h4>'.
	'<p><label for="user_id" class="ib">'.__('Author:').'</label> '.
		form::combo('user_id',$users_combo,$user_id).'</p>'.
		'<p><label for="cat_id" class="ib">'.__('Category:').'</label> '.
		form::combo('cat_id',$categories_combo,$cat_id).'</p>'.
		'<p><label for="status" class="ib">'.__('Status:').'</label> ' .
		form::combo('status',$status_combo,$status).'</p> '.
	'</div>'.
	
	'<div class="cell filters-sibling-cell">'.
		'<p><label for="selected" class="ib">'.__('Selected:').'</label> '.
		form::combo('selected',$selected_combo,$selected).'</p>'.
		'<p><label for="element_type" class="ib">'.__('Type:').'</label> '.
		form::combo('element_type',$element_type_combo,$element_type).'</p>'.
		'<p><label for="month" class="ib">'.__('Month:').'</label> '.
		form::combo('month',$dt_m_combo,$month).'</p>'.
		'<p><label for="lang" class="ib">'.__('Lang:').'</label> '.
		form::combo('lang',$lang_combo,$lang).'</p> '.
	'</div>'.
	
	'<div class="cell filters-options">'.
		'<h4>'.__('Display options').'</h4>'.
		'<p><label for="sortby" class="ib">'.__('Order by:').'</label> '.
		form::combo('sortby',$sortby_combo,$sortby).'</p>'.
		'<p><label for="order" class="ib">'.__('Sort:').'</label> '.
		form::combo('order',$order_combo,$order).'</p>'.
		'<p><span class="label ib">'.__('Show').'</span> <label for="nb" class="classic">'.
		form::field('nb',3,3,$nb_per_page).' '.
		__('Map elements per page').'</label></p>'.
	'</div>'.
	'</div>'.
	
	'<p><input type="submit" name="maps_filters" value="'.__('Apply filters and display options').'" />'.
		'<br class="clear" />'. //Opera sucks
	form::hidden(array('p'),'myGmaps').
	form::hidden(array('tab'),'entries-list').
	$core->formNonce().
	'</p>'.
	'</form>';
	
	# Show posts
	$post_list->display($page,$nb_per_page,
	'<form action="'.$p_url.'" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected map elements action:').'</label> '.
	form::combo('action',$posts_actions_page->getCombo()).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden(array('user_id'),$user_id).
	form::hidden(array('cat_id'),$cat_id).
	form::hidden(array('status'),$status).
	form::hidden(array('selected'),$selected).
	form::hidden(array('attachment'),$attachment).
	form::hidden(array('month'),$month).
	form::hidden(array('lang'),$lang).
	form::hidden(array('sortby'),$sortby).
	form::hidden(array('order'),$order).
	form::hidden(array('page'),$page).
	form::hidden(array('nb'),$nb_per_page).
	form::hidden(array('tab'),'entries-list').
	form::hidden(array('post_type'),'map').
	form::hidden(array('p'),'myGmaps').
	$core->formNonce().
	'</div>'.
	'</form>',
	$show_filters
	);
	
	echo '</div>';
	
	echo '<div class="multi-part" id="settings" title="'.__('Settings').'">'.
	'<form method="post" action="'.$p_url.'" id="settings-form">'.
	'<div class="fieldset"><h3>'.__('Activation').'</h3>'.
		'<p><label class="classic" for="myGmaps_enabled">'.
		form::checkbox('myGmaps_enabled','1',$s->myGmaps_enabled).
		__('Enable extension for this blog').'</label></p>'.
	'</div>'.
	'<div class="fieldset"><h3>'.__('Default map options').'</h3>'.	
	'<div class="map_toolbar">'.__('Search:').'<span class="map_spacer">&nbsp;</span>'.
		'<input size="50" maxlength="255" type="text" id="address" class="qx" /><input id="geocode" type="submit" value="'.__('OK').'" />'.
	'</div>'.
	'<p class="area" id="map_canvas"></p>'.
	'<p class="form-note info maximal mapinfo" style="width: 100%">'.__('Choose default map center by moving target marker or searching a location. Choose zoom level and map type by using map controls.').'</p>'.
		'<p>'.
		'<input type="hidden" name="myGmaps_center" id="myGmaps_center" value="'.$myGmaps_center.'" />'.
		'<input type="hidden" name="myGmaps_zoom" id="myGmaps_zoom" value="'.$myGmaps_zoom.'" />'.
		'<input type="hidden" name="myGmaps_type" id="myGmaps_type" value="'.$myGmaps_type.'" />'.
		'<input type="text" class="hidden" id="map_styles_list" value="'.$map_styles_list.'" />'.
		'<input type="text" class="hidden" id="map_styles_base_url" value="'.$map_styles_base_url.'" />'.
		$core->formNonce().
		'</p></div>'.
		'<p><input type="submit" name="saveconfig" value="'.__('Save configuration').'" /></p>'.
	
	'</form>'.
	'</div>';
	
	
	
}

dcPage::helpBlock('myGmaps');
?>
	</body>
</html>