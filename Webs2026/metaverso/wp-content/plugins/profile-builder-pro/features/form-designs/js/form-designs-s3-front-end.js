jQuery(document).ready(function() {

    jQuery('.wppb-form-field:not(.login-submit):not(.wppb-two-factor-authentication) input:disabled').parent().addClass('disabled-field');

    // mark Upload/Avatar Fields on Edit Form, if a file is present
    if ( jQuery('.wppb-avatar').find('.upload-field-details').length > 0 )
        jQuery('.wppb-avatar').addClass('file-selected');
    if ( jQuery('.wppb-upload').find('.upload-field-details').length > 0 )
        jQuery('.wppb-upload').addClass('file-selected');

    // Disable HTML5 validation. It prevents form field error markers to be displayed for required fields.
    jQuery('.wppb-register-user').attr('novalidate', 'novalidate');


    // handle changes for Upload and Avatar Fields
    observeUploadFieldChanges();

});


/**
 * Handles changes for Upload and Avatar Fields
 *
 */
function observeUploadFieldChanges() {

    // mark Upload/Avatar Fields when a file is selected for upload
    const uploadFieldObserver = new MutationObserver(handleUploadFieldChanges);

    jQuery('.wppb-avatar, .wppb-upload').each(function() {
        uploadFieldObserver.observe(this, {
            childList: true,
            subtree: true,
            attributes: true
        });
    });

    function handleUploadFieldChanges(mutationsList) {
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