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

        protected $retailcrm;
        protected $retailcrm_settings;

        private $customer = array();

        /**
         * WC_Retailcrm_Customers constructor.
         *
         * @param $retailcrm
         */
        public function __construct($retailcrm = false)
        {
            $this->retailcrm_settings = get_option(WC_Retailcrm_Base::$option_key);
            $this->retailcrm = $retailcrm;
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
         * @param int $customer_id
         *
         * @return WC_Customer $customer
         */
        public function createCustomer($customer_id)
        {
            if (!$this->retailcrm) {
                return;
            }

            $customer = $this->wcCustomerGet($customer_id);

            if ($customer->get_role() == self::CUSTOMER_ROLE) {
                $this->processCustomer($customer);
                $this->retailcrm->customersCreate($this->customer);
            }

            return $customer;
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
                'externalId' => $customer->get_id(),
                'firstName' => $firstName ? $firstName : $customer->get_username(),
                'lastName' => $customer->get_last_name(),
                'email' => $customer->get_email(),
                'address' => array(
                    'index' => $customer->get_billing_postcode(),
                    'countryIso' => $customer->get_billing_country(),
                    'region' => $customer->get_billing_state(),
                    'city' => $customer->get_billing_city(),
                    'text' => $customer->get_billing_address_1() . ',' . $customer->get_billing_address_2()
                )
            );

            if ($customer->get_billing_phone()) {
                $data_customer['phones'][] = array(
                   'number' => $customer->get_billing_phone()
                );
            }

            $this->customer = apply_filters('retailcrm_process_customer', $data_customer, $customer);
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
