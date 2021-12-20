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
        public function __construct($retailcrm, $retailcrm_settings, $customer_address)
        {
            $this->retailcrm = $retailcrm;
            $this->retailcrm_settings = $retailcrm_settings;
            $this->customer_address = $customer_address;
        }

        /**
         * Return corporate customer
         *
         * @return array
         */
        public function getCorporateCustomer()
        {
            return $this->customerCorporate;
        }

        /**
         * Is corporate customers enabled in provided API
         *
         * @return bool
         */
        public function isCorporateEnabled()
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return false;
            }

            return $this->retailcrm->getCorporateEnabled();
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
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
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
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
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
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
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
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
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
         * Create new address in corporate customer (if needed).
         *
         * @param int $corporateId
         * @param \WC_Customer $customer
         * @param \WC_Order|null $order
         *
         * @return bool
         */
        public function fillCorporateAddress($corporateId, $customer, $order = null)
        {
            $found = false;
            $builder = new WC_Retailcrm_Customer_Corporate_Address();
            $newAddress = $builder
                ->setIsMain(false)
                ->build($customer, $order)
                ->get_data();

            $addresses = $this->retailcrm->customersCorporateAddresses(
                $corporateId,
                array(),
                null,
                100,
                'id'
            );

            if (!empty($addresses['addresses']) && $addresses->isSuccessful()) {
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

            return $found;
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
            if ((empty($response) || !$response->isSuccessful()) && !$response->offsetExists('id')) {
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
         * Process customer for upload
         *
         * @param WC_Customer $customer
         *
         * @return void
         */
        public function processCustomerForUpload($customer)
        {
            $this->processCustomer($customer);
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
            $email = strtolower($customer->get_billing_email());

            if (empty($firstName) && empty($lastName) && $order instanceof WC_Order) {
                $firstName = $order->get_billing_first_name();
                $lastName = $order->get_billing_last_name();

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

            // If a customer has placed an order as a guest, then $customer->get_date_created() == null,
            // then we take $order->get_date_created() order
            $createdAt = empty($createdAt) ? $order->get_date_created() : $createdAt;

            $data_customer = array(
                'createdAt' => $createdAt->date('Y-m-d H:i:s'),
                'firstName' => $firstName ? $firstName : $customer->get_username(),
                'lastName' => $lastName,
                'email' => $email,
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

            // If the client is corporate, set the value isContact.
            if ($this->isCorporateEnabled()) {
                if ($order !== null) {
                    $company = $order->get_billing_company();
                }

                if (empty($company)) {
                    $company = $customer->get_billing_company();
                }

                if (!empty($company)) {
                    $data_customer['isContact'] = true;
                }
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

            $corpAddress = new WC_Retailcrm_Customer_Corporate_Address();

            $billingAddress = $corpAddress
                ->setIsMain(true)
                ->build($customer, $order)
                ->get_data();

            if (!empty($billingAddress)) {
                $data_company['contragent']['legalAddress'] = implode(
                    ', ',
                    array(
                        $billingAddress['index'],
                        $billingAddress['city'],
                        $billingAddress['region'],
                        $billingAddress['text']
                    )
                );
            }

            $this->customerCorporateAddress = $billingAddress;

            $this->customerCorporate = apply_filters(
                'retailcrm_process_customer_corporate',
                WC_Retailcrm_Plugin::clearArray($data_customer),
                $customer
            );

            $this->customerCorporateCompany = apply_filters(
                'retailcrm_process_customer_corporate_company',
                WC_Retailcrm_Plugin::clearArray($data_company),
                $customer
            );
        }

        /**
         * @param array $filter Search customer by fields.
         *
         * @return bool|array
         */
        private function searchCustomer($filter)
        {
            if (isset($filter['externalId'])) {
                $search = $this->retailcrm->customersGet($filter['externalId']);
            } elseif (isset($filter['email'])) {
                if (empty($filter['email'])) {
                    return false;
                }

                // If customer not corporate, we need unset this field.
                if (empty($filter['isContact'])) {
                    unset($filter['isContact']);
                }

                $search = $this->retailcrm->customersList($filter);
            }

            if (!empty($search) && $search->isSuccessful()) {
                $customer = false;

                if (isset($search['customers'])) {
                    if (empty($search['customers'])) {
                        return false;
                    }

                    if (!empty($filter['email'])) {
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
         * @param string $customerExternalId   Customer externalId.
         * @param string $customerEmailOrPhone Customer email or phone.
         * @param bool $isContact              Customer is the contact person.
         *
         * @return array|bool
         */
        public function findCustomerEmailOrId($customerExternalId, $customerEmailOrPhone, $isContact)
        {
            $customer = false;

            if (!empty($customerExternalId)) {
                $customer = $this->searchCustomer(array('externalId' => $customerExternalId));
            }

            if (!$customer && !empty($customerEmailOrPhone)) {
                $customer = $this->searchCustomer(array('email' => $customerEmailOrPhone, 'isContact' => $isContact));
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
            $clientRoles = wp_roles()->get_names();
            $clientRoles = apply_filters('retailcrm_customer_roles', WC_Retailcrm_Plugin::clearArray($clientRoles));

            if ($user instanceof WP_User) {
                $userRole = !empty($user->roles[0]) ? $user->roles[0] : null;
            } elseif ($user instanceof WC_Customer) {
                $role = $user->get_role();
                $userRole = !empty($role) ? $role : null;
            } else {
                return false;
            }

            return array_key_exists($userRole, $clientRoles);
        }
    }
endif;
