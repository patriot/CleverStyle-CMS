<?php
/**
 * @package		CleverStyle CMS
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2014, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
namespace	cs\User;
use
	cs\Config,
	cs\Language,
	cs\User,
	h;

/**
 * Trait that contains all methods from <i>>cs\User</i> for working with user data
 */
trait Data {
	/**
	 * Do we need to update users cache, if so - array will not be empty
	 * @var array
	 */
	protected	$update_cache	= [];
	/**
	 * Local cache of users data
	 * @var array
	 */
	protected	$data			= [];
	/**
	 * Changed users data, at the finish, data in db must be replaced by this data
	 * @var array
	 */
	protected	$data_set		= [];
	/**
	 * Whether to use memory cache (locally, inside object, may require a lot of memory if working with many users together)
	 * @var bool
	 */
	protected	$memory_cache	= true;
	/**
	 * Get data item of specified user
	 *
	 * @param string|string[]					$item
	 * @param bool|int 							$user	If not specified - current user assumed
	 *
	 * @return bool|string|mixed[]|Properties			If <i>$item</i> is integer - cs\User\Properties object will be returned
	 */
	function get ($item, $user = false) {
		if (is_scalar($item) && preg_match('/^[0-9]+$/', $item)) {
			return new Properties($item);
		}
		switch ($item) {
			case 'user_agent':
				return @$_SERVER['HTTP_USER_AGENT'] ?: '';
			case 'ip':
				return @$_SERVER['HTTP_X_REAL_IP'] ?: $_SERVER['REMOTE_ADDR'];
			case 'forwarded_for':
				if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					return false;
				}
				$tmp	= explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				return preg_replace('/[^a-f0-9\.:]/i', '', array_pop($tmp));
			case 'client_ip':
				return isset($_SERVER['HTTP_CLIENT_IP']) ? preg_replace('/[^a-f0-9\.:]/i', '', $_SERVER['HTTP_CLIENT_IP']) : false;
		}
		$result	= $this->get_internal($item, $user);
		if (!$this->memory_cache) {
			$this->__finish();
		}
		return $result;
	}
	/**
	 * Get data item of specified user
	 *
	 * @param string|string[]		$item
	 * @param bool|int 				$user		If not specified - current user assumed
	 * @param bool					$cache_only
	 *
	 * @return bool|string|mixed[]
	 */
	protected function get_internal ($item, $user = false, $cache_only = false) {
		$user = (int)$user ?: $this->id;
		if (!$user) {
			return false;
		}
		/**
		 * Reference for simpler usage
		 */
		$data = &$this->data[$user];
		/**
		 * If get an array of values
		 */
		if (is_array($item)) {
			$result = $new_items = [];
			/**
			 * Trying to get value from the local cache, or make up an array of missing values
			 */
			foreach ($item as $i) {
				if (in_array($i, $this->users_columns)) {
					if (($res = $this->get_internal($i, $user, true)) !== false) {
						$result[$i] = $res;
					} else {
						$new_items[] = $i;
					}
				}
			}
			if (empty($new_items)) {
				return $result;
			}
			/**
			 * If there are missing values - get them from the database
			 */
			$new_items	= '`'.implode('`, `', $new_items).'`';
			$res		= $this->db()->qf(
				"SELECT $new_items
				FROM `[prefix]users`
				WHERE `id` = '$user'
				LIMIT 1"
			);
			unset($new_items);
			if (is_array($res)) {
				$this->update_cache[$user]	= true;
				$data						= array_merge($res, $data ?: []);
				$result						= array_merge($result, $res);
				/**
				 * Sorting the resulting array in the same manner as the input array
				 */
				$res = [];
				foreach ($item as $i) {
					$res[$i] = &$result[$i];
				}
				return $res;
			} else {
				return false;
			}
		}
		/**
		 * If get one value
		 */
		return $this->get_internal_one_item($item, $user, $data, $cache_only);
	}
	/**
	 * @param string	$item
	 * @param int		$user
	 * @param mixed[]	$data
	 * @param bool		$cache_only
	 *
	 * @return array|bool
	 */
	protected function get_internal_one_item ($item, $user, &$data, $cache_only) {
		if (!in_array($item, $this->users_columns)) {
			return false;
		}
		/**
		 * If data in local cache - return them
		 */
		if (isset($data[$item])) {
			return $data[$item];
		}
		/**
		 * Try to get data from the cache
		 */
		$data_from_cache	= $this->cache->$user;
		if (is_array($data_from_cache)) {
			/**
			 * Update the local cache
			 */
			if (is_array($data_from_cache)) {
				$data = array_merge($data_from_cache, $data ?: []);
			}
			/**
			 * New attempt of getting the data from cache
			 */
			if (isset($data[$item])) {
				return $data[$item];
			}
		}
		if (!$cache_only) {
			$data_from_db = $this->db()->qfs(
				"SELECT `$item`
				FROM `[prefix]users`
				WHERE `id` = '$user'
				LIMIT 1"
			);
			if ($data_from_db !== false) {
				$this->update_cache[$user]	= true;
				return $data[$item] = $data_from_db;
			}
		}
		return false;
	}
	/**
	 * Set data item of specified user
	 *
	 * @param array|string	$item	Item-value array may be specified for setting several items at once
	 * @param mixed|null	$value
	 * @param bool|int		$user	If not specified - current user assumed
	 *
	 * @return bool
	 */
	function set ($item, $value = null, $user = false) {
		$result	= $this->set_internal($item, $value, $user);
		if (!$this->memory_cache) {
			$this->__finish();
		}
		return $result;
	}
	/**
	 * Set data item of specified user
	 *
	 * @param array|string	$item	Item-value array may be specified for setting several items at once
	 * @param mixed|null	$value
	 * @param bool|int		$user	If not specified - current user assumed
	 *
	 * @return bool
	 */
	protected function set_internal ($item, $value = null, $user = false) {
		$user = (int)$user ?: $this->id;
		if (!$user) {
			return false;
		}
		if (is_array($item)) {
			foreach ($item as $i => &$v) {
				if (in_array($i, $this->users_columns) && $i != 'id') {
					$this->set($i, $v, $user);
				}
			}
		} elseif (in_array($item, $this->users_columns) && $item != 'id') {
			if (in_array($item, ['login_hash', 'email_hash'])) {
				return true;
			}
			if ($item == 'login' || $item == 'email') {
				$value	= mb_strtolower($value);
				if ($item == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
					return false;
				}
				if ($this->get_id(hash('sha224', $value)) !== false) {
					return false;
				}
			} elseif ($item == 'language') {
				$L	= Language::instance();
				if ($user == $this->id) {
					$L->change($value);
					$value	= $value ? $L->clanguage : '';
				}
			} elseif ($item == 'avatar') {
				if (
					$value &&
					strpos($value, 'http') === false
				) {
					$value	= '';
				}
			}
			$this->update_cache[$user]		= true;
			$this->data[$user][$item]		= $value;
			$this->data_set[$user][$item]	= $value;
			if ($item == 'login' || $item == 'email') {
				$this->data[$user][$item.'_hash']		= hash('sha224', $value);
				$this->data_set[$user][$item.'_hash']	= hash('sha224', $value);
				unset($this->cache->{hash('sha224', $this->$item)});
			} elseif ($item == 'password_hash' || ($item == 'status' && $value == 0)) {
				$this->del_all_sessions($user);
			}
		}
		return true;
	}
	/**
	 * Get data item of current user
	 *
	 * @param string|string[]		$item
	 *
	 * @return array|bool|string
	 */
	function __get ($item) {
		return $this->get($item);
	}
	/**
	 * Set data item of current user
	 *
	 * @param array|string	$item	Item-value array may be specified for setting several items at once
	 * @param mixed|null	$value
	 *
	 * @return bool
	 */
	function __set ($item, $value = null) {
		return $this->set($item, $value);
	}
	/**
	 * Getting additional data item(s) of specified user
	 *
	 * @param string|string[]		$item
	 * @param bool|int				$user	If not specified - current user assumed
	 *
	 * @return bool|string|mixed[]
	 */
	function get_data ($item, $user = false) {
		$user	= (int)$user ?: $this->id;
		if (!$user || $user == User::GUEST_ID || !$item) {
			return false;
		}
		$Cache	= $this->cache;
		$data	= $Cache->{"data/$user"} ?: [];
		if (is_array($item)) {
			$result	= [];
			$absent	= [];
			foreach ($item as $i) {
				if (isset($data[$i])) {
					$result[$i]	= $data[$i];
				} else {
					$absent[]	= $i;
				}
			}
			unset($i);
			if ($absent) {
				$absent					= implode(
					',',
					$this->db()->s($absent)
				);
				$absent					= array_column(
					$this->db()->qfa([
						"SELECT `item`, `value`
						FROM `[prefix]users_data`
						WHERE
							`id`	= '$user' AND
							`item`	IN($absent)",
					]),
					'value',
					'item'
				);
				foreach ($absent as &$a) {
					$a	= _json_decode($a);
					if (is_null($a)) {
						$a	= false;
					}
				}
				unset($a);
				$result					+= $absent;
				$data					+= $absent;
				$Cache->{"data/$user"}	= $data;
			}
			return $result;
		}
		if ($data === false || !isset($data[$item])) {
			if (!is_array($data)) {
				$data	= [];
			}
			$data[$item]			= _json_decode($this->db()->qfs([
				"SELECT `value`
				FROM `[prefix]users_data`
				WHERE
					`id`	= '$user' AND
					`item`	= '%s'",
				$item
			]));
			if (is_null($data[$item])) {
				$data[$item]	= false;
			}
			$Cache->{"data/$user"}	= $data;
		}
		return $data[$item];
	}
	/**
	 * Setting additional data item(s) of specified user
	 *
	 * @param array|string	$item	Item-value array may be specified for setting several items at once
	 * @param mixed|null	$value
	 * @param bool|int		$user	If not specified - current user assumed
	 *
	 * @return bool
	 */
	function set_data ($item, $value = null, $user = false) {
		$user	= (int)$user ?: $this->id;
		if (!$user || $user == User::GUEST_ID || !$item) {
			return false;
		}
		if (is_array($item)) {
			$params	= [];
			foreach ($item as $i => $v) {
				$params[]	= [
					$i,
					_json_encode($v)
				];
			}
			unset($i, $v);
			$result			= $this->db_prime()->insert(
				"INSERT INTO `[prefix]users_data`
					(
						`id`,
						`item`,
						`value`
					) VALUES (
						$user,
						'%s',
						'%s'
					)
				ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
				$params
			);
		} else {
			$result	= $this->db_prime()->q(
				"INSERT INTO `[prefix]users_data`
					(
						`id`,
						`item`,
						`value`
					) VALUES (
						'$user',
						'%s',
						'%s'
					)
				ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
				$item,
				_json_encode($value)
			);
		}
		unset($this->cache->{"data/$user"});
		return $result;
	}
	/**
	 * Deletion of additional data item(s) of specified user
	 *
	 * @param string|string[]	$item
	 * @param bool|int			$user	If not specified - current user assumed
	 *
	 * @return bool
	 */
	function del_data ($item, $user = false) {
		$user	= (int)$user ?: $this->id;
		if (!$user || $user == User::GUEST_ID || !$item) {
			return false;
		}
		$item	= implode(
			',',
			$this->db_prime()->s((array)$item)
		);
		$result	= $this->db_prime()->q(
			"DELETE FROM `[prefix]users_data`
			WHERE
				`id`	= '$user' AND
				`item`	IN($item)"
		);
		unset($this->cache->{"data/$user"});
		return $result;
	}
	/**
	 * Get user id by login or email hash (sha224) (hash from lowercase string)
	 *
	 * @param  string $login_hash	Login or email hash
	 *
	 * @return bool|int				User id if found and not guest, otherwise - boolean <i>false</i>
	 */
	function get_id ($login_hash) {
		if (!preg_match('/^[0-9a-z]{56}$/', $login_hash)) {
			return false;
		}
		$id	= $this->cache->get($login_hash, function () use ($login_hash) {
			return $this->db()->qfs([
				"SELECT `id`
				FROM `[prefix]users`
				WHERE
					`login_hash`	= '%s' OR
					`email_hash`	= '%s'
				LIMIT 1",
				$login_hash,
				$login_hash
			]) ?: false;
		});
		return $id && $id != User::GUEST_ID ? $id : false;
	}
	/**
	 * Get user avatar, if no one present - uses Gravatar
	 *
	 * @param int|null	$size	Avatar size, if not specified or resizing is not possible - original image is used
	 * @param bool|int	$user	If not specified - current user assumed
	 *
	 * @return string
	 */
	function avatar ($size = null, $user = false) {
		$user	= (int)$user ?: $this->id;
		$avatar	= $this->get('avatar', $user);
		if (!$avatar && $this->id != User::GUEST_ID) {
			$avatar	= 'https://www.gravatar.com/avatar/'.md5($this->get('email', $user))."?d=mm&s=$size";
			$avatar	.= '&d='.urlencode(Config::instance()->core_url().'/includes/img/guest.svg');
		}
		if (!$avatar) {
			$avatar	= '/includes/img/guest.svg';
		}
		return h::prepare_url($avatar, true);
	}
	/**
	 * Get user name or login or email, depending on existing information
	 *
	 * @param bool|int $user	If not specified - current user assumed
	 *
	 * @return string
	 */
	function username ($user = false) {
		$user = (int)$user ?: $this->id;
		return $this->get('username', $user) ?: ($this->get('login', $user) ?: $this->get('email', $user));
	}
	/**
	 * Disable memory cache
	 *
	 * Memory cache stores users data inside User class in order to get data faster next time.
	 * But in case of working with large amount of users this cache can be too large. Disabling will cause some performance drop, but save a lot of RAM.
	 */
	function disable_memory_cache () {
		$this->memory_cache	= false;
	}
	/**
	 * Saving changes of cache and users data
	 */
	function __finish () {
		/**
		 * Updating users data
		 */
		if (is_array($this->data_set) && !empty($this->data_set)) {
			$update = [];
			foreach ($this->data_set as $id => &$data_set) {
				$data = [];
				foreach ($data_set as $i => &$val) {
					if (in_array($i, $this->users_columns) && $i != 'id') {
						$val	= xap($val, false);
						$data[]	= "`$i` = ".$this->db_prime()->s($val);
					} elseif ($i != 'id') {
						unset($data_set[$i]);
					}
				}
				if (!empty($data)) {
					$data		= implode(', ', $data);
					$update[]	= "UPDATE `[prefix]users`
						SET $data
						WHERE `id` = '$id'";
					unset($i, $val, $data);
				}
			}
			if (!empty($update)) {
				$this->db_prime()->q($update);
			}
			unset($update);
		}
		/**
		 * Updating users cache
		 */
		foreach ($this->data as $id => &$data) {
			if (isset($this->update_cache[$id]) && $this->update_cache[$id]) {
				$data['id']			= $id;
				$this->cache->$id	= $data;
			}
		}
		$this->update_cache = [];
		unset($id, $data);
		$this->data_set = [];
	}
	/**
	 * Do not track checking
	 *
	 * @return bool	<b>true</b> if tracking is not desired, <b>false</b> otherwise
	 */
	function dnt () {
		return isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1;
	}
}
