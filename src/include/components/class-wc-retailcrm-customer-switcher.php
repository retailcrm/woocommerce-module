<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Customer_Switcher - This component provides builder-like interface in order to make it easier to
 * change customer & customer data in the order via retailCRM history.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
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
        $companyAddress = $this->data->getCompanyAddress();

        if (!empty($newCustomer)) {
            WC_Retailcrm_Logger::debug(
                __METHOD__,
                array(
                    'Changing to individual customer for order',
                    $this->data->getWcOrder()->get_id()
                )
            );
            $this->processChangeToRegular($this->data->getWcOrder(), $newCustomer, false);
            $this->data->getWcOrder()->set_billing_company('');
        } else {
            if (!empty($newContact)) {
                WC_Retailcrm_Logger::debug(
                    __METHOD__,
                    array(
                        'Changing to contact person customer for order',
                        $this->data->getWcOrder()->get_id()
                    )
                );
                $this->processChangeToRegular($this->data->getWcOrder(), $newContact, true);
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

            if (!empty($companyAddress)) {
                $this->processCompanyAddress();
            }
        }

        return $this;
    }

    /**
     * Change order customer to regular one
     *
     * @param \WC_Order $wcOrder
     * @param array     $newCustomer
     * @param bool      $isContact
     *
     * @throws \WC_Data_Exception
     */
    public function processChangeToRegular($wcOrder, $newCustomer, $isContact)
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
            $wcCustomer = new WC_Customer($newCustomer['externalId']);

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

        $address = self::arrayValue($newCustomer, 'address', array());

        if ($isContact) {
            self::setShippingAddressToOrder($wcOrder, $address);
        } else {
            self::setBillingAddressToOrder($wcOrder, $address);
            self::setShippingAddressToOrder($wcOrder, $address);
        }

        $wcOrder->set_billing_phone(self::singleCustomerPhone($newCustomer));

        $this->result = new WC_Retailcrm_Customer_Switcher_Result($wcCustomer, $wcOrder);
    }

    /**
     * Process company address.
     *
     * @throws \WC_Data_Exception
     */
    protected function processCompanyAddress()
    {
        $wcOrder = $this->data->getWcOrder();
        $companyAddress = $this->data->getCompanyAddress();

        if (!empty($companyAddress)) {
            self::setBillingAddressToOrder($wcOrder, $companyAddress);
        }

        if (empty($this->result)) {
            $this->result = new WC_Retailcrm_Customer_Switcher_Result(null, $wcOrder);
        }
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
     * Sets billing address properties in order
     *
     * @param \WC_Order $wcOrder
     * @param array     $address
     *
     * @throws \WC_Data_Exception
     */
    private static function setBillingAddressToOrder($wcOrder, $address)
    {
        $wcOrder->set_billing_state(self::arrayValue($address, 'region', ''));
        $wcOrder->set_billing_postcode(self::arrayValue($address, 'index', ''));
        $wcOrder->set_billing_country(self::arrayValue($address, 'country', ''));
        $wcOrder->set_billing_city(self::arrayValue($address, 'city', ''));
        $wcOrder->set_billing_address_1(self::arrayValue($address, 'text', ''));
    }

    /**
     * Sets shipping address properties in order
     *
     * @param \WC_Order $wcOrder
     * @param array     $address
     *
     * @throws \WC_Data_Exception
     */
    private static function setShippingAddressToOrder($wcOrder, $address)
    {
        $wcOrder->set_shipping_state(self::arrayValue($address, 'region', ''));
        $wcOrder->set_shipping_postcode(self::arrayValue($address, 'index', ''));
        $wcOrder->set_shipping_country(self::arrayValue($address, 'country', ''));
        $wcOrder->set_shipping_city(self::arrayValue($address, 'city', ''));
        $wcOrder->set_shipping_address_1(self::arrayValue($address, 'text', ''));
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

        return isset($arr[$key]) ? $arr[$key] : $def;
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
            return '';
        }

        if (empty($customerData['phones']) || !is_array($customerData['phones'])) {
            return '';
        }

        $phones = $customerData['phones'];
        $phone = reset($phones);

        if (!isset($phone['number'])) {
            return '';
        }

        return (string) $phone['number'];
    }
}
