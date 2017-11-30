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
                include_once( WP_PLUGIN_DIR . '/woo-retailcrm/include/api/class-wc-retailcrm-proxy.php' );
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

        public function createCustomer($customer_id)
        {
            $customer = new WC_Customer($customer_id);

            if ($customer->get_role() == 'customer'){

                $data_customer = $this->processCustomer($customer);

                $this->retailcrm->customersCreate($data_customer);
            }
        }

        public function updateCustomer($customer_id)
        {
            $customer = new WC_Customer($customer_id);

            if ($customer->get_role() == 'customer'){

                $data_customer = $this->processCustomer($customer);

                $this->retailcrm->customersEdit($data_customer);
            }
        }

        protected function processCustomer($customer)
        {
            $createdAt = $customer->get_date_created();
            $data_customer = array(
                'createdAt' => $createdAt->date('Y-m-d H:i:s '),
                'externalId' => $customer_id,
                'firstName' => !empty($customer->get_first_name()) ? $customer->get_first_name() : $customer->get_username(),
                'lastName' => $customer->get_last_name(),
                'email' => $customer->get_email(),
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

            return $data_customer;
        }
    }
endif;
