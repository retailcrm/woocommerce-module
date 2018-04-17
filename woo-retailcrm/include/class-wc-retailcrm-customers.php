<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Customers
 * @category Integration
 * @author   RetailCRM
 */

if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) :

    /**
     * Class WC_Retailcrm_Customers
     */
    class WC_Retailcrm_Customers
    {
    	const CUSTOMER_ROLE = 'customer';

        protected $retailcrm;
        protected $retailcrm_settings;

        /**
         * WC_Retailcrm_Customers constructor.
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
         * @return void
         */
        public function customersUpload()
        {
        	if (!$this->retailcrm) {
        		return;
	        }

            $users = get_users();
            $data_customers = array();

            foreach ($users as $user) {
                if (!in_array(self::CUSTOMER_ROLE, $user->roles)) {
                    continue;
                }

                $customer = new WC_Customer($user->ID);
                $firstName = $customer->get_first_name();
                $data_customer = array(
                    'createdAt' => $user->data->user_registered,
                    'externalId' => $user->ID,
                    'firstName' => $firstName ? $firstName : $customer->get_username(),
                    'lastName' => $customer->get_last_name(),
                    'email' => $user->data->user_email,
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

                $data_customers[] = $data_customer;
            }

            $data = array_chunk($data_customers, 50);

            foreach ($data as $array_customers) {
                $this->retailcrm->customersUpload($array_customers);
            }
        }

        /**
         * Create customer in CRM
         * 
         * @param int $customer_id
         * 
         * @return void
         */
        public function createCustomer($customer_id)
        {
	        if (!$this->retailcrm) {
		        return;
	        }

            $customer = new WC_Customer($customer_id);

            if ($customer->get_role() == self::CUSTOMER_ROLE) {
                $data_customer = $this->processCustomer($customer);

                $this->retailcrm->customersCreate($data_customer);
            }
        }

        /**
         * Edit customer in CRM
         * 
         * @param int $customer_id
         * 
         * @return void
         */
        public function updateCustomer($customer_id)
        {
	        if (!$this->retailcrm) {
		        return;
	        }

            $customer = new WC_Customer($customer_id);

            if ($customer->get_role() == self::CUSTOMER_ROLE){
                $data_customer = $this->processCustomer($customer);

                $this->retailcrm->customersEdit($data_customer);
            }
        }

        /**
         * Process customer
         * 
         * @param WC_Customer $customer
         * 
         * @return array $data_customer
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

            return apply_filters('retailcrm_process_customer', $data_customer);
        }
    }
endif;
