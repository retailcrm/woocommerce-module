jQuery(function() {
    jQuery('#loyaltyRegisterForm').on("submit", function (event) {
        var termsCheck = jQuery('#termsLoyalty');
        var privacyCheck = jQuery('#privacyLoyalty');

        if (termsCheck.length) {
            if (!termsCheck.is(':checked')) {
                event.preventDefault();
                return false;
            }
        }

        if (privacyCheck.length) {
            if (!privacyCheck.is(':checked')) {
                event.preventDefault();
                return false;
            }
        }

        let phone = jQuery('#phoneLoyalty');

        if (!phone.val().match(/(?:\+|\d)[\d\-\(\) ]{7,}\d/)) {
            phone.parent().append('<span style="color: red" id="warningLoyaltyPhone">test</span>')

            event.preventDefault();
            return false;
        } else {
            jQuery('#warningLoyaltyPhone').remove();
        }

        jQuery.ajax({
            url: LoyaltyUrl.url + '/admin-ajax.php?action=register_customer_loyalty',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1, phone: phone.val(), userId: customerId},
            dataType: 'json'
        })
            .done(function (response) {
                if (response.hasOwnProperty('error')) {
                    jQuery('#loyaltyRegisterForm').append('<p style="color: red">'+ response.error + '</p>')

                    event.preventDefault();
                    return false;
                } else {
                    location.reload();
                }
            })

        event.preventDefault();
    });

    jQuery('#loyaltyActivateForm').on("submit", function (event) {
        var activateCheckbox = jQuery('#loyaltyActiveCheckbox');

        if (activateCheckbox.length) {
            if (!activateCheckbox.is(':checked')) {
                event.preventDefault();
                return false;
            }
        }

        jQuery.ajax({
            url: LoyaltyUrl.url + '/admin-ajax.php?action=activate_customer_loyalty',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1, loyaltyId: loyaltyId},
            dataType: 'json'
        })
            .done(function (response) {
                if (response.hasOwnProperty('error')) {
                    jQuery('#loyaltyRegisterForm').append('<p style="color: red">'+ response.error + '</p>')

                    event.preventDefault();
                    return false;
                } else {
                    location.reload();
                }
            })

        event.preventDefault();
    });
});
