(function( jQuery ) {
	'use strict';

	jQuery(document).ready(function($){
		var _custom_media = true,
		_orig_send_attachment = wp.media.editor.send.attachment;
		 
		jQuery('.single_image_upload .uploder_with_btn .button').click(function(e) {
			var send_attachment_bkp = wp.media.editor.send.attachment;
			var button = jQuery(this);
			var id = button.attr('id').replace('_button', '');
			_custom_media = true;

		wp.media.editor.send.attachment = function(props, attachment){
			if ( _custom_media ) {
			jQuery("#"+id).val(attachment.url);
			jQuery('#'+id).parent('.uploder_with_btn').next().find('img').attr('src', attachment.url).show();

			if( id == 'fill_loader_thumb' ) {
				jQuery('#wppu-fill-loader-thunb').find('img').attr('src', attachment.url).show();
				jQuery('#wppu-fill-loader-thunb').find('div.wppu-fill-thumbnail-fill').css('background-image', 'url('+attachment.url+')');
			} else if( id == 'object_loader_thumb' ) {
				jQuery('#wppu-object-wrapper').find('img').attr('src', attachment.url).show();
			}

			} else {
			return _orig_send_attachment.apply( this, [props, attachment] );
			};
		}

		wp.media.editor.open(button);
			return false;
		});
		 
		jQuery('.add_media').on('click', function(){
			_custom_media = false;
		});
	});


	jQuery(window).load(function(){
	jQuery(window).resize(function(){
	   
	   var windowMinus = jQuery('#wpadminbar').height();
	   var WH = jQuery(window).height() - windowMinus;
	   jQuery('#loading-image').height(WH);

	})
	jQuery(window).resize();
	});

	jQuery(window).load(function(){
		jQuery('.notice').remove();
		jQuery('.notice').each(function(){
			jQuery(this).remove();
		});
		jQuery('.form_loading').delay(100).fadeOut('slow');
		jQuery('#background_sizer').parents('tr').css({
			'display': 'none'
		});
	});

	jQuery(function() {
		jQuery( "#wppCaccordion" ).accordion({
			collapsible: true, 
			active: false,
			heightStyle: "content"
		});
	});

	jQuery(document).ready(function(jQuery){
	jQuery(".tab_content").hide();
	jQuery(".tab_content:first").show();   
	jQuery('#schedule').on('change',function(e){
		var ID = jQuery('select[name=selector]').val();
	   	jQuery(".tab_content").slideUp(400).delay(400).siblings('.tab_content#'+ID).slideDown(400);
	});

	jQuery('#preloader_load_type').on('change',function(e){
		if( jQuery(this).val() == "custom" ) {
			jQuery("#custom_load_time-wrapper").slideDown(400).delay(400).siblings('.tab_content#'+ID).slideDown(400);
		} else {
			jQuery("#custom_load_time-wrapper").slideUp(400).delay(400).siblings('.tab_content#'+ID).slideDown(400);
		}
	});

	jQuery(document).ready(function(jQuery){
		jQuery('#special_prev').closest('td').css('position', 'relative');
		jQuery('.defaultHide').closest('tr').hide();
		var bgImage = jQuery(".wow_wppu_preview");
		jQuery('#main_bgColor').wpColorPicker({
		change: function( event, ui ) {
		   bgImage.css('background-color', ui.color.toString());
		},
		clear: function() {
		   bgImage.css('background-color', '');
		}
		});

		var special_text = jQuery(".h-p");
		jQuery('#special_color').wpColorPicker({
		change: function( event, ui ) {
		   special_text.css('color', ui.color.toString());
		},
		clear: function() {
		   special_text.css('color', '');
		}
		});

		var sepecial_background = jQuery(".wppu-preloader");
		jQuery('#special_background').wpColorPicker({
		change: function( event, ui ) {
		   sepecial_background.css('background-color', ui.color.toString());
		},
		clear: function() {
		   sepecial_background.css('background-color', '');
		}
		});

		var threed_text = jQuery(".wppu-3d-loader-item");
		jQuery('#3d_loader_color').wpColorPicker({
		change: function( event, ui ) {
		   threed_text.css('color', ui.color.toString());
		},
		clear: function() {
		   threed_text.css('color', '');
		}
		});

		jQuery('#3d_loader_background').wpColorPicker({
		change: function( event, ui ) {
		   threed_text.css('background-color', ui.color.toString());
		},
		clear: function() {
		   threed_text.css('background-color', '');
		}
		});

		var three_background_main = jQuery(".wppu-threed-loader-con");
		jQuery('#3d_loader_background_con').wpColorPicker({
		change: function( event, ui ) {
		   three_background_main.css('background-color', ui.color.toString());
		},
		clear: function() {
		   three_background_main.css('background-color', '');
		}
		});

		var three_background = jQuery(".wppu-3d-loader-item");
		jQuery('#3d_loader_background').wpColorPicker({
		change: function( event, ui ) {
		   three_background.css('background-color', ui.color.toString());
		},
		clear: function() {
		   three_background.css('background-color', '');
		}
		});

		var trendy_background = jQuery(".trendy-preloader-main-con");
		jQuery('#trendy_background').wpColorPicker({
		change: function( event, ui ) {
		   trendy_background.css('background-color', ui.color.toString());
		},
		clear: function() {
		   trendy_background.css('background-color', '');
		}
		});

		jQuery('#trendy_textcolor').wpColorPicker({
		change: function( event, ui ) {
		   jQuery('#trendy_main_container .trendy_loading_text').css('color', ui.color.toString());
		},
		clear: function() {
		   jQuery('#trendy_main_container .trendy_loading_text').css('color', '');
		}
		});

		jQuery('#pace_color').wpColorPicker();
		jQuery('#pace_background').wpColorPicker();

		var fill_background = jQuery("#fill_loader_preview .wppu-preloader");
		jQuery('#fill_loader_background').wpColorPicker({
		change: function( event, ui ) {
		   fill_background.css('background-color', ui.color.toString());
		},
		clear: function() {
		   fill_background.css('background-color', '');
		}
		});

		var fill_text_color = jQuery("#wppu-fill-loader-text h2.wppu_fill_h2_attr");
		jQuery('#fill_loader_text_color').wpColorPicker({
		change: function( event, ui ) {
		   fill_text_color.css('color', ui.color.toString());
		},
		clear: function() {
		   fill_text_color.css('color', '');
		}
		});

		var progess_bar_bg = jQuery(".wppu_fill_progress_bar");
		jQuery('#fill_progress_bar_bg').wpColorPicker({
		change: function( event, ui ) {
		   progess_bar_bg.css('background-color', ui.color.toString());
		},
		clear: function() {
		   progess_bar_bg.css('background-color', '');
		}
		});

		var object_bg_color = jQuery(".object-wppu-preview");
		jQuery('#object_wrapper_bg').wpColorPicker({
		change: function( event, ui ) {
		   object_bg_color.css('background-color', ui.color.toString());
		},
		clear: function() {
		   object_bg_color.css('background-color', '');
		}
		});

		var object_main_color = jQuery(".wpppu-object-wrap");
		jQuery('#object_main_color').wpColorPicker({
		change: function( event, ui ) {
		   object_main_color.css('color', ui.color.toString());
		},
		clear: function() {
		   object_main_color.css('color', '');
		}
		});

		var counter_color = jQuery(".wppu_fill_counter");
		jQuery('#fill_counter_text_color').wpColorPicker({
		change: function( event, ui ) {
		   counter_color.css('color', ui.color.toString());
		},
		clear: function() {
		   counter_color.css('color', '');
		}
		});

		jQuery('#before_server_render input[type="radio"]').change(function() {
			console.log( jQuery(this).val() );
			if( jQuery(this).val() === 'enable' ) {
				jQuery(this).closest('.pixiefy_signle_column_content').find('.pixiefy_parents_lists').slideDown();
			} else {
				jQuery(this).closest('.pixiefy_signle_column_content').find('.pixiefy_parents_lists').slideUp();
			}
        });

	});
	jQuery(document).ready(function () {
	    jQuery("#ckAllPages").change(function () {
	        jQuery(".checkPages").prop('checked', jQuery(this).prop('checked'));
	        var sel = jQuery('.all_pages_checkbox_group input.checkPages:checked').map(function(_, el) {
		        return jQuery(el).attr('id');
		    }).get();
		    jQuery('input#get_all_pages').val(sel.join( ", " ));
		    var ID = jQuery('.all_pages_checkbox_group input.checkPages:checked').map(function(_, el) {
		        return jQuery(el).attr('data-id');
		    }).get();
		    jQuery('input#get_all_pages_ID').val(ID.join( ", " ));
	    });
	    jQuery("#ckAllPosts").change(function () {
	        jQuery(".checkPosts").prop('checked', jQuery(this).prop('checked'));
	        var sel = jQuery('.all_post_types_checkbox_group input.checkPosts:checked').map(function(_, el) {
		        return jQuery(el).attr('id');
		    }).get();
		    jQuery('input#get_all_post_type').val(sel.join( ", " ));
		    var ID = jQuery('.all_post_types_checkbox_group input.checkPosts:checked').map(function(_, el) {
		        return jQuery(el).attr('data-id');
		    }).get();
		    jQuery('input#get_all_post_type_list').val(ID.join( ", " ));
	    });
	});
	jQuery(document).ready(function(jQuery){
		jQuery('#main_option_active input:radio').change(
		function(){
		if (jQuery(this).val() == 'image_radio_options') {
			jQuery('.hideBar').closest('tr').slideUp('slow');
			jQuery('.main_ops').show();
			var val = jQuery(this).val();
			jQuery('#activated_link').attr('href', '?page=wppu_options&tab=image_options');
			jQuery('#activated_link').text('Image Options');
		    jQuery('.option_name_activated').text('Image Options');
		} else if (jQuery(this).val() == 'css_radio_options') {
			jQuery('.hideBar').closest('tr').slideUp('slow');
			jQuery('.main_ops').show();
			var val = jQuery(this).val();
			jQuery('#activated_link').attr('href', '?page=wppu_options&tab=css_options');
		    jQuery('#activated_link').text('Css Options');
		    jQuery('.option_name_activated').text('Css Options');
		} else if (jQuery(this).val() == 'special_radio_options') {
			jQuery('.hideBar').closest('tr').slideUp('slow');
			jQuery('.main_ops').show();
			var val = jQuery(this).val();
			jQuery('#activated_link').attr('href', '?page=wppu_options&tab=special_options');
		    jQuery('#activated_link').text('Special Options');
		    jQuery('.option_name_activated').text('Sepcial Options');
		} else if (jQuery(this).val() == 'pace_radio_options') {
			jQuery('.hideBar').closest('tr').slideUp('slow');
			jQuery('.main_ops').show();
			var val = jQuery(this).val();
			jQuery('#activated_link').attr('href', '?page=wppu_options&tab=pace_options');
		    jQuery('#activated_link').text('Pace jQuery');
		    jQuery('.option_name_activated').text('Pace jQuery');
		} else if (jQuery(this).val() == 'custom_your_options') {
			jQuery('.hideBar').closest('tr').slideDown('slow');
			var val = jQuery(this).val();
			jQuery('.main_ops').hide();
			jQuery('#activated_link').attr('href', '?page=wppu_options&tab=display_options');
		    jQuery('#activated_link').text('Customs Options');
		    jQuery('.option_name_activated').text('Customs Options');
		}
		});
	});

	jQuery("#clear_color").on("click", function(event){
		var bgImage = jQuery(".wow_wppu_preview");
		jQuery('#main_bgColor').attr("value","transparent");
		bgImage.css('background-color', 'transparent');
	});

	jQuery("#remove_bg").on("click", function(event){
		jQuery(this).prev().children('.wp-color-result').css('background-color', 'transparent');
		jQuery('#pace_background').attr("value","transparent");
	});

	jQuery(window).load(function () {
	  	jQuery(function(){
		    var dur = 10000;
		    jQuery( ".wppu_special span .h-p" ).animate({
			    width: "100%"}, {
			    duration: dur
		    });
		});

		jQuery('#myModalTabs .error, #myModalTabs .notice').remove();
	});
	jQuery('#run_animation').on('click', function(){
		jQuery( ".wppu_special span .h-p" ).animate({width: 0});
		jQuery( ".wppu_special span .h-p" ).animate({width: "100%"}, { duration: 10000 });
	});

	jQuery(document).on('click', '#run_animation_fill', function(){

		if (jQuery('#run_animation_fill').hasClass('wppu_click_disable')) {
			return false;
		}
		jQuery('#run_animation_fill').addClass('wppu_click_disable');
		setTimeout(function(){
		  jQuery('#run_animation_fill').removeClass('wppu_click_disable');
		}, 10000);

		if( jQuery('#wppu-fill-loader-text').length > 0  ) {
			jQuery( "#wppu-fill-loader-text h2.wppu_fill_h2_attr" ).animate({width: 0}).animate({width: "100%"}, { duration: 10000 });
		}
		if( jQuery('#wppu-fill-loader-thunb').length > 0  ) {
			jQuery( "#wppu-fill-loader-thunb .wppu-fill-thumbnail-fill" ).animate({height: 0}).animate({height: "100%"}, { duration: 10000 });
		}
		if( jQuery('.wppu_fill_counter').length > 0  ) {

			jQuery('.wppu_fill_counter span').each(function () {
			  var $this = jQuery(this);
			  jQuery({ Counter: 0 }).animate({ Counter: 100 }, {
			    duration: 10000,
			    easing: 'swing',
			    step: function () {
			      $this.text(Math.ceil(this.Counter));
			    }
			  });
			});

		}

		if ( jQuery('.wppu_fill_progress_bar').length > 0 ) {
			jQuery( ".wppu_fill_progress_bar" ).animate({width: 0}).animate({width: "100%"}, { duration: 10000 });
		}

		
	});



	jQuery('#fill_counter_enable').on('change', function(){
		if(jQuery(this).is(":checked")) {
			jQuery('.wppu_fill_counter').show();
		} else {
			jQuery('.wppu_fill_counter').hide();
		}
	});




	jQuery('#fill_progress_bar_pos').on('change', function(){
		var value = jQuery(this).val();
		jQuery('.wppu_fill_progress_bar').removeClass (function (index, className) {
		    return (className.match (/(^|\s)wppu_fill_progress_pos-\S+/g) || []).join(' ');
		}).addClass('wppu_fill_progress_pos-' + value);
	});

	jQuery(document).ready(function() {

		jQuery('#awesome_background').wpColorPicker({
				change: function( event, ui ) {
					jQuery('.awesome_limon_prv').css('background-color', ui.color.toString());
				}
			});
		jQuery('#awesome_textcolor').wpColorPicker({
			change: function( event, ui ) {
				jQuery('.awesome_title_txt').css('color', ui.color.toString());
			}
		});
		jQuery('#awesome_mainColor').wpColorPicker({
			change: function( event, ui ) {
				jQuery('.item-generate').css('color', ui.color.toString());
				jQuery('.item-generate.la-ball-atom div:nth-child(1)').css('background-color', ui.color.toString());
				jQuery('.wppu-loader-inner').children().css('border-color', ui.color.toString());
			}
		});
		function hasWhiteSpace(s) {
		  return /\s/g.test(s);
		}
		jQuery('input#special_text').change(function(){
		    jQuery('.wppu_special_preloader > .wppu_special').empty();
		    var str = jQuery(this).val();
		    for (var i = 0; i < str.length; i++) {
		    	if ( hasWhiteSpace(str[i]) ) {
		    		var string = '&nbsp;';
		    	} else {
		    		var string = str[i];
		    	}
		        jQuery('.wppu_special_preloader > .wppu_special').append('<span class="span-'+i+'"><p class="t-p">'+string+'</p><p class="h-p">'+string+'</p></span>');
		    }
		    var special_text = jQuery(".h-p");
			jQuery('#special_color').wpColorPicker({
			change: function( event, ui ) {
			   special_text.css('color', ui.color.toString());
			},
			clear: function() {
			   special_text.css('color', '');
			}
			});
	    });

	    jQuery("input#fill_loader_text").bind("change paste keyup", function() {
		   var txt = jQuery(this).val();
		    jQuery('#wppu-fill-loader-text h2').text(txt);
		});

	    jQuery('input#3d_loader_text').change(function(){
		    jQuery('.wppu_special_preloader > .wppu-3d-loader').empty();
		    var str = jQuery(this).val();
		    for (var i = 0; i < str.length; i++) {
		        jQuery('.wppu_special_preloader > .wppu-3d-loader').append('<span class="wppu-3d-loader-item">'+str[i]+'</span>');
		    }
		    var threed_text = jQuery(".wppu-3d-loader-item");
			jQuery('#3d_loader_color').wpColorPicker({
			change: function( event, ui ) {
			   threed_text.css('color', ui.color.toString());
			},
			clear: function() {
			   threed_text.css('color', '');
			}
			});
			jQuery('#3d_loader_background').wpColorPicker({
			change: function( event, ui ) {
			   threed_text.css('background-color', ui.color.toString());
			},
			clear: function() {
			   threed_text.css('background-color', '');
			}
			});
	    });

	    jQuery('#3d_loader_animate_style').change(function(){
	    	var cclass = jQuery(this).val();
			jQuery('#wppu_3d_loader_body').attr('class', '');
			jQuery('#wppu_3d_loader_body').attr('class', 'wppu-3d-loader ' + cclass);
			
		});


	   if(jQuery('#background-size-2').is(':checked') && jQuery('#background-size-2').val() == 'custom') { jQuery('.custom_size_slider').show(); }

		jQuery('#size_selector input:radio').change(
		    function(){
		    if (jQuery(this).val() == 'custom') {
		    	var v =jQuery('#background_sizer').val();
		        jQuery('.custom_size_slider').slideDown(150);
		        jQuery('.wow_wppu_preview').css('background-size', v + ' auto');
		    }
		    else {
		        jQuery('.custom_size_slider').slideUp(150);
		        jQuery('.wow_wppu_preview').css('background-size', 'auto');
		    }
		 });

		if(jQuery('#wppu-fill-loader-type-2').is(':checked') && jQuery('#wppu-fill-loader-type-2').val() == 'image') { 
			jQuery('#wppu_fill_thumb_main').show();
		} else {
			jQuery('#wppu_fill_text_main').show(); 
		}

		jQuery('#fill-loader-type input:radio').change(
		    function(){
		    if (jQuery(this).val() == 'image') {
		    	jQuery('.wppu_fill_type_thumb_only').slideDown();
		    	jQuery('.wppu_fill_type_text_only').slideUp();
		    	jQuery('#wppu_fill_thumb_main').slideDown();
		    	jQuery('#wppu_fill_text_main').slideUp();
		    }
		    else {
		        jQuery('.wppu_fill_type_thumb_only').slideUp(); 
		        jQuery('.wppu_fill_type_text_only').slideDown(); 
		        jQuery('#wppu_fill_text_main').slideDown();
		        jQuery('#wppu_fill_thumb_main').slideUp();
		    }
		 });

	});


	jQuery(document).ready(function() {

		jQuery(function() {
			jQuery( "#pixiefy-main-tabs" ).tabs({
				activate: function( event, ui ) {
			        localStorage.selectedTab = ui.newTab.index();
			    },
			    active: localStorage.selectedTab ? localStorage.selectedTab - 1 : 0,
				hide: 'fadeOut', 
				show: 'fadeIn'}
				).addClass( "ui-tabs-vertical ui-helper-clearfix" );
			jQuery( "#pixiefy-tabUL li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
			// jQuery( ".wppu-radio-set" ).buttonset();
			// jQuery( ".wppu-background-size-set" ).buttonset();
			// jQuery( ".wppu-fill-loader-type" ).buttonset();


		});

		jQuery(function() {
			jQuery('.all_pages_checkbox_group input.checkPages').change(function() {
			    var sel = jQuery('.all_pages_checkbox_group input.checkPages:checked').map(function(_, el) {
			        return jQuery(el).attr('id');
			    }).get();
			    jQuery('input#get_all_pages').val(sel.join( ", " ));
			});
			jQuery('.all_pages_checkbox_group input.checkPages').change(function() {
			    var ID = jQuery('.all_pages_checkbox_group input.checkPages:checked').map(function(_, el) {
			        return jQuery(el).attr('data-id');
			    }).get();
			    jQuery('input#get_all_pages_ID').val(ID.join( ", " ));
			});
		});

		jQuery(function() {
			jQuery('.all_post_types_checkbox_group input.checkPosts').change(function() {
			    var sel = jQuery('.all_post_types_checkbox_group input.checkPosts:checked').map(function(_, el) {
			        return jQuery(el).attr('id');
			    }).get();
			    jQuery('input#get_all_post_type').val(sel.join( ", " ));
			});
			jQuery('.all_post_types_checkbox_group input.checkPosts').change(function() {
			    var ID = jQuery('.all_post_types_checkbox_group input.checkPosts:checked').map(function(_, el) {
			        return jQuery(el).attr('data-id');
			    }).get();
			    jQuery('input#get_all_post_type_list').val(ID.join( ", " ));
			});
		});

	});

	jQuery(document).ready(function() {
		jQuery('#get_all_0001').closest('table.form-table').appendTo('#wppu-tabs-1');
		jQuery('#select_gif').closest('table.form-table').appendTo('#wppu-tabs-2');
		jQuery('#waitMe_ex_effect').closest('table.form-table').appendTo('#wppu-tabs-3');
		jQuery('#special_text').closest('table.form-table').appendTo('#wppu-tabs-4');
		jQuery('#paceSelectOptions').closest('table.form-table').appendTo('#wppu-tabs-5');
		jQuery('#custom_wppu_markup').closest('table.form-table').appendTo('#wppu-tabs-6');

		jQuery(function() {
			jQuery( "#draggle_preview_dialog" ).dialog({
				width: 350,
				autoOpen: false,
				show: {
				effect: "blind",
				duration: 100
				},
				hide: {
				effect: "explode",
				duration: 1000
				}
			});
			jQuery( "#preview_openar" ).click(function() {
				jQuery( "#draggle_preview_dialog" ).dialog( "open" );
			});

			jQuery( "#draggle_preview_dialog_css" ).dialog({
				width: 350,
				autoOpen: false,
				show: {
				effect: "blind",
				duration: 100
				},
				hide: {
				effect: "explode",
				duration: 1000
				}
			});
			jQuery( "#preview_openar_css" ).click(function() {
				jQuery( "#draggle_preview_dialog_css" ).dialog( "open" );
			});

			jQuery( "#draggle_preview_dialog_awesome" ).dialog({
				width: 350,
				autoOpen: false,
				show: {
				effect: "blind",
				duration: 100
				},
				hide: {
				effect: "explode",
				duration: 1000
				}
			});
			jQuery( "#preview_openar_awesome" ).click(function() {
				jQuery( "#draggle_preview_dialog_awesome" ).dialog( "open" );
			});



		});

		jQuery(function() {
			jQuery( '.toolTipD' ).tooltip({
				position: {
				my: "center bottom-20",
				at: "center top",
				using: function( position, feedback ) {
				jQuery( this ).css( position );
				jQuery( "<div>" )
				.addClass( "arrow" )
				.addClass( feedback.vertical )
				.addClass( feedback.horizontal )
				.appendTo( this );
				}
				}
			});
		});
	});

	jQuery(document).ready(function() {
		jQuery.fn.cleanWhitespace = function() {
		    textNodes = this.contents().filter(
		        function() { return (this.nodeType == 3 && !/\S/.test(this.nodeValue)); })
		        .remove();
		    return this;
		}

		// jQuery("input#get_all-1").hide().next().hide().next().hide();
		jQuery("#input_example").bind("change paste keyup", function() {
		   var txt = jQuery(this).val();
		    jQuery('.waitMe_text').text(txt);
		});
		jQuery("#awesome_loading_text").bind("change paste keyup", function() {
		   var txt = jQuery(this).val();
		    jQuery('.awesome_title_txt').text(txt);
		});
		jQuery("#trendy_loading_text").bind("change paste keyup", function() {
		   var txt = jQuery(this).val();

			if ( txt.length === 0 ) {
				var gethtml = jQuery('#trendy_main_container').parent().html(),
					$s = jQuery(gethtml).find('h2').addClass('hide_trendy_title').end(),
					html = $s.find('.item-inner').clone(),
					htmlmarkup = $s.html();
			} else {
				var gethtml = jQuery('#trendy_main_container').parent().html(),
					$s = jQuery(gethtml).find('h2').removeClass('hide_trendy_title').end(),
					html = $s.find('.item-inner').clone(),
					htmlmarkup = $s.html();
			}

			jQuery('#wppu_trendy_markup').val(htmlmarkup);
			jQuery('#trendy_main_container').html('').append(html);

			jQuery('#trendy_main_container .trendy_loading_text').text(txt);
		});

		jQuery('.css_click').on("click", function() {
		    jQuery('.css_activated').removeClass('css_activated');
	  		jQuery(this).addClass('css_activated');

	  		jQuery('.item-generate').html('');
			var val = jQuery(this).attr('data-class');
			var addDiv = jQuery(this).attr('data-add-div');
			jQuery('#awesome_loading_selected').val(val);
			jQuery('#awesome_selectedcounter').val(jQuery(this).attr('data-add-div'));
        	jQuery('.item-generate').attr('class', 'la-2x item-generate ' + val );
        	for (var i = 1; i <= addDiv; i++) {
        		jQuery('.item-generate').append('<div/>');
        	}
		});

		jQuery('.trendy_click').on('click', function() {
			var val = jQuery(this).attr('data-class');
		    jQuery('.trendy_activated').removeClass('trendy_activated');
	  		jQuery(this).addClass('trendy_activated');
	  		jQuery('#trendy_loading_style').val(val);
	  		jQuery('#wppu_trendy_markup').val('');
	  		

	  		var html = jQuery(this).children('.item-inner').clone();
	  		var htmlmarkup = jQuery(this).html();

	  		if ( jQuery('#trendy_loading_text').val().length === 0 ) {
				var gethtml = jQuery(this).wrap('<p/>').parent().html(),
					$s = jQuery(gethtml).find('h2').addClass('hide_trendy_title').end(),
					html = $s.find('.item-inner').clone(),
					htmlmarkup = '<div class="item-inner">' + $s.html() + '</div>';
			} else {
				var gethtml = jQuery(this).wrap('<p/>').parent().html(),
					$s = jQuery(gethtml).find('h2').text(jQuery('#trendy_loading_text').val()).end(),
					html = $s.find('.item-inner').clone(),
					htmlmarkup = '<div class="item-inner">' + $s.html() + '</div>';
			}

	  		//var htmlmarkup = htmlmarkup.replace(/\s/g,'');

	  		// console.log(html);
	  		// console.log(htmlmarkup);

	  		jQuery('#wppu_trendy_markup').val(htmlmarkup);
	  		jQuery('#trendy_main_container').html('').append(html);
		});
	});


	jQuery('.plz_click').on("click", function () {
	  var src = jQuery(this).find('img').attr('src');
	  jQuery('#select_gif').val(src);
	  jQuery('.wow_wppu_preview').css('background-image', 'url('+src+')');
	  jQuery('.plz_click').removeClass('gif_activated');
	  
	  jQuery(this).addClass('gif_activated');
	}); // dropdown menu toggle


});


})( jQuery );
