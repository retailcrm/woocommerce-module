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
         * @return array $uploadOrders | null
         */
        public function ordersUpload()
        {
            if (!$this->retailcrm) {
                return null;
            }

            $orders = get_posts(array(
                'numberposts' => -1,
                'post_type' => wc_get_order_types('view-orders'),
                'post_status' => array_keys(wc_get_order_statuses())
            ));

            $orders_data = array();

            foreach ($orders as $data_order) {
                $order = wc_get_order($data_order->ID);
                $this->processOrder($order);
                $customer = $order->get_user();

                if ($customer != false) {
                    $this->order['customer']['externalId'] = $customer->get('ID');
                }

                $orders_data[] = $this->order;
            }

            $uploadOrders = array_chunk($orders_data, 50);

            foreach ($uploadOrders as $uploadOrder) {
                $this->retailcrm->ordersUpload($uploadOrder);
            }

            return $uploadOrders;
        }

        /**
         * Create order
         *
         * @param $order_id
         *
         * @return WC_Order $order | null
         */
        public function orderCreate($order_id)
        {
            if (!$this->retailcrm) {
                return null;
            }

            $order = wc_get_order($order_id);
            $this->processOrder($order);
            $customer = $order->get_user();

            if ($customer != false) {
                $search = $this->retailcrm->customersGet($customer->get('ID'));

                if (!$search->isSuccessful()) {
                    $customer_data = array(
                        'externalId' => $customer->get('ID'),
                        'firstName' => $this->order['firstName'],
                        'lastName' => $this->order['lastName'],
                        'email' => $this->order['email']
                    );

                    $this->retailcrm->customersCreate($customer_data);
                } else {
                    $this->order['customer']['externalId'] = $search['customer']['externalId'];
                }
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
                    if ($payment_data['externalId'] == $order->get_id()) {
                        $payment = $payment_data;
                    }
                }
            }

            if (isset($payment) && $payment['type'] != $this->retailcrm_settings[$order->get_payment_method()]) {
                $response = $this->retailcrm->ordersPaymentDelete($payment['id']);

                if ($response->isSuccessful()) {
                    $payment = $this->createPayment($order);

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
        protected function getOrderData($order) {
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
                } else {
                    $shipping_method = $shipping_code[0];
                }

                $shipping_cost = $shipping['cost'];

                if (!empty($shipping_method) && !empty($this->retailcrm_settings[$shipping_method])) {
                    $order_data['delivery']['code'] = $this->retailcrm_settings[$shipping_method];
                }

                if (!empty($shipping_cost)) {
                    $order_data['delivery']['cost'] = $shipping_cost;
                }
            }

            if ($this->retailcrm_settings['api_version'] != 'v5' && $order->is_paid()) {
                $order_data['paymentStatus'] = 'paid';
            }

            $status = $order->get_status();
            $order_data['status'] = $this->retailcrm_settings[$status];

            $user_data_billing = $order->get_address('billing');

            if (!empty($user_data_billing)) {

                if (!empty($user_data_billing['phone'])) $order_data['phone'] = $user_data_billing['phone'];
                if (!empty($user_data_billing['email'])) $order_data['email'] = $user_data_billing['email'];
                if (!empty($user_data_billing['first_name'])) $order_data['firstName'] = $user_data_billing['first_name'];
                if (!empty($user_data_billing['last_name'])) $order_data['lastName'] = $user_data_billing['last_name'];
                if (!empty($user_data_billing['postcode'])) $order_data['delivery']['address']['index'] = $user_data_billing['postcode'];
                if (!empty($user_data_billing['city'])) $order_data['delivery']['address']['city'] = $user_data_billing['city'];
                if (!empty($user_data_billing['state'])) $order_data['delivery']['address']['region'] = $user_data_billing['state'];
                if (!empty($user_data_billing['country'])) $order_data['countryIso'] = $user_data_billing['country'];
            }

            $user_data = $order->get_address('shipping');

            if (!empty($user_data)) {

                if (!empty($user_data['phone'])) $order_data['phone'] = $user_data['phone'];
                if (!empty($user_data['email'])) $order_data['email'] = $user_data['email'];
                if (!empty($user_data['first_name'])) $order_data['firstName'] = $user_data['first_name'];
                if (!empty($user_data['last_name'])) $order_data['lastName'] = $user_data['last_name'];
                if (!empty($user_data['postcode'])) $order_data['delivery']['address']['index'] = $user_data['postcode'];
                if (!empty($user_data['city'])) $order_data['delivery']['address']['city'] = $user_data['city'];
                if (!empty($user_data['state'])) $order_data['delivery']['address']['region'] = $user_data['state'];
                if (!empty($user_data['country'])) $order_data['countryIso'] = $user_data['country'];
            }

            $order_data['delivery']['address']['text'] = sprintf(
                "%s %s %s %s %s",
                !empty($user_data_billing['postcode']) ? $user_data_billing['postcode'] : $user_data['postcode'],
                !empty($user_data_billing['state']) ? $user_data_billing['state'] : $user_data['state'],
                !empty($user_data_billing['city']) ? $user_data_billing['city'] : $user_data['city'],
                !empty($user_data_billing['address_1']) ? $user_data_billing['address_1'] : $user_data['address_1'],
                !empty($user_data_billing['address_2']) ? $user_data_billing['address_2'] : $user_data['address_2']
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
                    'externalId' => $order->get_id()
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

            $this->order = apply_filters('retailcrm_process_order', $order_data);
        }

        /**
         * Create payment in CRM
         * 
         * @param WC_Order $order
         * @param int $order_id
         * 
         * @return array $payment
         */
        protected function createPayment($order)
        {
            $payment = array(
                'amount' => $order->get_total(),
                'externalId' => $order->get_id()
            );

            $payment['order'] = array(
                'externalId' => $order->get_id()
            );

            if (isset($this->retailcrm_settings[$order->get_payment_method()])) {
                $payment['type'] = $this->retailcrm_settings[$order->get_payment_method()];
            }

            if ($order->is_paid()) {
                $payment['status'] = 'paid';
            }

            if ($order->get_date_paid()) {
                $pay_date = $order->get_date_paid();
                $payment['paidAt'] = trim($pay_date->date('Y-m-d H:i:s'));
            }

            $this->retailcrm->ordersPaymentCreate($payment);

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
