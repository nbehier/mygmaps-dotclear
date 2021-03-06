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

if (!defined('DC_RC_PATH')) { return; }

class adminMapsList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='',$filter=false)
	{
		if ($this->rs->isEmpty())
		{
			if( $filter ) {
				echo '<p><strong>'.__('No entry matches the filter').'</strong></p>';
			} else {
				echo '<p><strong>'.__('No entry').'</strong></p>';
			}
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);
			$entries = array();
			if (isset($_REQUEST['entries'])) {
				foreach ($_REQUEST['entries'] as $v) {
					$entries[(integer)$v]=true;
				}
			}
			$html_block =
			'<div class="table-outer">'.
			'<table>';

			if( $filter ) {
				$html_block .= '<caption>'.sprintf(__('List of %s entries match the filter.'), $this->rs_count).'</caption>';
			} else {
				$html_block .= '<caption class="hidden">'.__('Entries list').'</caption>';
			}

			$html_block .= '<tr>'.
			'<th colspan="2" class="first">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Category').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col" class="nowrap">'.__('Type').'</th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'</tr>%s</table></div>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			echo $pager->getLinks();

			$blocks = explode('%s',$html_block);

			echo $blocks[0];

			while ($this->rs->fetch())
			{
				echo $this->postLine(isset($entries[$this->rs->post_id]));
			}

			echo $blocks[1];

			echo $pager->getLinks();
		}
	}

	private function postLine($checked)
	{
		if ($this->core->auth->check('categories',$this->core->blog->id)) {
			$cat_link = '<a href="category.php?id=%s">%s</a>';
		} else {
			$cat_link = '%2$s';
		}
		$p_url	= 'plugin.php?p=myGmaps';
		if ($this->rs->cat_title) {
			$cat_title = sprintf($cat_link,$this->rs->cat_id,
			html::escapeHTML($this->rs->cat_title));
		} else {
			$cat_title = __('(No cat)');
		}

		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}

		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}

		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Selected'),'selected.png');
		}

		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}

		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';
		
		$meta =& $GLOBALS['core']->meta;
		$meta_rs = $meta->getMetaStr($this->rs->post_meta,'map');

		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,$checked,'','',!$this->rs->isEditable()).'</td>'.
		'<td class="maximal" scope="row"><a href="'.$p_url.'&amp;do=edit&amp;id='.$this->rs->post_id.'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$cat_title.'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_id).'</td>'.
		'<td class="nowrap">'.__($meta_rs).'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'</tr>';

		return $res;
	}
}
class adminMapsMiniList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='',$id)
	{
		if ($this->rs->isEmpty())
		{
			$res = '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);

			$html_block =
			'<div class="table-outer clear">'.
			'<table><caption class="hidden">'.__('Elements list').'</caption><tr>'.
			'<th scope="col">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Category').'</th>'.
			'<th scope="col">'.__('Type').'</th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'<th scope="col">'.__('Actions').'</th>'.
			'</tr>%s</table></div>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			$blocks = explode('%s',$html_block);

			$res = $blocks[0];

			while ($this->rs->fetch())
			{
				$res .= $this->postLine($id);
			}

			$res .= $blocks[1];

			return $res;
		}
	}

	private function postLine($id)
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}

		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}

		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Selected'),'selected.png');
		}

		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		global $core;
		if ($core->auth->check('categories',$core->blog->id)) {
			$cat_link = '<a href="category.php?id=%s">%s</a>';
		} else {
			$cat_link = '%2$s';
		}
		if ($this->rs->cat_title) {
			$cat_title = sprintf($cat_link,$this->rs->cat_id,
			html::escapeHTML($this->rs->cat_title));
		} else {
			$cat_title = __('None');
		}
		
		$meta =& $GLOBALS['core']->meta;
		$meta_rs = $meta->getMetaStr($this->rs->post_meta,'map');
		
		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';

		$res .=
		'<td scope="row" class="maximal"><a href="plugin.php?p=myGmaps&amp;do=edit&amp;id='.$this->rs->post_id.'" title="'.__('Edit map element').' : '.html::escapeHTML($this->rs->post_title).'">'.html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$cat_title.'</td>'.
		'<td class="nowrap">'.__($meta_rs).'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'<td class="nowrap count"><a class="element-remove" href="'.DC_ADMIN_URL.'post.php?id='.$id.'&amp;remove='.$this->rs->post_id.'" title="'.__('Remove map element').' : '.html::escapeHTML($this->rs->post_title).'"><img src="images/trash.png" alt="supprimer" /></a></td>'.
		'</tr>';

		return $res;
	}
}
