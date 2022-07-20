<?php
/**
 * CFQPZW_Admin_Filter Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept Qpay payments Using Contact form 7
 * @since 1.1
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CFQPZW_Admin_Filter' ) ) {

	/**
	 *  The CFQPZW_Admin_Filter Class
	 */
	class CFQPZW_Admin_Filter {

		function __construct() {
			add_filter( 'wpcf7_editor_panels',                               array( $this, 'filter__cfqpzw_wpcf7_editor_panels' ), 10, 1 );
			add_filter( 'post_row_actions',                                  array( $this, 'filter__cfqpzw_post_row_actions' ), 10, 1 );
			add_filter( 'plugin_action_links_'.CFQPZW_PLUGIN_BASENAME,       array( $this, 'filter__cfqpzw_admin_plugin_links'), 10, 2 );
			add_filter( 'manage_'.CFQPZW_POST_TYPE.'_posts_columns',         array( $this, 'filter__cfqpzw_manage_data_posts_columns' ), 10, 1 );
			add_filter( 'bulk_actions-edit-'.CFQPZW_POST_TYPE,               array( $this, 'filter__cfqpzw_bulk_actions_edit_data' ) );
			add_filter( 'manage_edit-'.CFQPZW_POST_TYPE.'_sortable_columns', array( $this, 'filter__cfqpzw_manage_data_sortable_columns' ), 10, 1 );

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

		/**
		* QPay tab
		* Adding tab in contact form 7
		*
		* @param $panels
		*
		* @return array
		*/
		public function filter__cfqpzw_wpcf7_editor_panels( $panels ) {

			$panels[ 'qpay-add-on' ] = array(
				'title'     => __( 'QPay', 'accept-qpay-payments-using-contact-form-7' ),
				'callback'  => array( $this, 'wpcf7_cfqpzw_admin_after_additional_settings' )
			);

			return $panels;
		}

		/**
		* Filter: post_row_actions
		*
		* - Used to modify the post list action buttons.
		*
		* @method filter__cfqpzw_post_row_actions
		*
		* @param  array $actions
		*
		* @return array
		*/
		function filter__cfqpzw_post_row_actions( $actions ) {

			if ( get_post_type() === CFQPZW_POST_TYPE ) {
				unset( $actions['view'] );
				unset( $actions['inline hide-if-no-js'] );
			}

			return $actions;
		}

		/**
		* Filter: plugin_action_links
		*
		* - Used to add links on Plugins listing page.
		*
		* @method filter__cfqpzw_admin_plugin_links
		*
		* @param  array $actions
		*
		* @return string
		*/
		function filter__cfqpzw_admin_plugin_links( $links, $file ) {
			if ( $file != CFQPZW_PLUGIN_BASENAME ) {
				return $links;
			}

			if ( ! current_user_can( 'wpcf7_read_contact_forms' ) ) {
				return $links;
			}

			$documentLink = '<a target="_blank" href="'.CFQPZW_DOCUMENT.'">' . __( 'Document Link', 'accept-qpay-payments-using-contact-form-7' ) . '</a>';
			array_unshift( $links , $documentLink);

			return $links;
		}

		/**
		* Filter: manage_cfqpzw_data_posts_columns
		*
		* - Used to add new column fields for the "cfqpzw_data" CPT
		*
		* @method filter__cfqpzw_manage_data_posts_columns
		*
		* @param  array $columns
		*
		* @return array
		*/
		function filter__cfqpzw_manage_data_posts_columns( $columns ) {
			unset( $columns['date'] );
			$columns['order_id']			= __( 'Order ID', 'accept-qpay-payments-using-contact-form-7' );
			$columns['transaction_id']		= __( 'Transaction ID', 'accept-qpay-payments-using-contact-form-7' );
			$columns['transaction_status']	= __( 'Transaction Status', 'accept-qpay-payments-using-contact-form-7' );
			$columns['total']				= __( 'Total Amount', 'accept-qpay-payments-using-contact-form-7' );
			$columns['date']				= __( 'Submitted Date', 'accept-qpay-payments-using-contact-form-7' );
			return $columns;
		}

		/**
		* Filter: manage_edit-cfqpzw_data_sortable_columns
		*
		* - Used to add the sortable fields into "cfqpzw_data" CPT
		*
		* @method filter__cfqpzw_manage_data_sortable_columns
		*
		* @param  array $columns
		*
		* @return array
		*/
		function filter__cfqpzw_manage_data_sortable_columns( $columns ) {
			$columns['total'] = '_total';
			return $columns;
		}

		/**
		* Filter: bulk_actions_edit_data
		*
		* - Add/Remove bulk actions for "cfqpzw_data" CPT
		*
		* @method filter__cfqpzw_bulk_actions_edit_data
		*
		* @param  array $actions
		*
		* @return array
		*/
		function filter__cfqpzw_bulk_actions_edit_data( $actions ) {
			unset( $actions['edit'] );
			return $actions;
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

		/**
		* Adding QPay fields in Qpay tab
		*
		* @param $cf7
		*/
		public function wpcf7_cfqpzw_admin_after_additional_settings( $cf7 ) {

			wp_enqueue_script( CFQPZW_PREFIX . '_admin_js' );
			require_once( CFQPZW_DIR .  '/inc/admin/template/' . CFQPZW_PREFIX . '.template.php' );

		}

	}
	add_action( 'plugins_loaded', function() {
		CFQPZW()->admin = new CFQPZW_Admin_Filter;
	} );
}
