jQuery(document).ready(function(){

    handleProfileBuilderForms();

})



/**
 * Handles the PB-Forms AJAX Validation
 *
 */
function handleProfileBuilderForms() {
    let wppbElements = jQuery('.wppb-user-forms');

    // Exit if there are no PB Forms
    if (wppbElements.length === 0) {
        return;
    }

    wppbElements.each(function() {
        let wppbElement = jQuery(this),
            form = wppbElement.is('form') ? wppbElement : wppbElement.find('form');

        // Ensure we have a PB Form
        if (form.length === 0) {
            return;
        }

        // Listen for PB-Form submission
        form.on('submit', function(submitEvent) {

            let formData = new FormData(this),
                paymentMethod = formData.get('pay_gate'),
                pmsSubscriptionPlans = form.find('.wppb-subscription-plans');

            if (pmsSubscriptionPlans.length === 0 || (pmsSubscriptionPlans.length > 0 && (!paymentMethod || paymentMethod !== 'stripe_connect'))) {
                submitEvent.preventDefault();
                validateProfileBuilderForm(wppbElement, form, formData);
            }

        });

    });
}


/**
 * PB-Forms AJAX Validation
 *
 */
function validateProfileBuilderForm(wppbElement, form, formData) {
    let formID = form.attr('id'),
        submitButton = form.find('input[type="submit"]');

    // Disable Submit button while AJAX request is in progress
    submitButton.prop('disabled', true).val('Processing...').css('opacity', '0.5');

    // Remove any Error Message added in case of AJAX request error
    submitButton.siblings('p.error-message').remove();

    jQuery.ajax({
        url: form.attr('action'),
        method: form.attr('method'),
        data: formData,
        contentType: false,
        processData: false,

        success: function(response, status, responseData) {
            let ajaxResponse = jQuery(response),
                wrapperClass = wppbElement.parent().closest('[class]').attr('class'),
                updatedContainer = ajaxResponse.find('[class="' + wrapperClass + '"]' + ':has(#' + formID + ')' ),
                errorMessage =  updatedContainer.find('.wppb-error, .wppb-warning'),
                successMessage =  '';

            if (updatedContainer.length === 0) {

                // Look for possible success messages
                if (formID === 'wppb-recover-password' || formID.indexOf('wppb-register') === 0) {
                    successMessage = ajaxResponse.find('[class="' + wrapperClass + '"]' + ':has(.wppb-success)' );
                }

            }

            if ( successMessage !== '' ) {
                updatedContainer = successMessage;
            }

            if (updatedContainer.length > 0) {
                if (formID.indexOf('wppb-register') === 0 && successMessage !== '') {
                    jQuery('[class="' + wrapperClass + '"]' + ':has(#' + formID + ')' ).html(updatedContainer.prop('outerHTML'));
                }
                else if (formID !== 'wppb-loginform' || (formID === 'wppb-loginform' && jQuery('#wppb-login-wrap').prev().attr('class') !== 'wppb-warning')) { // Look for dynamically added 2FA warning message before updating the Form
                    jQuery('[class="' + wrapperClass + '"]' + ':has(#' + formID + ')' ).html(updatedContainer.html());
                }
            }

            // Reset submit button
            submitButton.prop('disabled', false).val('Submit').css('opacity', '');

            // Scroll to the Form notification
            focusOnFormNotice(formID, wppbElement);

            // Call this Function again so the PB-Form is properly handled after a Form submission with errors
            handleProfileBuilderForms();

            if (errorMessage.length === 0 && (formID === 'wppb-loginform' || formID.indexOf('wppb-register') === 0)) {
                let autoLogin = ajaxResponse.find('p.redirect_message a').attr('href'),
                    formRedirect = formData.get('redirect_to');

                // Check for an Autologin link before making the redirect for the Registration Form
                if (autoLogin && formID.indexOf('wppb-register') === 0){
                    formRedirect = autoLogin;
                }

                if (formRedirect !== ''){
                    window.location.href = formRedirect;
                }
            }

            // Bind specific Form Field Events
            bindFormFieldEvents(formID);

        },

        error: function(xhr, status, error) {

            // Display error message
            let errorMessage = jQuery('<p>').addClass('error-message').css('color', '#C62828').text('Something went wrong! Please try again...');
            submitButton.after(errorMessage);

            // Reset submit button
            submitButton.prop('disabled', false).val('Submit').css('opacity', '');
        }
    });
}


/**
 * Bind specific Form Field Events
 *
 */
function bindFormFieldEvents(formID) {

    // Call this Function again for the Form Fields to be properly handled (for FD - Style 1 & Style-2)
    if (typeof handleFormFields === 'function')
        handleFormFields();

    // Add Placeholder for Login Form Fields
    if (formID === 'wppb-loginform' && typeof loginFieldPlaceholder === 'function')
        loginFieldPlaceholder();

    // Add Placeholder for 2FA Field
    if (formID === 'wppb-loginform' && typeof loginAuthPlaceholder === "function")
        loginAuthPlaceholder(jQuery(".login-auth"));

    // EPAA
    if (formID === 'wppb-edit-user' && typeof handleAdminApproval === 'function')
        handleAdminApproval();

    // DatePicker Field
    if (jQuery('.wppb-datepicker').length > 0 && typeof wppb_initialize_datepicker === 'function')
        wppb_initialize_datepicker();

    // ColorPicker Field
    if (jQuery('.wppb-colorpicker').length > 0 && typeof wppb_initialize_colorpicker === 'function')
        wppb_initialize_colorpicker();

    // Phone Field
    if (jQuery('.wppb-phone').length > 0 && typeof wppb_initialize_phone_field === 'function')
        wppb_initialize_phone_field();

    // Map Field
    if (jQuery('.wppb-map').length > 0 && typeof wppb_initialize_map === 'function')
        wppb_initialize_map();

    // Select2 & Select2 Multiple Fields
    let select2Fields = jQuery('.wppb-select2, .wppb-select2-multiple');
    if (select2Fields.length > 0 && typeof wppb_select2_initialize === "function") {
        select2Fields.each(function(){
            jQuery('.select2-container').remove();
        });
        wppb_select2_initialize();
    }

    // CPT Field
    let selectCPTFields = jQuery('.wppb-select-cpt');
    if (selectCPTFields.length > 0 ){
        selectCPTFields.each(function(){
            jQuery('.wppb-select-cpt .select2-container').remove();
        });
        jQuery('.custom_field_cpt_select').each( function(){
            let currentCptSelect = this;
            jQuery(currentCptSelect).select2();
        });
    }

    // "Select User To Edit" Field (Edit Profile Form)
    if (jQuery('#select_user_to_edit_form').length > 0 && typeof wppb_select_user_to_edit_initialize === 'function')
        wppb_select_user_to_edit_initialize();

    // Simple Upload Field
    if (jQuery('.wppb_simple_upload').length > 0)
        validate_simple_upload();

    // Password Visibility Toggle
    if (jQuery('button.wppb-toggle-pw').length > 0 && typeof wppb_password_visibility_toggle === 'function')
        jQuery( 'button.wppb-toggle-pw' ).on( 'click', wppb_password_visibility_toggle );

    // Password Strength feature
    if (jQuery('#passw1, #passw2').length > 0 && typeof check_pass_strength === 'function')
        jQuery('#passw1, #passw2').val('').on('keyup change', check_pass_strength);

    // reCAPTCHA
    if (typeof wppbRecaptchaCallback === 'function') {
        window.wppbRecaptchaCallbackExecuted = undefined;
        wppbRecaptchaCallback();
    }

    let test = $pms_form;

    // PMS Events
    if ( typeof $pms_form !== "undefined") {
        jQuery.getScript('/wp-content/plugins/paid-member-subscriptions-dev/assets/js/front-end.js')
              .done(function() {

                  if (jQuery('.pms-group-memberships-field').length > 0)
                      jQuery.getScript('/wp-content/plugins/paid-member-subscriptions-dev/add-ons-pro/group-memberships/assets/js/front-end.js');

                  if (jQuery('#pms-subscription-plans-discount').length > 0)
                      jQuery.getScript('/wp-content/plugins/paid-member-subscriptions-dev/includes/features/discount-codes/assets/js/frontend-discount-code.js')

                  if (jQuery('input.pms_pay_gate[value="stripe_connect"]').length > 0)
                      jQuery.getScript('/wp-content/plugins/paid-member-subscriptions-dev/includes/gateways/stripe/assets/front-end-connect.js');

              })
    }

}


/**
 * Scroll to the Form notification
 *
 */
function focusOnFormNotice(formID, wppbElement){
    let prevElement = jQuery('#' + wppbElement.attr('id')).prev();

    if (prevElement.is('.wppb-error, .wppb-warning') && ( !window.wppb_disable_automatic_scrolling || window.wppb_disable_automatic_scrolling != 1 )) {
        window.scrollTo({
                            top: prevElement.offset().top - 50,
                            behavior: 'smooth'
                        });
    }
}