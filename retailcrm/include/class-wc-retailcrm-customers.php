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
        public function __construct()
        {
            $this->retailcrm_settings = get_option( 'woocommerce_integration-retailcrm_settings' );

            if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) {
                include_once( __DIR__ . '/api/class-wc-retailcrm-proxy.php' );
            }

            $this->retailcrm = new WC_Retailcrm_Proxy(
                $this->retailcrm_settings['api_url'],
                $this->retailcrm_settings['api_key'],
                $this->retailcrm_settings['api_version']
            );
        }
        
        public function customersUpload()
        {
            $users = get_users();
            $data_customers = array();

            foreach ($users as $user) {
                if ($user->roles[0] != 'customer') continue;

                $customer = new WC_Customer($user->ID);

                $data_customer = array(
                    'createdAt' => $user->data->user_registered,
                    'externalId' => $user->ID,
                    'firstName' => !empty($customer->get_first_name()) ? $customer->get_first_name() : $customer->get_username(),
                    'lastName' => $customer->get_last_name(),
                    'email' => $user->data->user_email,
                    'phones' => array(
                        array(
                            'number' => $customer->get_billing_phone()
                        )
                    ),
                    'address' => array(
                        'index' => $customer->get_billing_postcode(),
                        'countryIso' => $customer->get_billing_country(),
                        'region' => $customer->get_billing_state(),
                        'city' => $customer->get_billing_city(),
                        'text' => $customer->get_billing_address_1() . ',' . $customer->get_billing_address_2()
                    )
                );

                $data_customers[] = $data_customer;
            }

            $data = array_chunk($data_customers, 50);

            foreach ($data as $array_customers) {
                $this->retailcrm->customersUpload($array_customers);
            }
        }

        /**
         * crearte crm customer
         *
         * @param int $customer_id
         *
         * @return void
         */
        public function createCustomer($customer_id)
        {
            if (get_userdata($customer_id)->roles[0] == 'customer'){

                $data_customer = $this->processCustomer($customer_id);

                $res = $this->retailcrm->customersCreate($data_customer);
            }
        }

        /**
         * update crm customer
         *
         * @param int $customer_id
         *
         * @return void
         */
        public function updateCustomer($customer_id)
        {
            if (get_userdata($customer_id)->roles[0] == 'customer'){

                $data_customer = $this->processCustomer($customer_id);

                $res = $this->retailcrm->customersEdit($data_customer);
            }
        }

        /**
         * get customer data
         *
         * @param int $customer_id
         *
         * @return void
         */
        protected function processCustomer($customer_id)
        {
            $customer_data = get_userdata( $customer_id );
            $customer_meta = get_user_meta( $customer_id );

            $data_customer = array(
                'createdAt' => $customer_data->user_registered,
                'externalId' => $customer_data->ID,
                'firstName' => !empty($customer_meta['first_name'][0]) ? $customer_meta['first_name'][0] : $customer_meta['billing_first_name'][0],
                'lastName' => $customer_meta['last_name'][0],
                'email' => $customer_data->user_email,
                'phones' => array(
                    array(
                        'number' => $customer_meta['billing_phone'][0]
                    )
                ),
                'address' => array(
                    'index' => $customer_meta['billing_postcode'][0],
                    'countryIso' => $customer_meta['billing_country'][0],
                    'region' => $customer_meta['billing_state'][0],
                    'city' => $customer_meta['billing_city'][0],
                    'text' => $customer_meta['billing_address_1'][0] . ',' . $customer_meta['billing_address_2'][0]
                )
            );

            return $data_customer;
        }
    }
endif;
1