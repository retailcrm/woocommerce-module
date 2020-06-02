<?php

/**
 * Class WC_Retailcrm_Customer_Switcher
 * This component provides builder-like interface in order to make it easier to change customer & customer data
 * in the order via retailCRM history.
 */
class WC_Retailcrm_Customer_Switcher implements WC_Retailcrm_Builder_Interface
{
    /**
     * @var \WC_Retailcrm_Customer_Switcher_State $data
     */
    private $data;

    /**
     * @var \WC_Retailcrm_Customer_Switcher_Result|null $result
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
     * @throws \WC_Data_Exception
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
            $this->processChangeToRegular($wcOrder, $newContact);
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
     * @param array     $newCustomer
     *
     * @throws \WC_Data_Exception
     */
    public function processChangeToRegular($wcOrder, $newCustomer)
    {
        $wcCustomer = null;

        if (isset($newCustomer['externalId'])) {
            $wcCustomer = WC_Retailcrm_Plugin::getWcCustomerById($newCustomer['externalId']);

            if (!empty($wcCustomer)) {
                $wcOrder->set_customer_id($wcCustomer->get_id());
            }
        }

        $fields = array(
            'billing_first_name' => self::arrayValue($newCustomer, 'firstName'),
            'billing_last_name' => self::arrayValue($newCustomer, 'lastName'),
            'billing_email' => self::arrayValue($newCustomer, 'email')
        );

        foreach ($fields as $field => $value) {
            $wcOrder->{'set_' . $field}($value);
        }

        $wcOrder->set_billing_company('');
        $this->result = new WC_Retailcrm_Customer_Switcher_Result($wcCustomer, $wcOrder);
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
        $this->data = new WC_Retailcrm_Customer_Switcher_State();
        $this->result = null;
        return $this;
    }

    /**
     * Set initial state into component
     *
     * @param \WC_Retailcrm_Customer_Switcher_State $data
     *
     * @return $this|\WC_Retailcrm_Builder_Interface
     */
    public function setData($data)
    {
        if (!($data instanceof WC_Retailcrm_Customer_Switcher_State)) {
            throw new \InvalidArgumentException('Invalid data type');
        }

        $this->data = $data;
        return $this;
    }

    /**
     * @return \WC_Retailcrm_Customer_Switcher_Result|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \WC_Retailcrm_Customer_Switcher_State
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array|\ArrayObject|\ArrayAccess $arr
     * @param string $key
     * @param string $def
     *
     * @return mixed|string
     */
    private static function arrayValue($arr, $key, $def = '')
    {
        if (!is_array($arr) && !($arr instanceof ArrayObject) && !($arr instanceof ArrayAccess)) {
            return $def;
        }

        if (!array_key_exists($key, $arr) && !empty($arr[$key])) {
            return $def;
        }

        return $arr[$key];
    }
}
