(function ($) {
    'use strict';

    $(document).ready(function() {
        $('.eb-pro-activate-plugin').on('change', function() {
            // submit the form
            $(this).closest('form').submit();
        });

        $('.eb-template-restore').on('click', function(e) {
            e.preventDefault();

            var key = $(this).data('template');

            // hide eb_template_actions with data-template
            $('.eb_template_actions[data-template="'+key+'"]').hide();
            $('.eb-template-restore-confirm[data-template="'+key+'"]').css('display', 'flex');
        });

        $('.eb-template-restore-confirm-no').on('click', function(e) {
            e.preventDefault();

            var key = $(this).data('template');

            // show eb_template_actions with data-template
            $('.eb_template_actions[data-template="'+key+'"]').show();
            $('.eb-template-restore-confirm[data-template="'+key+'"]').hide();
        });
    });
})(jQuery);
