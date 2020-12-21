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

abstract class WC_Retailcrm_Abstracts_Address extends WC_Retailcrm_Abstracts_Data
{
    const ADDRESS_TYPE_BILLING = 'billing';
    const ADDRESS_TYPE_SHIPPING = 'shipping';

    /** @var string $address_type */
    protected $address_type = 'shipping';

    /** @var bool $fallback_to_billing */
    protected $fallback_to_billing = false;

    /** @var bool $fallback_to_shipping */
    protected $fallback_to_shipping = false;

    /** @var array $data */
    protected $data = array(
        'index' => '',
        'city' => '',
        'region' => '',
        'text' => '',
    );

    /**
     * Resets inner state
     */
    public function reset_data()
    {
        $this->data = array(
            'index' => '',
            'city' => '',
            'region' => '',
            'text' => '',
        );

        return $this;
    }

    /**
     * @param bool $fallback_to_billing
     *
     * @return self
     */
    public function setFallbackToBilling($fallback_to_billing)
    {
        $this->fallback_to_billing = $fallback_to_billing;
        return $this;
    }

    /**
     * @param bool $fallback_to_shipping
     *
     * @return WC_Retailcrm_Abstracts_Address
     */
    public function setFallbackToShipping($fallback_to_shipping)
    {
        $this->fallback_to_shipping = $fallback_to_shipping;
        return $this;
    }

    /**
     * Sets woocommerce address type to work with
     *
     * @param string $addressType
     *
     * @return self
     */
    public function setWCAddressType($addressType = WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_SHIPPING)
    {
        $this->address_type = $addressType;
        return $this;
    }

    /**
     * Validate address
     *
     * @param array $address
     *
     * @return bool
     */
    public function validateAddress($address)
    {
        if (empty($address['country']) ||
            empty($address['state']) ||
            empty($address['postcode']) ||
            empty($address['city']) ||
            empty($address['address_1'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns address from order. Respects fallback_to_billing parameter.
     *
     * @param \WC_Order $order
     *
     * @return array
     */
    protected function getOrderAddress($order)
    {
        $orderAddress = $order->get_address($this->address_type);
        $checkEmptyArray = $this->validateAddress($orderAddress) ? array_filter($orderAddress) : array();
        
        if (empty($checkEmptyArray) && $this->address_type === self::ADDRESS_TYPE_BILLING && $this->fallback_to_shipping) {
            $orderAddress = $order->get_address(self::ADDRESS_TYPE_SHIPPING);
        }

        if (empty($checkEmptyArray) && $this->address_type === self::ADDRESS_TYPE_SHIPPING && $this->fallback_to_billing) {
            $orderAddress = $order->get_address(self::ADDRESS_TYPE_BILLING);
        }

        return $orderAddress;
    }

    /**
     * Glue two addresses
     *
     * @param string $address1
     * @param string $address2
     *
     * @return string
     */
    protected function joinAddresses($address1 = '', $address2 = '')
    {
        if (empty($address1) && empty($address2)) {
            return '';
        }

        if (empty($address2) && !empty($address1)) {
            return $address1;
        }

        return $address1 . ', ' . $address2;
    }

    /**
     * Returns state name by it's code
     *
     * @param string $countryCode
     * @param string $stateCode
     *
     * @return string
     */
    protected function get_state_name($countryCode, $stateCode)
    {
        if (preg_match('/^[A-Z\-0-9]{0,5}$/', $stateCode) && !is_null($countryCode)) {
            $countriesProvider = new WC_Countries();
            $states = $countriesProvider->get_states($countryCode);

            if (!empty($states) && array_key_exists($stateCode, $states)) {
                return (string) $states[$stateCode];
            }
        }

        return $stateCode;
    }
}
