<?php
/**
 * CLI regression checks using real WordPress hooks/deletion functions and ACF
 * validation/repeater writers. Options live in an in-memory SQLite database.
 * Run: php wp-content/plugins/citynet/tests/hotel-seo.php [path/to/acf]
 * No WordPress bootstrap, site database changes, or network requests.
 */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', dirname(__DIR__, 4) . '/');
define('EMPTY_TRASH_DAYS', 30);
error_reporting(E_ALL & ~E_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) return false;
	throw new ErrorException($message, 0, $severity, $file, $line);
});
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-post.php';

function hotel_test_function($file, $name)
{
	$tokens = token_get_all(file_get_contents($file));
	foreach ($tokens as $i => $token) {
		if (!is_array($token) || $token[0] !== T_FUNCTION) continue;
		$j = $i + 1;
		while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
		if (!is_array($tokens[$j]) || $tokens[$j][1] !== $name) continue;
		$code = ''; $depth = 0; $started = false;
		for ($j = $i; $j < count($tokens); $j++) {
			$t = $tokens[$j];
			$code .= is_array($t) ? $t[1] : $t;
			if ($t === '{' || (is_array($t) && in_array($t[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true))) {
				$depth++; $started = true;
			}
			if ($t === '}' && --$depth === 0 && $started) return $code;
		}
	}
	throw new RuntimeException('Function not found: ' . $name);
}

class HotelSeoTestDatabase
{
	public $options = 'options';
	public $posts = 'posts';
	public $db;
	function __construct() {
		$this->db = new PDO('sqlite::memory:');
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->exec('CREATE TABLE options (option_name TEXT PRIMARY KEY, option_value TEXT)');
	}
	function prepare($sql, ...$args) {
		return preg_replace_callback('/%[sd]/', function ($match) use (&$args) {
			$value = array_shift($args);
			return $match[0] === '%d' ? (string) (int) $value : $this->db->quote((string) $value);
		}, $sql);
	}
	function esc_like($value) { return addcslashes($value, '_%\\'); }
	function get_col($sql) {
		// MySQL defaults to backslash escapes; SQLite requires the ESCAPE clause.
		$sql = str_replace(' AND option_value =', " ESCAPE '\\' AND option_value =", $sql);
		return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
	}
	function get_row($sql) {
		preg_match('/WHERE ID = ([0-9]+)/', $sql, $match);
		return get_post((int) $match[1]);
	}
}
$wpdb = new HotelSeoTestDatabase();
$posts = $errors = $fields = array();
$checks = 0;
function expect_hotel($condition, $label) {
	if (!$condition) throw new RuntimeException('FAIL: ' . $label);
	$GLOBALS['checks']++;
}
function __($value, $domain = '') { return $value; }
function absint($value) { return abs((int) $value); }
function get_post($id) { return $id instanceof WP_Post ? $id : ($GLOBALS['posts'][$id] ?? null); }
function get_post_status($id) { return get_post($id)->post_status; }
function get_option($name, $default = false) {
	$stmt = $GLOBALS['wpdb']->db->prepare('SELECT option_value FROM options WHERE option_name = ?');
	$stmt->execute(array($name));
	$value = $stmt->fetchColumn();
	return $value === false ? $default : $value;
}
function acf_get_metadata($id, $name, $hidden = false) { return get_option(($hidden ? '_' : '') . $id . '_' . $name); }
function acf_update_metadata($id, $name, $value, $hidden = false) {
	$stmt = $GLOBALS['wpdb']->db->prepare('INSERT OR REPLACE INTO options VALUES (?, ?)');
	return $stmt->execute(array(($hidden ? '_' : '') . $id . '_' . $name, $value));
}
function acf_delete_metadata($id, $name, $hidden = false) {
	$stmt = $GLOBALS['wpdb']->db->prepare('DELETE FROM options WHERE option_name = ?');
	return $stmt->execute(array(($hidden ? '_' : '') . $id . '_' . $name));
}
function acf_flush_value_cache($id, $name) {}
function acf_add_validation_error($input, $message) { $GLOBALS['errors'][$input] = $message; }
function acf_get_store($name) {
	return new class {
		function get($key) { return array('type' => 'filter', 'variations' => array('type', 'name', 'key'), 'index' => 2); }
	};
}
function acf_add_local_field_group($group) {
	$visit = function ($items) use (&$visit) {
		foreach ($items as $field) {
			$field += array('name' => '', 'label' => '', 'required' => 0);
			$field['_name'] = $field['name'];
			if (isset($field['sub_fields'])) {
				$visit($field['sub_fields']);
				$field['sub_fields'] = array_map(function ($sub) { return $GLOBALS['fields'][$sub['key']]; }, $field['sub_fields']);
			}
			$GLOBALS['fields'][$field['key']] = $field;
		}
	};
	$visit($group['fields']);
}

$plugin = dirname(__DIR__);
$acf = $argv[1] ?? $plugin . '/includes/acf';
$source = file_get_contents($plugin . '/citynet.php');
$start = strpos($source, '/** Hotel SEO rows must');
$end = strpos($source, '// Public captcha verification endpoint', $start);
eval(substr($source, $start, $end - $start));
require $plugin . '/includes/acf-fields.php';
do_action('acf/include_fields');
$field = $fields['field_62764b4ba3aee'];

foreach (array('wp_trash_post', 'wp_delete_post') as $fn) eval(hotel_test_function(ABSPATH . 'wp-includes/post.php', $fn));
foreach (array('acf_update_value', 'acf_delete_value') as $fn) eval(hotel_test_function($acf . '/includes/acf-value-functions.php', $fn));
eval(hotel_test_function($acf . '/includes/validation.php', 'acf_validate_value'));
eval(hotel_test_function($acf . '/includes/acf-hook-functions.php', '_acf_apply_hook_variations'));
$methods = '';
foreach (array('validate_value', 'update_value', 'update_row', 'delete_row') as $fn) {
	$methods .= hotel_test_function($acf . '/pro/fields/class-acf-field-repeater.php', $fn);
}
eval('class HotelSeoTestRepeater {' . $methods . '}');
$repeater = new HotelSeoTestRepeater();
add_filter('acf/update_value', '_acf_apply_hook_variations', 10, 4);
add_filter('acf/update_value/type=repeater', array($repeater, 'update_value'), 10, 3);
add_filter('acf/validate_value/type=repeater', array($repeater, 'validate_value'), 10, 4);

foreach (array(11 => 'page', 12 => 'post', 13 => 'tour', 14 => 'city', 15 => 'country', 16 => 'page') as $id => $type) {
	$posts[$id] = new WP_Post((object) array('ID' => $id, 'post_type' => $type, 'post_status' => $id === 16 ? 'trash' : 'publish'));
}
$row = array('field_627669912ea7f' => '11', 'field_62764c24bc325' => "{'_id':'hotel-1'}", 'field_6406f97731756' => '0');
$blank = array('field_627669912ea7f' => '', 'field_62764c24bc325' => ' ', 'field_6406f97731756' => '1');
foreach (array('', array(), array('acfcloneindex' => $blank)) as $empty) {
	expect_hotel(acf_validate_value($empty, $field, 'hotel'), 'An unused repeater and clone are optional');
}
expect_hotel(acf_validate_value(array($row), $field, 'hotel'), 'A complete row with panel disabled is valid');
foreach (array($blank, array_replace($row, array('field_627669912ea7f' => '')), array_replace($row, array('field_62764c24bc325' => '  ')), array_replace($row, array('field_627669912ea7f' => '999')), array_replace($row, array('field_627669912ea7f' => '16'))) as $invalid) {
	expect_hotel(!acf_validate_value(array($invalid), $field, 'hotel'), 'Blank, partial, deleted and trashed references cannot validate');
	expect_hotel(isset($errors['hotel']), 'ACF receives the user-facing validation error');
}
expect_hotel(cn_validate_hotel_seo_rows('Earlier error', array($row)) === 'Earlier error', 'Earlier validation errors survive');

expect_hotel(acf_update_value(array($row), 'options', $field), 'Real ACF repeater writer saves a valid row');
$saved = $wpdb->db->query('SELECT * FROM options ORDER BY option_name')->fetchAll(PDO::FETCH_ASSOC);
expect_hotel(acf_update_value(array($row, $blank), 'options', $field) === false, 'Programmatic invalid updates are rejected before ACF writes');
expect_hotel($saved === $wpdb->db->query('SELECT * FROM options ORDER BY option_name')->fetchAll(PDO::FETCH_ASSOC), 'Invalid update leaves every existing option unchanged');
foreach (array('options_en' => 12, 'options_ar' => 13, 'options_pt-br' => 14) as $bucket => $id) {
	$named = array('cn_hotelpage' => $id, 'cn_hotelname' => 'hotel-' . $id, 'cn_display_panel' => '0');
	expect_hotel(acf_update_value(array($named), $bucket, $field), 'Programmatic field names and translated storage work');
}
foreach (array(11, 12, 13, 14) as $id) {
	expect_hotel(cn_is_hotel_seo_post($id), 'Stored default/translated references are protected');
	expect_hotel(wp_trash_post($id) === false, 'Core trash operation is blocked');
	expect_hotel(wp_delete_post($id, true) === false, 'Core permanent deletion is blocked');
	expect_hotel(wp_delete_post($id) === false, 'Core default deletion protects pages, posts and CPTs');
	expect_hotel(in_array('do_not_allow', apply_filters('map_meta_cap', array('delete_posts'), 'delete_post', 1, array($id)), true), 'Deletion capability is denied');
	expect_hotel(isset(apply_filters('display_post_states', array(), $posts[$id])['cn_hotel_seo']), 'The list explains why deletion is disabled');
}
expect_hotel(did_action('wp_trash_post') === 0 && did_action('before_delete_post') === 0, 'Deletion stops before any destructive action, including the legacy Citynet hook');
expect_hotel(apply_filters('pre_trash_post', null, $posts[15]) === null, 'Unrelated content can still be trashed');
expect_hotel(apply_filters('pre_delete_post', null, $posts[15]) === null, 'Unrelated content can still be deleted');
expect_hotel(apply_filters('pre_delete_post', false, $posts[15]) === false, 'Other plugins can still deny deletion');
expect_hotel(apply_filters('map_meta_cap', array('edit_posts'), 'edit_post', 1, array(11)) === array('edit_posts'), 'Linked content stays editable');
expect_hotel(apply_filters('map_meta_cap', array('delete_posts'), 'delete_post', 1, array(15)) === array('delete_posts'), 'Unrelated delete capabilities are unchanged');

acf_update_metadata('options', 'cn_hotel-page_8_cn_hotelpage', '15');
acf_update_metadata('options', 'cn_hotel-page_0_cn_hotelpage_backup', '15');
acf_update_metadata('options', 'unrelated_0_cn_hotelpage', '15');
expect_hotel(!cn_is_hotel_seo_post(15), 'Stale rows and similarly named options do not lock unrelated posts');
expect_hotel(acf_update_value('', 'options', $field), 'Removing every row is allowed');
expect_hotel(!cn_is_hotel_seo_post(11), 'Removing the connection immediately restores deletion');
expect_hotel(cn_is_hotel_seo_post(12), 'Clearing default rows preserves protection in other languages');
echo 'PASS: ' . $checks . ' hotel SEO checks (' . $acf . ").\n";
