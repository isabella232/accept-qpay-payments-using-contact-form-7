<?php
/**
 * Plugin Name: Accept Qpay payments Using Contact form 7
 * Plugin URL: https://wordpress.org/plugins/accept-qpay-payments-using-contact-form-7/
 * Description: This plugin will integrate QPay payment gateway for making your payments through Contact Form 7.
 * Version: 1.1
 * Author: ZealousWeb
 * Author URI: https://www.zealousweb.com
 * Developer: The Zealousweb Team
 * Developer E-Mail: opensource@zealousweb.com
 * Text Domain: accept-qpay-payments-using-contact-form-7
 * Domain Path: /languages
 *
 * Copyright: © 2009-2021 ZealousWeb Technologies.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package Accept Qpay payments Using Contact form 7
 * @since 1.1
 */

if ( !defined( 'CFQPZW_VERSION' ) ) {
	define( 'CFQPZW_VERSION', '1.1' ); // Version of plugin
}

if ( !defined( 'CFQPZW_FILE' ) ) {
	define( 'CFQPZW_FILE', __FILE__ ); // Plugin File
}

if ( !defined( 'CFQPZW_DIR' ) ) {
	define( 'CFQPZW_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( !defined( 'CFQPZW_URL' ) ) {
	define( 'CFQPZW_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( !defined( 'CFQPZW_PLUGIN_BASENAME' ) ) {
	define( 'CFQPZW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // Plugin base name
}

if ( !defined( 'CFQPZW_META_PREFIX' ) ) {
	define( 'CFQPZW_META_PREFIX', 'cfqpzw_' ); // Plugin metabox prefix
}

if ( !defined( 'CFQPZW_PREFIX' ) ) {
	define( 'CFQPZW_PREFIX', 'cf7qpay' ); // Plugin prefix
}

if ( !defined( 'CFQPZW_POST_TYPE' ) ) {
	define( 'CFQPZW_POST_TYPE', 'cfqpzw_data' ); // Plugin post type
}

if ( !defined( 'CFQPZW_SUPPORT' ) ) {
	define( 'CFQPZW_SUPPORT', 'mailto:opensource@zealousweb.com' ); // Plugin Support Link
}

if ( !defined( 'CFQPZW_DOCUMENT' ) ) {
	define( 'CFQPZW_DOCUMENT', 'https://www.zealousweb.com/documentation/wordpress-plugins/accept-qpay-payments-using-contact-form-7' ); // Plugin Document Link
}

if ( !defined( 'CFQPZW_PRODUCT' ) ) {
	define( 'CFQPZW_PRODUCT', 'https://www.zealousweb.com/wordpress-plugins/accept-qpay-payments-using-contact-form-7' ); // Plugin Document Link
}
/**
 * Initialize the main class
 */
if ( !function_exists( 'CFQPZW' ) ) {

	if ( is_admin() ) {
		require_once( CFQPZW_DIR . '/inc/admin/class.' . CFQPZW_PREFIX . '.admin.php' );
		require_once( CFQPZW_DIR . '/inc/admin/class.' . CFQPZW_PREFIX . '.admin.action.php' );
		require_once( CFQPZW_DIR . '/inc/admin/class.' . CFQPZW_PREFIX . '.admin.filter.php' );
	} else {
		require_once( CFQPZW_DIR . '/inc/front/class.' . CFQPZW_PREFIX . '.front.php' );
		require_once( CFQPZW_DIR . '/inc/front/class.' . CFQPZW_PREFIX . '.front.action.php' );
		require_once( CFQPZW_DIR . '/inc/front/class.' . CFQPZW_PREFIX . '.front.filter.php' );
	}

	require_once( CFQPZW_DIR . '/inc/lib/class.' . CFQPZW_PREFIX . '.lib.php' );

	//Initialize all the things.
	require_once( CFQPZW_DIR . '/inc/class.' . CFQPZW_PREFIX . '.php' );
}