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

            if (!jQuery('#warningLoyaltyPhone').length) {
                phone.parent().append('<span style="color: red" id="warningLoyaltyPhone">' + messagePhone + '</span>')
            }

            event.preventDefault();
            return false;
        } else {
            jQuery('#warningLoyaltyPhone').remove();
        }

        jQuery.ajax({
            url: loyaltyUrl.url + '/admin-ajax.php?action=register_customer_loyalty',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1, phone: phone.val(), userId: customerId, _ajax_nonce: nonce},
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
            url: loyaltyUrl.url + '/admin-ajax.php?action=activate_customer_loyalty',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1, loyaltyId: loyaltyId, _ajax_nonce: nonce},
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

    jQuery('.popup-open-loyalty').click(function() {
        if (jQuery(this).attr('id') === 'terms-popup') {
            jQuery('#popup-loyalty-text').html(termsLoyalty);
        } else {
            jQuery('#popup-loyalty-text').html(privacyLoyalty);
        }

        jQuery('.popup-fade-loyalty').fadeIn();
        return false;
    });

    jQuery('.popup-close-loyalty').click(function() {
        jQuery(this).parents('.popup-fade-loyalty').fadeOut();
        return false;
    });

    jQuery(document).keydown(function(e) {
        if (e.keyCode === 27) { // Key Escape
            e.stopPropagation();
            jQuery('.popup-fade-loyalty').fadeOut();
        }
    });

    jQuery('.popup-fade-loyalty').click(function(e) {
        if (jQuery(e.target).closest('.popup-loyalty').length == 0) {
            jQuery(this).fadeOut();
        }
    });

    jQuery('#phoneLoyalty').keydown(function (e) {
        let key = e.key;

        return (key >= '0' && key <= '9') || key == '+' || key == '(' || key == ')'|| key == '-' ||
            key == 'ArrowLeft' || key == 'ArrowRight' || key == 'Delete' || key == 'Backspace';
    });
});
