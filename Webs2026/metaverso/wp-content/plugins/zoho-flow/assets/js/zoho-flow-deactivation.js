jQuery(document).ready(function () {
    // Add class to ThickBox popup if the iframe matches the deactivation form
    jQuery(document).on('thickbox:iframe:loaded', function () { //NO I18N
        const tbWindow = jQuery('#TB_window'); //NO I18N
        const iframeSrc = jQuery('#TB_iframeContent').attr('src'); //NO I18N

        if (iframeSrc && iframeSrc.includes('Wordpress_showcase_page_deactivation_form')) { //NO I18N
            tbWindow.addClass('zf-deactivation-popup'); //NO I18N
        }
    });

    // Add custom buttons to the ThickBox window
    jQuery(document).on('thickbox:iframe:loaded', function () { //NO I18N
        const tbWindow = jQuery('#TB_window'); //NO I18N

        if (tbWindow.length && jQuery('.zf-deactivation-popup').length) { //NO I18N
            const deactivateButton = jQuery('<button>', { //NO I18N
                id: 'zf-deactivate-button', //NO I18N
                class: 'button button-primary thickbox', //NO I18N
                text: 'Deactivate', //NO I18N
                css: {
                    position: 'absolute', //NO I18N
                    bottom: '10px', //NO I18N
                    right: '20px', //NO I18N
                    height: '30px', //NO I18N
                    width: '100px' //NO I18N
                }
            });

            const closeButton = jQuery('<button>', { //NO I18N
                id: 'zf-cancel-button', //NO I18N
                class: 'button button-primary thickbox', //NO I18N
                text: 'Cancel', //NO I18N
                css: {
                    position: 'absolute', //NO I18N
                    bottom: '10px', //NO I18N
                    left: '20px', //NO I18N
                    height: '30px', //NO I18N
                    width: '100px' //NO I18N
                }
            });

            tbWindow.append(deactivateButton);
            tbWindow.append(closeButton);

            // Handle deactivation
            deactivateButton.on('click', function () { //NO I18N
                jQuery.post(zohoFlow.ajaxurl, {
                    action: 'zoho_flow_deactivate_plugin' //NO I18N
                })
                .done(function (response) {
                    if (response.success) {
                        tb_remove();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data); //NO I18N
                    }
                })
                .fail(function () {
                    alert('An unexpected error occurred.'); //NO I18N
                });
            });

            // Handle cancel
            closeButton.on('click', function () { //NO I18N
                tb_remove();
            });
        }
    });
});
