<?php
/**
 * @package		Photo gallery
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2013-2014, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs\modules\Photo_gallery;
use
	cs\Config,
	cs\Page,
	cs\User;
$Config			= Config::instance();
$User			= User::instance();
if (!$User->user()) {
	error_code(403);
	return;
}
if (!isset($Config->route[1])) {
	error_code(400);
	return;
}
$Photo_gallery	= Photo_gallery::instance();
$image			= $Photo_gallery->get($Config->route[1]);
if (!$image) {
	error_code(404);
	return;
}
if ($User->admin() || $image['user'] == $User->id) {
	$Photo_gallery->del($image['id']);
	Page::instance()->json('ok');
} else {
	error_code(403);
}
