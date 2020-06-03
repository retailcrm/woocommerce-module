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

        WC_Retailcrm_Logger::debug(__METHOD__, array('state', $this->data));

        $newCustomer = $this->data->getNewCustomer();
        $newContact = $this->data->getNewContact();
        $newCompany = $this->data->getNewCompanyName();

        if (!empty($newCustomer)) {
            WC_Retailcrm_Logger::debug(
                __METHOD__,
                array(
                    'Changing to individual customer for order',
                    $this->data->getWcOrder()->get_id()
                )
            );
            $this->processChangeToRegular($this->data->getWcOrder(), $newCustomer);
        } else {
            if (!empty($newContact)) {
                WC_Retailcrm_Logger::debug(
                    __METHOD__,
                    array(
                        'Changing to contact person customer for order',
                        $this->data->getWcOrder()->get_id()
                    )
                );
                $this->processChangeToRegular($this->data->getWcOrder(), $newContact);
            }

            if (!empty($newCompany)) {
                WC_Retailcrm_Logger::debug(
                    __METHOD__,
                    array(sprintf(
                        'Replacing old order id=`%d` company `%s` with new company `%s`',
                        $this->data->getWcOrder()->get_id(),
                        $this->data->getWcOrder()->get_billing_company(),
                        $newCompany
                    ))
                );
                $this->processCompanyChange();
            }
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

        WC_Retailcrm_Logger::debug(
            __METHOD__,
            array(
                'Switching in order',
                $wcOrder->get_id(),
                'to',
                $newCustomer
            )
        );

        if (isset($newCustomer['externalId'])) {
            $wcCustomer = WC_Retailcrm_Plugin::getWcCustomerById($newCustomer['externalId']);

            if (!empty($wcCustomer)) {
                $wcOrder->set_customer_id($wcCustomer->get_id());
                WC_Retailcrm_Logger::debug(
                    __METHOD__,
                    array(
                        'Set customer to',
                        $wcCustomer->get_id(),
                        'in order',
                        $wcOrder->get_id()
                    )
                );
            }
        } else {
            $wcOrder->set_customer_id(0);
            WC_Retailcrm_Logger::debug(
                __METHOD__,
                array(
                    'Set customer to 0 (guest) in order',
                    $wcOrder->get_id()
                )
            );
        }

        $fields = array(
            'billing_first_name' => self::arrayValue($newCustomer, 'firstName'),
            'billing_last_name' => self::arrayValue($newCustomer, 'lastName'),
            'billing_email' => self::arrayValue($newCustomer, 'email')
        );

        foreach ($fields as $field => $value) {
            $wcOrder->{'set_' . $field}($value);
        }

        if (isset($newCustomer['address'])) {
            $address = $newCustomer['address'];

            if (isset($address['region'])) {
                $wcOrder->set_billing_state($address['region']);
            }

            if (isset($address['index'])) {
                $wcOrder->set_billing_postcode($address['index']);
            }

            if (isset($address['country'])) {
                $wcOrder->set_billing_country($address['country']);
            }

            if (isset($address['city'])) {
                $wcOrder->set_billing_city($address['city']);
            }

            if (isset($address['text'])) {
                $wcOrder->set_billing_address_1($address['text']);
            }
        }

        $customerPhone = self::singleCustomerPhone($newCustomer);

        if (!empty($customerPhone)) {
            $wcOrder->set_billing_phone($customerPhone);
        }

        $wcOrder->set_billing_company('');
        $this->result = new WC_Retailcrm_Customer_Switcher_Result($wcCustomer, $wcOrder);
    }

    /**
     * This will update company field in order and create result if it's not set (happens when only company was changed).
     *
     * @throws \WC_Data_Exception
     */
    public function processCompanyChange()
    {
        $this->data->getWcOrder()->set_billing_company($this->data->getNewCompanyName());

        if (empty($this->result)) {
            $this->result = new WC_Retailcrm_Customer_Switcher_Result(null, $this->data->getWcOrder());
        }
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

    /**
     * Returns first phone from order data or null
     *
     * @param array $customerData
     *
     * @return string|null
     */
    private static function singleCustomerPhone($customerData)
    {
        if (!array_key_exists('phones', $customerData)) {
            return null;
        }

        if (empty($customerData['phones']) || !is_array($customerData['phones'])) {
            return null;
        }

        $phones = $customerData['phones'];
        $phone = reset($phones);

        if (!isset($phone['number'])) {
            return null;
        }

        return (string) $phone['number'];
    }
}
