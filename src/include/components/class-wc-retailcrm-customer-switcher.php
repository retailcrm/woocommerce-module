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
     * This will not produce any new entities.
     *
     * @return $this|\WC_Retailcrm_Builder_Interface
     * @throws \WC_Data_Exception
     */
    public function build()
    {
        $this->data->validate();

        $wcOrder = $this->data->getWcOrder();
        $newCustomer = $this->data->getNewCustomer();
        $newContact = $this->data->getNewContact();
        $newCompany = $this->data->getNewCompanyName();

        if (!empty($newCustomer)) {
            $this->processChangeToRegular($wcOrder, $newCustomer);
            return $this;
        }

        if (!empty($newContact)) {
            $this->processChangeToRegular($wcOrder, $newContact);
        }

        if (!empty($newCompany)) {
            $this->updateCompany($wcOrder, $newCompany);
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
        } else {
            //TODO:
            // 1. Too risky! Consider using default WooCommerce object.
            // 2. Will it work as expected with such property name? Check that.
            // 3. It will remove user from order directly, WC_Order logic is completely skipped here.
            //    It can cause these problems:
            //    1) Order is changed and it's state in WC_Order is inconsistent, which can lead to problems
            //       and data inconsistency while saving. For example, order saving can overwrite `_customer_user`
            //       meta, which will revert this operation and we'll end up with a broken data (order is still
            //       attached to an old customer). Whichever, this last statement should be checked.
            //    2) The second problem is a lifecycle in general. We're using builder interface, and code inside
            //       doesn't do anything which is not expected from builder. For example, besides this line, there's no
            //       CRUD operations. Such operation will not be expected here, so, it's better to remove it from here.
            //       The best solution would be to use WC_Order, and not modify it's data directly.
            delete_post_meta($wcOrder->get_id(), '_customer_user');
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
     * @param string   $company
     *
     * @throws \WC_Data_Exception
     */
    public function updateCompany($wcOrder, $company)
    {
        $wcOrder->set_billing_company($company);
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
