( function( $ ) {
    "use strict";

    if ( $('.wppu--loader').length === 0 ) {
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
    

    function wpppu_init() {
        if ( $effect == 'slideUp' ) {
            $(".wppu--loader").delay($delay).slideUp($transition);
        } 
        else if ( $effect == 'slideDown' ) {
            $(".wppu--loader").delay($delay).animate({
                top: "100%",
            }, $transition );
        } 
        else if ( $effect == 'slideLeft' ) {
            $(".wppu--loader").delay($delay).animate({
                right: "100%",
                opacity: 0,
            }, $transition );
        }
        else if ( $effect == 'slideRight' ) {
            $(".wppu--loader").delay($delay).animate({
                left: "100%",
            }, $transition );
        }
        else if ( $effect == 'blind' ) {
            $(".wppu--loader").delay($delay).hide("blind", $transition);
        }
        else if ( $effect == 'bounce' ) {
            $(".wppu--loader").delay($delay).hide("bounce", $transition);
        }
        else if ( $effect == 'clip' ) {
            $(".wppu--loader").delay($delay).hide("clip", $transition);
        }
        else if ( $effect == 'drop' ) {
            $(".wppu--loader").delay($delay).hide("drop", $transition);
        }
        else if ( $effect == 'fold' ) {
            $(".wppu--loader").delay($delay).hide("fold", $transition);
        }
        else if ( $effect == 'highlight' ) {
            $(".wppu--loader").delay($delay).hide("highlight", $transition);
        }
        else if ( $effect == 'pulsate' ) {
            $(".wppu--loader").delay($delay).hide("pulsate", $transition);
        }
        else if ( $effect == 'scale' ) {
            $(".wppu--loader").delay($delay).hide("scale", $transition);
        }
        else if ( $effect == 'shake' ) {
            $(".wppu--loader").delay($delay).hide("shake", $transition);
        }
        else if ( $effect == 'fadeOut' ) {
            $(".wppu--loader").delay($delay).fadeOut($transition);
        }

        
    }

    
    $(document).ready(function(){

        if ( $options_type == 'css_radio_options' ) {
            if ( $('#main_css_preoad_man').length === 0 ) {
                return false;
            }

            var $cssloader = $('#main_css_preoad_man'),
                $effect = $cssloader.data('effect'),
                $text = $cssloader.data('text'),
                $bg = $cssloader.data('bg'),
                $bg = $cssloader.data('bg'),
                $color = $cssloader.data('color');

            $('#css_preoaders_wppu').waitMe({
                effect: $effect,
                text: $text,
                bg: $bg,
                color: $color,
                sizeW:'',
                sizeH:''
            });
        }

        if( $load_type === "custom" ) {
            setTimeout(function(){
                wpppu_init();
            }, $load_time);
        }

        var $not = '.globalasst--auth';
        var array = '';

        function myFunction(item, index){

        }

        // $('a:not([href^="#"]):not([target="_blank"])').on('click', function(e){


        //     var $self = $(this),
        //         $condition = '';

        //     // if( $not.length ) {
        //     //     array = $not.split(", ");
        //     // }

        //     // console.log( array );
        //     if( array.length ) {
        //         jQuery.each( array, function( i, val ) {
        //             $condition += '!$self.closest(val).length';
        //         });
        //     }

        //     if( 
        //         !$self.closest('#wpadminbar').length &&
        //         $condition
        //     ) {
        //         $('.wppu--loader').show();
        //     }


        // });
      
    

    });


    $( window ).on("load", function() {
        
        if( $load_type !== "custom" ) {
            
            wpppu_init();
        }
    });

} )( jQuery );