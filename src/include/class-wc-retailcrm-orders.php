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
        protected $retailcrm_settings;
        protected $retailcrm;

        private $order = array();
        private $payment = array();

        public function __construct($retailcrm = false)
        {
            $this->retailcrm_settings = get_option(WC_Retailcrm_Base::$option_key);
            $this->retailcrm = $retailcrm;
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

            $orders = get_posts(array(
                'numberposts' => -1,
                'post_type' => wc_get_order_types('view-orders'),
                'post_status' => array_keys(wc_get_order_statuses()),
                'include' => $include
            ));

            $orders_data = array();

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

                $orders_data[] = $this->order;
            }

            if ($withCustomers === true && !empty($customers)) {
                if (!class_exists('WC_Retailcrm_Customers')) {
                    include_once(WC_Retailcrm_Base::checkCustomFile('customers'));
                }

                $retailcrmCustomer = new WC_Retailcrm_Customers($this->retailcrm);
                $retailcrmCustomer->customersUpload($customers);
            }

            $uploadOrders = array_chunk($orders_data, 50);

            foreach ($uploadOrders as $uploadOrder) {
                $this->retailcrm->ordersUpload($uploadOrder);
                time_nanosleep(0, 250000000);
            }

            return $uploadOrders;
        }

        /**
         * Create order
         *
         * @param $order_id
         *
         * @return mixed
         */
        public function orderCreate($order_id)
        {
            if (!$this->retailcrm) {
                return null;
            }

            $order = wc_get_order($order_id);
            $this->processOrder($order);
            $customer = $order->get_user();

            if (!class_exists('WC_Retailcrm_Customers')) {
                include_once(WC_Retailcrm_Base::checkCustomFile('customers'));
            }

            $retailcrm_customers = new WC_Retailcrm_Customers($this->retailcrm);

            if ($customer != false) {
                $search = $retailcrm_customers->searchCustomer(array('id' => $customer->get('ID')));

                if (!$search) {
                    $retailcrm_customers->createCustomer($customer);
                } else {
                    $this->order['customer']['externalId'] = $search['externalId'];
                }
            } else {
                $search = $retailcrm_customers->searchCustomer(array('email' => $order->get_billing_email()));

                if (!$search) {
                    $new_customer = $retailcrm_customers->buildCustomerFromOrderData($order);
                    $id = $retailcrm_customers->createCustomer($new_customer);

                    if ($id !== null) {
                        $this->order['customer']['id'] = $id;
                    }
                } else {
                    $this->order['customer']['externalId'] = $search['externalId'];
                }

                unset($new_customer);
            }

            $this->retailcrm->ordersCreate($this->order);

            return $order;
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

            if ($response->isSuccessful() && $this->retailcrm_settings['api_version'] == 'v5') {
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

            if ($response->isSuccessful()) {
                $retailcrmOrder = $response['order'];

                foreach ($retailcrmOrder['payments'] as $payment_data) {
                    $payment_external_id = explode('-', $payment_data['externalId']);

                    if ($payment_external_id[0] == $order->get_id()) {
                        $payment = $payment_data;
                    }
                }
            }

            if (isset($payment) && $payment['type'] == $this->retailcrm_settings[$order->get_payment_method()] && $order->is_paid()) {
                $payment = $this->sendPayment($order, true);

                return $payment;
            }

            if (isset($payment) && $payment['type'] != $this->retailcrm_settings[$order->get_payment_method()]) {
                $response = $this->retailcrm->ordersPaymentDelete($payment['id']);

                if ($response->isSuccessful()) {
                    $payment = $this->sendPayment($order);

                    return $payment;
                }
            }

            return null;
        }

        /**
         * Get order data
         *
         * @param WC_Order $order
         *
         * @return array $order_data_arr
         */
        protected function getOrderData($order)
        {
            $order_data_arr = array();
            $order_info = $order->get_data();

            $order_data_arr['id']              = $order_info['id'];
            $order_data_arr['payment_method']  = $order->get_payment_method();
            $order_data_arr['date']            = $order_info['date_created']->date('Y-m-d H:i:s');
            $order_data_arr['discount_total']  = $order_info['discount_total'];
            $order_data_arr['discount_tax']    = $order_info['discount_tax'];
            $order_data_arr['customer_comment'] = $order->get_customer_note();

            return $order_data_arr;
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

            $order_data_info = $this->getOrderData($order);
            $order_data = array();

            $order_data['externalId'] = $order_data_info['id'];
            $order_data['number'] = $order->get_order_number();
            $order_data['createdAt'] = trim($order_data_info['date']);
            $order_data['customerComment'] = $order_data_info['customer_comment'];

            if (!empty($order_data_info['payment_method'])
                && !empty($this->retailcrm_settings[$order_data_info['payment_method']])
                && $this->retailcrm_settings['api_version'] != 'v5'
            ) {
                $order_data['paymentType'] = $this->retailcrm_settings[$order_data_info['payment_method']];
            }

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

            if ($this->retailcrm_settings['api_version'] != 'v5' && $order->is_paid()) {
                $order_data['paymentStatus'] = 'paid';
            }

            $status = $order->get_status();
            $order_data['status'] = $this->retailcrm_settings[$status];

            $user_data_billing = $order->get_address('billing');

            if (!empty($user_data_billing)) {
                $order_data['phone'] = $user_data_billing['phone'];
                $order_data['email'] = $user_data_billing['email'];
            }

            $user_data_shipping = $order->get_address('shipping');

            if (!empty($user_data_shipping)) {
                $order_data['firstName'] = $user_data_shipping['first_name'];
                $order_data['lastName'] = $user_data_shipping['last_name'];
                $order_data['delivery']['address']['index'] = $user_data_shipping['postcode'];
                $order_data['delivery']['address']['city'] = $user_data_shipping['city'];
                $order_data['delivery']['address']['region'] = $user_data_shipping['state'];
                $order_data['countryIso'] = $user_data_shipping['country'];
            }

            $order_data['delivery']['address']['text'] = sprintf(
                "%s %s %s %s %s",
                $user_data_shipping['postcode'],
                $user_data_shipping['state'],
                $user_data_shipping['city'],
                $user_data_shipping['address_1'],
                $user_data_shipping['address_2']
            );

            $order_items = array();

            foreach ($order->get_items() as $item) {
                $uid = ($item['variation_id'] > 0) ? $item['variation_id'] : $item['product_id'] ;
                $price = round(($item['line_subtotal'] / $item->get_quantity()) + ($item['line_subtotal_tax'] / $item->get_quantity()), 2);

                $product_price = $item->get_total() ? $item->get_total() / $item->get_quantity() : 0;
                $product_tax  = $item->get_total_tax() ? $item->get_total_tax() / $item->get_quantity() : 0;
                $price_item = $product_price + $product_tax;
                $discount_price = $price - $price_item;

                $order_item = array(
                    'offer' => array('externalId' => $uid),
                    'productName' => $item['name'],
                    'initialPrice' => (float)$price,
                    'quantity' => $item['qty'],
                );

                if ($this->retailcrm_settings['api_version'] == 'v5' && round($discount_price, 2)) {
                    $order_item['discountManualAmount'] = round($discount_price, 2);
                } elseif ($this->retailcrm_settings['api_version'] == 'v4' && round($discount_price, 2)) {
                    $order_item['discount'] = round($discount_price, 2);
                }

                $order_items[] = $order_item;
            }

            $order_data['items'] = $order_items;

            if ($this->retailcrm_settings['api_version'] == 'v5') {
                $payment = array(
                    'amount' => $order->get_total(),
                    'externalId' => $order->get_id() . uniqid('-')
                );

                $payment['order'] = array(
                    'externalId' => $order->get_id()
                );

                if (!empty($order_data_info['payment_method']) && !empty($this->retailcrm_settings[$order_data_info['payment_method']])) {
                    $payment['type'] = $this->retailcrm_settings[$order_data_info['payment_method']];
                }

                if ($order->is_paid()) {
                    $payment['status'] = 'paid';
                }

                if ($order->get_date_paid()) {
                    $pay_date = $order->get_date_paid();
                    $payment['paidAt'] = trim($pay_date->date('Y-m-d H:i:s'));
                }

                if (!$update) {
                    $order_data['payments'][] = $payment;
                }
            }

            $this->order = apply_filters('retailcrm_process_order', $order_data, $order);
        }

        /**
         * Send payment in CRM
         *
         * @param WC_Order $order
         * @param boolean $update
         *
         * @return array $payment
         */
        protected function sendPayment($order, $update = false)
        {
            $payment = array(
                'amount' => $order->get_total(),
                'externalId' => $order->get_id() . uniqid('-')
            );

            $payment['order'] = array(
                'externalId' => $order->get_id()
            );

            if ($order->is_paid()) {
                $payment['status'] = 'paid';
            }

            if ($order->get_date_paid()) {
                $pay_date = $order->get_date_paid();
                $payment['paidAt'] = trim($pay_date->date('Y-m-d H:i:s'));
            }

            if ($update === false) {
                if (isset($this->retailcrm_settings[$order->get_payment_method()])) {
                    $payment['type'] = $this->retailcrm_settings[$order->get_payment_method()];
                }

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
