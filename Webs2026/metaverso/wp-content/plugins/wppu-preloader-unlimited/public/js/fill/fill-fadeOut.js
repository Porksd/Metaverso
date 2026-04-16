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

        if( $('.wppu_fill_progress_bar').length > 0 ) {
        	$( ".wppu_fill_progress_bar" ).animate({ width: "100%"}, { 
				duration: dur,
				complete: function(){
					$(".wppu-preloader").addClass("load_me_first");
				}
			});
        }
        if ( $('#wppu-fill-loader-thunb') ) {
        	$( "#wppu-fill-loader-thunb .wppu-fill-thumbnail-fill" ).animate({height: "100%"}, { 
				duration: dur,
				complete: function(){
					$(".wppu-preloader").addClass("load_me_first");
				}
			});
        }
        if ( $('.wppu_fill_counter') ) {
        	$('.wppu_fill_counter span').each(function () {
			  var $this = $(this);
			  $({ Counter: 0 }).animate({ Counter: 100 }, {
			    duration: dur,
			    easing: 'swing',
			    step: function () {
			      $this.text(Math.ceil(this.Counter));
			    }
			  });
			});
		}
		
		if( $load_type === "custom" ) {
            setTimeout(function(){
                if (jQuery(".wppu-preloader").hasClass("load_me_first") ) {
					jQuery(".wppu-preloader").delay($delay).fadeOut($transition);
					jQuery({countNum: jQuery('.wppu_fill_counter span').text()}).animate({countNum: 100}, {
					  duration: $delay,
					  easing:'linear',
					  step: function() {
						jQuery('.wppu_fill_counter').text(Math.floor(this.countNum) + '%');
					  },
					  complete: function() {
						jQuery('.wppu_fill_counter').text(this.countNum + '%');
					  }
					});
				} else {
					jQuery(".wppu-preloader").addClass("load_first wppu-preloader-loaded");
					jQuery(".wppu-preloader").delay(900+$delay).fadeOut($transition);
					jQuery({countNum: jQuery('.wppu_fill_counter span').text()}).animate({countNum: 100}, {
					  duration: 850+$delay,
					  easing:'linear',
					  step: function() {
						jQuery('.wppu_fill_counter').text(Math.floor(this.countNum) + '%');
					  },
					  complete: function() {
						jQuery('.wppu_fill_counter').text(this.countNum + '%');
					  }
					});
				}
            }, $load_time);
        }
    });

    $( window ).on("load", function() {
		if( $load_type !== "custom" ) {
			if (jQuery(".wppu-preloader").hasClass("load_me_first") ) {
				jQuery(".wppu-preloader").delay($delay).fadeOut($transition);
				jQuery({countNum: jQuery('.wppu_fill_counter span').text()}).animate({countNum: 100}, {
				duration: $delay,
				easing:'linear',
				step: function() {
					jQuery('.wppu_fill_counter').text(Math.floor(this.countNum) + '%');
				},
				complete: function() {
					jQuery('.wppu_fill_counter').text(this.countNum + '%');
				}
				});
			} else {
				jQuery(".wppu-preloader").addClass("load_first wppu-preloader-loaded");
				jQuery(".wppu-preloader").delay(900+$delay).fadeOut($transition);
				jQuery({countNum: jQuery('.wppu_fill_counter span').text()}).animate({countNum: 100}, {
				duration: 850+$delay,
				easing:'linear',
				step: function() {
					jQuery('.wppu_fill_counter').text(Math.floor(this.countNum) + '%');
				},
				complete: function() {
					jQuery('.wppu_fill_counter').text(this.countNum + '%');
				}
				});
			}
		}
    });

} )( jQuery );