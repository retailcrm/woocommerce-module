/**
 * Loyalty program UI for the block-based cart and checkout.
 * All calculations are done in PHP (Store API extension 'retailcrm-loyalty'), this script only displays the data.
 */
(function (wp, wc, settings) {
    if (!wp || !wp.element || !wp.plugins || !wc || !wc.blocksCheckout) {
        return;
    }

    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var blocksCheckout = wc.blocksCheckout;
    var translations = (settings && settings.translations) || {};

    // The slots are experimental in WooCommerce: render nothing if they are missing
    if (!blocksCheckout.ExperimentalOrderMeta || !blocksCheckout.ExperimentalDiscountsMeta) {
        return;
    }

    function getLoyaltyData(extensions) {
        return (extensions && extensions['retailcrm-loyalty']) || {};
    }

    function post(action, data) {
        var body = new FormData();

        body.append('action', action);
        body.append('nonce', settings.nonce);

        Object.keys(data).forEach(function (key) {
            body.append(key, data[key]);
        });

        return fetch(settings.ajax_url, {method: 'POST', credentials: 'same-origin', body: body})
            .then(function (response) {
                return response.json();
            })
            .then(function (json) {
                if (!json || !json.success) {
                    throw new Error((json && json.data) || 'Request failed');
                }

                return json.data;
            });
    }

    // Form for charging bonuses in the cart: the coupon is created by the plugin and applied via the cart store,
    // so the cart is updated without reloading the page
    function ChargeForm(props) {
        var loyalty = getLoyaltyData(props.extensions);
        var valueState = useState('');
        var errorState = useState('');
        var loadingState = useState(false);
        var value = valueState[0], setValue = valueState[1];
        var error = errorState[0], setError = errorState[1];
        var loading = loadingState[0], setLoading = loadingState[1];
        var maxCharge = Math.floor(loyalty.maxCharge || 0);

        // Bonuses are charged by a single coupon: the form is hidden while it is applied
        if (props.context !== 'woocommerce/cart' || !loyalty.active || maxCharge <= 0 || loyalty.appliedBonus > 0) {
            return null;
        }

        function charge(event) {
            event.preventDefault();

            var count = Number(value);

            if (!Number.isInteger(count) || count <= 0 || count > maxCharge) {
                setError(translations.incorrect_count);

                return;
            }

            setError('');
            setLoading(true);

            post('create_loyalty_coupon', {count: count})
                .then(function (data) {
                    return wp.data.dispatch('wc/store/cart').applyCoupon(data.coupon_code);
                })
                .then(function () {
                    setValue('');
                })
                .catch(function () {
                    setError(translations.error_occurred);
                })
                .finally(function () {
                    setLoading(false);
                });
        }

        // Same markup as the coupon form, so the block styles are applied
        return el(
            'div',
            {className: 'wc-block-components-totals-wrapper retailcrm-loyalty-charge'},
            el(
                'div',
                {className: 'wc-block-components-totals-item'},
                el(
                    'form',
                    // noValidate: the count is validated by the form itself to show the plugin error message
                    {className: 'wc-block-components-totals-coupon__form', style: {width: '100%'}, noValidate: true, onSubmit: charge},
                    el(
                        'div',
                        {
                            className: 'wc-block-components-text-input wc-block-components-totals-coupon__input'
                                + (value !== '' ? ' is-active' : '')
                        },
                        el('input', {
                            id: 'retailcrm-loyalty-charge-count',
                            type: 'number',
                            min: 1,
                            max: maxCharge,
                            step: 1,
                            value: value,
                            disabled: loading,
                            onChange: function (event) {
                                setValue(event.target.value);
                                setError('');
                            }
                        }),
                        el('label', {htmlFor: 'retailcrm-loyalty-charge-count'}, translations.bonus_count)
                    ),
                    el(
                        'button',
                        {
                            type: 'submit',
                            className: 'wc-block-components-button wp-element-button wc-block-components-totals-coupon__button contained',
                            disabled: loading || value === ''
                        },
                        el('div', {className: 'wc-block-components-button__text'}, loading ? translations.using : translations.use_bonuses)
                    )
                ),
                error ? el('div', {className: 'wc-block-components-validation-error', role: 'alert'}, el('p', null, error)) : null,
                el(
                    'div',
                    {className: 'wc-block-components-totals-item__description'},
                    translations.possible_write_off + ' ' + maxCharge + ' ' + translations.bonuses
                )
            )
        );
    }

    // The slot passes { extensions, cart, context } to its children, the data is updated with the cart
    function CreditBonuses(props) {
        var loyalty = getLoyaltyData(props.extensions);

        if (props.context !== 'woocommerce/checkout' || !(loyalty.creditBonuses > 0)) {
            return null;
        }

        // Same markup as the order summary totals rows, so the block styles apply the paddings
        return el(
            'div',
            {className: 'wc-block-components-totals-wrapper retailcrm-loyalty-credit-bonuses'},
            el(
                'div',
                {className: 'wc-block-components-totals-item'},
                el('span', {className: 'wc-block-components-totals-item__label'}, translations.credit_bonuses),
                el(
                    'span',
                    {className: 'wc-block-components-totals-item__value', style: {color: 'green', fontWeight: 'bold'}},
                    loyalty.creditBonuses
                )
            )
        );
    }

    wp.plugins.registerPlugin('retailcrm-loyalty-charge-form', {
        render: function () {
            return el(blocksCheckout.ExperimentalDiscountsMeta, null, el(ChargeForm));
        },
        scope: 'woocommerce-checkout'
    });

    wp.plugins.registerPlugin('retailcrm-loyalty-credit-bonuses', {
        render: function () {
            return el(blocksCheckout.ExperimentalOrderMeta, null, el(CreditBonuses));
        },
        scope: 'woocommerce-checkout'
    });
})(window.wp, window.wc, window.RetailcrmLoyaltyBlocks);
