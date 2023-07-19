<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// @codeCoverageIgnoreStart
function get_wc_shipping_methods_by_zones($enhanced = false)
{
    $result = [];

    $shippingZones = WC_Shipping_Zones::get_zones();
    $defaultZone = WC_Shipping_Zones::get_zone_by();

    $shippingZones[$defaultZone->get_id()] = [
        $defaultZone->get_data(),
        'zone_id' => $defaultZone->get_id(),
        'formatted_zone_location' => $defaultZone->get_formatted_location(),
        'shipping_methods' => $defaultZone->get_shipping_methods(false)
    ];

    if ($shippingZones) {
        foreach ($shippingZones as $code => $shippingZone) {
            foreach ($shippingZone['shipping_methods'] as $key => $shipping_method) {
                $shipping_methods = [
                    'id' => $shipping_method->id,
                    'instance_id' => $shipping_method->instance_id,
                    'title' => $shipping_method->title
                ];

                if ($enhanced) {
                    $shipping_code = $shipping_method->id;
                } else {
                    $shipping_code = $shipping_method->id . ':' . $shipping_method->instance_id;
                }

                if (!isset($result[$shipping_code])) {
                    $result[$shipping_code] = [
                        'name' => $shipping_method->method_title,
                        'enabled' => $shipping_method->enabled,
                        'description' => $shipping_method->method_description,
                        'title' => $shipping_method->title
                    ];
                }

                if ($enhanced) {
                    $result[$shipping_method->id]['shipping_methods'][$shipping_method->id . ':' . $shipping_method->instance_id] = $shipping_methods;
                    unset($shipping_methods);
                }
            }
        }
    }

    return $result;
}
// @codeCoverageIgnoreEnd

function get_wc_shipping_methods()
{
    $wc_shipping = WC_Shipping::instance();
    $shipping_methods = $wc_shipping->get_shipping_methods();

    $result = [];

    foreach ($shipping_methods as $code => $shipping) {
        $result[$code] = [
            'name' => $shipping->method_title,
            'enabled' => $shipping->enabled,
            'description' => $shipping->method_description,
            'title' => $shipping->title ? $shipping->title : $shipping->method_title
        ];
    }

    return apply_filters('retailcrm_shipping_list', WC_Retailcrm_Plugin::clearArray($result));
}

function retailcrm_get_delivery_service($method_id, $instance_id)
{
    $shippings_by_zone = get_wc_shipping_methods_by_zones(true);
    $method = explode(':', $method_id);
    $method_id = $method[0];
    $shipping = $shippings_by_zone[$method_id] ?? [];

    if ($shipping && isset($shipping['shipping_methods'][$method_id . ':' . $instance_id])) {
        return $shipping['shipping_methods'][$method_id . ':' . $instance_id];
    }

    return false;
}

/**
 * @param $id
 * @param $settings
 *
 * @return false|WC_Product|null
 */
function retailcrm_get_wc_product($id, $settings)
{
    if (
        isset($settings['bind_by_sku'])
        && $settings['bind_by_sku'] == WC_Retailcrm_Base::YES
    ) {
        $id = wc_get_product_id_by_sku($id);
    }

    return wc_get_product($id);
}

/**
 * Returns true if either wordpress debug mode or module debugging is enabled
 *
 * @return bool
 */
function retailcrm_is_debug()
{
    $options =  get_option(WC_Retailcrm_Base::$option_key);

    if (isset($options['debug_mode']) === true && $options['debug_mode'] === WC_Retailcrm_Base::YES) {
        return true;
    }
}

/**
 *  Returns true if current page equals wp-login
 *
 * @return bool
 */
function is_wplogin()
{
    $ABSPATH_MY = str_replace(['\\','/'], DIRECTORY_SEPARATOR, ABSPATH);

    return (
        (in_array($ABSPATH_MY . 'wp-login.php', get_included_files())
        || in_array($ABSPATH_MY . 'wp-register.php', get_included_files()))
        || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php')
        || $_SERVER['PHP_SELF'] == '/wp-login.php'
    );
}

/**
 * If a tax class with a standart rate is selected, woocommerce_shipping_tax_class = ''
 * If a tax class with a zero rate is selected, woocommerce_shipping_tax_class = zero-rate
 * If a tax class with a reduced rate is selected, woocommerce_shipping_tax_class = reduced-rate
 * If the tax is calculated based on the items in the cart, woocommerce_shipping_tax_class = inherit
 *
 * @return mixed
 */
function getShippingRate()
{
    if (!isset(WC()->cart)) {
        return null;
    }

    $shippingRates = WC_Tax::get_shipping_tax_rates();

    // Only one tax can be selected for shipping
    if (is_array($shippingRates)) {
        $shippingRates = array_shift($shippingRates);
    }

    return $shippingRates['rate'] ?? $shippingRates;
}

/**
 *  Get order item rate.
 *
 * @return mixed
 */
function getOrderItemRate($wcOrder)
{
    $orderItemTax = $wcOrder->get_taxes();

    if (is_array($orderItemTax)) {
        $orderItemTax = array_shift($orderItemTax);
    }

    return $orderItemTax instanceof WC_Order_Item_Tax ? $orderItemTax->get_rate_percent() : null;
}

function calculatePriceExcludingTax($priceIncludingTax, $rate)
{
    return round($priceIncludingTax / (1 + $rate / 100), wc_get_price_decimals());
}

/**
 * Write base logs in retailcrm file.
 *
 * @codeCoverageIgnore
 */
function writeBaseLogs($message)
{
    WC_Retailcrm_Logger::addCaller(__METHOD__, $message);
}
