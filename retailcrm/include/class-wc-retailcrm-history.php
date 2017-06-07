<?php
/**
 * Retailcrm Integration.
 *
 * @package  WC_Retailcrm_History
 * @category Integration
 * @author   Retailcrm
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
                include_once( __DIR__ . '/api/class-wc-retailcrm-proxy.php' );
            }

            $this->retailcrm = new WC_Retailcrm_Proxy(
                $this->retailcrm_settings['api_url'],
                $this->retailcrm_settings['api_key']
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
                        $args = array('qty' => $record['newValue']);

                        foreach ($items as $order_item_id => $item) {
                            if ($item['variation_id'] != 0 ) {
                                $offer_id = $item['variation_id'];
                            } else {
                                $offer_id = $item['product_id'];
                            } 
                            if ($offer_id == $record['item']['offer']['externalId']) {
                                $order->update_product($order_item_id, $product, $args);

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
                            $order = new WC_Order($record['order']['externalId']);
                            $items = $order->get_items('shipping');
                            $wc_shipping = new WC_Shipping();
                            $wc_shipping_list = $wc_shipping->get_shipping_methods();

                            foreach ($wc_shipping_list as $method) {
                                if ($method->id == $options[$newValue]) {
                                    $deliveryCost = $method->cost;
                                }
                            }

                            $item_id = $this->getShippingItemId($items);
                            $args = array(
                                'method_id' => $options[$newValue],
                                'cost' => $deliveryCost ? $deliveryCost : 0
                            );
                            $order->update_shipping( $item_id, $args );

                            $this->update_total($order);
                        }
                    }

                    elseif ($record['field'] == 'delivery_address.region') {
                        $order = new WC_Order($record['order']['externalId']);
                        $address = array(
                            'state' => $record['newValue']
                        );
                        $order->set_address($address, 'shipping');
                    }

                    elseif ($record['field'] == 'delivery_address.city') {
                        $order = new WC_Order($record['order']['externalId']);
                        $address = array(
                            'city' => $record['newValue']
                        );
                        $order->set_address($address, 'shipping');
                    }

                    elseif ($record['field'] == 'delivery_address.street') {
                        $order = new WC_Order($record['order']['externalId']);
                        $address = array(
                            'address_1' => $record['newValue']
                        );
                        $order->set_address($address, 'shipping');
                    }

                    elseif ($record['field'] == 'delivery_address.building') {
                        $order = new WC_Order($record['order']['externalId']);
                        $address = array(
                            'address_2' => $record['newValue']
                        );
                        $order->set_address($address, 'shipping');
                    }
                        
                    elseif ($record['field'] == 'payment_type') {
                        $order = new WC_Order($record['order']['externalId']);
                        $newValue = $record['newValue']['code'];
                        if (!empty($options[$newValue]) && !empty($record['order']['externalId'])) {
                            $payment = new WC_Payment_Gateways();
                            $payment_types = $payment->get_available_payment_gateways();
                            if (isset($payment_types[$options[$newValue]])) {
                                $order->set_payment_method($payment_types[$options[$newValue]]);
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
                            'last_name'  => $order_record['lastName'],
                            'company'    => '',
                            'email'      => $order_record['email'],
                            'phone'      => $order_record['phone'],
                            'address_1'  => $order_record['delivery']['address']['text'],
                            'address_2'  => '',
                            'city'       => $order_record['delivery']['address']['city'],
                            'state'      => $order_record['delivery']['address']['region'],
                            'postcode'   => isset($order_record['delivery']['address']['postcode']) ? $order_record['delivery']['address']['postcode'] : '',
                            'country'    => $order_record['delivery']['address']['countryIso']
                        );
                        $address_billing = array(
                            'first_name' => $order_record['customer']['firstName'],
                            'last_name'  => $order_record['customer']['lastName'],
                            'company'    => '',
                            'email'      => $order_record['customer']['email'],
                            'phone'      => $order_record['customer'][0]['number'],
                            'address_1'  => $order_record['customer']['address']['text'],
                            'address_2'  => '',
                            'city'       => $order_record['customer']['address']['city'],
                            'state'      => $order_record['customer']['address']['region'],
                            'postcode'   => isset($order_record['customer']['address']['postcode']) ? $order_record['customer']['address']['postcode'] : '',
                            'country'    => $order_record['customer']['address']['countryIso']
                        );
                            
                        if ($order_record['paymentType']) {
                            $payment = new WC_Payment_Gateways();
                            $payment_types = $payment->get_available_payment_gateways();
                            if (isset($payment_types[$options[$order_record['paymentType']]])) {
                                $order->set_payment_method($payment_types[$options[$order_record['paymentType']]]);
                            }
                        }

                        $order->set_address($address_billing, 'billing');
                        $order->set_address($address_shipping, 'shipping');
                        $product_data = $order_record['items'];

                        foreach ($product_data as $product) {
                            $order->add_product(wc_get_product($product['offer']['externalId']), $product['quantity']);
                        }

                        $wc_shipping = new WC_Shipping();
                        $wc_shipping_types = $wc_shipping->get_shipping_methods();
                            
                        foreach ($wc_shipping_types as $shipping_type) {
                            if ($shipping_type->id == $options[$order_record['delivery']['code']]) {
                                $shipping_method_id = $shipping_type->id;
                                $shipping_method_title = $shipping_type->title;
                                $shipping_total = $shipping_type->cost;
                            }
                        }

                        $shipping_rate = new WC_Shipping_Rate($shipping_method_id, isset($shipping_method_title) ? $shipping_method_title : '', isset($shipping_total) ? floatval($shipping_total) : 0, array(), $shipping_method_id);
                        $order->add_shipping($shipping_rate);
                            
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
            remove_action('woocommerce_order_status_changed', 'retailcrm_update_order_status', 11, 1);
            remove_action('woocommerce_saved_order_items', 'retailcrm_update_order_items', 10, 2);
            remove_action('update_post_meta', 'retailcrm_update_order', 11, 4);
            remove_action('woocommerce_payment_complete', 'retailcrm_update_order_payment', 11, 1);
            remove_action('woocommerce_checkout_update_user_meta', 'update_customer', 10, 2);
        }

        protected function addFuncsHook() 
        {       
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
            $order->update_taxes();
            $order->calculate_totals();
        }
    }
endif;
