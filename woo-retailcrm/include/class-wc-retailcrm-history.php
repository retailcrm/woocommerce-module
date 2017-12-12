<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_History
 * @category Integration
 * @author   RetailCRM
 */

if ( ! class_exists( 'WC_Retailcrm_History' ) ) :

    /**
     * Class WC_Retailcrm_History
     */
    class WC_Retailcrm_History
    {
        protected $startDateOrders;
        protected $startDateCustomers;
        protected $startDate;

        public function __construct()
        {
            $this->retailcrm_settings = get_option( 'woocommerce_integration-retailcrm_settings' );

            if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) {
                include_once( WP_PLUGIN_DIR . '/woo-retailcrm/include/api/class-wc-retailcrm-proxy.php' );
            }

            $this->retailcrm = new WC_Retailcrm_Proxy(
                $this->retailcrm_settings['api_url'],
                $this->retailcrm_settings['api_key'],
                $this->retailcrm_settings['api_version']
            );

            $this->startDate = new DateTime(date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s')))));
            $this->startDateOrders = $this->startDate;
            $this->startDateCustomers = $this->startDate;
        }

        public function getHistory()
        {
            if (isset($this->retailcrm_settings['history_orders'])) {
                $this->startDateOrders = new DateTime($this->retailcrm_settings['history_orders']);
            }

            if (isset($this->retailcrm_settings['history_customers'])) {
                $this->startDateCustomers = new DateTime($this->retailcrm_settings['history_orders']);
            }

            $this->ordersHistory($this->startDateOrders->format('Y-m-d H:i:s'));
            
            $this->customersHistory($this->startDateCustomers->format('Y-m-d H:i:s'));
        }

        protected function customersHistory($date)
        {
            $response = $this->retailcrm->customersHistory(array('startDate' => $date));

            if ($response->isSuccessful()) {
                $generatedAt = $response->generatedAt;

                foreach ($response['history'] as $record) {
                    if ($record['source'] == 'api' && $record['apiKey']['current'] == true) {
                        continue;
                    }

                    $this->removeFuncsHook();

                    if ($record['field'] == 'first_name' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'first_name', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'last_name' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'last_name', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'email' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_email', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'phones' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_phone', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.region' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_state', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.index' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_postcode', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.country' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_country', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.city' && $record['customer']['externalId']) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_city', $record['newValue']);
                        }
                    }

                    $this->addFuncsHook();
                }
            }

            if (empty($response)) {
                return;
            }

            $this->retailcrm_settings['history_customers'] = $generatedAt;
            update_option('woocommerce_integration-retailcrm_settings', $this->retailcrm_settings);
        }

        protected function ordersHistory($date)
        {
            $options = array_flip(array_filter($this->retailcrm_settings));

            $response = $this->retailcrm->ordersHistory(array('startDate' => $date));

            if ($response->isSuccessful()) {
                $generatedAt = $response->generatedAt;

                foreach ($response['history'] as $record) {
                    if ($record['source'] == 'api' && $record['apiKey']['current'] == true) {
                        continue;
                    }

                    $this->removeFuncsHook();

                    if ($record['field'] == 'status' && !empty($record['newValue']) && !empty($record['oldValue'])) {
                        $newStatus = $record['newValue']['code'];
                        if (!empty($options[$newStatus]) && !empty($record['order']['externalId'])) {
                            $order = new WC_Order($record['order']['externalId']);
                            $order->update_status($options[$newStatus]);
                        }
                    }

                    elseif($record['field'] == 'order_product' && $record['newValue']) {
                        $product = wc_get_product($record['item']['offer']['externalId']);
                        $order = new WC_Order($record['order']['externalId']);
                        $order->add_product($product, $record['item']['quantity']);

                        $this->update_total($order);
                    }

                    elseif($record['field'] == 'order_product.quantity' && $record['newValue']) {

                        $order = new WC_Order($record['order']['externalId']);
                        $product = wc_get_product($record['item']['offer']['externalId']);
                        $items = $order->get_items();

                        foreach ($items as $order_item_id => $item) {
                            if ($item['variation_id'] != 0 ) {
                                $offer_id = $item['variation_id'];
                            } else {
                                $offer_id = $item['product_id'];
                            } 
                            if ($offer_id == $record['item']['offer']['externalId']) {
                                wc_delete_order_item($order_item_id);  
                                $order->add_product($product, $record['newValue']);
                                $this->update_total($order);
                            }   
                        }    
                    }

                    elseif ($record['field'] == 'order_product' && !$record['newValue']) {
                        $order = new WC_Order($record['order']['externalId']);
                        $items = $order->get_items();

                        foreach ($items as $order_item_id => $item) {
                            if ($item['variation_id'] != 0 ) {
                                $offer_id = $item['variation_id'];
                            } else {
                                $offer_id = $item['product_id'];
                            } 
                            if ($offer_id == $record['item']['offer']['externalId']) {
                                wc_delete_order_item($order_item_id);  
                                $this->update_total($order);
                            }   
                        }
                    }

                    elseif ($record['field'] == 'delivery_type') {
                        $newValue = $record['newValue']['code'];

                        if (!empty($options[$newValue]) && !empty($record['order']['externalId'])) {
                            if (isset($options[$newValue])) {
                                $order = new WC_Order($record['order']['externalId']);
                                $items = $order->get_items('shipping');
                                $item_id = $this->getShippingItemId($items);
                                $crmOrder = $this->retailcrm->ordersGet($record['order']['externalId']);
                                $shipping_methods = get_wc_shipping_methods(true);

                                if (isset($shipping_methods[$options[$newValue]])) {
                                    $method_id = $options[$newValue];
                                } else {
                                    $method_id = explode(':', $options[$newValue]);
                                    $method_id = $method_id[0];
                                    $shipping_method = $shipping_methods[$method_id]['shipping_methods'][$options[$newValue]];
                                }

                                if ( is_object($crmOrder)) {
                                    if ($crmOrder->isSuccessful()) {
                                        $deliveryCost = isset($crmOrder['order']['delivery']['cost']) ? $crmOrder['order']['delivery']['cost'] : 0;
                                    }
                                }

                                $args = array(
                                    'method_id' => $options[$newValue],
                                    'method_title' => isset($shipping_method) ? $shipping_method['title'] : $shipping_methods[$options[$newValue]]['name'],
                                    'total' => $deliveryCost
                                );

                                $item = $order->get_item((int)$item_id);
                                $item->set_order_id((int)$order->get_id());
                                $item->set_props($args);
                                $item->save();
                            }

                            $updateOrder = new WC_Order((int)$order->get_id());
                            $this->update_total($updateOrder);
                        }
                    }

                    elseif ($record['field'] == 'delivery_address.region') {
                        $order = new WC_Order($record['order']['externalId']);
                        $order->set_shipping_state($record['newValue']);
                    }

                    elseif ($record['field'] == 'delivery_address.city') {
                        $order = new WC_Order($record['order']['externalId']);
                        $order->set_shipping_city($record['newValue']);
                    }

                    elseif ($record['field'] == 'delivery_address.street') {
                        $order = new WC_Order($record['order']['externalId']);
                        $order->set_shipping_address_1($record['newValue']);
                    }

                    elseif ($record['field'] == 'delivery_address.building') {
                        $order = new WC_Order($record['order']['externalId']);
                        $order->set_shipping_address_2($record['newValue']);
                    }

                    elseif ($record['field'] == 'payment_type') {
                        $order = new WC_Order($record['order']['externalId']);
                        $newValue = $record['newValue']['code'];
                        if (!empty($options[$newValue]) && !empty($record['order']['externalId'])) {
                            $payment = new WC_Payment_Gateways();
                            $payment_types = $payment->get_available_payment_gateways();
                            if (isset($payment_types[$options[$newValue]])) {
                                update_post_meta($order->get_id(), '_payment_method', $payment->id);
                            }
                        }
                    }

                    elseif ($record['field'] == 'payments') {
                        $response = $this->retailcrm->ordersGet($record['order']['externalId']);

                        if ($response->isSuccessful()) { 
                            $order_data = $response['order'];
                            $order = new WC_Order($record['order']['externalId']);
                            $payment = new WC_Payment_Gateways();
                            $payment_types = $payment->get_available_payment_gateways();

                            if (count($order_data['payments']) == 1) {
                                $paymentType = end($order_data['payments']);
                                if (isset($payment_types[$options[$paymentType['type']]])) {
                                    $payment = $payment_types[$options[$paymentType['type']]];
                                    update_post_meta($order->get_id(), '_payment_method', $payment->id);
                                }
                            } else {
                                foreach ($order_data['payments'] as $payment_data) {
                                    if (isset($payment_data['externalId'])) {
                                        $paymentType = $payment_data;
                                    }
                                }

                                if (!isset($paymentType)) {
                                    $paymentType = $order_data['payments'][0];
                                }

                                if (isset($payment_types[$options[$paymentType['type']]])) {
                                    update_post_meta($order->get_id(), '_payment_method', $payment->id);
                                }
                            }
                        }
                    }

                    elseif (isset($record['created']) && 
                        $record['created'] == 1 && 
                        !isset($record['order']['externalId'])) {

                        $args = array(
                            'status' => $options[$record['order']['status']],
                            'customer_id' => isset($record['order']['customer']['externalId']) ? 
                            $record['order']['customer']['externalId'] : 
                            null
                        );

                        $order_record = $record['order'];
                        $order_data = wc_create_order($args);
                        $order = new WC_Order($order_data->id);
                            
                        $address_shipping = array(
                            'first_name' => $order_record['firstName'],
                            'last_name'  => isset($order_record['lastName']) ? $order_record['lastName'] : '',
                            'company'    => '',
                            'email'      => isset($order_record['email']) ? $order_record['email'] : '',
                            'phone'      => isset($order_record['phone']) ? $order_record['phone'] : '',
                            'address_1'  => isset($order_record['delivery']['address']['text']) ? $order_record['delivery']['address']['text'] : '',
                            'address_2'  => '',
                            'city'       => isset($order_record['delivery']['address']['city']) ? $order_record['delivery']['address']['city'] : '',
                            'state'      => isset($order_record['delivery']['address']['region']) ? $order_record['delivery']['address']['region'] : '',
                            'postcode'   => isset($order_record['delivery']['address']['postcode']) ? $order_record['delivery']['address']['postcode'] : '',
                            'country'    => $order_record['delivery']['address']['countryIso']
                        );
                        $address_billing = array(
                            'first_name' => $order_record['customer']['firstName'],
                            'last_name'  => isset($order_record['customer']['lastName']) ? $order_record['customer']['lastName'] : '',
                            'company'    => '',
                            'email'      => isset($order_record['customer']['email']) ? $order_record['customer']['email'] : '',
                            'phone'      => isset($order_record['customer'][0]['number']) ? $order_record['customer'][0]['number'] : '',
                            'address_1'  => isset($order_record['customer']['address']['text']) ? $order_record['customer']['address']['text'] : '',
                            'address_2'  => '',
                            'city'       => isset($order_record['customer']['address']['city']) ? $order_record['customer']['address']['city'] : '',
                            'state'      => isset($order_record['customer']['address']['region']) ? $order_record['customer']['address']['region'] : '',
                            'postcode'   => isset($order_record['customer']['address']['postcode']) ? $order_record['customer']['address']['postcode'] : '',
                            'country'    => $order_record['customer']['address']['countryIso']
                        );
                            
                        if ($this->retailcrm_settings['api_version'] == 'v5') {    
                            if ($order_record['payments']) {
                                $payment = new WC_Payment_Gateways();

                                if (count($order_record['payments']) == 1) {
                                    $payment_types = $payment->get_available_payment_gateways();
                                    $paymentType = end($order_record['payments']);
                                    
                                    if (isset($payment_types[$options[$paymentType['type']]])) {
                                        $order->set_payment_method($payment_types[$options[$paymentType['type']]]);
                                    }
                                }
                            }
                        } else {
                            if ($order_record['paymentType']) {
                                $payment = new WC_Payment_Gateways();
                                $payment_types = $payment->get_available_payment_gateways();
                                if (isset($payment_types[$options[$order_record['paymentType']]])) {
                                    $order->set_payment_method($payment_types[$options[$order_record['paymentType']]]);
                                }
                            }
                        }

                        $order->set_address($address_billing, 'billing');
                        $order->set_address($address_shipping, 'shipping');
                        $product_data = isset($order_record['items']) ? $order_record['items'] : array();

                        if ($product_data) {
                            foreach ($product_data as $product) {
                                $order->add_product(wc_get_product($product['offer']['externalId']), $product['quantity']);
                            }
                        }

                        if (array_key_exists('delivery', $order_record)) {
                            $deliveryCode = isset($order_record['delivery']['code']) ? $order_record['delivery']['code'] : false;

                            if ($deliveryCode && isset($options[$deliveryCode])) {
                                $delivery = explode(':', $options[$deliveryCode]);

                                if (isset($delivery[1])) {
                                    $instance_id = $delivery[1];
                                }
                            }

                            if (isset($instance_id)) {
                                $wc_shipping = WC_Shipping_Zones::get_shipping_method($instance_id);
                                $shipping_method_title = $wc_shipping->method_title;
                                $shipping_method_id = $options[$deliveryCode];
                                $shipping_total = $order_record['delivery']['cost'];
                            } else {
                                $wc_shipping = new WC_Shipping();
                                $wc_shipping_types = $wc_shipping->get_shipping_methods();

                                foreach ($wc_shipping_types as $shipping_type) {
                                    if ($shipping_type->id == $options[$deliveryCode]) {
                                        $shipping_method_id = $shipping_type->id;
                                        $shipping_method_title = $shipping_type->method_title;
                                        $shipping_total = $order_record['delivery']['cost'];
                                    }
                                }
                            }

                            if (version_compare(get_option('woocommerce_db_version'), '3.0', '<' )) {
                                $shipping_rate = new WC_Shipping_Rate($shipping_method_id, isset($shipping_method_title) ? $shipping_method_title : '', isset($shipping_total) ? floatval($shipping_total) : 0, array(), $shipping_method_id);
                                $order->add_shipping($shipping_rate);
                            } else {
                                $shipping = new WC_Order_Item_Shipping();
                                $shipping->set_props( array(
                                    'method_title' => $shipping_method_title,
                                    'method_id'    => $shipping_method_id,
                                    'total'        => wc_format_decimal($shipping_total),
                                    'order_id'     => $order->id
                                ) );
                                $shipping->save();
                                $order->add_item( $shipping );
                            }
                        }

                        $this->update_total($order);

                        $ids[] = array(
                            'id' => (int)$order_record['id'],
                            'externalId' => (int)$order_data->id
                        );

                        $this->retailcrm->ordersFixExternalIds($ids);
                    }
                    $this->addFuncsHook();
                }
            }

            if (empty($response)) {
                return;
            }

            $this->retailcrm_settings['history_orders'] = $generatedAt;
            update_option('woocommerce_integration-retailcrm_settings', $this->retailcrm_settings);

        }

        protected function removeFuncsHook() 
        {      
            if (version_compare(get_option('woocommerce_db_version'), '3.0', '<' )) {
                remove_action('woocommerce_order_status_changed', 'retailcrm_update_order_status', 11, 1);
                remove_action('woocommerce_saved_order_items', 'retailcrm_update_order_items', 10, 2);
                remove_action('update_post_meta', 'retailcrm_update_order', 11, 4);
                remove_action('woocommerce_payment_complete', 'retailcrm_update_order_payment', 11, 1);
                remove_action('woocommerce_checkout_update_user_meta', 'update_customer', 10, 2);
            } else {
                remove_action('woocommerce_update_order', 'update_order', 11, 1);
                remove_action('woocommerce_order_status_changed', 'retailcrm_update_order_status', 11, 1);
            }
        }

        protected function addFuncsHook() 
        {   
            if (version_compare(get_option('woocommerce_db_version'), '3.0', '<' )) {
                if (!has_action('woocommerce_checkout_update_user_meta', 'update_customer')) {
                    add_action('woocommerce_checkout_update_user_meta', 'update_customer', 10, 2);
                }
                if (!has_action('woocommerce_order_status_changed', 'retailcrm_update_order_status')) {
                    add_action('woocommerce_order_status_changed', 'retailcrm_update_order_status', 11, 1);
                }
                if (!has_action('woocommerce_saved_order_items', 'retailcrm_update_order_items')) {
                    add_action('woocommerce_saved_order_items', 'retailcrm_update_order_items', 10, 2);
                }
                if (!has_action('update_post_meta', 'retailcrm_update_order')) {
                    add_action('update_post_meta', 'retailcrm_update_order', 11, 4);
                }
                if (!has_action('woocommerce_payment_complete', 'retailcrm_update_order_payment')) {
                    add_action('woocommerce_payment_complete', 'retailcrm_update_order_payment', 11, 1);
                }
            } else {
                if (!has_action('woocommerce_update_order', 'update_order')) {
                    add_action('woocommerce_update_order', 'update_order', 11, 1);
                }
                if (!has_action('woocommerce_checkout_update_user_meta', 'update_customer')) {
                    add_action('woocommerce_checkout_update_user_meta', 'update_customer', 10, 2);
                }
                if (!has_action('woocommerce_order_status_changed', 'retailcrm_update_order_status')) {
                    add_action('woocommerce_order_status_changed', 'retailcrm_update_order_status', 11, 1);
                }
            }
        }

        protected function getShippingItemId($items)
        {
            if ($items) {
                foreach ($items as $key => $value) {
                    $item_id[] = $key;
                }
            }

            return $item_id[0];
        }


        protected function update_total($order)
        {   
            $order->calculate_totals();
        }
    }
endif;
