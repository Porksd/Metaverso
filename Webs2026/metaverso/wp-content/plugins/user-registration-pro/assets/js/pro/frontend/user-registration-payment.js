/* global  user_registration_params */
/**
 * Script to handle total field in frontend.
 *
 * @since 1.2.0
 */
jQuery(function ($) {
	var ursL10n = user_registration_params.ursL10n;
	$(".ur-frontend-form").each(function () {
		var $form = $(this);
		var totalAmountField = {
			init: function () {
				$(document).ready(totalAmountField.ready);
				totalAmountField.bindUIActions();
			},
			/*
			 * load total amount on form load
			 */
			ready: function () {
				totalAmountField.loadTotal();
			},
			/*
			 * Update Total field with latest calculation.
			 */
			loadTotal: function () {
				$(".ur-frontend-form")
					.find(".ur-total-amount")
					.each(function () {
						totalAmountField.calculateTotalAmount(this);
					});
			},
			/*
			 * Payments: Update Total field(s) when latest calculation.
			 */
			bindUIActions: function () {
				var $paymentSingles = $form.find(
					".field-single_item:not([style*='display: none'])"
				);
				$(document).on("change input", $paymentSingles, function () {
					totalAmountField.calculateTotalAmount(this, true);
				});
				// Restrict user input payment fields
				$(document).on(
					"input keypress",
					".ur-payment-price",
					function (evt) {
						var $this = $(this),
							amount = $this.val();
						$this.val(amount.toString().replace(/[^0-9.]/g, ""));
					}
				);


				// Disallow Quantity field value to be empty which causes NaN in Total field.
				$(document).on( 'input keypress change', 'input.ur-quantity', function() {
					if ( ! $(this).val() ) {
						$(this).val( 0 );
					}
				});
			},
			/*
			 * calculate total amount
			 */
			calculateTotalAmount: function () {
				var total = 0;
				var $paymentSingles = $form.find(
					".field-single_item:not([style*='display: none'])"
				);
				// calculate single item amount
				var paymentSingles = $.map(
					$paymentSingles,
					function (paymentSingle) {
						var price =
							"" !== $(paymentSingle).find("input").val()
								? $(paymentSingle).find("input").val()
								: 0;
						var fieldId = $(paymentSingle).find("input").attr("data-id");

						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity( fieldId );
					}
				);
				// calculate range amount as payment slider
				var isPaymentSlider = $form
					.find(".field-range:not([style*='display: none'])")
					.find(".ur-currency-sign").length;
				if (isPaymentSlider > 0) {
					$rangeSlider = $form
						.find(".field-range:not([style*='display: none'])")
						.find(".ur-currency-sign")
						.parent()
						.parent();
					var paymentSlider = $.map($rangeSlider, function (range) {
						var price =
							"" !== $(range).find("input[type='number']").val()
								? $(range).find("input[type='number']").val()
								: 0;
						var fieldId = $(range).find("input[type='number']").attr('name');
						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity( fieldId );
					});
				}
				// calculate multiple choice payment amount
				var $paymentMultipleChoice = $form
					.find(
						".field-multiple_choice:not([style*='display: none'])"
					)
					.find("input");

				var paymentMultipleChoice = $.map(
					$paymentMultipleChoice,
					function (paymentchoice) {
						if ("checkbox" === $(paymentchoice).attr("type")) {
							var price = $(paymentchoice).prop("checked")
								? $(paymentchoice).val()
								: 0;
						}

						var fieldId = $(paymentchoice).attr("data-id");

						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity( fieldId );
					}
				);
				// render total calculated amount in total field
				$form.find(".ur-total-amount").each(function () {
					if (
						"hidden" === $(this).attr("type") ||
						"text" === $(this).attr("type")
					) {
						$(this).val(total);
					} else {
						$(this).text(total);
					}
				});
				return total;
			},

			/**
			 * Get quantity for field.
			 *
			 * @param {string} id
			 */
			getQuantity: function( id ) {
				var quantity = 1;
				var quantityField = $("input[data-target=" + id);
				if ( quantityField.length != 0) {
					quantity = quantityField.first().val();
				}
				return parseInt( quantity );
			},
		};

		totalAmountField.init(jQuery);
	});

	$(document).ready(function(){

		$(document).on("user_registration_frontend_multiple_choice_data_filter", function(event, field_value, field){
				var checkedValues = [];

				field.each(function () {
					if ($(this).is(":checked")) {
						var label = $(this)
							.siblings("label")
							.text();
						var value = $(this).val();
						checkedValues.push(
							label + ":" + value
						);
					}
				});
				field_value = checkedValues;
				field.closest(".field-multiple_choice").data("payment-value", field_value);
			});
	});
});
