<?php

/**
 * Class WC_Retailcrm_Customer_Switcher_State
 * Holds WC_Retailcrm_Customer_Switcher state. It exists only because we need to comply with builder interface.
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
     * @param array $newCompany
     *
     * @return WC_Retailcrm_Customer_Switcher_State
     */
    public function setNewCompany($newCompany)
    {
        if (isset($newCompany['name'])) {
            $this->setNewCompany($newCompany['name']);
        }

        return $this;
    }

    /**
     * Throws an exception if state is not valid
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    public function validate()
    {
        if (empty($this->getWcOrder())) {
            throw new \InvalidArgumentException('Empty WC_Order.');
        }

        if (empty($this->getNewCustomer())
            && empty($this->getNewContact())
            && empty($this->getNewCorporateCustomer())
        ) {
            throw new \InvalidArgumentException('New customer, new contact and new corporate customer is empty.');
        }

        if (!empty($this->getNewCustomer())
            && (!empty($this->getNewContact()) || !empty($this->getNewCorporateCustomer()))
        ) {
            throw new \InvalidArgumentException(
                'Too much data in state - cannot determine which customer should be used.'
            );
        }
    }
}
