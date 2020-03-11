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
            $wpUser = $wcOrder->get_user();

            if ($wpUser instanceof WP_User) {
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
                if (!WC_Retailcrm_Customers::isCustomer($wpUser)) {
                    return $wcOrder;
                }

>>>>>>> corporate customers alternative logic
>>>>>>> corporate customers alternative logic
                $wpUserId = (int) $wpUser->get('ID');
                $this->fillOrderCreate($wpUserId, $wpUser->get('email'), $wcOrder);
            } else {
                $wcCustomer = $this->customers->buildCustomerFromOrderData($wcOrder);
                $this->fillOrderCreate(0, $wcCustomer->get_email(), $wcOrder);
            }

            try {
                $response = $this->retailcrm->ordersCreate($this->order);

                if ($response instanceof WC_Retailcrm_Response) {
                    if ($response->isSuccessful()) {
                        return $wcOrder;
                    } else {
                        if ($response->offsetExists('error')) {
                            return $response['error'];
                        } elseif ($response->offsetExists('errors') && is_array($response['errors'])) {
                            $errorMessage = '';

                            foreach ($response['errors'] as $error) {
                                $errorMessage .= $error . ' >';
                            }

                            if (strlen($errorMessage) > 2) {
                                return substr($errorMessage, 0, strlen($errorMessage) - 2);
                            }

                            return $errorMessage;
                        }
                    }
                }
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }

            return $wcOrder;
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

            if ($this->retailcrm->getCorporateEnabled() && static::isCorporateOrder($wcOrder)) {
                $crmCorporate = array();
                $crmCorporateList = $this->customers->searchCorporateCustomer(array(
                    'contactIds' => array($foundCustomerId)
                ), true);

                if (!empty($crmCorporateList)) {
                    foreach ($crmCorporateList as $corporate) {
                        if (!empty($corporate)
                            && !empty($corporate['mainCompany'])
                            && isset($corporate['mainCompany']['name'])
                            && $corporate['mainCompany']['name'] == $wcOrder->get_billing_company()
                        ) {
                            $crmCorporate = $corporate;

                            break;
                        }
                    }
                }

                if (empty($crmCorporate)) {
                    $crmCorporate = $this
                        ->customers
                        ->findCorporateCustomerByMainCompany($wcOrder->get_billing_company());
                }

                if (empty($crmCorporate) || (!empty($crmCorporate['mainCompany'])
                    && $crmCorporate['mainCompany']['name'] != $wcOrder->get_billing_company())
                ) {
                    $corporateId = $this->customers->createCorporateCustomerForOrder(
                        $foundCustomerId,
                        $wcCustomerId,
                        $wcOrder
                    );
                    $this->order['customer']['id'] = $corporateId;
                } else {
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
                    $this->customers->fillCorporateAddress(
                        $crmCorporate['id'],
                        new WC_Customer($wcCustomerId),
                        $wcOrder
                    );
>>>>>>> new address logic & fixes
>>>>>>> new address logic & fixes
                    $this->order['customer']['id'] = $crmCorporate['id'];

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
         */
        public function updateOrder($order_id)
        {
            if (!$this->retailcrm) {
                return null;
            }

            $order = wc_get_order($order_id);
            $this->processOrder($order, true);

            if ($this->retailcrm_settings['api_version'] == 'v4') {
                $this->order['paymentType'] = $this->retailcrm_settings[$order->get_payment_method()];
            }

            $response = $this->retailcrm->ordersEdit($this->order);

            if ((!empty($response) && $response->isSuccessful()) && $this->retailcrm_settings['api_version'] == 'v5') {
                $this->payment = $this->orderUpdatePaymentType($order);
            }

            return $order;
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

            $response = $this->retailcrm->ordersGet($order->get_id());

            if (!empty($response) && $response->isSuccessful()) {
                $retailcrmOrder = $response['order'];

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
         * @param boolean $update
         *
         * @return void
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

            $order_data['delivery']['address'] = $this->order_address
                ->setFallbackToBilling(true)
                ->setWCAddressType(WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_SHIPPING)
                ->build($order)
                ->get_data();
            $order_items = array();

            /** @var WC_Order_Item_Product $item */
            foreach ($order->get_items() as $item) {
                $order_items[] = $this->order_item->build($item)->get_data();
                $this->order_item->reset_data();
            }

            $order_data['items'] = $order_items;

            if ($this->retailcrm_settings['api_version'] == 'v5' && !$update) {
                $this->order_payment->is_new = true;
                $order_data['payments'][] = $this->order_payment->build($order)->get_data();
            }

            $this->order = apply_filters('retailcrm_process_order', WC_Retailcrm_Plugin::clearArray($order_data), $order);
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
            return !empty($order->get_billing_company());
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

            $handle = 'retailcrm';
            $logger = new WC_Logger();
            $logger->add($handle, $prefix);

            foreach ($errors as $orderId => $error) {
                $logger->add($handle, sprintf("[%d] => %s", $orderId, $error));
            }

            $logger->add($handle, '==================================');
        }
    }
endif;
