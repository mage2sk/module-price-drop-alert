define(['jquery', 'Magento_Ui/js/modal/confirm'], function ($, confirm) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function (event) {
            var form = document.getElementById($(element).data('form'));

            event.preventDefault();
            if (!form) {
                return;
            }
            confirm({
                title: $(form).data('confirmTitle'),
                content: $(form).data('confirmMessage'),
                actions: {
                    confirm: function () {
                        form.submit();
                    }
                }
            });
        });
    };
});
