( function($) {
	"use strict";

	function check_qpay_field_validation(){

		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );

		if(
			jQuery( '.cfqpzw-settings #cfqpzw_qpay_gateway_id' ).val() == '' ||
			jQuery( '.cfqpzw-settings #cfqpzw_qpay_secret_key').val() == ''
		){
			jQuery("#qpay-add-on-tab .ui-tabs-anchor").find('span').remove();
			jQuery("#qpay-add-on-tab .ui-tabs-anchor").append('<span class="icon-in-circle" aria-hidden="true">!</span>');
		}else{
			jQuery("#qpay-add-on-tab .ui-tabs-anchor").find('span').remove();
		}

		if( jQuery( '.cfqpzw-settings #cfqpzw_use_qpay' ).prop( 'checked' ) == true ){
			jQuery('.cfqpzw-settings .cfqpzw-required-fields').each(function() {
				if ( jQuery.trim( jQuery(this).val() ) == '' ) {
				   jQuery("#qpay-add-on-tab .ui-tabs-anchor").find('span').remove();
				   jQuery("#qpay-add-on-tab .ui-tabs-anchor").append('<span class="icon-in-circle" aria-hidden="true">!</span>');
				}
			});

		}else{
			jQuery("#qpay-add-on-tab .ui-tabs-anchor").find('span').remove();
		}
		cfqpzw_validate();
	}

	function cfqpzw_cred_validate() {
		if ( jQuery( '.cfqpzw-settings #cfqpzw_payment_mode' ).val() == 'sandbox' && jQuery( '.cfqpzw-settings #cfqpzw_use_qpay' ).prop( 'checked' ) == true) {
			jQuery( '.cfqpzw-settings #cfqpzw_qpay_gateway_id, .cfqpzw-settings #cfqpzw_qpay_secret_key' ).prop( 'required', true );
		} else {
			jQuery( '.cfqpzw-settings #cfqpzw_qpay_gateway_id, .cfqpzw-settings #cfqpzw_qpay_secret_key' ).removeAttr( 'required' );
		}
	}

	function cfqpzw_validate() {
		if ( jQuery( '.cfqpzw-settings #cfqpzw_use_qpay' ).prop( 'checked' ) == true ) {
			jQuery('.cfqpzw-settings .cfqpzw-required-fields').each(function() {
				jQuery( jQuery(this) ).prop( 'required', true );
			});
		} else {
			jQuery('.cfqpzw-settings .cfqpzw-required-fields').each(function() {
				jQuery( jQuery(this) ).removeAttr( 'required' );
			});
		}
	}

	/**
	 * Validate QPay admin option required fields
	 */
	jQuery( document ).ready( function() {

		if(cfqpzw_object.translate_string_cfqpzw.cfqpzw_review != 1){
			if (typeof Cookies.get('review_cfqpzw') === 'undefined'){ // no cookie
				jQuery('#myModal').modal('show');
				Cookies.set('review_cfqpzw', 'yes', { expires: 15 }); // set cookie expiry to 1 day
			}
		}

		jQuery(".review-cfqpzw, .remind-cfqpzw").click(function(){
            jQuery("#myModal").modal('hide');
        });

		jQuery(".review-cfqpzw").click(function(){
			jQuery.ajax({
			    type: "post",
			    dataType: "json",
			    url: cfqpzw_object.ajax_url,
			    data: 'action=cfqpzw_review_done&value=1',
			    success: function(){
			    }
			});
		});

		check_qpay_field_validation()
	});
	jQuery( document ).on('click',".ui-state-default",function() {
		check_qpay_field_validation()
	});

	/**
	 * Order Prefix Validation
	 */
	jQuery("#cfqpzw_order_unique_prefix").keypress(function (e) {
		var regex = new RegExp("^[a-zA-Z0-9]+$");
	    var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
	    if (!regex.test(key)) {
	       event.preventDefault();
	       return false;
	    }
    });

	jQuery('#cfqpzw_order_unique_prefix').bind('copy paste', function (e) {
       e.preventDefault();
    });

	if ( jQuery( '.cfqpzw-settings #cfqpzw_amount' ).val() == '' && jQuery( '.cfqpzw-settings #cfqpzw_use_qpay' ).prop( 'checked' ) == true ) {
		cfqpzw_validate();
	}

	if ( jQuery( '.cfqpzw-settings #cfqpzw_payment_mode' ).val() != '' && jQuery( '.cfqpzw-settings #cfqpzw_use_qpay' ).prop( 'checked' ) == true ) {
		cfqpzw_cred_validate();
	}

	/**
	 * Remove Conatct from 7 if plugin required field is there.
	 */
	jQuery(document).on('click','input[name="wpcf7-delete"]',function(){
		jQuery('.cfqpzw-settings #cfqpzw_qpay_gateway_id,.cfqpzw-settings #cfqpzw_qpay_secret_key').removeAttr( 'required' );

		jQuery('.cfqpzw-settings .cfqpzw-required-fields').each(function() {
			jQuery( jQuery(this) ).removeAttr( 'required' );
		});
	});

	/**
	 * Apply select2 dunctionality for dropdown box
	 */
	jQuery( document ).ready( function() {
		jQuery('.cfqpzw-settings #cfqpzw_payment_mode, .cfqpzw-settings #cfqpzw_currency, .cfqpzw-settings #cfqpzw_returnurl, .cfqpzw-settings #cfqpzw_customer_postal_code, .cfqpzw-settings #cfqpzw_quantity').select2();

		jQuery('.cfqpzw-settings .cfqpzw-required-fields').each(function() {
			jQuery( jQuery(this) ).select2();
		});
	});
	/**
	 * Validate QPay admin option whene plugin functionality enabled for particular form
	 */
	jQuery( document ).on( 'change', '.cfqpzw-settings .enable_required', function() {
		cfqpzw_validate();
		cfqpzw_cred_validate();
	});

	jQuery( '#cfqpzw-gatewayid' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-gatewayid' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.gatewayid,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-secretkey' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-secretkey' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.secret_key,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-amount' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-amount' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.amount,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-quantity' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-quantity' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.quantity,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-select-currency' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-select-currency' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.currency,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-prefix' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-prefix' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.orderid_prefix,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-success-returnurl' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-success-returnurl' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.success_url,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-name' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-name' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_name,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-address' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-address' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_address,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-city' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-city' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_city,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-state' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-state' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_state,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-country' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-country' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_country,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-phone' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-phone' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_phone,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-customer-email' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-customer-email' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_email,
			position: 'left center',
		} ).pointer('open');
	} );

	jQuery( '#cfqpzw-email' ).on( 'mouseenter click', function() {
		jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
		jQuery( '#cfqpzw-email' ).pointer({
			pointerClass: 'wp-pointer cfqpzw-pointer',
			content: cfqpzw_object.translate_string_cfqpzw.customer_email,
			position: 'left center',
		} ).pointer('open');
	} );

} )( jQuery );
