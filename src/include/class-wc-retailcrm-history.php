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
        protected $retailcrmSettings;

        /** @var bool|\WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V4|\WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array|mixed */
        protected $orderMethods = array();

        /** @var string */
        protected $bindField = 'externalId';

        /**
         * WC_Retailcrm_History constructor.
         *
         * @param \WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V4|\WC_Retailcrm_Client_V5|bool $retailcrm (default = false)
         *
         * @throws \Exception
         */
        public function __construct($retailcrm = false)
        {
            $this->retailcrmSettings = get_option(WC_Retailcrm_Base::$option_key);

            if (
                isset($this->retailcrmSettings['bind_by_sku'])
                && $this->retailcrmSettings['bind_by_sku'] == WC_Retailcrm_Base::YES
            ) {
                $this->bindField = 'xmlId';
            }

            if (isset($this->retailcrmSettings['order_methods'])) {
                $this->orderMethods = $this->retailcrmSettings['order_methods'];
                unset($this->retailcrmSettings['order_methods']);
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
            $ordersSinceId = get_option('retailcrm_orders_history_since_id');
            $customersSinceId = get_option('retailcrm_customers_history_since_id');

            // @codeCoverageIgnoreStart
            // TODO: There is a task to analyze the work of getting history by date
            if (!$ordersSinceId && isset($this->retailcrmSettings['history_orders'])) {
                $this->startDateOrders = new DateTime($this->retailcrmSettings['history_orders']);
            }


            if (!$customersSinceId && isset($this->retailcrmSettings['history_customers'])) {
                $this->startDateCustomers = new DateTime($this->retailcrmSettings['history_customers']);
            }
            // @codeCoverageIgnoreEnd

            try {
                $this->customersHistory($this->startDateCustomers->format('Y-m-d H:i:s'), $customersSinceId);
                $this->ordersHistory($this->startDateOrders->format('Y-m-d H:i:s'), $ordersSinceId);
            // @codeCoverageIgnoreStart
            } catch (\Exception $exception) {
                WC_Retailcrm_Logger::add(
                    sprintf("[%s] - %s", $exception->getMessage(),
                        'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine())
                );
            }
            // @codeCoverageIgnoreEnd
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
                    // Only update customers, if customer not exist in WP - skip this customer !
                    if (!isset($crmCustomer['externalId'])) {
                        continue;
                    }

                    try {
                        $builder->reset();

                        // @codeCoverageIgnoreStart
                        if (!$builder->loadExternalId($crmCustomer['externalId'])) {
                            WC_Retailcrm_Logger::addCaller(__METHOD__, sprintf(
                                'Customer with id=%s is not found in the DB, skipping...',
                                $crmCustomer['externalId']
                            ));
                            continue;
                        }
                        // @codeCoverageIgnoreEnd

                        $wcCustomer = $builder
                            ->setData($crmCustomer)
                            ->build()
                            ->getResult();

                        if ($wcCustomer instanceof WC_Customer) {
                            $wcCustomer->save();
                        }

                        WC_Retailcrm_Logger::debug(__METHOD__, array('Updated WC_Customer:', $wcCustomer));

                    // @codeCoverageIgnoreStart
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
                    // @codeCoverageIgnoreEnd
                }

                update_option('retailcrm_customers_history_since_id', $lastChange['id']);
                WC_Retailcrm_Plugin::$history_run = false;
            }
        }

        /**
         * History orders
         *
         * @param string $date
         * @param int $sinceId
         *
         * @return boolean
         */
        protected function ordersHistory($date, $sinceId)
        {
            $filter = array('startDate' => $date);
            $options = array_flip(array_filter($this->retailcrmSettings));

            if ($sinceId) {
                $filter = array('sinceId' => $sinceId);
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
                $lastChange = end($history);
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
                            $wcOrderId = $this->orderUpdate($order, $options);
                        } else {
                            $wcOrderId = $this->orderCreate($order, $options);
                        }

                        $wcOrder = wc_get_order($wcOrderId);

                        if ($wcOrder instanceof WC_Order) {
                            $wcOrder->calculate_totals();
                        }

                    // @codeCoverageIgnoreStart
                    } catch (Exception $exception) {
                        WC_Retailcrm_Logger::add(
                            sprintf("[%s] - %s", $exception->getMessage(),
                                'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine())
                        );

                        continue;
                    }
                    // @codeCoverageIgnoreEnd
                }

                update_option('retailcrm_orders_history_since_id', $lastChange['id']);
                WC_Retailcrm_Plugin::$history_run = false;
            }

            return true;
        }

        /**
         * Update shipping
         *
         * @param array    $order
         * @param array    $options
         * @param WC_Order $wcOrder
         *
         * @return boolean
         * @throws \WC_Data_Exception
         */
        protected function updateShippingItemId($order, $options, $wcOrder)
        {
            $create = false;

            $shipping = $wcOrder->get_items('shipping');

            if (!$shipping) {
                $shipping = new WC_Order_Item_Shipping();
                $create = true;
            } else {
                $shipping = reset($shipping);
            }

            $dataStore = $shipping->get_data_store();

            if (isset($order['delivery']['code'])) {
                if (!isset($options[$order['delivery']['code']])) {
                    return false;
                }

                $shippingMethods = get_wc_shipping_methods();
                $shipping->set_method_title($shippingMethods[$options[$order['delivery']['code']]]['name']);
                $shipping->set_method_id($options[$order['delivery']['code']]);
            }

            if (isset($order['delivery']['cost']) && !wc_tax_enabled()) {
                $shipping->set_total($order['delivery']['cost']);
            }

            if (!empty($order['delivery']['netCost']) && wc_tax_enabled()) {
                $shipping->set_total($order['delivery']['netCost']);
            }

            if (isset($order['delivery']['service']['code'])) {
                $service = retailcrm_get_delivery_service($shipping->get_method_id(), $order['delivery']['service']['code']);

                if ($service) {
                    $shipping->set_instance_id($order['delivery']['service']['code']);
                }
            }

            if ($create === true) {
                $dataStore->create($shipping);
                $shipping->set_order_id($wcOrder->get_id());
            }

            $dataStore->update($shipping);

            return true;
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
            $wcOrder = wc_get_order($order['externalId']);

            if (!$wcOrder instanceof WC_Order) {
                return false;
            }

            if (isset($options[$order['status']])) {
                $wcOrder->update_status($options[$order['status']]);
            }

            if (isset($order['managerComment']) && !empty($order['managerComment'])) {
                $wcOrder->add_order_note($order['managerComment'], 0, false);
            }

            if (isset($order['customerComment']) && !empty($order['customerComment'])) {
                $wcOrder->set_customer_note($order['customerComment']);
            }

            if (isset($order['firstName'])) {
                $wcOrder->set_shipping_first_name($order['firstName']);
            }

            if (isset($order['lastName'])) {
                $wcOrder->set_shipping_last_name($order['lastName']);
            }

            if (!$this->handleCustomerDataChange($wcOrder, $order)) {
                if (isset($order['phone'])) {
                    $wcOrder->set_billing_phone($order['phone']);
                }

                if (isset($order['email'])) {
                    $wcOrder->set_billing_email($order['email']);
                }

                if (isset($order['contact']['address'])) {
                    var_dump('test');
                    $billingAddress = $order['contact']['address'];

                    // @codeCoverageIgnoreStart
                    // TODO: There is a task to analyze the work set billing address in WC order
                    $wcOrder->set_billing_state(self::arrayValue($billingAddress, 'region'));
                    $wcOrder->set_billing_postcode(self::arrayValue($billingAddress, 'index'));
                    $wcOrder->set_billing_country(self::arrayValue($billingAddress, 'country'));
                    $wcOrder->set_billing_city(self::arrayValue($billingAddress, 'city'));
                    $wcOrder->set_billing_address_1(self::arrayValue($billingAddress, 'text'));
                    // @codeCoverageIgnoreEnd
                }
            }

            if (array_key_exists('items', $order)) {
                foreach ($order['items'] as $key => $item) {
                    if (!isset($item['offer'][$this->bindField])) {
                        continue;
                    }

                    if (isset($item['create']) && $item['create'] == true) {
                        $arItemsNew = array();
                        $arItemsOld = array();
                        $product = retailcrm_get_wc_product(
                            $item['offer'][$this->bindField],
                            $this->retailcrmSettings
                        );

                        foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                            $arItemsOld[$orderItemId] = $orderItemId;
                        }

                        if (isset($item['externalIds'])) {
                            foreach ($item['externalIds'] as $externalId) {
                                if ($externalId['code'] == 'woocomerce') {
                                    $itemExternalId = explode('_', $externalId['value']);
                                }
                            }

                            if (array_key_exists($itemExternalId[1], $arItemsOld)) {
                                continue;
                            }
                        }

                        $wcOrder->add_product($product, $item['quantity']);

                        foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                            $arItemsNew[$orderItemId] = $orderItemId;
                        }

                        $tmpArray = array_diff($arItemsNew, $arItemsOld);
                        $result = end($tmpArray);

                        $order['items'][$key]['woocomerceId'] = $result;
                    } else {
                        foreach ($wcOrder->get_items() as $orderItem) {
                            if (
                                isset($this->retailcrmSettings['bind_by_sku'])
                                && $this->retailcrmSettings['bind_by_sku'] == WC_Retailcrm_Base::YES
                            ) {
                                $offerId = $item['offer']['xmlId'];
                            } elseif ($orderItem['variation_id'] != 0) {
                                $offerId = $orderItem['variation_id'];
                            } else {
                                $offerId = $orderItem['product_id'];
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

                            if (
                                $offerId == $item['offer'][$this->bindField]
                                && (isset($itemExternalId) && $itemExternalId[1] == $orderItem->get_id())
                            ) {
                                $this->deleteOrUpdateOrderItem($item, $orderItem, $itemExternalId[1]);
                            }
                        }
                    }
                }
            }

            if (array_key_exists('delivery', $order)) {
                $this->updateShippingItemId($order, $options, $wcOrder);

                if (isset($order['delivery']['address'])) {
                    $shippingAddress = $order['delivery']['address'];

                    if (isset($shippingAddress['region'])) {
                        $wcOrder->set_shipping_state($shippingAddress['region']);
                    }

                    if (isset($shippingAddress['city'])) {
                        $wcOrder->set_shipping_city($shippingAddress['city']);
                    }

                    if (isset($shippingAddress['street'])) {
                        $wcOrder->set_shipping_address_1($shippingAddress['street']);
                    }

                    if (isset($shippingAddress['building'])) {
                        $wcOrder->set_shipping_address_2($shippingAddress['building']);
                    }
                }
            }

            if (isset($order['paymentType'])) {
                if (!empty($options[$order['paymentType']])) {
                    $payment = WC_Payment_Gateways::instance();
                    $paymentTypes = $payment->payment_gateways();

                    if (isset($paymentTypes[$options[$order['paymentType']]])) {
                        $wcOrder->set_payment_method($paymentTypes[$options[$order['paymentType']]]);
                    }
                }
            }

            if (isset($order['payments']) && !empty($order['payments'])) {
                $payment = WC_Payment_Gateways::instance();
                $paymentTypes = $payment->payment_gateways();

                if (count($order['payments']) == 1) {
                    $paymentType = reset($order['payments']);

                    if (isset($paymentType['type'])
                        && isset($options[$paymentType['type']])
                        && isset($paymentTypes[$options[$paymentType['type']]])
                    ) {
                        $paymentType = $paymentTypes[$options[$paymentType['type']]];
                        $wcOrder->set_payment_method($paymentType);
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

                    if (isset($paymentTypes[$options[$paymentType['type']]])) {
                        $wcOrder->set_payment_method($paymentTypes[$options[$paymentType['type']]]);
                    }
                }
            }

            $wcOrder->save();

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
                    $this->editOrder($order,'update');
                }
            }

            return $wcOrder->get_id();
        }

        /**
         * @param array $item
         * @param \WC_Order_Item $orderItem
         * @param string $orderItemId
         *
         * @throws \Exception
         */
        private function deleteOrUpdateOrderItem($item, $orderItem, $orderItemId)
        {
            if (isset($item['delete']) && $item['delete'] == true) {
                wc_delete_order_item($orderItemId);
            } else {
                if (isset($item['quantity']) && $item['quantity']) {
                    $orderItem->set_quantity($item['quantity']);
                    $product = retailcrm_get_wc_product($item['offer'][$this->bindField], $this->retailcrmSettings);
                    $orderItem->set_subtotal($product->get_price());
                    $dataStore = $orderItem->get_data_store();
                    $dataStore->update($orderItem);
                }

                if (isset($item['summ']) && $item['summ']) {
                    $orderItem->set_total($item['summ']);
                    $dataStore = $orderItem->get_data_store();
                    $dataStore->update($orderItem);
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

            if (
                is_array($this->orderMethods)
                && $this->orderMethods
                && isset($order['orderMethod'])
                && !in_array($order['orderMethod'], $this->orderMethods)
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

                if (isset($crmOrder['delivery'])) {
                    $order['delivery'] = $crmOrder['delivery'];
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

            /** @var WC_Order|WP_Error wcOrder */
            $wcOrder = wc_create_order($args);
            $wcOrder->set_date_created($order['createdAt']);
            $customer = $order['customer'];
            $contactOrCustomer = array();
            $billingAddress = '';

            if ($this->retailcrm->getCorporateEnabled() && self::isOrderCorporate($order)) {
                if (isset($order['contact'])) {
                    $contactOrCustomer = $order['contact'];

                    if (!empty($order['contact']['address'])) {
                        $billingAddress = $order['contact']['address'];
                    } else {
                        WC_Retailcrm_Logger::add(sprintf('[%d] => %s', $order['id'], 'Error: Contact address is empty'));
                    }

                    if (self::noRealDataInEntity($contactOrCustomer)) {
                        $response = $this->retailcrm->customersGet($contactOrCustomer['id'], 'id');

                        if (!empty($response) && $response->offsetExists('customer')) {
                            $contactOrCustomer = $response['customer'];
                        }
                    }
                }
            } else {
                $contactOrCustomer = $customer;

                if (!empty($customer['address'])) {
                    $billingAddress = $customer['address'];
                } else {
                    WC_Retailcrm_Logger::add(sprintf('[%d] => %s', $order['id'], 'Error: Customer address is empty'));
                }


                if (!self::isOrderCorporate($order) && self::noRealDataInEntity($contactOrCustomer)) {
                    $response = $this->retailcrm->customersGet($contactOrCustomer['id'], 'id');

                    if (!empty($response) && $response->offsetExists('customer')) {
                        $contactOrCustomer = $response['customer'];
                    }
                }
            }

            if ($wcOrder instanceof WP_Error) {
                WC_Retailcrm_Logger::add(sprintf(
                    '[%d] error while creating order: %s',
                    $order['id'],
                    print_r($wcOrder->get_error_messages(), true)
                ));

                return false;
            }

            if (isset($order['managerComment']) && !empty($order['managerComment'])) {
                $wcOrder->add_order_note($order['managerComment'], 0, false);
            }

            if (isset($order['customerComment']) && !empty($order['customerComment'])) {
                $wcOrder->set_customer_note($order['customerComment']);
            }

            $companyName = '';

            if (
                $this->retailcrm->getCorporateEnabled()
                && self::isOrderCorporate($order)
                && !empty($customer['mainCompany'])
                && isset($customer['mainCompany']['name'])
            ) {
                $companyName = $customer['mainCompany']['name'];
            }

            $addressShipping = array(
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

            $addressBilling = array(
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
                    $paymentTypes = $payment->payment_gateways();
                    $payments = $order['payments'];
                    $paymentType = end($payments);
                    if (isset($options[$paymentType['type']]) && isset($paymentTypes[$options[$paymentType['type']]])) {
                        $wcOrder->set_payment_method($paymentTypes[$options[$paymentType['type']]]);
                    }
                }
            }

            $wcOrder->set_address($addressBilling, 'billing');
            $wcOrder->set_address($addressShipping, 'shipping');
            $productData = isset($order['items']) ? $order['items'] : array();

            if ($productData) {
                foreach ($productData as $key => $product) {
                    if (isset($product['delete']) && $product['delete'] == true) {
                        continue;
                    }

                    $arItemsNew = array();
                    $arItemsOld = array();
                    $item = retailcrm_get_wc_product($product['offer'][$this->bindField], $this->retailcrmSettings);

                    if (!$item) {
                        $logger = new WC_Logger();
                        $logger->add('retailcrm', 'Product not found by ' . $this->bindField);
                        continue;
                    }

                    foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                        $arItemsOld[$orderItemId] = $orderItemId;
                    }

                    $wcOrder->add_product(
                        $item,
                        $product['quantity'],
                        array(
                            'subtotal' => wc_get_price_excluding_tax(
                                $item,
                                array(
                                    'price' => $product['initialPrice'],
                                    'qty' => $product['quantity'],)
                            ),
                            'total' => wc_get_price_excluding_tax(
                                $item,
                                array(
                                    'price' => $product['initialPrice'] - $product['discountTotal'],
                                    'qty' => $product['quantity'],)
                            ),
                        )
                    );

                    foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                        $arItemsNew[$orderItemId] = $orderItemId;
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

                    $shipping->set_order_id($wcOrder->get_id());

                    $shipping->save();
                    $wcOrder->add_item($shipping);
                }
            }

            $ids[] = array(
                'id' => (int) $order['id'],
                'externalId' => (int) $wcOrder->get_id()
            );

            $wcOrder->save();

            $this->retailcrm->ordersFixExternalIds($ids);

            $this->editOrder($order);

            return $wcOrder->get_id();
        }

        /**
         * @param        $order
         * @param string $event
         */
        protected function editOrder($order, $event = 'create')
        {
            $data= array();
            $crmOrder= array();
            $orderItems= array();

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
                if (isset($item['delete']) && $item['delete'] == true) {
                    continue;
                }

                $orderItems[$id]['id'] = $item['id'];
                $orderItems[$id]['offer'] = array('id' => $item['offer']['id']);

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
                        // @codeCoverageIgnoreStart
                        WC_Retailcrm_Logger::add(
                            sprintf(
                                "Order externalId=`%s`: item doesn't have woocomerceId, skipping... (item id=`%s`)",
                                $order['externalId'],
                                $item['id']
                            )
                        );
                        continue;
                        // @codeCoverageIgnoreEnd
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
                        $orderItems[$id]['externalIds'] = array_merge($item['externalIds'], $externalIds);
                    }
                } else {
                    $orderItems[$id]['externalIds'] = $externalIds;
                }
            }

            if (!empty($orderItems)) {
                $orderEdit = array(
                    'id' => $order['id'],
                    'items' => WC_Retailcrm_Plugin::clearArray($orderItems),
                );

                $this->retailcrm->ordersEdit($orderEdit, 'id');
            }
        }

        /**
         * Handle customer data change (from individual to corporate, company change, etc)
         *
         * @param \WC_Order $wcOrder
         * @param array     $order
         *
         * @return bool True if customer change happened; false otherwise.
         */
        protected function handleCustomerDataChange($wcOrder, $order)
        {
            $handled = false;
            $crmOrder = array();
            $newCustomerId = null;
            $switcher = new WC_Retailcrm_Customer_Switcher();
            $data = new WC_Retailcrm_Customer_Switcher_State();
            $data->setWcOrder($wcOrder);

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
