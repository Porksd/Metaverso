/*
* Bulk purchase product purchase js.
*/
(function ($) {

	function errorMsgDialog(msg) {
		var error = $( document.createElement( 'div' ) );
		msg       = '<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> <strong>Error:</strong>' + msg + '</p></div>';
		error.html( msg );
		error.dialog(
			{
				title: "Message",
				autoOpen: false,
				modal: true,
				resizable: false,
				buttons: {
					"OK": function () {
						$( this ).dialog( "close" );
					}
				},
				open: function (event, ui) {
					$( ".ui-widget-overlay" ).css(
						{
							opacity: 0.8,
							filter: "Alpha(Opacity=100)",
							backgroundColor: "black"
						}
					);
				},
				create: function (event, ui) {
					$( event.target ).parent().css( 'position', 'fixed' );
				},
			}
		).dialog( "open" );
	}

	function unenrollFromCohort(recId, cohortName, userId, cohortManager) {
		$( "#eb-lading-parent" ).show();
		$.ajax(
			{
				method: "post",
				url: ajaxurl,
				dataType: "json",
				data: {
					'action': 'mucp_unenrol_user',
					'nonce': ebbpAdmin.nonce_admin,
					'rec_id': recId,
					'mdl_cohort_id': cohortName,
					'user_id': userId,
					'enrolled_by': cohortManager
				},
				success: function (response) {
					if (response['success']) {
						if (response['data'] == "OK") {
							var url              = window.location.href + "&unenroll=1";
							window.location.href = url;
						} else {
							errorMsgDialog( response['data'] );
						}
					} else {
						errorMsgDialog( response['data'] );
					}
					$( "#eb-lading-parent" ).hide();

				},
				error: function (response) {
					$( "#eb-lading-parent" ).hide();
					errorMsgDialog( response );
				},
			}
		);
	}

	$( '.mucp-manage-enrol-wrap' ).ready(
		function () {

			$( document ).on(
				"click",
				".moodle_course_group_purchase",
				function (event) {
					// $("#moodle_course_group_purchase").click(function(){
					var parent   = $( this ).parent();
					parent       = parent.parent();
					reuse_qty    = parent.parent().find( '.bp-reuse-qty-contain' );
					hidden_field = parent.find( '.moodle_course_group_purchase_hidden' );

					if (this.checked) {
						reuse_qty.css( "display", "block" );
						hidden_field.val( 'on' );
					} else {
						reuse_qty.css( "display", "none" );
						hidden_field.val( 'off' );
					}
				}
			);

			$( document ).on(
				"click",
				".bp_reuse_quantity",
				function (event) {
					// $("#moodle_course_group_purchase").click(function(){

					var hidden_field = $( this ).parent();
					hidden_field     = hidden_field.find( '.bp_reuse_quantity_hidden' );

					if (this.checked) {
						hidden_field.val( 'on' );
					} else {
						hidden_field.val( 'off' );
					}
				}
			);

			$( ".ebbp-cohort-details-link" ).click(
				function () {
					var recId         = $( this ).data( 'record-id' );
					var mdlCohortId   = $( this ).data( 'mdl-cohort-id' );
					var userId        = $( this ).data( 'user-id' );
					var cohortManager = $( this ).data( 'cohort-manager' );
					var cohortName    = $( this ).parent();
					cohortName        = cohortName.find( "p" ).text();
					$( "#eb-lading-parent" ).show();
					$.ajax(
						{
							method: "post",
							url: ajaxurl,
							dataType: "json",
							data: {
								'action': 'mucp_cohort_details',
								'nonce': ebbpAdmin.nonce_admin,
								'enrolled_by': $( this ).data( 'cohort-manager' ),
								'mdl_cohort_id': mdlCohortId,
								'user_id': userId
							},
							success: function (response) {
								if (response['success']) {
									response = response['data'];
									$( "#eb-lading-parent" ).hide();
									var title = response['cohort_name'] + " Cohort Details";
									$( "#eb-copany-name" ).html( response.companyName );
									$( "#eb-manager" ).html( response.manager );
									$( "#eb-members" ).html( response.members );
									$( "#eb-courses" ).html( response.courses );
									$( "#eb-current-user" ).html( response.currentUser );
									$( "#mucp-cohort-details-dialog" ).dialog(
										{
											title: title,
											autoOpen: false,
											modal: true,
											minWidth: 500,
											maxWidth: 600,
											resizable: false,
											open: function (event, ui) {
												$( ".ui-widget-overlay" ).css(
													{
														opacity: 0.8,
														filter: "Alpha(Opacity=100)",
														backgroundColor: "black"
													}
												);
												$( "#mucp-cohort-details-dialog" ).css( 'overflow', 'hidden' );
											},
											create: function (event, ui) {
												$( event.target ).parent().css( 'position', 'fixed' );
											},
											buttons: [
											{
												id: "unenroll_from_cohort",
												text: "Unenroll From Cohort",
												click: function () {
													$( "#mucp-cohort-details-dialog" ).dialog( 'close' );
													unenrollFromCohort( recId, mdlCohortId, userId, cohortManager );
												},
											},
											]
										}
									).dialog( "open" );
								} else {
									$( "#eb-lading-parent" ).hide();
									errorMsgDialog( response['data'] );
								}
							},
							error: function (response) {
								$( "#eb-lading-parent" ).hide();
								errorMsgDialog( response );
							},
						}
					);
				}
			);
		}
	);
})( jQuery );

jQuery (
	function ($) {
		$(document).ready(
			function () {
				$( "#eb_inpt_edit_group_name_btn" ).click(
					function () {
						var cohortId   = $( "#eb_mdl_cohort_id" ).val();
						var cohortName = $( "#eb_inpt_edit_cohort_name" ).val();
						$( document.body ).css( { 'cursor': 'wait' } );
						jQuery.ajax(
							{
								type: 'POST',
								url: ebbpManageGroup.ajax_url,
								dataType: 'json',
								data: {
									action: 'ebbp_edit_cohort_name',
									mdl_cohort_id: cohortId,
									mdl_cohort_name: cohortName,
									nonce: ebbpManageGroup.nonce_gp_mng,
								},
								success: function (response) {
									if (response.success == true) {
										$( "#wdm-eb-enroll-msg span" ).html( response.data );
										$( "#wdm-eb-enroll-msg" ).addClass( "wdm-manage-group-success-msg" );
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
			}
		);
	}
);
