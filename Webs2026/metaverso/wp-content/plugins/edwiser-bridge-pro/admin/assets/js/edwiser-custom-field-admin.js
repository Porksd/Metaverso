jQuery(document).ready(function($){

	// Jquery table sortable intialization.
    $( ".eb_custom_field_tbl>tbody" ).sortable();
    $( ".eb_custom_field_tbl>tbody" ).disableSelection();

        // Pop up design.
        $('.eb_cf_add_new_field_btn').click(function(event){
            event.preventDefault();
            row_obj = this;
            // empty the dialog box.
            eb_cf_clear_pop_up_fields_data();
			$('.eb-cf-loader').remove();
    
            $('.eb_cf_error_msg').css('display', 'none');
            $('.eb_cf_empty_table').css('display', 'none');
            $('.eb-cf-pop-up-cont').dialog({
                height: 600,
                width: 750,
                modal: true,
                resizable: false,
                dialogClass: 'eb-cf-dialog',
                buttons: [
                    {
                        text: eb_custom_fields.dialog_save_btn,
                        class: 'eb-cf-dialog-btn button-primary',
                        click: function () {
							$('.eb-cf-dialog-btn').after('<span class="eb-cf-loader alert alert-loading"></span>');
                            // Validate fields check if name exists in other rows.
                            var field_name = $('input[name="eb-cf-dialog-name"]').val();
                            var validtion_msg = validate_field_name(field_name, row_obj);

                            if('' == field_name || 1 != validtion_msg){
                                $('.eb_cf_error_msg').html(validtion_msg);
								$('.eb-cf-loader').remove();
                                return;
                            }

							var invalid_options = false;
							var type = $('select[name="eb-cf-dialog-type"]').val();
							if(type == 'select'){
								$('.eb-cf-pop-up-options-field').each(function(index, element){
									var op_val = $(element).find('.eb-cf-dialog-option-val').val();
									var op_text = $(element).find('.eb-cf-dialog-option-txt').val();
									if(op_val == '' || op_text == ''){
										invalid_options = true;
									}
								});
							}
							if(invalid_options){
								$('.eb_cf_error_msg').css('display', 'block');
								$('.eb_cf_error_msg').html(eb_custom_fields.dialog_option_validation);
								$('.eb-cf-loader').remove();
								return;
							}

							var data_array = eb_cf_get_pop_up_data();
							
							eb_cf_save_data(data_array);
                        }
                    },
                    {
                        text: eb_custom_fields.dialog_cancel_btn,
                        class: 'eb-cf-dialog-cancel-btn',
                        click: function(){
                            // Empty the dialog box.
                            eb_cf_clear_pop_up_fields_data();
                            // now close the dialog box.
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        });
            // $('.eb-cf-edit').click(function(event){
    $(document).on('click', '.eb-cf-edit', function (event) {
		event.preventDefault();

    	$('.eb_cf_error_msg').css('display', 'none');
		eb_cf_clear_pop_up_fields_data();
		$('.eb-cf-loader').remove();
	    // Get data of the existing column.
	    edit_row_obj = this;
		//create new input field with old name.
		var old_name = $(this).parent().parent().find('.eb-cf-tbl-name').val();
	    eb_cf_set_pop_up_data(edit_row_obj);

    	$('.eb-cf-pop-up-cont').dialog({
    		height: 600,
		    width: 700,
		    modal: true,
		    resizable: false,
		    dialogClass: 'eb-cf-dialog',
		    buttons: [
	            {
	                text: eb_custom_fields.dialog_save_btn,
	                class: 'eb-cf-dialog-btn button-primary',
	                click: function () {
						// add loader
						$('.eb-cf-dialog-btn').after('<span class="eb-cf-loader alert alert-loading"></span>');
	                	var field_name = $('input[name="eb-cf-dialog-name"]').val();
	                	var validtion_msg = validate_field_name(field_name, edit_row_obj);

	                	if('' == field_name || 1 != validtion_msg){
	                		$('.eb_cf_error_msg').html(validtion_msg);
							$('.eb-cf-loader').remove();
	                		return;
	                	}

						var invalid_options = false;
						var type = $('select[name="eb-cf-dialog-type"]').val();
						if(type == 'select'){
							$('.eb-cf-pop-up-options-field').each(function(index, element){
								var op_val = $(element).find('.eb-cf-dialog-option-val').val();
		    					var op_text = $(element).find('.eb-cf-dialog-option-txt').val();
								if(op_val == '' || op_text == ''){
									invalid_options = true;
								}
							});
						}
						if(invalid_options){
							$('.eb_cf_error_msg').css('display', 'block');
							$('.eb_cf_error_msg').html(eb_custom_fields.dialog_option_validation);
							$('.eb-cf-loader').remove();
							return;
						}

	                	var data_array = eb_cf_get_pop_up_data();
						data_array['edit'] = 1;
						data_array['old_name'] = old_name;
						eb_cf_save_data(data_array, edit_row_obj);
	                }
	            },
	            {
	            	text: eb_custom_fields.dialog_cancel_btn,
	            	class: 'eb-cf-dialog-cancel-btn',
	            	click: function(){
	            		// Empty the dialog box.
	                	eb_cf_clear_pop_up_fields_data();

	            		// now close the dialog box.
	                    $(this).dialog("close");
	            	}
	            }
	        ]
		});
    });



    function validate_field_name(dialog_value, row_obj){
		var msg = 1;
    	parent_tr  = $(row_obj).parent().parent();
    	parent_tr  = $(parent_tr).find('.eb-cf-tbl-name');

    	if('' == dialog_value){
    		$('.eb_cf_error_msg').css('display', 'block');
		    msg = eb_custom_fields.dialog_field_name_validation;
    	} else {
		    $('.eb-cf-tbl-name').not(parent_tr).each(function(index, element){
		    	if (dialog_value == $(element).val()) {
    				$('.eb_cf_error_msg').css('display', 'block');
		    		msg = eb_custom_fields.dialog_field_name_validation;
		    		return;
		    	}
		    });
    	}

	    return msg;
	}


    $("input[name='eb-cf-dialog-name']").on({
	  keydown: function(e) {
	    if (e.which === 32)
	      return false;
	  },
	  change: function() {
	    this.value = this.value.replace(/\s/g, "");
	  }
	});


    // jQuery('.eb_eb_custom_fields_wrap').bind('DOMSubtreeModified', function(){
    /*jQuery('.eb_eb_custom_fields_wrap').bind('beforeunload', function(){

	});*/



    /* Create new row and add in table from the dialog data, on the clieck of ad new field button. */
    function eb_cf_create_new_row(data_array) {
    	
    	html = '<tr class="ui-sortable-handle">'+
    				'<td style="width: 5%; text-align:center;"> <span class="dashicons dashicons-menu"></span> </td>'+
					'<td><input type="checkbox" name="" class="eb_cf_bulk_action_cb"></td>'+
					'<td>'+
						'<span class="eb-cf-tbl-name-lbl">'+ data_array['name'] +'</span>'+
						'<input type="hidden" class="eb-cf-tbl-name" name="eb-cf-tbl-name[]" value="'+ data_array['name'] +'">'+
					'</td>'+
					'<td>'+
						'<span class="eb-cf-tbl-type-lbl">'+ data_array['type'] +'</span>'+
						'<input type="hidden" class="eb-cf-tbl-type" name="eb-cf-tbl-type[]" value="'+ data_array['type'] +'">'+
					'</td>'+
					'<td>'+
						'<span class="eb-cf-tbl-label-lbl">'+ data_array['label'] +'</span>'+
						'<input type="hidden" class="eb-cf-tbl-label" name="eb-cf-tbl-label[]" value="'+ data_array['label'] +'">'+
					'</td>'+
					'<td>'+
						'<span class="eb-cf-tbl-placeholder-lbl">'+ data_array['placeholder'] +'</span>'+
						'<input type="hidden" class="eb-cf-tbl-placeholder" name="eb-cf-tbl-placeholder[]" value="'+ data_array['placeholder'] +'">'+
					'</td>'+
					'<td>';


					if(data_array['required']){
						// html += '<span class="eb-cf-tbl-enabled-lbl"> <span class="dashicons dashicons-saved"></span> </span>';
						html += '<span class="eb-cf-tbl-required-lbl"> <span class="dashicons dashicons-saved"></span> </span>';
					} else {
						html += '<span class="eb-cf-tbl-required-lbl"> - </span>';
					}

					html +=	'<input type="hidden" class="eb-cf-tbl-required" name="eb-cf-tbl-required[]" value="'+ data_array['required'] +'">'+
					'</td>'+
					'<td>';


					if(data_array['enabled']){
						html += '<span class="eb-cf-tbl-enabled-lbl"> <span class="dashicons dashicons-saved"></span> </span>';
					} else {
						html += '<span class="eb-cf-tbl-enabled-lbl"> - </span>';
					}
					html += '<input type="hidden" class="eb-cf-tbl-enabled" name="eb-cf-tbl-enabled[]" value="'+ data_array['enabled'] +'">'+
					'</td>';

					// Check if the selected field is select if yes then create one associative array for it and save it in the input value with json encode function. 
					if('select' == data_array['type']){

					}

					/*'<td> <span class="eb-cf-edit button"> Edit </span> </td>'+*/
					html += '<td>'+
								'<span class="dashicons dashicons-edit-page eb-cf-edit"></span>'+
								'<span class="dashicons dashicons-trash eb-cf-remove"></span>'+
								'<input type="hidden" class="eb-cf-tbl-class" name="eb-cf-tbl-class[]" value="'+ data_array['class'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-default-val" name="eb-cf-tbl-default-val[]" value="' + data_array['default-val'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-sync-on-moodle" name="eb-cf-tbl-sync-on-moodle[]" value="'+ data_array['sync-on-moodle'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-checkout" name="eb-cf-tbl-checkout[]" value="'+ data_array['checkout'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-woo-reg" name="eb-cf-tbl-woo-reg[]" value="'+ data_array['woo-reg'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-woo-my-accnt" name="eb-cf-tbl-woo-my-accnt[]" value="'+ data_array['woo-my-accnt'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-eb-reg" name="eb-cf-tbl-eb-reg[]" value="'+ data_array['eb-reg'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-eb-user-accnt" name="eb-cf-tbl-eb-user-accnt[]" value="'+ data_array['eb-user-accnt'] +'">'+
								'<input type="hidden" class="eb-cf-tbl-options" name="eb-cf-tbl-options[]" value="'+ data_array['options'] +'">'+
							'</td>';
					html += '</tr>';
		$('.eb_custom_field_tbl tbody').append(html);

    }

    // This function used for setting pop-up data with the existing available data .
    function eb_cf_set_pop_up_data(edit_obj)
    {
    	parent_tr = $(edit_obj).parent().parent();
    	$('select[name="eb-cf-dialog-type"]').val($(parent_tr).find('.eb-cf-tbl-type').val());
    	$('input[name="eb-cf-dialog-name"]').val($(parent_tr).find('.eb-cf-tbl-name').val());
    	$('input[name="eb-cf-dialog-label"]').val($(parent_tr).find('.eb-cf-tbl-label').val());
    	$('input[name="eb-cf-dialog-placeholder"]').val($(parent_tr).find('.eb-cf-tbl-placeholder').val());
    	$('input[name="eb-cf-dialog-class"]').val($(parent_tr).find('.eb-cf-tbl-class').val());
    	$('input[name="eb-cf-dialog-default-val"]').val($(parent_tr).find('.eb-cf-tbl-default-val').val());


	   	$('input[name="eb-cf-dialog-enabled"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-required"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-sync-moodle"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-checkout"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-woo-reg"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-woo-my-accnt"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-eb-reg"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-eb-user-accnt"]').prop('checked', false);

    	// If field type is select then set the dialog box options data.

       	if ($(parent_tr).find('.eb-cf-tbl-enabled').val() != 0) {
	    	$('input[name="eb-cf-dialog-enabled"]').prop('checked', true);
    	}

    	if ($(parent_tr).find('.eb-cf-tbl-required').val() != 0) {
	    	$('input[name="eb-cf-dialog-required"]').prop('checked', true);
    	}

    	if ($(parent_tr).find('.eb-cf-tbl-sync-on-moodle').val() != 0) {
    		$('input[name="eb-cf-dialog-sync-moodle"]').prop('checked', true);
    	}

    	if ($(parent_tr).find('.eb-cf-tbl-checkout').val() != 0) {
    		$('input[name="eb-cf-dialog-checkout"]').prop('checked', true);
    	}
		if ($(parent_tr).find('.eb-cf-tbl-woo-reg').val() != 0) {
    		$('input[name="eb-cf-dialog-woo-reg"]').prop('checked', true);
    	}
		if ($(parent_tr).find('.eb-cf-tbl-woo-my-accnt').val() != 0) {
    		$('input[name="eb-cf-dialog-woo-my-accnt"]').prop('checked', true);
    	}
		if ($(parent_tr).find('.eb-cf-tbl-eb-reg').val() != 0) {
    		$('input[name="eb-cf-dialog-eb-reg"]').prop('checked', true);
    	}
		

    	if ($(parent_tr).find('.eb-cf-tbl-eb-user-accnt').val() != 0) {
    		$('input[name="eb-cf-dialog-eb-user-accnt"]').prop('checked', true);
    	}

    	if ('select' == $(parent_tr).find('.eb-cf-tbl-type').val()) {
    		// If selected type is select then start to show the options fields, Get options value and show them.
    		var options = $(parent_tr).find('.eb-cf-tbl-options').val();
    		options = JSON.parse(options.replace(/\'/g, '"'));

	    	if($('.eb-cf-pop-up-options-field').length){
				$('.eb-cf-pop-up-options-field').css('display', 'flex');
	    		$('.eb-cf-pop-up-options-field').removeClass('eb-cf-hide');


	    		// Below code removes all option fields except the first one.
	    		var counter = 1;
	    		$('.eb-cf-pop-up-options-field').each(function(index, element){
	    			if(counter > 1){
	    				$(element).remove();
	    			}
	    			counter++;
	    		});


	    		// Now as in above code all options fields are removed lets add it again one by one according to the options provided.
	    		var counter = 0;

				$.each(options, function(index, value) {
	    			if (counter == 0) {
			    		$('.eb-cf-pop-up-options-field').find('.eb-cf-dialog-option-val').val(index);
			    		$('.eb-cf-pop-up-options-field').find('.eb-cf-dialog-option-txt').val(value);
	    			} else {
	    				var html_content = eb_cf_get_dynamic_options(index, value);
        				$('.eb-cf-pop-up-options-field:last').after(html_cont);
	    			}

	    			counter++;
	    		});

	    	}
    	}

    }

    /* On the click of Edit button this function modifies the data in the table  */
    function eb_cf_modify_existing_row(edit_obj)
    {
    	parent_tr  = $(edit_obj).parent().parent();

    	$(parent_tr).find('.eb-cf-tbl-type').val($('select[name="eb-cf-dialog-type"]').val());
    	$(parent_tr).find('.eb-cf-tbl-type-lbl').html($('select[name="eb-cf-dialog-type"]').val());

    	$(parent_tr).find('.eb-cf-tbl-name').val($('input[name="eb-cf-dialog-name"]').val());
    	$(parent_tr).find('.eb-cf-tbl-name-lbl').html($('input[name="eb-cf-dialog-name"]').val());
    	
    	$(parent_tr).find('.eb-cf-tbl-label').val($('input[name="eb-cf-dialog-label"]').val());
    	$(parent_tr).find('.eb-cf-tbl-label-lbl').html($('input[name="eb-cf-dialog-label"]').val());
    	
    	$(parent_tr).find('.eb-cf-tbl-placeholder').val($('input[name="eb-cf-dialog-placeholder"]').val());
    	$(parent_tr).find('.eb-cf-tbl-placeholder-lbl').html($('input[name="eb-cf-dialog-placeholder"]').val());

    	$(parent_tr).find('.eb-cf-tbl-class').val($('input[name="eb-cf-dialog-class"]').val());
    	$(parent_tr).find('.eb-cf-tbl-default-val').val($('input[name="eb-cf-dialog-default-val"]').val());

		/*eb-cf-tbl-validation-lbl*/
	    $(parent_tr).find('.eb-cf-tbl-enabled').val(0);
    	$(parent_tr).find('.eb-cf-tbl-enabled-lbl').html('-');
    	$(parent_tr).find('.eb-cf-tbl-required').val(0);
    	$(parent_tr).find('.eb-cf-tbl-required-lbl').html('-');
		$(parent_tr).find('.eb-cf-tbl-sync-on-moodle').val(0);
    	$(parent_tr).find('.eb-cf-tbl-checkout').val(0);
    	$(parent_tr).find('.eb-cf-tbl-woo-reg').val(0);
    	$(parent_tr).find('.eb-cf-tbl-woo-my-accnt').val(0);
    	$(parent_tr).find('.eb-cf-tbl-eb-reg').val(0);
    	$(parent_tr).find('.eb-cf-tbl-eb-user-accnt').val(0);

    	/*eb-cf-tbl-enabled-lbl*/
		if( $('input[name="eb-cf-dialog-enabled"]').prop("checked") == true ) {
	    	$(parent_tr).find('.eb-cf-tbl-enabled').val(1);
    		$(parent_tr).find('.eb-cf-tbl-enabled-lbl').html('<span class="dashicons dashicons-saved"></span>');
    	}

    	if( $('input[name="eb-cf-dialog-required"]').prop("checked") == true ) {
	    	$(parent_tr).find('.eb-cf-tbl-required').val(1);
    		$(parent_tr).find('.eb-cf-tbl-required-lbl').html('<span class="dashicons dashicons-saved"></span>');
    	} 

    	if ($('input[name="eb-cf-dialog-sync-moodle"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-cf-tbl-sync-on-moodle').val(1);
    	}

    	if ($('input[name="eb-cf-dialog-checkout"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-cf-tbl-checkout').val(1);
    	}

		if ($('input[name="eb-cf-dialog-woo-reg"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-cf-tbl-woo-reg').val(1);
    	}

		if ($('input[name="eb-cf-dialog-woo-my-accnt"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-cf-tbl-woo-my-accnt').val(1);
    	}

		if ($('input[name="eb-cf-dialog-eb-reg"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-cf-tbl-eb-reg').val(1);
    	}

    	if ($('input[name="eb-cf-dialog-eb-user-accnt"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-cf-tbl-eb-user-accnt').val(1);
    	}

    	data_array = {};
    	// Creating options array if selected field type is select
    	if ('select' == $('select[name="eb-cf-dialog-type"]').val()) {
	    	jQuery('.eb-cf-pop-up-options-field').each(function(index, element){
	    		if ('' != $(element).find('.eb-cf-dialog-option-val').val()) {
					data_array[$(element).find('.eb-cf-dialog-option-val').val()] = $(element).find('.eb-cf-dialog-option-txt').val();
	    		}
			});

    		// data_array['options'] = JSON.stringify(data_array['options']).replace('"', "'");
    		data_array = JSON.stringify(data_array,null,0).replace(/\"/g, "'");
	    	$(parent_tr).find('.eb-cf-tbl-options').val(data_array);
    	}

    }

    /* This function create data for the pop-up and returns it.*/
    function eb_cf_get_pop_up_data()
    {
	    data_array = {};
    	data_array['type']               = $('select[name="eb-cf-dialog-type"]').val();
    	data_array['name']               = $('input[name="eb-cf-dialog-name"]').val();
    	data_array['label']              = $('input[name="eb-cf-dialog-label"]').val();
    	data_array['placeholder']        = $('input[name="eb-cf-dialog-placeholder"]').val();
    	data_array['default-val']        = $('input[name="eb-cf-dialog-default-val"]').val();
    	data_array['class']              = $('input[name="eb-cf-dialog-class"]').val();

    	data_array['enabled']            = 0;
    	data_array['required']           = 0;
    	data_array['sync-on-moodle']     = 0;
		data_array['checkout']           = 0;
		data_array['woo-reg']            = 0;
    	data_array['woo-my-accnt']       = 0;
		data_array['eb-reg']             = 0;
    	data_array['eb-user-accnt']      = 0;
    	data_array['options']            = {};

    	// Creating options array if selected field type is select
    	if ('select' == data_array['type']) {
	    	jQuery('.eb-cf-pop-up-options-field').each(function(index, element){
				data_array['options'][$(element).find('.eb-cf-dialog-option-val').val()] = $(element).find('.eb-cf-dialog-option-txt').val();
			});
    		data_array['options'] = JSON.stringify(data_array['options'],null,0).replace(/\"/g, "'");
    	}

    	if( $('input[name="eb-cf-dialog-enabled"]').prop("checked") == true ) {
    		data_array['enabled'] = 1;
    	}

    	if( $('input[name="eb-cf-dialog-required"]').prop("checked") == true ) {
    		data_array['required'] = 1;
    	}

    	if( $('input[name="eb-cf-dialog-sync-moodle"]').prop("checked") == true ) {
    		data_array['sync-on-moodle'] = 1;
    	}

		if( $('input[name="eb-cf-dialog-checkout"]').prop("checked") == true ) {
    		data_array['checkout'] = 1;
    	}

		if( $('input[name="eb-cf-dialog-woo-reg"]').prop("checked") == true ) {
    		data_array['woo-reg'] = 1;
    	}

    	if( $('input[name="eb-cf-dialog-woo-my-accnt"]').prop("checked") == true ) {
    		data_array['woo-my-accnt'] = 1;
    	}

		if( $('input[name="eb-cf-dialog-eb-reg"]').prop("checked") == true ) {
    		data_array['eb-reg'] = 1;
    	}

    	if( $('input[name="eb-cf-dialog-eb-user-accnt"]').prop("checked") == true ) {
    		data_array['eb-user-accnt'] = 1;
    	}

    	return data_array;
    }

    /* This function clears all the data added in the pop-up. */
    function eb_cf_clear_pop_up_fields_data()
    {
    	var data_array = {
    		'select[name="eb-cf-dialog-type"]' : 'text',
    		'input[name="eb-cf-dialog-name"]' : '',
    		'input[name="eb-cf-dialog-label"]' : '',
    		'input[name="eb-cf-dialog-default-val"]' : '',
    		'input[name="eb-cf-dialog-placeholder"]' : '',
    		'input[name="eb-cf-dialog-class"]' : '',
    		'input[name="eb-cf-dialog-enabled"]' : 0,
    		'input[name="eb-cf-dialog-required"]' : 0,
    		'input[name="eb-cf-dialog-sync-mooodle"]' : 0,
    		'input[name="eb-cf-dialog-checkout"]' : 0,
    		'input[name="eb-cf-dialog-woo-reg"]' : 0,
			'input[name="eb-cf-dialog-woo-my-accnt"]' : 0,
			'input[name="eb-cf-dialog-eb-reg"]' : 0,
			'input[name="eb-cf-dialog-eb-user-accnt"]' : 0,
    	};

    	$.each(data_array, function(index, value){
    		$(index).val(value);
    	});

    	// Clearing checkboxes data.
    	$('input[name="eb-cf-dialog-enabled"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-required"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-sync-moodle"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-checkout"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-woo-reg"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-woo-my-accnt"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-eb-reg"]').prop('checked', false);
    	$('input[name="eb-cf-dialog-eb-user-accnt"]').prop('checked', false);

    	// remove extraa added options.
    	if($('.eb-cf-pop-up-options-field').length){
			$('.eb-cf-pop-up-options-field').css('display', 'none');
    		if (!$('.eb-cf-pop-up-options-field').hasClass('eb-cf-hide')) {
	    		$('.eb-cf-pop-up-options-field').addClass('eb-cf-hide');
    		}

    		var counter = 1;
    		$('.eb-cf-pop-up-options-field').each(function(index, element){
    			if(counter == 1){
		    		$(element).find('.eb-cf-dialog-option-val').val('');
		    		$(element).find('.eb-cf-dialog-option-txt').val('');
    			} else {
    				$(element).remove();
    			}
    			counter++;
    		});
    	}
    }

	/* This function send ajax request to save custom field data. */
	function eb_cf_save_data(data_array, obj = false) {
		$.ajax({
			url: eb_custom_fields.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				'action': 'eb_cf_save_data',
				'nonce': eb_custom_fields.nonce,
				'data': data_array
			},
			success: function(response) {
				if(response.status == 'success'){
					if(obj == false){
						eb_cf_create_new_row(data_array);
					} else {
						eb_cf_modify_existing_row(obj);
					}
					$('.eb-cf-loader').remove();
					$('.eb-cf-dialog-btn').text(eb_custom_fields.dialog_success_btn);
					// close dialog box after 1 second.
					setTimeout(function(){
						$('.eb-cf-pop-up-cont').dialog("close");
					}, 1000);
				} else {
					$('.eb_cf_delete_error_msg').css('display', 'block');
					$('.eb_cf_error_msg').html(response.message);
					$('.eb-cf-loader').remove();
				}
			}
		});
	}

    /* This function shows drop down options if type is select */
    $(document).on('change', 'select[name="eb-cf-dialog-type"]', function(event){
		event.preventDefault();
    	if ('select' == $(this).val()) {
    		$('.eb-cf-pop-up-options-field').css('display', 'flex');
    		$('.eb-cf-dialog-placeholder-wrap').css('display', 'none');
    		$('.eb-cf-dialog-def-val-wrap').css('display', 'flex');
    	} else if('checkbox' == $(this).val()) {
    		$('.eb-cf-dialog-placeholder-wrap').css('display', 'none');
    		$('.eb-cf-pop-up-options-field').css('display', 'none');
    		$('.eb-cf-dialog-def-val-wrap').css('display', 'none');
    	} else if('date' == $(this).val()) {
            $('.eb-cf-dialog-placeholder-wrap').css('display', 'none');
    		$('.eb-cf-pop-up-options-field').css('display', 'none');
    		$('.eb-cf-dialog-def-val-wrap').css('display', 'none');
        } else {
    		$('.eb-cf-dialog-placeholder-wrap').css('display', 'flex');
    		$('.eb-cf-pop-up-options-field').css('display', 'none');
    		$('.eb-cf-dialog-def-val-wrap').css('display', 'flex');
    	}
    });


    /* This function ads new options row dynamically. */
	$(document).on('click','.eb-cf-dialog-option-add-new',function(event){
		event.preventDefault();
    	// creating html sturcture to add new field.
    	html_cont = eb_cf_get_dynamic_options();

        options_parent = $(this).parent().parent();
        options_parent = options_parent.parent();
        options_parent.after(html_cont);

    });

	/* This is the html structure for options. */
	function eb_cf_get_dynamic_options(option_val = '', option_text='')
	{
		html_cont = '<div class="eb-cf-pop-up-fields eb-cf-pop-up-options-field">'+
                    '<div class="eb-cf-pop-up-lbl">'+
                        '<label></label>'+
					'</div>'+
                    '<div class="eb-cf-pop-up-right-field">'+
                    	'<input value="'+ option_val +'" class="eb-cf-dialog-option-inp eb-cf-dialog-option-val" type="text" name="eb-cf-dialog-option-val[]" placeholder="'+ eb_custom_fields.dialog_option_value +'">'+
                    	'<input value="'+ option_text +'" class="eb-cf-dialog-option-inp eb-cf-dialog-option-txt" type="text" name="eb-cf-dialog-option-txt[]" placeholder="'+ eb_custom_fields.dialog_option_text +'">'+
                    	'<span class="eb-cf-dialog-option-btn">'+
                    		'<span class="eb-cf-dialog-option-add-new dashicons dashicons-plus-alt"></span>'+
                    		'<span class="eb-cf-dialog-option-remove dashicons dashicons-dismiss"></span>'+
                    	'</span>'+
                	'</div>'+
                '</div>';

        return html_cont;
	}

	/* This function removes the added dynamic options  */
	$(document).on('click','.eb-cf-dialog-option-remove',function(event){
		event.preventDefault();
		options_parent = $(this).parent().parent();
        options_parent = options_parent.parent();
        options_parent.remove();

	});

	/* This function just empties the first option element as we are showing by default one option  */
	$(document).on('click','.eb-cf-dialog-option-first-remove',function(event){
		event.preventDefault();
		options_parent = $(this).parent().parent();
		options_parent.find('.eb-cf-dialog-option-val').val('');
		options_parent.find('.eb-cf-dialog-option-txt').val('');

        // options_parent = options_parent.parent();

	});

	/* This the function which is used to check all checkboxes on the click of header checkbox. */
	$(document).on('click', '.eb_cf_bulk_action_header_cb', function(event){
		// event.preventDefault();

		if($(this).prop("checked") == true){
            $('.eb_cf_bulk_action_cb').prop('checked', true);
        } else {
            $('.eb_cf_bulk_action_cb').prop('checked', false);
        }
	});

	/* function to handle Bulk actions */
	$(document).on('click', '.eb_cf_bulk_action_btns', function(event){
		event.preventDefault();

		var bulk_action = $(this).parent().find('.eb_cf_bulk_action_select').val();
		if ( bulk_action != '' ) {

			var checked_rows = $('.eb_cf_bulk_action_cb:checkbox:checked');
			var checked_fields = [];
			$.each(checked_rows, function(key, value) {
				value = $(value).parent().parent();

				var field_name = value.find('.eb-cf-tbl-name').val();
				checked_fields.push(field_name);

			});

			$.ajax({
				url: eb_custom_fields.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'eb_cf_bulk_action',
					nonce: eb_custom_fields.nonce,
					bulk_action: bulk_action,
					custom_fields: checked_fields,
				},
				success: function(response) {
					if ( response.status == 'success' ) {
						var checked_rows = $('.eb_cf_bulk_action_cb:checkbox:checked');
						$.each(checked_rows, function(key, value) {
							value = $(value).parent().parent();
							var row_enable_field = $(value).find('.eb-cf-tbl-enabled');

							if ( 'enable' == bulk_action ) {

								if ($(value).hasClass('eb-cf-disable-row')) {
									$(value).removeClass('eb-cf-disable-row');
								}

								// Set hidden input value of the enable input field accordung the bulk action value.
								row_enable_field.val(1);

								// Now set the field value which is shown in the table.
								row_enable_field.parent().find('.eb-cf-tbl-enabled-lbl').html('<span class="dashicons dashicons-saved"></span>');

							} else if ( 'disable' == bulk_action ) {
								$(value).addClass('eb-cf-disable-row');
								// Set input value of the enable input field accordung the bulk action value.
								row_enable_field.val(0);

								// Now set the field value which is shown in the table.
								row_enable_field.parent().find('.eb-cf-tbl-enabled-lbl').html(' - ');

							} else if ( 'delete' == bulk_action ) {
								$(value).css('background-color', '#f3d8d8');

								// If action selected is 'remove' then remove element from dom with fadeout effect
								$(value).fadeOut(500, function(){ 
									$(value).remove();
								});
							}
						});
					}
				}
			});
		}
	});

				

	// 		});
	// 	}
	// });



	$(document).on('click', '.eb-cf-remove', function(){
		var name = $(this).parent().parent().find('.eb-cf-tbl-name').val();
		var del_obj = this;
		$('.eb_cf_delete_error_msg').css('display', 'none');
		$('.eb-cf-pop-up-delete').dialog({
		    width: 700,
		    modal: true,
		    resizable: false,
		    dialogClass: 'eb-cf-dialog',
		    buttons: [
	            {
	                text: eb_custom_fields.dialog_delete_btn,
	                class: 'eb-cf-dialog-btn button-primary',
	                click: function () {
	                	$.ajax({
							url: eb_custom_fields.ajax_url,
							type: 'POST',
							dataType: 'json',
							data: {
								'action': 'eb_cf_delete_field',
								'nonce': eb_custom_fields.nonce,
								'name': name
							},
							success: function(response) {
								if(response.status == 'success'){
									$(del_obj).parent().parent().remove();
									$('.eb-cf-pop-up-delete').dialog("close");
								} else {
									$('.eb_cf_delete_error_msg').css('display', 'block');
									$('.eb_cf_delete_error_msg').html(response.message);
								}
								
							}
						});
	                }
	            },
	            {
	            	text: eb_custom_fields.dialog_cancel_btn,
	            	class: 'eb-cf-dialog-cancel-btn',
	            	click: function(){
	                    $(this).dialog("close");
	            	}
	            }
	        ]
		});
	});
});
