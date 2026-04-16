( function( $ ) {
    "use strict";

    if ( $('#pace_wow').length === 0 ) {
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

    if( $load_type === "custom" ) {
        setTimeout(function(){
            Pace.on("hide", function() {
                if ( $effect == 'slideUp' ) {
                    $("#pace_wow").delay($delay).slideUp($transition);
                } 
                else if ( $effect == 'slideDown' ) {
                    $("#pace_wow").delay($delay).animate({
                        top: "100%",
                    }, $transition );
                } 
                else if ( $effect == 'slideLeft' ) {
                    $("#pace_wow").delay($delay).animate({
                        right: "100%",
                        opacity: 0,
                    }, $transition );
                }
                else if ( $effect == 'slideRight' ) {
                    $("#pace_wow").delay($delay).animate({
                        left: "100%",
                    }, $transition );
                }
                else if ( $effect == 'blind' ) {
                    $("#pace_wow").delay($delay).hide("blind", $transition);
                }
                else if ( $effect == 'bounce' ) {
                    $("#pace_wow").delay($delay).hide("bounce", $transition);
                }
                else if ( $effect == 'clip' ) {
                    $("#pace_wow").delay($delay).hide("clip", $transition);
                }
                else if ( $effect == 'drop' ) {
                    $("#pace_wow").delay($delay).hide("drop", $transition);
                }
                else if ( $effect == 'fold' ) {
                    $("#pace_wow").delay($delay).hide("fold", $transition);
                }
                else if ( $effect == 'highlight' ) {
                    $("#pace_wow").delay($delay).hide("highlight", $transition);
                }
                else if ( $effect == 'pulsate' ) {
                    $("#pace_wow").delay($delay).hide("pulsate", $transition);
                }
                else if ( $effect == 'scale' ) {
                    $("#pace_wow").delay($delay).hide("scale", $transition);
                }
                else if ( $effect == 'shake' ) {
                    $("#pace_wow").delay($delay).hide("shake", $transition);
                }
                else if ( $effect == 'fadeOut' ) {
                    $("#pace_wow").delay($delay).fadeOut($transition);
                }
                
            });
        }, $load_time);
    } else {
        Pace.on("hide", function() {
            if ( $effect == 'slideUp' ) {
                $("#pace_wow").delay($delay).slideUp($transition);
            } 
            else if ( $effect == 'slideDown' ) {
                $("#pace_wow").delay($delay).animate({
                    top: "100%",
                }, $transition );
            } 
            else if ( $effect == 'slideLeft' ) {
                $("#pace_wow").delay($delay).animate({
                    right: "100%",
                    opacity: 0,
                }, $transition );
            }
            else if ( $effect == 'slideRight' ) {
                $("#pace_wow").delay($delay).animate({
                    left: "100%",
                }, $transition );
            }
            else if ( $effect == 'blind' ) {
                $("#pace_wow").delay($delay).hide("blind", $transition);
            }
            else if ( $effect == 'bounce' ) {
                $("#pace_wow").delay($delay).hide("bounce", $transition);
            }
            else if ( $effect == 'clip' ) {
                $("#pace_wow").delay($delay).hide("clip", $transition);
            }
            else if ( $effect == 'drop' ) {
                $("#pace_wow").delay($delay).hide("drop", $transition);
            }
            else if ( $effect == 'fold' ) {
                $("#pace_wow").delay($delay).hide("fold", $transition);
            }
            else if ( $effect == 'highlight' ) {
                $("#pace_wow").delay($delay).hide("highlight", $transition);
            }
            else if ( $effect == 'pulsate' ) {
                $("#pace_wow").delay($delay).hide("pulsate", $transition);
            }
            else if ( $effect == 'scale' ) {
                $("#pace_wow").delay($delay).hide("scale", $transition);
            }
            else if ( $effect == 'shake' ) {
                $("#pace_wow").delay($delay).hide("shake", $transition);
            }
            else if ( $effect == 'fadeOut' ) {
                $("#pace_wow").delay($delay).fadeOut($transition);
            }
            
        });
    }

    

} )( jQuery );