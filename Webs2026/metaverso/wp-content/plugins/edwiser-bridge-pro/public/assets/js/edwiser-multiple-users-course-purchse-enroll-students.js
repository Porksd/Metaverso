/**
* Bulk purchase Enroll students js.
*/
(function ($) {
	var dlgOverlyOpct  = 0.8;
	var dlgOverlyColor = "black";
	jQuery( document ).ready(
		function () {
			$( "#wdm_group_details" ).hide();

			var seat_limit = 0;

			/***********************************    ENROLL STUDENTS ALL CLICK HANDLERS     ***********************************************/

			/**
			 * Show option to enroll user.
			 */
			jQuery( '#edb_course_product_name' ).change(
				function () {
					var selected_val = $( this ).children( "option:selected" ).val();
					if (selected_val != 0) {
						$( '.ebbp_csv_enroll_error_msg' ).hide();

						getEnrolledUsers( 0 );
					} else {
						$( '.ebbp_csv_enroll_error_msg' ).hide();

						// Hide the group details.
						$( "#wdm_group_details" ).hide();

					}
				}
			);

			$( document ).on(
				"click",
				".ebbp_csv_enrollment_resp_pop_up",
				function (event) {
					var parent = $( this ).parent().parent();

					var originalContent = parent.find( ".ebbp_csv_enrollment_resp_msg_wrap" ).html();

					parent.find( '.ebbp_csv_enrollment_resp_msg' ).dialog(
						{
							height: 400,
							width: 500,
							modal: true,
							resizable: false,
							dialogClass: 'wdm-enroll-stud-page-dialog',
							open: function (event, ui) {
								// originalContent = $(".ebbp_csv_enrollment_resp_msg_wrap").html();
							},
							close: function (event, ui) {

								parent.find( ".ebbp_csv_enrollment_resp_msg_wrap" ).html( originalContent );

							},
							buttons: [
							{
								text: ebbpPublic.close,
								class: "wdm-dialog-cancel-button",
								click: function () {
									$( this ).dialog( "close" );
								}

							}
							]
						}
					);

				}
			);

			$( document ).on(
				"click",
				".ebbp_bulk_deleted_users_pop_up",
				function (event) {
					var parent          = $( this ).parent().parent();
					var originalContent = parent.find( ".ebbp_bulk_deleted_users_resp_msg_wrap" ).html();

					// var originalContent;
					parent.find( '.ebbp_bulk_deleted_users_resp_msg' ).dialog(
						{
							height: 400,
							width: 500,
							modal: true,
							resizable: false,
							dialogClass: 'wdm-enroll-stud-page-dialog ebbp_bulk_users_deleted_result',
							open: function (event, ui) {
								// originalContent = $(".ebbp_bulk_deleted_users_resp_msg_wrap").html();
							},
							close: function (event, ui) {
								parent.find( ".ebbp_bulk_deleted_users_resp_msg_wrap" ).html( originalContent );
							},
							buttons: [
							{
								text: ebbpPublic.close,
								class: "wdm-dialog-cancel-button",
								click: function () {
									$( this ).dialog( "close" );
								}

							}
							]
						}
					);
				}
			);

			// v1.1.1.
			// On Add Course Button Click
			$( document ).on(
				"click",
				"#add-product-button",
				function (event) {
					event.preventDefault();
					$( '.ebbp_action_msg' ).css( 'display', 'none' );

					jQuery( "#eb-lading-parent" ).show();
					var mdlCohortId = jQuery( "#edb_course_product_name" ).children( ":selected" ).val();
					if (mdlCohortId != "0") {
						jQuery.ajax(
							{
								type: 'POST',
								url: ebbpPublic.ajax_url,
								dataType: 'json',
								data: {
									action: 'ebbp_add_new_product',
									mdl_cohort_id: mdlCohortId,
									nonce_gp_mng: ebbpPublic.nonce_gp_mng,
								},
								success: function (response) {
									if (response.success == true) {
										var respCont = response.data;
										$( "#add-quantity-popup" ).empty();
										$( "#add-quantity-popup" ).html( respCont.data );
										$( '#bp-new-product-table' ).DataTable(
											{
												aaSorting: [[1, 'asc']],
												paging: false,
												lengthChange: false,
												autoWidth: false,
												'columnDefs': [{
													'targets': [0], /* column index */
													'orderable': false, /* true or false */
												},
												{
													'targets': [1], /* column index */
												},
												{
													'targets': [2], /* column index */
													'orderable': false, /* true or false */
												},
												{
													'targets': [3], /* column index */
													'orderable': false, /* true or false */
												},
												{
													'targets': [4], /* column index */
													'orderable': false, /* true or false */
												},
												{
													'targets': [5], /* column index */
													'orderable': false, /* true or false */
												},

												{
													'targets': [6], /* column index */
													'orderable': false, /* true or false */
												}],
												"language": {
													"emptyTable": ebbpPublic.emptyTableProducts,
												    "search": ebbpPublic.search,
												    "infoEmpty": ebbpPublic.infoEmpty,
												    "info": ebbpPublic.info,
												    "paginate": {
												        "first": ebbpPublic.first,
												        "last": ebbpPublic.last,
												        "previous": ebbpPublic.previous,
												        "next": ebbpPublic.next
												    }
												}
											}
										);
										$( '#add-quantity-popup' ).prop( 'title', ebbpPublic.addNewProductsIn );
										$( '.eb-background-div' ).show();
										$( "#add-quantity-popup" ).dialog( addProduct ).dialog( "open" );
										jQuery( "#eb-lading-parent" ).hide();
									} else {
										errorMsgDialog( response['data'] );
										jQuery( "#eb-lading-parent" ).hide();
									}
								},
								error: function (response) {
									errorMsgDialog( response );
									jQuery( "#eb-lading-parent" ).hide();
								}
							}
						);
					} else {
						jQuery( ".wdm_select_course_msg" ).css( "display", "block" );
						jQuery( "#eb-lading-parent" ).hide();
					}
				}
			);

			// v1.1.1.
			// On Add Quantity Button Click.
			$( document ).on(
				"click",
				"#add-quantity-button",
				function (event) {
					event.preventDefault();
					$( '.ebbp_action_msg' ).css( 'display', 'none' );

					jQuery( "#eb-lading-parent" ).show();
					var mdlCohortId = jQuery( "#edb_course_product_name" ).children( ":selected" ).val();
					if (mdlCohortId != "0") {
						// Changes.
						jQuery.ajax(
							{
								type: 'POST',
								url: ebbpPublic.ajax_url,
								dataType: 'json',
								data: {
									action: 'ebbp_add_quantity',
									mdl_cohort_id: mdlCohortId,
									nonce_gp_mng: ebbpPublic.nonce_gp_mng,
								},
								success: function (response) {
									if (response.success == true) {
										$( "#add-quantity-popup" ).empty();
										$( "#add-quantity-popup" ).html( response.data );

										$( "#add-quantity-table" ).DataTable(
											{
												searching: false,
												ordering: false,
												lengthChange: false,
												paging: false,
												"language": {
													"emptyTable": ebbpPublic.emptyTableProducts,
												    "search": ebbpPublic.search,
												    "infoEmpty": ebbpPublic.infoEmpty,
												    "info": ebbpPublic.info,
												    "paginate": {
												        "first": ebbpPublic.first,
												        "last": ebbpPublic.last,
												        "previous": ebbpPublic.previous,
												        "next": ebbpPublic.next
												    }
												}
											}
										);

										$( '#add-quantity-popup' ).prop( 'title', ebbpPublic.addQuantity );
										$( '.eb-background-div' ).show();
										$( "#add-quantity-popup" ).dialog( addQuantity ).dialog( "open" );
									} else {
										errorMsgDialog( response.data );
									}
									jQuery( "#eb-lading-parent" ).hide();
								},
								error: function (response) {
									errorMsgDialog( response );
									jQuery( "#eb-lading-parent" ).hide();
								}
							}
						);
					} else {
						jQuery( ".wdm_select_course_msg" ).css( "display", "block" );
						jQuery( "#eb-lading-parent" ).hide();
					}
				}
			);

			// v2.1.0.
			// On Add Quantity Button Click.
			$( document ).on(
				"click",
				"#bp-delete-cohort",
				function (event) {
					event.preventDefault();
					var mdlCohortId = jQuery( "#edb_course_product_name" ).children( ":selected" ).val();

					if (mdlCohortId != "0") {
						// Changes.
						$( "#add-quantity-popup" ).html( ebbpPublic.deleteCohortContent );
						$( "#add-quantity-popup" ).dialog(
							{
								autoOpen: false,
								title: ebbpPublic.deleteCohort,
								modal: true,
								maxHeight: 500,
								dialogClass: "wdm-enroll-stud-page-dialog bp-delete-cohort-dialog",
								buttons: [
								{
									text: ebbpPublic.deleteCohortBtn,
									class: "wdm-dialog-enroll-button",
									click: function () {
										jQuery( "#eb-lading-parent" ).show();
										jQuery.ajax(
											{
												type: 'POST',
												url: ebbpPublic.ajax_url,
												dataType: 'json',
												data: {
													action: 'bp_delete_cohort',
													cohortId: mdlCohortId,
													nonce_gp_mng: ebbpPublic.nonce_gp_mng,
												},
												success: function (response) {
													if (response.data) {
														location.reload();
													} else {
														$( ".wdm_select_course_msg .wdm_enroll_warning_message_lable" ).html( "Sorry, unable to delete group." );
														$( ".wdm_select_course_msg" ).css( "display", "block" );
													}

													$( "#add-quantity-popup" ).dialog( "close" );
													jQuery( "#eb-lading-parent" ).hide();
												},
												error: function (response) {
													jQuery( "#eb-lading-parent" ).hide();
													jQuery( "#eb-lading-parent" ).hide();
												}
											}
										);
									}
								},
								{
									text: ebbpPublic.cancel,
									class: "wdm-dialog-cancel-button",
									click: function () {
										$( this ).dialog( "close" );
									}
								},
								]
							}
						).dialog( 'open' );
					} else {
						jQuery( ".wdm_select_course_msg" ).css( "display", "block" );
					}
				}
			);

			$( document ).on(
				"click",
				"#enroll-new-user",
				function (event) {
					event.preventDefault();
					$( '.ebbp_action_msg' ).css( 'display', 'none' );
					$( '#enroll_user-pop-up' ).prop( 'title', ebbpPublic.enrollNewUser );
					setFormValues();
					var opt = mucpGetEnrolUserForm( 300, 400, ebbpPublic.enrollUser, ebbpPublic.enrollUser, 1, "enroll_user-pop-up" );
					jQuery( "#enroll_user-pop-up" ).dialog( opt ).dialog( "open" );
					var cid = jQuery( "#edb_course_product_name" ).val();
					jQuery( "#enroll_user_course" ).val( cid );

				}
			);

			$( document ).on(
				"click",
				".edit-enrolled-user",
				function (event) {
					$( '.ebbp_action_msg' ).css( 'display', 'none' );

					event.preventDefault();
					editId = jQuery( this ).attr( "id" );
					jQuery( "#eb-lading-parent" ).show();

					jQuery.ajax(
						{
							type: 'POST',
							url: ebbpPublic.ajax_url,
							dataType: 'json',
							data: {
								action: 'get_enrol_user_details',
								uid: editId,
								nonce: ebbpPublic.nonce_gp_mng,
							},
							success: function (response) {
								setFormValues( response.FirstName, response.lastname, response.email );
								jQuery( "#eb-lading-parent" ).hide();

							},
							error: function () {
								alert( "failed" );
								jQuery( "#eb-lading-parent" ).hide();
							}
						}
					);
					$( '#enroll_user-pop-up' ).prop( 'title', ebbpPublic.edit_user );
					jQuery( "#enroll_user-pop-up" ).dialog( editUser ).dialog( "open" );
				}
			);

			// $( '#wdm_user_csv_btn' ).on(
			$( '#wdm_user_csv' ).on(
				"EnrollUsersEvent",
				function (event) {
					event.preventDefault();
					jQuery( "#eb-lading-parent" ).show();
					$( '.ebbp_action_msg' ).css( 'display', 'none' );
					// var files    = jQuery( "#wdm_users_csv_input" ).prop( "files" );
					var formdata = new FormData();
					var id       = jQuery( "#edb_course_product_name" ).children( ":selected" ).val();
					var i        = 0;
					var len      = this.files.length;
					if (len > 0) {
						for (; i < len; i++) {
							if (formdata) {
								formdata.append( "wdm_users_csv_input", this.files[i] );
								formdata.append( "mdl_cohort_id", id );
								formdata.append( "wdm_eb_user_csv_nonce_field", ebbpPublic.nonce_csv_enroll );
							}
						}
					} else {
						formdata = false;
						alert( ebbpPublic.uploadFileFirst );
					}
					if (formdata) {
						var url;
						url = ebbpPublic.wdm_user_import_file;
						jQuery.ajax(
							{
								type: 'POST',
								url: url,
								data: formdata,
								processData: false,
								contentType: false,
								success: function (response) {
									var data = response.data;
									if (response.success == true) {
										jQuery( "#enroll-user-form-csv" ).html( data );
										var opt = csvGetEnrolUserForm( 600, 500, "title", "button", 1, "enroll-user-form-csv" );
										jQuery( "#eb-lading-parent" ).hide();
										jQuery( "#enroll-user-form-csv" ).dialog( opt ).dialog( 'open' );
										jQuery( '#btn_enroll' ).removeAttr( 'disabled' );
									} else {
										errorMsgDialog( data );
										jQuery( "#eb-lading-parent" ).hide();
									}
								},
								error: function (error) {
									errorMsgDialog( error );
									jQuery( "#eb-lading-parent" ).hide();

								}
							}
						);
					}
					return false;
				}
			);

			jQuery('#wdm_eb_upload_csv .fileinput-upload-button').click(
	            function (event) {
	                event.preventDefault();
	                jQuery('#wdm_user_csv').trigger('EnrollUsersEvent');
	            }
	        );


			$( document ).on(
				'click',
				'.bp-delete-enrolled-user',
				function () {
					var userId = $( this ).attr( "id" );
					$( '.ebbp_action_msg' ).css( 'display', 'none' );

					$( ".wdm_select_course_msg" ).css( "display", "none" );
					$( "#add-quantity-popup" ).html( ebbpPublic.removeUserConetnt );

					$( "#add-quantity-popup" ).dialog(
						{
							autoOpen: false,
							title: ebbpPublic.removeUserFromGroup,
							modal: true,
							maxHeight: 500,
							dialogClass: "wdm-enroll-stud-page-dialog bp-delete-enrolled-user-dialog",
							buttons: [
							{
								text: ebbpPublic.removeUser,
								class: "wdm-dialog-enroll-button",
								click: function () {
									jQuery( "#eb-lading-parent" ).show();
									$( "#add-quantity-popup" ).dialog( "close" );
									jQuery.ajax(
										{
											type: 'POST',
											url: ebbpPublic.ajax_url,
											dataType: 'json',
											data: {
												action: 'bp_delete_enrolled_user',
												userId: userId,
												cohortId: $( "#edb_course_product_name" ).val(),
												nonce: ebbpPublic.nonce_bp_enroll,
											},
											success: function (response) {
												$( "#wdm_user_delete_msg" ).css( "display", "block" );

												if (response.data.status) {
													var table = $( "#enroll-user-table" ).DataTable();
													var currentRow = $( "." + userId );
													table.row( currentRow ).remove().draw();


													$( "#wdm_user_delete_msg" ).html( response.data.msg );

													if( typeof(response.data.qty) != "undefined" && response.data.qty !== null ) {
														var qty = $( ".wdm_seats .wdm_seats_available" ).html();
														qty     = parseInt( qty ) + response.data.qty;
														$( ".wdm_seats .wdm_seats_available" ).html( qty );
														var selectDropdown = $( "#edb_course_product_name" );
														var name           = $( 'option:selected', selectDropdown ).data( 'name' );
														$( 'option:selected', selectDropdown ).html( name + "( " + qty + " )" );

														$( '.wdm_seats .wdm_seats_enrolled_users' ).html( parseInt( $( ".wdm_seats .wdm_seats_enrolled_users" ).html() ) - 1 );
													}
												} else {
													$( "#wdm_user_delete_msg" ).html( response.data.msg );
												}

												jQuery( "#eb-lading-parent" ).hide();
											},
											error: function (response) {
												jQuery( "#eb-lading-parent" ).hide();
											}
										}
									);
								}
							},
							{
								text: ebbpPublic.cancel,
								class: "wdm-dialog-cancel-button",
								click: function () {
									$( this ).dialog( "close" );
								}
							},
							]
						}
					).dialog( 'open' );
				}
			);

			/***********************************  END  ENROLL STUDENTS ALL CLICK HANDLERS    ***********************************************/

			/***********************************    ENROLL STUDENTS ALL DIALOG BOXES   ***********************************************/
			var editUser = {
				resizable: false,
				autoOpen: false,
				modal: true,
				maxWidth: 600,
				dialogClass: "wdm-enroll-stud-page-dialog",
				open: function (event, ui) {
					$( ".ui-widget-overlay" ).css(
						{
							opacity: dlgOverlyOpct,
							backgroundColor: dlgOverlyColor
						}
					);
					jQuery( '#wdm_csv_error_message' ).hide();
					jQuery( '#enroll_user_form-msg' ).hide();
					$( '.ui-dialog-buttonpane' ).find( 'button:contains(' + ebbpPublic.close + ')' ).addClass( 'wdm-dialog-cancel-button' );
					$( '.ui-dialog-buttonpane' ).find( 'button:contains(' + ebbpPublic.saveChanges + ')' ).addClass( 'wdm-dialog-edit-usr-button' );
				},
				close: function (event) {
					closeDialog( this );
				},
				create: function (event, ui) {
					$( event.target ).parent().css( 'position', 'fixed' );
				},
				buttons: [{
					text: ebbpPublic.saveChanges,
					class: 'wdm-dialog-checkout-button',
					click: function () {
						var success = validateEnrollUserForm();
						if (success) {
							jQuery( "#eb-lading-parent" ).show();
							$( '#enroll_user-pop-up' ).dialog( 'close' );
							$( "#enroll_user-form" ).css( "opacity", "0.5" );
							var firstName = $( "#wdm_enroll_fname" ).val();
							var lastName  = $( "#wdm_enroll_lname" ).val();
							var email     = $( "#wdm_enroll_email" ).val();
							jQuery.ajax(
								{
									type: 'POST',
									url: ebbpPublic.ajax_url,
									dataType: 'json',
									data: {
										action: 'edit_user',
										uid: editId,
										firstname: firstName,
										lastname: lastName,
										email: email,
										nonce: ebbpPublic.nonce_gp_mng,
									},
									success: function (response) {
										var data = response.data;
										$( "#eb-lading-parent" ).hide();
										if (response.success == true) {
											$( "#enroll_user-form" ).css( "opacity", "1" );
											$( '.eb-background-div' ).hide();
											// $('#enroll_user-pop-up').dialog('close');
											// $("#wdm_eb_message").html(data);
											$( "#wdm_user_delete_msg" ).html( data );

											// disabling all other notices
											$( "#wdm-eb-enroll-msg" ).css( "display", "none" );
											getEnrolledUsers( 0 );
										} else {
											// $('#enroll_user-pop-up').dialog('close');
											// $("#wdm_eb_message").html(data);
											$( "#wdm_user_delete_msg" ).html( data );
											// disabling all other notices
											$( "#wdm-eb-enroll-msg" ).css( "display", "none" );
										}
									},
									error: function (error) {
										$( "#eb-lading-parent" ).hide();
										// $('#enroll_user-pop-up').dialog('close');
										$( "#enroll_user-form" ).css( "opacity", "1" );
										$( '.eb-background-div' ).hide();
										// $('#enroll_user-pop-up').dialog('close');
										errorMsgDialog( error );
									}
								}
							);
						}
					}
				},
				{
					text: ebbpPublic.close,
					click: function (event) {
						closeDialog( this );
					}
				}]
			};

			/**
			 * -----------------------------------------
			 * Added this code for the responsive pop-up
			 * ----------------------------------------
			 */
			var screenWidth, screenHeight, dialogWidth, dialogHeight, isDesktop;
			screenWidth  = window.innerWidth;
			screenHeight = window.innerHeight;

			if (screenWidth < 500) {
				dialogWidth  = screenWidth * .85;
				dialogHeight = screenHeight * .75;
			} else if (screenWidth < 768) {
				dialogWidth  = screenWidth * .70;
				dialogHeight = screenHeight * .70;
			} else {
				dialogWidth  = screenWidth * .70;
				dialogHeight = screenHeight * .70;
				// isDesktop = true;
			}

			var addProduct = {
				resizable: false,
				autoOpen: false,
				modal: true,
				width: dialogWidth,
				height: dialogHeight,
				title: ebbpPublic.addNewProductsIn,
				// height: 'auto',
				// maxHeight: 550,
				// width: 'auto',
				// width: "60%",
				// maxWidth: 1000,
				// minWidth: 400,
				dialogClass: "wdm-enroll-stud-page-dialog",
				open: function (event, ui) {
					$( ".ui-widget-overlay" ).css(
						{
							opacity: dlgOverlyOpct,
							backgroundColor: dlgOverlyColor
						}
					);
				},
				close: function (event) {
					closeDialog( this );
				},
				create: function (event, ui) {
					$( event.target ).parent().css( 'position', 'fixed' );
				},
				buttons: [
				{
					text: ebbpPublic.proctocheckout,
					class: "wdm-dialog-checkout-button",
					click: proceedToCheckOut,
				},
				{
					text: ebbpPublic.cancel,
					class: "wdm-dialog-cancel-button",
					click: function (event) {
						closeDialog( this );
					}
				}
				]
			};

			var addQuantity = {
				resizable: false,
				autoOpen: false,
				modal: true,
				width: dialogWidth,
				height: dialogHeight,
				title: ebbpPublic.addQuantity,
				// maxHeight: 600,
				// width: 'auto',
				// maxWidth: 900,
				/*width: 'auto', // overcomes width:'auto' and maxWidth bug
				maxWidth: 1000,*/
				overflow: 'hidden',
				dialogClass: "wdm-enroll-stud-page-dialog",
				open: function (event, ui) {
					$( ".ui-widget-overlay" ).css(
						{
							opacity: dlgOverlyOpct,
							backgroundColor: dlgOverlyColor
						}
					);
				},
				close: function (event) {
					closeDialog( this );
				},
				create: function (event, ui) {
					$( event.target ).parent().css( 'position', 'fixed' );
				},
				buttons: [
				{
					text: ebbpPublic.proctocheckout,
					class: "wdm-dialog-checkout-button",
					click: proceedToCheckOut,
				},
				{
					text: ebbpPublic.cancel,
					class: "wdm-dialog-cancel-button",
					click: function (event) {
						closeDialog( this );
					}
				}
				],
			};

			var editId;

			function mucpGetEnrolUserForm(width, height, title, button, flag, popUp) {
				var opt = {
					resizable: false,
					autoOpen: false,
					modal: true,
					maxHeight: 500,
					dialogClass: "wdm-enroll-stud-page-dialog",
					open: function (event, ui) {
						$( ".ui-widget-overlay" ).css(
							{
								opacity: 0.2,
								// backgroundColor: 'white'
							}
						);
						jQuery( '#wdm_csv_error_message' ).hide();
						jQuery( '#enroll_user_form-msg' ).hide();
					},
					close: function (event) {
						$( '.eb-background-div' ).hide();
					},
					create: function (event, ui) {
						$( event.target ).parent().css( 'position', 'fixed' );
					},
					buttons: [
					{
						text: ebbpPublic.enrollUser,
						class: "wdm-dialog-enroll-button",
						click: function () {
							var success = true;
							if (flag) {
								success = validateEnrollUserForm();
							}
							if (success) {
								jQuery( "#eb-lading-parent" ).show();
								var mdlCohortId = jQuery( "#edb_course_product_name" ).children( ":selected" ).val();
								var firstname   = createArrayOfVariables( jQuery( "#wdm_enroll_fname" ) );
								var lastname    = createArrayOfVariables( jQuery( "#wdm_enroll_lname" ) );
								var email       = createArrayOfVariables( jQuery( "#wdm_enroll_email" ) );
								$( '#enroll_user-pop-up' ).dialog( 'close' );

								jQuery.ajax(
									{
										type: 'POST',
										url: ebbpPublic.ajax_url,
										dataType: 'json',
										data: {
											action: 'create_wordpress_user',
											mdl_cohort_id: mdlCohortId,
											firstname: firstname,
											lastname: lastname,
											email: email,
											nonce_bp_enroll: ebbpPublic.nonce_bp_enroll,
										},
										success: function (response) {
											var data = response.data;
											if (response.success == true) {
												// jQuery("#wdm_eb_message").html(data.msg);
												jQuery( "#wdm_user_delete_msg" ).html( data.msg );
												// disabling all other notices
												$( "#wdm-eb-enroll-msg" ).css( "display", "none" );
												jQuery( '.eb-background-div' ).hide();
												jQuery( "#loding-icon" ).removeClass( "loader" );
												$( ".wdm_success_message" ).css( "display", "block" );
												$( '#edb_course_product_name' ).find( ":selected" ).text( data.cohort );
												// $('#' + popUp).dialog('close');
												getEnrolledUsers( 1 );
											} else {
												errorMsgDialog( data );
											}
										},
										error: function (error) {
											errorMsgDialog( error );
											jQuery( "#eb-lading-parent" ).hide();
											$( '.eb-background-div' ).hide();
											jQuery( "#loding-icon" ).removeClass( "loader" );
											// $("#enroll-user-form-csv").dialog('close');
											$( '#enroll_user-pop-up' ).dialog( 'close' );
										}
									}
								);
							}
						}
					},
					{
						text: ebbpPublic.cancel,
						class: "wdm-dialog-cancel-button",
						click: function () {
							$( this ).dialog( "close" );
						}
					},
					]
				};
				return opt;
			}

			// v1.1.1
			function csvGetEnrolUserForm(width, height, title, button, flag, popUp) {
				var opt = {
					resizable: false,
					autoOpen: false,
					// width: width,
					modal: true,
					maxHeight: 500,
					dialogClass: "wdm-enroll-stud-page-dialog wdm-enroll-stud-page-csv-dialog",
					open: function (event, ui) {
						$( ".ui-widget-overlay" ).css(
							{
								opacity: dlgOverlyOpct,
								backgroundColor: dlgOverlyColor
							}
						);
						jQuery( '#enroll_user_form-msg' ).hide();
					},
					close: function (event) {
						$( '.eb-background-div' ).hide();
					},
					create: function (event, ui) {
						$( event.target ).parent().css( 'position', 'fixed' );
					},
					buttons: [
					{
						text: ebbpPublic.enrollUser,
						class: "wdm-dialog-enroll-button",
						click: function () {
							var success = true;
							if (flag) {
								success = validatecsvEnrollUserForm();
							}
							if (success) {

								var cohortId   = jQuery( "#edb_course_product_name" ).children( ":selected" ).val();
								var cohortName = jQuery( "#edb_course_product_name" ).children( ":selected" ).data( "cohort-name" );
								var firstname  = createArrayOfVariables( jQuery( ".txt_fname" ) );
								var lastname   = createArrayOfVariables( jQuery( ".txt_lname" ) );
								var email      = createArrayOfVariables( jQuery( ".txt_email" ) );
								var total      = firstname.length;

								enroll_csv_users_recursively( cohortId, cohortName, firstname, lastname, email, total, 0 );

							}
						}
					},
					{
						text: ebbpPublic.cancel,
						class: "wdm-dialog-cancel-button",
						click: function () {
							$( this ).dialog( "close" );
						}
					},
					]
				};
				return opt;
			}

			/***********************************  END  ENROLL STUDENTS ALL DIALOG BOXES   ***********************************************/

			/***********************   ENROLL STUDENTS OTHER DEPENDENCIES       *****************************/

			function return_set_of_array(obj, from, offset) {
				var arr = [];
				for (var i = from; i < from + offset; i++) {
					arr.push( obj[i] );
				}

				return arr;
			}

			/**
			 * Here we are using offset as 30 which is static
			 */
			function enroll_csv_users_recursively(cohortId, cohortName, firstname, lastname, email, total, processed_users) {
				offset = 5;
				if (processed_users + offset >= total) {
					offset = total - processed_users;
				}

				// set progress
				$( '#ebbp_csv_processed_users_count' ).html( processed_users );
				$( '#ebbp_csv_total_users_count' ).html( total );

				$( '#ebbp_csv_users_progress_percent' ).html( Math.round( processed_users * 100 / total ) + '%' );
				$( "#ebbp_csv_users_progress_wrap" ).show();

				$( "#enroll-user-form-csv" ).dialog( 'close' );
				// return_set_of_array(firstname, processed_users, offset);

				jQuery.ajax(
					{
						type: 'POST',
						url: ebbpPublic.ajax_url,
						dataType: 'json',
						data: {
							action: 'create_wordpress_user',
							mdl_cohort_id: cohortId,
							cohortName: cohortName,
							firstname: return_set_of_array( firstname, processed_users, offset ),
							lastname: return_set_of_array( lastname, processed_users, offset ),
							email: return_set_of_array( email, processed_users, offset ),
							processed_users: processed_users,
							total: total,
							nonce_bp_enroll: ebbpPublic.nonce_bp_enroll,
						},
						success: function (response) {
							var data = response.data;
							if (response.success == true) {
								// Check if all users are processed.
								processed_users = processed_users + data.processed_users;
								if (processed_users < total) {
									enroll_csv_users_recursively( cohortId, cohortName, firstname, lastname, email, total, processed_users );
								} else {
									// Response message from response.
									jQuery( "#wdm_user_delete_msg" ).html( data.msg );
									// disabling all other notices
									$( "#wdm-eb-enroll-msg" ).css( "display", "none" );
									$( "#ebbp_csv_users_progress_wrap" ).hide();
									jQuery( "#loding-icon" ).removeClass( "loader" );
									$( ".wdm_success_message" ).css( "display", "block" );
									$( '#edb_course_product_name' ).find( ":selected" ).text( data.cohort );
									getEnrolledUsers( 1 );
								}

							} else {
								errorMsgDialog( data );
							}
						},
						error: function (error) {
							errorMsgDialog( error );
							$( "#ebbp_csv_users_progress_wrap" ).hide();
							jQuery( "#loding-icon" ).removeClass( "loader" );
							$( "#enroll-user-form-csv" ).dialog( 'close' );
						}
					}
				);

			}

			function closeDialog(dialogObj) {
				$( dialogObj ).dialog( 'destroy' );
			}

			function validateEnrollUserForm() {

				var numItems      = jQuery( '#wdm_enroll_fname' ).length;
				var empty_flag    = 0;
				var username_flag = 0;
				var email_flag    = 0;
				jQuery( '#wdm_enroll_fname' ).each(
					function () {
						var firstName = jQuery( this ).val();
						firstName     = firstName.trim();

						if (firstName == '' || firstName.length == 0) {
							empty_flag = 1;
							return false;
						}
					}
				);

				jQuery( '#wdm_enroll_lname' ).each(
					function () {
						var lastName = jQuery( this ).val();
						lastName     = lastName.trim();

						if (lastName == '' || lastName.length == 0) {
							empty_flag = 1;
							return false;
						}
					}
				);

				jQuery( '.txt_uname' ).each(
					function () {
						if (jQuery( this ).val() == '') {
							empty_flag = 1;
							return false;
						}
					}
				);
				var emailReg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				jQuery( '#wdm_enroll_email' ).each(
					function () {
						if (jQuery( this ).val() == '') {
							empty_flag = 1;
							return false;
						} else {
							if ( ! emailReg.test( jQuery( this ).val() )) {
								jQuery( '#enroll_user_form-msg' ).html( '<div class="wdm_select_course_msg" style = "display:block"><i class="dashicons dashicons-dismiss wdm_select_course_msg_dismiss"></i><label class="wdm_enroll_warning_message_label">' + ebbpPublic.invalidEmailId + '</div>' );
								email_flag = 1;
							}
						}
					}
				);
				if (empty_flag == 1 || email_flag == 1 || username_flag == 1) {
					if (empty_flag == 1) {
						jQuery( '#enroll_user_form-msg' ).html( '<div class="wdm_select_course_msg" style = "display:block"><i class="dashicons dashicons-dismiss wdm_select_course_msg_dismiss"></i><label class="wdm_enroll_warning_message_label">' + ebbpPublic.mandatoryMsg + '</div>' );
					}
					jQuery( '#enroll_user_form-msg p' ).addClass( 'wdm_error_message' );
					jQuery( '#enroll_user_form-msg' ).show();
					return false;
				} else {
					return true;
				}
			}

			function setFormValues(FirstName = '', lastname = '', email = '') {
				jQuery( "#wdm_enroll_fname" ).val( FirstName );
				jQuery( "#wdm_enroll_lname" ).val( lastname );
				jQuery( "#wdm_enroll_email" ).val( email );

			}

			function proceedToCheckOut() {
				// v1.1.1
				// Check if the Add Quantity or Add Product Pop up is open
				var popup = "";
				if (jQuery( ".add-more-quantity" ).length != 0) {
					var Quantity = jQuery( ".wdm_new_qty_per_prod" );
					popup        = "quantity";
				} else {
					var Quantity         = jQuery( ".wdm_new_qty_per_new_prod" );
					var selectedProducts = jQuery( ".wdm_selected_products" );
					popup                = "products";
				}

				var productArray = {};
				// For Add Products.
				if (popup == "products") {
					Quantity.each(
						function (index, value) {
							if (jQuery( selectedProducts[index] ).is( ':checked' )) {
								productArray[$( value ).attr( 'id' )] = $( value ).html();
							}
						}
					);
				} else if (popup == "quantity") {
					Quantity.each(
						function (index, value) {
							if ($( value ).html() != 0) {
								productArray[$( value ).attr( 'id' )] = $( value ).html();
							}
						}
					);
				}
				if (jQuery.isEmptyObject( productArray )) {
					jQuery( "#add-quantity-msg" ).html( "<p>" + ebbpPublic.enterQuantity + "</p>" );
				} else {
					var cohortId = $( "#add-quantity-table" ).data( "cohortid" );
					jQuery.ajax(
						{
							type: 'POST',
							url: ebbpPublic.ajax_url,
							dataType: 'json',
							data: {
								action: 'ebbp_add_to_cart',
								mdl_cohort_id: cohortId,
								productQuantity: productArray,
								nonce_gp_mng: ebbpPublic.nonce_gp_mng,
							},
							success: function (response) {
								if (response.success == true) {
									$( "body" ).css( "cursor", "default" );
									window.location = response.data;
								} else {
									errorMsgDialog( response.data );
								}
							},
							error: function (response) {
								$( "body" ).css( "cursor", "default" );
								$( '#add-quantity-popup' ).dialog( 'close' );
								errorMsgDialog( response );
							}
						}
					);
					$( this ).dialog( "close" );
				}
			}

			function getEnrolledUsers(flag) {
				if ( ! flag) {
					$( '.wdm_error_message' ).remove();
					$( '.wdm_success_message' ).remove();
					$( '.wdm_enroll_warning_message' ).remove();
				}

				$( ".wdm_select_course_msg" ).css( "display", "none" );
				$( '#wdm_eb_message_divider' ).remove();
				$( "#0" ).remove();
				var mdlCohortId = $( "#edb_course_product_name" ).children( ":selected" ).val();

				// setting edit group name sections input field value.
				var name = $( "#edb_course_product_name" ).children( ":selected" ).data( "name" );
				$( "#eb_inpt_edit_cohort_name" ).val( $.trim( name ) );

				$( "#eb-lading-parent" ).show();
				$.ajax(
					{
						type: 'POST',
						url: ebbpPublic.ajax_url,
						dataType: 'json',
						data: {
							action: 'get_user_bulk_course_details',
							mdl_cohort_id: mdlCohortId,
							nonce_gp_mng: ebbpPublic.nonce_gp_mng,
						},
						success: function (response) {
							$( "#eb-lading-parent" ).hide();
							var data = response.data;
							if (response.success == true) {
								seat_limit = data['seats'];
								$( '.wdm_seats .wdm_seats_available' ).html( ' ' + seat_limit );
								$( '.wdm_seats .wdm_seats_enrolled_users' ).html( data['enrolled_users'] );
								$( '#wdm_user_data' ).empty();
								$( '.wdm_enrolled_users' ).html( data.html );
								$( "#wdm_group_details" ).show();
								$( "#wdm_associated_courses" ).html( data.asso_courses );
								var icons = {
									header: "ui-icon-triangle-1-e",
									activeHeader: "ui-icon-triangle-1-s"
								};

								$( '#enroll-user-table' ).DataTable(
									{
										paging: true,
										pagingType: "simple_numbers",
										/*lengthChange: true,
										"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],*/
										responsive: true,
										"dom": '<"ebbp_enroll_stu_action_wrap">frtip',
										order: [2, 'asc'],
										'columnDefs': [{
											'targets': [0], /* column index */
											'orderable': false, /* true or false */
										},
										{
											'targets': [3], /* column index */
											'orderable': false, /* true or false */
										},
										{
											'targets': [4], /* column index */
											'orderable': false, /* true or false */
										}],
										select: {
											style: 'os',
											selector: 'td.select-checkbox'
										},
										"language": {
											"emptyTable": ebbpPublic.emptyTable,
										    "search": ebbpPublic.search,
										    "infoEmpty": ebbpPublic.infoEmpty,
										    "info": ebbpPublic.info,
										    "paginate": {
										        "first": ebbpPublic.first,
										        "last": ebbpPublic.last,
										        "previous": ebbpPublic.previous,
										        "next": ebbpPublic.next
										    }
										}
									}
								);

								$( "div.ebbp_enroll_stu_action_wrap" ).html( '<div> <select class="ebbp_enroll_stu_action"> <option> ' + ebbpPublic.select_action_lbl + '</option><option value="delete"> ' + ebbpPublic.remove + ' </option></select> <span> <button id="ebbp_enroll_student_action" style="cursor:not-allowed">' + ebbpPublic.apply + '</button> </span> </div> <div class="ebbp_action_msg"></div>' );

								$( '#ebbp_enroll_student_action' ).click(
									function (event) {

										event.preventDefault();
										$( '.ebbp_action_msg' ).css( 'display', 'none' );

										if ($( '.ebbp_enroll_stu_action' ).val() == 'delete') {

											$( '.ebbp_bulk_users_deleted_result' ).remove();
											// Get all checked users.
											var arr_checked_users = new Array();

											// var sids = new Array();
											$( '.ebbp_ebroll-students_cb' ).each(
												function (index, value) {
													if (this.checked) {
														arr_checked_users.push( $( this ).data( 'id' ) );
													}
												}
											);

											if (arr_checked_users.length <= 0) {
													$( '.ebbp_action_msg' ).css( 'display', 'block' );
													$( '.ebbp_action_msg' ).html( ebbpPublic.select_delete_users );
											} else {
												var userId = $( this ).attr( "id" );
												$( ".wdm_select_course_msg" ).css( "display", "none" );
												$( "#add-quantity-popup" ).html( ebbpPublic.removeUserConetnt );

												$( "#add-quantity-popup" ).dialog(
													{
														autoOpen: false,
														title: ebbpPublic.removeUserFromGroup,
														modal: true,
														maxHeight: 500,
														dialogClass: "wdm-enroll-stud-page-dialog bp-delete-enrolled-user-dialog",
														buttons: [
														{
															text: ebbpPublic.removeUser,
															class: "wdm-dialog-enroll-button",
															click: function () {
																$( "#add-quantity-popup" ).dialog( "close" );
																jQuery( "#eb-lading-parent" ).show();

																var total = arr_checked_users.length;

																// call function to recursively delete users.
																remove_users_from_group( arr_checked_users, total, 0 )
																$( '.ebbp_action_msg' ).css( 'display', 'none' );
															}
														},
														{
															text: ebbpPublic.cancel,
															class: "wdm-dialog-cancel-button",
															click: function () {
																$( this ).dialog( "close" );
															}
														},
														]
													}
												).dialog( 'open' );
											}

										} else {
											$( '.ebbp_action_msg' ).css( 'display', 'block' );
											$( '.ebbp_action_msg' ).html( ebbpPublic.select_action );
										}

									}
								);

								function remove_users_from_group(arr_checked_users, total, processed_users) {

									offset = 30;
									if (processed_users + offset >= total) {
										offset = total - processed_users;
									}

									jQuery.ajax(
										{
											type: 'POST',
											url: ebbpPublic.ajax_url,
											dataType: 'json',
											data: {
												action: 'bp_delete_multiple_enrolled_user',
												userId: return_set_of_array( arr_checked_users, processed_users, offset ),
												processed_users: processed_users,
												total: total,
												cohortId: $( "#edb_course_product_name" ).val(),
												nonce: ebbpPublic.nonce_gp_mng,
											},
											success: function (response) {

												if (response.data.status) {

													processed_users = processed_users + response.data.processed_users;
													if (processed_users < total) {
														remove_users_from_group( arr_checked_users, total, processed_users );
													} else {

														jQuery( "#eb-lading-parent" ).hide();

														$( "#wdm_user_delete_msg" ).css( "display", "block" );

														var table = $( "#enroll-user-table" ).DataTable();

														for (var i = 0; i <= response.data.enrolled_user.length; i++) {
															var currentRow = $( "." + response.data.enrolled_user[i] );
															table.row( currentRow ).remove();
														}

														table.draw();

														$( "#wdm_user_delete_msg" ).html( response.data.msg );

														if (response.data.qty) {

															var qty = $( ".wdm_seats .wdm_seats_available" ).html();
															qty     = parseInt( qty ) + response.data.qty;
															$( ".wdm_seats .wdm_seats_available" ).html( qty );
															var selectDropdown = $( "#edb_course_product_name" );
															var name           = $( 'option:selected', selectDropdown ).data( 'name' );
															$( 'option:selected', selectDropdown ).html( name + "( " + qty + " )" );
														}
														$( '.wdm_seats .wdm_seats_enrolled_users' ).html( parseInt( $( ".wdm_seats .wdm_seats_enrolled_users" ).html() ) - response.data.processed_users );

													}

												} else {
													$( "#wdm_user_delete_msg" ).html( response.data.msg );
												}

											},
											error: function (response) {
												jQuery( "#eb-lading-parent" ).hide();
											}
										}
									);
								}

								$( document ).on(
									'change',
									'.ebbp_enroll_stu_action',
									function () {

										if ($( this ).val() == 'delete') {

											$( '#ebbp_enroll_student_action' ).css( 'cursor', 'pointer' );
										} else {
											$( '#ebbp_enroll_student_action' ).css( 'cursor', 'not-allowed' );
										}
									}
								);

								$( '#ebbp_enroll_student_cb_head' ).on(
									'click',
									function (e) {
										if (this.checked) {
											var count = 0;
											$( '.ebbp_ebroll-students_cb' ).prop( "checked", true );
										} else {
											$( '.ebbp_ebroll-students_cb' ).prop( "checked", false );
										}
									}
								);

								/*
								 *----------------------
								 * Functionality to clear the selected file in the bootstrap file uploader.
								 *----------------------
								 */

								$( '#wdm_user_data' ).append( "<ul class='wdm_button'><li><input type='button' id='btn_add_new' class='button' value='" + ebbpPublic.addNewUser + "'><input type='submit' id='btn_enroll' class='button' value='" + ebbpPublic.enroll + "' disabled></li></ul>" );

								if ($( '.wdm_seats .wdm_seats_available ' ).html() == 0) {
									$( "#enroll-new-user" ).prop( 'disabled', true );
									$( "#enroll-new-user" ).css( "cursor", "not-allowed" );
									$( "#enroll-multiple-users" ).prop( 'disabled', true );
									$( "#enroll-multiple-users" ).css( "cursor", "not-allowed" );
								} else {
									$( "#enroll-new-user" ).prop( 'disabled', false );
									$( "#enroll-new-user" ).css( "cursor", "default" );
									$( "#enroll-new-user" ).hover().css( "cursor", "pointer" );
									$( "#enroll-multiple-users" ).prop( 'disabled', false );
									$( "#enroll-multiple-users" ).css( "cursor", "default" );
									$( "#enroll-multiple-users" ).hover().css( "cursor", "pointer" );
								}
							} else {
								errorMsgDialog( data );
							}
						},
						error: function (error) {
							jQuery( "#eb-lading-parent" ).hide();
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
						title: ebbpPublic.error,
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

			function validatecsvEnrollUserForm() {

				var numItems      = jQuery( '.wdm_new_user' ).length;
				var empty_flag    = 0;
				var username_flag = 0;
				var email_flag    = 0;
				jQuery( '.txt_fname' ).each(
					function () {
						if (jQuery( this ).val() == '') {
							empty_flag = 1;
							jQuery( this ).css( 'border', '1px solid red' );
							return false;
						}
					}
				);
				jQuery( '.txt_lname' ).each(
					function () {
						if (jQuery( this ).val() == '') {
							empty_flag = 1;
							jQuery( this ).css( 'border', '1px solid red' );
							return false;
						}
					}
				);

				var emailReg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				jQuery( '.txt_email' ).each(
					function () {
						if (jQuery( this ).val() == '') {
							empty_flag = 1;
							jQuery( this ).css( 'border', '1px solid red' );
							return false;
						} else {
							if ( ! emailReg.test( jQuery( this ).val() )) {
								// jQuery('#wdm_eb_message').append("<p>" + jQuery(this).val() + " is not a valid email id </p>");

								// jQuery('#enroll_user_form-msg').html("<p>" + ebbpPublic.invalidEmailId + " " + jQuery(this).val() + "</p>");
								jQuery( '#wdm_csv_error_message' ).html( '<div class="wdm_select_course_msg" style = "display:block"><i class="dashicons dashicons-dismiss wdm_select_course_msg_dismiss"></i><label class="wdm_enroll_warning_message_label">' + ebbpPublic.invalidEmailId + '</div>' );
								// jQuery('#enroll_user_form-msg p').addClass('wdm_error_message');
								email_flag = 1;
								jQuery( this ).css( 'border', '1px solid red' );
							}
						}
					}
				);
				if (empty_flag == 1 || email_flag == 1 || username_flag == 1) {
					if (empty_flag == 1) {
						// jQuery('#enroll_user_form-msg').html("<p>" + ebbpPublic.mandatoryMsg + "</p>");
						jQuery( '#wdm_csv_error_message' ).html( '<div class="wdm_select_course_msg" style = "display:block"><i class="dashicons dashicons-dismiss wdm_select_course_msg_dismiss"></i><label class="wdm_enroll_warning_message_label">' + ebbpPublic.mandatoryMsg + '</div>' );
					}
					// jQuery('#enroll_user_form-msg p').addClass('wdm_error_message');
					jQuery( '#wdm_csv_error_message' ).show();
					/*jQuery('html,body').animate({scrollTop: 0}, '500', 'swing');*/
					return false;
				} else {
					return true;
				}
			}

			/**
			 * Functionality to update the product quntity on checkbox checked uncheced in the.
			 * add more product functions.
			 */
			$( document ).on(
				'change',
				'.wdm_selected_products',
				function (event) {
					// Get the id of current checkbox.
					var selectedItemId = $( this ).attr( 'id' );
					var parentRow      = $( this ).closest( "tr" );
					// Genrate the id product price id using checkbox id.
					var priceId = selectedItemId.replace( "-wdm-sele-prod", "" ) + "-per-product-price";
					// Genrate the id product qunatity id using checkbox id.
					var qtyId = selectedItemId.replace( "-wdm-sele-prod", "" );
					// Genrate the id for grand total using checkbox id.
					var totalPriceId = selectedItemId.replace( "-wdm-sele-prod", "" ) + "-total-price";

					/**
					 * Check is the product is selected.
					 */
					if ($( "#" + selectedItemId ).prop( 'checked' ) == true) {
						parentRow.find( 'td' ).addClass( 'wdm-tbl-sel-row' );
						// $(parentRow).addClass("wdm-tbl-sel-row");
						// calculate the grand total and add update into the total price column.
						$( "#" + totalPriceId ).html( ($( "#" + qtyId ).html() * $( "#" + priceId ).html()).toFixed( 2 ) );
					} else {
						// set the total price per product on checkbox uncheked.
						parentRow.find( 'td' ).removeClass( 'wdm-tbl-sel-row' );
						// $(parentRow).removeClass("wdm-tbl-sel-row");
						$( "#" + totalPriceId ).html( "0" );
					}
					// Upadte the grand total.
					var grandTotal = calculateGrantTotal();
					$( "#add-quantity-total-price" ).html( grandTotal.toFixed( 2 ) );

				}
			);

			function createArrayOfVariables(obj) {
				var arr = [];
				obj.each(
					function () {
						var eachValue = $( this ).val();
						arr.push( eachValue )
						// alert(eachValue);
					}
				);
				return arr;
			}

			/**
			 * Functionality to calculate the grand total in the add more product/quantity popup.
			 *
			 * @returns {Number}
			 */
			function calculateGrantTotal() {
				var total = 0;
				$( '.wdm-quantity-total' ).each(
					function (event) {
						total += parseFloat( $( this ).text() );
					}
				);
				return total;
			}

			/**
			 * Functionality to increase the product quantity uniformly in the add.
			 * more product/quantity popup box for the each product.
			 */
			$( document ).on(
				'keypress click input',
				'#wdm_new_prod_qty',
				function (event) {
					if (event.which == 45 || event.which == 46 || event.which == 189) {
						event.preventDefault();
					}
					var self = this;
					$( ".wdm_new_qty_per_prod" ).html( $( this ).val() );
					$( ".wdm_new_qty_per_new_prod" ).html( $( this ).val() );
					$(".wdm-quantity-total").each(function (ind, el) {
						jQuery(el).html($(self).val() * jQuery('#' + jQuery(el).parents('tr').find('.wdm_new_qty_per_prod').attr('id') + "-per-product-price").text());
					});
					var grandTotal = calculateGrantTotal();
					$( "#add-quantity-total-price" ).html( grandTotal.toFixed( 2 ) );
				}
			);

			/***********************   END ENROLL STUDENTS OTHER DEPENDENCIES       *****************************/

			/***********************   OTHER JS  *******************/

			// ENROLL_STUDENTS TAB CONTENT JS.
			$( ".eb-enroll-student-tab" ).click(
				function () {
					var sectionName    = $( this ).data( "section" );
					var oldSectionName = $( ".eb-enroll-student-tab-container .eb-enroll-student-tab-active" ).data( "section" );
					$( "#" + oldSectionName ).addClass( "eb_hidden_tab_content" );
					$( "#" + oldSectionName ).removeClass( "eb_current_tab" );

					$( "#" + sectionName ).addClass( "eb_current_tab" );
					$( ".eb-enroll-student-tab-container .eb-enroll-student-tab-active" ).removeClass( "eb-enroll-student-tab-active" );
					$( this ).addClass( "eb-enroll-student-tab-active" );
				}
			);

			$( "#eb_inpt_edit_cohort_name_btn" ).click(
				function () {
					var cohortId   = $( "#edb_course_product_name" ).children( ":selected" ).val();
					var cohortName = $( "#eb_inpt_edit_cohort_name" ).val();
					$( document.body ).css( { 'cursor': 'wait' } );
					jQuery.ajax(
						{
							type: 'POST',
							url: ebbpPublic.ajax_url,
							dataType: 'json',
							data: {
								action: 'ebbp_edit_cohort_name',
								mdl_cohort_id: cohortId,
								mdl_cohort_name: cohortName,
								nonce: ebbpPublic.nonce_gp_mng,
							},
							success: function (response) {
								if (response.success == true) {
									$( "#wdm-eb-enroll-msg span" ).html( response.data );
									$( "#wdm-eb-enroll-msg" ).addClass( "wdm-diff-prod-qty-success" );
									$( "#wdm-eb-enroll-msg" ).css( "display", "block" );
									$( document.body ).css( { 'cursor': 'default' } );
									var qty = $( '#edb_course_product_name' ).children( ":selected" ).data( "qty" );
									$( '#edb_course_product_name' ).find( ":selected" ).text( cohortName + "( " + qty + " )" );

									/* $("body").css("cursor", "default");
									 window.location = response.data;*/
								} else {
									$( "#wdm-eb-enroll-msg span" ).html( response.data );
									$( "#wdm-eb-enroll-msg" ).addClass( "wdm_error_msg" );
									$( document.body ).css( { 'cursor': 'default' } );
									$( "#wdm-eb-enroll-msg" ).css( "display", "block" );
									// errorMsgDialog(response.data);
								}
							},
							error: function (response) {
								$( document.body ).css( { 'cursor': 'default' } );
							}
						}
					);
				}
			);

			$( "#enroll-multiple-users" ).click(
				function (event) {
					event.preventDefault();
					if ($( "#wdm_eb_upload_csv" ).hasClass( "eb_hide" )) {
						$( "#wdm_eb_upload_csv" ).slideDown();
						$( "#wdm_eb_upload_csv" ).removeClass( "eb_hide" );
					} else {
						$( "#wdm_eb_upload_csv" ).slideUp();
						$( "#wdm_eb_upload_csv" ).addClass( "eb_hide" );
					}

				}
			);

			/*
			* --------------------------------------------
			* Add quantity pop-up box input field value validation restricting user from entring less than 10000 quantity.
			* --------------------------------------------
			*/
			// Reusable Function to Enforce MaxLength.
			function restrictMaxInputLength(event) {
				var t = event.target;

				if (t.id == "wdm_new_prod_qty") {
					// if (t.hasAttribute('maxlength')) {
					t.value = t.value.slice( 0, t.getAttribute( 'maxlength' ) );
					// }
				}

			}

			// Global Listener for anything with an maxlength attribute.
			// I put the listener on the body, put it on whatever.
			document.body.addEventListener( 'input', restrictMaxInputLength );

			/***********************   END  OTHER JS  *******************/

		}
	);
})( jQuery );
