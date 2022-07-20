<?php
/**
 * CFQPZW_Lib Class
 *
 * Handles the Library functionality.
 *
 * @package WordPress
 * @package Accept Qpay payments Using Contact form 7
 * @since 1.1
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CFQPZW_Lib' ) ) {

	class CFQPZW_Lib {


		var $data_fields = array(
			'_form_id'              => 'Form ID/Name',
			'_email'                => 'Email Address',
			'_transaction_id'       => 'Transaction ID',
			'_order_id'             => 'Order ID',
			'_amount'               => 'Amount',
			'_quantity'             => 'Quantity',
			'_total'                => 'Total',
			'_submit_time'          => 'Submit Time',
			'_request_ip'           => 'Request IP',
			'_currency'             => 'Currency code',
			'_form_data'            => 'Form data',
			'_transaction_status'   => 'Transaction status',
			'_transaction_response' => 'Transaction response',
		);

		function __construct() {

			add_action( 'init',                      array( $this, 'action__cfqpzw_init' ) );
			add_action( 'init',                      array( $this, 'action__cfqpzw_qpay_direct_ipn' ) );
			add_action( 'wpcf7_init',                array( $this, 'action__cfqpzw_wpcf7_init' ), 10, 0 );
			add_action( 'wpcf7_init',                array( $this, 'action__cfqpzw_wpcf7_verify_version' ), 10, 0 );
			add_action( 'wpcf7_before_send_mail',    array( $this, 'action__cfqpzw_wpcf7_before_send_mail' ), 20, 3 );
			add_shortcode( 'qpay-details',           array( $this, 'shortcode__qpay_details' ) );

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
		 * Action: init
		 *
		 * - Fire the email when return back from the 2checkout.
		 *
		 * @method action__init
		 *
		 */

		function action__cfqpzw_init() {
			if ( !isset( $_SESSION ) || session_status() == PHP_SESSION_NONE ) {
				session_start();
			}
		}

		/**
		 * Qpay Verify CF7 dependencies.
		 *
		 * @method action__cfqpzw_wpcf7_verify_version
		 *
		 */
		function action__cfqpzw_wpcf7_verify_version(){

			$cf7_verify = $this->wpcf7_version();
			if ( version_compare($cf7_verify, '5.2') >= 0 ) {
				add_filter( 'wpcf7_feedback_response',	array( $this, 'filter__cfqpzw_wpcf7_ajax_json_echo' ), 20, 2 );
			} else{
				add_filter( 'wpcf7_ajax_json_echo',	array( $this, 'filter__cfqpzw_wpcf7_ajax_json_echo' ), 20, 2 );
			}

		}

		/**
		* Action: init
		*
		* - Fire the email when return back from the QPay.
		*
		* @method action__init
		*
		*/
		function action__cfqpzw_qpay_direct_ipn(){
			global $wpdb;

			$form_ID = (int)( isset( $_REQUEST['form'] ) ? sanitize_text_field( $_REQUEST['form'] ) : '' );

			if (
				isset( $_REQUEST['status'] )
				&& !empty( $_REQUEST['status'] )
				&& isset( $_REQUEST['qpay_direct'] )
				&& !empty( $_REQUEST['qpay_direct'] == 'ipn' )
				&& !empty( $form_ID )
			){

				$from_data  = unserialize( $_SESSION[ CFQPZW_META_PREFIX . 'form_instance' ] );
				$form_ID	= sanitize_text_field( $_REQUEST['form'] );

				$attachment = '';
				if(!empty($_SESSION[ CFQPZW_META_PREFIX . 'form_attachment_' . $form_ID ])){
					$attachment = str_replace('\\', '/', $_SESSION[ CFQPZW_META_PREFIX . 'form_attachment_' . $form_ID ] );
				}

				$get_posted_data = $from_data->get_posted_data();

				$mode           = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'payment_mode', true );
				$orderid        = sanitize_text_field( $_REQUEST['referenceId'] );
				$status         = sanitize_text_field( $_REQUEST['status'] );
				$amount         = sanitize_text_field( $_REQUEST['amount'] );
				$currency       = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'currency', true );
				$customer_name  = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_name', true );
				$quantity       = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'quantity', true );
				$amount         = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'amount', true );
				$customer_email = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_email', true );

				$customer_name  = ( ( !empty( $customer_name ) && array_key_exists( $customer_name, $get_posted_data ) ) ?  $get_posted_data[$customer_name]  : '' );
				$amount_val     = ( ( !empty( $amount ) && array_key_exists( $amount, $get_posted_data ) ) ? floatval( $get_posted_data[$amount] ) : '0' );
				$quanity_val    = ( ( !empty( $quantity ) && array_key_exists( $quantity, $get_posted_data ) ) ? floatval( $get_posted_data[$quantity] ) : '0' );
				$customer_email = ( ( !empty( $customer_email ) && array_key_exists( $customer_email, $get_posted_data ) ) ? $get_posted_data[$customer_email] : '' );
				$exceed_ct		= sanitize_text_field( substr( get_option( '_exceed_cfqpzw_l' ), 6 ) );
				if (
					!empty( $amount )
					&& array_key_exists( $amount, $get_posted_data )
					&& is_array( $get_posted_data[$amount] )
					&& !empty( $get_posted_data[$amount] )
				) {
					$val = 0;
					foreach ( $get_posted_data[$amount] as $k => $value ) {
						$val = $val + floatval($value);
					}
					$amount_val = $val;
				}

				if (
					!empty( $quantity )
					&& array_key_exists( $quantity, $get_posted_data )
					&& is_array( $get_posted_data[$quantity] )
					&& !empty( $get_posted_data[$quantity] )
				) {
					$qty_val = 0;
					foreach ( $get_posted_data[$quantity] as $k => $qty ) {
						$qty_val = $qty_val + floatval($qty);
					}
					$quanity_val = $qty_val;
				}

				$total_amount_Payable = (float) ( empty( $quanity_val ) ? $amount_val : ( $quanity_val* $amount_val ) );
				$total_amount_Payable = sprintf('%0.2f',$total_amount_Payable);

				$transactions_message = $status;
				$transaction_status = $status;

				if( $transaction_status == 'success' ){
					$transaction_icon = 'success';
				}else{
					$transaction_icon = 'error';
				}

				if(isset($_REQUEST['transactionId']) && !empty($_REQUEST['transactionId'])){
					$transactionid 	= sanitize_text_field( $_REQUEST['transactionId'] );
					$different = $wpdb->get_var( "SELECT* FROM {$wpdb->postmeta} WHERE meta_key = '_transaction_id' AND meta_value = '$transactionid'" );
					if( !empty( $different) ) {
						return;
					}
				}

				if( $transaction_status == 'success' )
				{
					$cfqpzw_post_id = wp_insert_post( array (
						'post_type'     => CFQPZW_POST_TYPE,
						'post_title'    => $customer_email, // email/invoice_no
						'post_status'   => 'publish',
						'comment_status'=> 'closed',
						'ping_status'   => 'closed',
					) );
				}

				if ( !empty( $cfqpzw_post_id )  && $transaction_status == 'success' ) {

					if(!get_option('_exceed_cfqpzw')){
						sanitize_text_field( add_option('_exceed_cfqpzw', '1') );
					}else{
						$exceed_val = sanitize_text_field( get_option( '_exceed_cfqpzw' ) ) + 1;
						update_option( '_exceed_cfqpzw', $exceed_val );
					}

					if ( !empty( sanitize_text_field( get_option( '_exceed_cfqpzw' ) ) ) && sanitize_text_field( get_option( '_exceed_cfqpzw' ) ) > $exceed_ct ) {
						$get_posted_data['_exceed_num_cfqpzw'] = '1';
					}

					add_post_meta( $cfqpzw_post_id, '_form_id', sanitize_text_field( $form_ID ));
					add_post_meta( $cfqpzw_post_id, '_email', sanitize_text_field( $customer_email ));
					add_post_meta( $cfqpzw_post_id, '_order_id', sanitize_text_field( $orderid ));
					add_post_meta( $cfqpzw_post_id, '_transaction_id', sanitize_text_field( $transactionid ));
					add_post_meta( $cfqpzw_post_id, '_amount', sanitize_text_field( $amount_val .' '. $currency ));
					add_post_meta( $cfqpzw_post_id, '_quantity', sanitize_text_field( $quanity_val ));
					add_post_meta( $cfqpzw_post_id, '_total', sanitize_text_field( $total_amount_Payable .' '. $currency ));
					add_post_meta( $cfqpzw_post_id, '_request_ip', sanitize_text_field( $this->getUserIpAddr() ));
					add_post_meta( $cfqpzw_post_id, '_currency', sanitize_text_field( $currency ));
					add_post_meta( $cfqpzw_post_id, '_form_data', serialize( $get_posted_data ) );
					add_post_meta( $cfqpzw_post_id, '_transaction_status', sanitize_text_field( $transaction_status ));
					add_post_meta( $cfqpzw_post_id, '_attachment', sanitize_text_field( $attachment ));

					$data                        = array();
					$data['Transaction ID']      =  $transactionid;
					$data['Transaction Message'] =  $status;
					$data['Amount']              =  $total_amount_Payable .' '. $currency;
					$data['Invoice Number']      =  $orderid;

					add_filter( 'wpcf7_mail_components', array( $this, 'cfqpzw_filter__wpcf7_mail_components' ), 888, 3 );
					$this->mail( $from_data, $get_posted_data, $data);
					remove_filter( 'wpcf7_mail_components', array( $this, 'cfqpzw_filter__wpcf7_mail_components' ), 888, 3 );

				}
				unset( $_SESSION[ CFQPZW_META_PREFIX . 'secure_form' . $form_ID ] );
			}
		}

		/**
		* Email send
		*
		* @method mail
		*
		* @param  object $contact_form WPCF7_ContactForm::get_instance()
		* @param  [type] $posted_data  WPCF7_Submission::get_posted_data()
		*
		* @uses $this->prop(), $this->mail_replace_tags(), $this->get_form_attachments(),
		*
		* @return bool
		*/
		function mail( $contact_form, $posted_data, $payment_info_data) {

			if( empty( $contact_form ) ) {
				return false;
			}
			$contact_form_data = $contact_form->get_contact_form();

			$mail = $contact_form_data->prop( 'mail' );
			$mail = $this->mail_replace_tags( $mail, $posted_data, $payment_info_data );

			$result = WPCF7_Mail::send( $mail, 'mail' );

			if ( $result ) {
				$additional_mail = array();

				if (
					$mail_2 = $this->prop( 'mail_2', $contact_form_data )
					and $mail_2['active']
				) {

					$mail_2 = $this->mail_replace_tags( $mail_2, $posted_data, $payment_info_data );
					$additional_mail['mail_2'] = $mail_2;
				}

				$additional_mail = apply_filters( 'wpcf7_additional_mail', $additional_mail, $contact_form_data );

				foreach ( $additional_mail as $name => $template ) {
					WPCF7_Mail::send( $template, $name );
				}

				return true;
			}

			return false;
		}

		/**
		* Filter: Modify the email components.
		*
		* @method filter__wpcf7_mail_components
		*
		* @param  array $components
		* @param  object $current_form WPCF7_ContactForm::get_current()
		* @param  object $mail WPCF7_Mail::get_current()
		*
		* @return array
		*/
		function cfqpzw_filter__wpcf7_mail_components( $components, $current_form, $mail ) {

			$from_data = unserialize( $_SESSION[ CFQPZW_META_PREFIX . 'form_instance' ] );

			$form_ID = $from_data->get_contact_form()->id();

			if (
				   !empty( $mail->get( 'attachments', true ) )
				&& !empty( $this->get_form_attachments( $form_ID ) )
			) {
				$components['attachments'] = $this->get_form_attachments( $form_ID );
			}

			return $components;
		}

		/**
		* get the property from the
		*
		* @method prop    used from WPCF7_ContactForm:prop()
		*
		* @param  string $name
		* @param  object $class_object WPCF7_ContactForm:get_current()
		*
		* @return mixed
		*/
		public function prop( $name, $class_object ) {
			$props = $class_object->get_properties();
			return isset( $props[$name] ) ? $props[$name] : null;
		}

		/**
		* Mail tag replace
		*
		* @method mail_replace_tags
		*
		* @param  array $mail
		* @param  array $data
		*
		* @return array
		*/
		function mail_replace_tags( $mail, $data, $payment_info_data ) {

			$mail = ( array ) $mail;
			$data = ( array ) $data;

			$amount = (
				(
					!empty( $data )
					&& is_array( $data )
					&& array_key_exists( '_wpcf7', $data )
				)
				? get_post_meta( $data['_wpcf7'], CFQPZW_META_PREFIX . 'amount', true )
				: ''
			) ;

			$quantity = (
				(
					!empty( $data )
					&& is_array( $data )
					&& array_key_exists( '_wpcf7', $data )
				)
				? get_post_meta( $data['_wpcf7'], CFQPZW_META_PREFIX . 'quantity', true )
				: ''
			) ;

			$new_mail = array();

			if ( !empty( $mail ) && !empty( $data ) ) {

				foreach ( $mail as $key => $value ) {
					if( $key != 'attachments' ) {

						foreach ( $data as $k => $v ) {
							if (
								!empty( $amount )
								&& is_array( $v )
								&& $k == $amount
							) {
								$v2 = array_sum( $v );
							}elseif (
								!empty( $quantity )
								&& is_array( $v )
								&& $k == $quantity
							) {
								$v2 = array_sum( $v );
							} else if ( is_array( $v ) ) {
								$v2 = implode (", ", $v );
							} else {
								$v2 = $v;
							}

							$value = str_replace( '[' . $k . ']' , $v2, $value );
						}

						if ( $key == 'body' ){

							if( is_array( $payment_info_data ) ){

								$paypaldetails = '';
								if ( $mail['use_html'] == 2 ) {
									$paypaldetails .= "<h2>".__( 'QPay Response Details:', 'accept-qpay-payments-using-contact-form-7' )."</h2><table>";

									foreach($payment_info_data as $paymentKey => $paymentData){
										$paypaldetails .= '<tr><td>'.__( $paymentKey, 'accept-qpay-payments-using-contact-form-7' ).'</td><td>'.$paymentData.
										'</td></tr>';
									}

									$paypaldetails .= '</table>';
								} else {

									$paypaldetails .= __( 'QPay Response Details:', 'accept-qpay-payments-using-contact-form-7' )."\n"."\n";
									foreach($payment_info_data as $paymentKey => $paymentData){
										$paypaldetails .= __( $paymentKey, 'accept-qpay-payments-using-contact-form-7' ).' : '.$paymentData."\n";
									}
								}

								$value = str_replace('[qpay-payment-details]', $paypaldetails, $value);
							}
						}
					}
					$new_mail[ $key ] = $value;
				}
			}

			return $new_mail;
		}

		/**
		* Get attachment for the from
		*
		* @method get_form_attachments
		*
		* @param  int $form_ID form_id
		*
		* @return array
		*/
		function get_form_attachments( $form_ID ) {
			if(
				!empty( $form_ID )
				&& isset( $_SESSION[ CFQPZW_META_PREFIX . 'form_attachment_' . $form_ID ] )
				&& !empty( $_SESSION[ CFQPZW_META_PREFIX . 'form_attachment_' . $form_ID ] )
			) {
				return unserialize( $_SESSION[ CFQPZW_META_PREFIX . 'form_attachment_' . $form_ID ] );
			}
		}

		/**
		 * Initialize Qpay Country tag
		 *
		 * @method action__cfqpzw_wpcf7_init
		 *
		 *  @param  array form_tag
		 *
		 * @return	mixed
		 */
		function action__cfqpzw_wpcf7_init() {

			wpcf7_add_form_tag(
				array( 'qpay_country', 'qpay_country*' ),
				array( $this, 'wpcf7_qpay_country_form_tag_handler' ),
				array( 'name-attr' => true )
			);

			add_filter( 'wpcf7_validate_qpay_country',  array( $this, 'wpcf7_qpay_country_validation_filter' ), 10, 2 );
			add_filter( 'wpcf7_validate_qpay_country*', array( $this, 'wpcf7_qpay_country_validation_filter' ), 10, 2 );
		}

		/**
		* Action: CF7 before send email
		*
		* @method action__cfqpzw_wpcf7_before_send_mail
		*
		* @param  object $contact_form WPCF7_ContactForm::get_instance()
		* @param  bool   $abort
		* @param  object $contact_form WPCF7_Submission class
		*
		*/

		function action__cfqpzw_wpcf7_before_send_mail( $contact_form, $abort, $wpcf7_submission ) {
			$submission		= WPCF7_Submission::get_instance(); // CF7 Submission Instance
			$form_ID			= $contact_form->id();
			$form_instance	= WPCF7_ContactForm::get_instance($form_ID); // CF7 From Instance

			if ( $submission ) {
				// CF7 posted data
				$posted_data = $submission->get_posted_data();
			}

			if ( !empty( $form_ID ) ) {

				$use_qpay = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'use_qpay', true );

				if ( empty( $use_qpay ) )
					return;

				$use_qpay                       = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'use_qpay', true );
				$payment_mode                   = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'payment_mode', true );
				$qpay_gateway_id                = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'qpay_gateway_id', true );
				$qpay_secret_key                = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'qpay_secret_key', true );
				$payment_unique_prefix          = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'order_unique_prefix', true );
				if( $payment_unique_prefix != '' ){
					$payment_unique_prefix = $payment_unique_prefix.'-';
				}
				$amount                         = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'amount', true );
				$quantity                       = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'quantity', true );
				//Customer Billing Details
				$customer_name                  = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_name', true );
				$customer_address               = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_address', true );
				$customer_city                  = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_city', true );
				$customer_state                 = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_state', true );
				$customer_country               = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_country', true );
				$customer_phone                 = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_phone', true );
				$customer_email                 = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_email', true );

				$success_return_url             = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'returnurl', true );
				$currency                       = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'currency', true );
				$success_returnurl              = get_permalink( $success_return_url );

				if ( empty( $customer_name ) || empty( $customer_address ) || empty( $customer_city ) || empty( $customer_state )
					||empty( $customer_country )|| empty( $customer_phone ) || empty( $customer_email ) )	{
						$error = __( 'Payment Page not Configured Properly. Please Conatct Admin. ', 'accept-qpay-payments-using-contact-form-7' );
				}

				$amount_val  = ( ( !empty( $amount ) && array_key_exists( $amount, $posted_data ) ) ? floatval( $posted_data[$amount] ) : '0' );
				$quanity_val = ( ( !empty( $quantity ) && array_key_exists( $quantity, $posted_data ) ) ? floatval( $posted_data[$quantity] ) : '' );

				$customer_name      = ( ( !empty( $customer_name ) && array_key_exists( $customer_name, $posted_data ) ) ?  $posted_data[$customer_name]  : '' );
				$customer_address   = ( ( !empty( $customer_address ) && array_key_exists( $customer_address, $posted_data ) ) ? $posted_data[$customer_address] : '' );
				$customer_city      = ( ( !empty( $customer_city ) && array_key_exists( $customer_city, $posted_data ) ) ?  $posted_data[$customer_city]  : '' );
				$customer_state     = ( ( !empty( $customer_state ) && array_key_exists( $customer_state, $posted_data ) ) ?  $posted_data[$customer_state]  : '' );
				$customer_country   = ( ( !empty( $customer_country ) && array_key_exists( $customer_country, $posted_data ) ) ?  $posted_data[$customer_country] : '' );
				$customer_phone     = ( ( !empty( $customer_phone ) && array_key_exists( $customer_phone, $posted_data ) ) ?  $posted_data[$customer_phone] : '' );
				$customer_email     = ( ( !empty( $customer_email ) && array_key_exists( $customer_email, $posted_data ) ) ? $posted_data[$customer_email] : '' );

				if (
					!empty( $amount )
					&& array_key_exists( $amount, $posted_data )
					&& is_array( $posted_data[$amount] )
					&& !empty( $posted_data[$amount] )
				) {
					$val = 0;
					foreach ( $posted_data[$amount] as $k => $value ) {
						$val = $val + floatval($value);
					}
					$amount_val = $val;
				}

				if (
					!empty( $quantity )
					&& array_key_exists( $quantity, $posted_data )
					&& is_array( $posted_data[$quantity] )
					&& !empty( $posted_data[$quantity] )
				) {
					$qty_val = 0;
					foreach ( $posted_data[$quantity] as $k => $qty ) {
						$qty_val = $qty_val + floatval($qty);
					}
					$quanity_val = $qty_val;
				}

				$amountPayable = (float) ( empty( $quanity_val ) ? $amount_val : ( $quanity_val* $amount_val ) );

				if ( empty( $amountPayable ) ) {
					add_filter( 'wpcf7_skip_mail', array( $this, 'cfqpzw_filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CFQPZW_META_PREFIX . 'amount_error' . $form_ID ] = __( 'Please Enter Amount value or Value in Numeric.', 'accept-qpay-payments-using-contact-form-7' );
					return;
				}

				if (
					$amountPayable < 0
					&& $amountPayable != 0
				)  {
					add_filter( 'wpcf7_skip_mail', array( $this, 'cfqpzw_filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CFQPZW_META_PREFIX . 'amount_error' . $form_ID ] = __( 'Please Enter Amount value or Value in Numeric.', 'accept-qpay-payments-using-contact-form-7' );
					return;
				}

				$amountPayable = sprintf('%0.2f', $amountPayable);

				$validate_field = array();

				if ( empty( $amount_val ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'amount', true );

				if ( empty( $customer_name ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_name', true );

				if ( empty( $customer_address ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_address', true );

				if ( empty( $customer_city ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_city', true );

				if ( empty( $customer_state ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_state', true );

				if ( empty( $customer_country ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_country', true );

				if ( empty( $customer_phone ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_phone', true );

				if ( empty( $customer_email ) )
					$validate_field[] = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'customer_email', true );

				if ( !empty( $validate_field ) ){
					add_filter( 'wpcf7_skip_mail', array( $this, 'cfqpzw_filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CFQPZW_META_PREFIX . 'qpay_fields_error' . $form_ID ] = array_unique( $validate_field );
					return;
				}

				if( $payment_mode == 'sandbox'){
					$qpay_gateway_url = 'https://demopaymentapi.qpayi.com/api/gateway/v1.0';
				}else{
					$qpay_gateway_url = 'https://qpayi.com:9100/api/gateway/v1.0';
				}

				if ( !empty( $success_return_url ) && $success_return_url != 'Select page') {
					$success_returnurl = $success_returnurl;
				}else{
					$success_returnurl = $submission->get_meta( 'url');
				}

				$mail = $contact_form->prop( 'mail' );
				$VendorEMail = $mail['recipient'];

				$generate_success_returnurl = add_query_arg( array( 'qpay_direct' => 'ipn','form' => $form_ID ), $success_returnurl );

				$time_stamp = date("ymdHis");
				$orderid = $payment_unique_prefix.$form_ID . "-" . $time_stamp;

				$qpay_arg['action']         = 'capture';
				$qpay_arg['gatewayId']      = $qpay_gateway_id;
				$qpay_arg['secretKey']      = $qpay_secret_key;
				$qpay_arg['referenceId']    = $orderid;
				$qpay_arg['amount']         = $amountPayable;
				$qpay_arg['currency']       = $currency;
				$qpay_arg['mode']           = $payment_mode;
				$qpay_arg['description']    = sprintf(__('Order %s', 'contact-form-7-qpay-addon'), $orderid);
				$qpay_arg['returnUrl']      = $generate_success_returnurl;
				$qpay_arg['name']           = $customer_name;
				$qpay_arg['address']        = $customer_address;
				$qpay_arg['city']           = $customer_city;
				$qpay_arg['state']          = $customer_state;
				$qpay_arg['country']        = $customer_country;
				$qpay_arg['phone']          = $customer_phone;
				$qpay_arg['email']          = $customer_email;

				$post_values = "";
				foreach( $qpay_arg as $key => $value ) {
					$post_values .= "$key=" . $value . "&";
				}
				$post_values = rtrim( $post_values, "& " );

				// Set string in block size
				$datapadded = $this->pkcs5_pad( trim( $post_values ),16 );

				$qpay_arg_array = array();
				foreach ($qpay_arg as $key => $value) {
					$qpay_arg_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
				}

				$secure_form = '<form action="'.$qpay_gateway_url.'" method="post" name="qpay-payment-form" id=qpay-payment-form-'.$form_ID.' >
						' . implode('', $qpay_arg_array) . '
						</form>';

				$_SESSION[ CFQPZW_META_PREFIX . 'qpayauth' . $form_ID ] = 'redirect';
				$_SESSION[ CFQPZW_META_PREFIX . 'secure_form' . $form_ID ] = $secure_form;

				if( !empty( $submission->uploaded_files() ) ) {
					$cf7_verify = $this->wpcf7_version();
					if ( version_compare( $cf7_verify, '5.4' ) >= 0 ) {
						$uploaded_files = $this->zw_cf7_upload_files( $submission->uploaded_files(), 'new' );
					}else{
						$uploaded_files = $this->zw_cf7_upload_files( array($submission->uploaded_files()), 'old' );
					}


					if ( !empty( $uploaded_files ) ) {
						$_SESSION[ CFQPZW_META_PREFIX . 'form_attachment_' . $form_ID ] = serialize( $uploaded_files );
					}
				}

				$_SESSION[ CFQPZW_META_PREFIX . 'form_instance' ] = serialize( $submission );
				add_filter( 'wpcf7_skip_mail', array( $this, 'cfqpzw_filter__wpcf7_skip_mail' ), 20 );

			}
			return $submission;

		}

		/**
		* Filter: Skip email when qpay enable.
		*
		* @method filter__wpcf7_skip_mail
		*
		* @param  bool $bool
		*
		* @return bool
		*/
		function cfqpzw_filter__wpcf7_skip_mail( $bool ) {
			return true;
		}

		function shortcode__qpay_details() {
			$form_ID = (int)( isset( $_REQUEST['form'] ) ? sanitize_text_field($_REQUEST['form']) : '' );

			if ( isset( $_REQUEST['status'] )
				&& !empty( $_REQUEST['status'] )
				&& !empty( $form_ID )
			)
			{
				$form_ID    = sanitize_text_field( $_REQUEST['form'] );
				$orderid    = sanitize_text_field( $_REQUEST['referenceId'] );
				$status     = sanitize_text_field( $_REQUEST['status'] );
				$amount     = sprintf('%0.2f',$_REQUEST['amount']);
				$currency   = get_post_meta( $form_ID, CFQPZW_META_PREFIX . 'currency', true );

				if (
					( $status == 'success' )
				) {
					$transactionid 	= sanitize_text_field( $_REQUEST['transactionId'] );
					echo '<table class="cfqpzw-transaction-details" align="center">' .
						'<tr>'.
							'<th align="left">' . __( 'Transaction Amount :', 'accept-qpay-payments-using-contact-form-7' ) . '</th>'.
							'<td align="left">' . $amount . ' ' . $currency . '</td>'.
						'</tr>' .
						'<tr>'.
							'<th align="left">' . __( 'Payment Status :', 'accept-qpay-payments-using-contact-form-7' ) . '</th>'.
							'<td align="left">' . $status . '</td>'.
						'</tr>' .
						'<tr>'.
							'<th align="left">' . __( 'Transaction Id :', 'accept-qpay-payments-using-contact-form-7' ) . '</th>'.
							'<td align="left">' . $transactionid . '</td>'.
						'</tr>' .
						'<tr>'.
							'<th align="left">' . __( 'Invoice ID :', 'accept-qpay-payments-using-contact-form-7' ) . '</th>'.
							'<td align="left">' . $orderid . '</td>'.
						'</tr>' .
					'</table>';

				}else{
					$status_reason = sanitize_text_field( $_REQUEST['reason'] );
					echo '<table class="cfqpzw-transaction-details" align="center">' .
					'<tr>'.
						'<th align="left">' . __( 'Response :', 'accept-qpay-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . ucfirst($status) . '</td>'.
					'</tr>' .
					'<tr>'.
						'<th align="left">' . __( 'Response Message :', 'accept-qpay-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . ucfirst($status_reason) . '</td>'.
					'</tr>' .
				'</table>';
				}

			}

			return ob_get_clean();

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
		* Filter: Modify the contact form 7 response.
		*
		* @method filter__cfqpzw_wpcf7_ajax_json_echo
		*
		* @param  array $response
		* @param  array $result
		*
		* @return array
		*/
		function filter__cfqpzw_wpcf7_ajax_json_echo( $response, $result ) {

			$cf7_verify = $this->wpcf7_version();

			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CFQPZW_META_PREFIX . 'qpayauth' . $result[ 'contact_form_id' ] ]  )
				&& $_SESSION[ CFQPZW_META_PREFIX . 'qpayauth' . $result[ 'contact_form_id' ] ]  == 'redirect'
			) {

				$response["status"]        = "mail_sent";
				$response["redirect_form"] = $_SESSION[ CFQPZW_META_PREFIX . 'secure_form' . $result[ 'contact_form_id' ] ];
				$response["message"]       = __( 'Please wait you are redirecting to QPay..!', 'contact-form-7-qpay-addon');
				unset( $_SESSION[ CFQPZW_META_PREFIX . 'qpayauth' . $result[ 'contact_form_id' ] ] );
			}

			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CFQPZW_META_PREFIX . 'qpay_fields_error' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {
				$response[ 'message' ] = __('One or more fields have an error. Please check and try again.', CFQPZW_PREFIX);
				$response[ 'status' ]  = 'validation_failed';
				$fields_msg            = array();

				foreach ($_SESSION[ CFQPZW_META_PREFIX . 'qpay_fields_error' . $result[ 'contact_form_id' ] ] as $value) {

					$field_error_message['into'] = 'span.wpcf7-form-control-wrap.'.$value;
					if( $value == 'amount' ){
						$field_error_message['message'] = __( 'Please Enter Amount value or Value in Numeric.', 'accept-qpay-payments-using-contact-form-7');
					}else{
						$field_error_message['message'] = __('The field is required.', 'accept-qpay-payments-using-contact-form-7');
					}
					$fields_msg[] = $field_error_message;
				}
				if ( version_compare($cf7_verify, '5.2') >= 0 ) {
					$response[ 'invalid_fields' ] = $fields_msg;
				} else {
					$response[ 'invalidFields' ] = $fields_msg;
				}
				unset( $_SESSION[ CFQPZW_META_PREFIX . 'qpay_fields_error' . $result[ 'contact_form_id' ] ] );
			}

			if (
				array_key_exists( 'contact_form_id', $result )
				&& array_key_exists( 'status', $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CFQPZW_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {
				$amount  = get_post_meta( $result[ 'contact_form_id' ], CFQPZW_META_PREFIX . 'amount', true );
				$response[ 'message' ]       = __('One or more fields have an error. Please check and try again.', 'accept-qpay-payments-using-contact-form-7');
				$response[ 'status' ]        = 'validation_failed';
				if ( version_compare($cf7_verify, '5.2') >= 0 ) {
					$response[ 'invalid_fields' ] = array(
														array(
														'into'=>'span.wpcf7-form-control-wrap.'.$amount,
														'message'=> $_SESSION[ CFQPZW_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] ));
				} else {
					$response[ 'invalidFields' ] = array(
														array(
														'into'=>'span.wpcf7-form-control-wrap.'.$amount,
														'message'=> $_SESSION[ CFQPZW_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] ));
				}
				unset( $_SESSION[ CFQPZW_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] );
			}

			return $response;
		}

		/**
		 * Filter: wpcf7_qpay_country_validation_filter
		 *
		 * - Perform Validation on QPay card details.
		 *
		 * @param  object  $result WPCF7_Validation
		 * @param  object  $tag    Form tag
		 *
		 * @return object
		 */
		function wpcf7_qpay_country_validation_filter( $result, $tag ) {

			$qpay_country = isset( $_POST[$tag['name']] ) ? sanitize_text_field($_POST[$tag['name']]) : array();

			$id = isset( $_POST[ '_wpcf7' ] ) ? sanitize_text_field($_POST[ '_wpcf7' ]) : '';

			if ( !empty( $id ) ) {
				$id = ( int ) $_POST[ '_wpcf7' ];
			} else {
				return $result;
			}

			$use_qpay = get_post_meta( $id, CFQPZW_META_PREFIX . 'use_qpay', true );

			if ( empty( $use_qpay ) )
				return $result;

			$error = array();

			if ( empty( $qpay_country ) )
				$error[] = __( 'Country', 'accept-qpay-payments-using-contact-form-7' );

			if ( !empty( $error ) )
				$result->invalidate( $tag, 'Please enter ' . implode(', ', $error ) );

			return $result;
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
		 * - Render CF7 Shortcode on front end.
		 *
		 * @method wpcf7_qpay_country_form_tag_handler
		 *
		 * @param $tag
		 *
		 * @return html
		 */

		function wpcf7_qpay_country_form_tag_handler( $tag ) {
			if ( empty( $tag->name ) ) {
				return '';
			}
			$validation_error = wpcf7_get_validation_error( $tag->name );

			$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

			if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) ) {
				$class .= ' wpcf7-validates-as-' . $tag->basetype;
			}

			if ( $validation_error ) {
				$class .= ' wpcf7-not-valid';
			}

			$atts = array();

			if ( $tag->is_required() ) {
				$atts['aria-required'] = 'true';
			}

			$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

			$atts['value'] = 1;

			$atts['type'] = 'hidden';
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts( $atts );

			$form_instance = WPCF7_ContactForm::get_current();
			$form_id = $form_instance->id();

			wp_enqueue_style( CFQPZW_PREFIX . '_front_css');
			wp_enqueue_script( CFQPZW_PREFIX . '_front_js');
			wp_enqueue_style( CFQPZW_PREFIX . '_select2' );
			wp_enqueue_script( CFQPZW_PREFIX . '_select2' );

			$value = (string) reset( $tag->values );

			$found = 0;
			$html = '';

			ob_start();


			if ( $contact_form = wpcf7_get_current_contact_form() ) {
				$form_tags = $contact_form->scan_form_tags();
				foreach ( $form_tags as $k => $v ) {

					if ( $v['type'] == $tag->type ) {
						$found++;
					}
					if ( $v['name'] == $tag->name ) {
						if ( $found <= 1 ) {

							echo '<span class="credit_card_details wpcf7-form-control-wrap '.sanitize_html_class( $tag->name ).'">
								<select name="' . $tag->name . '" class="wpcf7-form-control qpay-country">
									<option value="">Select Country</option>';
									echo $this->get_country();
							echo '</select></span>';
						}
						break;
					}
				}
			}
			return ob_get_clean();
		}

		/**
		 * [get_country description]
		 * @return [html] [option list of country]
		 */
		function get_country(){

			$country_lists = array( 'AF' => 'Afghanistan',
									'AX' => 'Aland Islands',
									'AL' => 'Albania',
									'DZ' => 'Algeria',
									'AS' => 'American Samoa',
									'AD' => 'Andorra',
									'AO' => 'Angola',
									'AI' => 'Anguilla',
									'AQ' => 'Antarctica',
									'AG' => 'Antigua and Barbuda',
									'AR' => 'Argentina',
									'AM' => 'Armenia',
									'AW' => 'Aruba',
									'AU' => 'Australia',
									'AT' => 'Austria',
									'AZ' => 'Azerbaijan',
									'BS' => 'Bahamas',
									'BH' => 'Bahrain',
									'BD' => 'Bangladesh',
									'BB' => 'Barbados',
									'BY' => 'Belarus',
									'BE' => 'Belgium',
									'BZ' => 'Belize',
									'BJ' => 'Benin',
									'BM' => 'Bermuda',
									'BT' => 'Bhutan',
									'BO' => 'Bolivia',
									'BQ' => 'Bonaire, Saint Eustatius and Saba',
									'BA' => 'Bosnia and Herzegovina',
									'BW' => 'Botswana',
									'BV' => 'Bouvet Island',
									'BR' => 'Brazil',
									'IO' => 'British Indian Ocean Territory',
									'VG' => 'British Virgin Islands',
									'BN' => 'Brunei',
									'BG' => 'Bulgaria',
									'BF' => 'Burkina Faso',
									'BI' => 'Burundi',
									'KH' => 'Cambodia',
									'CM' => 'Cameroon',
									'CA' => 'Canada',
									'CV' => 'Cape Verde',
									'KY' => 'Cayman Islands',
									'CF' => 'Central African Republic',
									'TD' => 'Chad',
									'CL' => 'Chile',
									'CN' => 'China',
									'CX' => 'Christmas Island',
									'CC' => 'Cocos Islands',
									'CO' => 'Colombia',
									'KM' => 'Comoros',
									'CK' => 'Cook Islands',
									'CR' => 'Costa Rica',
									'HR' => 'Croatia',
									'CU' => 'Cuba',
									'CW' => 'Curacao',
									'CY' => 'Cyprus',
									'CZ' => 'Czech Republic',
									'CD' => 'Democratic Republic of the Congo',
									'DK' => 'Denmark',
									'DJ' => 'Djibouti',
									'DM' => 'Dominica',
									'DO' => 'Dominican Republic',
									'TL' => 'East Timor',
									'EC' => 'Ecuador',
									'EG' => 'Egypt',
									'SV' => 'El Salvador',
									'GQ' => 'Equatorial Guinea',
									'ER' => 'Eritrea',
									'EE' => 'Estonia',
									'ET' => 'Ethiopia',
									'FK' => 'Falkland Islands',
									'FO' => 'Faroe Islands',
									'FJ' => 'Fiji',
									'FI' => 'Finland',
									'FR' => 'France',
									'GF' => 'French Guiana',
									'PF' => 'French Polynesia',
									'TF' => 'French Southern Territories',
									'GA' => 'Gabon',
									'GM' => 'Gambia',
									'GE' => 'Georgia',
									'DE' => 'Germany',
									'GH' => 'Ghana',
									'GI' => 'Gibraltar',
									'GR' => 'Greece',
									'GL' => 'Greenland',
									'GD' => 'Grenada',
									'GP' => 'Guadeloupe',
									'GU' => 'Guam',
									'GT' => 'Guatemala',
									'GG' => 'Guernsey',
									'GN' => 'Guinea',
									'GW' => 'Guinea-Bissau',
									'GY' => 'Guyana',
									'HT' => 'Haiti',
									'HM' => 'Heard Island and McDonald Islands',
									'HN' => 'Honduras',
									'HK' => 'Hong Kong',
									'HU' => 'Hungary',
									'IS' => 'Iceland',
									'IN' => 'India',
									'ID' => 'Indonesia',
									'IR' => 'Iran',
									'IQ' => 'Iraq',
									'IE' => 'Ireland',
									'IM' => 'Isle of Man',
									'IL' => 'Israel',
									'IT' => 'Italy',
									'CI' => 'Ivory Coast',
									'JM' => 'Jamaica',
									'JP' => 'Japan',
									'JE' => 'Jersey',
									'JO' => 'Jordan',
									'KZ' => 'Kazakhstan',
									'KE' => 'Kenya',
									'KI' => 'Kiribati',
									'XK' => 'Kosovo',
									'KW' => 'Kuwait',
									'KG' => 'Kyrgyzstan',
									'LA' => 'Laos',
									'LV' => 'Latvia',
									'LB' => 'Lebanon',
									'LS' => 'Lesotho',
									'LR' => 'Liberia',
									'LY' => 'Libya',
									'LI' => 'Liechtenstein',
									'LT' => 'Lithuania',
									'LU' => 'Luxembourg',
									'MO' => 'Macao',
									'MK' => 'Macedonia',
									'MG' => 'Madagascar',
									'MW' => 'Malawi',
									'MY' => 'Malaysia',
									'MV' => 'Maldives',
									'ML' => 'Mali',
									'MT' => 'Malta',
									'MH' => 'Marshall Islands',
									'MQ' => 'Martinique',
									'MR' => 'Mauritania',
									'MU' => 'Mauritius',
									'YT' => 'Mayotte',
									'MX' => 'Mexico',
									'FM' => 'Micronesia',
									'MD' => 'Moldova',
									'MC' => 'Monaco',
									'MN' => 'Mongolia',
									'ME' => 'Montenegro',
									'MS' => 'Montserrat',
									'MA' => 'Morocco',
									'MZ' => 'Mozambique',
									'MM' => 'Myanmar',
									'NA' => 'Namibia',
									'NR' => 'Nauru',
									'NP' => 'Nepal',
									'NL' => 'Netherlands',
									'NC' => 'New Caledonia',
									'NZ' => 'New Zealand',
									'NI' => 'Nicaragua',
									'NE' => 'Niger',
									'NG' => 'Nigeria',
									'NU' => 'Niue',
									'NF' => 'Norfolk Island',
									'KP' => 'North Korea',
									'MP' => 'Northern Mariana Islands',
									'NO' => 'Norway',
									'OM' => 'Oman',
									'PK' => 'Pakistan',
									'PW' => 'Palau',
									'PS' => 'Palestinian Territory',
									'PA' => 'Panama',
									'PG' => 'Papua New Guinea',
									'PY' => 'Paraguay',
									'PE' => 'Peru',
									'PH' => 'Philippines',
									'PN' => 'Pitcairn',
									'PL' => 'Poland',
									'PT' => 'Portugal',
									'PR' => 'Puerto Rico',
									'QA' => 'Qatar',
									'CG' => 'Republic of the Congo',
									'RE' => 'Reunion',
									'RO' => 'Romania',
									'RU' => 'Russia',
									'RW' => 'Rwanda',
									'BL' => 'Saint Barthelemy',
									'SH' => 'Saint Helena',
									'KN' => 'Saint Kitts and Nevis',
									'LC' => 'Saint Lucia',
									'MF' => 'Saint Martin',
									'PM' => 'Saint Pierre and Miquelon',
									'VC' => 'Saint Vincent and the Grenadines',
									'WS' => 'Samoa',
									'SM' => 'San Marino',
									'ST' => 'Sao Tome and Principe',
									'SA' => 'Saudi Arabia',
									'SN' => 'Senegal',
									'RS' => 'Serbia',
									'SC' => 'Seychelles',
									'SL' => 'Sierra Leone',
									'SG' => 'Singapore',
									'SX' => 'Sint Maarten',
									'SK' => 'Slovakia',
									'SI' => 'Slovenia',
									'SB' => 'Solomon Islands',
									'SO' => 'Somalia',
									'ZA' => 'South Africa',
									'GS' => 'South Georgia and the South Sandwich Islands',
									'KR' => 'South Korea',
									'SS' => 'South Sudan',
									'ES' => 'Spain',
									'LK' => 'Sri Lanka',
									'SD' => 'Sudan',
									'SR' => 'Suriname',
									'SJ' => 'Svalbard and Jan Mayen',
									'SZ' => 'Swaziland',
									'SE' => 'Sweden',
									'CH' => 'Switzerland',
									'SY' => 'Syria',
									'TW' => 'Taiwan',
									'TJ' => 'Tajikistan',
									'TZ' => 'Tanzania',
									'TH' => 'Thailand',
									'TG' => 'Togo',
									'TK' => 'Tokelau',
									'TO' => 'Tonga',
									'TT' => 'Trinidad and Tobago',
									'TN' => 'Tunisia',
									'TR' => 'Turkey',
									'TM' => 'Turkmenistan',
									'TC' => 'Turks and Caicos Islands',
									'TV' => 'Tuvalu',
									'VI' => 'U.S. Virgin Islands',
									'UG' => 'Uganda',
									'UA' => 'Ukraine',
									'AE' => 'United Arab Emirates',
									'GB' => 'United Kingdom',
									'US' => 'United States',
									'UM' => 'United States Minor Outlying Islands',
									'UY' => 'Uruguay',
									'UZ' => 'Uzbekistan',
									'VU' => 'Vanuatu',
									'VA' => 'Vatican',
									'VE' => 'Venezuela',
									'VN' => 'Vietnam',
									'WF' => 'Wallis and Futuna',
									'EH' => 'Western Sahara',
									'YE' => 'Yemen',
									'ZM' => 'Zambia',
									'ZW' => 'Zimbabwe',
								 );

			$country_list_html = '';

			foreach ($country_lists as $iso => $country_name) {
				$country_list_html .='<option value="'.$iso.'">'.$country_name.'</option>';
			}
			return $country_list_html;

		}

		/**
		 * Function: getUserIpAddr
		 *
		 * @method getUserIpAddr
		 *
		 * @return string
		 */
		function getUserIpAddr() {
			$ip = false;

			if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
				$ip = filter_var( $_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP );
			} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				// Check ip from share internet.
				$ip = filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
				if ( is_array( $ips ) ) {
					$ip = filter_var( $ips[0], FILTER_VALIDATE_IP );
				}
			} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
			}

			$ip       = false !== $ip ? $ip : '127.0.0.1';
			$ip_array = explode( ',', $ip );
			$ip_array = array_map( 'trim', $ip_array );

			if($ip_array[0] == '::1'){
				$ipser = array('http://ipv4.icanhazip.com','http://v4.ident.me','http://bot.whatismyipaddress.com');
				shuffle($ipser);
				$ipservices = array_slice($ipser, 0,1);
				$ret = wp_remote_get($ipservices[0]);
				if(!is_wp_error($ret)){
					if (isset($ret['body'])) {
						return sanitize_text_field( $ret['body'] );
					}
				}
			}

			return sanitize_text_field( apply_filters( 'cfgeo_get_ip', $ip_array[0] ) );
		}

		/**
		* Get the attachment upload directory from plugin.
		*
		* @method zw_wpcf7_upload_tmp_dir
		*
		* @return string
		*/
		function zw_wpcf7_upload_tmp_dir() {

			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'];
			$cfqpzw_upload_dir = $upload_dir . '/cfqpzw-uploaded-files';

			if ( !is_dir( $cfqpzw_upload_dir ) ) {
				mkdir( $cfqpzw_upload_dir, 0755 );
			}

			return $cfqpzw_upload_dir;
		}

		function pkcs5_pad($text, $blocksize)
		{
			$pad = $blocksize - (strlen($text) % $blocksize);
			return $text . str_repeat(chr($pad), $pad);
		}

		/**
		 * Copy the attachment into the plugin folder.
		 *
		 * @method zw_cf7_upload_files
		 *
		 * @param  array $attachment
		 *
		 * @uses $this->zw_wpcf7_upload_tmp_dir(), WPCF7::wpcf7_maybe_add_random_dir()
		 *
		 * @return array
		 */
		function zw_cf7_upload_files( $attachment, $version ) {
			if( empty( $attachment ) )
				return;

			$new_attachment = $attachment;
			foreach ( $attachment as $key => $value ) {
				$tmp_name = $value;
				$uploads_dir = wpcf7_maybe_add_random_dir( $this->zw_wpcf7_upload_tmp_dir() );
				foreach ($tmp_name as $newkey => $file_path) {
					$get_file_name = explode( '/', $file_path );
					$new_file = path_join( $uploads_dir, end( $get_file_name ) );

					if ( copy( $file_path, $new_file ) ) {
						chmod( $new_file, 0755 );
						if($version == 'old'){
							$new_attachment_file[$newkey] = $new_file;
						}else{
							$new_attachment_file[$key] = $new_file;
						}
					}
				}
			}
			return $new_attachment_file;
		}

		/**
		 * Get current conatct from 7 version.
		 *
		 * @method wpcf7_version
		 *
		 * @return string
		 */
		function wpcf7_version() {

			$wpcf7_path = plugin_dir_path( CFQPZW_DIR ) . 'contact-form-7/wp-contact-form-7.php';

			if( ! function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( $wpcf7_path );

			return $plugin_data['Version'];
		}

	}

add_action( 'plugins_loaded', function() {
	CFQPZW()->lib = new CFQPZW_Lib;
} );
}
