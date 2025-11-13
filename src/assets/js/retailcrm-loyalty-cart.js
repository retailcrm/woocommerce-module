function inputLoyaltyCode() {
    let couponInput = document.getElementById('coupon_code');
    let couponCode = document.getElementById('input_loyalty_code');

    couponInput.value = couponCode.innerText;
}

function bonus_charge() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    let bonusCount = parseInt(document.getElementById('chargeBonus').value);
    let max = parseInt(document.getElementById('hidden-count').textContent);

    if (bonusCount > max) {
        let error = document.getElementById('error');
        error.innerText = RetailcrmAdminCoupon.translations.incorrect_count;
        error.hidden = false;
        error.style.color = 'red';
        return;
    }

    error.hidden = true;

    jQuery.ajax({
        url: '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: {
            action: 'create_loyalty_coupon',
            count: bonusCount,
            nonce: RetailcrmAdminCoupon.loyalty_nonce
        },
        success: function (response) {
            if (response.success) {
                jQuery('.charge-button').text(RetailcrmAdminCoupon.translations.using_bonuses);
                applyCouponToCart(response.data.coupon_code);
            } else {
                jQuery('.charge-button').prop('disabled', false).text(RetailcrmAdminCoupon.translations.error_occurred);
            }
        },
        error: function () {
            jQuery('.charge-button').prop('disabled', false).text(RetailcrmAdminCoupon.translations.error_occurred);
        }
    });

    function applyCouponToCart(couponCode) {
        jQuery.ajax({
            url: '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'apply_coupon_to_cart',
                coupon_code: couponCode,
                nonce: RetailcrmAdminCoupon.apply_coupon_nonce
            },
            success: function (response) {
                if (response.success) {
                    setTimeout(function () {
                        location.reload(true);
                    }, 1000);
                } else {
                    jQuery('.charge-button').prop('disabled', false).text(RetailcrmAdminCoupon.translations.error_occurred);
                }
            },
            error: function () {
                jQuery('.charge-button').prop('disabled', false).text(RetailcrmAdminCoupon.translations.error_occurred);
            }
        });
    }
}