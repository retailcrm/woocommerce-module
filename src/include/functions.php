<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// @codeCoverageIgnoreStart
// TODO: There is a task to analyze the work
function get_wc_shipping_methods_by_zones($enhanced = false)
{
    $result = array();

    $shippingZones = WC_Shipping_Zones::get_zones();
    $defaultZone = WC_Shipping_Zones::get_zone_by();

    $shippingZones[$defaultZone->get_id()] = array(
        $defaultZone->get_data(),
        'zone_id' => $defaultZone->get_id(),
        'formatted_zone_location' => $defaultZone->get_formatted_location(),
        'shipping_methods' => $defaultZone->get_shipping_methods(false)
    );

    if ($shippingZones) {
        foreach ($shippingZones as $code => $shippingZone) {
            foreach ($shippingZone['shipping_methods'] as $key => $shipping_method) {
                $shipping_methods = array(
                    'id' => $shipping_method->id,
                    'instance_id' => $shipping_method->instance_id,
                    'title' => $shipping_method->title
                );

                if ($enhanced) {
                    $shipping_code = $shipping_method->id;
                } else {
                    $shipping_code = $shipping_method->id . ':' . $shipping_method->instance_id;
                }

                if (!isset($result[$shipping_code])) {
                    $result[$shipping_code] = array(
                        'name' => $shipping_method->method_title,
                        'enabled' => $shipping_method->enabled,
                        'description' => $shipping_method->method_description,
                        'title' => $shipping_method->title
                    );
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

    $result = array();

    foreach ($shipping_methods as $code => $shipping) {
        $result[$code] = array(
            'name' => $shipping->method_title,
            'enabled' => $shipping->enabled,
            'description' => $shipping->method_description,
            'title' => $shipping->title ? $shipping->title : $shipping->method_title
        );
    }

    return apply_filters('retailcrm_shipping_list', WC_Retailcrm_Plugin::clearArray($result));
}

function retailcrm_get_delivery_service($method_id, $instance_id)
{
    $shippings_by_zone = get_wc_shipping_methods_by_zones(true);
    $method = explode(':', $method_id);
    $method_id = $method[0];
    $shipping = isset($shippings_by_zone[$method_id]) ? $shippings_by_zone[$method_id] : array();

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
    $ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);

    return (
        (in_array($ABSPATH_MY . 'wp-login.php', get_included_files())
        || in_array($ABSPATH_MY . 'wp-register.php', get_included_files()))
        || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php')
        || $_SERVER['PHP_SELF'] == '/wp-login.php'
    );
}
