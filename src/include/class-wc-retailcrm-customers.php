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
        private $customerCorporateContact = array();

        /** @var array */
        private $customerCorporateCompany = array();

        /** @var array */
        private $customerCorporateAddress = array();

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
         * Returns true if corporate customers are enabled and accessible
         *
         * @param WC_Retailcrm_Client_V5|\WC_Retailcrm_Proxy $apiClient
         *
         * @return bool
         */
        public static function isCorporateEnabledInApi($apiClient)
        {
            if (is_object($apiClient)) {
                $requiredMethods = array(
                    "/api/customers-corporate",
                    "/api/customers-corporate/create",
                    "/api/customers-corporate/fix-external-ids",
                    "/api/customers-corporate/notes",
                    "/api/customers-corporate/notes/create",
                    "/api/customers-corporate/notes/{id}/delete",
                    "/api/customers-corporate/history",
                    "/api/customers-corporate/upload",
                    "/api/customers-corporate/{externalId}",
                    "/api/customers-corporate/{externalId}/edit"
                );

                $credentials = $apiClient->credentials();

                if ($credentials && isset($credentials['credentials'])) {
                    $existingMethods = array_filter(
                        $credentials['credentials'],
                        function ($val) use ($requiredMethods) {
                            return in_array($val, $requiredMethods);
                        }
                    );

                    return count($requiredMethods) == count($existingMethods);
                }
            }

            return false;
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

            return static::isCorporateEnabledInApi($this->retailcrm);
        }

        /**
         * Returns true if provided customer has company name in billing address.
         * Note: customer can have company name in address which was added after synchronization.
         * In that case customer will not be corporate, but this method will return true.
         *
         * @param \WC_Customer $customer
         *
         * @return bool
         */
        public static function customerPossiblyCorporate($customer)
        {
            if (!($customer instanceof WC_Customer)) {
                return false;
            }

            return !empty($customer->get_billing_company());
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
            $corporateEnabled = $this->isCorporateEnabled();
            $data_customers = array();
            $data_corporate = array();

            foreach ($users as $user) {
                if (!\in_array(self::CUSTOMER_ROLE, $user->roles)) {
                    continue;
                }

                $customer = $this->wcCustomerGet($user->ID);
                if ($corporateEnabled && static::customerPossiblyCorporate($customer)) {
                    $data_corporate[] = $customer;
                } else {
                    $this->processCustomer($customer);
                    $data_customers[] = $this->customer;
                }

                $data_customers[] = $this->customer;
            }

            $data = \array_chunk($data_customers, 50);

            foreach ($data as $array_customers) {
                $this->retailcrm->customersUpload($array_customers);
                time_nanosleep(0, 250000000);
            }

            foreach ($data_corporate as $corporateCustomer) {
                $this->createCorporateCustomer($corporateCustomer);
                time_nanosleep(0, 50000000);
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
            if ($this->isCorporateEnabled() && static::customerPossiblyCorporate($customer)) {
                return $this->createCorporateCustomer($customer);
            } else {
                return $this->createRegularCustomer($customer);
            }
        }

        /**
         * Update customer in CRM
         *
         * @param $customer
         *
         * @return void|\WC_Customer
         */
        public function updateCustomer($customer)
        {
            if ($this->isCorporateEnabled() && static::customerPossiblyCorporate($customer)) {
                return $this->updateCorporateCustomer($customer);
            } else {
                return $this->updateRegularCustomer($customer);
            }
        }

        /**
         * Create regular customer in CRM
         *
         * @param int | WC_Customer $customer
         *
         * @return mixed
         */
        public function createRegularCustomer($customer)
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

                if ((!empty($response) && $response->isSuccessful()) && isset($response['id'])) {
                    return $response['id'];
                }
            }

            return null;
        }

        /**
         * Edit regular customer in CRM
         *
         * @param int $customer_id
         *
         * @return WC_Customer $customer
         */
        public function updateRegularCustomer($customer_id)
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
         * Create corporate customer in CRM
         *
         * @param int | WC_Customer $customer
         *
         * @return mixed
         */
        public function createCorporateCustomer($customer)
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
                $this->processCorporateCustomer($customer);
                $response = $this->retailcrm->customersCorporateCreate($this->customerCorporate);

                return $this->fillCorporateCustomer($response);
            }

            return null;
        }

        /**
         * Edit customer in CRM
         *
         * @param int $customer_id
         *
         * @return WC_Customer|void $customer
         */
        public function updateCorporateCustomer($customer_id)
        {
            if (!$this->retailcrm) {
                return;
            }

            $customer = $this->wcCustomerGet($customer_id);

            if ($customer->get_role() == self::CUSTOMER_ROLE){
                $this->processCorporateCustomer($customer);
                $response = $this->retailcrm->customersCorporateGet($this->customerCorporate['externalId']);

                $this->fillCorporateCustomer($response);
            }

            return $customer;
        }

        /**
         * Fills corporate customer with required data after customer was created or updated.
         * Create or update response after sending customer must be passed.
         *
         * @param \WC_Retailcrm_Response $response
         *
         * @return \WC_Retailcrm_Customer_Corporate_Response|null
         */
        protected function fillCorporateCustomer($response)
        {
            $customerData = array();
            $addressId = 0;
            $companyId = 0;
            $contactId = 0;
            $contactExternalId = '';

            if (!$response->isSuccessful()) {
                return null;
            }

            if ($response->offsetExists('customerCorporate') && isset($response['customerCorporate']['id'])) {
                $customerData = $response['customerCorporate'];
            } else {
                $customerData = $response;
            }

            if (!empty($customerData['id'])) {
                $customerData = $this->retailcrm->customersCorporateGet($customerData['id'], 'id');

                if ($customerData->isSuccessful() && isset($customerData['customerCorporate'])) {
                    $this->customerCorporate = $customerData['customerCorporate'];
                    $customerData = $customerData['customerCorporate'];

                    // Create main address or obtain existing address
                    if (empty($customerData['mainAddress'])) {
                        $addressCreateResponse = $this->retailcrm->customersCorporateAddressesCreate(
                            $customerData['id'],
                            $this->customerCorporateAddress,
                            'id'
                        );

                        if ($addressCreateResponse->isSuccessful() && isset($addressCreateResponse['id'])) {
                            $this->customerCorporateAddress['id'] = $addressCreateResponse['id'];
                            $addressId = (int) $addressCreateResponse['id'];
                        }
                    } else {
                        $addressEditResponse = $this->retailcrm->customersCorporateAddressesEdit(
                            $customerData['id'],
                            $customerData['mainAddress']['id'],
                            $this->customerCorporateAddress,
                            'id',
                            'id'
                        );

                        if ($addressEditResponse->isSuccessful() && isset($addressEditResponse['id'])) {
                            $this->customerCorporateAddress['id'] = $addressEditResponse['id'];
                            $addressId = (int) $addressEditResponse['id'];
                        }
                    }

                    // Update address in company if address was obtained / created
                    if (!empty($this->customerCorporateCompany)
                        && isset($this->customerCorporateAddress['id'])
                    ) {
                        $this->customerCorporateCompany['address'] = array(
                            'id' => $this->customerCorporateAddress['id']
                        );
                    }

                    // Create main company or obtain existing
                    if (empty($customerData['mainCompany'])) {
                        $companyCreateResponse = $this->retailcrm->customersCorporateCompaniesCreate(
                            $customerData['id'],
                            $this->customerCorporateCompany,
                            'id'
                        );

                        if ($companyCreateResponse->isSuccessful() && isset($companyCreateResponse['id'])) {
                            $this->customerCorporateCompany['id'] = $companyCreateResponse['id'];
                            $companyId = (int) $companyCreateResponse['id'];
                        }
                    } else {
                        $companyEditResponse = $this->retailcrm->customersCorporateCompaniesEdit(
                            $customerData['id'],
                            $customerData['mainCompany']['id'],
                            $this->customerCorporateCompany,
                            'id',
                            'id'
                        );

                        if ($companyEditResponse->isSuccessful() && isset($companyEditResponse['id'])) {
                            $this->customerCorporateCompany['id'] = $companyEditResponse['id'];
                            $companyId = (int) $companyEditResponse['id'];
                        }
                    }

                    // Create main customer or obtain existing
                    if (empty($customerData['mainCustomerContact'])) {
                        $contactCustomerCreated = false;
                        $contactCustomerGetResponse =
                            $this->retailcrm->customersGet($this->customerCorporateContact['externalId']);

                        if ($contactCustomerGetResponse->isSuccessful() && isset($contactCustomerGetResponse['customer'])) {
                            $this->customerCorporateContact['id'] = $contactCustomerGetResponse['customer']['id'];
                            $this->retailcrm->customersEdit($this->customerCorporateContact, 'id');
                            $contactId = (int) $contactCustomerGetResponse['customer']['id'];
                            $contactExternalId = $this->customerCorporateContact['externalId'];

                            $contactCustomerCreated = true;
                        } else {
                            $contactCustomerCreateResponse = $this->retailcrm->customersCreate($this->customerCorporateContact);

                            if ($contactCustomerCreateResponse->isSuccessful() && isset($contactCustomerCreateResponse['id'])) {
                                $contactId = (int) $contactCustomerCreateResponse['id'];
                                $contactExternalId = $this->customerCorporateContact['externalId'];
                                $contactCustomerCreated = true;
                            }
                        }

                        if ($contactCustomerCreated) {
                            $contactPair = array(
                                'isMain' => true,
                                'customer' => array(
                                    'id' => $contactId,
                                    'externalId' => $contactExternalId,
                                    'site' => $this->retailcrm->getSingleSiteForKey()
                                )
                            );

                            // Update company in contact in company was obtained / created
                            if (!empty($this->customerCorporateContact)
                                && isset($this->customerCorporateCompany['id'])
                            ) {
                                $contactPair['companies'] = array(
                                    array(
                                        'company' => array(
                                            'id' => $this->customerCorporateCompany['id']
                                        )
                                    )
                                );
                            }

                            $this->retailcrm->customersCorporateContactsCreate(
                                $customerData['id'],
                                $contactPair,
                                'id'
                            );
                        }
                    } else {
                        $this->customerCorporateContact['id'] = $customerData['mainCustomerContact']['customer']['id'];
                        $this->retailcrm->customersEdit($this->customerCorporateContact, 'id');
                        $contactId = (int) $this->customerCorporateContact['id'];
                        $contactExternalId = $this->customerCorporateContact['externalId'];
                    }
                }

                return new WC_Retailcrm_Customer_Corporate_Response(
                    isset($this->customerCorporate['id']) ? $this->customerCorporate['id'] : 0,
                    $this->customerCorporate['externalId'],
                    $addressId,
                    $companyId,
                    $contactId,
                    $contactExternalId
                );
            }

            return null;
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
                'email' => $customer->get_billing_email(),
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

            $this->customer = apply_filters('retailcrm_process_customer', WC_Retailcrm_Plugin::clearArray($data_customer), $customer);
        }

        /**
         * Process corporate customer
         *
         * @param WC_Customer $customer
         *
         * @return void
         */
        protected function processCorporateCustomer($customer)
        {
            $createdAt = $customer->get_date_created();
            $firstName = $customer->get_first_name();
            $data_contact = array(
                'createdAt' => $createdAt->date('Y-m-d H:i:s'),
                'firstName' => $firstName ? $firstName : $customer->get_username(),
                'lastName' => $customer->get_last_name(),
                'email' => $customer->get_email(),
                'address' => $this->customer_address->build($customer)->get_data()
            );
            $data_company = array(
                'isMain' => true,
                'name' => $customer->get_billing_company()
            );
            $data_customer = array(
                'externalId' => $customer->get_id(),
                'nickName' => $data_contact['firstName']
            );

            if ($customer->get_id() > 0) {
                $data_contact['externalId'] = static::getContactPersonExternalId($customer->get_id());
            }

            if ($customer->get_billing_phone()) {
                $data_contact['phones'][] = array(
                   'number' => $customer->get_billing_phone()
                );
            }

            $this->customerCorporate = apply_filters(
                'retailcrm_process_customer_corporate',
                WC_Retailcrm_Plugin::clearArray($data_customer),
                $customer
            );
            $this->customerCorporateContact = apply_filters(
                'retailcrm_process_customer_corporate_contact',
                WC_Retailcrm_Plugin::clearArray($data_contact),
                $customer
            );
            $this->customerCorporateAddress = apply_filters(
                'retailcrm_process_customer_corporate_address',
                WC_Retailcrm_Plugin::clearArray(array_merge(
                    $data_contact['address'],
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
        public function searchCustomer($filter)
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
                if (isset($search['customers'])) {
                    if (empty($search['customers'])) {
                        return false;
                    }

                    $arrayCustumers = $search['customers'];
                    $customer = reset($arrayCustumers);
                } else {
                    $customer = $search['customer'];
                }

                return $customer;
            }

            return false;
        }

        /**
         * @param array $filter
         *
         * @return bool|array
         */
        public function searchCorporateCustomer($filter)
        {
            if (isset($filter['externalId'])) {
                $search = $this->retailcrm->customersCorporateGet($filter['externalId']);
            } elseif (isset($filter['email'])) {
                $search = $this->retailcrm->customersCorporateList(array('email' => $filter['email']));
            }

            if ($search->isSuccessful()) {
                if (isset($search['customersCorporate'])) {
                    if (empty($search['customersCorporate'])) {
                        return false;
                    }

                    $customer = reset($search['customersCorporate']);
                } else {
                    $customer = $search['customerCorporate'];
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

        public static function getContactPersonExternalId($wpCustomerId)
        {
            return 'wpcontact_' . $wpCustomerId;
        }

        public static function isContactPersonExternalId($wpCustomerId)
        {
            return strpos($wpCustomerId, 'wpcontact_') !== false;
        }

        public static function getCustomerIdFromContact($contactExternalId)
        {
            return str_ireplace('wpcontact_', '', $contactExternalId);
        }
    }
endif;
