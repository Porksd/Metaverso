function resetApiKeyForm(){
	jQuery("#generate-api-key-form #api-key-description").val("").focus(); //NO I18N
	jQuery("#generate-api-key").attr("disabled", true); //NO I18N
	jQuery("#api-key-span").text(""); //NO I18N
	jQuery("#generate-api-key-form").show(); //NO I18N
	jQuery("#api-key-div").hide(); //NO I18N
	jQuery("#generate-api-key-error").remove(); //NO I18N
}

function copyApiKey(){
		var temp=document.createElement('input');
  	var texttoCopy=document.getElementById('api-key-span').innerHTML.trim();
  	temp.type='input';
  	temp.setAttribute('value',texttoCopy);
  	document.body.appendChild(temp);
  	temp.select();
  	document.execCommand("copy"); //No I18N
  	temp.remove();
  	jQuery("#copy-sucess").hide(); //No I18N
		//jQuery("#copy-api-key-sucess").show(); //No I18N
		jQuery("#copy-sucess").css({"display":"inline-block"}); //No I18N
		setTimeout(function(){
			jQuery("#copy-sucess").hide(); //No I18N
		}, 1000);
}

function copySiteUrl(){
	var temp=document.createElement('input');
  	var texttoCopy=document.getElementById('site-url-span').innerHTML.trim();
  	temp.type='input';
  	temp.setAttribute('value',texttoCopy);
  	document.body.appendChild(temp);
  	temp.select();
  	document.execCommand("copy"); //No I18N
  	temp.remove();
  	jQuery("#copy-sucess").hide(); //No I18N
		//jQuery("#copy-site-url-sucess").show(); //No I18N
		jQuery("#copy-sucess").css({"display":"inline-block"}); //No I18N
		setTimeout(function(){
			jQuery("#copy-sucess").hide(); //No I18N
		}, 1000);
}

function generateApiKey(frm){

	var description = frm.find("[name=description]").val().trim();
	if(!description){
		frm.find("[name=description]").focus();
	}
	var btn = frm.find("#generate-api-key");
	btn.val(i18n.generating);
	var data = jQuery("#generate-api-key-form").serialize(); //NO I18N
	jQuery.post(ajaxurl, data, function(response){
		div = jQuery("#api-key-div"); //NO I18N
		jQuery("#api-key-span").text(response); //NO I18N
		div.show();
		jQuery("#generate-api-key-form").hide(); //NO I18N
		btn.val(i18n.generate);
		reloadApiKeyTable();
	}).fail(function(response){
		jQuery("#generate-api-key-error").remove(); //NO I18N
		span = jQuery("<span id='generate-api-key-error' style='color:red;vertical-align:middle;margin-left:10px;'></span>");
		btn.after(span);
		span.text(response.responseJSON.data);
		btn.val(i18n.generate);
	});
}

function reloadApiKeyTable(){
	var paramArray = jQuery("#remove-api-key-form").serializeArray(); //No I18N
	var data = {};
	for(var paramObj in paramArray){
		if(paramArray[paramObj].name == "service_id"){
			data[paramArray[paramObj].name] = paramArray[paramObj].value;
		}
	}
	data.action = "zoho_flow_api_key_table"; //No I18N
	if(data.service_id){
		jQuery.post( ajaxurl, data, function(response){
			jQuery("#api-key-list-display").html(response); //No I18N
		} );
	}
}

jQuery(document).ready(function(){

	jQuery("#loader").fadeOut(); //NO I18N

	jQuery("#copy-api-key").click(function(e){ //NO I18N
		copyApiKey();
	});

	jQuery("#copy-site-url").click(function(e){ //NO I18N
		copySiteUrl();
	});

	jQuery("#open-api-key-generation-popup").click(function(e){ //NO I18N
		resetApiKeyForm();
		jQuery("#generate-api-key-form #api-key-description").keyup(function(e){ //NO I18N
			jQuery("#generate-api-key-error").remove(); //NO I18N
			var descInput = jQuery("#api-key-description"); //NO I18N
			var btn = jQuery("#generate-api-key"); //NO I18N
			if(descInput.val().replaceAll(' ','') != ""){
				btn.attr("disabled", false); //NO I18N
			}
			else{
				btn.attr("disabled", true); //NO I18N
			}
		});
	});

	jQuery("#open-api-key-generation-popup-from-right-panel").click(function(e){ //NO I18N
		resetApiKeyForm();
		jQuery("#generate-api-key-form #api-key-description").keyup(function(e){ //NO I18N
			jQuery("#generate-api-key-error").remove(); //NO I18N
			var descInput = jQuery("#api-key-description"); //NO I18N
			var btn = jQuery("#generate-api-key"); //NO I18N
			if(descInput.val().replaceAll(' ','') != ""){
				btn.attr("disabled", false); //NO I18N
			}
			else{
				btn.attr("disabled", true); //NO I18N
			}
		});
	});

	jQuery("#ok-api-key-popup").click(function(e){ //NO I18N
		tb_remove();
		//window.location.reload();
	});

	jQuery("#generate-api-key").click(function(e){ //NO I18N
		e.preventDefault();
		frm = jQuery("#generate-api-key-form"); //NO I18N
		generateApiKey(frm);
		//reloadApiKeyTable();
	});

	jQuery("#generate-api-key-form").submit(function(e){ //NO I18N
		e.preventDefault();
		frm = jQuery(e.srcElement);
		generateApiKey(frm);
	});


	//jQuery('.delete-api-key').click(function(e){ //NO I18N
	jQuery("#remove-api-key-form").on('click', ".delete-api-key", function(e) { //No I18N
		e.preventDefault();
		var cfrm = confirm(i18n.remove_api_key_confirmation);
		if(!cfrm){
			return;
		}
		var data = jQuery("#remove-api-key-form").serialize(); //NO I18N
		var icon = this;
		var id = icon.id;
		if(id.startsWith("api-key")){
			api_key_id = id.replace('api-key-', '');
			data = data + "&api_key_id=" + api_key_id; //NO I18N
			jQuery.post(ajaxurl, data, function(response){
				jQuery(icon).parents("tr").remove(); //NO I18N
				jQuery("#notice").attr("class", "notice").addClass("notice-success").text(response).show(); //NO I18N
				setTimeout(function(){
					jQuery("#notice").fadeOut("slow").attr("class", "notice").text(""); //NO I18N
				}, 5000);
			}).fail(function(response){
				jQuery("#notice").attr("class", "notice").addClass("notice-error").text(response.responseJSON.data).show(); //NO I18N
				setTimeout(function(){
					jQuery("#notice").fadeOut("slow").attr("class", "notice").text(""); //NO I18N
				}, 5000);
			});
		}
	});

	jQuery( 'a.zoho-flow-rating-link' ).click( function() { //NO I18N
		jQuery.post( ajaxurl, { action: 'zoho_flow_rated' } ); //NO I18N
		jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) ); //NO I18N
		jQuery("#review_notice").remove(); //NO I18N
	});

	jQuery('.grid-app-available').on('mouseover', function() { //NO I18N
		jQuery(this).css('box-shadow', '0 8px 16px 0 rgba(85,93,102,.3)'); //NO I18N
    jQuery(this).find('.grid-app-direct-integration').css('visibility', 'visible'); //NO I18N
	});

	jQuery('.grid-app-available').on('mouseout', function() { //NO I18N
		jQuery(this).css('box-shadow', 'none'); //NO I18N
    jQuery(this).find('.grid-app-direct-integration').css('visibility', 'hidden'); //NO I18N
	});

	jQuery('.grid-app-not-available').on('mouseover', function() { //NO I18N
		//jQuery(this).css('box-shadow', '0 8px 16px 0 rgba(85,93,102,.3)'); //NO I18N
		jQuery(this).css('background', '#f2f2f2'); //NO I18N
		jQuery(this).css('opacity', '0.5'); //NO I18N
    jQuery(this).find('.grid-app-direct-integration').css('visibility', 'visible'); //NO I18N
	});

	jQuery('.grid-app-not-available').on('mouseout', function() { //NO I18N
		//jQuery(this).css('box-shadow', 'none'); //NO I18N
		jQuery(this).css('background', ''); //NO I18N
		jQuery(this).css('opacity', '1'); //NO I18N
    jQuery(this).find('.grid-app-direct-integration').css('visibility', 'hidden'); //NO I18N
	});

	jQuery(document).on('thickbox:iframe:loaded', function () { //NO I18N
        const tbWindow = jQuery('#TB_window'); //NO I18N
        const iframeSrc = jQuery('#TB_iframeContent').attr('src'); //NO I18N

        if (iframeSrc && iframeSrc.includes('Wordpress_showcase_page_feedback_form')) { //NO I18N
            tbWindow.addClass('zf-feedback-popup'); //NO I18N
        }
		else if (iframeSrc && iframeSrc.includes('Request_an_App')) { //NO I18N
            tbWindow.addClass('zf-app-request-popup'); //NO I18N
        }
		else if (iframeSrc && iframeSrc.includes('68JFXlpm6iI')) { //NO I18N
            tbWindow.addClass('zf-youtube-popup'); //NO I18N
        }
    });

});
