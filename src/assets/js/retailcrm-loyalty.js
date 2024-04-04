jQuery(function() {
    if (jQuery('#woocommerce_integration-retailcrm_loyalty').is(':checked')) {
        checkActiveCoupon();
    }

    jQuery('#woocommerce_integration-retailcrm_loyalty').change(function () {
        if (this.checked) {
            checkActiveCoupon();
        }
    })

    function checkActiveCoupon()
    {
        jQuery.ajax({
            url: AdminUrl.url + '/admin-ajax.php?action=get_status_coupon',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1},
            dataType: 'json'
        })
            .done(function (response) {
                if (response.coupon_status !== 'yes') {
                    alert('lol');
                    var checkElement = jQuery('#woocommerce_integration-retailcrm_loyalty');
                    checkElement.parent().css('color', 'red');
                    checkElement.css('border-color', 'red');
                    checkElement.parent().parent().append("<p style='color: red'>Test</p>");
                    checkElement.prop('checked', false);
                }
            })
    }
});
