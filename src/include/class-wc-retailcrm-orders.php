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
         * @param bool $withCustomers
         * @param array $include
         * @return array $uploadOrders | null
         */
        public function ordersUpload($include = array(), $withCustomers = false)
        {
            if (!$this->retailcrm) {
                return null;
            }

            $isCorporateEnabled = $this->retailcrm->getCorporateEnabled();
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

            $orders_data = array();
            $orders_data_corps = array();

            foreach ($orders as $data_order) {
                $order = wc_get_order($data_order->ID);
                $this->processOrder($order);
                $customer = $order->get_user();
                $customers = array();

                if ($customer != false) {
                    $this->order['customer']['externalId'] = $customer->get('ID');

                    if ($withCustomers === true) {
                        $customers[] = $customer->get('ID');
                    }
                }

                if ($isCorporateEnabled
                    && WC_Retailcrm_Customers::customerPossiblyCorporate(new WC_Customer($customer->get('ID')))
                ) {
                    $corporate = $this->retailcrm->customersCorporateGet($customer->get('ID'));
                    time_nanosleep(0, 100000000);

                    if ($corporate instanceof WC_Retailcrm_Response
                        && $corporate->isSuccessful()
                        && $corporate->offsetExists('customerCorporate')
                        && isset($corporate['customerCorporate']['mainCustomerContact'])
                        && isset($corporate['customerCorporate']['mainCustomerContact']['customer'])
                        && isset($corporate['customerCorporate']['mainCustomerContact']['customer']['id'])
                        && isset($corporate['customerCorporate']['mainCustomerContact']['customer']['externalId'])
                    ) {
                        $this->order['contact'] = array(
                            'id' =>
                                $corporate['customerCorporate']['mainCustomerContact']['customer']['id']
                        );

                        $orders_data_corps[] = $this->order;
                    } else {
                        $orders_data[] = $this->order;
                    }
                } else {
                    $orders_data[] = $this->order;
                }
            }

            $corporateCustomers = array();

            if ($withCustomers === true && !empty($customers)) {
                if ($isCorporateEnabled) {
                    foreach ($customers as $key => $customer) {
                        if (WC_Retailcrm_Customers::customerPossiblyCorporate(new WC_Customer($customer))) {
                            $corporateCustomers[] = $customer;
                            unset($customers[$key]);
                        }
                    }

                    $uploadCustomers = array_chunk($customers, 50);

                    foreach ($uploadCustomers as $uploadCustomer) {
                        $this->customers->customersUpload($uploadCustomer);
                        time_nanosleep(0, 250000000);
                    }

                    foreach ($corporateCustomers as $corporateCustomer) {
                        $response = $uploader->createCorporateCustomer(new WC_Customer($corporateCustomer));

                        if ($response instanceof WC_Retailcrm_Customer_Corporate_Response) {
                            if ($response->getContactId() != 0) {
                                foreach ($orders_data as $key => $order) {
                                    if (isset($order['customer']['externalId'])
                                        && $order['customer']['externalId'] == $response->getExternalId()
                                    ) {
                                        $order['contact'] = array(
                                            'id' => $response->getContactId()
                                        );
                                        $orders_data_corps[] = $order;
                                        unset($orders_data[$key]);

                                        break;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $uploadCustomers = array_chunk($customers, 50);

                    foreach ($uploadCustomers as $uploadCustomer) {
                        $this->customers->customersUpload($uploadCustomer);
                        time_nanosleep(0, 250000000);
                    }
                }
            }

            $uploadOrders = array_chunk(WC_Retailcrm_Plugin::clearArray($orders_data), 50);
            $uploadOrdersCorps = array_chunk(WC_Retailcrm_Plugin::clearArray($orders_data_corps), 50);

            foreach ($uploadOrders as $uploadOrder) {
                $this->retailcrm->ordersUpload($uploadOrder);
                time_nanosleep(0, 250000000);
            }

            foreach ($uploadOrdersCorps as $uploadOrdersCorp) {
                $this->retailcrm->ordersUpload($uploadOrdersCorp);
                time_nanosleep(0, 250000000);
            }

            return $uploadOrders;
        }

        /**
         * Create order
         *
         * @param      $order_id
         *
         * @param bool $forceRegularCustomer
         *
         * @return mixed
         * @throws \Exception
         */
        public function orderCreate($order_id, $forceRegularCustomer = false)
        {
            if (!$this->retailcrm) {
                return null;
            }

            $isCorporateEnabled = $forceRegularCustomer ? false : $this->retailcrm->getCorporateEnabled();
            $wcOrder = wc_get_order($order_id);
            $this->processOrder($wcOrder);
            $wpUser = $wcOrder->get_user();

            if ($wpUser instanceof WP_User) {
                $wpUserId = (int)$wpUser->get('ID');
                $wooCustomer = new WC_Customer($wpUserId);

                if ($isCorporateEnabled && WC_Retailcrm_Customers::customerPossiblyCorporate($wooCustomer)) {
                    $foundRegularCustomer = $this->customers->searchCustomer(array(
                        'externalId' => $wpUserId
                    ));

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
                }
            } else {
                $wcCustomer = $this->customers->buildCustomerFromOrderData($wcOrder);

                if ($isCorporateEnabled && WC_Retailcrm_Customers::customerPossiblyCorporate($wcCustomer)) {
                    $foundRegularCustomer = $this->customers->searchCustomer(array(
                        'email' => $wcOrder->get_billing_email()
                    ));

                    // If regular customer was found - create order with it.
                    if (!empty($foundRegularCustomer)) {
                        return $this->orderCreate($order_id, true);
                    }

                    $foundCustomer = $this->customers->searchCorporateCustomer(array(
                        'email' => $wcOrder->get_billing_email()
                    ));

                    if (empty($foundCustomer)) {
                        $customerData = $this->customers->createCorporateCustomer($wcCustomer);

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
                }
            }

            $this->retailcrm->ordersCreate($this->order);

            return $wcOrder;
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

            $order_data['delivery']['address'] = $this->order_address->build($order)->get_data();
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
    }
endif;
