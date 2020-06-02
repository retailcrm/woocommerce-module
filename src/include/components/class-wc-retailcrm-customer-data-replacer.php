<?php

/**
 * Class WC_Retailcrm_Customer_Data_Replacer
 * This component provides builder-like interface in order to make it easier to change customer & customer data
 * in the order via retailCRM history.
 */
class WC_Retailcrm_Customer_Data_Replacer implements WC_Retailcrm_Builder_Interface
{
    /**
     * @var \WC_Retailcrm_Customer_Data_Replacer_State $data
     */
    private $data;

    /**
     * @var \WC_Retailcrm_Customer_Data_Replacer_Result|null $result
     */
    private $result;

    /**
     * WC_Retailcrm_Customer_Data_Replacer constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * In fact, this will execute customer change in provided order.
     * This will not build anything.
     *
     * @return $this|\WC_Retailcrm_Builder_Interface
     */
    public function build()
    {
        $this->data->validate();

        $wcOrder = $this->data->getWcOrder();
        $newCustomer = $this->data->getNewCustomer();
        $newCorporateCustomer = $this->data->getNewCorporateCustomer();
        $newContact = $this->data->getNewContact();
        $newCompany = $this->data->getNewCompany();

        if (!empty($newCustomer)) {
            $this->processChangeToRegular($wcOrder, $newCustomer);
            return $this;
        }

        if (!empty($newContact)) {
            $this->processChangeToContactPerson($wcOrder, $newContact);
        }

        if (!empty($newCompany)) {
            $this->updateCompany($wcOrder, $newCorporateCustomer, $newCompany);
        }

        return $this;
    }

    /**
     * Change order customer to regular one
     *
     * @param \WC_Order $wcOrder
     * @param array $newCustomer
     */
    public function processChangeToRegular($wcOrder, $newCustomer)
    {
        // TODO: Implement
    }

    /**
     * Change order customer to corporate one (we only care about it's contact)
     *
     * @param \WC_Order $wcOrder
     * @param array $newContact
     */
    public function processChangeToContactPerson($wcOrder, $newContact)
    {
        // TODO: Implement
    }

    /**
     * Update company in the order
     *
     * @param WC_Order $wcOrder
     * @param array $corporateCustomer
     * @param array $company
     */
    public function updateCompany($wcOrder, $corporateCustomer, $company)
    {
        // TODO: Implement
    }

    /**
     * @return $this|\WC_Retailcrm_Builder_Interface
     */
    public function reset()
    {
        $this->data = new WC_Retailcrm_Customer_Data_Replacer_State();
        $this->result = null;
        return $this;
    }

    /**
     * Set initial state into component
     *
     * @param \WC_Retailcrm_Customer_Data_Replacer_State $data
     *
     * @return $this|\WC_Retailcrm_Builder_Interface
     */
    public function setData($data)
    {
        if (!($data instanceof WC_Retailcrm_Customer_Data_Replacer_State)) {
            throw new \InvalidArgumentException('Invalid data type');
        }

        $this->data = $data;
        return $this;
    }

    /**
     * @return \WC_Retailcrm_Customer_Data_Replacer_Result|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \WC_Retailcrm_Customer_Data_Replacer_State
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns WC_Customer by id. Returns null if there's no such customer.
     *
     * @param int $id
     *
     * @return \WC_Customer|null
     */
    private function getWcCustomerById($id)
    {
        try {
            return new WC_Customer($id);
        } catch (\Exception $exception) {
            return null;
        }
    }
}
