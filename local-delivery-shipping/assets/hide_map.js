// JavaScript Document
"use strict";

jQuery(document).ready(function($) {

    function toggleCustomBox() {
        var selectedMethod = $('input:checked', '#shipping_method').attr('id');
        if (selectedMethod.indexOf("local_delivery") >= 0) {
            $('.lds_plugin').show();
        } else {
            $('.lds_plugin').hide();
        };
    };

    $(document).ready(toggleCustomBox);

    $(document).on('change', '#shipping_method input:radio', toggleCustomBox);
});
