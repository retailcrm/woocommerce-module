<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Customer_Switcher_State - Holds WC_Retailcrm_Customer_Switcher state.
 * It exists only because we need to comply with builder interface.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Customer_Switcher_State
{
    /** @var \WC_Order $wcOrder */
    private $wcOrder;

    /** @var array */
    private $newCustomer;

    /** @var array */
    private $newContact;

    /** @var string $newCompanyName */
    private $newCompanyName;

    /** @var array $companyAddress */
    private $companyAddress;

    /**
     * @return \WC_Order
     */
    public function getWcOrder()
    {
        return $this->wcOrder;
    }

    /**
     * @param \WC_Order $wcOrder
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setWcOrder($wcOrder)
    {
        $this->wcOrder = $wcOrder;
        return $this;
    }

    /**
     * @return array
     */
    public function getNewCustomer()
    {
        return $this->newCustomer;
    }

    /**
     * @param array $newCustomer
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setNewCustomer($newCustomer)
    {
        $this->newCustomer = $newCustomer;
        return $this;
    }

    /**
     * @return array
     */
    public function getNewContact()
    {
        return $this->newContact;
    }

    /**
     * @param array $newContact
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setNewContact($newContact)
    {
        $this->newContact = $newContact;
        return $this;
    }

    /**
     * @return string
     */
    public function getNewCompanyName()
    {
        return $this->newCompanyName;
    }

    /**
     * @param string $newCompanyName
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setNewCompanyName($newCompanyName)
    {
        $this->newCompanyName = $newCompanyName;
        return $this;
    }

    /**
     * @return array
     */
    public function getCompanyAddress()
    {
        return $this->companyAddress;
    }

    /**
     * @param array $companyAddress
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setCompanyAddress($companyAddress)
    {
        $this->companyAddress = $companyAddress;
        return $this;
    }

    /**
     * @param array $newCompany
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setNewCompany($newCompany)
    {
        if (isset($newCompany['name'])) {
            $this->setNewCompanyName($newCompany['name']);
        }

        if (isset($newCompany['address']) && !empty($newCompany['address'])) {
            $this->setCompanyAddress($newCompany['address']);
        }

        return $this;
    }

    /**
     * Returns true if current state may be processable (e.g. when customer or related data was changed).
     * It doesn't guarantee state validity.
     *
     * @return bool
     */
    public function feasible()
    {
        return !(empty($this->newCustomer) && empty($this->newContact) && empty($this->newCompanyName));
    }

    /**
     * Throws an exception if state is not valid
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    public function validate()
    {
        if (empty($this->wcOrder)) {
            throw new \InvalidArgumentException('Empty WC_Order.');
        }

        if (empty($this->newCustomer) && empty($this->newContact) && empty($this->newCompanyName)) {
            throw new \InvalidArgumentException('New customer, new contact and new company is empty.');
        }

        if (!empty($this->newCustomer) && !empty($this->newContact)) {
            WC_Retailcrm_Logger::debug(
                __METHOD__,
                array(
                    'State data (customer and contact):' . PHP_EOL,
                    $this->getNewCustomer(),
                    $this->getNewContact()
                )
            );
            throw new \InvalidArgumentException(
                'Too much data in state - cannot determine which customer should be used.'
            );
        }
    }
}
