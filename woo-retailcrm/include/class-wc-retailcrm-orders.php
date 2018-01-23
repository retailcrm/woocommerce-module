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

        public function __construct()
        {
            $this->retailcrm_settings = get_option( 'woocommerce_integration-retailcrm_settings' );

            if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) {
                include_once( WP_PLUGIN_DIR . '/woo-retailcrm/include/api/class-wc-retailcrm-proxy.php' );
            }

            $this->retailcrm = new WC_Retailcrm_Proxy(
                $this->retailcrm_settings['api_url'],
                $this->retailcrm_settings['api_key'],
                $this->retailcrm_settings['api_version']
            );
        }

        /**
         * Upload orders to CRM
         */
        public function ordersUpload()
        {
            $orders = get_posts(array(
                'numberposts' => -1,
                'post_type' => wc_get_order_types('view-orders'),
                'post_status' => array_keys(wc_get_order_statuses())
            ));

            $orders_data = array();

            foreach ($orders as $data_order) {
                $order_data = $this->processOrder($data_order->ID);

                $order = new WC_Order($order_id);
                $customer = $order->get_user();

                if ($customer != false) {
                    $order_data['customer']['externalId'] = $customer->get('ID');
                }

                $orders_data[] = $order_data;
            }

            $uploadOrders = array_chunk($orders_data, 50);

            foreach ($uploadOrders as $uploadOrder) {
               $this->retailcrm->ordersUpload($uploadOrder);
            }
        }

        /**
         * Create order
         *
         * @param $order_id
         */
        public function orderCreate($order_id)
        {
            $order_data = $this->processOrder($order_id);

            $order = new WC_Order($order_id);
            $customer = $order->get_user();

            if ($customer != false) {
                $search = $this->retailcrm->customersGet($customer->get('ID'));

                if (!$search->isSuccessful()) {
                    $customer_data = array(
                        'externalId' => $customer->get('ID'),
                        'firstName' => $order_data['firstName'],
                        'lastName' => $order_data['lastName'],
                        'email' => $order_data['email']
                    );

                    $this->retailcrm->customersCreate($customer_data);

                } else {
                    $order_data['customer']['externalId'] = $search['customer']['externalId'];
                }
            }

            $res = $this->retailcrm->ordersCreate($order_data);
        }

        /**
         * Update shipping address
         *
         * @param $order_id, $address
         */
        public function orderUpdateShippingAddress($order_id, $address) {
            $address['externalId'] = $order_id;

            $response = $this->retailcrm->ordersEdit($address);
        }

        /**
         * Update order status
         *
         * @param $order_id
         */
        public function orderUpdateStatus($order_id) {
            $order = new WC_Order( $order_id );

            $order_data = array(
                'externalId' => $order_id,
                'status' => $this->retailcrm_settings[$order->get_status()]
            );

            $response = $this->retailcrm->ordersEdit($order_data);
        }

        /**
         * Update order payment type
         *
         * @param $order_id
         */
        public function orderUpdatePaymentType($order_id, $payment_method) {

            if ($this->retailcrm_settings['api_version'] != 'v5') {
                $order_data = array(
                    'externalId' => $order_id,
                    'paymentType' => $this->retailcrm_settings[$payment_method]
                );

                $this->retailcrm->ordersEdit($order_data);
            } else {
                $response = $this->retailcrm->ordersGet($order_id);

                if ($response->isSuccessful()) $order = $response['order'];

                foreach ($order['payments'] as $payment_data) {
                    if ($payment_data['externalId'] == $order_id) {
                        $payment = $payment_data;
                    }
                }

                $order = new WC_Order($order_id);

                if (isset($payment) && $payment['type'] != $this->retailcrm_settings[$order->payment_method]) {
                    $response = $this->retailcrm->ordersPaymentDelete($payment['id']);

                    if ($response->isSuccessful()) {
                        $this->createPayment($order, $order_id);
                    }
                }
            }
        }

        /**
         * Update order payment
         *
         * @param $order_id
         */
        public function orderUpdatePayment($order_id) {

            if ($this->retailcrm_settings['api_version'] != 'v5') {
                $order_data = array(
                    'externalId' => $order_id,
                    'paymentStatus' => 'paid'
                );

                $this->retailcrm->ordersEdit($order_data);
            } else {
                $payment = array(
                    'externalId' => $order_id,
                    'status' => 'paid'
                );

                $this->retailcrm->ordersPaymentsEdit($payment);
            }

        }

        /**
         * Update order items
         *
         * @param $order_id, $data
         */
        public function orderUpdateItems($order_id, $data) {
            $order = new WC_Order( $order_id );

            $order_data['externalId'] = $order_id;
            $shipping_method = end($data['shipping_method']);
            $shipping_cost = end($data['shipping_cost']);
            $products = $order->get_items();
            $items = array();

            foreach ($products as $order_item_id => $product) {
                if ($product['variation_id'] > 0) {
                    $offer_id = $product['variation_id'];
                } else {
                    $offer_id = $product['product_id'];
                }

                $_product = wc_get_product($offer_id);

                if ($this->retailcrm_settings['api_version'] != 'v3') {
                    $items[] = array(
                        'offer' => array('externalId' => $offer_id),
                        'productName' => $product['name'],
                        'initialPrice' => (float)$_product->get_price(),
                        'quantity' => $product['qty']
                    );
                } else {
                    $items[] = array(
                        'productId' => $offer_id,
                        'productName' => $product['name'],
                        'initialPrice' => (float)$_product->get_price(),
                        'quantity' => $product['qty']
                    );
                }
            }

            $order_data['items'] = $items;

            if (!empty($shipping_method) && !empty($this->retailcrm_settings[$shipping_method])) {
                $order_data['delivery']['code'] = $this->retailcrm_settings[$shipping_method];
            }

            if (!empty($shipping_cost)) {
                $shipping_cost = str_replace(',', '.', $shipping_cost);
                $order_data['delivery']['cost'] = $shipping_cost;
            }

            $response = $this->retailcrm->ordersEdit($order_data);
        }

        /**
         * get order data depending on woocommerce version
         *
         * @param int $order_id
         *
         * @return arr
         */
        public function getOrderData($order_id) {
            $order = new WC_Order( $order_id );
            $order_data_arr = array();

            if (version_compare(get_option('woocommerce_db_version'), '3.0', '<' )) {
                $order_data_arr['id']              = $order->id;
                $order_data_arr['date']            = $order->order_date;
                $order_data_arr['payment_method']  = $order->payment_method;
                $order_data_arr['discount_total']  = $order->data['discount_total'];
                $order_data_arr['discount_tax']    = $order->data['discount_tax'];
                $order_data_arr['customer_comment'] = $order->data['customerComment'];
            } else {
                $order_info = $order->get_data();

                $order_data_arr['id']              = $order_info['id'];
                $order_data_arr['payment_method']  = $order->get_payment_method();
                $order_data_arr['date']            = $order_info['date_created']->date('Y-m-d H:i:s');
                $order_data_arr['discount_total']  = $order_info['discount_total'];
                $order_data_arr['discount_tax']    = $order_info['discount_tax'];
                $order_data_arr['customer_comment'] = $order->get_customer_note();
            }

            return $order_data_arr;
        }

        /**
         * process to combine order data
         *
         * @param int $order_id
         * @param boolean $update
         * 
         * @return array $order_data
         */
        public function processOrder($order_id, $update = false)
        {
            if ( !$order_id ){
                return;
            }

            $order = new WC_Order( $order_id );
            $order_data_info = $this->getOrderData($order_id);
            $order_data = array();

            $order_data['externalId'] = $order_data_info['id'];
            $order_data['number'] = $order->get_order_number();
            $order_data['createdAt'] = trim($order_data_info['date']);
            $order_data['customerComment'] = $order_data_info['customer_comment'];

            if ( !empty( $order_data_info['payment_method'] ) && !empty($this->retailcrm_settings[$order_data_info['payment_method']]) && $this->retailcrm_settings['api_version'] != 'v5') {
                $order_data['paymentType'] = $this->retailcrm_settings[$order_data_info['payment_method']];
            }

            if ($order->get_items( 'shipping' )) {
                $shipping = end($order->get_items( 'shipping' ));
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
                $_product = wc_get_product($uid);
                $price = round($item['line_subtotal'] + $item['line_subtotal_tax'], 2);

                if ($_product) {
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
                }

                $order_items[] = $order_item;
            }

            $order_data['items'] = $order_items;

            if ($this->retailcrm_settings['api_version'] == 'v5') {
                $payment = array(
                    'amount' => $order->get_total(),
                    'externalId' => $order_id
                );

                $payment['order'] = array(
                    'externalId' => $order_id
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
                } else {
                    $this->editPayment($payment);
                }
            }

            return $order_data;
        }

        /**
         * Create payment in CRM
         * 
         * @param WC_Order $order
         * @param int $order_id
         * 
         * @return void
         */
        protected function createPayment($order, $order_id)
        {
            $payment = array(
                'amount' => $order->get_total(),
                'externalId' => $order_id
            );

            $payment['order'] = array(
                'externalId' => $order_id
            );

            if (!empty($order->payment_method) && !empty($this->retailcrm_settings[$order->payment_method])) {
                $payment['type'] = $this->retailcrm_settings[$order->payment_method];
            }

            if ($order->is_paid()) {
                $payment['status'] = 'paid';
            }

            if ($order->get_date_paid()) {
                $pay_date = $order->get_date_paid();
                $payment['paidAt'] = trim($pay_date->date('Y-m-d H:i:s'));
            }

            $this->retailcrm->ordersPaymentCreate($payment);
        }

        /**
         * Edit payment in CRM
         * 
         * @param array $payment
         * 
         * @return void
         */
        protected function editPayment($payment)
        {
            $this->retailcrm->ordersPaymentEdit($payment);
        }

        /**
         * Edit order in CRM
         * 
         * @param int $order_id
         * 
         * @return void
         */
        public function updateOrder($order_id)
        {
            $order = $this->processOrder($order_id, true);

            $response = $this->retailcrm->ordersEdit($order);

            $orderWc = new WC_Order($order_id);

            if ($response->isSuccessful()) {
                $this->orderUpdatePaymentType($order_id, $orderWc->payment_method);
            }
        }
    }
endif;