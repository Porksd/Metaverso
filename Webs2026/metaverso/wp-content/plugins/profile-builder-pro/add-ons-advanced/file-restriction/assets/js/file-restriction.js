jQuery(document).ready(function() {

    /****************************************************/
    /* Content Restriction section settings
    /****************************************************/

    // initialize Select2 for "Protected File Types" field, with the ability to add new options
    jQuery('#restricted-file-types').select2({
        placeholder: 'Select File Types',
        tags: true,
    });

    // handle "Protected File Types" setting state
    handleFileTypesSelector();

    // enable/disable "Protected File Types" setting as needed
    jQuery('#restrict-all-files-enable').change(function() {
        handleFileTypesSelector();
    });


    /****************************************************/
    /* Advanced Settings -> Fields section settings
    /****************************************************/

    // initialize Select2 for "Restricted Upload Fields" field, without the ability to add new options
    jQuery('#toolbox-restricted-upload-fields').select2({
        placeholder: 'Select Upload Fields',
        tags: false,
    });

    // initialize Select2 for "Restrict Files to User Roles" field, without the ability to add new options
    jQuery('#toolbox-restricted-file-roles').select2({
        placeholder: 'Select User Roles',
        tags: false,
    });

    // handle "Restricted Upload Fields" and "Restrict Files to User Roles" settings visibility
    handleFieldSettingsVisibility();

    // hide/show "Restricted Upload Fields" and "Restrict Files to User Roles" settings as needed
    jQuery('#toolbox-restrict-uploaded-file').change(function() {
        handleFieldSettingsVisibility();
    });

});


/**
 * Enable/Disable "Protected File Types" selector
 *
 * */
function handleFileTypesSelector() {
    if (jQuery('#restrict-all-files-enable').is(':checked')) {
        jQuery('#restricted-file-types').next('.select2-container').css({
                                             'pointer-events': 'none',
                                             'opacity': '0.7',
                                         });
    } else {
        jQuery('#restricted-file-types').next('.select2-container').css({
                                             'pointer-events': 'all',
                                             'opacity': '1',
                                         });
    }
}


/**
 * Hide/Show "Restricted Upload Fields" and "Restrict Files to User Roles" settings
 *
 * */
function handleFieldSettingsVisibility(){
    if (jQuery('#toolbox-restrict-uploaded-file').prop('checked'))
        jQuery('#restricted-file-roles, #restricted-upload-fields').css('display','flex');
    else
        jQuery('#restricted-file-roles, #restricted-upload-fields').hide();
}