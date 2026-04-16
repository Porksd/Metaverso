jQuery(document).ready(function() {
    // Handle Form Fields on page load/refresh
    handleFormFields();
});


/**
 * Handles Form Fields
 *
 */
function handleFormFields () {
    jQuery('.wppb-form-field:not(.login-submit):not(.wppb-two-factor-authentication) input:disabled').parent().addClass('disabled-field');

    // Text Fields
    handleFloatingLabels(jQuery('.wppb-form-text-field, .wppb-woocommerce-customer-billing-address .wppb_billing_state, .wppb-woocommerce-customer-shipping-address .wppb_shipping_state, .pms-billing-details .pms-billing-state, .wppb-subscription-plans .pms-group-name-field, .wppb-subscription-plans .pms-group-description-field, #pms-subscription-plans-discount'));

    // Select Fields
    focusInOutSelectFields(jQuery('.wppb-form-select-field, #select_user_to_edit_form .wppb-form-field'));

    // handle PWYW special case: price is modified from 0 to anything (PMS Billing fields are displayed after the DOM was already loaded)
    jQuery('.wppb-subscription-plans .pms-subscription-plan-price input.pms_pwyw_pricing').focusout(function () {
        if ( this.value > 0 ) {
            handleFloatingLabels(jQuery('.pms-billing-details .wppb-form-text-field, .pms-billing-details .pms-billing-state'));
            focusInOutSelectFields(jQuery('.wppb-form-select-field' ));
        }
    });

    // mark Upload/Avatar Fields on Edit Form, if a file is present
    if ( jQuery('.wppb-avatar').find('.upload-field-details').length > 0 )
        jQuery('.wppb-avatar').addClass('file-selected');
    if ( jQuery('.wppb-upload').find('.upload-field-details').length > 0 )
        jQuery('.wppb-upload').addClass('file-selected');

    // Disable HTML5 validation. It prevents form field error markers to be displayed for required fields.
    jQuery('.wppb-register-user').attr('novalidate', 'novalidate');

    // handle changes for specific Form Fields
    observeFieldChanges();

}


/**
 * Handles Text Input Field Label (activate/deactivate Floating Labels)
 *
 */
function handleFloatingLabels (formFields) {

    formFields.each(function () {

        let field = jQuery(this),
            input = field.find('input:not([type="submit"])').not('.wppb-field-visibility-settings input'),
            textarea = field.find('textarea'),
            label = field.find('label').not('.wppb-field-visibility-settings label');

        if ( input.length === 0 && textarea.length > 0 )
            input = textarea;

        if ( input.val() ) {
            label.addClass('active');

            if (field.hasClass('wppb-phone'))
                input.addClass('active');
        }

        input.focusin(function () {
            label.addClass('active focused');
        })

        input.focusout(function () {
            label.removeClass('focused');
            checkInput();
        })

        field.click(function (e) {
            if ( jQuery(e.target).parents('.wppb_bdp_visibility_settings').length === 0 )
                label.addClass('focused');
        })

        if (field.hasClass('wppb-phone')) {
            input.change(function () {
                if (label.hasClass('active')) {
                    input.addClass('active');
                } else {
                    input.removeClass('active');
                }
            })
        }

        if (field.hasClass('wppb-datepicker')) {
            input.change(function () {
                if ( input.val() )
                    label.addClass('active');
                else label.removeClass('active');

            })
        }


        /**
         * Mark Labels as needed
         *
         */
        function checkInput() {
            if (input.val()) {
                label.addClass('active');
            } else {
                label.removeClass('active');
            }
        }

    });

}

/**
 * Handles Select Field Label (focus in/out on Field Labels)
 *
 */
function focusInOutSelectFields(formFields) {

    formFields.each(function () {
        let field = jQuery(this),
            select = field.find('select'),
            label = field.find('label').not('.wppb-field-visibility-settings label');

        if ( select.val() ) {
            label.addClass('active');
            field.removeClass('placeholder-hidden');
        }

        if ( select.length > 0 && ( !select.val() || select.val().length === 0 ) ) {
            label.removeClass('active');
            field.addClass('placeholder-hidden');
        }

        field.focusin(function () {
            label.addClass('active focused');
        });

        field.focusout(function () {
            label.removeClass('focused');
            checkSelect();
        })

        field.click(function (e) {
            if ( jQuery(e.target).parents('.wppb_bdp_visibility_settings').length === 0 )
                label.addClass('focused');
        })

        select.change(function() {
            checkSelect();
        })


        /**
         * Mark Fields and Labels as needed
         *
         */
        function checkSelect() {
            if ( (select.val() && select.val().length > 0) || ( field.is('.wppb_shipping_state, .wppb_billing_state, .pms-billing-state') && field.find('input').val() ) ){
                label.addClass('active');
                field.removeClass('placeholder-hidden');
            } else {
                label.removeClass('active');
                field.addClass('placeholder-hidden');
            }

        }
    })

}


/**
 * Handles changes for specific Form Fields
 *
 */
function observeFieldChanges() {

    // handle case when a new Input or Select field is added to the Repeater Group
    const repeaterObserver = new MutationObserver(handleRepeaterMutations);

    jQuery('.wppb-repeater').each(function() {
        repeaterObserver.observe(this, {
            childList: true,
            subtree: true
        });
    });

    function handleRepeaterMutations(mutationsList) {
        mutationsList.forEach(mutation => {
            if (mutation.type === 'childList') {
                handleFloatingLabels(jQuery('.wppb-repeater .wppb-form-text-field'));
                focusInOutSelectFields(jQuery('.wppb-repeater .wppb-form-select-field'));
            }
        });
    }


    // handle case when an initially hidden conditional field is displayed
    const textFieldObserver = new MutationObserver(handleTextFieldMutations);

    jQuery('.wppb-form-text-field').each(function() {
        textFieldObserver.observe(this, {
            attributes: true,
            attributeFilter: ['style']
        });
    });

    function handleTextFieldMutations(mutationsList) {
        mutationsList.forEach(mutation => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                if (jQuery(mutation.target).is(':visible')) {
                    handleFloatingLabels(jQuery('.wppb-form-text-field'));
                }
            }
        });
    }


    // mark Upload/Avatar Fields when a file is selected for upload
    const uploadFieldObserver = new MutationObserver(handleUploadFieldMutations);

    jQuery('.wppb-avatar, .wppb-upload').each(function() {
        uploadFieldObserver.observe(this, {
            childList: true,
            subtree: true,
            attributes: true
        });
    });

    function handleUploadFieldMutations(mutationsList) {
        mutationsList.forEach(mutation => {
            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                let uploadField = jQuery(mutation.target);

                if (uploadField.find('.upload-field-details').length > 0) {
                    uploadField.addClass('file-selected');
                } else {
                    uploadField.removeClass('file-selected');
                }
            }
        });
    }

}


jQuery(window).on('load', function () {

    jQuery('#wppb-form-style-2-wrapper .wppb-woocommerce-customer-billing-address .wppb_billing_state, #wppb-form-style-2-wrapper .wppb-woocommerce-customer-shipping-address .wppb_shipping_state, #wppb-form-style-2-wrapper .pms-billing-details .pms-billing-state').each(function () {
        stateFieldLabelSpacing(jQuery(this));
    });

    // jQuery('#wppb-form-style-2-wrapper .wppb-woocommerce-customer-billing-address .wppb_billing_state, #wppb-form-style-2-wrapper .wppb-woocommerce-customer-shipping-address .wppb_shipping_state, #wppb-form-style-2-wrapper .pms-billing-details .pms-billing-state').on('DOMSubtreeModified', function() {
    //     stateFieldLabelSpacing(jQuery(this));
    // });


    // handle case when the State field type is changed (Input or Select)
    const stateFieldObserver = new MutationObserver(handleStateFieldMutations);

    jQuery('#wppb-form-style-2-wrapper .wppb-woocommerce-customer-billing-address .wppb_billing_state, #wppb-form-style-2-wrapper .wppb-woocommerce-customer-shipping-address .wppb_shipping_state, #wppb-form-style-2-wrapper .pms-billing-details .pms-billing-state').each(function() {
        stateFieldObserver.observe(this, {
            childList: true,
            subtree: true,
            attributes: true,
        });
    });

    function handleStateFieldMutations(mutationsList) {
        mutationsList.forEach(mutation => {
            let stateField = jQuery(mutation.target).parent();

            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                stateFieldLabelSpacing(stateField);
            }
        });
    }

});

/**
 * Handles PMS and WOO State Field Label spacing for Form Design - Style 2
 *
 */
function stateFieldLabelSpacing(stateField) {
    let label = stateField.find('label'),
        elementType = jQuery('#' + label.attr('for')).prop('nodeName');

    if (elementType === 'INPUT')
        label.css('left', '30px');
    else stateField.find('label').css('left', '0');
}