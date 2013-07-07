<?php
/**
 * @package		Blogs
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2013, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs\modules\Blogs;
use			cs\modules\Comments\Comments;
global $Core;
$Core->create('_cs\\modules\\Blogs\\Blogs');
$Core->register_trigger(
	'admin/System/components/modules/disable',
	function ($data) {
		if ($data['name'] == 'Blogs') {
			clean_pcache();
		}
	}
);
$Core->register_trigger(
	'admin/System/general/optimization/clean_pcache',
	function () {
		clean_pcache();
	}
);
$Core->register_trigger(
	'System/Page/rebuild_cache',
	function () {
		if (file_exists(PCACHE.'/module.Blogs.js') && file_exists(PCACHE.'/module.Blogs.css')) {
			return;
		}
		rebuild_pcache();
	}
);
function clean_pcache () {
	if (file_exists(PCACHE.'/module.Blogs.js')) {
		unlink(PCACHE.'/module.Blogs.js');
	}
	if (file_exists(PCACHE.'/module.Blogs.css')) {
		unlink(PCACHE.'/module.Blogs.css');
	}
}
function rebuild_pcache (&$data = null) {
	$key	= [];
	file_put_contents(
		PCACHE.'/module.Blogs.js',
		$key[]	= gzencode(
			file_get_contents(MODULES.'/Blogs/includes/js/general.js'),
			9
		),
		LOCK_EX | FILE_BINARY
	);
	file_put_contents(
		PCACHE.'/module.Blogs.css',
		$key[]	= gzencode(
			file_get_contents(MODULES.'/Blogs/includes/css/general.css'),
			9
		),
		LOCK_EX | FILE_BINARY
	);
	if ($data !== null) {
		$data['key']	.= md5(implode('', $key));
	}
}
$Core->register_trigger(
	'System/Index/mainmenu',
	function ($data) {
		global $L;
		if ($data['path'] == 'Blogs') {
			$data['path']	= path($L->Blogs);
		}
	}
);
$Core->register_trigger(
	'api/Comments/add',
	function ($data) {
		global $Config, $User;
		if (!(
			$data['module'] == 'Blogs' &&
			$Config->module('Blogs')->enable_comments &&
			$User->user() &&
			class_exists('\\cs\\modules\\Comments\\Comments')
		)) {
			return true;
		}
		global $Blogs;
		if ($Blogs->get($data['item'])) {
			global $Comments;
			$Comments->set_module('Blogs');
			$data['Comments']	= $Comments;
		}
		return false;
	}
);
$Core->register_trigger(
	'api/Comments/edit',
	function ($data) {
		global $Config, $User;
		if (!(
			$data['module'] == 'Blogs' &&
			$Config->module('Blogs')->enable_comments &&
			$User->user() &&
			class_exists('\\cs\\modules\\Comments\\Comments')
		)) {
			return true;
		}
		global $Comments;
		$Comments->set_module('Blogs');
		$comment			= $Comments->get($data['id']);
		if ($comment && ($comment['user'] == $User->id || $User->admin())) {
			$data['Comments']	= $Comments;
		}
		return false;
	}
);
$Core->register_trigger(
	'api/Comments/delete',
	function ($data) {
		global $Config, $User;
		if (!(
			$data['module'] == 'Blogs' &&
			$Config->module('Blogs')->enable_comments &&
			$User->user() &&
			class_exists('\\cs\\modules\\Comments\\Comments')
		)) {
			return true;
		}
		global $Comments;
		$Comments->set_module('Blogs');
		$comment			= $Comments->get($data['id']);
		if ($comment && ($comment['user'] == $User->id || $User->admin())) {
			$data['Comments']	= $Comments;
			if (
				$comment['parent'] &&
				(
					$comment = $Comments->get($comment['parent'])
				) && (
					$comment['user']  == $User->id || $User->admin()
				)
			) {
				$data['delete_parent']	= true;
			}
		}
		return false;
	}
);