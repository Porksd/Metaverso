jQuery( function( $ ) {
	// Orderby
	$( '.eb-pro-shop-orderby-filter' ).on( 'change', 'select.orderby', function() {
        console.log('orderby');
		$( this ).closest( 'form' ).trigger( 'submit' );
	});

    // category filter
    $( '.eb-pro-shop-category-filter' ).on( 'change', 'select.category', function() {
        $( this ).closest( 'form' ).trigger( 'submit' );
    });

    // image selector
    $('.product-small-image').on('click', function(){
        var img_src = $(this).find('img').attr('src');
        var img_srcset = $(this).find('img').attr('srcset');

        // change the main image
        $('.product-main-image img').attr('src', img_src);
        $('.product-main-image img').attr('srcset', img_srcset);

        // change selected class
        $('.product-small-image img').removeClass('selected');
        $(this).find('img').addClass('selected');
    })

    // product page tabs
    $('.eb-pro-product-page-details .tab').on('click', function(){
        var tab_id = $(this).attr('data-tab');

        $('.eb-pro-product-page-details .tab').removeClass('active');
        $('.eb-pro-product-page-details .tab-content').removeClass('active');

        $(this).addClass('active');
        $('.'+tab_id+'-content').addClass('active');
    });

    // quantity plus minus
    $('.eb-pro-product-page-sidebar .quantity-plus').on('click', function(e){
        e.preventDefault();
        // var quantity = parseInt($('.eb-pro-product-page-sidebar .quantity-input').val());
        // $('.eb-pro-product-page-sidebar .quantity-input').val(quantity + 1);
        // find nearest form and update the quantity
        var quantity = parseInt($(this).closest('.quantity-wrap').find('input').val());
        $(this).closest('.quantity-wrap').find('input').val(quantity + 1);
        
    }
    );

    $('.eb-pro-product-page-sidebar .quantity-minus').on('click', function(e){
        e.preventDefault();
        var quantity = parseInt($(this).closest('.quantity-wrap').find('input').val());
        if(quantity > 1){
            $(this).closest('.quantity-wrap').find('input').val(quantity - 1);
        }
    });

    $('.eb-pro-shop-grid-view').on('click', function(){
        $('.eb-pro-shop-products-card-wrap').show();
        $('.eb-pro-shop-products-list-wrap').attr('style', 'display: none !important'); // display flex is added forcefully

        // chnage the color of the grid view button
        $('.eb-pro-shop-list-view svg path').css('fill', '#385B5C');
        $('.eb-pro-shop-grid-view svg path').css('fill', '#0041C9');
    });

    $('.eb-pro-shop-list-view').on('click', function(){
        $('.eb-pro-shop-products-card-wrap').hide();
        $('.eb-pro-shop-products-list-wrap').show();

        // chnage the color of the list view button
        $('.eb-pro-shop-list-view svg path').css('fill', '#0041C9');
        $('.eb-pro-shop-grid-view svg path').css('fill', '#385B5C');
    });

    $(window).on('load', function(){
        $( ".single_variation_wrap" ).on(
            "show_variation",
            function ( event, variation ) {
                //$('.single_variation').hide();
                $('#variation_id').val(variation.variation_id);
                $('.eb-pro-cart-cta').removeAttr('disabled');

                $('.associated-courses').hide();
                // show associated courses with data-variation-id
                if (variation.variation_id) {
                    $('.associated-courses[data-variation-id="'+variation.variation_id+'"]').show();
                }
            }
        );

        // on variation reset hide the associated courses and disable the add to cart button
        $( ".single_variation_wrap" ).on(
            "hide_variation",
            function ( event ) {
                $('.associated-courses').hide();
                $('.eb-pro-cart-cta').attr('disabled', 'disabled');
            }
        );
    });

});
