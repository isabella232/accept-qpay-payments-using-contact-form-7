<?php
/**
 * CFQPZW_Admin_Action Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @package Accept Qpay payments Using Contact form 7
 * @since 1.1
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CFQPZW_Admin_Action' ) ) {

	/**
	 *  The CFQPZW_Admin_Action Class
	 */
	class CFQPZW_Admin_Action {

		function __construct()  {

			add_action( 'admin_init',                                       array( $this, 'action__cfqpzw_init' ) );
			add_action( 'add_meta_boxes',                                   array( $this, 'action__cfqpzw_add_meta_boxes' ) );

			// Save settings of contact form 7 admin
			add_action( 'wpcf7_save_contact_form',                          array( $this, 'action__cfqpzw_wpcf7_save_contact_form' ), 20, 2 );
			add_action( 'manage_'.CFQPZW_POST_TYPE.'_posts_custom_column',  array( $this, 'action__manage_cfqpzw_data_posts_custom_column' ), 10, 2 );
			add_action( 'pre_get_posts',                                    array( $this, 'action__cfqpzw_pre_get_posts' ) );
			add_action( 'restrict_manage_posts',                            array( $this, 'action__cfqpzw_restrict_manage_posts' ) );
			add_action( 'parse_query',                                      array( $this, 'action__cfqpzw_parse_query' ) );
			add_action( CFQPZW_PREFIX . '/postbox',                         array( $this, 'action__cfqpzw_postbox' ) );
			add_action( 'wp_ajax_cfqpzw_review_done',           			array( $this, 'action__cfqpzw_review_done'));
			add_action( 'wp_ajax_nopriv_cfqpzw_review_done',    			array( $this, 'action__cfqpzw_review_done'));

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
		 * Action: admin_init
		 *
		 * - Register admin min js and admin min css
		 *
		 */
		function action__cfqpzw_init() {

			wp_register_script( CFQPZW_PREFIX . '_modal_js', CFQPZW_URL . 'assets/js/bootstrap.min.js', array(), CFQPZW_VERSION );
			wp_register_script( CFQPZW_PREFIX . '_cookie_js', CFQPZW_URL . 'assets/js/cookie.min.js', array(), CFQPZW_VERSION );
			wp_register_script( CFQPZW_PREFIX . '_admin_js', CFQPZW_URL . 'assets/js/admin.min.js', array( 'jquery-core' ), CFQPZW_VERSION );
			wp_register_style( CFQPZW_PREFIX . '_admin_css', CFQPZW_URL . 'assets/css/admin.min.css', array(), CFQPZW_VERSION );
			wp_register_style( 'select2', CFQPZW_URL . 'assets/css/select2.min.css', array(), CFQPZW_VERSION );
			wp_register_script( 'select2', CFQPZW_URL . 'assets/js/select2.min.js', array( 'jquery-core' ), CFQPZW_VERSION );

		}

		/**
		 * Action: add_meta_boxes
		 *
		 * - Add mes boxes for the CPT "cfqpzw_data"
		 */

		function action__cfqpzw_add_meta_boxes() {
			add_meta_box( 'cfqpzw-data', __( 'From Data', 'accept-qpay-payments-using-contact-form-7' ), array( $this, 'cfqpzw_show_from_data' ), CFQPZW_POST_TYPE, 'normal', 'high' );
			add_meta_box( 'cfqpzw-help', __( 'Do you need help for configuration?', 'accept-qpay-payments-using-contact-form-7' ), array( $this, 'cfqpzw_show_help_data' ), CFQPZW_POST_TYPE, 'side', 'high' );
		}

		/**
		 * Action: cfqpzw_wpcf7_save_contact_form
		 *
		 * - Save setting fields data.
		 *
		 * @param object $WPCF7_form
		 */
		public function action__cfqpzw_wpcf7_save_contact_form( $WPCF7_form ) {

			$wpcf7 = WPCF7_ContactForm::get_current();

			if ( !empty( $wpcf7 ) ) {
				$post_id = $wpcf7->id();
			}

			$form_fields = array(
				CFQPZW_META_PREFIX . 'use_qpay',
				CFQPZW_META_PREFIX . 'payment_mode',
				CFQPZW_META_PREFIX . 'qpay_gateway_id',
				CFQPZW_META_PREFIX . 'qpay_secret_key',
				CFQPZW_META_PREFIX . 'order_unique_prefix',
				CFQPZW_META_PREFIX . 'amount',
				CFQPZW_META_PREFIX . 'customer_name',
				CFQPZW_META_PREFIX . 'customer_address',
				CFQPZW_META_PREFIX . 'customer_city',
				CFQPZW_META_PREFIX . 'customer_state',
				CFQPZW_META_PREFIX . 'customer_country',
				CFQPZW_META_PREFIX . 'customer_phone',
				CFQPZW_META_PREFIX . 'customer_email',
				CFQPZW_META_PREFIX . 'quantity',
				CFQPZW_META_PREFIX . 'returnurl',
				CFQPZW_META_PREFIX . 'currency',

			);
			/**
			 * Save custom form setting fields
			 *
			 * @var array $form_fields
			 */

			$form_fields = apply_filters( CFQPZW_PREFIX . 'save_fields', $form_fields );

			if(!get_option('_exceed_cfqpzw_l')){
				add_option('_exceed_cfqpzw_l', 'cfqpzw10');
			}

			if ( !empty( $form_fields ) ) {
				foreach ( $form_fields as $key ) {
					if( isset( $_REQUEST[ $key ] ) ){
						$keyval = sanitize_text_field( $_REQUEST[ $key ] );
						update_post_meta( $post_id, $key, $keyval );
					}else{
						update_post_meta( $post_id, $key, '' );
					}
				}
			}
		}

		/**
		 * Action: manage_data_posts_custom_column
		 *
		 * @method manage_cfqpzw_data_posts_custom_column
		 *
		 * @param  string  $column
		 * @param  int     $post_id
		 *
		 * @return string
		 */
		function action__manage_cfqpzw_data_posts_custom_column( $column, $post_id ) {
			$data_ct = $this->cfqpzw_check_data_ct( sanitize_text_field( $post_id ) );

			switch ( $column ) {

				case 'order_id' :
					if( $data_ct ){
						echo "<a href='".CFQPZW_PRODUCT."' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_order_id', true ) )
							? (
								(
									!empty( CFQPZW()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_order_id', true ), CFQPZW()->lib->response_status)
								)
								? CFQPZW()->lib->response_status[get_post_meta( $post_id , '_order_id', true )]
								: get_post_meta( $post_id , '_order_id', true )
							)
							: ''
						);
					}
				break;

				case 'transaction_id' :
					if( $data_ct ){
						echo "<a href='".CFQPZW_PRODUCT."' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_transaction_id', true ) )
							? (
								(
									!empty( CFQPZW()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_transaction_id', true ), CFQPZW()->lib->response_status)
								)
								? CFQPZW()->lib->response_status[get_post_meta( $post_id , '_transaction_id', true )]
								: get_post_meta( $post_id , '_transaction_id', true )
							)
							: ''
						);
					}
				break;

				case 'transaction_status' :
					if( $data_ct ){
						echo "<a href='".CFQPZW_PRODUCT."' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_transaction_status', true ) )
							? (
								(
									!empty( CFQPZW()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_transaction_status', true ), CFQPZW()->lib->response_status)
								)
								? CFQPZW()->lib->response_status[get_post_meta( $post_id , '_transaction_status', true )]
								: ucfirst(get_post_meta( $post_id , '_transaction_status', true ))
							)
							: ''
						);
					}
				break;

				case 'total' :
				if( $data_ct ){
						echo "<a href='".CFQPZW_PRODUCT."' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo ( !empty( get_post_meta( $post_id , '_total', true ) ) ? get_post_meta( $post_id , '_total', true ) : '' );
					}
				break;

			}
		}

		/**
		 * Action: pre_get_posts
		 *
		 * - Used to perform order by into CPT List.
		 *
		 * @method action__cfqpzw_pre_get_posts
		 *
		 * @param  object $query WP_Query
		 */
		function action__cfqpzw_pre_get_posts( $query ) {

			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( CFQPZW_POST_TYPE ) )
			)
				return;

			$orderby = $query->get( 'orderby' );

			if ( '_total' == $orderby ) {
				$query->set( 'meta_key', '_total' );
				$query->set( 'orderby', 'meta_value_num' );
			}
		}

		/**
		 * Action: restrict_manage_posts
		 *
		 * - Used to creat filter by form and export functionality.
		 *
		 * @method action__cfqpzw_restrict_manage_posts
		 *
		 * @param  string $post_type
		 */
		function action__cfqpzw_restrict_manage_posts( $post_type ) {

			if ( CFQPZW_POST_TYPE != $post_type ) {
				return;
			}

			$posts = get_posts(
				array(
					'post_type'			=> 'wpcf7_contact_form',
					'post_status'		=> 'publish',
					'suppress_filters'	=> false,
					'posts_per_page'	=> -1
				)
			);

			if ( empty( $posts ) ) {
				return;
			}

			$selected = ( isset( $_GET['form-id'] ) ? sanitize_text_field($_GET['form-id']) : '' );

			echo '<select name="form-id" id="form-id">';
			echo '<option value="all">' . __( 'Select Forms', 'accept-qpay-payments-using-contact-form-7' ) . '</option>';
			foreach ( $posts as $post ) {
				echo '<option value="' . $post->ID . '" ' . selected( $selected, $post->ID, false ) . '>' . $post->post_title  . '</option>';
			}
			echo '</select>';

		}

		/**
		 * Action: parse_query
		 *
		 * - Filter data by form id.
		 *
		 * @method action__cfqpzw_parse_query
		 *
		 * @param  object $query WP_Query
		 */
		function action__cfqpzw_parse_query( $query ) {
			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( CFQPZW_POST_TYPE ) )
			)
				return;

			if (
				is_admin()
				&& isset( $_GET['form-id'] )
				&& 'all' != $_GET['form-id']
			) {
				$query->query_vars['meta_value']	= sanitize_text_field($_GET['form-id']);
				$query->query_vars['meta_compare']	= '=';
			}

		}


		/**
		 * Action: CFQPZW_PREFIX /postbox
		 *
		 * - Added metabox for the setting fields in backend.
		 *
		 * @method action__cfqpzw_postbox
		 */
		function action__cfqpzw_postbox() {

			echo '<div id="configuration-help" class="postbox">' .
				apply_filters(
					CFQPZW_PREFIX . '/help/postbox',
					'<h3>' . __( 'Do you need help for configuration?', 'accept-qpay-payments-using-contact-form-7' ) . '</h3>' .
					'<p></p>' .
					'<ol>' .
						'<li><a href="'.CFQPZW_DOCUMENT.'" target="_blank">Refer the document.</a></li>' .
						'<li><a href="'.CFQPZW_SUPPORT.'" target="_blank">Contact Us</a></li>' .
					'</ol>'
				) .
			'</div>';
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
		 * - Used to display the form data in CPT detail page.
		 *
		 * @method cfqpzw_show_from_data
		 *
		 * @param  object $post WP_Post
		 */

		function cfqpzw_show_from_data( $post ) {
			$fields = CFQPZW()->lib->data_fields;
			$form_id = get_post_meta( $post->ID, '_form_id', true );

			$post_type = $post->post_type;
			$data_ct = $this->cfqpzw_check_data_ct( sanitize_text_field( $post->ID ) );
			echo '<table class="cfqpzw-box-data form-table">' .
				'<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

				if ( !empty( $fields ) ) {
					if( $data_ct ){
						echo'<tr class="inside-field"><th scope="row">You are using Free Accept Qpay payments Using Contact form 7 - no license needed. Enjoy! 🙂</th></tr>';
							echo'<tr class="inside-field"><th scope="row"><a href="https://www.zealousweb.com/wordpress-plugins/accept-qpay-payments-using-contact-form-7/" target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
					}else{
						if ( array_key_exists( '_transaction_response', $fields ) ) {
							unset( $fields['_transaction_response'] );
						}

						$attachment = ( !empty( get_post_meta( $post->ID, '_attachment', true ) ) ? unserialize( get_post_meta( $post->ID, '_attachment', true ) ) : '' );
						$root_path = get_home_path();

						foreach ( $fields as $key => $value ) {

							if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key != '_form_data'
								&& $key != '_transaction_response'
								&& $key != '_transaction_status'
							) {

								$val = get_post_meta( $post->ID, $key, true );

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-qpay-payments-using-contact-form-7' ) . '</label>' .
									'</th>' .
									'<td>' .
										(
											(
											'_form_id' == $key
											&& !empty( get_the_title( get_post_meta( $post->ID, $key, true ) ) )
										)
										? get_the_title( get_post_meta( $post->ID, $key, true ) )
										: get_post_meta( $post->ID, $key, true )
									) .
								'</td>' .
							'</tr>';

						} else if(
							!empty( get_post_meta( $post->ID, $key, true ) )
							&& $key == '_transaction_status'
						){
							echo '<tr class="form-field">' .
								'<th scope="row">' .
									'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-qpay-payments-using-contact-form-7' ) . '</label>' .
								'</th>' .
								'<td>' .
									(
										(
											!empty( CFQPZW()->lib->response_status )
											&& array_key_exists( get_post_meta( $post->ID , $key, true ), CFQPZW()->lib->response_status )
										)
										? CFQPZW()->lib->response_status[get_post_meta( $post->ID , $key, true )]
										: ucfirst(get_post_meta( $post->ID , $key, true ))
									) .
								'</td>' .
							'</tr>';
						} else if (
							!empty( get_post_meta( $post->ID, $key, true ) )
							&& $key == '_transaction_status'
						) {

							echo '<tr class="form-field">' .
								'<th scope="row">' .
									'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-qpay-payments-using-contact-form-7' ) . '</label>' .
								'</th>' .
								'<td>' .
									(
										(
											!empty( CFQPZW()->lib->response_status )
											&& array_key_exists( get_post_meta( $post->ID , $key, true ), CFQPZW()->lib->response_status )
										)
										? CFQPZW()->lib->response_status[get_post_meta( $post->ID , $key, true )]
										: get_post_meta( $post->ID , $key, true )
									) .
								'</td>' .
							'</tr>';

						} else if (
							!empty( get_post_meta( $post->ID, $key, true ) )
							&& $key == '_form_data'
						) {

							echo '<tr class="form-field">' .
								'<th scope="row">' .
									'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-qpay-payments-using-contact-form-7' ) . '</label>' .
								'</th>' .
								'<td>' .
									'<table>';

										$data = unserialize( get_post_meta( $post->ID, $key, true ) );

										$hide_data = apply_filters( CFQPZW_PREFIX . '/hide-display', array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' ) );
										foreach ( $hide_data as $key => $value ) {
											if ( array_key_exists( $value, $data ) ) {
												unset( $data[$value] );
											}
										}

										if ( !empty( $data ) ) {
											foreach ( $data as $key => $value ) {
												if ( strpos( $key, 'two_checkout-' ) === false ) {
													echo '<tr class="inside-field">' .
														'<th scope="row">' .
															__( sprintf( '%s', $key ), 'accept-qpay-payments-using-contact-form-7' ) .
														'</th>' .
														'<td>' .
															(
																(
																	!empty( $attachment )
																	&& array_key_exists( $key, $attachment )
																)
																? '<a href="' . esc_url( home_url( str_replace( $root_path, '/', $attachment[$key] ) ) ) . '" target="_blank" download>' . __( substr($attachment[$key], strrpos($attachment[$key], '/') + 1), 'accept-qpay-payments-using-contact-form-7' ) . '</a>'
																: __( sprintf( '%s', ( is_array($value) ? implode( ', ', $value ) :  $value ) ), 'accept-qpay-payments-using-contact-form-7' )
															) .
														'</td>' .
													'</tr>';
												}
											}
										}

									echo '</table>' .
								'</td>
							</tr>';

						} else if (
							!empty( get_post_meta( $post->ID, $key, true ) )
							&& $key == '_transaction_response'
						) {

							echo '<tr class="form-field">' .
								'<th scope="row">' .
									'<label for="hcf_author">' . __( sprintf( '%s', $value ), 'accept-qpay-payments-using-contact-form-7' ) . '</label>' .
								'</th>' .
								'<td>' .
									'<code style="word-break: break-all;">' .
										(
											get_post_meta( $post->ID , $key, true )
										) .
									'</code>' .
								'</td>' .
							'</tr>';
						}
					}

					}
				}

			echo '</table>';
		}

		/**
		* check data ct
		*/
		function cfqpzw_check_data_ct( $post_id ){

			$data = unserialize( get_post_meta( $post_id, '_form_data', true ) );
			if( !empty( get_post_meta( $post_id, '_form_data', true ) ) && isset( $data['_exceed_num_cfqpzw'] ) && !empty( $data['_exceed_num_cfqpzw'] ) ){
				return $data['_exceed_num_cfqpzw'];
			}else{
				return '';
			}

		}

		/**
		 * Action: review done
		 *
		 * - Review done.
		 *
		 * @method action__cfqpzw_review_done
		 */
		function action__cfqpzw_review_done(){
			if( isset( $_POST['value'] ) && $_POST['value'] == 1 ){
				add_option( 'cfqpzw_review', "1" );
			}
		}

		/**
		 * - Used to add meta box in CPT detail page.
		 */
		function cfqpzw_show_help_data() {
			echo '<div id="cfqpzw-data-help">' .
				apply_filters(
					CFQPZW_PREFIX . '/help/'.CFQPZW_POST_TYPE.'/postbox',
					'<ol>' .
						'<li><a href="'.CFQPZW_DOCUMENT.'" target="_blank">Refer the document.</a></li>' .
						'<li><a href="'.CFQPZW_SUPPORT.'" target="_blank">Contact Us</a></li>' .
					'</ol>'
				) .
			'</div>';
		}

	}
	add_action( 'plugins_loaded', function() {
		CFQPZW()->admin = new CFQPZW_Admin_Action;
	} );
}
