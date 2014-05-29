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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$_menu['Blog']->addItem(

	__('Google Maps'),
	'plugin.php?p=myGmaps&amp;do=list','index.php?pf=myGmaps/icon.png',
	preg_match('/plugin.php\?p=myGmaps(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('usage,contentadmin',$core->blog->id));
	
$core->addBehavior('adminDashboardFavs',array('myGmapsBehaviors','dashboardFavs'));
$core->addBehavior('adminPageHelpBlock', array('myGmapsBehaviors', 'adminPageHelpBlock'));

$__autoload['adminMapsMiniList'] = dirname(__FILE__).'/inc/lib.pager.php';

class myGmapsBehaviors
{
    public static function adminPageHelpBlock($blocks)
	{
		$found = false;
		foreach($blocks as $block) {
			if ($block == 'core_post') {
				$found = true;
				break;
			}
		}
		if (!$found) {
			return null;
		}
		$blocks[] = 'myGmaps_post';
	}
	
	public static function dashboardFavs($core,$favs)
    {
        $favs['myGmaps'] = new ArrayObject(array(
            'myGmaps',
            __('Google Maps'),
            'plugin.php?p=myGmaps&amp;do=list',
            'index.php?pf=myGmaps/icon.png',
            'index.php?pf=myGmaps/icon-big.png',
            'usage,contentadmin',
            null,
            null));
    }
}

$p_url	= 'plugin.php?p='.basename(dirname(__FILE__));



if (isset($_GET['remove']) && $_GET['remove'] == 'map') {
	try {

		global $core;

		$post_id = $_GET['id'];
		$meta =& $GLOBALS['core']->meta;
		$meta->delPostMeta($post_id,'map');
		$meta->delPostMeta($post_id,'map_options');
		
		$core->blog->triggerBlog();
		
		if (isset($_GET['post_type']) && $_GET['post_type'] == 'page') {
			http::redirect('plugin.php?p=pages&act=page&id='.$post_id.'#gmap-area');
		} else {
			http::redirect(DC_ADMIN_URL.'post.php?id='.$post_id.'#gmap-area');
		}
		
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
} elseif (!empty($_GET['remove']) && is_numeric($_GET['remove'])) {
	try {

		global $core;

		$post_id = $_GET['id'];

		$meta =& $GLOBALS['core']->meta;
		$meta->delPostMeta($post_id,'map',(integer) $_GET['remove']);
		
		$core->blog->triggerBlog();

		if (isset($_GET['post_type']) && $_GET['post_type'] == 'page') {
			http::redirect('plugin.php?p=pages&act=page&id='.$post_id.'#gmap-area');
		} else {
			http::redirect(DC_ADMIN_URL.'post.php?id='.$post_id.'#gmap-area');
		}

	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}
$core->addBehavior('adminPostHeaders',array('myGmapsPostBehaviors','postHeaders'));
$core->addBehavior('adminPageHeaders',array('myGmapsPostBehaviors','postHeaders'));
$core->addBehavior('adminPostFormItems',array('myGmapsPostBehaviors','adminPostFormItems'));

class myGmapsPostBehaviors
{
	public static function postHeaders()
	{
		global $core;
		$s =& $core->blog->settings->myGmaps;	
		
		
		if (!$s->myGmaps_enabled) {
			return;
		}
		
		return
		'<script type="text/javascript">'."\n".
		'$(document).ready(function() {'."\n".
			'$(\'#gmap-area label\').toggleWithLegend($(\'#post-gmap\'), {'."\n".
				'legend_click: true,'."\n".
				'cookie: \'dcx_gmap_detail\''."\n".
			'});'."\n".
			'$(\'a.map-remove\').click(function() {'."\n".
			'msg = \''.__('Are you sure you want to remove this map?').'\';'."\n".
			'if (!window.confirm(msg)) {'."\n".
				'return false;'."\n".
			'}'."\n".
			'});'."\n".
			'$(\'a.element-remove\').click(function() {'."\n".
			'msg = \''.__('Are you sure you want to remove this element?').'\';'."\n".
			'if (!window.confirm(msg)) {'."\n".
				'return false;'."\n".
			'}'."\n".
			'});'."\n".
		'});'."\n".
		'</script>'.
		'<style type="text/css">'."\n".
		'a.map-remove {'."\n".
		'color : #900;'."\n".
		'}'."\n".
		'</style>';
		
	}
	public static function adminPostFormItems($main_items,$sidebar_items, $post)
	{
		global $core;
		$s =& $core->blog->settings->myGmaps;	
		
		if (!$s->myGmaps_enabled) {
			return;
		}
		if (is_null($post)) {
			return;
		}
		$id = $post->post_id;
		
		$meta =& $GLOBALS['core']->meta;
		$meta_rs = $meta->getMetaStr($post->post_meta,'map');
		$meta_rs_options = $meta->getMetaStr($post->post_meta,'map_options');
		
		if ($meta_rs == '' && $meta_rs_options == '')
		{
			$item =
			'<div class="area" id="gmap-area">'.
			'<label for="post-gmap" class="bold">'.__('Google Map:').'</label>'.
			'<div id="post-gmap" >'.
			'<p>'.__('No map').'</p>'.
			'<p><a href="plugin.php?p=myGmaps&amp;post_id='.$id.'">'.__('Add a map to entry').'</a></p>'.
			'</div>'.
			'</div>';
			
		} else if ($meta_rs == '' && $meta_rs_options != '') {
		
			$item =
			'<div class="area" id="gmap-area">'.
			'<label for="post-gmap" class="bold">'.__('Google Map:').'</label>'.
			'<div id="post-gmap" >'.
			'<p>'.__('Empty map').'</p>'.
			'<p class="two-boxes"><a href="plugin.php?p=myGmaps&amp;post_id='.$id.'"><strong>'.__('Edit map').'</strong></a></p>'.
			'<p class="two-boxes right"><a class="map-remove delete" href="'.DC_ADMIN_URL.'post.php?id='.$id.'&amp;remove=map"><strong>'.__('Remove map').'</strong></a></p>'.
			'</div>'.
			'</div>';
		
		} else {
			$maps_array = explode(",",$meta->getMetaStr($post->post_meta,'map'));
			$maps_options = explode(",",$meta->getMetaStr($post->post_meta,'map_options'));
			
			$item =
			'<div class="area" id="gmap-area">'.
			'<label for="post-gmap" class="bold">'.__('Google Map:').'</label>'.
			'<div id="post-gmap" >';
			
			# Get map elements
			try {
				$params['post_id'] = $meta->splitMetaValues($meta_rs);
				$params['post_type'] = 'map';
				$params['no_content'] = true;
				$posts = $core->blog->getPosts($params);
				$counter = $core->blog->getPosts($params,true);
				$post_list = new adminMapsMiniList($core,$posts,$counter->f(0));
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
			$page = '1';
			$nb_per_page = '30';
			
			$item .= 
			'<div id="form-entries">'.
			'<p>'.__('Included elements list').'</p>'.
			$post_list->display($page,$nb_per_page,$enclose_block='',$id).
			'</div>'.
			'<p class="two-boxes"><a href="plugin.php?p=myGmaps&amp;post_id='.$id.'"><strong>'.__('Edit map').'</strong></a></p>'.
			'<p class="two-boxes right"><a class="map-remove delete" href="'.DC_ADMIN_URL.'post.php?id='.$id.'&amp;remove=map"><strong>'.__('Remove map').'</strong></a></p>'.
			'</div>'.
			'</div>';
		}
		
		$main_items['gmap-area'] = $item;
	}
}
?>