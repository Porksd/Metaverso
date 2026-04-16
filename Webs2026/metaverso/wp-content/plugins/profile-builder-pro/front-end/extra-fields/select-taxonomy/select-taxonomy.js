/* initialize here the select2 */
jQuery(function(){
    jQuery('.custom_field_taxonomy_select').each( function(){
        var currentTaxonomySelect = this;
        jQuery( currentTaxonomySelect ).select2().on('select2:open', function(){
            // compatibility with Divi Overlay
            if( jQuery(currentTaxonomySelect).parents( '.overlay-container' ).length ){
                jQuery(currentTaxonomySelect).data('select2').dropdown.$dropdownContainer.css( 'z-index', '99999999' );
            }
        });
    });
});
