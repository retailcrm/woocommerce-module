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
            url: RetailcrmAdminUrl.url + '/admin-ajax.php?action=retailcrm_get_status_coupon',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1, _ajax_nonce: RetailcrmAdminUrl.nonce},
            dataType: 'json'
        })
            .done(function (response) {
                if (response.error) {
                    alert(response.error);

                    return false;
                }

                if (response.coupon_status !== 'yes') {
                    var checkElement = jQuery('#woocommerce_integration-retailcrm_loyalty');
                    checkElement.parent().css('color', 'red');
                    checkElement.css('border-color', 'red');
                    checkElement.prop('checked', false);

                    if (!jQuery('#coupon_warning').length) {
                        checkElement.parent().parent().append(
                            "<p id='coupon_warning' style='color: red'>" + response.translate.coupon_warning + "</p>"
                        );
                    }
                }
            })
    }
});
