<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function get_wc_shipping_methods($enhanced = false) {
    $result = array();

    $shippingZonesObj = new WC_Shipping_Zones();
    $shippingZones = $shippingZonesObj->get_zones();

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

    return $result;
}
