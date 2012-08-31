<?php
/**
 * @package		Blogs
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2012 by Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */

if (!isset($_POST['mode'])) {
	return;
}
global $Index, $Blogs;
switch ($_POST['mode']) {
	case 'add_section':
		$Index->save((bool)$Blogs->add_section($_POST['parent'], $_POST['title'], $_POST['path']));
	break;
	case 'edit_section':
		$Index->save((bool)$Blogs->set_section($_POST['id'], $_POST['parent'], $_POST['title'], $_POST['path']));
	break;
	case 'delete_section':
		$Index->save((bool)$Blogs->del_section($_POST['id']));
	break;
	case 'edit_post':
		global $Page, $Config, $L;
		$save	= true;
		if (empty($_POST['title'])) {
			$Page->warning($L->post_title_empty);
			$save	= false;
		}
		if (empty($_POST['sections'])) {
			$Page->warning($L->no_post_sections_selected);
			$save	= false;
		}
		if (empty($_POST['content'])) {
			$Page->warning($L->post_content_empty);
			$save	= false;
		}
		if (empty($_POST['tags'])) {
			$Page->warning($L->no_post_tags_specified);
			$save	= false;
		}
		if ($save) {
			$Index->save(
				(bool)$Blogs->set($_POST['id'], $_POST['title'], null, $_POST['content'], $_POST['sections'], _trim(explode(',', $_POST['tags'])))
			);
		}
	break;
	case 'delete_post':
		$Index->save((bool)$Blogs->del($_POST['id']));
	break;
	case 'general':
		global $Config;
		$Index->save($Config->module(MODULE)->set($_POST['general']));
	break;
}