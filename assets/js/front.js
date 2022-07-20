jQuery( document ).ready( function( $ ) {

	if (jQuery('body').find(".qpay-country").length > 0){
		jQuery('.qpay-country').select2();
	}
	document.addEventListener('wpcf7mailsent', function( event ) {
		setTimeout(function(){
			var contactform_id = event.detail.contactFormId;
			var formdata = event.detail.apiResponse.redirect_form;
			document.getElementById(event.detail.unitTag).innerHTML += formdata;
			document.getElementById("qpay-payment-form-"+event.detail.contactFormId).submit();
		}, 1000);

	} );

} );
