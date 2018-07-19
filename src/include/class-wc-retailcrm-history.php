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
        protected $retailcrm_settings;
        protected $retailcrm;
        protected $order_methods = array();

        /**
         * WC_Retailcrm_History constructor.
         * @param $retailcrm (default = false)
         */
        public function __construct($retailcrm = false)
        {
            $this->retailcrm_settings = get_option(WC_Retailcrm_Base::$option_key);

            if (isset($this->retailcrm_settings['order_methods'])) {
                $this->order_methods = $this->retailcrm_settings['order_methods'];
                unset($this->retailcrm_settings['order_methods']);
            }

            $this->retailcrm = $retailcrm;

            $this->startDate = new DateTime(date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s')))));
            $this->startDateOrders = $this->startDate;
            $this->startDateCustomers = $this->startDate;
        }

        /**
         * Get history method
         * 
         * @return void
         */
        public function getHistory()
        {
            $orders_since_id = get_option('retailcrm_orders_history_since_id');
            $customers_since_id = get_option('retailcrm_customers_history_since_id');

            if (!$orders_since_id && isset($this->retailcrm_settings['history_orders'])) {
                $this->startDateOrders = new DateTime($this->retailcrm_settings['history_orders']);
            }

            if (!$customers_since_id && isset($this->retailcrm_settings['history_customers'])) {
                $this->startDateCustomers = new DateTime($this->retailcrm_settings['history_orders']);
            }

            $this->customersHistory($this->startDateCustomers->format('Y-m-d H:i:s'), $customers_since_id);
            $this->ordersHistory($this->startDateOrders->format('Y-m-d H:i:s'), $orders_since_id);
        }

        /**
         * History customers
         * 
         * @param string $date
         * @param int $since_id
         * 
         * @return null
         */
        protected function customersHistory($date, $since_id)
        {
            if ($since_id) {
                $response = $this->retailcrm->customersHistory(array('sinceId' => $since_id)); 
            } else {
                $response = $this->retailcrm->customersHistory(array('startDate' => $date)); 
            }

            if ($response->isSuccessful()) {
                if (empty($response['history'])) {
                    return;
                }

                $history = $response['history'];
                $end_change = end($history);
                $new_since_id = $end_change['id'];

                foreach ($history as $record) {
                    if ($record['source'] == 'api' && $record['apiKey']['current'] == true) {
                        continue;
                    }

                    WC_Retailcrm_Plugin::$history_run = true;

                    if ($record['field'] == 'first_name' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'first_name', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'last_name' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'last_name', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'email' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_email', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'phones' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_phone', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.region' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_state', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.index' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_postcode', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.country' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_country', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'address.city' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'billing_city', $record['newValue']);
                        }
                    }

                    WC_Retailcrm_Plugin::$history_run = false;
                }
            }

            if (empty($response)) {
                return;
            }

            update_option('retailcrm_customers_history_since_id', $new_since_id);
        }

        /**
         * History orders
         * 
         * @param string $date
         * @param int $since_id
         * 
         * @return null
         */
        protected function ordersHistory($date, $since_id)
        {
            $options = array_flip(array_filter($this->retailcrm_settings));

            if ($since_id) {
                $response = $this->retailcrm->ordersHistory(array('sinceId' => $since_id));
            } else {
                $response = $this->retailcrm->ordersHistory(array('startDate' => $date));
            }

            if ($response->isSuccessful()) {
                if (empty($response['history'])) {
                    return;
                }

                $history = $response['history'];
                $end_change = end($history);
                $new_since_id = $end_change['id'];

                foreach ($history as $record) {
                    if ($record['source'] == 'api' && $record['apiKey']['current'] == true) {
                        continue;
                    }

                    WC_Retailcrm_Plugin::$history_run = true;

                    try {
                        $this->orderEdit($record, $options);
                    } catch (Exception $exception) {
                        $logger = new WC_Logger();
                        $logger->add('retailcrm',
                            sprintf("[%s] - %s", $exception->getMessage(),
                            'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine())
                        );

                        continue;
                    }

                    WC_Retailcrm_Plugin::$history_run = false;
                }
            }

            if (empty($response)) {
                return;
            }

            update_option('retailcrm_orders_history_since_id', $new_since_id);
        }

        /**
         * Edit order in WC
         * 
         * @param array $record
         * @param array $options
         * @return mixed
         */
        protected function orderEdit($record, $options)
        {
            if ($record['field'] == 'status' && !empty($record['newValue']) && !empty($record['oldValue'])) {
                $newStatus = $record['newValue']['code'];
                if (!empty($options[$newStatus]) && !empty($record['order']['externalId'])) {
                    $order = wc_get_order($record['order']['externalId']);
                    $order->update_status($options[$newStatus]);
                }
            }

            elseif ($record['field'] == 'order_product' && $record['newValue']) {
                $product = wc_get_product($record['item']['offer']['externalId']);
                $order = wc_get_order($record['order']['externalId']);
                $order->add_product($product, $record['item']['quantity']);
            }

            elseif ($record['field'] == 'order_product.quantity' && $record['newValue']) {
                $order = wc_get_order($record['order']['externalId']);
                $items = $order->get_items();

                foreach ($items as $order_item_id => $item) {
                    if ($item['variation_id'] != 0 ) {
                        $offer_id = $item['variation_id'];
                    } else {
                        $offer_id = $item['product_id'];
                    }
                    if ($offer_id == $record['item']['offer']['externalId']) {
                        wc_delete_order_item($order_item_id);
                        $product = wc_get_product($offer_id);
                        $order->add_product($product, $record['newValue']);
                    }
                }
            }

            elseif ($record['field'] == 'order_product' && !$record['newValue']) {
                $order = wc_get_order($record['order']['externalId']);
                $items = $order->get_items();

                foreach ($items as $order_item_id => $item) {
                    if ($item['variation_id'] != 0 ) {
                        $offer_id = $item['variation_id'];
                    } else {
                        $offer_id = $item['product_id'];
                    }
                    if ($offer_id == $record['item']['offer']['externalId']) {
                        wc_delete_order_item($order_item_id);
                    }   
                }
            }

            elseif ($record['field'] == 'delivery_type'
                || $record['field'] == 'delivery_cost'
                || $record['field'] == 'delivery_net_cost'
                || $record['field'] == 'delivery_service'
            ) {
                $newValue = isset($record['newValue']['code']) ? $record['newValue']['code'] : $record['newValue'];
                $this->updateShippingItemId($record['field'], $newValue, $record['order']['externalId'], $options);
            }

            elseif ($record['field'] == 'delivery_address.region') {
                $order = wc_get_order($record['order']['externalId']);
                $order->set_shipping_state($record['newValue']);
            }

            elseif ($record['field'] == 'delivery_address.city') {
                $order = wc_get_order($record['order']['externalId']);
                $order->set_shipping_city($record['newValue']);
            }

            elseif ($record['field'] == 'delivery_address.street') {
                $order = wc_get_order($record['order']['externalId']);
                $order->set_shipping_address_1($record['newValue']);
            }

            elseif ($record['field'] == 'delivery_address.building') {
                $order = wc_get_order($record['order']['externalId']);
                $order->set_shipping_address_2($record['newValue']);
            }

            elseif ($record['field'] == 'payment_type') {
                $order = wc_get_order($record['order']['externalId']);
                $newValue = $record['newValue']['code'];
                if (!empty($options[$newValue]) && !empty($record['order']['externalId'])) {
                    $payment = WC_Payment_Gateways::instance();
                    $payment_types = $payment->payment_gateways();

                    if (isset($payment_types[$options[$newValue]])) {
                        $order->set_payment_method($payment_types[$options[$newValue]]);
                    }
                }
            }

            elseif ($record['field'] == 'payments') {
                $response = $this->retailcrm->ordersGet($record['order']['externalId']);

                if ($response->isSuccessful()) { 
                    $order_data = $response['order'];
                    $order = wc_get_order($record['order']['externalId']);
                    $payment = WC_Payment_Gateways::instance();
                    $payment_types = $payment->payment_gateways();

                    if (count($order_data['payments']) == 1) {
                        $paymentType = end($order_data['payments']);
                        if (isset($payment_types[$options[$paymentType['type']]])) {
                            $payment_type = $payment_types[$options[$paymentType['type']]];
                            $order->set_payment_method($payment_type);
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
                            $order->set_payment_method($payment_types[$options[$paymentType['type']]]);
                        }
                    }
                }
            }

            elseif (isset($record['created']) 
                && $record['created'] == 1 
                && !isset($record['order']['externalId'])
            ) {
                if (is_array($this->order_methods)
                    && $this->order_methods
                    && isset($record['order']['orderMethod'])
                    && !in_array($record['order']['orderMethod'], $this->order_methods)
                ) {
                    return false;
                }

                $args = array(
                    'status' => isset($options[$record['order']['status']]) ?
                        isset($options[$record['order']['status']]) :
                        'processing',
                    'customer_id' => isset($record['order']['customer']['externalId']) ? 
                        $record['order']['customer']['externalId'] : 
                        null
                );

                $order_record = $record['order'];
                $order = wc_create_order($args);

                $address_shipping = array(
                    'first_name' => $order_record['firstName'],
                    'last_name'  => isset($order_record['lastName']) ? $order_record['lastName'] : '',
                    'company'    => '',
                    'address_1'  => isset($order_record['delivery']['address']['text']) ? $order_record['delivery']['address']['text'] : '',
                    'address_2'  => '',
                    'city'       => isset($order_record['delivery']['address']['city']) ? $order_record['delivery']['address']['city'] : '',
                    'state'      => isset($order_record['delivery']['address']['region']) ? $order_record['delivery']['address']['region'] : '',
                    'postcode'   => isset($order_record['delivery']['address']['index']) ? $order_record['delivery']['address']['index'] : '',
                    'country'    => $order_record['delivery']['address']['countryIso']
                );

                $address_billing = array(
                    'first_name' => $order_record['customer']['firstName'],
                    'last_name'  => isset($order_record['customer']['lastName']) ? $order_record['customer']['lastName'] : '',
                    'company'    => '',
                    'email'      => isset($order_record['customer']['email']) ? $order_record['customer']['email'] : '',
                    'phone'      => isset($order_record['customer']['phones'][0]['number']) ? $order_record['customer']['phones'][0]['number'] : '',
                    'address_1'  => isset($order_record['customer']['address']['text']) ? $order_record['customer']['address']['text'] : '',
                    'address_2'  => '',
                    'city'       => isset($order_record['customer']['address']['city']) ? $order_record['customer']['address']['city'] : '',
                    'state'      => isset($order_record['customer']['address']['region']) ? $order_record['customer']['address']['region'] : '',
                    'postcode'   => isset($order_record['customer']['address']['index']) ? $order_record['customer']['address']['index'] : '',
                    'country'    => $order_record['customer']['address']['countryIso']
                );

                if ($this->retailcrm_settings['api_version'] == 'v5') {
                    if (isset($order_record['payments']) && $order_record['payments']) {
                        $payment = WC_Payment_Gateways::instance();

                        if (count($order_record['payments']) == 1) {
                            $payment_types = $payment->payment_gateways();
                            $payments = $order_record['payments'];
                            $paymentType = end($payments);

                            if (isset($options[$paymentType['type']]) && isset($payment_types[$options[$paymentType['type']]])) {
                                $order->set_payment_method($payment_types[$options[$paymentType['type']]]);
                            }
                        }
                    }
                } else {
                    if (isset($order_record['paymentType']) && $order_record['paymentType']) {
                        $payment = WC_Payment_Gateways::instance();
                        $payment_types = $payment->payment_gateways();

                        if (isset($options[$order_record['paymentType']]) && isset($payment_types[$options[$order_record['paymentType']]])) {
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
                        $shipping = new WC_Order_Item_Shipping();
                        $shipping_methods = get_wc_shipping_methods();
                        $shipping->set_method_title($shipping_methods[$options[$deliveryCode]]['name']);
                        $shipping->set_method_id($options[$deliveryCode]);

                        if (isset($order_record['delivery']['service']['code'])) {
                            $service = retailcrm_get_delivery_service(
                                $shipping->get_method_id(),
                                $order_record['delivery']['service']['code']
                            );

                            if ($service) {
                                $shipping->set_instance_id($order_record['delivery']['service']['code']);
                            }
                        }

                        if (!wc_tax_enabled()) {
                            $shipping->set_total($order_record['delivery']['cost']);
                        } else {
                            $shipping->set_total($order_record['delivery']['netCost']);
                        }

                        $shipping->set_order_id($order->get_id());

                        $shipping->save();
                        $order->add_item($shipping);
                    }
                }

                $ids[] = array(
                    'id' => (int)$order_record['id'],
                    'externalId' => (int)$order->get_id()
                );

                $this->retailcrm->ordersFixExternalIds($ids);
            }

            if (isset($record['order']['externalId']) && !empty($record['order']['externalId'])) {
                $newOrder = wc_get_order($record['order']['externalId']);
                $this->update_total($newOrder);
            }
        }

        /**
         * Update shipping
         *
         * @param string $field
         * @param string $new_value
         * @param string $order_id
         * @param array $options
         *
         * @return mixed
         */
        protected function updateShippingItemId($field, $new_value, $order_id, $options)
        {
            $order = wc_get_order($order_id);
            $shippings = $order->get_items('shipping');
            $shipping = reset($shippings);

            if ($field == 'delivery_type') {
                if (!isset($options[$new_value])) {
                    return false;
                }

                $shipping_methods = get_wc_shipping_methods();
                $shipping->set_method_title($shipping_methods[$options[$new_value]]['name']);
                $shipping->set_method_id($options[$new_value]);
            }

            if ($field == 'delivery_cost' && !wc_tax_enabled()) {
                $shipping->set_total($new_value);
            }

            if ($field == 'delivery_net_cost' && wc_tax_enabled()) {
                $shipping->set_total($new_value);
            }

            if ($field == 'delivery_service') {
                $service = retailcrm_get_delivery_service($shipping->get_method_id(), $new_value);

                if ($service) {
                    $shipping->set_instance_id($new_value);
                }
            }

            $data_store = WC_Data_Store::load('order-item-shipping');
            $data_store->update($shipping);
            $updateOrder = wc_get_order((int)$order->get_id());
            $this->update_total($updateOrder);

            return true;
        }

        /**
         * Calculate totals in order
         *
         * @param WC_Order $order
         *
         * @return void
         */
        protected function update_total($order)
        {   
            $order->calculate_totals();
        }
    }
endif;
