<?php
/*
Plugin Name:  Citynet
Description:  افزونه پنل رزرواسیون سیتی نت
Plugin URI:   https://citynet.ir/
Author:       Citynet
Author URI:   https://citynet.ir/
Version:      5.7.21
Text Domain:  citynet_plugin
Domain Path:  /languages
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/
// disable direct file access
if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/includes/api.php';

function citynet_load_textdomain()
{
	load_plugin_textdomain(
		'citynet_plugin',
		false,
		plugin_basename(dirname(__FILE__)) . '/languages'
	);
}
add_action('plugins_loaded', 'citynet_load_textdomain', 8);

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
	define('MY_ACF_PATH', plugin_dir_path(__FILE__) . 'includes/acf/');
	define('MY_ACF_URL', plugin_dir_url(__FILE__) . 'includes/acf/');

	// Include the ACF plugin.
	include_once(MY_ACF_PATH . 'acf.php');

	// Customize the url setting to fix incorrect asset URLs.
	add_filter('acf/settings/url', 'my_acf_settings_url');
	function my_acf_settings_url($url)
	{
		return MY_ACF_URL;
	}
	// (Optional) Hide the ACF admin menu item.
	add_filter('acf/settings/show_admin', 'my_acf_settings_show_admin');
	function my_acf_settings_show_admin($show_admin)
	{
		return false;
	}
}
//Returns ACF repeater fields values
function cn_repeater($field, $subfields, $lang_prefix = '')
{
	$rows = false;
	$result = [];

	$rows = (int) cn_option($field, $lang_prefix);
	if ($rows) :
		for ($row_index = 0; $row_index < $rows; $row_index++) :
			foreach ($subfields as $subfield) :
				$subfield_key = $field . '_' . $row_index . '_' . $subfield;
				$value = cn_option($subfield_key, $lang_prefix);
				$result[$row_index][$subfield] = $value;
			endforeach;
		endfor;
	endif;

	return $result;
}

function cn_option($field, $lang_prefix = '', $default = false)
{
	return get_option('options' . $lang_prefix . '_' . $field, $default);
}
require_once __DIR__ . '/includes/languages.php';
// include plugin dependencies: admin only
if (is_admin()) {
	require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
}
require_once __DIR__ . '/includes/acf-fields.php';
//generate plugin pages
function cn_generate_pages()
{
	$pages =  [
		'panelReturn2',
		'hotel',
		'flight',
		'visa',
		'train',
		'book',
		'ticket',
		'cip',
		'tour',
		'print-action',
		'package',
		'flightplushotel',
		'insurance',
		'cipprint',
		'newcipprint',
		'subsidiary',
		'redirect',
		'manifest',
		'flightdeclaration',
		'boardpassengers',
		'panelReturn',
		'contracts',
		'contractsHTML',
		'purchasesHTML',
		'contractsHtml',
		'purchasesHtml',
		'financialreports',
		'cipfinancialreports',
		'print',
		'errorlogs',
		'baseInfo',
		'dashboard',
		'cipmanagement',
		'printTicket',
		'printContract',
		'recharge',
		'editProfile',
		'financialContracts',
		'purchases',
		'financialPurchases',
		'baggage',
		'cabinDeclaration',
		'commission',
		'costDeclaration',
		'editCabinDeclaration',
		'editFlight',
		'flightDeclaration',
		'editFlightDeclaration',
		'pathDeclaration',
		'rules',
		'userTreasury',
		'treasury',
		'seats',
		'ticketReport',
		'trackOrder',
		'currencyConvert',
		'stopFlights',
		'cnpwa',
		'pwaeditprofile',
		'cnsupport',
		'cnmenu',
		'pwaservices',
		'pwabuilding',
		'searchflight',
		'getpdf',
		'linkreports',
		'bankreports',
		'ledgersrequests',
		'visainventory',
		'visamanifest',
		'loginwithtoken',
		'letterOfCredence',
		'organsLetterOfCredence',
		'invoices',
		'invoices/management',
		'invoices/create',
		'invoices/print'
	];
	return $pages;
}
function cn_generate_real_pages()
{
	$real_pages = [
		['title' => 'print-action', 'slug' => 'print-action'], 
		['title' => 'panelReturn2', 'slug' => 'panelReturn2'],
		['title' => 'dashboard', 'slug' => 'dashboard'],
		['title' => 'cipmanagement', 'slug' => 'cipmanagement'],
		['title' => 'cnpwa', 'slug' => 'cnpwa'],
		['title' => 'hotel', 'slug' => 'hotel'],
		['title' => 'flight', 'slug' => 'flight'],
		['title' => 'visa', 'slug' => 'visa'],
		['title' => 'book', 'slug' => 'book'],
		['title' => 'cip', 'slug' => 'cip'],
		['title' => 'tour', 'slug' => 'tour'],
		['title' => 'package', 'slug' => 'package'],
		['title' => 'flightplushotel', 'slug' => 'flightplushotel'],
		['title' => 'insurance', 'slug' => 'insurance'],
		['title' => 'cipprint', 'slug' => 'cipprint'],
		['title' => 'newcipprint', 'slug' => 'newcipprint'],
		['title' => 'subsidiary', 'slug' => 'subsidiary'],
		['title' => 'redirect', 'slug' => 'redirect'],
		['title' => 'boardpassengers', 'slug' => 'boardpassengers'],
		['title' => 'panelReturn', 'slug' => 'panelReturn'],
		['title' => 'contracts', 'slug' => 'contracts'],
		['title' => 'contractsHTML', 'slug' => 'contractsHTML'],
		['title' => 'purchasesHTML', 'slug' => 'purchasesHTML'],
		['title' => 'contractsHtml', 'slug' => 'contractsHtml'],
		['title' => 'purchasesHtml', 'slug' => 'purchasesHtml'],
		['title' => 'financialreports', 'slug' => 'financialreports'],
		['title' => 'cipfinancialreports', 'slug' => 'cipfinancialreports'],
		['title' => 'print', 'slug' => 'print'],
		['title' => 'errorlogs', 'slug' => 'errorlogs'],
		['title' => 'baseInfo', 'slug' => 'baseInfo'],
		['title' => 'printTicket', 'slug' => 'printTicket'],
		['title' => 'printContract', 'slug' => 'printContract'],
		['title' => 'recharge', 'slug' => 'recharge'],
		['title' => 'editProfile', 'slug' => 'editProfile'],
		['title' => 'financialContracts', 'slug' => 'financialContracts'],
		['title' => 'purchases', 'slug' => 'purchases'],
		['title' => 'financialPurchases', 'slug' => 'financialPurchases'],
		['title' => 'userTreasury', 'slug' => 'userTreasury'],
		['title' => 'treasury', 'slug' => 'treasury'],
		['title' => 'trackOrder', 'slug' => 'trackOrder'],
		['title' => 'currencyConvert', 'slug' => 'currencyConvert'],
		['title' => 'getpdf', 'slug' => 'getpdf'],
		//cipmanagement
		['title' => 'manifest', 'slug' => 'manifest'],
		['title' => 'flights', 'slug' => 'flights'],
		['title' => 'transfers', 'slug' => 'transfers'],
		['title' => 'businessReport', 'slug' => 'businessReport'],
		['title' => 'letterOfCredence', 'slug' => 'letterOfCredence'],
		['title' => 'orgsLetterOfCredence', 'slug' => 'organsLetterOfCredence'],
		['title' => 'showPassenger', 'slug' => 'showPassenger'],
		['title' => 'operationsReport', 'slug' => 'operationsReport'],
		['title' => 'operationsTotalReport', 'slug' => 'operationsTotalReport'],
		['title' => 'flightsManifest', 'slug' => 'flightsManifest'],
		['title' => 'formflights', 'slug' => 'formflights'],
		['title' => 'suites', 'slug' => 'suites'],
		['title' => 'cipList', 'slug' => 'cipList'],
		//dashboard
		['title' => 'seats', 'slug' => 'seats'],
		['title' => 'automation', 'slug' => 'automation'],
		['title' => 'automationstart', 'slug' => 'automationstart'],
		['title' => 'rules', 'slug' => 'rules'],
		['title' => 'ticketReport', 'slug' => 'ticketReport'],
		['title' => 'baggage', 'slug' => 'baggage'],
		['title' => 'cabinDeclaration', 'slug' => 'cabinDeclaration'],
		['title' => 'commission', 'slug' => 'commission'],
		['title' => 'costDeclaration', 'slug' => 'costDeclaration'],
		['title' => 'editCabinDeclaration', 'slug' => 'editCabinDeclaration'],
		['title' => 'editFlight', 'slug' => 'editFlight'],
		['title' => 'flightdeclaration', 'slug' => 'flightdeclaration'],
		['title' => 'editFlightDeclaration', 'slug' => 'editFlightDeclaration'],
		['title' => 'pathDeclaration', 'slug' => 'pathDeclaration'],
		['title' => 'stopFlights', 'slug' => 'stopFlights'],
		//cnpwa
		['title' => 'pwaeditprofile', 'slug' => 'pwaeditprofile'],
		['title' => 'cnsupport', 'slug' => 'cnsupport'],
		['title' => 'cnmenu', 'slug' => 'cnmenu'],
		['title' => 'pwaservices', 'slug' => 'pwaservices'],
		['title' => 'pwabuilding', 'slug' => 'pwabuilding'],
		['title' => 'searchflight', 'slug' => 'searchflight'],
	];
	return $real_pages;
}
function cn_pages_with_parent($parent)
{
	if ($parent == 'invoices') return ['management' , 'create'];
	if ($parent == 'cipmanagement')
		return [
			'manifest',
			'flights',
			'transfers',
			'businessReport',
			'letterOfCredence',
			'organsLetterOfCredence',
			'showPassenger',
			'operationsReport',
			'operationsTotalReport',
			'flightsManifest',
			'formflights',
			'suites',
			'flightdeclaration',
			'cipList'
		];
	if ($parent == 'dashboard')
		return [
			'seats',
			'automation',
			'automationstart',
			'rules',
			'ticketReport',
			'baggage',
			'cabinDeclaration',
			'commission',
			'costDeclaration',
			'editCabinDeclaration',
			'editFlight',
			'flightDeclaration',
			'editFlightDeclaration',
			'pathDeclaration',
			'stopFlights'
		];
	else
		return [
			'pwaeditprofile',
			'cnsupport',
			'cnmenu',
			'pwaservices',
			'pwabuilding',
			'searchflight'
		];
}
// Adds custom classes to body tag
add_filter('body_class', function ($classes) {
	$classes[] = citynet_is_panel_pages() ? 'page-template-app-templates' : '';
	$url = strtok($_SERVER['REQUEST_URI'], '?');
	$url = trim($url, '/');
	$url = explode('/', $url);
	$url = implode('-', $url);
	$classes[] = citynet_is_panel_pages() ?  $url : '';
	return $classes;
});
function get_page_id($slug)
{
	$query = new WP_Query(
		array(
			'name' => $slug,
			'post_type' => 'page'
		)
	);
	$query->the_post();
	return get_the_ID();
}
function cn_is_multilang_active()
{
	return is_plugin_active('sitepress-multilingual-cms/sitepress.php');
}
//[citynet] shortcode for displaying app
function cn_generate_app($atts)
{
	return "<div id='app'></div>";
}
add_shortcode('citynet', 'cn_generate_app');

function cn_get_flight_page_id()
{
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($current_lang);
	}
	$flight_page = get_posts(array(
		'name'           => 'flight',
		'posts_per_page' => 1,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'fields' => 'ids'
	));
	$flight_page_id = [];
	if ($flight_page) {
		foreach ($flight_page as $page_id) {
			//wp_delete_post($post->ID, true);
			$flight_page_id[] = $page_id;
		}
		$flight_page_id = $flight_page_id[0];
	} else {
		if (cn_is_multilang_active()) {
			$sitepress->switch_lang($current_lang);
		}
		$flight_page = array(
			'post_title'  => 'flight',
			'post_name'   => 'flight',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type'   => 'page',
			'post_content' => '[citynet]'
		);
		$flight_page_id = wp_insert_post($flight_page);
	}
	return $flight_page_id;
}
function cn_get_tour_page_id()
{
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($current_lang);
	}
	$tour_page = get_posts(array(
		'name'           => 'tour',
		'posts_per_page' => 1,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'fields' => 'ids'
	));
	$tour_page_id = [];
	if ($tour_page) {
		foreach ($tour_page as $page_id) {
			//wp_delete_post($post->ID, true);
			$tour_page_id[] = $page_id;
		}
		$tour_page_id = $tour_page_id[0];
	} else {
		if (cn_is_multilang_active()) {
			$sitepress->switch_lang($current_lang);
		}
		$tour_page = array(
			'post_title'  => 'tour',
			'post_name'   => 'tour',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type'   => 'page',
			'post_content' => '[citynet]'
		);
		$tour_page_id = wp_insert_post($tour_page);
	}
	return $tour_page_id;
}
function cn_get_hotel_page_id()
{
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($current_lang);
	}
	$hotel_page = get_posts(array(
		'name'           => 'hotel',
		'posts_per_page' => 1,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'fields' => 'ids'
	));
	$hotel_page_id = [];
	if ($hotel_page) {
		foreach ($hotel_page as $page_id) {
			//wp_delete_post($post->ID, true);
			$hotel_page_id[] = $page_id;
		}
		$hotel_page_id = $hotel_page_id[0];
	} else {
		if (cn_is_multilang_active()) {
			$sitepress->switch_lang($current_lang);
		}
		$hotel_page = array(
			'post_title'  => 'hotel',
			'post_name'   => 'hotel',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type'   => 'page',
			'post_content' => '[citynet]'
		);
		$hotel_page_id = wp_insert_post($hotel_page);
	}
	return $hotel_page_id;
}

function cn_get_visa_page_id(){
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($current_lang);
	}
	$visa_page = get_posts(array(
		'name'           => 'visa',
		'posts_per_page' => 1,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'fields' => 'ids'
	));
	$hotel_page_id = [];
	if ($visa_page) {
		foreach ($visa_page as $page_id) {
			//wp_delete_post($post->ID, true);
			$visa_page_id[] = $page_id;
		}
		$visa_page_id = $visa_page_id[0];
	} else {
		if (cn_is_multilang_active()) {
			$sitepress->switch_lang($current_lang);
		}
		$visa_page = array(
			'post_title'  => 'visa',
			'post_name'   => 'visa',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type'   => 'page',
			'post_content' => '[citynet]'
		);
		$visa_page_id = wp_insert_post($visa_page);
	}
	return $visa_page_id;
}

function cn_get_cip_page_id()
{
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($current_lang);
	}
	$cip_page = get_posts(array(
		'name'           => 'cip',
		'posts_per_page' => 1,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'fields'         => 'ids'
	));
	$cip_page_id = [];
	if ($cip_page) {
		foreach ($cip_page as $page_id) { $cip_page_id[] = $page_id; }
		$cip_page_id = $cip_page_id[0];
	} else {
		if (cn_is_multilang_active()) { $sitepress->switch_lang($current_lang); }
		$cip_page = array(
			'post_title'   => 'cip',
			'post_name'    => 'cip',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'page',
			'post_content' => '[citynet]'
		);
		$cip_page_id = wp_insert_post($cip_page);
	}
	return $cip_page_id;
}

function cn_get_train_page_id()
{
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$sitepress->switch_lang($current_lang);
	}
	$cip_page = get_posts(array(
		'name'           => 'train',
		'posts_per_page' => 1,
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'fields'         => 'ids'
	));
	$cip_page_id = [];
	if ($cip_page) {
		foreach ($cip_page as $page_id) { $cip_page_id[] = $page_id; }
		$cip_page_id = $cip_page_id[0];
	} else {
		if (cn_is_multilang_active()) { $sitepress->switch_lang($current_lang); }
		$cip_page = array(
			'post_title'   => 'train',
			'post_name'    => 'train',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'page',
			'post_content' => '[citynet]'
		);
		$cip_page_id = wp_insert_post($cip_page);
	}
	return $cip_page_id;
}


function citynet_activate()
{
	$cn_pages_ids = [];
	$other_langs = [];
	if (cn_is_multilang_active()) {
		global $sitepress;
		$current_lang = ICL_LANGUAGE_CODE;
		$langs = cn_get_wpml_active_langs();
		foreach ($langs as $lang) {
			if ($lang != $current_lang) {
				$other_langs[] = $lang;
			}
		}
	}

	$cn_pages_ids = [];
	$flight_page_id = cn_get_flight_page_id();
	$tour_page_id = cn_get_tour_page_id();
	$hotel_page_id = cn_get_hotel_page_id();
	$visa_page_id = cn_get_visa_page_id();
	$cip_page_id = cn_get_cip_page_id();
	$train_page_id = cn_get_train_page_id();

	array_push($cn_pages_ids, $flight_page_id);
	array_push($cn_pages_ids, $tour_page_id);
	array_push($cn_pages_ids, $hotel_page_id);
	array_push($cn_pages_ids, $visa_page_id);
	array_push($cn_pages_ids, $cip_page_id);
	array_push($cn_pages_ids, $train_page_id);


	if ($other_langs) {
		// Get original post's translation ID
		$trid = $sitepress->get_element_trid($flight_page_id, 'post_page');
		$trid_tour = $sitepress->get_element_trid($tour_page_id, 'post_page');
		$trid_hotel = $sitepress->get_element_trid($hotel_page_id, 'post_page');
		$trid_visa = $sitepress->get_element_trid($visa_page_id, 'post_page');



		foreach ($other_langs as $other_lang) {
			$sec_flight_page = apply_filters('wpml_object_id', $flight_page_id, 'page', false, $other_lang);
			$sec_tour_page = apply_filters('wpml_object_id', $tour_page_id, 'page', false, $other_lang);
			$sec_hotel_page = apply_filters('wpml_object_id', $hotel_page_id, 'page', false, $other_lang);
			$sec_visa_page = apply_filters('wpml_object_id', $visa_page_id, 'page', false, $other_lang);


			if (!$sec_flight_page) {
				$sec_flight_page_id = wp_insert_post([
					'post_title'   => 'flight',
					'post_name'    => 'flight-citynet',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'page',
					'post_content' => '[citynet]'
				]);

				// Tell WPML the second post is a translation of the first
				$sitepress->set_element_language_details($sec_flight_page_id, 'post_page', $trid, $other_lang);
				wp_update_post(array(
					'ID' => $sec_flight_page_id,
					'post_name' => 'flight'
				));

				array_push($cn_pages_ids, $sec_flight_page_id);
			}
			if (!$sec_tour_page) {
				$sec_tour_page_id = wp_insert_post([
					'post_title'   => 'tour',
					'post_name'    => 'tour-citynet',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'page',
					'post_content' => '[citynet]'
				]);
				$sitepress->set_element_language_details($sec_tour_page_id, 'post_page', $trid_tour, $other_lang);
				wp_update_post(array(
					'ID' => $sec_tour_page_id,
					'post_name' => 'tour'
				));
				array_push($cn_pages_ids, $sec_tour_page_id);
			}
			if (!$sec_hotel_page) {
				$sec_hotel_page_id = wp_insert_post([
					'post_title'   => 'hotel',
					'post_name'    => 'hotel-citynet',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'page',
					'post_content' => '[citynet]'
				]);
				$sitepress->set_element_language_details($sec_hotel_page_id, 'post_page', $trid_hotel, $other_lang);
				wp_update_post(array(
					'ID' => $sec_hotel_page_id,
					'post_name' => 'hotel'
				));
				array_push($cn_pages_ids, $sec_hotel_page_id);
			}
			if (!$sec_visa_page) {
				$sec_visa_page_id = wp_insert_post([
					'post_title'   => 'visa',
					'post_name'    => 'visa-citynet',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'page',
					'post_content' => '[citynet]'
				]);
				$sitepress->set_element_language_details($sec_visa_page_id, 'post_page', $trid_visa, $other_lang);
				wp_update_post(array(
					'ID' => $sec_visa_page_id,
					'post_name' => 'visa'
				));
				array_push($cn_pages_ids, $sec_visa_page_id);
			}
		}
	}

	add_option('citynet_pages_ids', implode(',', $cn_pages_ids));

	$default_options = [
		'cn_widget_theme' => 'default',
		'cn_panel_users_theme' => 'false'
	];
	foreach ($default_options as $option_name => $default_value) {
		add_option($option_name, $default_value);
	}

	//	add option to check if plugin wizard is enabled
	update_option("cn_show_my_plugin_wizard_notice", 1);
}
register_activation_hook(__FILE__, 'citynet_activate');

add_filter('acf/load_field/name=cn_mobile_codes', function ($field) {
	$field['choices'] = [];

	$string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");
	$json_a = json_decode($string, true);

	foreach ($json_a as $key => $value) {
		$field['choices'][$key] = '+' . $value['code'] . ' ' . $value['name'];
	}
	return $field;
});

// select for cn_default_mobile_code
add_filter('acf/load_field/name=cn_default_mobile_code', function ($field) {
	$field['choices'] = [];

	$string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");
	$json_a = json_decode($string, true);

	foreach ($json_a as $key => $value) {
		$field['choices'][$key] = '+' . $value['code'] . ' ' . $value['name'];
	}
	return $field;
});

// select for cn_default_nationality
add_filter('acf/load_field/name=cn_default_nationality', function ($field) {
	$field['choices'] = [];

	$string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");
	$json_a = json_decode($string, true);

	foreach ($json_a as $key => $value) {
		$field['choices'][$key] =  $value['name'];
	}
	return $field;
});

// select for cn_default_visa_country
add_filter('acf/load_field/name=cn_default_visa_country', function ($field) {
	$field['choices'] = [];

	$string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");
	$json_a = json_decode($string, true);

	foreach ($json_a as $key => $value) {
		$field['choices'][$key] =  $value['name'];
	}
	return $field;
});

// select for cn_country
add_filter('acf/load_field/name=cn-country', function ($field) {
	$field['choices'] = [];
	$string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");
	$json_a = json_decode($string, true);
	foreach ($json_a as $key => $value) {
		$field['choices'][$key] =  $value['name'];
	}
	return $field;
});
// // //select for default value cn_country
// add_filter('acf/load_value/name=cn-visa-most-visited-nationalities', function ($value) {
// 	$value	= array();
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'ترکیه',
// 		'field_65b75282f1cd8' => 'TR',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'فرانسه',
// 		'field_65b75282f1cd8' => 'FR',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'امارات',
// 		'field_65b75282f1cd8' => 'AE',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'آلمان',
// 		'field_65b75282f1cd8' => 'DE',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'ارمنستان',
// 		'field_65b75282f1cd8' => 'AM',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'چین',
// 		'field_65b75282f1cd8' => 'CN',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'آذربایجان',
// 		'field_65b75282f1cd8' => 'AZ',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'اسپانیا',
// 		'field_65b75282f1cd8' => 'ES',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'عراق',
// 		'field_65b75282f1cd8' => 'IQ',
// 	);
// 	$value[] = array(
// 		'field_65b75273f1cd7' => 'تایلند',
// 		'field_65b75282f1cd8' => 'TH',
// 	);
// 	return $value;
// });

function cn_admin_enqueue_scripts()
{
	wp_enqueue_script('cn-admin-js', plugin_dir_url(__FILE__) . 'admin/js/cn-admin.js', null, filemtime(plugin_dir_path(__FILE__) . 'admin/js/cn-admin.js'), true);
}

add_action('acf/input/admin_enqueue_scripts', 'cn_admin_enqueue_scripts');

add_action('init', function () {
	$plugin_pages = cn_generate_pages();
	foreach ($plugin_pages as  $page_slug) :
		if ($page_slug != 'flight' && $page_slug != 'tour' && $page_slug != 'hotel' && $page_slug != 'visa' && $page_slug != 'cip' && $page_slug != 'train') {
			add_rewrite_rule($page_slug . '/?$', 'index.php?cn-plugin-page=' . $page_slug, 'top');
			add_rewrite_rule(strtolower($page_slug) . '/?$', 'index.php?cn-plugin-page=' . strtolower($page_slug), 'top');
		}
	endforeach;

	$plugin_pages_with_parent = cn_pages_with_parent('dashboard');
	foreach ($plugin_pages_with_parent as $page_slug) :
		add_rewrite_rule('dashboard/' . $page_slug . '/?$', 'index.php?cn-plugin-page=' . $page_slug, 'top');
		add_rewrite_rule('dashboard/' . strtolower($page_slug) . '/?$', 'index.php?cn-plugin-page=' . strtolower($page_slug), 'top');
	endforeach;

	$plugin_pages_with_parent = cn_pages_with_parent('cipmanagement');
	foreach ($plugin_pages_with_parent as $page_slug) :
		add_rewrite_rule('cipmanagement/' . $page_slug . '/?$', 'index.php?cn-plugin-page=' . $page_slug, 'top');
		add_rewrite_rule('cipmanagement/' . strtolower($page_slug) . '/?$', 'index.php?cn-plugin-page=' . strtolower($page_slug), 'top');
	endforeach;
	$plugin_pages_with_parent = cn_pages_with_parent('cnpwa');
	foreach ($plugin_pages_with_parent as $page_slug) :
		add_rewrite_rule('cnpwa/' . $page_slug . '/?$', 'index.php?cn-plugin-page=' . $page_slug, 'top');
		add_rewrite_rule('cnpwa/' . strtolower($page_slug) . '/?$', 'index.php?cn-plugin-page=' . strtolower($page_slug), 'top');
	endforeach;

	flush_rewrite_rules();

	// Add a custom capability for access specific roles to citynet settings
	// The 'can_manage_citynet_settings' capability checks in ./admin/admin-menu.php:15
	$roles = [
		'administrator' => true,
		'editor' => true
	];
	$citynet_cap = 'can_manage_citynet_settings';
	$grant_roles = apply_filters('citynet_manage_grant_roles', $roles, $citynet_cap);

	if ($grant_roles && is_array($grant_roles)) :
		foreach ($grant_roles as $role => $grant) :
			$role_object = get_role($role);
			if ($role_object) :
				$role_object->add_cap($citynet_cap, $grant);
			endif;
		endforeach;
	endif;

	// Check to remove manage capability for roles that granted from before, and now not allowed!
	global $wp_roles;
	if ($all_roles = $wp_roles->roles) :
		foreach ($all_roles as $role => $role_info) :
			if (isset($role_info['capabilities'][$citynet_cap]) && !isset($grant_roles[$role])) :
				$role_object = get_role($role);
				if ($role_object) :
					$role_object->remove_cap($citynet_cap);
				endif;
			endif;
		endforeach;
	endif;

	// Remove it later
	citynet_check_submitted_in_list();
}, 10, 0);

add_filter('query_vars', function ($vars) {
	foreach (['cn-plugin-page'] as $parameter) :
		$vars[] = $parameter;
	endforeach;
	return $vars;
});

add_action('parse_request', function ($query) {
	if (
		isset($query->query_vars['cn-plugin-page']) &&
		strtolower($query->query_vars['cn-plugin-page']) != 'panelreturn' &&
		strtolower($query->query_vars['cn-plugin-page']) != 'panelreturn2' &&
		strtolower($query->query_vars['cn-plugin-page']) != 'contractshtml' &&
		strtolower($query->query_vars['cn-plugin-page']) != 'flight' &&
		strtolower($query->query_vars['cn-plugin-page']) != 'cip' &&
		strtolower($query->query_vars['cn-plugin-page']) != 'train' &&
		strtolower($query->query_vars['cn-plugin-page']) != 'loginwithtoken'
	) {
		set_query_var('citynet_plugin_page', $query->query_vars['cn-plugin-page']);
		include 'app-templates/cn-template.php';
		exit();
	} elseif (isset($query->query_vars['cn-plugin-page']) && strtolower($query->query_vars['cn-plugin-page']) == 'panelreturn') {
		set_query_var('citynet_plugin_page', $query->query_vars['cn-plugin-page']);
		include 'app-templates/cn-bank-return-template.php';
		exit();
	} elseif (isset($query->query_vars['cn-plugin-page']) && strtolower($query->query_vars['cn-plugin-page']) == 'panelreturn2') {
		set_query_var('citynet_plugin_page', $query->query_vars['cn-plugin-page']);
		include 'app-templates/cn-bank-return2-template.php';
		exit();
	}
	elseif (isset($query->query_vars['cn-plugin-page']) && strtolower($query->query_vars['cn-plugin-page']) == 'contractshtml') {
		set_query_var('citynet_plugin_page', $query->query_vars['cn-plugin-page']);
		include 'app-templates/cn-contracts-html-template.php';
		exit();
	}elseif (isset($query->query_vars['cn-plugin-page']) && strtolower($query->query_vars['cn-plugin-page']) == 'loginwithtoken') {
		set_query_var('citynet_plugin_page', $query->query_vars['cn-plugin-page']);
		include 'app-templates/cn-login-with-token.php';
		exit();
	}
});
add_filter('document_title_parts', function ($title) {
	if (get_query_var('citynet_plugin_page')) :
		$plugin_pages = cn_generate_pages();
		foreach ($plugin_pages as $plugin_page) {
			$plugin_pages_title[$plugin_page] = $plugin_page;
		}
		$title['title'] = $plugin_pages_title[get_query_var('citynet_plugin_page')];
	endif;
	return $title;
}, 10);


function cn_get_wpml_active_langs()
{
	$lang_codes = [];
	$wpml_langs = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
	// WPML's public filter may be unavailable before the main query (e.g. REST).
	if (!is_array($wpml_langs)) {
		global $sitepress;
		$wpml_langs = is_object($sitepress) && method_exists($sitepress, 'get_active_languages')
			? $sitepress->get_active_languages()
			: array();
	}
	foreach ((array) $wpml_langs as $lang => $lang_info) {
		$lang_codes[] = $lang;
	}
	return $lang_codes;
}
function citynet_get_wpml_default_lang()
{
	$default_lang = apply_filters('wpml_default_language', NULL);
	return $default_lang;
}
add_action("admin_init", function () {
	if (get_option($opt_name = "cn_show_my_plugin_wizard_notice")) {
		delete_option($opt_name);
		add_action("admin_notices", "cn_wizard_notice");
	}
	return;
});
function cn_wizard_completed()
{
	return false;
}
function cn_wizard_notice()
{
	if (cn_wizard_completed()) {
		return;
	} // completed already
?>
	<div class="cn-layer"></div>
	<div class="cn-updated cn-notice is-dismissible">
		<p><?php _e('Citynet Plugin Enabled!', 'citynet_plugin'); ?></p>
		<p><?php _e('Go to the Plugin Settings page for full activation.', 'citynet_plugin'); ?></p>
		<p><a href="<?php admin_url(); ?>?page=citynet-settings" class="button button-primary"><?php _e('Setting Page', 'citynet_plugin'); ?></a></p>
	</div>
<?php
}

function citynet_deactivation()
{
	foreach (['cn_widget_theme', 'cn_panel_users_theme', 'citynet_pages_ids', 'citynet_pages_to_remove'] as $option_name) {
		delete_option($option_name);
	}
}
register_deactivation_hook(__FILE__, 'citynet_deactivation');

//restrict plugin pages from delete
function cn_page_deletion($post_ID)
{
	$cn_pages    = get_option('citynet_pages_ids');
	$cn_page_ids = explode(',', $cn_pages);
	if (in_array($post_ID, $cn_page_ids)) {
		// wp_update_post([
		// 	'ID'          => $post_ID,
		// 	'post_status' => 'publish'
		// ]);
		$cn_page_ids = array_merge(array_diff($cn_page_ids, [$post_ID]));
		update_option('citynet_pages_ids', implode(',', $cn_page_ids));
		wp_delete_post($post_ID, true);
		wp_redirect(admin_url() . 'edit.php?post_type=page&cn_ids=' . $post_ID);
		exit();
	}
}
add_action('wp_trash_post', 'cn_page_deletion', 10, 1);

/** Hotel SEO rows must contain both an existing post and a hotel selection. */
function cn_validate_hotel_seo_rows($valid, $value, $field = array(), $input = '')
{
	if ($valid !== true || empty($value)) {
		return $valid;
	}

	$error = __('در «صفحات داخلی هتل جهت سئو»، برای هر ردیف یک برگه یا مطلب معتبر و یک هتل انتخاب کنید؛ ردیف خالی یا ناقص را حذف کنید.', 'citynet_plugin');
	if (!is_array($value)) {
		return $error;
	}

	foreach ($value as $index => $row) {
		if ($index === 'acfcloneindex') {
			continue;
		}
		if (!is_array($row)) {
			return $error;
		}

		// Form submissions use field keys; update_field() can use field names.
		$post_id = $row['field_627669912ea7f'] ?? $row['cn_hotelpage'] ?? 0;
		$hotel = $row['field_62764c24bc325'] ?? $row['cn_hotelname'] ?? '';
		if ($post_id instanceof WP_Post) {
			$post_id = $post_id->ID;
		}
		if (!is_scalar($post_id) || !ctype_digit((string) $post_id) || (int) $post_id < 1
			|| !is_string($hotel) || trim($hotel) === '') {
			return $error;
		}

		$post = get_post((int) $post_id);
		if (!$post || $post->post_status === 'trash') {
			return $error;
		}
	}

	return $valid;
}
add_filter('acf/validate_value/key=field_62764b4ba3aee', 'cn_validate_hotel_seo_rows', 10, 4);

/** Also protect programmatic ACF updates, which bypass form validation. */
function cn_guard_hotel_seo_update($check, $value, $post_id, $field)
{
	if (($field['key'] ?? '') === 'field_62764b4ba3aee'
		&& cn_validate_hotel_seo_rows(true, $value) !== true) {
		return false;
	}
	return $check;
}
add_filter('acf/pre_update_value', 'cn_guard_hotel_seo_update', 10, 4);

/** Find live references in every saved ACF language, independently of WPML. */
function cn_is_hotel_seo_post($post_id)
{
	global $wpdb;
	$post_id = absint($post_id);
	if (!$post_id) {
		return false;
	}

	$option_names = $wpdb->get_col($wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s",
		$wpdb->esc_like('options') . '%' . $wpdb->esc_like('_cn_hotel-page_') . '%' . $wpdb->esc_like('_cn_hotelpage'),
		(string) $post_id
	));
	foreach ($option_names as $option_name) {
		if (preg_match('/^(options(?:_.+)?_cn_hotel-page)_([0-9]+)_cn_hotelpage$/', $option_name, $matches)
			&& (int) $matches[2] < (int) get_option($matches[1], 0)) {
			// Ignore stale subfield options left behind after a row was removed.
			return true;
		}
	}
	return false;
}

/** Stop both trashing and permanent deletion before any data is removed. */
function cn_guard_hotel_seo_post_deletion($check, $post)
{
	return cn_is_hotel_seo_post($post->ID) ? false : $check;
}
add_filter('pre_trash_post', 'cn_guard_hotel_seo_post_deletion', 10, 2);
add_filter('pre_delete_post', 'cn_guard_hotel_seo_post_deletion', 10, 2);

/** Hide deletion controls and reject admin, bulk and REST deletion requests. */
function cn_hotel_seo_delete_capability($caps, $cap, $user_id, $args)
{
	if ($cap === 'delete_post' && !empty($args[0]) && cn_is_hotel_seo_post($args[0])) {
		$caps[] = 'do_not_allow';
	}
	return $caps;
}
add_filter('map_meta_cap', 'cn_hotel_seo_delete_capability', 10, 4);

function cn_hotel_seo_post_state($states, $post)
{
	if (cn_is_hotel_seo_post($post->ID)) {
		$states['cn_hotel_seo'] = __('سیستمی: متصل به سئوی هتل سیتی‌نت (حذف غیرفعال)', 'citynet_plugin');
	}
	return $states;
}
add_filter('display_post_states', 'cn_hotel_seo_post_state', 10, 2);

// Public captcha verification endpoint; Google validates the submitted token.
function generate_captcha_wp_api()
{
	register_rest_route('citynet-api/v1', '/verify-captcha/', array(
		'methods' => 'POST',
		'callback' => 'generate_captcha_info_api',
		'permission_callback' => '__return_true',
	));
}
add_action('rest_api_init', 'generate_captcha_wp_api');

function generate_captcha_info_api($request)
{
	return cn_api_verify_captcha($request->get_json_params());
}

function generate_panel_info()
{
	$selected_services = [];
	$pages_with_panel = [];
	$social_urls = [];
	$themes = [];
	$hotels_sortkey = [];
	$agency_tel = [];
	$agency_tel_txt = [];
	$cn_airport_flight = [];
	$cn_default_hotel = [];
	$cn_default_cip = [];
	$cn_not_found_result_flight = [];
	$cn_not_found_result_train = [];
	$cn_not_found_result_hotel = [];
	$cn_not_found_result_flightplushotel = [];
	$cn_not_found_result_tour = [];
	$cn_not_found_result_package = [];
	$cn_not_found_result_cip = [];
	$cn_not_found_result_insurance = [];
	$header_menu_settings = [];
	$pwa = [];
	$default_currency = [];
	$site_langs = [];
	$mobile_codes = [];
	$default_mobile_code = [];
	$default_nationality = [];
	$hide_close_flight = [];
	$hide_zero_price  = [];
	$cities_for_seo = [];
	$hotels_for_seo = [];
	$cip_airports_for_seo = [];
	$visa_countries_for_seo = [];
	$insurance_countries_for_seo = [];
	$most_visited_airports = [];
	$most_visited_airports_cols = [];
	$most_visited_countries = [];
	$most_visited_nationalities = [];
	$most_visited_cities = [];
	$most_visited_cities_cols = [];
	$loading_settings = [];
	$menu_show_type = [];
	$flights_show_type_b2b = [];
	$flights_show_type_b2c = [];
	$flights_show_type_admin = [];
	$flights_show_type_counter = [];
	$display_registration_date = [];
	$display_registration_calender = [];
 	$class_cabin_in_flight = [];
	$hide_flight_type_label = [];
	$datepicker_locales = [];
	$auth_info = [];
	$hide_suppliers_counter = [];
	$hide_suppliers_admin = [];
	$hide_logo_contract_report = [];
	$hide_wallet = [];
	$hide_credit = [];
	$hide_bank = [];
	$filter_subsystems = [];
	$hide_system_prices = [];
	$hide_charter_prices = [];
	$show_alert_register = [];
	$show_citizens_checkbox = [];
	$morning_time_cip = [];
	$evening_time_cip = [];
	$default_visa_country = [];
	$special_offer_hotel = [];
	$display_support_phone = [];
	$show_all_prefixes = [];
	$get_stations_trains = [];
	$get_train_information_seo = [];
	$get_package_information = [];
	$get_flight_information_seo = []; // Initialize the array for flight information SEO
	$stations_trains_default_price_display = [];
	$get_tax_display = [];



	$default_lang = citynet_plugin_default_language();
	$flight_routes = [];
	$required_fa_name = [];
	$other_langs = [$default_lang];

	if (cn_is_multilang_active()) {
		$langs = cn_get_wpml_active_langs();
		foreach ($langs as $lang) {
			if ($lang != $default_lang) {
				$other_langs[] = $lang;
			}
		}
	}
	// Start languages loop
	foreach ($other_langs as $lang) {
		$site_langs[] = $lang;
		$lang_prefix = citynet_plugin_options_language_prefix($lang);
		$flight_routes[$lang] = citynet_plugin_flight_routes($lang);
		$required_fa_name[$lang] = cn_option('cn-required-fa-name', $lang_prefix) == '1';
		$services_tabs = cn_repeater('cn_services', ['cn-select-service', 'name', 'active', 'show', 'hide-logo-footer','rules'], $lang_prefix);
		//citynet_print_r( $services_tabs  );
		if ($services_tabs) {
			foreach ($services_tabs as $row) {
				$selected_services[$lang][] = [
					'name' => find_service_name($lang, $row['name'], $row['cn-select-service']),
					'responsive_name' => find_service_name($lang, $row['name'], $row['cn-select-service']),
					'icon' => find_service_tab($row['cn-select-service']),
					'service' => find_service_tab($row['cn-select-service']),
					'active' => ($row['active'] == 1) ? true : false,
					'show' => ($row['show'] == 1) ? true : false,
					'hideLogoFooter' => ($row['hide-logo-footer'] == 1) ? true : false,
					'rules' => $row['rules'],
				];
			}
		} else {
			$selected_services[$lang] = [
				[
					'name' => [
						'fa' => 'پرواز',
						'en' => 'Flight',
						'ar' => 'طيران'
					],
					'responsive_name' =>  [
						'fa' => 'پرواز',
						'en' => 'Flight',
						'ar' => 'طيران'
					],
					'icon' => 'Flight',
					'service' => 'Flight',
					'active' => true,
					'show' =>  true,
					'hide-logo-footer' => false
				],
				[
					'name' => [
						'fa' => 'هتل',
						'en' => 'Hotel',
						'ar' => 'الفندق'
					],
					'responsive_name' =>  [
						'fa' => 'هتل',
						'en' => 'Hotel',
						'ar' => 'الفندق'
					],
					'icon' => 'Hotel',
					'service' => 'Hotel',
					'active' => true,
					'show' =>  true,
					'hide-logo-footer' => false
				],
				[
					'name' => [
						'fa' => 'پرواز+هتل',
						'en' => 'Flight+Hotel',
						'ar' => 'طيران+الفندق'
					],
					'responsive_name' =>  [
						'fa' => 'پرواز+هتل',
						'en' => 'Flight+Hotel',
						'ar' => 'طيران+الفندق'
					],
					'icon' => 'FlightAndHotel',
					'service' => 'FlightAndHotel',
					'active' => true,
					'show' =>  true,
					'hide-logo-footer' => false
				],
			];
		}

		$auth_info[$lang] = [
			'automaitonOTP' => cn_option('cn-auth-automaiton-otp', $lang_prefix) ? (int)cn_option('cn-auth-automaiton-otp', $lang_prefix) : 0,
			'usernameTitle' => cn_option('cn-auth-username-title', $lang_prefix),
			'subtitle' => cn_option('cn-auth-subtitle', $lang_prefix),
			'loginType' => cn_option('cn_login_type', $lang_prefix) ? cn_option('cn_login_type', $lang_prefix) : null,
			'hideTypes' => cn_option('cn_login_hide_show', $lang_prefix) ? cn_option('cn_login_hide_show', $lang_prefix) : null,
			'defaultLoginType' => cn_option('cn_default_login_type', $lang_prefix) ? cn_option('cn_default_login_type', $lang_prefix) : null,
		];

		$optional_tabs = cn_repeater('cn_optional_tabs', ['cn_title', 'cn_url', 'cn_icon'], $lang_prefix);
		if ($optional_tabs) {
			foreach ($optional_tabs as $row) {
				$selected_services[$lang][] = [
					'name'    => [
						$lang => $row['cn_title'],
						// 'en' => $row['cn_title'],
					],
					'responsive_name'   => [
						$lang => $row['cn_title'],
						//'en' => $row['cn_title']
					],
					'icon'    => $row['cn_icon'],
					'url'    => $row['cn_url'],
					'service' => 'optional',
					'active'  => true,
					'show'  => true
				];
			}
		}
		$hotels_cities = cn_repeater('cn_hotel-city', ['cn_citypage', 'cn_city', 'cn_search2'], $lang_prefix);
		if ($hotels_cities) {
			foreach ($hotels_cities as $row) {
				$slug = get_page_uri($row['cn_citypage']);

				$city		= $row['cn_city'];
				$city_parts = explode( '|', $city );
				if ( count( $city_parts ) != 2 ) {
					$hotel = str_replace( "'", '"', $city );
					$hotel = json_decode( $hotel, true );
					$city  = sprintf( '%s|%s', $hotel['_id'], $hotel['cityNameEn'] );
				}

				$cities_for_seo[$lang][] = [
					'slug'	 => $slug,
					'city'	 => $city,
					'search' => $row['cn_search2']
				];
			}
		} else {
			$cities_for_seo[$lang] = [];
		}
		$hotels_single_pages = cn_repeater('cn_hotel-page', ['cn_hotelpage', 'cn_hotelname', 'cn_display_panel'], $lang_prefix);
		if ($hotels_single_pages) {
			foreach ($hotels_single_pages as $row) {
				$slug = get_permalink($row['cn_hotelpage']);
				$hotels_for_seo[$lang][] = [
					'slug'  => $slug,
					'hotel' => $row['cn_hotelname'],
					'show'  => $row['cn_display_panel'],
				];
			}
		} else {
			$hotels_for_seo[$lang] = [];
		}
		//display panel in internal pages or posts
		$panel_pages = cn_repeater('cn_panel_pages', ['cn_panel_page', 'cn_select_tab'], $lang_prefix);
		if ($panel_pages) {
			foreach ($panel_pages as $row) {
				$slug = get_permalink($row['cn_panel_page']);
				$pages_with_panel[$lang][] = [
					'slug'  => $slug,
					'tab'   => ($row['cn_select_tab']) ? $row['cn_select_tab'] : 'All'
				];
			}
		} else {
			$pages_with_panel[$lang] = [];
		}
		$airports = cn_repeater('cn-most-visited-airports', ['cn-title', 'cn-airport'], $lang_prefix);
		if ($airports) {
			foreach ($airports as $row) {
				$most_visited_airports[$lang][] = [
					'title'  => $row['cn-title'],
					'airport'  => $row['cn-airport']
				];
			}
		} else {
			$most_visited_airports[$lang] = [];
		}

		//seo visa country
		$visa_countries = cn_repeater('cn_seo_visa_pages', ['cn_visa_page', 'cn_visa_country', 'cn_show_result','cn_visa_nationality'], $lang_prefix);
		if ($visa_countries) {
			foreach ($visa_countries as $row) {
				$slug = get_permalink($row['cn_visa_page']);
				$home_url = home_url(); // Get the home URL
				$slug = str_replace($home_url, '', $slug); // Remove the home_url() portion from the permalink
				$slug = ltrim($slug, '/');
				if($slug){
					$visa_countries_for_seo[$lang][] = [
						'slug'  => $slug,
						'country'  => $row['cn_visa_country'],
						'nationality'  => $row['cn_visa_nationality'],
						'showResult'  => $row['cn_show_result'],
					];
				}
				
			}
		} else {
			$visa_countries_for_seo[$lang] = [];
		}

		//seo cip airport
		$cip_airports = cn_repeater('cn_seo_cip_pages', ['cn_cip_page', 'cn_cip_airport', 'cn_cip_flightType' , 'cn_cip_tripType', 'cn_show_result'], $lang_prefix);
		if ($cip_airports) {
			foreach ($cip_airports as $row) {
				$slug = get_permalink($row['cn_cip_page']);
				$home_url = home_url(); // Get the home URL
				$slug = str_replace($home_url, '', $slug); // Remove the home_url() portion from the permalink
				$slug = ltrim($slug, '/');
				if($slug){
					$cip_airports_for_seo[$lang][] = [
						'slug'  => $slug,
						'tripType' => $row['cn_cip_tripType'],
						'flightType' => $row['cn_cip_flightType'],
						'airport'  => $row['cn_cip_airport'],
						'showResult'  => $row['cn_show_result'],
					];
				}
			}
		} else {
			$cip_airports_for_seo[$lang] = [];
		}

		//seo insurance page
		$insurance_countries = cn_repeater('cn_seo_insurance_pages', ['cn_insurance_page', 'cn_insurance_country','cn_insurance_durations', 'cn_insurance_visatype' , 'cn_show_result'], $lang_prefix);
		if ($insurance_countries) {
			foreach ($insurance_countries as $row) {
				$slug = get_permalink($row['cn_insurance_page']);
				$home_url = home_url(); // Get the home URL
				$slug = str_replace($home_url, '', $slug); // Remove the home_url() portion from the permalink
				$slug = ltrim($slug, '/');
				if($slug){
					$insurance_countries_for_seo[$lang][] = [
						'slug'  => $slug,
						'country'  => $row['cn_insurance_country'],
						'durations'  => $row['cn_insurance_durations'],
						'visaType'  => $row['cn_insurance_visatype'],
						'showResult'  => $row['cn_show_result'],
					];
				}
				
			}
		} else {
			$insurance_countries_for_seo[$lang] = [];
		}

		$most_visited_airports_cols[$lang] = cn_option('cn-most-visited-airports-columns', $lang_prefix) ? cn_option('cn-most-visited-airports-columns', $lang_prefix) : 4;

		$hide_flight_type_label[$lang] = cn_option('cn_hide_flight_type_label', $lang_prefix) ? true : false;

		$datepicker_locales[$lang] = cn_option('cn_datepicker_locale', $lang_prefix) ? cn_option('cn_datepicker_locale', $lang_prefix) : null;

		$cities = cn_repeater('cn-most-visited-cities', ['cn-title', 'cn-city'], $lang_prefix);
		if ($cities) {
			foreach ($cities as $row) {
				$most_visited_cities[$lang][] = [
					'title'  => $row['cn-title'],
					'city'  => $row['cn-city']
				];
			}
		} else {
			$most_visited_cities[$lang] = [];
		}
		$most_visited_cities_cols[$lang] = cn_option('cn-most-visited-cities-columns', $lang_prefix) ? cn_option('cn-most-visited-cities-columns', $lang_prefix) : 4;

		//insurance
		$countries = cn_repeater('cn-most-visited-countries', ['cn-title', 'cn-country'], $lang_prefix);
		if ($countries) {
			foreach ($countries as $row) {
				$most_visited_countries[$lang][] = [
					'title'  => $row['cn-title'],
					'country'  => $row['cn-country']
				];
			}
		} else {
			$most_visited_countries[$lang] = [
				[
					'title' => 'ترکیه',
					'country' => 'TR',
				],
				[
					'title' => 'فرانسه',
					'country' => 'FR',
				],
				[
					'title' => 'امارات',
					'country' => 'AE',
				],
				[
					'title' => 'آلمان',
					'country' => 'DE',
				],
				[
					'title' => 'ارمنستان',
					'country' => 'AM',
				],
				[
					'title' => 'چین',
					'country' => 'CN',
				],
				[
					'title' => 'آذربایجان',
					'country' => 'AZ',
				],
				[
					'title' => 'اسپانیا',
					'country' => 'ES',
				],
				[
					'title' => 'عراق',
					'country' => 'IQ',
				],
				[
					'title' => 'تایلند',
					'country' => 'TH',
				],

			];
		}

		//visa
		$nationalities = cn_repeater('cn-visa-most-visited-nationalities', ['cn-title', 'cn-country'], $lang_prefix);
		if ($nationalities) {
			foreach ($nationalities as $row) {
				$most_visited_nationalities[$lang][] = [
					'title'  => $row['cn-title'],
					'country'  => $row['cn-country']
				];
			}
		}
		

		//train
		$stations_trains = cn_repeater('busy-cities-stations-trains', ['title', 'city-or-train-station','choices'], $lang_prefix);
		if ($stations_trains) {
			foreach ($stations_trains as $row) {
				$get_stations_trains[$lang][] = [
					'title'  => $row['title'],
					'stationsTrains'  => $row['city-or-train-station']
				];
			}
		}
		//train seo
		$stations_trains_seo = cn_repeater('train-information-seo', ['train-page', 'origin-city','destination-city','show'], $lang_prefix);
		
		if ($stations_trains_seo) {
			foreach ($stations_trains_seo as $row) {
				$slug = get_permalink($row['train-page']);
				$home_url = home_url(); // Get the home URL
				$slug = str_replace($home_url, '', $slug); // Remove the home_url() portion from the permalink
				$slug = ltrim($slug, '/');
				$get_train_information_seo[$lang][] = [
					'slug'  => $slug,
					'origin-city'  => $row['origin-city'],
					'destination-city'  => $row['destination-city'],
					'show'  => $row['show']
				];
			}
		}
		// citynet_print_r($get_train_information_seo);
		// Flight SEO
		$flights_seo = cn_repeater('flight-information-seo', ['flight-page', 'origin-city', 'destination-city', 'show'], $lang_prefix);

		if ($flights_seo) {
			foreach ($flights_seo as $row) {
				$slug = get_permalink($row['flight-page']); // Get the permalink for the selected page
				$home_url = home_url(); // Get the home URL
				$slug = str_replace($home_url, '', $slug); // Remove the home_url() portion from the permalink
				$slug = ltrim($slug, '/'); // Trim leading slashes

				// Decode the slug to handle Persian characters
				$slug = rawurldecode($slug); // Decode the slug

				$get_flight_information_seo[$lang][] = [
					'slug' => $slug,
					'origin-city' => $row['origin-city'],
					'destination-city' => $row['destination-city'],
					'show' => $row['show']
				];
			}
		}
		$packages = cn_repeater('package_settings', ['package_type', 'package_value'], $lang_prefix);
		
		if ($packages) {
			foreach ($packages as $row) {
				$values = [];
		
				if (!empty($row['package_value'])) {
					// تبدیل رشته به آرایه با استفاده از کاما به عنوان جداکننده
					$values = array_map('trim', explode(',', $row['package_value']));
				}
		
				$get_package_information[$lang][] = [
					'package_type' => $row['package_type'],
					'package_value' => $values
				];
			}
		}
		
		$colors[$lang]               = [
			'primary'   => cn_option('cn_main-color', $lang_prefix),
			'buttons'   => cn_option('cn_buttons-color', $lang_prefix),
			'SearchHeader'   => cn_option('cn_search-color', $lang_prefix),
			'darkmodePrimary'   => cn_option('cn_main-darkmode-color', $lang_prefix),
			'darkmodeButtons'   => cn_option('cn_buttons-darkmode-color', $lang_prefix),

		];
		$social_urls = [];
		$socials        = [
			[1 => 'instagram'],
			[2 => 'telegram'],
			[3 => 'twitter'],
			[4 => 'facebook'],
			[5 => 'linkedin'],
			[6 => 'whatsapp']
		];
		$social_counter = 1;
		foreach ($socials as $social) {
			if (cn_option('cn_' . $social[$social_counter], $lang_prefix)) {
				$social_urls[$social[$social_counter]] = cn_option('cn_' . $social[$social_counter], $lang_prefix);
			}
			$social_counter++;
		}
		$themes[$lang] = cn_option('cn_widget_theme', $lang_prefix);

		$hotels_sortkey[$lang] = cn_option('cn-hotels-sortkey', $lang_prefix) ? cn_option('cn-hotels-sortkey', $lang_prefix) : 'default';

		$add_to_wp_menu = cn_option('cn_add-to-wp-menu', $lang_prefix);
		$panel_menu_custom_wrapper_id = cn_option('cn_custom-panel-menu-wrapper-id', $lang_prefix);
		$header_menu_settings[$lang]['addToMenu'] = ($add_to_wp_menu ? true : false);
		$header_menu_settings[$lang]['lightTheme'] = cn_option('cn_menu-theme', $lang_prefix) ? true : false;
		if ($add_to_wp_menu) $header_menu_settings[$lang]['wrapperId'] = ($panel_menu_custom_wrapper_id ? $panel_menu_custom_wrapper_id : null);

		$agency_tel[$lang] = cn_option('cn_agency_phone_number', $lang_prefix) ? cn_option('cn_agency_phone_number', $lang_prefix) : cn_option('tel', $lang_prefix);
		$agency_tel_txt[$lang] = cn_option('cn_agency_phone_txt', $lang_prefix) ? cn_option('cn_agency_phone_txt', $lang_prefix) : cn_option('tel', $lang_prefix);
		$cn_airport_flight[$lang] = cn_option('cn_airport_flight', $lang_prefix) ? cn_option('cn_airport_flight', $lang_prefix) : '';
		$cn_default_hotel[$lang] = cn_option('cn_default_hotel', $lang_prefix) ? cn_option('cn_default_hotel', $lang_prefix) : '';
		$cn_default_cip[$lang] = cn_option('cn_default_cip', $lang_prefix) ? cn_option('cn_default_cip', $lang_prefix) : '';
		$cn_cip_optional_passport[$lang] = cn_option('cn-cip-optional-passport', $lang_prefix) ? cn_option('cn-cip-optional-passport', $lang_prefix) : 0;

		//shift timo for cip
		$morning_time_cip[$lang] = cn_option('cn-morning-time', $lang_prefix) ? cn_option('cn-morning-time', $lang_prefix) : null;
		$evening_time_cip[$lang] = cn_option('cn-evening-time', $lang_prefix) ? cn_option('cn-evening-time', $lang_prefix) : null;

		$cn_not_found_result_flight[$lang] = cn_option('not-found-result-flight', $lang_prefix) ? cn_option('not-found-result-flight', $lang_prefix) : '';
		$cn_not_found_result_train[$lang] = cn_option('not-found-result-train', $lang_prefix) ? cn_option('not-found-result-train', $lang_prefix) : '';
		$cn_not_found_result_hotel[$lang] = cn_option('not-found-result-hotel', $lang_prefix) ? cn_option('not-found-result-hotel', $lang_prefix) : '';
		$cn_not_found_result_flightplushotel[$lang] = cn_option('not-found-result-flightplushotel', $lang_prefix) ? cn_option('not-found-result-flightplushotel', $lang_prefix) : '';
		$cn_not_found_result_tour[$lang] = cn_option('not-found-result-tour', $lang_prefix) ? cn_option('not-found-result-tour', $lang_prefix) : '';
		$cn_not_found_result_package[$lang] = cn_option('not-found-result-package', $lang_prefix) ? cn_option('not-found-result-package', $lang_prefix) : '';
		$cn_not_found_result_cip[$lang] = cn_option('not-found-result-cip', $lang_prefix) ? cn_option('not-found-result-cip', $lang_prefix) : '';
		$cn_not_found_result_insurance[$lang] = cn_option('not-found-result-insurance', $lang_prefix) ? cn_option('not-found-result-insurance', $lang_prefix) : '';
		$special_offer_hotel[$lang] = cn_option('cn-special-offer-hotel', $lang_prefix) ? cn_option('cn-special-offer-hotel', $lang_prefix) : '';
		$display_support_phone[$lang] = cn_option('cn-display-support-phone', $lang_prefix) ? cn_option('cn-display-support-phone', $lang_prefix) : '';



		$menu_items = [];
		$menu_items_count = cn_option('cn_menu_items', $lang_prefix);
		if ($menu_items_count) {
			$counter = 0;
			while ($counter < $menu_items_count) {
				$menu_items[] = [
					'title' => cn_option('cn_menu_items_' . $counter . '_cn_title', $lang_prefix),
					'link' => cn_option('cn_menu_items_' . $counter . '_cn_link', $lang_prefix)
				];
				$counter++;
			}
		}

		$loading_settings[$lang] = [
			'flight' => [
				'text' => cn_option('cn-flight-loading-text', $lang_prefix),
				'img' => wp_get_attachment_image_url(cn_option('cn-flight-loading-img', $lang_prefix), 'full'),
			],
			'hotel' => [
				'text' => cn_option('cn-hotel-loading-text', $lang_prefix),
				'img' => wp_get_attachment_image_url(cn_option('cn-hotel-loading-img', $lang_prefix), 'full'),
			],
			'flightAndHotel' => [
				'text' => cn_option('cn-fph-loading-text', $lang_prefix),
				'img' => wp_get_attachment_image_url(cn_option('cn-fph-loading-img', $lang_prefix), 'full'),
			],
		]; 

		$pwa[$lang] = [
			'menuBanner' => wp_get_attachment_image_url(cn_option('cn_menu_banner', $lang_prefix), 'full'),
			'homepageBanners' => [
				0 => [
					'banner' => wp_get_attachment_image_url(cn_option('cn_pwa_banner1', $lang_prefix), 'full'),
					'url' => cn_option('cn_pwa_banner_link1', $lang_prefix)
				],
				1 => [
					'banner' => wp_get_attachment_image_url(cn_option('cn_pwa_banner2', $lang_prefix), 'full'),
					'url' => cn_option('cn_pwa_banner_link2', $lang_prefix)
				]
			],
			'socialUrls' => $social_urls,
			'menuItems' => $menu_items
		];
		$default_currency[$lang] = cn_option('cn_default_currency', $lang_prefix) ? cn_option('cn_default_currency', $lang_prefix) : null;
		$menu_show_type[$lang] = cn_option('cn-menu-show-type', $lang_prefix) ? cn_option('cn-menu-show-type', $lang_prefix) : 'default';
		$flights_show_type_b2c[$lang] = cn_option('cn-flights-show-type-b2c', $lang_prefix) ? cn_option('cn-flights-show-type-b2c', $lang_prefix) : 'simple';
		$flights_show_type_b2b[$lang] = cn_option('cn-flights-show-type-b2b', $lang_prefix) ? cn_option('cn-flights-show-type-b2b', $lang_prefix) : 'simple';
		$flights_show_type_admin[$lang] = cn_option('cn-flights-show-type-admin', $lang_prefix) ? cn_option('cn-flights-show-type-admin', $lang_prefix) : 'simple';
		$flights_show_type_counter[$lang] = cn_option('cn-flights-show-type-counter', $lang_prefix) ? cn_option('cn-flights-show-type-counter', $lang_prefix) : 'simple';
		$display_registration_date[$lang] = cn_option('display-registration-date', $lang_prefix) ? cn_option('display-registration-date', $lang_prefix) : 'both';
		$display_registration_calender[$lang] = cn_option('display-registration-calender', $lang_prefix) ? cn_option('display-registration-calender', $lang_prefix) : 'both';
		$class_cabin_in_flight[$lang] = cn_option('cn_class_cabin_in_flight', $lang_prefix) ? cn_option('cn_class_cabin_in_flight', $lang_prefix) : null;
		$hide_suppliers_counter[$lang] = cn_option('cn-hide-suppliers-counter', $lang_prefix) ? true : false;
		$hide_suppliers_admin[$lang] = cn_option('cn-hide-suppliers-admin', $lang_prefix) ? true : false;

		$hide_logo_contract_report[$lang] = cn_option('hide-logo-contract-report', $lang_prefix) ? true : false;
		$show_all_prefixes[$lang] = cn_option('show-all-prefixes', $lang_prefix) ? cn_option('show-all-prefixes', $lang_prefix) : '';
		if($show_all_prefixes[$lang] == '1'){
			$mobile_codes[$lang] = ['ALL'];
	   }else{
		   $mobile_codes[$lang] = cn_option('cn_mobile_codes', $lang_prefix) ? cn_option('cn_mobile_codes', $lang_prefix) : null;
	   }

	   $stations_trains_default_price_display[$lang] = cn_option('default-price-display', $lang_prefix) ? cn_option('default-price-display', $lang_prefix) : '';
	   $get_stations_trains_default_price_display[$lang] = ($stations_trains_default_price_display[$lang] == '1')? 'international': 'domestic';	
	   
	   $tax_display[$lang] = cn_option('tax_display', $lang_prefix) ? cn_option('tax_display', $lang_prefix) : '';
	   $get_tax_display[$lang] = ($tax_display[$lang] == '1')? true : false;
		$default_mobile_code[$lang] = cn_option('cn_default_mobile_code', $lang_prefix) ? cn_option('cn_default_mobile_code', $lang_prefix) : null;

		$default_nationality[$lang] = cn_option('cn_default_nationality', $lang_prefix) ? cn_option('cn_default_nationality', $lang_prefix) : null;
		$default_visa_country[$lang] = cn_option('cn_default_visa_country', $lang_prefix) ? cn_option('cn_default_visa_country', $lang_prefix) : null;

		$hide_close_flight[$lang] = cn_option('cn-hide-close-flight', $lang_prefix) ? true : false;
		$hide_zero_price[$lang] = cn_option('cn-hide-zero-price', $lang_prefix) ? true : false;

		$hide_wallet[$lang] = cn_option('cn-hide-wallet', $lang_prefix) ? true : false;
		$hide_credit[$lang] = cn_option('cn-hide-credit', $lang_prefix) ? true : false;
		$hide_bank[$lang] = cn_option('cn-hide-bank', $lang_prefix) ? true : false;

		$hide_charter_prices[$lang] = cn_option('cn_hide_charter_prices') ? true : false;
		$hide_system_prices[$lang] = cn_option('cn_hide_system_prices') ? true : false;

		//airlins settings
		$filter_subsystems[$lang] =cn_option('cn-filter-subsystems', $lang_prefix) ? true : false;

		$show_alert_register[$lang] = cn_option('cn-show-alert-register', $lang_prefix) ? true : false;
		
		$show_citizens_checkbox[$lang] = cn_option('cn-show-citizens-checkbox', $lang_prefix) ? true : false;
		$max_adult_child_search[$lang] =cn_option('max_adult_child_search', $lang_prefix) ?cn_option('max_adult_child_search', $lang_prefix) : '9';

		
		// wp_die(citynet_print_r($social_urls));
	}
	// End languages loop

	$org_id = (int)cn_option('cn_org-id', '') ? (int)cn_option('cn_org-id', '') : null;
	$base_url = cn_option('cn_baseurl', '');

	$inventory_id = (int)get_field('cn_org-inventory-id', 'option') ? (int)get_field('cn_org-inventory-id', 'option') : null;
	$plugin_data = get_plugin_data(__FILE__);
	$plugin_version = $plugin_data['Version'];
	
	$hotel_search_option = cn_option('hotel_search_option', '') ? cn_option('hotel_search_option', '') : '';
	// Retrieve the values as integers
	$display_mobile_number = (int) get_field('display_mobile_number', 'option');
	$print_barcode_label = (int) get_field('print_barcode_label', 'option');

	return [
		'baseUrl'		   => get_home_url(null, '/'),
		'siteName'         => get_bloginfo('name'),
		'agencyPhone'      =>  $agency_tel,
		'agencyPhoneTxt'      =>  $agency_tel_txt,
		'siteAddress'      => home_url(),
		'siteLanguage'     => $default_lang,
		'contractsByTravel'     => get_field('cn-contracts-by-travel', 'option') ? true : false,
		'selectedServices' => $selected_services,
		'classCabinInFlight' => $class_cabin_in_flight,
		'seoRoutes'        => $flight_routes,
		'seoCities'    	=> $cities_for_seo,
		'seoHotels'    	=> $hotels_for_seo,
		'seoVisas'    	=> $visa_countries_for_seo,
		'seoInsurances' => $insurance_countries_for_seo,
		'seoCips'    	=> $cip_airports_for_seo,
		'panelPages'     => $pages_with_panel,
		'mostVisitedAirports'    	=> [
			'items' => $most_visited_airports,
			'cols' => $most_visited_airports_cols
		],
		'mostVisitedCountries' => $most_visited_countries,
		'mostVisitedNationalities' => $most_visited_nationalities,
		'defaultCity' => $cn_airport_flight,
		'defaultHotel' => $cn_default_hotel,
		'defaultCip' => $cn_default_cip,
		'cipOptionalPassport' => $cn_cip_optional_passport,
		'notFoundResult' => [
			'flight' =>  $cn_not_found_result_flight,
			'hotel' => $cn_not_found_result_hotel,
			'train'  => $cn_not_found_result_train,
			'flightplushotel' => $cn_not_found_result_flightplushotel,
			'tour' => $cn_not_found_result_tour,
			'package' => $cn_not_found_result_package,
			'cip' => $cn_not_found_result_cip,
			'insurance' => $cn_not_found_result_insurance,
		],
		'mostVisitedCities'    	=> [
			'items' => $most_visited_cities,
			'cols' => $most_visited_cities_cols
		],
		'loginInfo'        => $auth_info,
		'colors'           => $colors,
		'datepickerLocales'           => $datepicker_locales,
		'loadingSettings'  => $loading_settings,
		'widgetTheme'      => $themes,
		'hasTrackOrder'    => get_field('cn_tour_track_order', 'option') ? get_field('cn_tour_track_order', 'option') : 0,
		'hideChargeCreditMenu'    => get_field('cn-hide-charge-credit-menu', 'option') ? get_field('cn-hide-charge-credit-menu', 'option') : 0,
		'hideEditProfileMenu'    => get_field('cn-hide-edit-profile-menu', 'option') ? get_field('cn-hide-edit-profile-menu', 'option') : 0,
		'showTrackOrderBtn'    => get_field('cn_show_track_order_btn', 'option') ? get_field('cn_show_track_order_btn', 'option') : false,
		'cnSiteUrlAlias'    => get_field('cn_site_url_alias', 'option') ? get_field('cn_site_url_alias', 'option') : false,
		'cipManifestDateType'    => get_field('cn-cip-manifest-date-type', 'option') ? (int)get_field('cn-cip-manifest-date-type', 'option')  : 0,
		'cipVoucherPriceEdit'    => (int)get_field('cn-cip-voucher-price-edit', 'option'),
		'signUpOptionalText'    => cn_option('cn-optional-b2b-signup-txt', '') ? cn_option('cn-optional-b2b-signup-txt', '') : '',
		'signUpOptionalTextSec'    => cn_option('cn-optional-b2b-signup-txt-2', '') ? cn_option('cn-optional-b2b-signup-txt-2', '') : '',
		'showAlertRegister'    => $show_alert_register,
		'hideCharterPrices' => $hide_charter_prices,
		'hideSystemyPrices' => $hide_system_prices,
		'menuShowType'   => $menu_show_type,
		'flightsShowTypeB2b' => $flights_show_type_b2b,
		'flightsShowTypeB2c' => $flights_show_type_b2c,
		'flightsShowTypeAdmin' => $flights_show_type_admin,
		'flightsShowTypeCounter' => $flights_show_type_counter,
		'displayRegistrationDate' => $display_registration_date,
		'displayRegistrationCalender' => $display_registration_calender,
		'hideFlightTypeLabel' => $hide_flight_type_label,
		'headerMenu' 	   => $header_menu_settings,
		'mobileCodes' 	   => $mobile_codes,
		'defaultMobileCode' 	   => $default_mobile_code,
		'defaultNationality' 	   => $default_nationality,
		'hideCloseFlight' => $hide_close_flight,
		'showCitizensCheckbox' => $show_citizens_checkbox,
		'hideZeroPriceInTicket' => 	$hide_zero_price,
		'googleCaptcha'    => (get_field('cn_captcha', 'option') == 'on') ? get_field('cn_site_key', 'option') : null,
 		'requiredFaName' => $required_fa_name,
		'orgId' 		   => $org_id,
		'baseUrl' 		   => $base_url,
		'inventoryId'		=> $inventory_id,
		'defaultCurrency'  => $default_currency,
		'siteLangs'  => $site_langs,
		'hotelsSortkey' 	   => $hotels_sortkey,
		'pwa' 			   => $pwa,
		'version' 			   => $plugin_version,
		'hideSuppliersCounter' => $hide_suppliers_counter,
		'hideSuppliersAdmin' => $hide_suppliers_admin,
		'hideLogoContractReport' => $hide_logo_contract_report,
		'peymentedMethod' => [
			'hideWallet' => $hide_wallet,
			'hideCredit' => $hide_credit,
			'hideBank' => $hide_bank,
		],
		'cipShifts' => [
			'morningTime' => $morning_time_cip,
			'eveningTime' => $evening_time_cip,
		],
		'airlineSettings' => [
			'filterSubsystems' => $filter_subsystems,
		],
		'maxAdultChildSearch' => $max_adult_child_search,
		'defaultVisaCountry' => $default_visa_country,
		'specialOfferHotel' => $special_offer_hotel,
		'displaySupportPhone' => $display_support_phone,
		'stationsTrains' => $get_stations_trains,
		'trainInformationSeo' => $get_train_information_seo,
		'flightInformationSeo' => $get_flight_information_seo,
		'packageInformation' => $get_package_information,
		'hotelSearchOption' => $hotel_search_option,
		'displayMobileNumber' => $display_mobile_number,
		'printBarcodeLabel' => $print_barcode_label,
		'stationsTrainsDefaultPriceDisplay' => $get_stations_trains_default_price_display,
		'mergeTaxWithBaseFare:' => $get_tax_display,
		'cn_isDefaultInsuranceActive' => get_field('cn_isDefaultInsuranceActive', 'option') ? get_field('cn_isDefaultInsuranceActive', 'option') : 0,
		'cn_isDefaultCipActive' => get_field('cn_isDefaultCipActive', 'option') ? get_field('cn_isDefaultCipActive', 'option') : 0,
	];
}

//disable slug edit on plugin created pages
add_filter('get_sample_permalink_html', 'cn_hide_permalinks', 10, 2);
function cn_hide_permalinks($return, $post_ID)
{
	$cn_pages    = get_option('citynet_pages_ids');
	$cn_page_ids = explode(',', $cn_pages);
	if (in_array($post_ID, $cn_page_ids)) {
		return '';
	}
	return $return;
}

//Load template from bank page
add_filter('page_template', function ($page_template) {

	if (get_page_template_slug() == 'cn-bank-return-template.php') {
		$page_template = dirname(__FILE__) . '/app-templates/cn-bank-return-template.php';
	}else if (get_page_template_slug() == 'cn-login-with-token.php') {
		$page_template = dirname(__FILE__) . '/app-templates/cn-login-with-token.php.php';
	}
	return $page_template;
});


/**
 * Add "Bank" template to page attirbute template section.
 */
add_filter('theme_page_templates', function ($post_templates, $wp_theme, $post, $post_type) {

	$post_templates['cn-bank-return-template.php'] = 'قالب بازگشت بانک پنل سیتی نت';
	$post_templates['cn-login-with-token.php'] = 'قالب login-with-token';
	return $post_templates;
}, 10, 4);


// Function to add custom classes to the admin body element
function cn_admin_body_class($classes)
{
    // Get the CityNet page IDs
    $cn_pages = get_option('citynet_pages_ids');
    $cn_page_ids = explode(',', $cn_pages);

    // Add 'toplevel_page_citynet-settings' class
    $classes .= 'toplevel_page_citynet-settings';

    // Check if the request is for editing a CityNet page
    if (isset($_GET['post']) && in_array($_GET['post'], $cn_page_ids) && isset($_GET['action']) && $_GET['action'] == 'edit') {
        // Add 'page-attribute-hidden' class
        $classes .= ' page-attribute-hidden';
    }

    return $classes;
}
// Hook the cn_admin_body_class function to the admin_body_class filter
add_filter('admin_body_class', 'cn_admin_body_class');

// Function to add a custom link to the plugin meta row
function cn_plugin_meta_links($links, $file)
{
    // Check if the file is 'citynet/citynet.php'
    if ($file === 'citynet/citynet.php') {
        // Add a custom link to the plugin meta row
        $links[] = '<a href="' . admin_url() . '/admin.php?page=citynet-setting" title="' . __('Setting Page', 'citynet_plugin') . '">' . __('Setting Page', 'citynet_plugin') . '</a>';
    }
    return $links;
}

// Hook the cn_plugin_meta_links function to the plugin_row_meta filter
add_filter('plugin_row_meta', 'cn_plugin_meta_links', 10, 2);

// Copy logo from acf settings to dir : reservation-logo/logo.png
function cn_get_image_path($attachment_id, $size = 'full')
{
	$file = get_attached_file($attachment_id, true);
	if (empty($size) || $size === 'full' || strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'svg') {
		// for the original size get_attached_file is fine
		return realpath($file);
	}
	if (!wp_attachment_is_image($attachment_id)) {
		return false; // the id is not referring to a media
	}
	$info = image_get_intermediate_size($attachment_id, $size);
	if (!is_array($info) || !isset($info['file'])) {
		return false; // probably a bad size argument
	}

	return realpath(str_replace(wp_basename($file), $info['file'], $file));
}

add_action('updated_option', function ($option_name, $old_value, $option_value) {
	if ($option_name == 'options_cn_logo') :
		$logo_curr_address = $option_value ? cn_get_image_path($option_value, 'full') : $option_value;
		if ($logo_curr_address) {
			$logo_new_dir = ABSPATH . 'reservation-logo/';
			if (!file_exists($logo_new_dir)) {
				mkdir($logo_new_dir, 0755, true);
			}
			copy($logo_curr_address, $logo_new_dir . 'logo.png');
		}
	endif;
	
	if ($option_name == 'options_cn_sas') :
		$logo_curr_address = $option_value ? cn_get_image_path($option_value, 'full') : $option_value;
		if ($logo_curr_address) {
			$logo_new_dir = ABSPATH . 'reservation-logo/';
			if (!file_exists($logo_new_dir)) {
				mkdir($logo_new_dir, 0755, true);
			}
			copy($logo_curr_address, $logo_new_dir . 'sas.png');
		}
	endif;

	if ($option_name == 'options_cn_stamp') :
		$logo_curr_address = $option_value ? cn_get_image_path($option_value, 'full') : $option_value;
		if ($logo_curr_address) {
			$logo_new_dir = ABSPATH . 'reservation-logo/';
			if (!file_exists($logo_new_dir)) {
				mkdir($logo_new_dir, 0755, true);
			}
			copy($logo_curr_address, $logo_new_dir . 'stamp.png');
		}
	endif;

	if ($option_name == 'options_cn_signature') :
		$logo_curr_address = $option_value ? cn_get_image_path($option_value, 'full') : $option_value;
		if ($logo_curr_address) {
			$logo_new_dir = ABSPATH . 'reservation-logo/';
			if (!file_exists($logo_new_dir)) {
				mkdir($logo_new_dir, 0755, true);
			}
			copy($logo_curr_address, $logo_new_dir . 'signature.png');
		}
	endif;

	if ($option_name == 'options_cn_footer') :
		$logo_curr_address = $option_value ? cn_get_image_path($option_value, 'full') : $option_value;
		if ($logo_curr_address) {
			$logo_new_dir = ABSPATH . 'reservation-logo/';
			if (!file_exists($logo_new_dir)) {
				mkdir($logo_new_dir, 0755, true);
			}
			copy($logo_curr_address, $logo_new_dir . 'footer.png');
		}
	endif;

	if (cn_is_multilang_active()) {
		global $sitepress;
		$other_langs = [];
		$current_lang = (ICL_LANGUAGE_CODE) ? ICL_LANGUAGE_CODE : '' ;
		$my_default_lang = apply_filters('wpml_default_language', NULL);
		//citynet_print_r($my_default_lang );
		$langs = cn_get_wpml_active_langs();
		foreach ($langs as $lang) {
			if ($lang != $my_default_lang) {
				$other_langs[] = $lang;
			}
		}
		if ($my_default_lang == $current_lang) {
			if ($option_name == 'options_cn_create_page') {
				if ($option_value == 1) {
					$cn_real_pages = cn_generate_real_pages();
					foreach ($cn_real_pages as $real_page) {
						// Insert the post into the database and add its id to $cn_page_ids array and check if posts with same slug exist and stop plugin activation
						$cn_same_slug_posts = get_posts(array(
							'name'           => $real_page['slug'],
							'posts_per_page' => 1,
							'post_type'      => 'page',
							'post_status'    => 'publish',
						));

						if ($cn_same_slug_posts) {
							foreach ($cn_same_slug_posts as $cn_same_slug_post) {
								// wp_delete_post( $cn_same_slug_post->ID , false );
								if (!metadata_exists('post', $cn_same_slug_post->ID, 'cn_page')) {
									$meta_value = in_array($cn_same_slug_post->post_name, ['flight', 'hotel', 'tour', 'package', 'flightplushotel', 'insurance', 'cip']) ? 'cn_seo_page' : 'cn_not_seo_page';
									add_post_meta($cn_same_slug_post->ID, 'cn_page', $meta_value);
								}
							}
						} else {
							$cn_post = array(
								'post_title'  => $real_page['title'],
								'post_name'   => $real_page['slug'],
								'post_status' => 'publish',
								'post_author' => 1,
								'post_type'   => 'page',
								'post_content' => '[citynet]',
								'meta_input'   => array(
									'cn_page' => in_array($real_page['slug'], ['flight', 'hotel', 'tour', 'package', 'flightplushotel', 'insurance', 'cip']) ? 'cn_seo_page' : 'cn_not_seo_page',
								)
							);
							if (in_array($real_page['slug'], cn_pages_with_parent('dashboard'))) {
								$cn_post['post_parent'] = get_page_id('dashboard');
							}
							if (in_array($real_page['slug'], cn_pages_with_parent('cipmanagement'))) {
								$cn_post['post_parent'] = get_page_id('cipmanagement');
							}
							if (in_array($real_page['slug'], cn_pages_with_parent('cnpwa'))) {
								$cn_post['post_parent'] = get_page_id('cnpwa');
							}
							$cn_pages_ids[] = wp_insert_post($cn_post);
						}
					}
					add_option('citynet_pages_ids', implode(',', $cn_pages_ids));
				}
			}
		} else {
			foreach ($other_langs as $other_lang) {
				if ($option_name == 'options_' . $other_lang . '_cn_create_page') {
					if ($option_value == 1) {
						$cn_real_pages = cn_generate_real_pages();
						foreach ($cn_real_pages as $real_page) {
							if (!isset($real_page['id'])) {
								$real_page['id'] = get_page_by_path($real_page['slug'], OBJECT, 'page')->ID;
							}
							$pages[] = $real_page;
						}
						foreach ($pages as $page) {

							$trid = $sitepress->get_element_trid($page['id'], 'post_page');
							$page_id = apply_filters('wpml_object_id', $page['id'], 'page', false, $other_lang);

							if (!$page_id) {
								$other_lang_page_id = wp_insert_post([
									'post_title'   => $page['slug'],
									'post_name'    => $page['slug'] + '-citynet',
									'post_status'  => 'publish',
									'post_author'  => 1,
									'post_type'    => 'page',
									'post_content' => '[citynet]'
								]);
								$sitepress->set_element_language_details($other_lang_page_id, 'post_page', $trid, $other_lang);
								wp_update_post(array(
									'ID' => $other_lang_page_id,
									'post_name' => $page['slug'],
									'meta_input'   => array(
										'cn_page' => in_array($page['slug'], ['flight', 'hotel', 'tour', 'package', 'flightplushotel', 'insurance', 'cip']) ? 'cn_seo_page' : 'cn_not_seo_page',
									)
								));
								$slug = get_post_field('post_name', $other_lang_page_id);
								if (in_array($slug, cn_pages_with_parent('dashboard'))) {
									wp_update_post(array(
										'ID' => $other_lang_page_id,
										'post_parent' => get_page_by_path('dashboard')->ID,
									));
								}
								if (in_array($slug, cn_pages_with_parent('cipmanagement'))) {
									wp_update_post(array(
										'ID' => $other_lang_page_id,
										'post_parent' => get_page_by_path('cipmanagement')->ID,
									));
								}

								if (in_array($slug, cn_pages_with_parent('cnpwa'))) {
									wp_update_post(array(
										'ID' => $other_lang_page_id,
										'post_parent' => get_page_by_path('cnpwa')->ID,
									));
								}
							}
						}
					}
				}
			}
		}
	} else {
		if ($option_name == 'options_cn_create_page') {
			if ($option_value == 1) {
				$cn_real_pages = cn_generate_real_pages();
				//citynet_print_r($cn_real_pages);
				foreach ($cn_real_pages as $real_page) {
					// Insert the post into the database and add its id to $cn_page_ids array and check if posts with same slug exist and stop plugin activation
					$cn_same_slug_posts = get_posts(array(
						'name'           => $real_page['slug'],
						'posts_per_page' => 1,
						'post_type'      => 'page',
						'post_status'    => 'publish',
					));

					if ($cn_same_slug_posts) {
						foreach ($cn_same_slug_posts as $cn_same_slug_post) {
							// wp_delete_post( $cn_same_slug_post->ID , false );
							if (!metadata_exists('post', $cn_same_slug_post->ID, 'cn_page')) {
								$meta_value = in_array($cn_same_slug_post->post_name, ['flight', 'hotel', 'tour', 'package', 'flightplushotel', 'insurance', 'cip']) ? 'cn_seo_page' : 'cn_not_seo_page';
								add_post_meta($cn_same_slug_post->ID, 'cn_page', $meta_value);
							}
						}
					} else {
						$cn_post = array(
							'post_title'  => $real_page['title'],
							'post_name'   => $real_page['slug'],
							'post_status' => 'publish',
							'post_author' => 1,
							'post_type'   => 'page',
							'post_content' => '[citynet]',
							'meta_input'   => array(
								'cn_page' => in_array($real_page['slug'], ['flight', 'hotel', 'tour', 'package', 'flightplushotel', 'insurance', 'cip']) ? 'cn_seo_page' : 'cn_not_seo_page',
							)
						);
						if (in_array($real_page['slug'], cn_pages_with_parent('dashboard'))) {
							$cn_post['post_parent'] = get_page_id('dashboard');
						}
						if (in_array($real_page['slug'], cn_pages_with_parent('cipmanagement'))) {
							$cn_post['post_parent'] = get_page_id('cipmanagement');
						}
						if (in_array($real_page['slug'], cn_pages_with_parent('cnpwa'))) {
							$cn_post['post_parent'] = get_page_id('cnpwa');
						}
						$cn_pages_ids[] = wp_insert_post($cn_post);
					}
				}
				add_option('citynet_pages_ids', implode(',', $cn_pages_ids));
				//citynet_print_r('done');
			}
		}
	}
}, 10, 3);

//display cn_create_page for citynet user
add_filter("acf/prepare_field/name=cn_create_page", function ($field) {
	$user = wp_get_current_user();
	// hide from other user
	if (strpos($user->user_email, "@citynet.ir") == false) {
		return false;
	}
	// display just for citynet user
	return $field;
});

// Filter to prepare the cn_baseurl field for display
add_filter("acf/prepare_field/name=cn_baseurl", function ($field) {
    $user = wp_get_current_user();

    // Hide from other users
    if (strpos($user->user_email, "@citynet.ir") == false) {
        return false;
    }

    // Display the field only for CityNet users
    return $field;
});

function cn_settings_link($links_array, $plugin_file_name)
{

	// if you use this action hook inside main plugin file, use basename(__FILE__) to check
	if (strpos($plugin_file_name, basename(__FILE__))) {
		// we can add one more array element at the beginning with array_unshift()
		array_unshift($links_array, '<a href="' . admin_url() . 'admin.php?page=citynet-settings">' . __('Setting Page', 'citynet_plugin') . '</a>');
	}

	return $links_array;
}
add_filter('plugin_action_links', 'cn_settings_link', 10, 2);

//update checker
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'http://plugin.citysoft.ir/json/plugin.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'citynet'
);
//edit post row actions and disable quick edit for created plugin pages
function cn_post_row_actions($actions, $post)
{
	$cn_pages    = get_option('citynet_pages_ids');
	$cn_page_ids = explode(',', $cn_pages);
	if (in_array($post->ID, $cn_page_ids)) {
		unset($actions['inline hide-if-no-js']);
	}
	return $actions;
}
add_filter('page_row_actions', 'cn_post_row_actions', 10, 2);
function cn_create_seo_page()
{
	global $sitepress;
	$current_lang = citynet_plugin_options_language_prefix(citynet_plugin_current_language());

	$flight_routes_count = get_option('options' . $current_lang . '_cn_flight-route');
	$flight_seo_page_ids = [];
	if ($flight_routes_count) {
		$counter        = 0;
		$other_langs = [];
		if (cn_is_multilang_active()) {
			$langs = cn_get_wpml_active_langs();
			foreach ($langs as $lang) {
				if ($lang != ICL_LANGUAGE_CODE) {
					$other_langs[] = $lang;
				}
			}
		}
		while ($counter < $flight_routes_count) {
			$current_route = get_option('options' . $current_lang . '_cn_flight-route_' . $counter . '_cn_origin')
				. '-' .
				get_option('options' . $current_lang . '_cn_flight-route_' . $counter . '_cn_destination');
			if (cn_is_multilang_active()) {
				$sitepress->switch_lang(ICL_LANGUAGE_CODE);
			}
			$cn_same_seo_flight_posts = get_posts(array(
				'name'           => $current_route,
				'posts_per_page' => 1,
				'post_type'      => 'any',
				'post_status'    => 'publish'
			));
			$flight_page_id = cn_get_flight_page_id();
			if (!$cn_same_seo_flight_posts) {
				$flight_seo_page_id = wp_insert_post([
					'post_title'   => $current_route,
					'post_name'    => $current_route,
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'page',
					'post_parent'  => $flight_page_id,
					'post_content' => '[citynet]'
				]);

				if ($other_langs) {
					// Get original post's translation ID
					$trid = $sitepress->get_element_trid($flight_seo_page_id, 'post_page');

					foreach ($other_langs as $other_lang) {
						$flight_page_id = apply_filters('wpml_object_id', $flight_page_id, 'page', false, $other_lang);
						$sec_flight_seo_page_id = wp_insert_post([
							'post_title'   => $current_route,
							'post_name'    => $current_route . '-citynet',
							'post_status'  => 'publish',
							'post_author'  => 1,
							'post_type'    => 'page',
							'post_parent'  => $flight_page_id,
							'post_content' => '[citynet]'
						]);

						// Tell WPML the second post is a translation of the first
						$sitepress->set_element_language_details($sec_flight_seo_page_id, 'post_page', $trid, $other_lang);
						wp_update_post(array(
							'ID' => $sec_flight_seo_page_id,
							'post_name' => $current_route
						));
						array_push($flight_seo_page_ids, $sec_flight_seo_page_id);
					}
				}
				array_push($flight_seo_page_ids, $flight_seo_page_id);
			}
			$counter++;
		}
		$cn_pages     = get_option('citynet_pages_ids');
		$cn_pages_ids = explode(',', $cn_pages);
		foreach ($flight_seo_page_ids as $flight_seo_page_id) {
			array_push($cn_pages_ids, $flight_seo_page_id);
		}
		update_option('citynet_pages_ids', implode(',', $cn_pages_ids));
	}
}
add_action('wp_loaded', 'cn_create_seo_page');

// Function to enqueue scripts and styles for CityNet panel pages
// Function to enqueue scripts and styles for CityNet panel pages
function cn_scripts_panel()
{
    $plugin_path_real = plugin_dir_path(__FILE__);
    
    // Get current theme setting
    $lang_prefix = citynet_plugin_options_language_prefix(citynet_plugin_current_language());
    $widget_theme = cn_option('cn_widget_theme', $lang_prefix);
    
    // Check if captcha is enabled
    if (get_field('captcha', 'option') == "on") {
        // Enqueue the Google reCAPTCHA script
        wp_enqueue_script('captcha', 'https://www.google.com/recaptcha/api.js?hl=fa', [], null, true);
    }

    // Enqueue the xlsx.full.min.js script
    wp_enqueue_script('citynet-xlsx', plugin_dir_url(__FILE__) . "assets/other/xlsx.full.min.js", [], filemtime($plugin_path_real . "assets/other/xlsx.full.min.js"), true);
    
    // Enqueue the chunk-vendors.css style
    wp_enqueue_style("citynet-chunk-vendor", plugin_dir_url(__FILE__) . "public/style/desk/chunk-vendors.css", null, filemtime($plugin_path_real . "public/style/desk/chunk-vendors.css"));

    // Register and enqueue the chunk-desktop.js script
    wp_register_script("citynet-chunk-js", plugin_dir_url(__FILE__) . "public/style/desk/chunk-desktop.js", null, filemtime($plugin_path_real . "public/style/desk/chunk-desktop.js"), true);
    // Enqueue the chunk-desktop.css style
    wp_enqueue_style("citynet-chunk-css", plugin_dir_url(__FILE__) . "public/style/desk/chunk-desktop.css", null, filemtime($plugin_path_real . "public/style/desk/chunk-desktop.css"));

    // Check if the device is not mobile
    if (!wp_is_mobile()) {
        // Enqueue the panel-style.css style
        wp_enqueue_style("ds-panel-style", plugin_dir_url(__FILE__) . "public/style/desk/panel-style.css", null, filemtime($plugin_path_real . "public/style/desk/panel-style.css"));
        // Enqueue the app.js script with a dependency on chunk-desktop.js
        wp_enqueue_script("cn-app-js", plugin_dir_url(__FILE__) . "public/js/app.js", ['citynet-chunk-js'], filemtime($plugin_path_real . "public/js/app.js"), true);
        // Enqueue the app.css style
        wp_enqueue_style("cn-ds-app-css", plugin_dir_url(__FILE__) . "public/css/app.css", null, filemtime($plugin_path_real . "public/css/app.css"));
    } else {
        // Check if widget theme is theme5 for mobile
        if ($widget_theme === 'theme5') {
            // Enqueue theme5 specific mobile CSS
            wp_enqueue_style("mb-panel-style-theme5", plugin_dir_url(__FILE__) . "public/style/mob/panel-style_Mobile_sama.css", null, filemtime($plugin_path_real . "public/style/mob/panel-style-theme5.css"));
        } else {
            // Enqueue the default panel-style_Mobile.css style
            wp_enqueue_style("mb-panel-style", plugin_dir_url(__FILE__) . "public/style/mob/panel-style_Mobile.css", null, filemtime($plugin_path_real . "public/style/mob/panel-style_Mobile.css"));
        }
        
        // Enqueue the app.js script with a dependency on chunk-desktop.js
        wp_enqueue_script("cn-app-js", plugin_dir_url(__FILE__) . "public/mobile/js/app.js", ['citynet-chunk-js'], filemtime($plugin_path_real . "public/mobile/js/app.js"), true);
        // Enqueue the app.css style
        wp_enqueue_style("mb-cn-app-css", plugin_dir_url(__FILE__) . "public/mobile/css/app.css", null, filemtime($plugin_path_real . "public/mobile/css/app.css"));
    }

    // Get panel info
    $panel_info = generate_panel_info();

    // Add panel info as inline script to cn-app-js
    wp_add_inline_script('cn-app-js', 'var citynetPluginWpData =' . json_encode($panel_info));

    // Add panel info as inline script to citynet-chunk-js
    wp_add_inline_script('citynet-chunk-js', 'var citynetPluginWpData =' . json_encode($panel_info));
}

// Hook the cn_scripts_panel function to the wp_enqueue_scripts action
add_action('wp_enqueue_scripts', 'cn_scripts_panel');

// Function to enqueue scripts and styles for CityNet admin pages
function cn_admin_scripts($hook)
{
    // Check if the current page is CityNet settings
    if ($hook === 'toplevel_page_citynet-settings') {
        // Enqueue the JavaScript file with a dependency on jQuery
        wp_enqueue_script('cn-admin-js', plugin_dir_url(__FILE__) . 'admin/js/cn-admin.js', null, filemtime(plugin_dir_path(__FILE__) . 'admin/js/cn-admin.js'), true);

		if (cn_is_multilang_active()) {
			global $sitepress;
			$current_lang = $sitepress->get_current_language();
			wp_localize_script('cn-admin-js', 'wpmlData', array(
				'currentLanguage' => $current_lang
			));
		}

        // Enqueue the CSS file
        wp_enqueue_style('cn-admin', plugin_dir_url(__FILE__) . 'admin/css/cn-admin.css', [], filemtime(plugin_dir_path(__FILE__) . 'admin/css/cn-admin.css'));
    }
}
// Hook the cn_admin_scripts function to the admin_enqueue_scripts action
add_action('admin_enqueue_scripts', 'cn_admin_scripts');


// Function to check if the current page is a panel page
function citynet_is_panel_pages()
{
    global $post;

    $post_obj = $post ?: get_queried_object();

 
    if (!$post_obj || !is_object($post_obj)) {

        $page_slug = '';
    } else {
        $page_slug = $post_obj->post_name ?? '';
    }


    $panel_info = generate_panel_info();
    $flight_routes = $panel_info['seoRoutes'] ?? [];


    foreach ($flight_routes as $lang => $routes) {
        foreach ((array)$routes as $r) {
            if (!empty($r['route']) && $page_slug === $r['route']) {
                return true;
            }
        }
    }

    $url = $_SERVER['REQUEST_URI'] ?? '';
    $url_first = explode('/', ltrim($url, '/'))[0] ?? '';

    $last = rtrim($url, '/');
    $pos = strrpos($last, '/');
    $last = ($pos !== false) ? substr($last, $pos + 1) : $last;
    $last = (strpos($last, '?') !== false) ? substr($last, 0, strpos($last, '?')) : $last;

    $citynet_pages = cn_generate_pages();
    $citynet_pages_tolower = array_map('strtolower', $citynet_pages);

    if (
        in_array($last, $citynet_pages, true) ||
        in_array($url_first, $citynet_pages, true) ||
        in_array(strtolower($last), $citynet_pages_tolower, true) ||
        in_array(strtolower($url_first), $citynet_pages_tolower, true)
    ) {
        return true;
    }

    return false;
}
// declare a script for the new button
// the script will insert the shortcode on the click event
function cn_add_tinymce_plugin($plugin_array)
{
	$plugin_array['cn_mce_button'] = plugin_dir_url(__FILE__) . 'admin/js/cn-mce-button.js';

	return $plugin_array;
}

// Hook function to add a shortcode in the wp_head action
function add_cn_shortcode()
{
    // Generate panel information
    $panel_info = generate_panel_info();
    $hotels_cities = $panel_info['seoCities'];
    $hotels = $panel_info['seoHotels'];
    $seo_routes = $panel_info['seoRoutes'];
    $panel_pages = $panel_info['panelPages'];
    $seo_visas = $panel_info['seoVisas'];
    $seo_cips = $panel_info['seoCips'];
    $seo_insurances = $panel_info['seoInsurances'];
	$seo_trains = $panel_info['trainInformationSeo'];


    // Get the current URL and request URI
    $url = $_SERVER['REQUEST_URI'];
    $actual_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    // Flag to determine if it's an SEO page
    $is_seo = false;

    // Check if the actual URL matches any panel pages
    foreach ($panel_pages as $key => $val) {
        foreach ($val as $key2 => $val2) {
            if (in_array($actual_url, $val2)) {
                $is_seo = true;
                break 2;
            }
        }
    }

    // Check if the URL matches any SEO cities
    foreach ($hotels_cities as $key => $val) {
        foreach ($val as $key2 => $val2) {
            $str = $panel_info['siteLanguage'] == $key ? $val2['slug'] : $key . '/' . $val2['slug'];
            if (strtolower(trim($str, "/")) == strtolower(trim($url, "/"))) {
                $is_seo = true;
                break 2;
            }
        }
    }

    // Check if the actual URL matches any SEO routes
    foreach ($seo_routes as $key => $val) {
        foreach ($val as $key2 => $val2) {
            $url_arry = parse_url($actual_url);
            if (strpos($url_arry['path'], $val2['route'])) {
                $is_seo = true;
                break 2;
            }
        }
    }

    global $wp;
    // Get the current URL without query parameters
    $current_url =  home_url($wp->request);

    // Check if the current URL matches any SEO hotels
    foreach ($hotels as $key => $val) {
        foreach ($val as $key2 => $val2) {
            if (strtolower(trim($current_url, "/")) == strtolower(trim($val2['slug'], "/"))) {
                $is_seo = true;
                break 2;
            }
        }
    }

    // Check if the current URL matches any SEO visas
    foreach ($seo_visas as $key => $val) {
        foreach ($val as $key2 => $val2) {
            $position = strpos(strtolower(trim($current_url, "/")), strtolower(trim($val2['slug'], "/")));
            if ($position !== false) {
                $is_seo = true;
                break 2;
            }
		}
    }

	// Check if the current URL matches any SEO visas
	foreach ($seo_cips as $key => $val) {
        foreach ($val as $key2 => $val2) {
            $position = strpos(strtolower(trim($current_url, "/")), strtolower(trim($val2['slug'], "/")));
            if ($position !== false) {
                $is_seo = true;
                break 2;
            }
        }
    }

	// Check if the current URL matches any SEO insurances
	foreach ($seo_insurances as $key => $val) {
        foreach ($val as $key2 => $val2) {
            $position = strpos(strtolower(trim($current_url, "/")), strtolower(trim($val2['slug'], "/")));
			if ($position !== false) {
				$is_seo = true;
				break 2;
			}
        } 
    }

	// Check if the current URL matches any SEO trains
	foreach ($seo_trains as $key => $val) {
		foreach ($val as $key2 => $val2) {
			$position = strpos(strtolower(trim($current_url, "/")), strtolower(trim($val2['slug'], "/")));
			if ($position !== false) {
				$is_seo = true;
				break 2;
			} 
		}
	}

    // If it's not an SEO page, panel page, or front page, echo the shortcode
    if (!$is_seo && !citynet_is_panel_pages() && !is_front_page()) {
        echo '<div id="app"></div>';
    }
}

// Add the add_cn_shortcode function to the wp_head action
add_action('wp_head', 'add_cn_shortcode');
function citynet_check_submitted_in_list()
{
	cn_api_submit_version('5.7.21');
}

// Check if flight and tour pages not exist add them after update
add_action('upgrader_process_complete', function ($upgrader_object, $options) {
	// The path to our plugin's main file
	$citynet_plugin = plugin_basename(__FILE__);
	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
		// Iterate through the plugins being updated and check if citynet is there
		foreach ($options['plugins'] as $plugin) {
			if ($plugin == $citynet_plugin) {
				// If it's citynet plugin
				citynet_activate();
			}
		}
	}
}, 10, 2);

// Function to find the service tab based on the service type
function find_service_tab($service)
{
    switch ($service):
        case 'flight':
            return 'Flight';
            break;

        case 'hotel':
            return 'Hotel';
            break;

        case 'flighandhotel':
            return 'FlightAndHotel';
            break;

        case 'insurance':
            return 'Insurance';
            break;

        case 'cip':
            return 'Cip';
            break;

        case 'package':
            return 'Package';
            break;

        case 'tour':
            return 'Tour';

        case 'visa':
            return 'Visa';
            break;

        case 'train':
            return 'Train';
            break;

    endswitch;
}

// Function to find the service name in multi-language mode
function find_service_name($lang, $name, $service)
{
    switch ($service):
        case 'flight':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'پرواز',
                    'en' => 'Flight',
                    'ar' => 'طيران',
                    'ru' => 'Полет',
                    'tr' => 'Uçuş'
                ];
            }
            break;

        case 'hotel':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'هتل',
                    'en' => 'Hotel',
                    'ar' => 'الفندق',
                    'ru' => 'Отель',
                    'tr' => 'Otel'
                ];
            }
            break;

        case 'flighandhotel':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'پرواز+هتل',
                    'en' => 'Flight+Hotel',
                    'ar' => 'طيران+الفندق',
                    'ru' => 'отель + перелет',
                    'tr' => 'Uçuş+Otel',
                ];
            }
            break;

        case 'tour':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'تور',
                    'en' => 'Tour',
                    'ar' => 'سياحة',
                    'ru' => 'Тур',
                    'tr' => 'Ağ',
                ];
            }
            break;

        case 'package':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'پکیج سفر',
                    'en' => 'Package',
                    'ar' => 'عزیمة السفر',
                    'ru' => 'туристический пакет',
                    'tr' => 'seyahat paketi',
                ];
            }
            break;

        case 'insurance':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'بیمه مسافرتی',
                    'en' => 'Insurance',
                    'ar' => 'تأمين',
                    'ru' => 'страхование',
                    'tr' => 'Seyahat sigortası',
                ];
            }
            break;

        case 'cip':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'تشریفات فرودگاه',
                    'en' => 'CIP',
                    'ar' => 'CIP',
                    'ru' => 'Процедуры в аэропорту',
                    'tr' => 'Havaalanı prosedürleri',
                ];
            }
            break;

        case 'visa':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'ویزا',
                    'en' => 'visa',
                    'ar' => 'تأشيرة',
                    'ru' => 'виза',
                    'tr' => 'vize',
                ];
            }
            break;

        case 'train':
            if ($name) {
                return [
                    $lang => $name,
                ];
            } else {
                return [
                    'fa' => 'قطار',
                    'en' => 'train',
                    'ar' => 'القطار',
                    'ru' => 'Тренироваться',
                    'tr' => 'Tren',
                ];
            }
            break;

    endswitch;
}


// Remote lookup fields share validation, caching and admin error reporting.
add_filter('acf/load_field/name=cn_visa_country', function ($field) {
	return cn_api_load_choices($field, 'visa/countries', 'CountryCode', 'En', __('کشورهای ویزا', 'citynet_plugin'));
});

/** Legacy entry point retained for integrations expecting string|null. */
function getGuestToken()
{
	$token = cn_api_get_guest_token();
	if (is_wp_error($token)) {
		cn_api_add_notice($token, __('دریافت دسترسی سرویس', 'citynet_plugin'));
		return null;
	}
	return $token;
}

// Add filter for the 'cn_visa_nationality' field to modify its choices dynamically
add_filter('acf/load_field/name=cn_visa_nationality', function ($field) {
    $field['choices'] = []; // Initialize an empty array to store the choices

    // Read the contents of the 'mobile_codes.json' file
    $string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");

    // Decode the JSON string into an associative array
    $json_a = json_decode($string, true);

    // Iterate over each element in the associative array
    foreach ($json_a as $key => $value) {
        // Assign the 'name' value as a choice for the field
        $field['choices'][$key] = $value['name'];
    }

    return $field; // Return the modified field
});

/**
 * Custom validation function to prevent duplicate slugs in a specific ACF field.
 *
 * @param mixed  $valid  The current validation status.
 * @param mixed  $value  The submitted field value.
 * @param array  $field  The field array containing field settings.
 * @param string $input  The current input name.
 *
 * @return mixed  The updated validation status.
 */
function prevent_duplicate_slug($valid, $value, $field, $input) {
	// Check if the field name matches the target field
    if ($field['name'] === 'cn_seo_visa_pages') {
		// Check if $value is an array and not empty
		 if (!is_array($value) || empty($value)) {
            return $valid; // or handle the error appropriately
        }
		
        $visas = array();  // Array to store existing visas

        foreach ($value as $item) {
			// Get the values of the relevant fields
            $slug = $item['field_65b8fde0dc43b'];
            $country = $item['field_65b8fdefdc43c'];
            $nationality = $item['field_65ba352b71ad3'];
    
            foreach ($visas as $existingVisa) {
				// Check for duplicate countries when nationality is empty
                if (empty($nationality) && $country === $existingVisa['country'] ) {
                    $valid = __('کشور تکراری است.', 'citynet_plugin');
                    break 2;
                }
                // Check for duplicate rows based on slug, country, and nationality
                if ($slug === $existingVisa['slug'] && $country === $existingVisa['country'] && $nationality === $existingVisa['nationality']) {
                    $valid = __('سطر انتخابی تکراری است', 'citynet_plugin');
                    break 2;
                }
            }
			// Store the current visa in the array
            $visas[] = array(
                'slug' => $slug,
                'country' => $country,
                'nationality' => $nationality,
            );
        }
    }

    return $valid;
}
// Register the custom validation function as a filter for the specified ACF field
add_filter('acf/validate_value/key=field_65b8fae666a44', 'prevent_duplicate_slug', 10, 4);

// Action to delete non-SEO panel pages when the plugin is deactivated
add_action("deactivated_plugin", function ($plugin, $network_deactivating) {
    /** @var wpdb $wpdb WP Database */
    global $wpdb;
    $table_posts = $wpdb->prefix . 'posts';
    $table_postmeta = $wpdb->prefix . 'postmeta';

    // Prepare and execute a database query to select post IDs of non-SEO panel pages
    $prepared_statement = $wpdb->prepare("SELECT post_id FROM `$table_postmeta` where meta_value='cn_not_seo_page' AND meta_key='cn_page'");
    $values = $wpdb->get_col($prepared_statement);

    // Loop through the selected post IDs and delete the corresponding posts and post meta
    foreach ($values as $value) {
        $wpdb->delete($table_posts, ['ID' => $value]);
        $table_postmeta = $wpdb->prefix . 'postmeta';
        $wpdb->delete($table_postmeta, ['post_id' => $value]);
    }
}, 10, 2);

// Action to add meta tag for non-SEO panel pages in the wp_head hook
add_action('wp_head', function () {
    global $post;
    if (is_page() && get_option('cn_create_page')) {
        $cn_page = get_post_meta(get_the_ID(), 'cn_page', false);

        // Check if the current page is a non-SEO panel page
        if (in_array("cn_not_seo_page", $cn_page)) {
            echo '<meta name="robots" content="noindex,nofollow" />';
        }
    }
}, 2);

/** Legacy entry point retained for integrations expecting array|false. */
function get_api_data()
{
	$items = cn_api_get_items('train/stations', 'Code', 'Name');
	if (is_wp_error($items)) {
		cn_api_add_notice($items, __('ایستگاه‌های قطار', 'citynet_plugin'));
		return false;
	}
	return $items;
}

function cn_load_train_station_choices($field)
{
	return cn_api_load_choices($field, 'train/stations', 'Code', 'Name', __('ایستگاه‌های قطار', 'citynet_plugin'));
}
add_filter('acf/load_field/name=city-or-train-station', 'cn_load_train_station_choices');
add_filter('acf/load_field/name=origin-city', 'cn_load_train_station_choices');
add_filter('acf/load_field/name=destination-city', 'cn_load_train_station_choices');

add_filter('acf/load_field/name=cn_insurance_durations', function ($field) {
	return cn_api_load_choices($field, 'insurance/durations', 'Value', 'Title', __('مدت سفر بیمه', 'citynet_plugin'));
});

// select for cn_insurance_country
add_filter('acf/load_field/name=cn_insurance_country', function ($field) {
	$field['choices'] = [];

	$string = file_get_contents(__DIR__ . "/assets/other/mobile_codes.json");
	$json_a = json_decode($string, true);

	foreach ($json_a as $key => $value) {
		$field['choices'][$key] =  $value['name'];
	}
	return $field;
});




function validate_unique_package_types($valid, $value, $field, $input) {
    // ACF submits an optional repeater with no rows as an empty string.
    // Preserve prior validation errors and only inspect actual repeater rows.
    if ($valid !== true || !is_array($value)) {
        return $valid;
    }

    // Only validate the package_settings repeater field
    if ($field['key'] === 'field_package_settings_repeater') {
        // Collect all the package types
        $package_types = array();
        foreach ($value as $package) {
            if (is_array($package) && isset($package['field_package_type']) && is_scalar($package['field_package_type'])) {
                $package_types[] = $package['field_package_type'];
            }
        }
        
        // Check for duplicate package types
        if (count($package_types) !== count(array_unique($package_types))) {
            $valid = 'شما نمی‌توانید پکیج‌های تکراری انتخاب کنید.';
        }
    }
    
    return $valid;
}

add_filter('acf/validate_value/name=package_settings', 'validate_unique_package_types', 10, 4);




function citynet_acf_admin_styles() {
    $screen = get_current_screen();
    
    if ($screen && $screen->id === 'toplevel_page_citynet-settings' && apply_filters('citynet/admin/use_legacy_styles', true)) {
        $custom_css = "
            /* استایل کلی برای فیلدهای ACF */
            .acf-field {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 15px;
                // border-radius: 8px;
                margin-bottom: 15px;
                transition: all 0.4s ease-in-out;
                position: relative;
                // overflow: hidden;
            }

            /* افکت هنگام هاور: تغییر رنگ و سایه */
            .acf-field:hover {
                background: linear-gradient(135deg, #eaf2f8, #d4eaf7);
                box-shadow: 0px 4px 10px rgba(0, 115, 170, 0.2);
            //     transform: translateY(-2px);
            }

            /* خط رنگی متحرک هنگام هاور */
            .acf-field::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                width: 5px;
                height: 100%;
                background: #0073aa;
                transition: transform 0.3s ease-in-out;
                transform: scaleY(0);
            }

            .acf-field:hover::before {
                transform: scaleY(1);
            }

            /* استایل خاص برای دکمه‌های ACF */
            .acf-button {
                background: #0073aa !important;
                color: #fff !important;
                // border-radius: 8px !important;
                padding: 10px 18px !important;
                transition: all 0.3s;
                font-weight: bold;
            }

            /* افکت هاور روی دکمه‌ها */
            .acf-button:hover {
                background: #005f8d !important;
                box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
                transform: scale(1.05);
            }

            /* استایل خاص برای فیلدهای رادیویی */
            .acf-field-radio {
                background: #ffffff;
                border-left: 5px solid #0073aa;
                padding: 20px;
                transition: all 0.4s ease;
            }

            /* تغییر رنگ هنگام هاور روی گزینه‌های رادیویی */
            .acf-field-radio:hover {
                background: #f0f8ff;
                border-left-color: #005f8d;
            }

            /* استایل دایره‌ای برای دکمه‌های رادیو */
            .acf-field-radio input[type='radio'] {
                transform: scale(1.3);
                margin-right: 8px;
                transition: all 0.2s ease;
            }

            .acf-field-radio input[type='radio']:hover {
                transform: scale(1.5);
            }

            /* انیمیشن موجی هنگام هاور روی فیلدها */
            // .acf-field::after {
            //     content: '';
            //     position: absolute;
            //     bottom: -5px;
            //     left: 50%;
            //     width: 0;
            //     height: 4px;
            //     background: #0073aa;
            //     transition: all 0.3s ease-in-out;
            //     transform: translateX(-50%);
            // }

            .acf-field:hover::after {
                width: 100%;
            }
        ";

        wp_add_inline_style('wp-admin', $custom_css);
    }
}
add_action('admin_enqueue_scripts', 'citynet_acf_admin_styles');

add_action('acf/input/admin_footer', function () {
    ?>
    <style>
        /* پنهان‌کردن تکست‌اریا کامل */
        .acf-field[data-name="rules"] textarea {
            display: none !important;
        }

        .rules-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .rules-modal-content {
            background: white;
            width: 500px;
            max-width: 90%;
            margin: 10% auto;
            padding: 20px;
            position: relative;
        }
        .rules-modal-close {
            position: absolute;
            top: 10px; right: 15px;
            font-weight: bold;
            font-size: 20px;
            cursor: pointer;
        }

        .acf-field[data-name="rules"] .rules-open-btn {
            margin-top: 5px;
            display: inline-block;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {

        function addEditButtons() {
            $('tr.acf-row').each(function(){
                const $row = $(this);
                const $fieldTd = $row.find('td[data-name="rules"]');
                const $textarea = $fieldTd.find('textarea');

                if ($fieldTd.find('.rules-open-btn').length === 0 && $textarea.length) {
                    const btn = $('<button type="button" class="button rules-open-btn"><span class="dashicons dashicons-edit"></span> ویرایش قوانین</button>');
                    $fieldTd.append(btn);

                    btn.on('click', function(e) {
                        e.preventDefault();
                        openModal($textarea);
                    });
                }
            });
        }

        function openModal($textarea) {
            const existingModal = $('#rules-modal');
            const value = $textarea.val();

            if (existingModal.length === 0) {
                const modalHtml = `
                    <div class="rules-modal" id="rules-modal">
                        <div class="rules-modal-content">
                            <span class="rules-modal-close">&times;</span>
                            <h3>ویرایش قوانین و مقررات</h3>
                            <textarea id="rules-modal-text" style="width:100%; height:150px;"></textarea>
                            <br><br>
                            <button type="button" class="button button-primary" id="rules-modal-save">ذخیره</button>
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);

                // بستن مدال
                $('body').on('click', '.rules-modal-close', function() {
                    $('#rules-modal').fadeOut();
                });

                // ذخیره متن وارد شده و انتقال به فیلد اصلی
                $('body').on('click', '#rules-modal-save', function() {
                    const newValue = $('#rules-modal-text').val();
                    const targetTextarea = $('#rules-modal').data('target');
                    targetTextarea.val(newValue).trigger('change');
                    $('#rules-modal').fadeOut();
                });
            }

            $('#rules-modal-text').val(value);
            $('#rules-modal').data('target', $textarea).fadeIn();
        }

        // برای بار اول
        addEditButtons();

        // هنگام اضافه شدن ردیف جدید (تکرارشونده‌ها)
        if (typeof acf !== 'undefined') {
            acf.addAction('append', function($el){
                addEditButtons();
            });
        }
    });
    </script>
    <?php
});





/**
 * Return IDs of pages generated and managed by the Citynet plugin.
 *
 * Sources:
 * - citynet_pages_ids option
 * - Pages containing [citynet]
 * - Pages marked with cn_page meta
 *
 * @return int[]
 */
if ( ! function_exists( 'cn_theme_get_citynet_plugin_page_ids' ) ) {

	function cn_theme_get_citynet_plugin_page_ids() {

		static $page_ids = null;

		if ( null !== $page_ids ) {
			return $page_ids;
		}

		$page_ids = array();

		/*
		 * If Citynet plugin is not active,
		 * there is nothing to exclude.
		 */
		if ( ! function_exists( 'cn_generate_pages' ) ) {
			return $page_ids;
		}

		global $wpdb;

		/*
		 * IDs stored directly by Citynet.
		 */
		$stored_ids = wp_parse_id_list(
			(string) get_option( 'citynet_pages_ids', '' )
		);

		/*
		 * Pages containing [citynet].
		 */
		$shortcode_like = '%' . $wpdb->esc_like( '[citynet]' ) . '%';

		$shortcode_page_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_content LIKE %s
				",
				'page',
				$shortcode_like
			)
		);

		/*
		 * Pages marked with cn_page meta.
		 */
		$marked_page_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT DISTINCT meta.post_id
				FROM {$wpdb->postmeta} AS meta
				INNER JOIN {$wpdb->posts} AS posts
					ON posts.ID = meta.post_id
				WHERE meta.meta_key = %s
				AND posts.post_type = %s
				",
				'cn_page',
				'page'
			)
		);

		/*
		 * Merge and sanitize all IDs.
		 */
		$page_ids = array_values(
			array_unique(
				array_filter(
					array_map(
						'absint',
						array_merge(
							$stored_ids,
							$shortcode_page_ids,
							$marked_page_ids
						)
					)
				)
			)
		);

		return $page_ids;
	}
}


/**
 * Hide Citynet-generated pages from ACF selectors.
 *
 * Applies to:
 * - Page Link
 * - Post Object
 * - Relationship
 *
 * @param array $args
 * @param array $field
 * @param mixed $post_id
 *
 * @return array
 */
if ( ! function_exists( 'cn_theme_exclude_citynet_plugin_pages_from_acf_query' ) ) {

	function cn_theme_exclude_citynet_plugin_pages_from_acf_query(
		$args,
		$field = array(),
		$post_id = false
	) {

		$plugin_page_ids = cn_theme_get_citynet_plugin_page_ids();

		if ( empty( $plugin_page_ids ) ) {
			return $args;
		}

		/*
		 * Preserve pages already excluded by ACF
		 * or other filters.
		 */
		$already_excluded = isset( $args['post__not_in'] )
			? wp_parse_id_list( $args['post__not_in'] )
			: array();

		$args['post__not_in'] = array_values(
			array_unique(
				array_merge(
					$already_excluded,
					$plugin_page_ids
				)
			)
		);

		return $args;
	}

	add_filter(
		'acf/fields/page_link/query',
		'cn_theme_exclude_citynet_plugin_pages_from_acf_query',
		20,
		3
	);

	add_filter(
		'acf/fields/post_object/query',
		'cn_theme_exclude_citynet_plugin_pages_from_acf_query',
		20,
		3
	);

	add_filter(
		'acf/fields/relationship/query',
		'cn_theme_exclude_citynet_plugin_pages_from_acf_query',
		20,
		3
	);
}

add_action('rest_api_init', function () {
    register_rest_route('myplugin/v2', '/panel-info/', [
        'methods'  => 'GET',
        'callback' => 'get_panel_info',
        'permission_callback' => '__return_true', // برای دسترسی عمومی، اگه لازم نیست عمومی باشه اینو عوض کن
    ]);
});

if ( ! function_exists( 'get_panel_info' ) ) {
	function get_panel_info() {
		if (function_exists('generate_panel_info')) {
			$data = generate_panel_info();
			return rest_ensure_response($data);
		} else {
			return new WP_Error('no_function', 'تابع generate_panel_info پیدا نشد', ['status' => 500]);
		}
	}
}
