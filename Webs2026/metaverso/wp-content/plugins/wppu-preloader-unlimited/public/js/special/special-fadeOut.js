( function( $ ) {
    "use strict";

    if ( $('.wppu-preloader').length === 0 ) {
        return false;
    }

    var $options_type = wppreloaderData.options_type,
        $delay = parseInt(wppreloaderData.delay),
        $effect = wppreloaderData.effect,
        $load_type = wppreloaderData.load_type,
        $load_time = wppreloaderData.load_time,
        $transition = parseInt(wppreloaderData.transition);

    if (typeof $effect === typeof undefined || $effect === false) {
        $options_type = 'image_radio_options';
    }
    if (typeof $effect === typeof undefined || $effect === false) {
        $effect = 'fadeOut';
    }
    if (typeof $delay === typeof undefined || $delay === false) {
        $delay = 200;
    }
    if (typeof $transition === typeof undefined || $transition === false) {
        $transition = 400;
    }
    if (typeof $load_type === typeof undefined) {
        $load_type = "window";
    }
    if (typeof $load_time === typeof undefined || $load_time === false) {
        $load_time = 1500;
    }

    var dur = 20000;

    $(document).ready(function(){

		$( ".wppu_special span .h-p" ).animate({
		width: "100%"}, {
		duration: dur,
		  complete: function() {
		    if ($(".wppu-preloader").hasClass("load_first") ) {
		      $(".wppu-preloader").delay($delay).fadeOut($delay);
		    } else {
		      $(".wppu-preloader").addClass("load_me_first");
		    }
		  }
		});
        if( $load_type === "custom" ) {
            setTimeout(function(){
                if ($(".wppu-preloader").hasClass("load_me_first") ) {
                    $(".wppu-preloader").delay($delay).fadeOut($delay);
                } else {
                    $(".wppu-preloader").addClass("load_first wppu-preloader-loaded");
                    $(".wppu-preloader").delay($delay).fadeOut($delay);
                }
            }, $load_time);
        }
    });

    $( window ).on("load", function() {
        if( $load_type !== "custom" ) {
            if ($(".wppu-preloader").hasClass("load_me_first") ) {
                $(".wppu-preloader").delay($delay).fadeOut($delay);
            } else {
                $(".wppu-preloader").addClass("load_first wppu-preloader-loaded");
                $(".wppu-preloader").delay($delay).fadeOut($delay);
            }
        }
    });

} )( jQuery );