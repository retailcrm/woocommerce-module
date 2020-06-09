<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Orders
 * @category Integration
 * @author   RetailCRM
 */

if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) :

    /**
     * Class WC_Retailcrm_Orders
     */
    class WC_Retailcrm_Orders
    {
        /** @var bool|WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V5|\WC_Retailcrm_Client_V4 */
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
        private $ordersGetRequestCache = array();

        /** @var array */
        private $order = array();

        /** @var array */
        private $payment = array();

        public function __construct(
            $retailcrm = false,
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
        }

        /**
         * Upload orders to CRM
         *
         * @param array $include
         *
         * @return array $uploadOrders | null
         * @throws \Exception
         */
        public function ordersUpload($include = array())
        {
            if (!$this->retailcrm) {
                return null;
            }

            $uploader = new WC_Retailcrm_Customers(
                $this->retailcrm,
                $this->retailcrm_settings,
                new WC_Retailcrm_Customer_Address()
            );

            $orders = get_posts(array(
                'numberposts' => -1,
                'post_type' => wc_get_order_types('view-orders'),
                'post_status' => array_keys(wc_get_order_statuses()),
                'include' => $include
            ));

            $regularUploadErrors = array();
            $corporateUploadErrors = array();

            foreach ($orders as $data_order) {
                $order = wc_get_order($data_order->ID);

                $errorMessage = $this->orderCreate($data_order->ID);

                if (is_string($errorMessage)) {
                    if ($this->retailcrm->getCorporateEnabled() && self::isCorporateOrder($order)) {
                        $corporateUploadErrors[$data_order->ID] = $errorMessage;
                    } else {
                        $regularUploadErrors[$data_order->ID] = $errorMessage;
                    }
                }
            }

            static::logOrdersUploadErrors($regularUploadErrors, 'Error while uploading these regular orders');
            static::logOrdersUploadErrors($corporateUploadErrors, 'Error while uploading these corporate orders');

            return array();
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
            if (!$this->retailcrm) {
                return null;
            }

            $this->order_payment->reset_data();

            $wcOrder = wc_get_order($order_id);
            $this->processOrder($wcOrder);
<<<<<<< HEAD
            $wpUser = $wcOrder->get_user();

            if ($wpUser instanceof WP_User) {
<<<<<<< HEAD
<<<<<<< HEAD
=======
<<<<<<< HEAD
<<<<<<< HEAD
                $wpUserId = (int)$wpUser->get('ID');
                $wooCustomer = new WC_Customer($wpUserId);

<<<<<<< HEAD
                if (empty($foundCustomer)) {
                	$foundCustomer = $this->customers->searchCustomer(array(
                		'email' => $wcOrder->get_billing_email()
	                ));
                }

                if (empty($foundCustomer)) {
                    $customerId = $this->customers->createCustomer($wpUserId);
=======
                if ($isCorporateEnabled && WC_Retailcrm_Customers::customerPossiblyCorporate($wooCustomer)) {
                    $foundRegularCustomer = $this->customers->searchCustomer(array(
                        'externalId' => $wpUserId
                    ));
>>>>>>> WIP: corporate customers support

                    // If regular customer was found - create order with it.
                    if (!empty($foundRegularCustomer)) {
                        return $this->orderCreate($order_id, true);
                    }

                    $foundCustomer = $this->customers->searchCorporateCustomer(array(
                        'externalId' => $wpUserId
                    ));

                    if (empty($foundCustomer)) {
                        $customerData = $this->customers->createCorporateCustomer($wpUserId);

                        if ($customerData instanceof WC_Retailcrm_Customer_Corporate_Response) {
                            if (!empty($customerData->getId())) {
                                $this->order['customer']['id'] = $customerData->getId();
                            }

                            if (!empty($customerData->getContactId())) {
                                $this->order['contact']['id'] = $customerData->getContactId();
                            }

                            if (!empty($customerData->getContactExternalId())) {
                                $this->order['contact']['externalId'] = $customerData->getContactExternalId();
                            }
                        }
                    } else {
                        $this->order['customer']['externalId'] = $foundCustomer['externalId'];

                        if (isset($foundCustomer['mainCustomerContact']['customer']['id'])) {
                            $this->order['contact']['id'] = $foundCustomer['mainCustomerContact']['customer']['id'];
                        }
                    }
                } else {
<<<<<<< HEAD
	                if (!empty($foundCustomer['externalId'])) {
		                $this->order['customer']['externalId'] = $foundCustomer['externalId'];
	                } else {
		                $this->order['customer']['id'] = $foundCustomer['id'];
	                }
=======
                    $foundCustomer = $this->customers->searchCustomer(array(
                        'externalId' => $wpUserId
                    ));

                    if (empty($foundCustomer)) {
                        $customerId = $this->customers->createRegularCustomer($wpUserId);

                        if (!empty($customerId)) {
                            $this->order['customer']['id'] = $customerId;
                        }
                    } else {
                        $this->order['customer']['externalId'] = $foundCustomer['externalId'];
                    }
>>>>>>> WIP: corporate customers support
                }
=======
=======
=======
>>>>>>> restore correct merge state
                if (!WC_Retailcrm_Customers::isCustomer($wpUser)) {
                    return $wcOrder;
                }

<<<<<<< HEAD
>>>>>>> corporate customers alternative logic
>>>>>>> corporate customers alternative logic
                $wpUserId = (int) $wpUser->get('ID');
<<<<<<< HEAD
                $this->fillOrderCreate($wpUserId, $wpUser->get('email'), $wcOrder);
<<<<<<< HEAD
=======
>>>>>>> WIP: different (better) logic for corporate clients, fix for possible problem with identifiers
=======
                $this->fillOrderCreate($wpUserId, $wpUser->get('billing_email'), $wcOrder);
>>>>>>> merge changes
>>>>>>> merge changes
=======
                $wpUserId = (int) $wpUser->get('ID');
                $this->fillOrderCreate($wpUserId, $wpUser->get('billing_email'), $wcOrder);
>>>>>>> restore correct merge state
            } else {
                $wcCustomer = $this->customers->buildCustomerFromOrderData($wcOrder);
                $this->fillOrderCreate(0, $wcCustomer->get_billing_email(), $wcOrder);
            }
=======
>>>>>>> company fix for corporate clients implementation & pass client change from cms to retailCRM

            try {
                $response = $this->retailcrm->ordersCreate($this->order);

                if ($response instanceof WC_Retailcrm_Response) {
                    if ($response->isSuccessful()) {
                        return $wcOrder;
                    } else {
                        return $response->getErrorString();
                    }
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
                if (!WC_Retailcrm_Customers::isCustomer($wpUser)) {
                    return false;
                }

                $wpUserId = (int) $wpUser->get('ID');

                if (!$update || $update && $customerWasChanged) {
                    $this->fillOrderCreate($wpUserId, $wpUser->get('billing_email'), $wcOrder);
                }
            } else {
                $wcCustomer = $this->customers->buildCustomerFromOrderData($wcOrder);

                if (!$update || $update && $customerWasChanged) {
                    $this->fillOrderCreate(0, $wcCustomer->get_billing_email(), $wcOrder);
                }
            }

            if ($update && $customerWasChanged) {
                $this->order['firstName'] = $wcOrder->get_shipping_first_name();
                $this->order['lastName'] = $wcOrder->get_shipping_last_name();
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
            $foundCustomerId = '';
            $foundCustomer = $this->customers->findCustomerEmailOrId($wcCustomerId, $wcCustomerEmail);

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
<<<<<<< HEAD
<<<<<<< HEAD
=======
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
                	if (!empty($foundCustomer['externalId'])) {
		                $this->order['customer']['externalId'] = $foundCustomer['externalId'];
	                } else {
                		$this->order['customer']['id'] = $foundCustomer['id'];
	                }
=======
>>>>>>> new address logic & fixes
                    $foundCustomer = $this->customers->searchCustomer(array(
                        'email' => $wcOrder->get_billing_email()
                    ));

                    if (empty($foundCustomer)) {
                        $customerId = $this->customers->createRegularCustomer($wcCustomer);

                        if (!empty($customerId)) {
                            $this->order['customer']['id'] = $customerId;
                        }
                    } else {
                        $this->order['customer']['externalId'] = $foundCustomer['externalId'];
                    }
<<<<<<< HEAD
=======
>>>>>>> WIP: corporate customers support
=======
=======
=======
>>>>>>> restore correct merge state
                    $this->customers->fillCorporateAddress(
                        $crmCorporate['id'],
                        new WC_Customer($wcCustomerId),
                        $wcOrder
                    );
<<<<<<< HEAD
>>>>>>> new address logic & fixes
>>>>>>> new address logic & fixes
                    $this->order['customer']['id'] = $crmCorporate['id'];

=======
                    $this->order['customer']['id'] = $crmCorporate['id'];
>>>>>>> restore correct merge state
                }

                $companiesResponse = $this->retailcrm->customersCorporateCompanies(
                    $this->order['customer']['id'],
                    array(),
                    null,
                    null,
                    'id'
                );

                if (!empty($companiesResponse) && $companiesResponse->isSuccessful()) {
                    foreach ($companiesResponse['companies'] as $company) {
                        if ($company['name'] == $wcOrder->get_billing_company()) {
                            $this->order['company'] = array(
                                'id' => $company['id'],
                                'name' => $company['name']
                            );
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
            if (!$this->retailcrm) {
                return null;
            }

            $wcOrder = wc_get_order($order_id);
            $this->processOrder($wcOrder, true);

            $response = $this->retailcrm->ordersEdit($this->order);

<<<<<<< HEAD
            if ((!empty($response) && $response->isSuccessful()) && $this->retailcrm_settings['api_version'] == 'v5') {
=======
            if ($response->isSuccessful()) {
<<<<<<< HEAD
>>>>>>> Dropped v4, fixes for several bugs, tests.
                $this->payment = $this->orderUpdatePaymentType($order);
=======
                $this->payment = $this->orderUpdatePaymentType($wcOrder);
>>>>>>> company fix for corporate clients implementation & pass client change from cms to retailCRM
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

<<<<<<< HEAD
            $response = $this->retailcrm->ordersGet($order->get_id());

            if (!empty($response) && $response->isSuccessful()) {
                $retailcrmOrder = $response['order'];
=======
            $retailcrmOrder = $this->getCrmOrder($order->get_id());
>>>>>>> several fixes & environment variable which can be used to output logs to stdout in tests

            if (!empty($retailcrmOrder)) {
                foreach ($retailcrmOrder['payments'] as $payment_data) {
                    $payment_external_id = explode('-', $payment_data['externalId']);

                    if ($payment_external_id[0] == $order->get_id()) {
                        $payment = $payment_data;
                    }
                }
            }

            if (isset($payment) && $payment['type'] == $this->retailcrm_settings[$order->get_payment_method()] && $order->is_paid()) {
                $payment = $this->sendPayment($order, true, $payment['externalId']);

                return $payment;
            }

            if (isset($payment) && $payment['type'] != $this->retailcrm_settings[$order->get_payment_method()]) {
                $response = $this->retailcrm->ordersPaymentDelete($payment['id']);

                if (!empty($response) && $response->isSuccessful()) {
                    $payment = $this->sendPayment($order);

                    return $payment;
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

            if ($update === true) {
                $this->orders->is_new = false;
            }

            $order_data = $this->orders->build($order)->get_data();

            if ($order->get_items('shipping')) {
                $shippings = $order->get_items( 'shipping' );
                $shipping = reset($shippings);
                $shipping_code = explode(':', $shipping['method_id']);

                if (isset($this->retailcrm_settings[$shipping['method_id']])) {
                    $shipping_method = $shipping['method_id'];
                } elseif (isset($this->retailcrm_settings[$shipping_code[0]])) {
                    $shipping_method = $shipping_code[0];
                } else {
                    $shipping_method = $shipping['method_id'] . ':' . $shipping['instance_id'];
                }

                $shipping_cost = $shipping['total'] + $shipping['total_tax'];

                if (!empty($shipping_method) && !empty($this->retailcrm_settings[$shipping_method])) {
                    $order_data['delivery']['code'] = $this->retailcrm_settings[$shipping_method];
                    $service = retailcrm_get_delivery_service($shipping['method_id'], $shipping['instance_id']);

                    if ($service) {
                        $order_data['delivery']['service'] = array(
                            'name' => $service['title'],
                            'code' => $service['instance_id'],
                            'active' => true
                        );
                    }
                }

                if (!empty($shipping_cost)) {
                    $order_data['delivery']['cost'] = $shipping_cost;
                }

                if ($shipping['total']) {
                    $order_data['delivery']['netCost'] = $shipping['total'];
                }
            }

            $order_items = array();
            $order_data['delivery']['address'] = $this->order_address
                ->setFallbackToBilling(true)
                ->setWCAddressType(WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_SHIPPING)
                ->build($order)
                ->get_data();

            /** @var WC_Order_Item_Product $item */
            foreach ($order->get_items() as $item) {
                $order_items[] = $this->order_item->build($item)->get_data();
                $this->order_item->reset_data();
            }

            $order_data['items'] = $order_items;

            if (!$update) {
                $this->order_payment->is_new = true;
                $order_data['payments'][] = $this->order_payment->build($order)->get_data();
            }

            $this->order = WC_Retailcrm_Plugin::clearArray($order_data);
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
            $this->order_payment->is_new = !$update;
            $payment = $this->order_payment->build($order, $externalId)->get_data();

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

            $crmOrder = array();
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
            $synchronizableUserData = self::isCorporateCrmOrder($crmOrder)
                ? $crmOrder['contact'] : $crmOrder['customer'];

            if (!$customerWasChanged) {
                if (self::isCorporateCrmOrder($crmOrder)) {
                    $currentCrmCompany = isset($crmOrder['company']) ? $crmOrder['company']['name'] : '';

                    if (!empty($currentCrmCompany) && $currentCrmCompany != $wcOrder->get_billing_company()) {
                        $customerWasChanged = true;
                    }
                }

                if (isset($synchronizableUserData['externalId'])
                    && $synchronizableUserData['externalId'] != $wcOrder->get_customer_id()
                ) {
                    $customerWasChanged = true;
                } elseif (isset($synchronizableUserData['email'])
                    && $synchronizableUserData['email'] != $wcOrder->get_billing_email()
                ) {
                    $customerWasChanged = true;
                }
            }

            return $customerWasChanged;
        }

        /**
         * Logs orders upload errors with prefix log message.
         * Array keys must be orders ID's in WooCommerce, values must be strings (error messages).
         *
         * @param array  $errors
         * @param string $prefix
         */
        public static function logOrdersUploadErrors($errors, $prefix = 'Errors while uploading these orders')
        {
            if (empty($errors)) {
                return;
            }

            WC_Retailcrm_Logger::add($prefix);

            foreach ($errors as $orderId => $error) {
                WC_Retailcrm_Logger::add(sprintf("[%d] => %s", $orderId, $error));
            }

            WC_Retailcrm_Logger::add('==================================');
        }
    }
endif;
