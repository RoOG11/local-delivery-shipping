// Toggle Delivery User Interface Script
"use strict";

jQuery(document).ready(($) => {

    const manageDeliveryUI = () => {
        const chosenMethod = $('#shipping_method input:radio:checked').attr('id');
        const isLocalDelivery = chosenMethod.includes("local_delivery");

        if (isLocalDelivery) {
            $('.lds_plugin').fadeIn();
        } else {
            $('.lds_plugin').fadeOut();
        }
    };

    manageDeliveryUI();

    $(document).on('change', '#shipping_method input:radio', manageDeliveryUI);
});
