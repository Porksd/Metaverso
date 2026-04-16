/*
* Bulk purchase product purchase js.
*/
(function( $ ) {
	'use strict';
	/*$( '#woocommerce-order-items' ).on( 'change', 'input#wi_unenrol', function(){
		// var r;
		// if ($(this).is(':checked')) {
		// 	r = confirm('Are you sure?');
		// }
		// console.log(r);
		// console.log('yeh');
		$('.refund-actions button').prop('disabled', true);
		$.post(
			ajaxurl,
			{
				'action': 	'unenrol_check_status',
				'unenrol':  $('#wi_unenrol:checked').length ? 'checked' : '',
				'security': $('#wi_refund_unenrol').val(),
				'order_id': $('#wi_order_id').val(),
			},
			function(response){
				$('.refund-actions button').prop('disabled', false);
			}
		);
	} );
	*/
	$( '#woocommerce-order-items' ).on(
		'click',
		'button.refund-items',
		function(){
			if ($( '.bp-refund-wrapper' ).length === 0) {
				$( '.refund-actions' ).before( bpRefund.html );
			}
			/*$.post(
			ajaxurl,
			{
				'action': 	'bp-refund-content',
			},
			function(response){
				//console.log(response.data.display);
				if (response.data.display === 'true') {
					$('.wi-refund-wrapper').show();
				} else {
					$('.wi-refund-wrapper').hide();
				}
			}
			);*/
		}
	);

	$( '#woocommerce-order-items' ).on(
		'click',
		'.refund-actions .do-manual-refund',
		function(){
			var objParam            = {};
			objParam["action"]      = "bp_save_refund_data";
			objParam["refund-type"] = $( 'select[name="bp-refund-type"]' ).val();
			objParam["full-refund"] = "";
			objParam["order-id"]    = $( "#bp_order_id" ).val();
			objParam["nonce"]       = ebbpRefund.nonce_refund;

			// If else to set checkbox value.
			if ($( 'input[name="bp-full-refund-check"]' ).prop( "checked" ) == true) {
				objParam["full-refund"] = "on";
			} else if ($( 'input[name="bp-full-refund-check"]' ).prop( "checked" ) == false) {
				objParam["full-refund"] = "off";
			}

			$( ".bp-partial-refund-fields" ).each(
				function(index) {
					objParam[$( this ).attr( "name" )] = $( this ).val();
				}
			);

			$.post(
				ajaxurl,
				objParam,
				function(response){

				}
			);
		}
	);

	$( '#woocommerce-order-items' ).on(
		"change",
		"select[name='bp-refund-type']",
		function(){
			if ("bp-partial-refund" == $( this ).val()) {
				$( ".bp-refund-qty-wrapper" ).css( "display", "table-row" );
				$( ".bp-refund-checkbox-wrapper" ).css( "display", "none" );
			} else if ("bp-full-refund" == $( this ).val()) {
				$( ".bp-refund-qty-wrapper" ).css( "display", "none" );
				$( ".bp-refund-checkbox-wrapper" ).css( "display", "table-row" );
			} else {
				$( ".bp-refund-qty-wrapper" ).css( "display", "none" );
				$( ".bp-refund-checkbox-wrapper" ).css( "display", "none" );
			}
		}
	);

	/**
	 * ---------------------------------
	 * Group refund quantity validations
	 * ---------------------------------
	 */

	// Functionality to validate input field for the max and min quantity.
	$( document ).on(
		'input',
		'.bp-partial-refund-fields',
		function () {
			var maxQty     = $( this ).data( "availqty" );
			var toolTipDiv = $( this ).parent();
			toolTipDiv     = toolTipDiv.find( ".eb-tooltiptext" );
			if ($( this ).val() > maxQty) {
				toolTipDiv.css( "visibility", "visible" );
				$( this ).val( "" );
			}
		}
	);

	// This lets enter only 1-9 numbers in input box and not enything else.
	$( document ).on(
		'keypress',
		'.bp-partial-refund-fields',
		function(e) {
			if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
				// Display error message.
				$( "#errmsg" ).html( "Digits Only" ).show().fadeOut( "slow" );
				return false;
			}
		}
	);

	// This removes the tool tip shown on the edwiser refund section.
	$( document ).on(
		'focusout',
		'.bp-partial-refund-fields',
		function () {
			var toolTipDiv = $( this ).parent();
			toolTipDiv     = toolTipDiv.find( ".eb-tooltiptext" );
			toolTipDiv.css( "visibility", "hidden" );
		}
	);

	/************************************/

})( jQuery );
