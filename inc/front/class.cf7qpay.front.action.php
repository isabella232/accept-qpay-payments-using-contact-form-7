<?php
/**
 * CFQPZW_Front_Action Class
 *
 * Handles the Frontend Actions.
 *
 * @package WordPress
 * @subpackage Accept Qpay payments Using Contact form 7
 * @since 1.1
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CFQPZW_Front_Action' ) ){

	/**
	 *  The CFQPZW_Front_Action Class
	 */
	class CFQPZW_Front_Action {

		function __construct()  {
			add_action( 'wp_enqueue_scripts', array( $this, 'action__wp_enqueue_scripts' ) );

		}

		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		 * Action: wp_enqueue_scripts
		 *
		 * - enqueue script in front side
		 *
		 */
		function action__wp_enqueue_scripts() {
			wp_enqueue_script( CFQPZW_PREFIX . '_front_js', CFQPZW_URL . 'assets/js/front.min.js', array( 'jquery-core' ), CFQPZW_VERSION );
			wp_register_style( CFQPZW_PREFIX . '_select2', CFQPZW_URL . 'assets/css/select2.min.css', array(), CFQPZW_VERSION );
			wp_register_script( CFQPZW_PREFIX . '_select2', CFQPZW_URL . 'assets/js/select2.min.js', array( 'jquery-core' ), CFQPZW_VERSION );
		}


		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/


	}
	add_action( 'plugins_loaded', function() {
		CFQPZW()->admin = new CFQPZW_Front_Action;
	} );
}
