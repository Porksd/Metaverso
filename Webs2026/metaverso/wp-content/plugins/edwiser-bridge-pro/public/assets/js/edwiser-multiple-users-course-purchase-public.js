/*
* Bulk purchase product purchase css
*/
(function ($) {
	var dlgOverlyOpct  = 0.8;
	var dlgOverlyColor = "black";
	jQuery( document ).ready(
		function () {
			var div = '<div id="eb-lading-parent" class="eb-lading-parent-wrap"><div class="eb-loader-progsessing-anim"></div></div>';
			$( "body" ).append( div );
			div = '<div class="eb-background-div"></div>';

			/**
			 * Variable product support.
			 */
			$( ".single_variation_wrap" ).on(
				"show_variation",
				function (event, variation) {
					// Fired when the user selects all the required dropdowns / attributes.
					// and a final variation is selected / shown.

					$( '.wdm_edwiser_bulk_purchase' ).css( 'display', 'none' );

					if ($( '#bp_enable_group_purchase_' + [variation.variation_id] ).length) {
						$( '#bp_enable_group_purchase_' + [variation.variation_id] ).show();
					}/* else {
					$('.wdm_edwiser_bulk_purchase').hide();
					}*/

				}
			);

			/**
			 * Events to dismiss the responce messages on the enrolle student page.
			 */
			$( document ).on(
				'click',
				'.wdm_enroll_warning_msg_dismiss',
				function () {
					$( this ).parent().remove();
					// $('.wdm_user_list').remove();
					$( '#wdm_eb_message_divider' ).remove();
					// $('#wdm_eb_enroll_user_page').css("border", "0px solid white");
				}
			);
			$( document ).on(
				'click',
				'.wdm_error_msg_dismiss',
				function () {
					$( this ).parent().remove();
					// $('.wdm_user_list').remove();
					$( '#wdm_eb_message_divider' ).remove();
					// $('#wdm_eb_enroll_user_page').css("border", "0px solid white");
				}
			);
			$( document ).on(
				'click',
				'.wdm_success_msg_dismiss',
				function () {
					$( this ).parent().remove();
					// $('.wdm_user_list').remove();
					$( '#wdm_eb_message_divider' ).remove();
					// $('#wdm_eb_enroll_user_page').css("border", "0px solid white");
				}
			);

			/**
			 * removing error msg
		 *
			 * @since 1.1.0
			 */

			$( document ).on(
				'click',
				'.wdm_success_msg_dismiss, .wdm_select_course_msg_dismiss',
				function () {
					$( '.wdm_select_course_msg' ).css( "display", "none" );
					$( '#wdm_eb_message_divider' ).remove();
				}
			);

			$( document ).on(
				'click',
				'.wdm_enroll_warning_msg_dismiss',
				function () {
					$( '.wdm_enroll_warning_message' ).css( "display", "none" );
					$( '#wdm_eb_message_divider' ).remove();
				}
			);

			$( document ).on(
				'click',
				'.wdm_success_msg_dismiss',
				function () {
					$( '.wdm_success_message' ).css( "display", "none" );
					$( '#wdm_eb_message_divider' ).remove();
				}
			);

			$( document ).on(
				'click',
				'.wdm_grp_update_msg_dismiss',
				function () {
					$( this ).parent().css( "display", "none" );
				}
			);

			$( "body" ).append( div );
			// Added this in the enroll students js.
			/*$('#wdm_eb_upload_csv').hide();
			$('#wdm_avaliable_reg').hide();
			$('#enroll-new-user-btn-div').hide();
			$("#wdm_associated_courses_container").hide();*/
			var seat_limit = 0;

			$( document ).on(
				"change",
				"#mucp-cart-group-checkbox",
				function () {
					if ($( this ).prop( "checked" ) == true) {
						processGroupPurchaseCheckbox( 1 );
					} else if ($( this ).prop( "checked" ) == false) {
						processGroupPurchaseCheckbox( 0 );
					}
				}
			);

			function isItemQtyEql() {
				var allQty = [];
				$( '.qty' ).each(
					function () {
						allQty.push( $( this ).val() );
					}
				)
				var max    = allQty[0];
				for (var cnt = 1; cnt < allQty.length; cnt++) {
					if (max != allQty[cnt]) {
						return false;
					}
				}
				return true;
			}

			/**
			 * This will check is the bulk purchase option is present on screen
			 * if the checkbox is present on the screen then disable the quantity box.
			 */
			if ($( "#wdm_edwiser_self_enroll" ).length) {
				var ischecked = $( this ).is( ':checked' );
				if ( ! ischecked) {
					$( ".qty" ).prop( 'disabled', true );
					$( ".qty" ).attr( "value", "1" );
				} else {
					$( ".qty" ).prop( 'disabled', false );
				}
			}

			/**
			 * Handle the group purchase enable disable events
			 * If the checkbox is enabled then enable the product quantity box
			 * Disable otherwise
			 */
			// add event listner for each checkbox
			$( "input[name='wdm_edwiser_self_enroll']" ).change(
				function () {
					var ischecked = $( this ).is( ':checked' );
					if ( ! ischecked) {
						$( ".qty" ).prop( 'disabled', true );
						$( ".qty" ).attr( "value", "1" );
					} else {
						$( ".qty" ).prop( 'disabled', false );
					}
				}
			);

			function processGroupPurchaseCheckbox(isChecked) {
				jQuery.ajax(
					{
						type: 'POST',
						url: ebbpPublic.ajax_url,
						dataType: 'json',
						data: {
							action: 'check_for_different_products',
							single_group: isChecked,
						},
						success: function (response) {
							if (response.success == true) {
								if (response.data.status) {
									$( ".wdm-diff-prod-qty-error" ).addClass( "wdm-hide" );
									$( ".wdm-diff-prod-qty-success" ).removeClass( "wdm-hide" );
									$( "#wdm-diff-prod-qty-success-msg" ).html( response.data.msg );
								} else {
									$( ".wdm-diff-prod-qty-error" ).removeClass( "wdm-hide" );
									$( ".wdm-diff-prod-qty-success" ).addClass( "wdm-hide" );
									$( "#wdm-diff-prod-qty-error-msg" ).html( response.data.msg );
								}
							} else {
								$( ".wdm-diff-prod-qty-error" ).removeClass( "wdm-hide" );
								$( ".wdm-diff-prod-qty-success" ).addClass( "wdm-hide" );
								$( "#wdm-diff-prod-qty-error-msg" ).html( response.data );
								$( "#mucp-cart-group-checkbox" ).prop( "checked", false );
							}
						},
						error: function (error) {
							errorMsgDialog( error );
						}
					}
				);

			}

			// v1.1.1.
			/**
			 * Show Error message.
			 */
			function errorMsgDialog(msg) {
				var error = $( document.createElement( 'div' ) );
				msg       = '<div class="ui-state-error ui-corner-all"><i class="dashicons dashicons-warning" aria-hidden="true"></i><p style = "margin-top:5%;">' + msg + '</p></div>';
				error.append( msg );
				error.dialog(
					{
						title: "Error",
						autoOpen: false,
						modal: true,
						resizable: false,
						dialogClass: "wdm-error-message-dialog",
						buttons: [
						{
							text: ebbpPublic.ok,
							class: 'wdm-dialog-ok-button',
							click: function () {
								closeDialog( this );
							}
						},
						],
						open: function (event, ui) {
							$( ".ui-widget-overlay" ).css(
								{
									opacity: dlgOverlyOpct,
									backgroundColor: dlgOverlyColor
								}
							);
						},
						create: function (event, ui) {
							$( event.target ).parent().css( 'position', 'fixed' );
						},
					}
				).dialog( "open" );
			}

			/**
			 *  Handling change on input of Add Quantity Popup
			 */
			$( document ).on(
				'keypress click input',
				'input.add-more-quantity',
				function (event) {
					if (event.which == 45 || event.which == 46 || event.which == 189) {
						event.preventDefault();
					}
					var grand_total = 0;
					var quantity    = $( this ).val();
					if (quantity == "") {
						quantity = 0;
					}
					var productId       = $( this ).attr( "id" );
					var minQuantity     = $( "#" + productId + "-min-quantity" ).html();
					var perProductPrice = $( "#" + productId + "-per-product-price" ).html();
					$( "#" + productId + "-total-quantity" ).html( parseInt( minQuantity ) + parseInt( quantity ) );
					$( "#" + productId + "-total-price" ).html( parseFloat( perProductPrice ) * parseInt( quantity ) );
					var totals = jQuery( '.wdm-quantity-total' );
					totals.each(
						function (index, value) {
							grand_total += parseFloat( $( this ).html() );
						}
					);
					$( '#add-quantity-total-price' ).html( grand_total );
				}
			);

			/**
			 * Functionality to calculate the total price per product
			 * in the add more product/quantity popup
			 */
			$(document).ready(function () {

				function calculateTotalPrice(targetElement, isNewProd = false) {
					var wdmId = $(targetElement).attr('id');
					var priceId;
					var totalPriceId = wdmId + "-total-price";
					var quantityElement = $("#" + wdmId);
					var priceElement;
					var calculate = true;

					if (isNewProd) {
						var selectedProd = wdmId + "-wdm-sele-prod";
						if ($("#" + selectedProd).prop('checked') !== true) {
							calculate = false;
						}
						priceId = wdmId + "-per-product-price";
					} else {
						priceId = wdmId + "-per-product-price";
					}
					priceElement = $("#" + priceId);

					if (calculate && quantityElement.length && priceElement.length && totalPriceElement.length && !isNaN(parseFloat(quantityElement.val())) && !isNaN(parseFloat(priceElement.text()))) {
						var quantity = parseFloat(quantityElement.val());
						var price = parseFloat(priceElement.text());
						totalPriceElement.html((quantity * price).toFixed(2));
					}
				}

				// Initial calculation for existing elements
				$('.wdm_new_qty_per_prod, .wdm_new_qty_per_new_prod').each(function () {
					calculateTotalPrice(this, $(this).hasClass('wdm_new_qty_per_new_prod'));
				});

				// Set up Mutation Observer
				const targetNode = document.body; // Or a more specific parent element
				const config = {
					subtree: true,
					childList: true,
					attributes: true,
					attributeFilter: ['class']
				};

				const observer = new MutationObserver(function (mutationsList, observer) {
					for (const mutation of mutationsList) {
						if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
							mutation.addedNodes.forEach(node => {
								if (node.classList) {
									if (node.classList.contains('wdm_new_qty_per_prod')) {
										calculateTotalPrice(node);
									} else if (node.classList.contains('wdm_new_qty_per_new_prod')) {
										calculateTotalPrice(node, true);
									}
								}
								if (node.querySelectorAll) {
									node.querySelectorAll('.wdm_new_qty_per_prod').forEach(function () {
										calculateTotalPrice(this);
									});
									node.querySelectorAll('.wdm_new_qty_per_new_prod').forEach(function () {
										calculateTotalPrice(this, true);
									});
								}
							});
						} else if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
							if (mutation.target.classList.contains('wdm_new_qty_per_prod')) {
								calculateTotalPrice(mutation.target);
							} else if (mutation.target.classList.contains('wdm_new_qty_per_new_prod')) {
								calculateTotalPrice(mutation.target, true);
							}
						}
					}
				});
				observer.observe(targetNode, config);

				// Event listener for direct changes.
				$(document).on('change', '.wdm_new_qty_per_prod, .wdm_new_qty_per_new_prod', function (event) {
					calculateTotalPrice(this, $(this).hasClass('wdm_new_qty_per_new_prod'));
				});
			});

			// v1.1.1.
			/**
			 *  Handling change on input of Add Product Popup
			 */
			$( document ).on(
				'keypress click input',
				'input.add-more-product,.wdm_selected_products',
				function (event) {
					if (event.which == 45 || event.which == 46 || event.which == 189) {
						event.preventDefault();
					}
					var minVal      = $( this ).attr( 'min' );
					var grand_total = 0;
					var quantity    = $( this ).val();
					if (quantity == "" || quantity < minVal) {
						quantity = minVal;
					}
					var selectedProducts = jQuery( ".wdm_selected_products" );
					var productId        = $( this ).attr( "id" );
					var perProductPrice  = $( "#" + productId + "-per-product-price" ).html();
					var total            = parseInt( perProductPrice ) * parseInt( quantity );
					$( "#" + productId + "-total-price" ).html( total );
					var totals = jQuery( '.wdm-product-total' );
					totals.each(
						function (index, value) {
							if (jQuery( selectedProducts[index] ).is( ':checked' )) {
								grand_total += parseInt( $( this ).html() );
							}
						}
					);
					$( '#add-product-total-price' ).html( grand_total );
				}
			);

			$( document ).on(
				'keypress',
				'#add-quantity-inp',
				function (event) {
					if (event.which == 45 || event.which == 189) {
						event.preventDefault();
					}

				}
			);

			function closeDialog(dialogObj) {
				$( dialogObj ).dialog( 'destroy' );
			}

			/**
			 * create fields for new user.
			 */
			jQuery( '#wdm_user_data' ).delegate(
				'#btn_add_new',
				'click',
				function () {
					var numItems = jQuery( '#wdm_enroll_fname' ).length;
					if (numItems < seat_limit) {
						jQuery( '.wdm_button' ).before(
							"<ul class='wdm_new_user'>\n\
                                        <li>\n\
                                            <i class='dashicons dashicons-dismiss wdm_remove_user'></i>\n\
                                        </li>\n\
                                        <li>\n\
                                                <label for='lbl_first_name'>" + ebbpPublic.enterFirstName + "</label>\n\
                                            <input type=text class='txt_fname' name='firstname[]' required>\n\
                                        </li>\n\
                                        <li>\n\
                                                <label class='lbl_last_name'>" + ebbpPublic.enterLastName + "</label>\n\
                                            <input type=text class='txt_lname' name='lastname[]' required>\n\
                                        </li>\n\
                                        <li>\n\
                                                <label class='lbl_email'>" + ebbpPublic.enterEmailName + "</label>\n\
                                            <input type='email' class='txt_email' name='email[]' ' required>\n\
                                        </li>\n\
                                            </ul>"
						);
						jQuery( '#btn_enroll' ).removeAttr( 'disabled' );
						$( this ).toggleClass( "active" );
					}
					if (numItems + 1 == seat_limit) {
						jQuery( this ).attr( 'disabled', 'disabled' );
					}
				}
			);

			$( document ).on(
				'click',
				'input',
				'.txt_fname, .txt_lname, .txt_email',
				function (event) {
					jQuery( this ).css( 'border', 'solid 1px #000' );
				}
			);

			/**
			 * Remove extra user fields.
			 */
			jQuery( '#enroll-user-form-csv' ).delegate(
				'.wdm_remove_user',
				'click',
				function () {
					jQuery( this ).parent().parent().remove();

					if (jQuery( '.wdm_remove_user' ).length <= 0) {
						jQuery( '#enroll-user-form-csv' ).dialog( 'close' );
						jQuery( '#btn_enroll' ).attr( 'disabled', 'disabled' );
					}
					jQuery( "#btn_add_new" ).removeAttr( 'disabled' );

				}
			);

			$( document ).on(
				'click',
				'.ebbp_course_progress',
				function () {
					// loader.
					// $("body").css("cursor", "progress");
					$( '#eb-lading-parent' ).css( 'display', 'block' );

					var cohortId = $( this ).data( 'cohortid' );
					var userId   = $( this ).data( 'userid' );

					var courseProgress = {
						resizable: false,
						title: ebbpPublic.courseprogres,
						autoOpen: false,
						modal: true,
						width: 550,
						dialogClass: "ebbp_custom_field_dialog wdm-enroll-stud-page-dialog",
						buttons: [
						{
							text: ebbpPublic.close,
							class: "wdm-dialog-cancel-button",
							click: function () {
								$( this ).dialog( "close" );
							}
						}
						],
					};

					$.ajax(
						{
							type: 'POST',
							url: ebbpPublic.ajax_url,
							dataType: 'json',
							data: {
								action: 'get_cohort_course_progress',
								cohort_id: cohortId,
								user_id: userId,
								nonce: ebbpPublic.nonce_gp_mng
							},
							success: function (response) {

								// $("body").css("cursor", "default");
								$( '#eb-lading-parent' ).css( 'display', 'none' );

								if (response.success == true) {

									/*$("#add-quantity-popup").empty();
									$("#add-quantity-popup").html(response.data);

									$("#add-quantity-table").DataTable({
									searching: false,
									ordering: false,
									lengthChange: false,
									paging : false
									});

									$('#add-quantity-popup').prop('title', ebbpPublic.addQuantity);
									$('.eb-background-div').show();
									$("#add-quantity-popup").dialog(addQuantity).dialog("open");*/

									$( response.data ).dialog( courseProgress ).dialog( "open" );

								}
								// else {

								// errorMsgDialog(response.data);

								// }
								// jQuery("#eb-lading-parent").hide();
							},
							error: function (response) {

								// $("body").css("cursor", "default");
								$( '#eb-lading-parent' ).css( 'display', 'none' );

								// errorMsgDialog(response);
								// jQuery("#eb-lading-parent").hide();
							}
						}
					);

				}
			);

		}
	);

})( jQuery );

/*
 * VARIABLE PRODUCT JS
 */
