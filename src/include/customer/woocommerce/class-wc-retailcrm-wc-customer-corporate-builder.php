<?php

/**
 * Class WC_Retailcrm_WC_Customer_Corporate_Builder
 * It converts retailCRM customer data (array) into WC_Customer
 */
class WC_Retailcrm_WC_Customer_Corporate_Builder extends WC_Retailcrm_Abstract_Builder
{
    /**
     * @var \WC_Customer $customer
     */
    private $customer;

    /**
     * @var array $contactPerson
     */
    private $contactPerson;

    /**
     * @var \WC_Retailcrm_Builder_Interface $customerBuilder
     */
    private $customerBuilder;
    
    /**
     * WC_Retailcrm_WC_Customer_Builder constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @param array $contactPerson
     *
     * @return \WC_Retailcrm_WC_Customer_Corporate_Builder
     */
    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;
        return $this;
    }

    /**
     * @return array
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * @param \WC_Retailcrm_Builder_Interface $customerBuilder
     */
    public function setCustomerBuilder($customerBuilder)
    {
        $this->customerBuilder = $customerBuilder;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->contactPerson['firstName'] = $firstName;
        return $this;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->contactPerson['lastName'] = $lastName;
        return $this;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->contactPerson['email'] = $email;
        return $this;
    }

    /**
     * @param string $externalId
     *
     * @return $this
     */
    public function setExternalId($externalId)
    {
        $this->contactPerson['externalId'] = $externalId;
        return $this;
    }

    /**
     * @param array $phones
     *
     * @return $this
     */
    public function setPhones($phones)
    {
        if (self::isPhonesArrayValid($phones)) {
            $this->contactPerson['phones'] = $phones;
        }

        return $this;
    }

    /**
     * @param array $address
     *
     * @return $this
     */
    public function setAddress($address)
    {
        if (is_array($address)) {
            $this->contactPerson['address'] = $address;
        }

        return $this;
    }

    /**
     * @param \WC_Customer $customer
     *
     * @return $this
     */
    public function setWcCustomer($customer)
    {
        if ($customer instanceof WC_Customer) {
            $this->customer = $customer;
        }

        return $this;
    }

    /**
     * Sets provided externalId and loads associated customer from DB (it it exists there).
     * Returns true if everything went find; returns false if customer wasn't found.
     *
     * @param string $externalId
     *
     * @return bool
     * @throws \Exception
     */
    public function loadExternalId($externalId)
    {
        try {
            $wcCustomer = new WC_Customer($externalId);
        } catch (\Exception $exception) {
            return false;
        }

        $this->setExternalId($externalId);
        $this->setWcCustomer($wcCustomer);

        return true;
    }

    public function reset()
    {
        parent::reset();
        $this->customer = new WC_Customer();
        $this->contactPerson = array();
        $this->customerBuilder = new WC_Retailcrm_WC_Customer_Builder();

        return $this;
    }

    /**
     * Fill WC_Customer fields with customer data from retailCRM.
     * If field is not present in retailCRM customer - it will remain unchanged.
     *
     * @return $this|\WC_Retailcrm_Builder_Interface
     * @throws \Exception
     */
    public function build()
    {
        $this->checkBuilderValidity();
        WC_Retailcrm_Logger::debug(
            __METHOD__,
            'Building WC_Customer from corporate data:',
            $this->data,
            "\nContact:",
            $this->contactPerson
        );

       $wcCustomer = $this->customerBuilder
           ->setData($this->contactPerson)
           ->build()
           ->getResult();

        return $this;
    }

    /**
     * @return mixed|\WC_Customer|null
     */
    public function getResult()
    {
        return $this->customer;
    }

    /**
     * Throws an exception if internal state is not ready for data building.
     *
     * @throws \RuntimeException
     */
    private function checkBuilderValidity()
    {
        if (empty($this->data)) {
            throw new \RuntimeException('Empty data');
        }

        if (!is_array($this->data)) {
            throw new \RuntimeException('Data must be an array');
        }
    }

    /**
     * Returns true if provided variable contains array with customer phones.
     *
     * @param mixed $phones
     *
     * @return bool
     */
    private static function isPhonesArrayValid($phones)
    {
        if (!is_array($phones)) {
            return false;
        }

        foreach ($phones as $phone) {
            if (!is_array($phone) || count($phone) != 1 || !array_key_exists('number', $phone)) {
                return false;
            }
        }

        return true;
    }
}
