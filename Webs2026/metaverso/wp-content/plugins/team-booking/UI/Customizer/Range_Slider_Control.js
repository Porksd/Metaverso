(function ($) {
    "use strict";

    wp.customize.bind('ready', function () {
        rangeSlider();
    });

    var rangeSlider = function () {
        var slider = $('.range-slider');
        var range = $('.range-slider__range');
        var value = $('.range-slider__value');

        slider.each(function () {

            value.each(function () {
                var value = $(this).prev().attr('value');
                var suffix = $(this).prev().attr('suffix') || '';
                $(this).html(value + suffix);
            });

            range.on('input', function () {
                var suffix = $(this).attr('suffix') || '';
                $(this).next(value).html(this.value + suffix);
            });
        });
    };

})(jQuery);