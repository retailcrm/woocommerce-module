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
        protected $bind_field = 'externalId';

        /** @var WC_Retailcrm_Order_Item */
        protected $order_item;

        /**
         * WC_Retailcrm_History constructor.
         * @param $retailcrm (default = false)
         */
        public function __construct($retailcrm = false)
        {
            $this->retailcrm_settings = get_option(WC_Retailcrm_Base::$option_key);

            if (isset($this->retailcrm_settings['bind_by_sku'])
                && $this->retailcrm_settings['bind_by_sku'] == WC_Retailcrm_Base::YES
            ) {
                $this->bind_field = 'xmlId';
            }

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

                    if (isset($record['customer']['externalId'])) {
                        $customer = new WC_Customer($record['customer']['externalId']);

                        if ($customer->get_id() == 0) {
                            continue;
                        }
                    }

                    WC_Retailcrm_Plugin::$history_run = true;

                    if ($record['field'] == 'first_name' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']){
                            update_user_meta($record['customer']['externalId'], 'first_name', $record['newValue']);
                        }
                    }

                    elseif ($record['field'] == 'last_name' && isset($record['customer']['externalId'])) {
                        if ($record['newValue']) {
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
         * @return boolean
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
                    return false;
                }

                $history = $response['history'];
                $last_change = end($history);
                $historyAssembly = self::assemblyOrder($response['history']);

                WC_Retailcrm_Plugin::$history_run = true;

                foreach ($historyAssembly as $orderHistory) {
                    $order = apply_filters('retailcrm_history_before_save', $orderHistory);

                    if (isset($order['deleted']) && $order['deleted'] == true) {
                        continue;
                    }

                    try {
                        if (isset($order['externalId'])) {
                            $wc_order_id = $this->orderUpdate($order, $options);
                        } else {
                            $wc_order_id = $this->orderCreate($order, $options);
                        }

                        $wc_order = wc_get_order($wc_order_id);

                        if ($wc_order instanceof WC_Order) {
                            $this->update_total($wc_order);
                        }
                    } catch (Exception $exception) {
                        $logger = new WC_Logger();
                        $logger->add('retailcrm',
                            sprintf("[%s] - %s", $exception->getMessage(),
                                'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine())
                        );

                        continue;
                    }
                }

                update_option('retailcrm_orders_history_since_id', $last_change['id']);
                WC_Retailcrm_Plugin::$history_run = false;
            }

            return true;
        }

        /**
         * Update shipping
         *
         * @param array $order
         * @param array $options
         * @param WC_Order $wc_order
         *
         * @return boolean
         */
        protected function updateShippingItemId($order, $options, $wc_order)
        {
            $create = false;

            $shippings = $wc_order->get_items('shipping');

            if (!$shippings) {
                $shipping = new WC_Order_Item_Shipping();
                $create = true;
            } else {
                $shipping = reset($shippings);
            }

            $data_store = $shipping->get_data_store();

            if (isset($order['delivery']['code'])) {
                if (!isset($options[$order['delivery']['code']])) {
                    return false;
                }

                $shipping_methods = get_wc_shipping_methods();
                $shipping->set_method_title($shipping_methods[$options[$order['delivery']['code']]]['name']);
                $shipping->set_method_id($options[$order['delivery']['code']]);
            }

            if (isset($order['delivery']['cost']) && !wc_tax_enabled()) {
                $shipping->set_total($order['delivery']['cost']);
            }

            if (isset($order['delivery']['netCost']) && wc_tax_enabled()) {
                $shipping->set_total($order['delivery']['netCost']);
            }

            if (isset($order['delivery']['service']['code'])) {
                $service = retailcrm_get_delivery_service($shipping->get_method_id(), $order['delivery']['service']['code']);

                if ($service) {
                    $shipping->set_instance_id($order['delivery']['service']['code']);
                }
            }

            if ($create === true) {
                $data_store->create($shipping);
                $shipping->set_order_id($wc_order->get_id());
            }

            $data_store->update($shipping);

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

        /**
         * Update order in WC
         *
         * @param array $order
         * @param array $options
         *
         * @return bool
         */
        protected function orderUpdate($order, $options)
        {
            $wc_order = wc_get_order($order['externalId']);

            if (!$wc_order instanceof WC_Order) {
                return false;
            }

            if (isset($options[$order['status']])) {
                $wc_order->update_status($options[$order['status']]);
            }

            if (array_key_exists('items', $order)) {
                foreach ($order['items'] as $key => $item) {

                    if (!isset($item['offer'][$this->bind_field])) {
                        continue;
                    }

                    if (isset($item['create']) && $item['create'] == true) {
                        $product = retailcrm_get_wc_product(
                            $item['offer'][$this->bind_field],
                            $this->retailcrm_settings
                        );

                        foreach ($wc_order->get_items() as $order_item_id => $order_item) {
                            $arItemsOld[$order_item_id] = $order_item_id;
                        }

                        $wc_order->add_product($product, $item['quantity']);

                        foreach ($wc_order->get_items() as $order_item_id => $order_item) {
                            $arItemsNew[$order_item_id] = $order_item_id;
                        }

                        $diff = array_diff($arItemsNew, $arItemsOld);
                        $result = end($diff);
                        $order['items'][$key]['woocomerceId'] = $result;
                    } else {
                        foreach ($wc_order->get_items() as $order_item_id => $order_item) {
                            if ($order_item['variation_id'] != 0 ) {
                                $offer_id = $order_item['variation_id'];
                            } else {
                                $offer_id = $order_item['product_id'];
                            }

                            if (isset($item['externalIds'])) {
                                foreach ($item['externalIds'] as $externalId) {
                                    if ($externalId['code'] == 'woocomerce') {
                                        $itemExternalId = explode('_', $externalId['value']);
                                    }
                                }
                            } else {
                                $itemExternalId = explode('_', $item['externalId']);
                            }

                            if ($offer_id == $item['offer'][$this->bind_field]
                                && $itemExternalId[1] == $order_item->get_id()
                            ) {
                                $this->deleteOrUpdateOrderItem($item, $order_item, $itemExternalId[1]);
                            }

                        }
                    }
                }
            }

            if (array_key_exists('delivery', $order)) {
                $this->updateShippingItemId($order, $options, $wc_order);

                if (isset($order['delivery']['address'])) {
                    $shipping_address = $order['delivery']['address'];

                    if (isset($shipping_address['region'])) {
                        $wc_order->set_shipping_state($shipping_address['region']);
                    }

                    if (isset($shipping_address['city'])) {
                        $wc_order->set_shipping_city($shipping_address['city']);
                    }

                    if (isset($shipping_address['street'])) {
                        $wc_order->set_shipping_address_1($shipping_address['street']);
                    }

                    if (isset($shipping_address['building'])) {
                        $wc_order->set_shipping_address_2($shipping_address['building']);
                    }
                }
            }

            if (isset($order['paymentType'])) {
                if (!empty($options[$order['paymentType']])) {
                    $payment = WC_Payment_Gateways::instance();
                    $payment_types = $payment->payment_gateways();

                    if (isset($payment_types[$options[$order['paymentType']]])) {
                        $wc_order->set_payment_method($payment_types[$options[$order['paymentType']]]);
                    }
                }
            }

            if (isset($order['payments']) && !empty($order['payments'])) {
                $payment = WC_Payment_Gateways::instance();
                $payment_types = $payment->payment_gateways();

                if (count($order['payments']) == 1) {
                    $paymentType = reset($order['payments']);

                    if (isset($paymentType['type'])
                        && isset($options[$paymentType['type']])
                        && isset($payment_types[$options[$paymentType['type']]])
                    ) {
                        $payment_type = $payment_types[$options[$paymentType['type']]];
                        $wc_order->set_payment_method($payment_type);
                    }
                } else {
                    foreach ($order['payments'] as $payment_data) {
                        if (isset($payment_data['externalId'])) {
                            $paymentType = $payment_data;
                        }
                    }

                    if (!isset($paymentType)) {
                        $paymentType = $order['payments'][0];
                    }

                    if (isset($payment_types[$options[$paymentType['type']]])) {
                        $wc_order->set_payment_method($payment_types[$options[$paymentType['type']]]);
                    }
                }
            }

            $wc_order->save();

            $checkNewItem = false;
            foreach ($order['items'] as $item) {
                if (!empty($item['externalIds'])) {
                    continue;
                } else {
                    $checkNewItem = true;
                }
            }

            if ($checkNewItem == true) {
                $this->editOrder($this->retailcrm_settings, $wc_order, $order,'update');
            }

            return $wc_order->get_id();
        }

        /**
         * @param $item
         * @param $order_item
         * @param $order_item_id
         */
        private function deleteOrUpdateOrderItem($item, $order_item, $order_item_id)
        {
            if (isset($item['delete']) && $item['delete'] == true) {
                wc_delete_order_item($order_item_id);
            } else {
                if (isset($item['quantity']) && $item['quantity']) {
                    $order_item->set_quantity($item['quantity']);
                    $product = retailcrm_get_wc_product($item['offer'][$this->bind_field], $this->retailcrm_settings);
                    $order_item->set_subtotal($product->get_price());
                    $data_store = $order_item->get_data_store();
                    $data_store->update($order_item);
                }

                if (isset($item['summ']) && $item['summ']) {
                    $order_item->set_total($item['summ']);
                    $data_store = $order_item->get_data_store();
                    $data_store->update($order_item);
                }
            }
        }

        /**
         * Create order in WC
         *
         * @param array $order
         * @param array $options
         *
         * @return bool
         */
        protected function orderCreate($order, $options)
        {
            if (!isset($order['create'])) {
                return false;
            }

            if (is_array($this->order_methods)
                && $this->order_methods
                && isset($order['orderMethod'])
                && !in_array($order['orderMethod'], $this->order_methods)
            ) {
                return false;
            }

            $args = array(
                'status' => isset($options[$order['status']])
                    ? isset($options[$order['status']])
                    : 'processing',
                'customer_id' => isset($order['customer']['externalId'])
                    ? $order['customer']['externalId']
                    : null
            );

            $wc_order = wc_create_order($args);

            $address_shipping = array(
                'first_name' => isset($order['firstName']) ? $order['firstName'] : '',
                'last_name'  => isset($order['lastName']) ? $order['lastName'] : '',
                'company'    => '',
                'address_1'  => isset($order['delivery']['address']['text']) ? $order['delivery']['address']['text'] : '',
                'address_2'  => '',
                'city'       => isset($order['delivery']['address']['city']) ? $order['delivery']['address']['city'] : '',
                'state'      => isset($order['delivery']['address']['region']) ? $order['delivery']['address']['region'] : '',
                'postcode'   => isset($order['delivery']['address']['index']) ? $order['delivery']['address']['index'] : '',
                'country'    => isset($order['delivery']['address']['countryIso']) ? $order['delivery']['address']['countryIso'] : ''
            );

            $address_billing = array(
                'first_name' => $order['customer']['firstName'],
                'last_name'  => isset($order['customer']['lastName']) ? $order['customer']['lastName'] : '',
                'company'    => '',
                'email'      => isset($order['customer']['email']) ? $order['customer']['email'] : '',
                'phone'      => isset($order['customer']['phones'][0]['number']) ? $order['customer']['phones'][0]['number'] : '',
                'address_1'  => isset($order['customer']['address']['text']) ? $order['customer']['address']['text'] : '',
                'address_2'  => '',
                'city'       => isset($order['customer']['address']['city']) ? $order['customer']['address']['city'] : '',
                'state'      => isset($order['customer']['address']['region']) ? $order['customer']['address']['region'] : '',
                'postcode'   => isset($order['customer']['address']['index']) ? $order['customer']['address']['index'] : '',
                'country'    => $order['customer']['address']['countryIso']
            );

            if ($this->retailcrm_settings['api_version'] == 'v5') {
                if (isset($order['payments']) && $order['payments']) {
                    $payment = WC_Payment_Gateways::instance();

                    if (count($order['payments']) == 1) {
                        $payment_types = $payment->payment_gateways();
                        $payments = $order['payments'];
                        $paymentType = end($payments);
                        if (isset($options[$paymentType['type']]) && isset($payment_types[$options[$paymentType['type']]])) {
                            $wc_order->set_payment_method($payment_types[$options[$paymentType['type']]]);
                        }
                    }
                }
            } else {
                if (isset($order['paymentType']) && $order['paymentType']) {
                    $payment = WC_Payment_Gateways::instance();
                    $payment_types = $payment->payment_gateways();

                    if (isset($options[$order['paymentType']]) && isset($payment_types[$options[$order['paymentType']]])) {
                        $wc_order->set_payment_method($payment_types[$options[$order['paymentType']]]);
                    }
                }
            }

            $wc_order->set_address($address_billing, 'billing');
            $wc_order->set_address($address_shipping, 'shipping');
            $product_data = isset($order['items']) ? $order['items'] : array();

            if ($product_data) {
                foreach ($product_data as $key => $product) {
                    $item = retailcrm_get_wc_product($product['offer'][$this->bind_field], $this->retailcrm_settings);
                    if ($product['discountTotal'] > 0) {
                        $item->set_price($product['initialPrice'] - $product['discountTotal']);
                    }

                    foreach ($wc_order->get_items() as $order_item_id => $order_item) {
                        $arItemsOld[$order_item_id] = $order_item_id;
                    }

                    $wc_order->add_product(
                        $item,
                        $product['quantity']
                    );

                    foreach ($wc_order->get_items() as $order_item_id => $order_item) {
                        $arItemsNew[$order_item_id] = $order_item_id;
                    }

                    if (!empty($arItemsOld)) {
                        $result = end(array_diff($arItemsNew, $arItemsOld));
                    } else {
                        $result = end($arItemsNew);
                    }

                    $order['items'][$key]['woocomerceId'] = $result;

                }
            }

            if (array_key_exists('delivery', $order)) {
                $deliveryCode = isset($order['delivery']['code']) ? $order['delivery']['code'] : false;

                if ($deliveryCode && isset($options[$deliveryCode])) {
                    $shipping = new WC_Order_Item_Shipping();
                    $shipping_methods = get_wc_shipping_methods();
                    $shipping->set_method_title($shipping_methods[$options[$deliveryCode]]['name']);
                    $shipping->set_method_id($options[$deliveryCode]);

                    if (isset($order['delivery']['service']['code'])) {
                        $service = retailcrm_get_delivery_service(
                            $shipping->get_method_id(),
                            $order['delivery']['service']['code']
                        );

                        if ($service) {
                            $shipping->set_instance_id($order['delivery']['service']['code']);
                        }
                    }

                    if (!wc_tax_enabled()) {
                        $shipping->set_total($order['delivery']['cost']);
                    } else {
                        $shipping->set_total($order['delivery']['netCost']);
                    }

                    $shipping->set_order_id($wc_order->get_id());

                    $shipping->save();
                    $wc_order->add_item($shipping);
                }
            }

            $ids[] = array(
                'id' => (int) $order['id'],
                'externalId' => (int) $wc_order->get_id()
            );

            $wc_order->save();

            $this->retailcrm->ordersFixExternalIds($ids);

            $this->editOrder($this->retailcrm_settings, $wc_order, $order);

            return $wc_order->get_id();
        }

        /**
         * @param        $settings
         * @param        $wc_order
         * @param        $order
         * @param string $event
         */
        protected function editOrder($settings, $wc_order, $order, $event = 'create')
        {
            $order_items = array();
            if ($event == 'update') {
                $result = $this->retailcrm->ordersGet($order['externalId']);
                if ($result->isSuccessful()) {
                    $orderCrm = $result['order'];
                }
                $data = $orderCrm;
            }

            if ($event == 'create') {
                $data = $order;
            }

            foreach ($data['items'] as $id => $item) {
                $order_items[$id]['id'] = $item['id'];
                $order_items[$id]['offer'] = array('id' => $item['offer']['id']);
                $externalIds = array(
                    array(
                        'code' => 'woocomerce',
                        'value' => $item['offer']['externalId'] . '_' . $order['items'][$item['id']]['woocomerceId'],
                    )
                );

                if ($item['externalIds']) {
                    $order_items[$id]['externalIds'] = $item['externalIds'];
                    $order_items[$id]['externalIds'][] = $externalIds;
                } else {
                    $order_items[$id]['externalIds'] = $externalIds;
                }
            }

            if (!empty($order_items)) {
                $orderEdit = array(
                    'id' => $order['id'],
                    'items' => $order_items,
                );

                $this->retailcrm->ordersEdit($orderEdit, 'id');
            }
        }

        /**
         * @param array $orderHistory
         *
         * @return array
         */
        public static function assemblyOrder($orderHistory)
        {
            if (file_exists(__DIR__ . '/../config/objects.xml')) {
                $objects = simplexml_load_file(__DIR__ . '/../config/objects.xml');
                foreach($objects->fields->field as $object) {
                    $fields[(string)$object["group"]][(string)$object["id"]] = (string)$object;
                }
            }

            $orders = array();

            foreach ($orderHistory as $change) {
                if ($change['source'] == 'api'
                    && isset($change['apiKey']['current'])
                    && $change['apiKey']['current'] == true
                ) {
                    continue;
                }

                $change['order'] = self::removeEmpty($change['order']);
                if(isset($change['order']['items']) && $change['order']['items']) {
                    $items = array();
                    foreach($change['order']['items'] as $item) {
                        if(isset($change['created'])) {
                            $item['create'] = 1;
                        }
                        $items[$item['id']] = $item;
                    }
                    $change['order']['items'] = $items;
                }

                if(isset($change['order']['contragent']['contragentType']) && $change['order']['contragent']['contragentType']) {
                    $change['order']['contragentType'] = $change['order']['contragent']['contragentType'];
                    unset($change['order']['contragent']);
                }

                if (!empty($orders) && isset($orders[$change['order']['id']])) {
                    $orders[$change['order']['id']] = array_merge($orders[$change['order']['id']], $change['order']);
                } else {
                    $orders[$change['order']['id']] = $change['order'];
                }

                if (isset($change['item']) && $change['item']) {
                    if(isset($orders[$change['order']['id']]['items'][$change['item']['id']])) {
                        $orders[$change['order']['id']]['items'][$change['item']['id']] = array_merge($orders[$change['order']['id']]['items'][$change['item']['id']], $change['item']);
                    } else {
                        $orders[$change['order']['id']]['items'][$change['item']['id']] = $change['item'];
                    }

                    if ($change['oldValue'] === null
                        && $change['field'] == 'order_product'
                    ) {
                        $orders[$change['order']['id']]['items'][$change['item']['id']]['create'] = true;
                    }

                    if ($change['newValue'] === null
                        && $change['field'] == 'order_product'
                    ) {
                        $orders[$change['order']['id']]['items'][$change['item']['id']]['delete'] = true;
                    }

                    if (!isset($orders[$change['order']['id']]['items'][$change['item']['id']]['create'])
                        && isset($fields['item'][$change['field']])
                        && $fields['item'][$change['field']]
                    ) {
                        $orders[$change['order']['id']]['items'][$change['item']['id']][$fields['item'][$change['field']]] = $change['newValue'];
                    }
                } elseif ($change['field'] == 'payments' && isset($change['payment'])) {
                    if ($change['newValue'] !== null) {
                        $orders[$change['order']['id']]['payments'][] = self::newValue($change['payment']);
                    }
                }  else {
                    if (isset($fields['delivery'][$change['field']]) && $fields['delivery'][$change['field']] == 'service') {
                        $orders[$change['order']['id']]['delivery']['service']['code'] = self::newValue($change['newValue']);
                    } elseif (isset($fields['delivery'][$change['field']]) && $fields['delivery'][$change['field']]) {
                        $orders[$change['order']['id']]['delivery'][$fields['delivery'][$change['field']]] = self::newValue($change['newValue']);
                    } elseif (isset($fields['orderAddress'][$change['field']]) && $fields['orderAddress'][$change['field']]) {
                        $orders[$change['order']['id']]['delivery']['address'][$fields['orderAddress'][$change['field']]] = $change['newValue'];
                    } elseif (isset($fields['integrationDelivery'][$change['field']]) && $fields['integrationDelivery'][$change['field']]) {
                        $orders[$change['order']['id']]['delivery']['service'][$fields['integrationDelivery'][$change['field']]] = self::newValue($change['newValue']);
                    } elseif (isset($fields['customerContragent'][$change['field']]) && $fields['customerContragent'][$change['field']]) {
                        $orders[$change['order']['id']][$fields['customerContragent'][$change['field']]] = self::newValue($change['newValue']);
                    } elseif (strripos($change['field'], 'custom_') !== false) {
                        $orders[$change['order']['id']]['customFields'][str_replace('custom_', '', $change['field'])] = self::newValue($change['newValue']);
                    } elseif (isset($fields['order'][$change['field']]) && $fields['order'][$change['field']]) {
                        $orders[$change['order']['id']][$fields['order'][$change['field']]] = self::newValue($change['newValue']);
                    }

                    if (isset($change['created'])) {
                        $orders[$change['order']['id']]['create'] = 1;
                    }

                    if (isset($change['deleted'])) {
                        $orders[$change['order']['id']]['deleted'] = 1;
                    }
                }
            }

            return $orders;
        }

        public static function assemblyCustomer($customerHistory)
        {
            $customers = array();
            foreach ($customerHistory as $change) {
                $change['order'] = self::removeEmpty($change['customer']);

                if (!empty($customers[$change['customer']['id']]) && $customers[$change['customer']['id']]) {
                    $customers[$change['customer']['id']] = array_merge($customers[$change['customer']['id']], $change['customer']);
                } else {
                    $customers[$change['customer']['id']] = $change['customer'];
                }
            }

            return $customers;
        }

        public static function newValue($value)
        {
            if (isset($value['code'])) {
                return $value['code'];
            } else {
                return $value;
            }
        }

        public static function removeEmpty($inputArray)
        {
            $outputArray = array();
            if (!empty($inputArray)) {
                foreach ($inputArray as $key => $element) {
                    if(!empty($element) || $element === 0 || $element === '0'){
                        if (is_array($element)) {
                            $element = self::removeEmpty($element);
                        }
                        $outputArray[$key] = $element;
                    }
                }
            }

            return $outputArray;
        }
    }
endif;
