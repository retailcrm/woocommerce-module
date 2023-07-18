<?php

if (!class_exists('WC_Retailcrm_Customers')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Customers - Allows transfer data customers with CMS.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Customers
    {
        /** @var bool | WC_Retailcrm_Proxy | \WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array */
        protected $retailcrm_settings = [];

        /** @var WC_Retailcrm_Customer_Address */
        protected $customer_address;

        /** @var array */
        private $customer = [];

        /** @var array */
        private $customerCorporate = [];

        /** @var array */
        private $customerCorporateCompany = [];

        /** @var array */
        private $customerCorporateAddress = [];

        /**@var array */
        private $customFields = [];

        /**@var null */
        public $isSubscribed = null;

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

            if (!empty($retailcrm_settings['customer-meta-data-retailcrm'])) {
                $this->customFields = json_decode($retailcrm_settings['customer-meta-data-retailcrm'], true);
            }
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
         * Customer can registration on site, we need:
         * 1. Check by email if the customer already exists in CRM - then update the customer details.
         * 2. If the customer is not in CRM, then create a new customer.
         *
         * @param int $customerId
         *
         * @return void|null
         * @throws Exception
         */
        public function registerCustomer($customerId)
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $wcCustomer = new WC_Customer($customerId);
            $email      = $wcCustomer->get_billing_email();

            if (empty($email)) {
                $email = $wcCustomer->get_email();
            }

            if (empty($email)) {
                WC_Retailcrm_Logger::add('Error: Customer email is empty, externalId: ' . $wcCustomer->get_id());

                return null;
            } else {
                $wcCustomer->set_billing_email($email);
                $wcCustomer->save();
            }

            $response = $this->retailcrm->customersList(['email' => $email]);

            if ($response->isSuccessful() && !empty($response['customers'])) {
                $customers = $response['customers'];
                $customer  = reset($customers);

                if (isset($customer['id'])) {
                    $this->updateCustomerById($customerId, $customer['id']);

                    $builder = new WC_Retailcrm_WC_Customer_Builder();
                    $builder
                        ->setWcCustomer($wcCustomer)
                        ->setPhones(!empty($customer['phones']) ? $customer['phones'] : [])
                        ->setAddress(!empty($customer['address']) ? $customer['address'] : false)
                        ->build()
                        ->getResult()
                        ->save();

                    WC_Retailcrm_Logger::add('Customer was edited, externalId: ' . $wcCustomer->get_id());
                }
            } else {
                $this->createCustomer($customerId);

                $message = $this->isSubscribed
                    ? 'The client has agreed to receive promotional newsletter, email: '
                    : 'The client refused to receive promotional newsletters, email: ';

                WC_Retailcrm_Logger::addCaller('subscribe', $message . $email);
                WC_Retailcrm_Logger::add('Customer was created, externalId: ' . $wcCustomer->get_id());
            }
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
         * @param $customerId
         *
         * @return void|\WC_Customer
         * @throws \Exception
         */
        public function updateCustomer($customerId)
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return;
            }

            $customer = $this->wcCustomerGet($customerId);

            if ($this->isCustomer($customer)) {
                $this->processCustomer($customer);
                $this->retailcrm->customersEdit($this->customer);
            }

            return $customer;
        }

        /**
         * Update customer in CRM by ID
         *
         * @param int        $customerId
         * @param int|string $crmCustomerId
         *
         * @return void|\WC_Customer
         * @throws \Exception
         */
        public function updateCustomerById($customerId, $crmCustomerId)
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return;
            }

            $customer = $this->wcCustomerGet($customerId);

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
                ->getData();

            $addresses = $this->retailcrm->customersCorporateAddresses(
                $corporateId,
                [],
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
                $this->customerCorporateCompany['address'] = [
                    'id' => $response['id'],
                ];

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

                if (empty($email)) {
                    $email = $order->get_billing_email();
                }

                if (empty($billingPhone)) {
                    $billingPhone = $order->get_billing_phone();
                }
            }

            // If a customer has placed an order as a guest, then $customer->get_date_created() == null,
            // then we take $order->get_date_created() order
            $createdAt = empty($createdAt) ? $order->get_date_created() : $createdAt;

            $customerData = [
                'createdAt' => $createdAt->date('Y-m-d H:i:s'),
                'firstName' => !empty($firstName) ? $firstName : $customer->get_username(),
                'lastName' => $lastName,
                'email' => $email,
                'address' => $this->customer_address->build($customer, $order)->getData()
            ];

            if ($customer->get_id() > 0) {
                $customerData['externalId'] = $customer->get_id();
            }

            // The guest client is unsubscribed by default
            if ($customer->get_id() === 0 && $customer->get_date_created() === null) {
                $customerData['subscribed'] = false;
            }

            if ($this->isSubscribed !== null) {
                $customerData['subscribed'] = $this->isSubscribed;
            }

            if (!empty($billingPhone)) {
                $customerData['phones'][] = [
                    'number' => $billingPhone
                ];
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
                    $customerData['isContact'] = true;
                }
            }

            if (!empty($this->customFields)) {
                foreach ($this->customFields as $metaKey => $customKey) {
                    $metaValue = $customer->get_meta($metaKey);

                    if (empty($metaValue)) {
                        continue;
                    }

                    if (strpos($customKey, 'default-crm-field') !== false) {
                        $crmField = explode('#', $customKey);

                        if (count($crmField) === 2 && isset($crmField[1])) {
                            if ($crmField[1] === 'phones') {
                                $customerData[$crmField[1]][] = ['number' => $metaValue];
                            } elseif ($crmField[1] === 'tags') {
                                $customerData['addTags'][] = $metaValue;
                            } else {
                                $customerData[$crmField[1]] = $metaValue;
                            }
                        } elseif (isset($crmField[1], $crmField[2])) {
                            // For customer delivery
                            $customerData[$crmField[1]][$crmField[2]] = $metaValue;
                        }
                    } else {
                        $customerData['customFields'][$customKey] = $metaValue;
                    }
                }
            }

            $this->customer = apply_filters(
                'retailcrm_process_customer',
                WC_Retailcrm_Plugin::clearArray($customerData),
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
            $data_company = [
                'isMain' => true,
                'name' => $order->get_billing_company()
            ];

            $data_customer = [
                'nickName' => $order->get_billing_company(),
                'customerContacts' => [
                    [
                        'isMain' => true,
                        'customer' => [
                            'id' => $crmCustomerId
                        ]
                    ]
                ]
            ];

            $corpAddress = new WC_Retailcrm_Customer_Corporate_Address();

            $billingAddress = $corpAddress
                ->setIsMain(true)
                ->build($customer, $order)
                ->getData();

            if (!empty($billingAddress)) {
                $data_company['contragent']['legalAddress'] = implode(
                    ', ',
                    [
                        $billingAddress['index'],
                        $billingAddress['city'],
                        $billingAddress['region'],
                        $billingAddress['text']
                    ]
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
                $customer = $this->searchCustomer(['externalId' => $customerExternalId]);
            }

            if (!$customer && !empty($customerEmailOrPhone)) {
                $customer = $this->searchCustomer(['email' => $customerEmailOrPhone, 'isContact' => $isContact]);
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
                if (method_exists($new_customer, 'set_billing_' . $prop)) {
                    $new_customer->{'set_billing_' . $prop}($value);
                }
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
