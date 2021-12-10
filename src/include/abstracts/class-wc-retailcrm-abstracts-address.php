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
     * Returns shipping address from order.
     *
     * @param \WC_Order $order
     *
     * @return array
     */
    protected function getOrderAddress($order)
    {
        if ($order === null) {
            return array();
        }

        $orderShippingAddress = array(
            'postcode' => $order->get_shipping_postcode(),
            'state' => $order->get_shipping_state(),
            'city' => $order->get_shipping_city(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2()
        );

        if (!empty($orderShippingAddress)) {
            return array(
                'index'  => $orderShippingAddress['postcode'],
                'city'   => $orderShippingAddress['city'],
                'region' => $this->get_state_name($order->get_shipping_country(), $orderShippingAddress['state']),
                'text'   => implode(' ', $orderShippingAddress)
            );
        }
    }

    /**
     * Returns billing address from customer.
     *
     * @param \WC_Customer $customer
     * @param \WC_Order $order
     *
     * @return array
     */
    protected function getCustomerAddress($customer, $order)
    {
        if ($customer === null) {
            return array();
        }

        $customerBillingAddress = $customer->get_billing_address();

        if ($order instanceof WC_Order && empty($customerBillingAddress)) {
            return array(
                'index' => $order->get_billing_postcode(),
                'countryIso' => $this->validateCountryCode($order->get_billing_country()),
                'region' => $this->get_state_name($order->get_billing_country(), $order->get_billing_state()),
                'city' => $order->get_billing_city(),
                'text' => $this->joinAddresses($order->get_billing_address_1(), $order->get_billing_address_2())
            );
        } else {
            return array(
                'index' => $customer->get_billing_postcode(),
                'countryIso' => $this->validateCountryCode($customer->get_billing_country()),
                'region' => $this->get_state_name($customer->get_billing_country(), $customer->get_billing_state()),
                'city' => $customer->get_billing_city(),
                'text' => $this->joinAddresses($customer->get_billing_address_1(), $customer->get_billing_address_2())
            );
        }
    }

    /**
     * Validate countryIso. Check if a given code represents a valid ISO 3166-1 alpha-2 code.
     *
     * @param $countryCode
     *
     * @return string
     */
    private function validateCountryCode($countryCode)
    {
        $countries = new WC_Countries();

        return $countries->country_exists($countryCode) ? $countryCode : '';
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
        return implode(', ', array_filter(array($address1, $address2)));
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
