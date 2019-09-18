<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Customers
 * @category Integration
 * @author   RetailCRM
 */

if (!class_exists('WC_Retailcrm_Customers')) :

    /**
     * Class WC_Retailcrm_Customers
     */
    class WC_Retailcrm_Customers {

        const CUSTOMER_ROLE = 'customer';

        /** @var bool | WC_Retailcrm_Proxy */
        protected $retailcrm;

        /** @var array */
        protected $retailcrm_settings = array();

        /** @var WC_Retailcrm_Customer_Address */
        protected $customer_address;

        /** @var array */
        private $customer = array();

        /**
         * WC_Retailcrm_Customers constructor.
         *
         * @param bool | WC_Retailcrm_Proxy $retailcrm
         * @param array $retailcrm_settings
         * @param WC_Retailcrm_Customer_Address $customer_address
         */
        public function __construct($retailcrm = false, $retailcrm_settings, $customer_address)
        {
            $this->retailcrm = $retailcrm;
            $this->retailcrm_settings = $retailcrm_settings;
            $this->customer_address = $customer_address;
        }

        /**
         * Upload customers to CRM
         *
         * @param array $ids
         * @return array mixed
         */
        public function customersUpload($ids = array())
        {
            if (!$this->retailcrm) {
                return null;
            }

            $users = get_users(array('include' => $ids));
            $data_customers = array();

            foreach ($users as $user) {
                if (!\in_array(self::CUSTOMER_ROLE, $user->roles)) {
                    continue;
                }

                $customer = $this->wcCustomerGet($user->ID);
                $this->processCustomer($customer);
                $data_customers[] = $this->customer;
            }

            $data = \array_chunk($data_customers, 50);

            foreach ($data as $array_customers) {
                $this->retailcrm->customersUpload($array_customers);
                time_nanosleep(0, 250000000);
            }

            return $data;
        }

        /**
         * Create customer in CRM
         *
         * @param int | WC_Customer $customer
         *
         * @return mixed
         */
        public function createCustomer($customer)
        {
            if (!$this->retailcrm) {
                return null;
            }

            if (is_int($customer)) {
                $customer = $this->wcCustomerGet($customer);
            }

            if (!$customer instanceof WC_Customer) {
                return null;
            }

            if ($customer->get_role() == self::CUSTOMER_ROLE) {
                $this->processCustomer($customer);
                $response = $this->retailcrm->customersCreate($this->customer);

                if ($response->isSuccessful() && isset($response['id'])) {
                    return $response['id'];
                }
            }

            return null;
        }

        /**
         * Edit customer in CRM
         *
         * @param int $customer_id
         *
         * @return WC_Customer $customer
         */
        public function updateCustomer($customer_id)
        {
            if (!$this->retailcrm) {
                return;
            }

            $customer = $this->wcCustomerGet($customer_id);

            if ($customer->get_role() == self::CUSTOMER_ROLE){
                $this->processCustomer($customer);
                $this->retailcrm->customersEdit($this->customer);
            }

            return $customer;
        }

        /**
         * Process customer
         *
         * @param WC_Customer $customer
         *
         * @return void
         */
        protected function processCustomer($customer)
        {
            $createdAt = $customer->get_date_created();
            $firstName = $customer->get_first_name();
            $data_customer = array(
                'createdAt' => $createdAt->date('Y-m-d H:i:s'),
                'firstName' => $firstName ? $firstName : $customer->get_username(),
                'lastName' => $customer->get_last_name(),
                'email' => $customer->get_email(),
                'address' => $this->customer_address->build($customer)->get_data()
            );

            if ($customer->get_id() > 0) {
                $data_customer['externalId'] = $customer->get_id();
            }

            if ($customer->get_billing_phone()) {
                $data_customer['phones'][] = array(
                   'number' => $customer->get_billing_phone()
                );
            }

            $this->customer = apply_filters('retailcrm_process_customer', $data_customer, $customer);
        }

        /**
         * @param array $filter
         *
         * @return bool|array
         */
        public function searchCustomer($filter)
        {
            if (isset($filter['externalId'])) {
                $search = $this->retailcrm->customersGet($filter['externalId']);
            } elseif (isset($filter['email'])) {
                $search = $this->retailcrm->customersList(array('email' => $filter['email']));
            }

            if ($search->isSuccessful()) {
                if (isset($search['customers'])) {
                    if (empty($search['customers'])) {
                        return false;
                    }

                    $customer = reset($search['customers']);
                } else {
                    $customer = $search['customer'];
                }

                return $customer;
            }

            return false;
        }

        /**
         * @param WC_Order $order
         *
         * @return WC_Customer
         * @throws Exception
         */
        public function buildCustomerFromOrderData($order)
        {
            $new_customer = new WC_Customer;

            foreach ($order->get_address('billing') as $prop => $value) {
                $new_customer->{'set_billing_' . $prop}($value);
            }

            $new_customer->set_first_name($order->get_billing_first_name());
            $new_customer->set_last_name($order->get_billing_last_name());
            $new_customer->set_email($order->get_billing_email());
            $new_customer->set_date_created($order->get_date_created());

            return $new_customer;
        }

        /**
         * @param int $customer_id
         *
         * @return WC_Customer
         */
        public function wcCustomerGet($customer_id)
        {
            return new WC_Customer($customer_id);
        }

        /**
         * @return array
         */
        public function getCustomer()
        {
            return $this->customer;
        }
    }
endif;
