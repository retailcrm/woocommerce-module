<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_WC_Customer_Builder - It converts retailCRM customer data (array) into WC_Customer.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_WC_Customer_Builder extends WC_Retailcrm_Abstract_Builder
{
    /**
     * @var \WC_Customer $customer
     */
    private $customer;

    /**
     * WC_Retailcrm_WC_Customer_Builder constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->data['firstName'] = $firstName;
        return $this;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->data['lastName'] = $lastName;
        return $this;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->data['email'] = $email;
        return $this;
    }

    /**
     * @param string $externalId
     *
     * @return $this
     */
    public function setExternalId($externalId)
    {
        $this->data['externalId'] = $externalId;
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
            $this->data['phones'] = $phones;
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
            $this->data['address'] = $address;
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

        return $this;
    }

    /**
     * Fill WC_Customer fields with customer data from RetailCRM.
     * If field is not present in retailCRM customer - it will remain unchanged.
     *
     * @return $this|\WC_Retailcrm_Builder_Interface
     */
    public function build()
    {
        $this->checkBuilderValidity();

        WC_Retailcrm_Logger::debug(__METHOD__, ['Building WC_Customer from data:', $this->data]);

        $this->customer->set_first_name($this->dataValue('firstName', $this->customer->get_first_name()));
        $this->customer->set_last_name($this->dataValue('lastName', $this->customer->get_last_name()));
        $this->customer->set_billing_email($this->dataValue('email', $this->customer->get_billing_email()));
        $phones = $this->dataValue('phones', []);

        if ((is_array($phones) || $phones instanceof Countable) && count($phones) > 0) {
            $phoneData = reset($phones);

            if (is_array($phoneData) && isset($phoneData['number'])) {
                $this->customer->set_billing_phone($phoneData['number']);
            }
        } elseif (is_string($phones) || is_numeric($phones)) {
            $this->customer->set_billing_phone($phones);
        }

        $address = $this->dataValue('address');

        if (!empty($address)) {
            $this->customer->set_billing_state(self::arrayValue(
                $address,
                'region',
                $this->customer->get_billing_state()
            ));
            $this->customer->set_billing_postcode(self::arrayValue(
                $address,
                'index',
                $this->customer->get_billing_postcode()
            ));
            $this->customer->set_billing_country(self::arrayValue(
                $address,
                'country',
                $this->customer->get_billing_country()
            ));
            $this->customer->set_billing_city(self::arrayValue(
                $address,
                'city',
                $this->customer->get_billing_city()
            ));
        }

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
     * @throws RuntimeException
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

