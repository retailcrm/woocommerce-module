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
        /** @var \DateTime */
        protected $startDateOrders;

        /** @var \DateTime */
        protected $startDateCustomers;

        /** @var \DateTime */
        protected $startDate;

        /** @var array|mixed|void */
        protected $retailcrm_settings;

        /** @var bool|\WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V4|\WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array|mixed */
        protected $order_methods = array();

        /** @var string */
        protected $bind_field = 'externalId';

        /** @var WC_Retailcrm_Order_Item */
        protected $order_item;

        /**
         * WC_Retailcrm_History constructor.
         *
         * @param \WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V4|\WC_Retailcrm_Client_V5|bool $retailcrm (default = false)
         *
         * @throws \Exception
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
         * Get history method.
         *
         * @return void
         * @throws \Exception
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

            try {
                $this->customersHistory($this->startDateCustomers->format('Y-m-d H:i:s'), $customers_since_id);
                $this->ordersHistory($this->startDateOrders->format('Y-m-d H:i:s'), $orders_since_id);
            } catch (\Exception $exception) {
                WC_Retailcrm_Logger::add(
                    sprintf("[%s] - %s", $exception->getMessage(),
                        'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine())
                );
            }
        }

        /**
         * History customers
         *
         * @param string $date
         * @param int    $sinceId
         *
         * @return void
         * @throws \Exception
         */
        protected function customersHistory($date, $sinceId)
        {
            $filter = array('startDate' => $date);

            if ($sinceId) {
                $filter = array('sinceId' => $sinceId);
            }

            $request = new WC_Retailcrm_Paginated_Request();
            $history = $request
                ->setApi($this->retailcrm)
                ->setMethod('customersHistory')
                ->setParams(array($filter, '{{page}}'))
                ->setDataKey('history')
                ->setLimit(100)
                ->execute()
                ->getData();

            if (!empty($history)) {
                $builder = new WC_Retailcrm_WC_Customer_Builder();
                $lastChange = end($history);
                $customers = WC_Retailcrm_History_Assembler::assemblyCustomer($history);
                WC_Retailcrm_Plugin::$history_run = true;
                WC_Retailcrm_Logger::debug(__METHOD__, array('Assembled customers history:', $customers));

                foreach ($customers as $crmCustomer) {
                    if (!isset($crmCustomer['externalId'])) {
                        continue;
                    }

                    try {
                        $builder->reset();

                        if (!$builder->loadExternalId($crmCustomer['externalId'])) {
                            WC_Retailcrm_Logger::addCaller(__METHOD__, sprintf(
                                'Customer with id=%s is not found in the DB, skipping...',
                                $crmCustomer['externalId']
                            ));
                            continue;
                        }

                        $wcCustomer = $builder
                            ->setData($crmCustomer)
                            ->build()
                            ->getResult();

                        if ($wcCustomer instanceof WC_Customer) {
                            $wcCustomer->save();
                        }

                        WC_Retailcrm_Logger::debug(__METHOD__, array('Updated WC_Customer:', $wcCustomer));
                    } catch (\Exception $exception) {
                        WC_Retailcrm_Logger::error(sprintf(
                            'Error while trying to process history: %s',
                            $exception->getMessage()
                        ));
                        WC_Retailcrm_Logger::error(sprintf(
                            '%s:%d',
                            $exception->getFile(),
                            $exception->getLine()
                        ));
                        WC_Retailcrm_Logger::error($exception->getTraceAsString());
                    }
                }

                update_option('retailcrm_customers_history_since_id', $lastChange['id']);
                WC_Retailcrm_Plugin::$history_run = false;
            }
        }

	    /**
	     * History orders
	     *
	     * @param string $date
	     * @param int    $since_id
	     *
	     * @return boolean
	     */
        protected function ordersHistory($date, $since_id)
        {
            $filter = array('startDate' => $date);
            $options = array_flip(array_filter($this->retailcrm_settings));

            if ($since_id) {
                $filter = array('sinceId' => $since_id);
            }

            $request = new WC_Retailcrm_Paginated_Request();
            $history = $request
                ->setApi($this->retailcrm)
                ->setMethod('ordersHistory')
                ->setParams(array($filter, '{{page}}'))
                ->setDataKey('history')
                ->setLimit(100)
                ->execute()
                ->getData();

            if (!empty($history)) {
                $last_change = end($history);
                $historyAssembly = WC_Retailcrm_History_Assembler::assemblyOrder($history);
                WC_Retailcrm_Logger::debug(__METHOD__, array('Assembled orders history:', $historyAssembly));
                WC_Retailcrm_Plugin::$history_run = true;

                foreach ($historyAssembly as $orderHistory) {
                    $order = WC_Retailcrm_Plugin::clearArray(apply_filters('retailcrm_history_before_save', $orderHistory));

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
                        WC_Retailcrm_Logger::add(
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
         * @param array    $order
         * @param array    $options
         * @param WC_Order $wc_order
         *
         * @return boolean
         * @throws \WC_Data_Exception
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

            $activeNetCost = $this->retailcrm_settings['send_delivery_net_cost'];

            if (isset($order['delivery']['netCost']) && wc_tax_enabled() && $activeNetCost != 'yes') {
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
         * @throws \Exception
         * @throws \WC_Data_Exception
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

            if (isset($order['customerComment'])) {
                $wc_order->set_customer_note($order['customerComment']);
            }

            if (isset($order['managerComment']) && !empty($order['managerComment'])) {
                $wc_order->add_order_note($order['managerComment'], 0, false);
            }

            if (isset($order['firstName'])) {
                $wc_order->set_shipping_first_name($order['firstName']);
            }

            if (isset($order['lastName'])) {
                $wc_order->set_shipping_last_name($order['lastName']);
            }

            if (!$this->handleCustomerDataChange($wc_order, $order)) {
                if (isset($order['phone'])) {
                    $wc_order->set_billing_phone($order['phone']);
                }

                if (isset($order['email'])) {
                    $wc_order->set_billing_email($order['email']);
                }

                if (isset($order['company']['address'])) {
                    $billingAddress = $order['company']['address'];

                    $wc_order->set_billing_state(self::arrayValue($billingAddress, 'region', '--'));
                    $wc_order->set_billing_postcode(self::arrayValue($billingAddress, 'index', '--'));
                    $wc_order->set_billing_country(self::arrayValue($billingAddress, 'country', '--'));
                    $wc_order->set_billing_city(self::arrayValue($billingAddress, 'city', '--'));
                    $wc_order->set_billing_address_1(self::arrayValue($billingAddress, 'text', '--'));
                }
            }

            if (array_key_exists('items', $order)) {
                foreach ($order['items'] as $key => $item) {
                    if (!isset($item['offer'][$this->bind_field])) {
                        continue;
                    }

                    if (isset($item['create']) && $item['create'] == true) {
                        $arItemsNew = array();
                        $arItemsOld = array();
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

                        $tmpArray = array_diff($arItemsNew, $arItemsOld);
                        $result = end($tmpArray);

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
                                && (isset($itemExternalId) && $itemExternalId[1] == $order_item->get_id())
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

            if (isset($order['items'])) {
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
            }

            return $wc_order->get_id();
        }

        /**
         * @param array $item
         * @param \WC_Order_Item $order_item
         * @param string $order_item_id
         *
         * @throws \Exception
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
         * @throws \WC_Data_Exception
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

            $orderResponse = $this->retailcrm->ordersGet($order['id'], 'id');

            if (null !== $orderResponse && $orderResponse->offsetExists('order')) {
                $crmOrder = $orderResponse['order'];

                if (isset($crmOrder['customer'])) {
                    $order['customer'] = $crmOrder['customer'];
                }

                if (isset($crmOrder['contact'])) {
                    $order['contact'] = $crmOrder['contact'];
                }

                if (isset($crmOrder['company'])) {
                    $order['company'] = $crmOrder['company'];
                }
            }

            $customerId = isset($order['customer']['externalId']) ? $order['customer']['externalId'] : null;

            if (self::isOrderCorporate($order)) {
                $customerId = isset($order['contact']['externalId']) ? $order['contact']['externalId'] : null;
            }

            $args = array(
                'status' => isset($options[$order['status']])
                    ? $options[$order['status']]
                    : 'processing',
                'customer_id' => $customerId
            );

            /** @var WC_Order|WP_Error $wc_order */
            $wc_order = wc_create_order($args);
            $wc_order->set_date_created($order['createdAt']);
            $customer = $order['customer'];
            $contactOrCustomer = array();
            $address = isset($order['customer']['address']) ? $order['customer']['address'] : array();
            $billingAddress = $address;
            $companyName = '';

            if ($this->retailcrm->getCorporateEnabled()) {
                $billingAddress = isset($order['company']['address']) ? $order['company']['address'] : $address;

                if (empty($billingAddress)) {
                    $billingAddress = $address;
                }
            }

            if ($this->retailcrm->getCorporateEnabled() && self::isOrderCorporate($order)) {
                if (isset($order['contact'])) {
                    $contactOrCustomer = $order['contact'];

                    if (self::noRealDataInEntity($contactOrCustomer)) {
                        $response = $this->retailcrm->customersGet($contactOrCustomer['id'], 'id');

                        if (!empty($response) && $response->offsetExists('customer')) {
                            $contactOrCustomer = $response['customer'];
                        }
                    }
                }
            } else {
                $contactOrCustomer = $customer;

                if (!self::isOrderCorporate($order) && self::noRealDataInEntity($contactOrCustomer)) {
                    $response = $this->retailcrm->customersGet($contactOrCustomer['id'], 'id');

                    if (!empty($response) && $response->offsetExists('customer')) {
                        $contactOrCustomer = $response['customer'];
                    }
                }
            }

            if ($wc_order instanceof WP_Error) {
                WC_Retailcrm_Logger::add(sprintf(
                    '[%d] error while creating order: %s',
                    $order['id'],
                    print_r($wc_order->get_error_messages(), true)
                ));

                return false;
            }

            if (isset($order['managerComment']) && !empty($order['managerComment'])) {
                $wc_order->add_order_note($order['managerComment'], 0, false);
            }

            // TODO Check if that works; also don't forget to set this company field while creating order from CMS!
            if ($this->retailcrm->getCorporateEnabled()
                && self::isOrderCorporate($order)
                && !empty($order['company'])
                && isset($order['company']['name'])
            ) {
                $companyName = $order['company']['name'];
            }

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
                'first_name' => isset($contactOrCustomer['firstName']) ? $contactOrCustomer['firstName'] : '',
                'last_name'  => isset($contactOrCustomer['lastName']) ? $contactOrCustomer['lastName'] : '',
                'company'    => $companyName,
                'email'      => isset($contactOrCustomer['email']) ? $contactOrCustomer['email'] : '',
                'phone'      => isset($contactOrCustomer['phones'][0]['number']) ? $contactOrCustomer['phones'][0]['number'] : '',
                'address_1'  => isset($billingAddress['text']) ? $billingAddress['text'] : '',
                'address_2'  => '',
                'city'       => isset($billingAddress['city']) ? $billingAddress['city'] : '',
                'state'      => isset($billingAddress['region']) ? $billingAddress['region'] : '',
                'postcode'   => isset($billingAddress['index']) ? $billingAddress['index'] : '',
                'country'    => isset($billingAddress['countryIso']) ? $billingAddress['countryIso'] : ''
            );

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

            $wc_order->set_address($address_billing, 'billing');
            $wc_order->set_address($address_shipping, 'shipping');
            $product_data = isset($order['items']) ? $order['items'] : array();

            if ($product_data) {
                foreach ($product_data as $key => $product) {
                    $arItemsNew = array();
                    $arItemsOld = array();
                    $item = retailcrm_get_wc_product($product['offer'][$this->bind_field], $this->retailcrm_settings);

                    if (!$item) {
                        $logger = new WC_Logger();
                        $logger->add('retailcrm', 'Product not found by ' . $this->bind_field);
                        continue;
                    }

                    if (isset($product['discountTotal']) && $product['discountTotal'] > 0) {
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
                        $arItemsTemp = array_diff($arItemsNew, $arItemsOld);
                        $result = end($arItemsTemp);
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

                    if (isset($order['delivery']['cost']) && !wc_tax_enabled()) {
                        $shipping->set_total($order['delivery']['cost']);
                    } elseif (isset($order['delivery']['netCost'])) {
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
            $data= array();
            $crmOrder= array();
            $order_items= array();

            if ($event == 'update') {
                $result = $this->retailcrm->ordersGet($order['externalId']);

                if (!empty($result) && $result->isSuccessful()) {
                    $crmOrder = $result['order'];
                    $data = $crmOrder;
                }
            }

            if ($event == 'create') {
               $data = $order;
            }

            $iterableItems = isset($data['items']) ? $data['items'] : array();

            foreach ($iterableItems as $id => $item) {
                $order_items[$id]['id'] = $item['id'];
                $order_items[$id]['offer'] = array('id' => $item['offer']['id']);

                if (!isset($order['items'][$item['id']])) {
                    if (empty($crmOrder)) {
                        $result = $this->retailcrm->ordersGet($order['id'], 'id');

                        if (!empty($result) && $result->isSuccessful()) {
                            $crmOrder = $result['order'];
                        }
                    }

                    if (!empty($crmOrder) && isset($crmOrder['items'][$item['id']])) {
                        $woocommerceId = self::getItemWoocommerceId($crmOrder['items'][$item['id']]);
                    } else {
                        WC_Retailcrm_Logger::add(
                            sprintf(
                                "Order externalId=`%s`: item doesn't have woocomerceId, skipping... (item id=`%s`)",
                                $order['externalId'],
                                $item['id']
                            )
                        );
                        continue;
                    }
                } else {
                    $woocommerceId = self::getItemWoocommerceId($order['items'][$item['id']]);
                }

                if (empty($woocommerceId)) {
                    WC_Retailcrm_Logger::add(
                        sprintf(
                            "Order externalId=`%s`: item doesn't have woocomerceId after all assertions, which" .
                            " is unexpected, skipping... (item id=`%s`)",
                            $order['externalId'],
                            $item['id']
                        )
                    );

                    continue;
                }

                $externalIds = array(
                    array(
                        'code' => 'woocomerce',
                        'value' => $item['offer']['externalId'] . '_' . $woocommerceId,
                    )
                );

                if (!empty($item['externalIds'])) {
                    $found = false;

                    foreach ($item['externalIds'] as $key => $extIdArr) {
                        if (isset($extIdArr['code']) && $extIdArr['code'] == 'woocomerce') {
                            $item['externalIds'][$key] = $externalIds;
                            $found = true;

                            break;
                        }
                    }

                    if (!$found) {
                        $order_items[$id]['externalIds'] = array_merge($item['externalIds'], $externalIds);
                    }
                } else {
                    $order_items[$id]['externalIds'] = $externalIds;
                }
            }

            if (!empty($order_items)) {
                $orderEdit = array(
                    'id' => $order['id'],
                    'items' => WC_Retailcrm_Plugin::clearArray($order_items),
                );

                $this->retailcrm->ordersEdit($orderEdit, 'id');
            }
        }

        /**
         * Handle customer data change (from individual to corporate, company change, etc)
         *
         * @param \WC_Order $wc_order
         * @param array     $order
         *
         * @return bool True if customer change happened; false otherwise.
         */
        protected function handleCustomerDataChange($wc_order, $order)
        {
            $handled = false;
            $crmOrder = array();
            $newCustomerId = null;
            $switcher = new WC_Retailcrm_Customer_Switcher();
            $data = new WC_Retailcrm_Customer_Switcher_State();
            $data->setWcOrder($wc_order);

            WC_Retailcrm_Logger::debug(
                __METHOD__,
                array(
                    'processing order',
                    $order
                )
            );

            if (isset($order['customer'])) {
                $crmOrder = $this->getCRMOrder($order['id'], 'id');

                if (empty($crmOrder)) {
                    WC_Retailcrm_Logger::addCaller(__METHOD__, sprintf(
                        'Cannot get order data from retailCRM. Skipping customer change. History data: %s',
                        print_r($order, true)
                    ));

                    return false;
                }

                $newCustomerId = $order['customer']['id'];
                $isChangedToRegular = self::isCustomerChangedToRegular($order);
                $isChangedToCorporate = self::isCustomerChangedToLegal($order);

                if (!$isChangedToRegular && !$isChangedToCorporate) {
                    $isChangedToCorporate = self::isOrderCorporate($crmOrder);
                    $isChangedToRegular = !$isChangedToCorporate;
                }

                if ($isChangedToRegular) {
                    $this->prepareChangeToIndividual(
                        self::arrayValue($crmOrder, 'customer', array()),
                        $data
                    );
                }
            }

            if (isset($order['contact'])) {
                $newCustomerId = $order['contact']['id'];

                if (empty($crmOrder)) {
                    $crmOrder = $this->getCRMOrder($order['id'], 'id');
                }

                if (empty($crmOrder)) {
                    WC_Retailcrm_Logger::addCaller(__METHOD__, sprintf(
                        'Cannot get order data from retailCRM. Skipping customer change. History data: %s',
                        print_r($order, true)
                    ));

                    return false;
                }

                if (self::isOrderCorporate($crmOrder)) {
                    $this->prepareChangeToIndividual(
                        self::arrayValue($crmOrder, 'contact', array()),
                        $data,
                        true
                    );

                    $data->setNewCustomer(array());
                }
            }

            if (isset($order['company'])) {
                if (empty($crmOrder)) {
                    $crmOrder = $this->getCRMOrder($order['id'], 'id');
                }

                $data->setNewCompany($crmOrder['company']);
            }

            if ($data->feasible()) {
                try {
                    $result = $switcher->setData($data)
                        ->build()
                        ->getResult();

                    $result->save();
                    $handled = true;
                } catch (\Exception $exception) {
                    $errorMessage = sprintf(
                        'Error switching order externalId=%s to customer id=%s (new company: id=%s %s). Reason: %s',
                        $order['externalId'],
                        $newCustomerId,
                        isset($order['company']) ? $order['company']['id'] : '',
                        isset($order['company']) ? $order['company']['name'] : '',
                        $exception->getMessage()
                    );
                    WC_Retailcrm_Logger::addCaller(__METHOD__, $errorMessage);
                    WC_Retailcrm_Logger::debug(__METHOD__, sprintf(
                        '%s%s%s',
                        $errorMessage,
                        PHP_EOL,
                        $exception->getTraceAsString()
                    ));
                    $handled = false;
                }
            }

            return $handled;
        }

        /**
         * Returns retailCRM order by id or by externalId.
         * It returns only order data, not ApiResponse or something.
         *
         * @param string $id Order identifier
         * @param string $by Search field (default: 'externalId')
         *
         * @return array
         */
        protected function getCRMOrder($id, $by = 'externalId')
        {
            $crmOrderResponse = $this->retailcrm->ordersGet($id, $by);

            if (!empty($crmOrderResponse)
                && $crmOrderResponse->isSuccessful()
                && $crmOrderResponse->offsetExists('order')
            ) {
                return (array) $crmOrderResponse['order'];
            }

            return array();
        }

        /**
         * Sets all needed data for customer switch to switcher state
         *
         * @param array                                 $crmCustomer
         * @param \WC_Retailcrm_Customer_Switcher_State $data
         * @param bool                                  $isContact
         */
        protected function prepareChangeToIndividual($crmCustomer, $data, $isContact = false)
        {
            WC_Retailcrm_Logger::debug(
                __METHOD__,
                array(
                    'Using this individual person data in order to set it into order,',
                    $data->getWcOrder()->get_id(),
                    ': ',
                    $crmCustomer
                )
            );

            if ($isContact) {
                $data->setNewContact($crmCustomer);
            } else {
                $data->setNewCustomer($crmCustomer);
            }
        }

        /**
         * @param array $itemData
         *
         * @return int|string|null
         */
        protected static function getItemWoocommerceId($itemData)
        {
            $woocommerceId = null;

            if (isset($itemData['woocomerceId'])) {
                $woocommerceId = $itemData['woocomerceId'];
            } elseif (isset($itemData['externalIds'])) {
                foreach ($itemData['externalIds'] as $extIdArr) {
                    if (isset($extIdArr['code']) && $extIdArr['code'] == 'woocomerce') {
                        $woocommerceId = $extIdArr['value'];
                    }
                }
            }

            if (!empty($woocommerceId) && strpos($woocommerceId, '_') !== false) {
                $wcIdArr = explode('_', $woocommerceId);
                $woocommerceId = $wcIdArr[1];
            }

            return $woocommerceId;
        }

        /**
         * Returns true if provided crm order is corporate
         *
         * @param array $order
         *
         * @return bool
         */
        private static function isOrderCorporate($order)
        {
            return isset($order['customer']['type']) && $order['customer']['type'] == 'customer_corporate';
        }

        /**
         * This assertion returns true if customer was changed from legal entity to individual person.
         * It doesn't return true if customer was changed from one individual person to another.
         *
         * @param array $assembledOrder Order data, assembled from history
         *
         * @return bool True if customer in order was changed from corporate to regular
         */
        private static function isCustomerChangedToRegular($assembledOrder)
        {
            return isset($assembledOrder['contragentType']) && $assembledOrder['contragentType'] == 'individual';
        }

        /**
         * This assertion returns true if customer was changed from individual person to a legal entity.
         * It doesn't return true if customer was changed from one legal entity to another.
         *
         * @param array $assembledOrder Order data, assembled from history
         *
         * @return bool True if customer in order was changed from corporate to regular
         */
        private static function isCustomerChangedToLegal($assembledOrder)
        {
            return isset($assembledOrder['contragentType']) && $assembledOrder['contragentType'] == 'legal-entity';
        }

        /**
         * Helper method. Checks if entity only contains identifiers.
         * Returns true if entity contains only these keys: 'id', 'externalId', 'site', or if array is empty.
         * Returns false otherwise.
         *
         * @param array $entity
         *
         * @return bool
         */
        private static function noRealDataInEntity($entity)
        {
            $allowedKeys = array('id', 'externalId', 'site');

            if (count($entity) <= 3) {
                foreach (array_keys($entity) as $key) {
                    if (!in_array($key, $allowedKeys)) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        }

        /**
         * @param array|\ArrayObject|\ArrayAccess $arr
         * @param string $key
         * @param string $def
         *
         * @return mixed|string
         */
        private static function arrayValue($arr, $key, $def = '')
        {
            if (!is_array($arr) && !($arr instanceof ArrayObject) && !($arr instanceof ArrayAccess)) {
                return $def;
            }

            if (!array_key_exists($key, $arr) && !empty($arr[$key])) {
                return $def;
            }

            return isset($arr[$key]) ? $arr[$key] : $def;
        }
    }

endif;
