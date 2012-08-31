<?php
/**
 * @package		Blogs
 * @category	modules
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2012 by Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */

namespace	cs\modules\Blogs;
use			\h;
global $Index, $L, $Page;
$Index->buttons	= false;
$Page->title($L->browse_sections);
$Index->content(
	h::{'table.cs-left-all.cs-fullwidth-table'}(
		h::{'tr th.ui-widget-header.ui-corner-all'}(
			[
				$L->blogs_sections,
				[
					'style'	=> 'width: 80%'
				]
			],
			$L->action
		).
		h::{'tr| td.ui-widget-content.ui-corner-all'}(
			get_sections_rows()
		).
		h::{'tr td[colspan=2] a.cs-button'}(
			[
				$L->add_section,
				[
					'href'	=> 'admin/'.MODULE.'/add_section'
				]
			]
		)
	)
);