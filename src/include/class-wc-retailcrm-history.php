<?php

if (!class_exists('WC_Retailcrm_History')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_History - Allows transfer data orders/customers with CRM.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_History
    {
        const PAGE_LIMIT = 25;

        /** @var \DateTime */
        protected $startDate;

        /** @var array|mixed|void */
        protected $retailcrmSettings;

        /** @var bool|WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array|mixed */
        protected $orderMethods = [];

        /** @var string */
        protected $bindField = 'externalId';

        /**
         * WC_Retailcrm_History constructor.
         *
         * @param WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5|bool $retailcrm (default = false)
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

            // Because the orderHistory method uses array_flip, and the option with array called error
            if (isset($this->retailcrmSettings['stores_for_uploading'])) {
                unset($this->retailcrmSettings['stores_for_uploading']);
            }

            $this->retailcrm = $retailcrm;
            $this->startDate = new DateTime('-1 days');
        }

        /**
         * Get history method.
         *
         * @return void
         */
        public function getHistory()
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            try {
                $this->customersHistory();
                $this->ordersHistory();
            // @codeCoverageIgnoreStart
            } catch (\Exception $exception) {
                WC_Retailcrm_Logger::add(
                    sprintf(
                        '[%s] - %s',
                        $exception->getMessage(),
                        'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine()
                    )
                );
            }
            // @codeCoverageIgnoreEnd
        }

        /**
         * History customers
         *
         * @return void
         * @throws \Exception
         */
        protected function customersHistory()
        {
            $page = 1;
            $sinceId = get_option('retailcrm_customers_history_since_id');
            $filter = !empty($sinceId)
                ? ['sinceId' => $sinceId]
                : ['startDate' => $this->startDate->format('Y-m-d H:i:s')];

            do {
                $historyResponse = $this->retailcrm->customersHistory($filter);
                $history = $this->getHistoryData($historyResponse);

                if (!empty($history)) {
                    $lastChange = end($history);
                    $filter['sinceId'] = $lastChange['id'];

                    update_option('retailcrm_customers_history_since_id', $lastChange['id']);

                    WC_Retailcrm_Logger::debug(__METHOD__, [
                        'Processing customers history, ID:',
                        $filter['sinceId']
                    ]);

                    $builder   = new WC_Retailcrm_WC_Customer_Builder();
                    $customers = WC_Retailcrm_History_Assembler::assemblyCustomer($history);

                    WC_Retailcrm_Logger::debug(__METHOD__, ['Assembled customers history:', $customers]);
                    WC_Retailcrm_Plugin::$history_run = true;

                    foreach ($customers as $crmCustomer) {
                         /*
                         * Only update customers, if customer not exist in WP - skip this customer !
                         * Update sinceId, because we must process history data.
                         */
                        if (!isset($crmCustomer['externalId'])) {
                            $filter['sinceId'] = $lastChange['id'];

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

                                // If customer not found in the DB, update sinceId
                                $filter['sinceId'] = $lastChange['id'];

                                continue;
                            }
                            // @codeCoverageIgnoreEnd

                            $wcCustomer = $builder
                                ->setData($crmCustomer)
                                ->build()
                                ->getResult();

                            if ($wcCustomer instanceof WC_Customer) {
                                $wcCustomer->save();

                                $customFields = apply_filters(
                                    'retailcrm_process_customer_custom_fields',
                                    $this->getCustomData('customer'),
                                    $crmCustomer,
                                    $wcCustomer
                                );

                                $this->updateMetaData($customFields, $crmCustomer, $wcCustomer);
                            }

                            WC_Retailcrm_Logger::debug(__METHOD__, ['Updated WC_Customer:', $wcCustomer]);

                            // @codeCoverageIgnoreStart
                        } catch (Exception $exception) {
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
                } else {
                    break;
                }

                $page++;

                if ($page > self::PAGE_LIMIT) {
                    break;
                }

            } while ($historyResponse['pagination']['currentPage'] < $historyResponse['pagination']['totalPageCount']);
        }

        /**
         * History orders
         *
         * @return bool
         */
        protected function ordersHistory()
        {
            $page = 1;
            $options = array_flip(array_filter($this->retailcrmSettings));
            $sinceId = get_option('retailcrm_orders_history_since_id');
            $filter = !empty($sinceId)
                ? ['sinceId' => $sinceId]
                : ['startDate' => $this->startDate->format('Y-m-d H:i:s')];

            do {
                $historyResponse = $this->retailcrm->OrdersHistory($filter);
                $history = $this->getHistoryData($historyResponse);

                if (!empty($history)) {
                    $lastChange = end($history);
                    $filter['sinceId'] = $lastChange['id'];

                    update_option('retailcrm_orders_history_since_id', $lastChange['id']);

                    WC_Retailcrm_Logger::debug(__METHOD__, [
                        'Processing orders history, ID:',
                        $filter['sinceId']
                    ]);

                    $historyAssembly = WC_Retailcrm_History_Assembler::assemblyOrder($history);

                    WC_Retailcrm_Logger::debug(__METHOD__, ['Assembled orders history:', $historyAssembly]);
                    WC_Retailcrm_Plugin::$history_run = true;

                    foreach ($historyAssembly as $orderHistory) {
                        $order = WC_Retailcrm_Plugin::clearArray(
                            apply_filters(
                                'retailcrm_history_before_save',
                                $orderHistory
                            )
                        );

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

                                $customFields = apply_filters(
                                    'retailcrm_process_order_custom_fields',
                                    $this->getCustomData('order'),
                                    $order,
                                    $wcOrder
                                );

                                $this->updateMetaData($customFields, $order, $wcOrder);

                                $wcOrderNumber = $wcOrder->get_order_number();

                                if (
                                    $order['number'] != $wcOrderNumber
                                    && isset($this->retailcrmSettings['update_number'])
                                    && $this->retailcrmSettings['update_number'] == WC_Retailcrm_Base::YES
                                ) {
                                    $this->retailcrm->ordersEdit(
                                        ['id' => $order['id'], 'number' => $wcOrderNumber],
                                        'id'
                                    );
                                }
                            }
                        // @codeCoverageIgnoreStart
                        } catch (Exception $exception) {
                            WC_Retailcrm_Logger::add(
                                sprintf(
                                    '[%s] - %s',
                                    $exception->getMessage(),
                                    'Exception in file - ' . $exception->getFile() . ' on line ' . $exception->getLine()
                                )
                            );

                            continue;
                        }
                        // @codeCoverageIgnoreEnd
                    }
                } else {
                    break;
                }

                if ($page > self::PAGE_LIMIT) {
                    break;
                }

            } while ($historyResponse['pagination']['currentPage'] < $historyResponse['pagination']['totalPageCount']);

            WC_Retailcrm_Plugin::$history_run = false;

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

            if (wc_tax_enabled()) {
                $rate = getShippingRate();

                $shipping->set_total($this->getDeliveryCost($order, $rate));
            } else {
                $shipping->set_total($this->getDeliveryCost($order));
            }

            if (isset($order['delivery']['service']['code'])) {
                $service = retailcrm_get_delivery_service(
                    $shipping->get_method_id(),
                    $order['delivery']['service']['code']
                );

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

            if (isset($order['status']) && isset($options[$order['status']])) {
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
                    $billingAddress = $order['contact']['address'];

                    // @codeCoverageIgnoreStart
                    if (isset($billingAddress['country'])) {
                        $wcOrder->set_billing_country($billingAddress['country']);
                    }

                    if (isset($billingAddress['region'])) {
                        $wcOrder->set_billing_state($billingAddress['region']);
                    }

                    if (isset($billingAddress['index'])) {
                        $wcOrder->set_billing_postcode($billingAddress['index']);
                    }

                    if (isset($billingAddress['city'])) {
                        $wcOrder->set_billing_city($billingAddress['city']);
                    }

                    if (isset($billingAddress['street'])) {
                        $wcOrder->set_billing_address_1($billingAddress['street']);
                    }

                    if (isset($billingAddress['building'])) {
                        $wcOrder->set_billing_address_2($billingAddress['building']);
                    }
                    // @codeCoverageIgnoreEnd
                }
            }

            if (array_key_exists('items', $order)) {
                foreach ($order['items'] as $key => $crmProduct) {
                    if (!isset($crmProduct['offer'][$this->bindField])) {
                        continue;
                    }

                    if (isset($crmProduct['create']) && $crmProduct['create'] == true) {
                        $arItemsNew = [];
                        $arItemsOld = [];

                        $wcProduct = retailcrm_get_wc_product(
                            $crmProduct['offer'][$this->bindField],
                            $this->retailcrmSettings
                        );

                        if (!$wcProduct) {
                            WC_Retailcrm_Logger::add('Product not found by ' . $this->bindField);
                            continue;
                        }

                        foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                            $arItemsOld[$orderItemId] = $orderItemId;
                        }

                        if (isset($crmProduct['externalIds'])) {
                            foreach ($crmProduct['externalIds'] as $externalId) {
                                if ($externalId['code'] == 'woocomerce') {
                                    $itemExternalId = explode('_', $externalId['value']);
                                }
                            }

                            if (array_key_exists($itemExternalId[1], $arItemsOld)) {
                                continue;
                            }
                        }

                        $this->addProductInWcOrder($wcOrder, $wcProduct, $crmProduct);

                        foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                            $arItemsNew[$orderItemId] = $orderItemId;
                        }

                        $tmpArray = array_diff($arItemsNew, $arItemsOld);
                        $result = end($tmpArray);

                        $order['items'][$key]['woocomerceId'] = $result;
                    } else {
                        foreach ($wcOrder->get_items() as $wcOrderItem) {
                            if (
                                isset($this->retailcrmSettings['bind_by_sku'])
                                && $this->retailcrmSettings['bind_by_sku'] == WC_Retailcrm_Base::YES
                            ) {
                                $offerId = $crmProduct['offer']['xmlId'];
                            } elseif ($wcOrderItem['variation_id'] != 0) {
                                $offerId = $wcOrderItem['variation_id'];
                            } else {
                                $offerId = $wcOrderItem['product_id'];
                            }

                            if (isset($crmProduct['externalIds'])) {
                                foreach ($crmProduct['externalIds'] as $externalId) {
                                    if ($externalId['code'] == 'woocomerce') {
                                        $itemExternalId = explode('_', $externalId['value']);
                                    }
                                }
                            } else {
                                $itemExternalId = explode('_', $crmProduct['externalId']);
                            }

                            if (
                                $offerId == $crmProduct['offer'][$this->bindField]
                                && (isset($itemExternalId) && $itemExternalId[1] == $wcOrderItem->get_id())
                            ) {
                                if (isset($crmProduct['delete']) && $crmProduct['delete'] == true) {
                                    wc_delete_order_item($itemExternalId[1]);
                                }

                                $this->updateProductInWcOrder($wcOrderItem, $crmProduct);
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

                    if (isset($shippingAddress['index'])) {
                        $wcOrder->set_shipping_postcode($shippingAddress['index']);
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

            if (isset($order['payments']) && !empty($order['payments'])) {
                $payment = WC_Payment_Gateways::instance();
                $paymentTypes = $payment->payment_gateways();

                if (count($order['payments']) == 1) {
                    $paymentType = reset($order['payments']);

                    if (
                        isset($paymentType['type'])
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
                    $this->editOrder($order, 'update');
                }
            }

            return $wcOrder->get_id();
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

            $orderStatus = isset($options[$order['status']]) ? $options[$order['status']] : 'pending';

            $wcOrder = wc_create_order(['status' => $orderStatus, 'customer_id' => $customerId]);
            $wcOrder->set_date_created($order['createdAt']);
            $customer = $order['customer'];
            $contactOrCustomer = [];
            $billingAddress = '';

            if ($this->retailcrm->getCorporateEnabled() && self::isOrderCorporate($order)) {
                if (isset($order['contact'])) {
                    $contactOrCustomer = $order['contact'];

                    if (!empty($order['contact']['address'])) {
                        $billingAddress = $order['contact']['address'];
                    } else {
                        WC_Retailcrm_Logger::add(
                            sprintf(
                                '[%d] => %s',
                                $order['id'],
                                'Error: Contact address is empty'
                            )
                        );
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

            $shippingAddress      = $order['delivery']['address'];
            $shippingAddressLines = $this->getAddressLines($shippingAddress['text']);

            $addressShipping = [
                'first_name' => $order['firstName'] ?? '',
                'last_name'  => $order['lastName'] ?? '',
                'company'    => '',
                'address_1'  => $shippingAddressLines['address_1'] ?? $shippingAddressLines,
                'address_2'  => $shippingAddressLines['address_2'] ?? '',
                'city'       => $shippingAddress['city'] ?? '',
                'state'      => $shippingAddress['region'] ?? '',
                'postcode'   => $shippingAddress['index'] ?? '',
                'country'    => $shippingAddress['countryIso'] ?? ''
            ];

            $billingAddressLines = $this->getAddressLines($billingAddress['text']);

            $addressBilling = [
                'first_name' => $contactOrCustomer['firstName'] ?? '',
                'last_name'  => $contactOrCustomer['lastName'] ?? '',
                'company'    => $companyName,
                'email'      => $contactOrCustomer['email'] ?? '',
                'phone'      => $contactOrCustomer['phones'][0]['number'] ?? '',
                'address_1'  => $billingAddressLines['address_1'] ?? $billingAddressLines,
                'address_2'  => $billingAddressLines['address_2'] ?? '',
                'city'       => $billingAddress['city'] ?? '',
                'state'      => $billingAddress['region'] ?? '',
                'postcode'   => $billingAddress['index'] ?? '',
                'country'    => $billingAddress['countryIso'] ?? ''
            ];

            $wcOrder->set_address($addressShipping, 'shipping');
            $wcOrder->set_address($addressBilling, 'billing');

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

            $crmOrderItems = $order['items'] ?? [];

            if ($crmOrderItems) {
                foreach ($crmOrderItems as $key => $crmProduct) {
                    if (isset($crmProduct['delete']) && $crmProduct['delete'] == true) {
                        continue;
                    }

                    $arItemsNew = [];
                    $arItemsOld = [];

                    $wcProduct = retailcrm_get_wc_product(
                        $crmProduct['offer'][$this->bindField],
                        $this->retailcrmSettings
                    );

                    if (!$wcProduct) {
                        WC_Retailcrm_Logger::add('Product not found by ' . $this->bindField);
                        continue;
                    }

                    foreach ($wcOrder->get_items() as $orderItemId => $orderItem) {
                        $arItemsOld[$orderItemId] = $orderItemId;
                    }

                    $this->addProductInWcOrder($wcOrder, $wcProduct, $crmProduct);

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

                    if (wc_tax_enabled()) {
                        $rate = getShippingRate();

                        $shipping->set_total($this->getDeliveryCost($order, $rate));
                    } else {
                        $shipping->set_total($this->getDeliveryCost($order));
                    }

                    $shipping->set_order_id($wcOrder->get_id());
                    $shipping->save();

                    $wcOrder->add_item($shipping);
                }
            }

            $ids[] = [
                'id' => (int) $order['id'],
                'externalId' => (int) $wcOrder->get_id()
            ];

            $wcOrder->save();

            $this->retailcrm->ordersFixExternalIds($ids);

            $this->editOrder($order);

            return $wcOrder->get_id();
        }

        /**
         * @param array  $order Data CRM order.
         * @param string $event
         */
        protected function editOrder($order, $event = 'create')
        {
            $data       = [];
            $crmOrder   = [];
            $orderItems = [];

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

            $iterableItems = $data['items'] ?? [];

            foreach ($iterableItems as $id => $item) {
                if (isset($item['delete']) && $item['delete'] == true) {
                    continue;
                }

                $orderItems[$id]['id']        = $item['id'];
                $orderItems[$id]['offer']     = ['id' => $item['offer']['id']];
                $orderItems[$id]['priceType'] = $item['priceType'] ?? '';

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

                $externalIds = [
                    [
                        'code' => 'woocomerce',
                        'value' => $item['offer']['externalId'] . '_' . $woocommerceId,
                    ]
                ];

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
                $orderEdit = [
                    'id' => $order['id'],
                    'items' => WC_Retailcrm_Plugin::clearArray($orderItems),
                ];

                $this->retailcrm->ordersEdit($orderEdit, 'id');
            }
        }

        /**
         * Returns data for address_1 and address_2(if exist data for this field) for WC order.
         *
         * @param string|null $addressLine
         *
         * @return mixed
         */
        private function getAddressLines($addressLine)
        {
            if ($addressLine === null) {
                return null;
            }

            if (strpos($addressLine, WC_Retailcrm_Abstracts_Address::ADDRESS_LINE_DIVIDER) !== false) {
                $addressLines = explode(WC_Retailcrm_Abstracts_Address::ADDRESS_LINE_DIVIDER, $addressLine);

                return ['address_1' => $addressLines[0], 'address_2' => $addressLines[1]];
            }

            return $addressLine;
        }

        /**
         * Add product in WC order.
         *
         * @param $wcOrder
         * @param $wcProduct
         * @param $crmProduct
         *
         * @return void
         */
        private function addProductInWcOrder($wcOrder, $wcProduct, $crmProduct)
        {
            $discountTotal   = $crmProduct['discountTotal'];
            $productQuantity = $crmProduct['quantity'];

            $wcOrder->add_product(
                $wcProduct,
                $productQuantity,
                [
                    'total'    => $this->getProductTotalPrice($wcProduct, $productQuantity, $discountTotal),
                    'subtotal' => $this->getProductSubTotalPrice($wcProduct, $productQuantity),
                ]
            );
        }

        /**
         * Update product in WC order.
         *
         * @param $wcOrderItem
         * @param $crmProduct
         *
         * @return void
         */
        private function updateProductInWcOrder($wcOrderItem, $crmProduct)
        {
            if (!empty($crmProduct['quantity'])) {
                $wcProduct = retailcrm_get_wc_product($crmProduct['offer'][$this->bindField], $this->retailcrmSettings);
                $productQuantity = $crmProduct['quantity'];
                $subTotal        = $this->getProductSubTotalPrice($wcProduct, $productQuantity);

                $wcOrderItem->set_quantity($productQuantity);
                $wcOrderItem->set_subtotal($subTotal);

                $wcOrderItem->save();
            }

            if (!empty($crmProduct['summ'])) {
                if (wc_tax_enabled()) {
                    $wcOrder  = wc_get_order($wcOrderItem->get_order_id());
                    $itemRate = getOrderItemRate($wcOrder);

                    if ($itemRate === null) {
                        $itemRate = getShippingRate();
                    }

                    $wcOrderItem->set_total(calculatePriceExcludingTax($crmProduct['summ'], $itemRate));
                } else {
                    $wcOrderItem->set_total($crmProduct['summ']);
                }

                $wcOrderItem->save();
            }
        }

        private function getProductSubTotalPrice($wcProduct, $quantity)
        {
            return wc_get_price_excluding_tax($wcProduct, ['qty' => $quantity]);
        }

        private function getProductTotalPrice($wcProduct, $quantity, $discountTotal)
        {
            return wc_get_price_excluding_tax(
                $wcProduct,
                [
                    'qty'   => $quantity,
                    'price' => $wcProduct->get_price() - $discountTotal,
                ]
            );
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
            $data          = new WC_Retailcrm_Customer_Switcher_State();
            $handled       = false;
            $switcher      = new WC_Retailcrm_Customer_Switcher();
            $crmOrder      = [];
            $newCustomerId = null;

            $data->setWcOrder($wcOrder);

            WC_Retailcrm_Logger::debug(__METHOD__, ['processing order', $order]);

            if (isset($order['customer'])) {
                $crmOrder = $this->getCRMOrder($order['id'], 'id');

                if (empty($crmOrder)) {
                    WC_Retailcrm_Logger::addCaller(__METHOD__, sprintf(
                        'Cannot get order data from retailCRM. Skipping customer change. History data: %s',
                        print_r($order, true)
                    ));

                    return false;
                }

                $newCustomerId        = $order['customer']['id'];
                $isChangedToRegular   = self::isCustomerChangedToRegular($order);
                $isChangedToCorporate = self::isCustomerChangedToLegal($order);

                if (!$isChangedToRegular && !$isChangedToCorporate) {
                    $isChangedToCorporate = self::isOrderCorporate($crmOrder);
                    $isChangedToRegular   = !$isChangedToCorporate;
                }

                if ($isChangedToRegular) {
                    $this->prepareChangeToIndividual(
                        self::arrayValue($crmOrder, 'customer', []),
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

                    $data->setNewCustomer([]);
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
                // @codeCoverageIgnoreStart
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
                // @codeCoverageIgnoreEnd
            }

            return $handled;
        }

        /**
         * Get delivery cost for WC order
         *
         * @param array $order
         *
         * @return double
         */
        private function getDeliveryCost($order, $rate = null)
        {
            $deliveryCost = $order['delivery']['cost'] ?? 0.0;

            if (empty($rate) || empty($deliveryCost)) {
                return $deliveryCost;
            }

            return calculatePriceExcludingTax($deliveryCost, $rate);
        }

        /**
         * Get custom fields mapping with settings.
         *
         * @param string $dataType This data type which we get.
         *
         * @return mixed|void
         */
        private function getCustomData($dataType)
        {
            if (!empty($this->retailcrmSettings["$dataType-meta-data-retailcrm"])) {
                return json_decode(
                    $this->retailcrmSettings["$dataType-meta-data-retailcrm"],
                    true
                );
            }
        }

        /**
         * Update meta data in CMS.
         *
         * @param array  $customFields Custom fields witch settings mapping.
         * @param array  $crmData Data witch CRM.
         * @param object $wcObject Object witch WC.
         *
         * @return void
         */
        private function updateMetaData($customFields, $crmData, $wcObject)
        {
            if (empty($customFields)) {
                return;
            }

            foreach ($customFields as $metaKey => $customKey) {
                if (empty($crmData['customFields'][$customKey])) {
                    continue;
                }

                if ($wcObject instanceof WC_Order) {
                    update_post_meta($wcObject->get_id(), $metaKey, $crmData['customFields'][$customKey]);
                } else {
                    update_user_meta($wcObject->get_id(), $metaKey, $crmData['customFields'][$customKey]);
                }
            }
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

            if (
                !empty($crmOrderResponse)
                && $crmOrderResponse->isSuccessful()
                && $crmOrderResponse->offsetExists('order')
            ) {
                return (array) $crmOrderResponse['order'];
            }

            return [];
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
                [
                    'Using this individual person data in order to set it into order,',
                    $data->getWcOrder()->get_id(),
                    ': ',
                    $crmCustomer
                ]
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
            $allowedKeys = ['id', 'externalId', 'site'];

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

        /**
         *
         * Get history data.
         *
         * @param $historyResponse
         *
         * @return array
         */
        private function getHistoryData($historyResponse)
        {
            if (
                !$historyResponse instanceof WC_Retailcrm_Response
                || !$historyResponse->isSuccessful()
                || empty($historyResponse['history'])
                || empty($historyResponse['pagination'])
            ) {
                return [];
            }

            time_nanosleep(0, 300000000);

            return $historyResponse['history'];
        }
    }

endif;
