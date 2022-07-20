<?php
/**
 * CFQPZW_Admin Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept Qpay payments Using Contact form 7
 * @since 1.1
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CFQPZW_Admin' ) ) {

	/**
	 * The CFQPZW_Admin Class
	 */
	class CFQPZW_Admin {

		var $action = null,
			$filter = null;

		function __construct() {

			add_action( 'admin_menu',	array( $this, 'action__cfqpzw_admin_menu' ) );

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
		* Action: admin_menu
		*
		* - Used for removing the "submitdiv" metabox "cfqpzw_data" CPT
		*
		* @method action__cfqpzw_admin_menu
		*/
		function action__cfqpzw_admin_menu() {
			remove_meta_box( 'submitdiv', CFQPZW_POST_TYPE, 'side' );
		}

		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/


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
		CFQPZW()->admin = new CFQPZW_Admin;
	} );
}
