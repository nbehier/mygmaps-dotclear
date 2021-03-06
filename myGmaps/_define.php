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

$this->registerModule(
	/* Name */				"myGmaps",
	/* Description*/		"Create custom maps associated to your posts",
	/* Author */			"Philippe aka amalgame",
	/* Version */			'4.4',
	/* Permissions */		array(
								'permissions' =>	'usage,contentadmin',
								'type' => 'plugin',
								'dc_min' => '2.6'
							)
);
?>