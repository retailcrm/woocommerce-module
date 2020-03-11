<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

/**
 * Class WC_Retailcrm_Customer_Address
 */
class WC_Retailcrm_Customer_Corporate_Address extends WC_Retailcrm_Abstracts_Address
{
    /** @var string $filter_name */
    protected $filter_name = 'customer_address';

    /** @var string $address_type */
    protected $address_type = 'shipping';

    /** @var bool $fallback_to_billing */
    protected $fallback_to_billing = false;

    /** @var bool $isMain */
    protected $isMain = true;

    /** @var bool $explicitIsMain */
    protected $explicitIsMain;

    /**
     * Sets woocommerce address type to work with
     *
     * @param string $addressType
     *
     * @return \WC_Retailcrm_Customer_Corporate_Address
     */
    public function setWCAddressType($addressType = WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_SHIPPING)
    {
        $this->address_type = $addressType;
        return $this;
    }

    /**
     * @param bool $fallback_to_billing
     *
     * @return WC_Retailcrm_Customer_Corporate_Address
     */
    public function setFallbackToBilling($fallback_to_billing)
    {
        $this->fallback_to_billing = $fallback_to_billing;
        return $this;
    }

    /**
     * @param bool $isMain
     *
     * @return WC_Retailcrm_Customer_Corporate_Address
     */
    public function setIsMain($isMain)
    {
        $this->isMain = $isMain;
        return $this;
    }

    /**
     * @param bool $explicitIsMain
     *
     * @return WC_Retailcrm_Customer_Corporate_Address
     */
    public function setExplicitIsMain($explicitIsMain)
    {
        $this->explicitIsMain = $explicitIsMain;
        return $this;
    }

    /**
     * @param WC_Customer    $customer
     * @param \WC_Order|null $order
     *
     * @return self
     */
    public function build($customer, $order = null)
    {
        if ($order instanceof WC_Order) {
            $address = $this->getOrderAddress($order);
            $data = array(
                'index' => $address['postcode'],
                'countryIso' => $address['country'],
                'region' => $address['state'],
                'city' => $address['city'],
                'name' => $address['company'],
                'text' => $this->joinAddresses($address['address_1'], $address['address_2'])
            );
        } else {
            if (WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_SHIPPING == $this->address_type) {
                $data = $this->getCustomerBillingAddress($customer);

                if (empty($address) && $this->fallback_to_billing) {
                    $data = $this->getCustomerShippingAddress($customer);
                }
            } else {
                $data = $this->getCustomerBillingAddress($customer);
            }
        }

        if ($this->isMain) {
            $data['isMain'] = true;
        } elseif ($this->explicitIsMain) {
            $data['isMain'] = false;
        }

        $this->set_data_fields($data);

        return $this;
    }

    /**
     * Returns built customer billing address
     *
     * @param \WC_Customer|\WP_User $customer
     *
     * @return array
     */
    public function getCustomerBillingAddress($customer)
    {
        return array(
            'index' => $customer->get_billing_postcode(),
            'countryIso' => $customer->get_billing_country(),
            'region' => $customer->get_billing_state(),
            'city' => $customer->get_billing_city(),
            'name' => $customer->get_billing_company(),
            'text' => $this->joinAddresses(
                $customer->get_billing_address_1(),
                $customer->get_billing_address_2()
            )
        );
    }

    /**
     * Returns built customer shipping address
     *
     * @param \WC_Customer|\WP_User $customer
     *
     * @return array
     */
    public function getCustomerShippingAddress($customer)
    {
        return array(
            'index' => $customer->get_shipping_postcode(),
            'countryIso' => $customer->get_shipping_country(),
            'region' => $customer->get_shipping_state(),
            'city' => $customer->get_shipping_city(),
            'name' => $customer->get_shipping_company(),
            'text' => $this->joinAddresses(
                $customer->get_shipping_address_1(),
                $customer->get_shipping_address_2()
            )
        );
    }
}
