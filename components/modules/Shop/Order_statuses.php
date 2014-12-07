<?php
/**
 * @package   Shop
 * @category  modules
 * @author    Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright Copyright (c) 2014, Nazar Mokrynskyi
 * @license   MIT License, see license.txt
 */
namespace cs\modules\Shop;
use
	cs\Cache\Prefix,
	cs\Config,
	cs\Language,
	cs\CRUD,
	cs\Singleton;

/**
 * @method static Order_statuses instance($check = false)
 */
class Order_statuses {
	use
		CRUD,
		Singleton;

	const TYPE_OTHER            = 0;
	const TYPE_CREATED          = 1;
	const TYPE_CONFIRMED        = 2;
	const TYPE_PAYMENT_AWAITING = 3;
	const TYPE_PAID             = 4;
	const TYPE_PROCESSING       = 5;
	const TYPE_PROCESSED        = 6;
	const TYPE_SHIPPED          = 7;
	const TYPE_DELIVERED        = 8;
	const TYPE_CANCELED         = 9;
	const TYPE_REFUNDED         = 10;
	const TYPE_COMPLETED        = 11;

	protected $data_model          = [
		'id'                       => 'int',
		'title'                    => 'text',
		'type'                     => 'int',
		'color'                    => 'string',
		'send_update_status_email' => 'int',
		'comment'                  => 'ml:html'
	];
	protected $data_model_ml_group = 'Shop/order_statuses';
	protected $table               = '[prefix]shop_order_statuses';
	/**
	 * @var Prefix
	 */
	protected $cache;

	protected function construct () {
		$this->cache = new Prefix('Shop/order_statuses');
	}
	/**
	 * Returns database index
	 *
	 * @return int
	 */
	protected function cdb () {
		return Config::instance()->module('Shop')->db('shop');
	}
	/**
	 * Get order status
	 *
	 * @param int|int[] $id
	 *
	 * @return array|bool
	 */
	function get ($id) {
		if (is_array($id)) {
			foreach ($id as &$i) {
				$i = $this->get($i);
			}
			return $id;
		}
		$L  = Language::instance();
		$id = (int)$id;
		return $this->cache->get("$id/$L->clang", function () use ($id) {
			return $this->read_simple($id);
		});
	}
	/**
	 * Add new order status
	 *
	 * @param string $title
	 * @param int    $type
	 * @param string $color
	 * @param int    $send_update_status_email
	 * @param string $comment
	 *
	 * @return bool|int Id of created item on success of <b>false</> on failure
	 *
	 */
	function add ($title, $type, $color, $send_update_status_email, $comment) {
		return $this->create_simple([
			$title,
			$type,
			$color,
			$send_update_status_email,
			$comment
		]);
	}
	/**
	 * Set data of specified order status
	 *
	 * @param int    $id
	 * @param string $title
	 * @param int    $type
	 * @param string $color
	 * @param int    $send_update_status_email
	 * @param string $comment
	 *
	 * @return bool
	 */
	function set ($id, $title, $type, $color, $send_update_status_email, $comment) {
		$id = (int)$id;
		if ($this->update_simple([
			$id,
			$title,
			$type,
			$color,
			$send_update_status_email,
			$comment
		])
		) {
			$L = Language::instance();
			$this->cache->del("$id/$L->clang");
			return true;
		}
		return false;
	}
	/**
	 * Delete specified order status
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	function del ($id) {
		$id = (int)$id;
		if ($this->delete_simple($id)) {
			unset($this->cache->$id);
			return true;
		}
		return false;
	}
}
