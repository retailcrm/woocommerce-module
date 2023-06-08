<?php

if (!class_exists('WC_Retailcrm_Orders')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Orders - Allows transfer data orders with CMS.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Orders
    {
        /** @var bool|WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array */
        protected $retailcrm_settings;

        /** @var WC_Retailcrm_Order_Item */
        protected $order_item;

        /** @var WC_Retailcrm_Order_Address */
        protected $order_address;

        /** @var WC_Retailcrm_Order_Payment */
        protected $order_payment;

        /** @var WC_Retailcrm_Customers */
        protected $customers;

        /** @var WC_Retailcrm_Order */
        protected $orders;

        /** @var array */
        private $ordersGetRequestCache = [];

        /** @var array */
        private $order = [];

        /** @var array */
        private $payment = [];

        /**@var array */
        private $customFields = [];

        public function __construct(
            $retailcrm,
            $retailcrm_settings,
            $order_item,
            $order_address,
            $customers,
            $orders,
            $order_payment
        ) {
            $this->retailcrm = $retailcrm;
            $this->retailcrm_settings = $retailcrm_settings;
            $this->order_item = $order_item;
            $this->order_address = $order_address;
            $this->customers = $customers;
            $this->orders = $orders;
            $this->order_payment = $order_payment;

            if (!empty($retailcrm_settings['order-meta-data-retailcrm'])) {
                $this->customFields = json_decode($retailcrm_settings['order-meta-data-retailcrm'], true);
            }
        }

        /**
         * Create order. Returns wc_get_order data or error string.
         *
         * @param      $order_id
         *
         * @return bool|WC_Order|WC_Order_Refund|string
         * @throws \Exception
         */
        public function orderCreate($order_id)
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $this->order_payment->resetData();

            $wcOrder = wc_get_order($order_id);
            $this->processOrder($wcOrder);

            try {
                $response = $this->retailcrm->ordersCreate($this->order);

                if ($response instanceof WC_Retailcrm_Proxy) {
                    if ($response->isSuccessful()) {
                        return $wcOrder;
                    }

                    return $response->getErrorString();
                }
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }

            return $wcOrder;
        }

        /**
         * Process order customer data
         *
         * @param \WC_Order $wcOrder
         * @param bool      $update
         *
         * @return bool Returns false if order cannot be processed
         * @throws \Exception
         */
        protected function processOrderCustomerInfo($wcOrder, $update = false)
        {
            $customerWasChanged = false;
            $wpUser = $wcOrder->get_user();

            if ($update) {
                $response = $this->getCrmOrder($wcOrder->get_id());

                if (!empty($response)) {
                    $customerWasChanged = self::isOrderCustomerWasChanged($wcOrder, $response);
                }
            }

            if ($wpUser instanceof WP_User) {
                if (!$this->customers->isCustomer($wpUser)) {
                    return false;
                }

                $wpUserId = (int) $wpUser->get('ID');

                if (!$update || ($update && $customerWasChanged)) {
                    $this->fillOrderCreate($wpUserId, $wpUser->get('billing_email'), $wcOrder);
                }
            } else {
                $wcCustomer = $this->customers->buildCustomerFromOrderData($wcOrder);

                if (!$update || ($update && $customerWasChanged)) {
                    $this->fillOrderCreate(0, $wcCustomer->get_billing_email(), $wcOrder);
                }
            }

            return true;
        }

        /**
         * Fill order on create
         *
         * @param int       $wcCustomerId
         * @param string    $wcCustomerEmail
         * @param \WC_Order $wcOrder
         *
         * @throws \Exception
         */
        protected function fillOrderCreate($wcCustomerId, $wcCustomerEmail, $wcOrder)
        {
            $isContact = $this->retailcrm->getCorporateEnabled() && static::isCorporateOrder($wcOrder);

            $foundCustomer = $this->customers->findCustomerEmailOrId(
                $wcCustomerId,
                strtolower($wcCustomerEmail),
                $isContact
            );

            if (empty($foundCustomer)) {
                $foundCustomerId = $this->customers->createCustomer($wcCustomerId, $wcOrder);

                if (!empty($foundCustomerId)) {
                    $this->order['customer']['id'] = $foundCustomerId;
                }
            } else {
                $this->order['customer']['id'] = $foundCustomer['id'];
                $foundCustomerId = $foundCustomer['id'];
            }

            $this->order['contragent']['contragentType'] = 'individual';

            if ($this->retailcrm->getCorporateEnabled() && static::isCorporateOrder($wcOrder)) {
                unset($this->order['contragent']['contragentType']);

                $crmCorporate = $this->customers->searchCorporateCustomer(array(
                    'contactIds' => array($foundCustomerId),
                    'companyName' => $wcOrder->get_billing_company()
                ));

                if (empty($crmCorporate)) {
                    $crmCorporate = $this->customers->searchCorporateCustomer(array(
                        'companyName' => $wcOrder->get_billing_company()
                    ));
                }

                if (empty($crmCorporate)) {
                    $corporateId = $this->customers->createCorporateCustomerForOrder(
                        $foundCustomerId,
                        $wcCustomerId,
                        $wcOrder
                    );
                    $this->order['customer']['id'] = $corporateId;
                } else {
                    // Testing of this method occurs in customers tests.
                    // @codeCoverageIgnoreStart
                    $addressFound = $this->customers->fillCorporateAddress(
                        $crmCorporate['id'],
                        new WC_Customer($wcCustomerId),
                        $wcOrder
                    );

                    // If address not found create new address.
                    if (!$addressFound) {
                        WC_Retailcrm_Logger::add(
                            sprintf(
                                '[%d] => %s',
                                $this->order['customer']['id'],
                                'Notification: Create new address for corporate customer'
                            )
                        );
                    }

                    $this->order['customer']['id'] = $crmCorporate['id'];
                    // @codeCoverageIgnoreEnd
                }

                $companiesResponse = $this->retailcrm->customersCorporateCompanies(
                    $this->order['customer']['id'],
                    [],
                    null,
                    null,
                    'id'
                );

                if (!empty($companiesResponse) && $companiesResponse->isSuccessful()) {
                    foreach ($companiesResponse['companies'] as $company) {
                        if ($company['name'] == $wcOrder->get_billing_company()) {
                            $this->order['company'] = [
                                'id' => $company['id'],
                                'name' => $company['name']
                            ];
                            break;
                        }
                    }
                }

                $this->order['contact']['id'] = $foundCustomerId;
            }
        }

        /**
         * Edit order in CRM
         *
         * @param int $order_id
         *
         * @return WC_Order $order | null
         * @throws \Exception
         */
        public function updateOrder($order_id)
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $wcOrder = wc_get_order($order_id);

            $this->processOrder($wcOrder, true);

            $response = $this->retailcrm->ordersEdit($this->order);

            if (!empty($response) && $response->isSuccessful()) {
                $this->payment = $this->orderUpdatePaymentType($wcOrder);
            }

            return $wcOrder;
        }

        /**
         * Update order payment type
         *
         * @param WC_Order $order
         *
         * @return null | array $payment
         */
        protected function orderUpdatePaymentType($order)
        {
            if (!isset($this->retailcrm_settings[$order->get_payment_method()])) {
                return null;
            }

            $retailcrmOrder = $this->getCrmOrder($order->get_id());

            if (empty($retailcrmOrder)) {
                return null;
            }

            foreach ($retailcrmOrder['payments'] as $paymentData) {
                $paymentId = explode('-', $paymentData['externalId']);

                if ($paymentId[0] == $order->get_id()) {
                    $payment = $paymentData;
                }
            }

            if (empty($payment)) {
                return null;
            }

            if ($payment['type'] == $this->retailcrm_settings[$order->get_payment_method()] && $order->is_paid()) {
                return $this->sendPayment($order, true, $payment['externalId']);
            }

            if ($payment['type'] != $this->retailcrm_settings[$order->get_payment_method()]) {
                $response = $this->retailcrm->ordersPaymentDelete($payment['id']);

                if (!empty($response) && $response->isSuccessful()) {
                    return $this->sendPayment($order);
                }
            }

            return null;
        }

        /**
         * process to combine order data
         *
         * @param WC_Order $order
         * @param boolean  $update
         *
         * @return void
         * @throws \Exception
         */
        protected function processOrder($order, $update = false)
        {
            if (!$order instanceof WC_Order) {
                return;
            }

            if ($order->get_status() == 'auto-draft') {
                return;
            }

            if ($update === true) {
                $this->orders->is_new = false;
            }

            $orderData = $this->orders->build($order)->getData();

            if ($order->get_items('shipping')) {
                $shippings = $order->get_items('shipping');
                $shipping = reset($shippings);
                $shipping_code = explode(':', $shipping['method_id']);

                if (isset($this->retailcrm_settings[$shipping['method_id']])) {
                    $shipping_method = $shipping['method_id'];
                } elseif (isset($this->retailcrm_settings[$shipping_code[0]])) {
                    $shipping_method = $shipping_code[0];
                } else {
                    $shipping_method = $shipping['method_id'] . ':' . $shipping['instance_id'];
                }

                if (!empty($shipping_method) && !empty($this->retailcrm_settings[$shipping_method])) {
                    $orderData['delivery']['code'] = $this->retailcrm_settings[$shipping_method];
                    $service = retailcrm_get_delivery_service($shipping['method_id'], $shipping['instance_id']);

                    if ($service) {
                        $orderData['delivery']['service'] = [
                            'name' => $service['title'],
                            'code' => $service['instance_id'],
                            'active' => true
                        ];
                    }
                }

                if (isset($shipping['total'])) {
                    $orderData['delivery']['netCost'] = $shipping['total'];
                    $orderData['delivery']['cost']    = isset($shipping['total_tax'])
                        ? $shipping['total'] + $shipping['total_tax']
                        : $shipping['total'];

                    if (wc_tax_enabled()) {
                        $shippingTaxClass = get_option('woocommerce_shipping_tax_class');

                        $rate = $shippingTaxClass == 'inherit'
                            ? getOrderItemRate($order)
                            : getShippingRate();

                        if ($rate !== null) {
                            $orderData['delivery']['vatRate'] = $rate;
                        }
                    }
                }
            }

            $orderData['delivery']['address'] = $this->order_address->build($order)->getData();
            $orderItems = [];

            /** @var WC_Order_Item_Product $item */
            foreach ($order->get_items() as $item) {
                $orderItems[] = $this->order_item->build($item)->getData();

                $this->order_item->resetData();
            }

            $orderData['items'] = $orderItems;
            $orderData['discountManualAmount']  = 0;
            $orderData['discountManualPercent'] = 0;

            if (!$update && $order->get_total() > 0) {
                $this->order_payment->isNew = true;
                $orderData['payments'][]    = $this->order_payment->build($order)->getData();
            }

            if (!empty($this->customFields)) {
                foreach ($this->customFields as $metaKey => $customKey) {
                    $metaValue = $order->get_meta($metaKey);

                    if (empty($metaValue)) {
                        continue;
                    }

                    if (strpos($customKey, 'default-crm-field') !== false) {
                        $crmField = explode('#', $customKey);

                        if (count($crmField) === 2 && isset($crmField[1])) {
                            $orderData[$crmField[1]] = $metaValue;
                        } elseif (isset($crmField[1], $crmField[2], $crmField[3])) {
                            // For order delivery
                            $orderData[$crmField[1]][$crmField[2]][$crmField[3]] = $metaValue;
                        }
                    } else {
                        $orderData['customFields'][$customKey] = $metaValue;
                    }
                }
            }

            $this->order = WC_Retailcrm_Plugin::clearArray($orderData);
            $this->processOrderCustomerInfo($order, $update);

            $this->order = apply_filters(
                'retailcrm_process_order',
                WC_Retailcrm_Plugin::clearArray($this->order),
                $order
            );
        }

        /**
         * Send payment in CRM
         *
         * @param WC_Order $order
         * @param boolean $update
         * @param mixed $externalId
         *
         * @return array $payment
         */
        protected function sendPayment($order, $update = false, $externalId = false)
        {
            $this->order_payment->isNew = !$update;

            $payment = $this->order_payment->build($order, $externalId)->getData();
            $integrationPayments = get_option('retailcrm_integration_payments');

            if (is_array($integrationPayments)) {
                $integrationPayments = array_flip($integrationPayments);
            }

            if ($update === true && isset($integrationPayments[$payment['type']])) {
                return $payment;
            }

            if ($update === false) {
                $this->retailcrm->ordersPaymentCreate($payment);
            } else {
                $this->retailcrm->ordersPaymentEdit($payment);
            }

            return $payment;
        }

        /**
         * ordersGet wrapper with cache (in order to minimize request count).
         *
         * @param int|string $orderId
         * @param bool       $cached
         *
         * @return array
         */
        protected function getCrmOrder($orderId, $cached = true)
        {
            if ($cached && isset($this->ordersGetRequestCache[$orderId])) {
                return (array) $this->ordersGetRequestCache[$orderId];
            }

            $crmOrder = [];
            $response = $this->retailcrm->ordersGet($orderId);

            if (!empty($response) && $response->isSuccessful() && isset($response['order'])) {
                $crmOrder = (array) $response['order'];
                $this->ordersGetRequestCache[$orderId] = $crmOrder;
            }

            return $crmOrder;
        }

        /**
         * @return array
         */
        public function getOrder()
        {
            return $this->order;
        }

        /**
         * @return array
         */
        public function getPayment()
        {
            return $this->payment;
        }

        /**
         * Returns true if provided order is for corporate customer
         *
         * @param WC_Order $order
         *
         * @return bool
         */
        public static function isCorporateOrder($order)
        {
            $billingCompany = $order->get_billing_company();

            return !empty($billingCompany);
        }

        /**
         * Returns true if passed crm order is corporate
         *
         * @param array|\ArrayAccess $order
         *
         * @return bool
         */
        public static function isCorporateCrmOrder($order)
        {
            return (is_array($order) || $order instanceof ArrayAccess)
                && isset($order['customer'])
                && isset($order['customer']['type'])
                && $order['customer']['type'] == 'customer_corporate';
        }

        /**
         * Returns true if customer in order was changed. `true` will be returned if one of these four conditions is met:
         *
         *  1. If CMS order is corporate and retailCRM order is not corporate or vice versa, then customer obviously
         *     needs to be updated in retailCRM.
         *  2. If billing company from CMS order is not the same as the one in the retailCRM order,
         *     then company needs to be updated.
         *  3. If contact person or individual externalId is different from customer ID in the CMS order, then
         *     contact person or customer in retailCRM should be updated (even if customer id in the order is not set).
         *  4. If contact person or individual email is not the same as the CMS order billing email, then
         *     contact person or customer in retailCRM should be updated.
         *
         * @param \WC_Order $wcOrder
         * @param array|\ArrayAccess $crmOrder
         *
         * @return bool
         */
        public static function isOrderCustomerWasChanged($wcOrder, $crmOrder)
        {
            if (!isset($crmOrder['customer'])) {
                return false;
            }

            $customerWasChanged = self::isCorporateOrder($wcOrder) != self::isCorporateCrmOrder($crmOrder);
            $synchronizableUserData = (self::isCorporateCrmOrder($crmOrder) && isset($crmOrder['contact']))
                ? $crmOrder['contact'] : $crmOrder['customer'];

            if (!$customerWasChanged) {
                if (self::isCorporateCrmOrder($crmOrder)) {
                    $currentCrmCompany = isset($crmOrder['company']) ? $crmOrder['company']['name'] : '';

                    if (!empty($currentCrmCompany) && $currentCrmCompany != $wcOrder->get_billing_company()) {
                        $customerWasChanged = true;
                    }
                }

                if (
                    isset($synchronizableUserData['externalId'])
                    && $synchronizableUserData['externalId'] != $wcOrder->get_customer_id()
                ) {
                    $customerWasChanged = true;
                } elseif (
                    isset($synchronizableUserData['email'])
                    && $synchronizableUserData['email'] != $wcOrder->get_billing_email()
                ) {
                    $customerWasChanged = true;
                }
            }

            return $customerWasChanged;
        }
    }
endif;