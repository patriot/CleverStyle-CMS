<?php
/**
 * @package		CleverStyle CMS
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2014, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs;
use
	h,
	cs\Page\Includes,
	cs\Page\Meta;

/**
 * Provides next triggers:<br>
 *  System/Page/pre_display
 *  System/Page/rebuild_cache
 *  ['key'	=> &$key]		//Reference to the key, that will be appended to all css and js files, can be changed to reflect JavaScript and CSS changes
 *  System/Page/external_sign_in_list
 *  ['list'	=> &$list]		//Reference to the list of external sign in systems, actually handled by theme itself, not this class
 *
 * @method static Page instance($check = false)
 */
class Page {
	use
		Singleton,
		Includes;
	public	$Content;
	public	$interface	= true;
	public	$pre_Html	= '';
	public	$Html 		= '';
	public		$Description	= '';
	public		$Title			= [];
	public	$Head		= '';
	public	$pre_Body	= '';
	public		$Left	= '';
	public		$Top	= '';
	public		$Right	= '';
	public		$Bottom	= '';
	public	$post_Body	= '';
	public	$post_Html	= '';
	/**
	 * Number of tabs by default for indentation the substitution of values into template
	 *
	 * @var array
	 */
	public	$level = [
		'Head'		=> 0,
		'pre_Body'	=> 1,
		'Left'		=> 3,
		'Top'		=> 3,
		'Content'	=> 4,
		'Bottom'	=> 3,
		'Right'		=> 3,
		'post_Body'	=> 1
	];
	public	$link			= [];
	public	$Search			= [];
	public	$Replace		= [];
	public	$canonical_url	= false;
	protected	$theme;
	protected	$error_showed		= false;
	protected	$finish_called_once	= false;
	/**
	 * Initialization: setting of title and theme according to specified parameters
	 *
	 * @param string	$title
	 * @param string	$theme
	 *
	 * @return Page
	 */
	function init ($title, $theme) {
		$this->Title[0] = htmlentities($title, ENT_COMPAT, 'utf-8');
		$this->set_theme($theme);
		return $this;
	}
	/**
	 * Theme changing
	 *
	 * @param string	$theme
	 *
	 * @return Page
	 */
	function set_theme ($theme) {
		$this->theme = $theme;
		return $this;
	}
	/**
	 * Adding of content on the page
	 *
	 * @param string	$add
	 * @param bool|int	$level
	 *
	 * @return Page
	 */
	function content ($add, $level = false) {
		if ($level !== false) {
			$this->Content .= h::level($add, $level);
		} else {
			$this->Content .= $add;
		}
		return $this;
	}
	/**
	 * Sets body with content, that is transformed into JSON format
	 *
	 * @param mixed	$add
	 *
	 * @return Page
	 */
	function json ($add) {
		if (!api_path()) {
			header('Content-Type: application/json; charset=utf-8', true);
			interface_off();
		}
		$this->Content	= _json_encode($add);
		return $this;
	}
	/**
	 * Loading of theme template
	 *
	 * @return Page
	 */
	protected function get_template () {
		/**
		 * Theme is fixed for administration, and may vary for other pages
		 */
		if (admin_path()) {
			$this->theme	= 'CleverStyle';
		}
		$theme_dir	= THEMES."/$this->theme";
		_include_once("$theme_dir/prepare.php", false);
		ob_start();
		/**
		 * If website is closed and user is not an administrator - send `503 Service Unavailable` header and show closed site page
		 */
		if (
			!Config::instance()->core['site_mode'] &&
			!User::instance(true)->admin() &&
			code_header(503) &&
			!_include_once("$theme_dir/closed.php", false) &&
			!_include_once("$theme_dir/closed.html", false)
		) {
			echo
				"<!doctype html>\n".
				h::title(get_core_ml_text('closed_title')).
				get_core_ml_text('closed_text');
		} else {
			_include_once("$theme_dir/index.php", false) || _include_once("$theme_dir/index.html");
		}
		$this->Html = ob_get_clean();
		return $this;
	}
	/**
	 * Processing of template, substituting of content, preparing for the output
	 *
	 * @return Page
	 */
	protected function prepare () {
		$Config	= Config::instance(true);
		/**
		 * Loading of template
		 */
		$this->get_template();
		/**
		 * Forming page title
		 */
		foreach ($this->Title as $i => &$v) {
			if (!($v = trim($v))) {
				unset($this->Title[$i]);
			}
		}
		$this->Title = $Config->core['title_reverse'] ? array_reverse($this->Title) : $this->Title;
		$this->Title = implode($Config->core['title_delimiter'] ?: '|', $this->Title);
		/**
		 * Forming <head> content
		 */
		$this->Head			=
			h::title($this->Title).
			h::meta(
				[
					'charset'	=> 'utf-8'
				],
				$this->Description ? [
					'name'		=> 'description',
					'content'	=> $this->Description
				] : false,
				[
					'name'		=> 'generator',
					'content'	=> 'CleverStyle CMS by Mokrynskyi Nazar'
				]
			).
			h::base($Config ? [
				'href' => $Config->base_url().'/'
			] : false).
			$this->Head.
			h::link([
				'rel'	=> 'shortcut icon',
				'href'	=> $this->get_favicon_path()
			]).
			h::link($this->link ?: false);
		/**
		 * Addition of CSS, JavaScript and Web Components includes
		 */
		$this->add_includes_on_page();
		/**
		 * Generation of Open Graph protocol information
		 */
		Meta::instance()->render();
		/**
		 * Substitution of information into template
		 */
		$this->Html			= str_replace(
			[
				'<!--pre_Html-->',
				'<!--head-->',
				'<!--pre_Body-->',
				'<!--left_blocks-->',
				'<!--top_blocks-->',
				'<!--content-->',
				'<!--bottom_blocks-->',
				'<!--right_blocks-->',
				'<!--post_Body-->',
				'<!--post_Html-->'
			],
			_rtrim([
				$this->pre_Html,
				$this->get_property_with_indentation('Head'),
				$this->get_property_with_indentation('pre_Body'),
				$this->get_property_with_indentation('Left'),
				$this->get_property_with_indentation('Top'),
				$this->get_property_with_indentation('Content'),
				$this->get_property_with_indentation('Bottom'),
				$this->get_property_with_indentation('Right'),
				$this->get_property_with_indentation('post_Body'),
				$this->post_Html
			], "\t"),
			$this->Html
		);
		return $this;
	}
	/**
	 * @return string
	 */
	protected function get_favicon_path () {
		$theme_favicon	= "$this->theme/img/favicon";
		if (file_exists(THEMES."/$theme_favicon.png")) {
			return "$theme_favicon.png";
		} elseif (file_exists(THEMES."/$theme_favicon.ico")) {
			return "$theme_favicon.ico";
		}
		return 'favicon.ico';
	}
	/**
	 * @param string $property
	 *
	 * @return string
	 */
	protected function get_property_with_indentation ($property) {
		return h::level($this->$property, $this->level[$property]);
	}
	/**
	 * Replacing anything in source code of finally generated page
	 *
	 * Parameters may be both simply strings for str_replace() and regular expressions for preg_replace()
	 *
	 * @param string|string[]	$search
	 * @param string|string[]	$replace
	 *
	 * @return Page
	 */
	function replace ($search, $replace = '') {
		if (is_array($search)) {
			foreach ($search as $i => $val) {
				$this->Search[] = $val;
				$this->Replace[] = is_array($replace) ? $replace[$i] : $replace;
			}
		} else {
			$this->Search[] = $search;
			$this->Replace[] = $replace;
		}
		return $this;
	}
	/**
	 * Processing of replacing in content
	 *
	 * @param string	$content
	 *
	 * @return string
	 */
	protected function process_replacing ($content) {
		array_map(
			function ($search, $replace) use (&$content) {
				$content = _preg_replace($search, $replace, $content) ?: str_replace($search, $replace, $content);
			},
			$this->Search,
			$this->Replace
		);
		$this->Search  = [];
		$this->Replace = [];
		return $content;
	}
	/**
	 * Adding links
	 *
	 * @param array	$data	According to h class syntax
	 *
	 * @return Page
	 */
	function link ($data) {
		if ($data !== false) {
			$this->link[]	= [$data];
		}
		return $this;
	}
	/**
	 * Simple wrapper of $Page->link() for inserting Atom feed on page
	 *
	 * @param string    $href
	 * @param string    $title
	 *
	 * @return Page
	 */
	function atom ($href, $title = 'Atom Feed') {
		return $this->link([
			'href'	=> $href,
			'title'	=> $title,
			'rel'	=> 'alternate',
			'type'	=> 'application/atom+xml'
		]);
	}
	/**
	 * Simple wrapper of $Page->link() for inserting RSS feed on page
	 *
	 * @param string	$href
	 * @param string	$title
	 *
	 * @return Page
	 */
	function rss ($href, $title = 'RSS Feed') {
		return $this->link([
			'href'	=> $href,
			'title'	=> $title,
			'rel'	=> 'alternate',
			'type'	=> 'application/rss+xml'
		]);
	}
	/**
	 * Specify canonical url of current page
	 *
	 * @param string	$url
	 *
	 * @return Page
	 */
	function canonical_url ($url) {
		$this->canonical_url	= $url;
		return $this->link([
			'href'	=> $this->canonical_url,
			'rel'	=> 'canonical'
		]);
	}
	/**
	 * Adding text to the title page
	 *
	 * @param string	$title
	 * @param bool		$replace	Replace whole title by this
	 *
	 * @return Page
	 */
	function title ($title, $replace = false) {
		$title	= htmlentities($title, ENT_COMPAT, 'utf-8');
		if ($replace) {
			$this->Title	= [$title];
		} else {
			$this->Title[]	= $title;
		}
		return $this;
	}
	/**
	 * Display success message
	 *
	 * @param string $success_text
	 *
	 * @return Page
	 */
	function success ($success_text) {
		return $this->top_message($success_text, 'success uk-lead');
	}
	/**
	 * Display notice message
	 *
	 * @param string $notice_text
	 *
	 * @return Page
	 */
	function notice ($notice_text) {
		return $this->top_message($notice_text, 'warning uk-lead');
	}
	/**
	 * Display warning message
	 *
	 * @param string $warning_text
	 *
	 * @return Page
	 */
	function warning ($warning_text) {
		return $this->top_message($warning_text, 'danger');
	}
	/**
	 * Generic method for 3 methods above
	 *
	 * @param string $message
	 * @param string $class_ending
	 *
	 * @return Page
	 */
	protected function top_message ($message, $class_ending) {
		$this->Top .= h::div(
			$message,
			[
				'class'	=> "cs-center uk-alert uk-alert-$class_ending"
			]
		);
		return $this;
	}
	/**
	 * Error pages processing
	 *
	 * @param null|string|string[]	$custom_text	Custom error text instead of text like "404 Not Found",
	 * 												or array with two elements: [error, error_description]
	 * @param bool					$json			Force JSON return format
	 */
	function error ($custom_text = null, $json = false) {
		if ($this->error_showed) {
			return;
		}
		$this->error_showed	= true;
		if (!error_code()) {
			error_code(500);
		}
		/**
		 * Hack for 403 after sign out in administration
		 */
		if (!api_path() && error_code() == 403 && _getcookie('sign_out')) {
			header('Location: /', true, 302);
			$this->Content	= '';
			exit;
		}
		interface_off();
		$error	= code_header(error_code());
		if (is_array($custom_text)) {
			list($error, $error_description)	= $custom_text;
		} else {
			$error_description	= $custom_text ?: $error;
		}
		if (api_path() || $json) {
			if ($json) {
				header('Content-Type: application/json; charset=utf-8', true);
				interface_off();
			}
			$this->json([
				'error'				=> $error,
				'error_description'	=> $error_description
			]);
		} else {
			ob_start();
			if (
				!_include_once(THEMES."/$this->theme/error.html", false) &&
				!_include_once(THEMES."/$this->theme/error.php", false)
			) {
				echo "<!doctype html>\n".
					h::title(code_header($error)).
					($error_description ?: $error);
			}
			$this->Content	= ob_get_clean();
		}
		$this->__finish();
		exit;
	}
	/**
	 * Page generation
	 */
	function __finish () {
		/**
		 * Protection from double calling
		 */
		if ($this->finish_called_once) {
			return;
		}
		$this->finish_called_once	= true;
		/**
		 * Check whether gzip compression required, and apply it if so
		 */
		$ob		= false;
		if (Config::instance(true)->core['gzip_compression'] && !zlib_compression()) {
			ob_start('ob_gzhandler');
			$ob = true;
		}
		/**
		 * For AJAX and API requests only content without page template
		 */
		if (!$this->interface) {
			/**
			 * Processing of replacing in content
			 */
			echo $this->process_replacing($this->Content ?: (api_path() ? 'null' : ''));
		} else {
			Trigger::instance()->run('System/Page/pre_display');
			/**
			 * Processing of template, substituting of content, preparing for the output
			 */
			$this->prepare();
			/**
			 * Processing of replacing in content
			 */
			$this->Html = $this->process_replacing($this->Html);
			Trigger::instance()->run('System/Page/display');
			echo rtrim($this->Html);
		}
		if ($ob) {
			ob_end_flush();
		}
	}
}
