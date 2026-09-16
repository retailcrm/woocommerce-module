/**
 * Loyalty program UI for the block-based cart and checkout.
 * All calculations are done in PHP (Store API extension 'retailcrm-loyalty'), this script only displays the data.
 */
(function (wp, wc, settings) {
    if (!wp || !wp.element || !wp.plugins || !wc || !wc.blocksCheckout) {
        return;
    }

    var el = wp.element.createElement;
    var blocksCheckout = wc.blocksCheckout;
    var translations = (settings && settings.translations) || {};

    // The slots are experimental in WooCommerce: render nothing if they are missing
    if (!blocksCheckout.ExperimentalOrderMeta) {
        return;
    }

    function getLoyaltyData(extensions) {
        return (extensions && extensions['retailcrm-loyalty']) || {};
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

    wp.plugins.registerPlugin('retailcrm-loyalty-credit-bonuses', {
        render: function () {
            return el(blocksCheckout.ExperimentalOrderMeta, null, el(CreditBonuses));
        },
        scope: 'woocommerce-checkout'
    });
})(window.wp, window.wc, window.RetailcrmLoyaltyBlocks);
