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
    class WC_Retailcrm_Customers
    {
        /**
         * Administrator role
         */
        const ADMIN_ROLE = 'administrator';

        /**
         * Every customer has this role
         */
        const CUSTOMER_ROLE = 'customer';

        /** @var bool | WC_Retailcrm_Proxy | \WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array */
        protected $retailcrm_settings = array();

        /** @var WC_Retailcrm_Customer_Address */
        protected $customer_address;

        /** @var array */
        private $customer = array();

        /** @var array */
        private $customerCorporate = array();

        /** @var array */
        private $customerCorporateCompany = array();

        /** @var array */
        private $customerCorporateAddress = array();

        /**
         * WC_Retailcrm_Customers constructor.
         *
         * @param bool | WC_Retailcrm_Proxy     $retailcrm
         * @param array                         $retailcrm_settings
         * @param WC_Retailcrm_Customer_Address $customer_address
         */
        public function __construct($retailcrm = false, $retailcrm_settings, $customer_address)
        {
            $this->retailcrm = $retailcrm;
            $this->retailcrm_settings = $retailcrm_settings;
            $this->customer_address = $customer_address;
        }

        /**
         * setCustomerAddress
         *
         * @param $address
         *
         * @return $this
         */
        public function setCustomerAddress($address)
        {
            if ($address instanceof WC_Retailcrm_Customer_Address) {
                $this->customer_address = $address;
            }

            return $this;
        }

        /**
         * Is corporate customers enabled in provided API
         *
         * @return bool
         */
        public function isCorporateEnabled()
        {
            if (!$this->retailcrm) {
                return false;
            }

            return $this->retailcrm->getCorporateEnabled();
        }

        /**
         * Upload customers to CRM
         *
         * @param array $ids
         *
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
                if (!$this->isCustomer($user)) {
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
         * @param \WC_Order|null    $order
         *
         * @return mixed
         * @throws \Exception
         */
        public function createCustomer($customer, $order = null)
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

            if ($this->isCustomer($customer)) {
                $this->processCustomer($customer, $order);
                $response = $this->retailcrm->customersCreate($this->customer);

                if ((!empty($response) && $response->isSuccessful()) && isset($response['id'])) {
                    return $response['id'];
                }
            }

            return null;
        }

        /**
         * Update customer in CRM
         *
         * @param $customer_id
         *
         * @return void|\WC_Customer
         * @throws \Exception
         */
        public function updateCustomer($customer_id)
        {
            if (!$this->retailcrm) {
                return;
            }

            $customer = $this->wcCustomerGet($customer_id);

            if ($this->isCustomer($customer)) {
                $this->processCustomer($customer);
                $this->retailcrm->customersEdit($this->customer);
            }

            return $customer;
        }

        /**
         * Update customer in CRM by ID
         *
         * @param int        $customer_id
         * @param int|string $crmCustomerId
         *
         * @return void|\WC_Customer
         * @throws \Exception
         */
        public function updateCustomerById($customer_id, $crmCustomerId)
        {
            if (!$this->retailcrm) {
                return;
            }

            $customer = $this->wcCustomerGet($customer_id);

            if ($this->isCustomer($customer)) {
                $this->processCustomer($customer);
                $this->customer['id'] = $crmCustomerId;
                $this->retailcrm->customersEdit($this->customer, 'id');
            }

            return $customer;
        }

        /**
         * Create corporate customer in CRM
         *
         * @param int               $crmCustomerId
         * @param int | WC_Customer $customer
         * @param \WC_Order         $order
         *
         * @return mixed
         * @throws \Exception
         */
        public function createCorporateCustomerForOrder($crmCustomerId, $customer, $order)
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

            if ($this->isCustomer($customer)) {
                $this->processCorporateCustomer($crmCustomerId, $customer, $order);
                $response = $this->retailcrm->customersCorporateCreate($this->customerCorporate);

                return $this->fillCorporateCustomer($response);
            }

            return null;
        }

        /**
         * Create new address in corporate customer (if needed)
         *
         * @param int $corporateId
         * @param \WC_Customer $customer
         * @param \WC_Order|null $order
         */
        public function fillCorporateAddress($corporateId, $customer, $order = null)
        {
            $found = false;
            $builder = new WC_Retailcrm_Customer_Corporate_Address();
            $newAddress = $builder
                ->setFallbackToShipping(true)
                ->setIsMain(false)
                ->setExplicitIsMain(false)
                ->setWCAddressType(WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_BILLING)
                ->build($customer, $order)
                ->get_data();
            $addresses = $this->retailcrm->customersCorporateAddresses(
                $corporateId,
                array(),
                null,
                100,
                'id'
            );

            if ($addresses && $addresses->isSuccessful() && $addresses->offsetExists('addresses')) {
                foreach ($addresses['addresses'] as $address) {
                    foreach ($newAddress as $field => $value) {
                        if (isset($address[$field]) && $address[$field] != $value) {
                            continue 2;
                        }
                    }

                    $found = true;

                    break;
                }
            } else {
                $found = true;
            }

            if (!$found) {
                $this->retailcrm->customersCorporateAddressesCreate(
                    $corporateId,
                    $newAddress,
                    'id',
                    $this->retailcrm->getSingleSiteForKey()
                );
            }
        }

        /**
         * Fills corporate customer with required data after customer was created or updated.
         * Create or update response after sending customer must be passed.
         *
         * @param \WC_Retailcrm_Response $response
         *
         * @return string|int|null
         */
        protected function fillCorporateCustomer($response)
        {
            if (!$response->isSuccessful() || $response->isSuccessful() && !$response->offsetExists('id')) {
                return null;
            }

            $customerId = $response['id'];
            $response = $this->retailcrm->customersCorporateAddressesCreate(
                $customerId,
                $this->customerCorporateAddress,
                'id',
                $this->retailcrm->getSingleSiteForKey()
            );

            if ($response->isSuccessful() && $response->offsetExists('id')) {
                $this->customerCorporateCompany['address'] = array(
                    'id' => $response['id'],
                );
                $this->retailcrm->customersCorporateCompaniesCreate(
                    $customerId,
                    $this->customerCorporateCompany,
                    'id',
                    $this->retailcrm->getSingleSiteForKey()
                );
            }

            return $customerId;
        }

        /**
         * Process customer
         *
         * @param WC_Customer   $customer
         * @param WC_Order|null $order
         *
         * @return void
         * @throws \Exception
         */
        protected function processCustomer($customer, $order = null)
        {
            $createdAt = $customer->get_date_created();
            $firstName = $customer->get_first_name();
            $lastName = $customer->get_last_name();
            $billingPhone = $customer->get_billing_phone();
            $email = $customer->get_billing_email();

            if (empty($firstName) && empty($lastName) && $order instanceof WC_Order) {
                $firstName = $order->get_billing_first_name();
                $lastName = $order->get_billing_last_name();

                if (empty($firstName) && empty($lastName)) {
                    $firstName = $order->get_shipping_first_name();
                    $lastName = $order->get_shipping_last_name();
                }

                if (empty($firstName)) {
                    $firstName = $customer->get_username();
                }

                if (empty($email)) {
                    $email = $order->get_billing_email();
                }

                if (empty($billingPhone)) {
                    $order->get_billing_phone();
                }
            }

            if (empty($createdAt)) {
                $createdAt = new WC_DateTime();
            }

            $data_customer = array(
                'createdAt' => $createdAt->date('Y-m-d H:i:s'),
                'firstName' => $firstName ? $firstName : $customer->get_username(),
                'lastName' => $lastName,
                'email' => $customer->get_billing_email(),
                'address' => $this->customer_address->build($customer, $order)->get_data()
            );

            if ($customer->get_id() > 0) {
                $data_customer['externalId'] = $customer->get_id();
            }

            if (!empty($billingPhone)) {
                $data_customer['phones'][] = array(
                    'number' => $customer->get_billing_phone()
                );
            }

            $this->customer = apply_filters(
                'retailcrm_process_customer',
                WC_Retailcrm_Plugin::clearArray($data_customer),
                $customer
            );
        }

        /**
         * Process corporate customer
         *
         * @param int         $crmCustomerId
         * @param WC_Customer $customer
         * @param \WC_Order   $order
         *
         * @return void
         */
        protected function processCorporateCustomer($crmCustomerId, $customer, $order)
        {
            $data_company = array(
                'isMain' => true,
                'name' => $order->get_billing_company()
            );

            $data_customer = array(
                'nickName' => $order->get_billing_company(),
                'customerContacts' => array(
                    array(
                        'isMain' => true,
                        'customer' => array(
                            'id' => $crmCustomerId
                        )
                    )
                )
            );

            $orderAddress = new WC_Retailcrm_Order_Address();
            $corpAddress = new WC_Retailcrm_Customer_Corporate_Address();

            $address = $orderAddress
                ->setWCAddressType(WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_BILLING)
                ->build($order)
                ->get_data();

            $shippingAddress = $corpAddress
                ->setWCAddressType(WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_BILLING)
                ->setFallbackToBilling(true)
                ->setIsMain(true)
                ->build($customer, $order)
                ->get_data();

            if (isset($address['text'])) {
                $data_company['contragent']['legalAddress'] = $address['text'];
            }

            $this->customerCorporate = apply_filters(
                'retailcrm_process_customer_corporate',
                WC_Retailcrm_Plugin::clearArray($data_customer),
                $customer
            );
            $this->customerCorporateAddress = apply_filters(
                'retailcrm_process_customer_corporate_address',
                WC_Retailcrm_Plugin::clearArray(array_merge(
                    $shippingAddress,
                    array('isMain' => true)
                )),
                $customer
            );
            $this->customerCorporateCompany = apply_filters(
                'retailcrm_process_customer_corporate_company',
                WC_Retailcrm_Plugin::clearArray($data_company),
                $customer
            );
        }

        /**
         * @param array $filter
         *
         * @return bool|array
         */
        private function searchCustomer($filter)
        {
            if (isset($filter['externalId'])) {
                $search = $this->retailcrm->customersGet($filter['externalId']);
            } elseif (isset($filter['email'])) {
                if (empty($filter['email']) && count($filter) == 1) {
                    return false;
                }

                $search = $this->retailcrm->customersList(array('email' => $filter['email']));
            }

            if (!empty($search) && $search->isSuccessful()) {
                $customer = false;

                if (isset($search['customers'])) {
                    if (empty($search['customers'])) {
                        return false;
                    }

                    if (isset($filter['email']) && count($filter) == 1) {
                        foreach ($search['customers'] as $finding) {
                            if (isset($finding['email']) && $finding['email'] == $filter['email']) {
                                $customer = $finding;
                            }
                        }
                    } else {
                        $dataCustomers = $search['customers'];
                        $customer = reset($dataCustomers);
                    }
                } else {
                    $customer = !empty($search['customer']) ? $search['customer'] : false;
                }

                return $customer;
            }

            return false;
        }

        /**
         * Returns customer data by externalId or by email, returns false in case of failure
         *
         * @param $customerExternalId
         * @param $customerEmailOrPhone
         *
         * @return array|bool
         */
        public function findCustomerEmailOrId($customerExternalId, $customerEmailOrPhone)
        {
            $customer = false;

            if (!empty($customerExternalId)) {
                $customer = $this->searchCustomer(array('externalId' => $customerExternalId));
            }

            if (!$customer && !empty($customerEmailOrPhone)) {
                $customer = $this->searchCustomer(array('email' => $customerEmailOrPhone));
            }

            return $customer;
        }

        /**
         * Search by provided filter, returns first found customer
         *
         * @param array $filter
         * @param bool  $returnGroup Return all customers for group filter instead of first
         *
         * @return bool|array
         */
        public function searchCorporateCustomer($filter, $returnGroup = false)
        {
            $search = $this->retailcrm->customersCorporateList($filter);

            if (!empty($search) && $search->isSuccessful()) {
                if (isset($search['customersCorporate'])) {
                    if (empty($search['customersCorporate'])) {
                        return false;
                    }

                    if ($returnGroup) {
                        return $search['customersCorporate'];
                    } else {
                        $dataCorporateCustomers = $search['customersCorporate'];
                        $customer = reset($dataCorporateCustomers);
                    }
                } else {
                    $customer = false;
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
            $new_customer = new WC_Customer();

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
         * @throws \Exception
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

        /**
         * Returns true if provided WP_User or WC_Customer should be uploaded to CRM
         *
         * @param \WC_Customer|\WP_User $user
         *
         * @return bool
         */
        public function isCustomer($user)
        {
            $retailcrmSettings = array();

            if (!empty($this->retailcrm_settings) && array_key_exists('client_roles', $this->retailcrm_settings)) {
                $retailcrmSettings = $this->retailcrm_settings['client_roles'];
            }

            if (empty($retailcrmSettings)) {
                $selectedRoles = array(self::CUSTOMER_ROLE, self::ADMIN_ROLE);
            } else {
                $selectedRoles = $retailcrmSettings;
            }

            if ($user instanceof WP_User) {
                $userRoles = $user->roles;
            } elseif ($user instanceof WC_Customer) {
                $wpUser = get_user_by('id', $user->get_id());
                $userRoles = ($wpUser) ? $wpUser->roles : array($user->get_role());
            } else {
                return false;
            }

            $result = array_filter($userRoles, function ($userRole) use ($selectedRoles) {
                return in_array($userRole, $selectedRoles);
            });

            return !empty($result);
        }
    }
endif;
