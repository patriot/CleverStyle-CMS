/**
 * @package		CleverStyle CMS
 * @author		Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright	Copyright (c) 2011-2012, Nazar Mokrynskyi
 * @license		MIT License, see license.txt
 */
/**
 * File is not included anywhere!
 * Next code only for IDE inspections and autocomplete
 */
var base_url = '',
	current_base_url = '',
	public_key = '',
	yes = '',
	no = '',
	auth_error_connection = '',
	please_type_your_email = '',
	reg_success = '',
	reg_confirmation = '',
	reg_error_connection = '',
	rules_agree = '',
	rules_text = '',
	please_type_current_password = '',
	please_type_new_password = '',
	current_new_password_equal = '',
	password_changed_successfully = '',
	password_changing_error_connection = '',
	restore_password_confirmation = '',
	language = '',
	language_en = '',
	lang = '',
	module = '',
	in_admin = 1,
	debug = 0,
	session_id = '',
	cookie_prefix = '',
	cookie_domain = '',
	cookie_path = '',
	protocol = '',
	routing = [];
debug_window();
admin_cache();
db_test();
storage_test();
blocks_toggle('');
json_decode();
block_switch_textarea('');
base64_encode();