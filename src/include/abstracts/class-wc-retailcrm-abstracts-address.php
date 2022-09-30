<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Abstracts_Address - Builds data for addresses orders/customers.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
abstract class WC_Retailcrm_Abstracts_Address extends WC_Retailcrm_Abstracts_Data
{
    /**
     * Divider for order delivery address_1 and address_2
     */
    const ADDRESS_LINE_DIVIDER = ' || ';

    /**
     * Returns shipping address from order.
     *
     * @param \WC_Order $order
     *
     * @return array
     */
    protected function getOrderAddress($order)
    {
        if (!$order instanceof WC_Order) {
            return [];
        }

        return [
            'index'  => $order->get_shipping_postcode(),
            'city'   => $order->get_shipping_city(),
            'region' => $this->getRegion($order->get_shipping_country(), $order->get_shipping_state()),
            'text'   => $this->getText($order, 'order'),
        ];
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
        if (!$customer instanceof WC_Customer) {
            return [];
        }

        $customerBillingAddress = $customer->get_billing_address();

        if ($order instanceof WC_Order && empty($customerBillingAddress)) {
            return [
                'index' => $order->get_billing_postcode(),
                'countryIso' => $this->getCountryCode($order->get_billing_country()),
                'region' => $this->getRegion($order->get_billing_country(), $order->get_billing_state()),
                'city' => $order->get_billing_city(),
                'text' => $this->getText($order),
            ];
        } else {
            return [
                'index' => $customer->get_billing_postcode(),
                'countryIso' => $this->getCountryCode($customer->get_billing_country()),
                'region' => $this->getRegion($customer->get_billing_country(), $customer->get_billing_state()),
                'city' => $customer->get_billing_city(),
                'text' => $this->getText($customer),
            ];
        }
    }

    /**
     * Glue two addresses
     *
     * @param string $address1
     * @param string $address2
     *
     * @return string
     */
    protected function joinAddresses(string $address1 = '', string $address2 = '')
    {
        return implode(self::ADDRESS_LINE_DIVIDER, array_filter([$address1, $address2]));
    }

    /**
     * Validate countryIso. Check if a given code represents a valid ISO 3166-1 alpha-2 code.
     *
     * @param $countryCode
     *
     * @return string
     */
    private function getCountryCode($countryCode)
    {
        $countries = new WC_Countries();

        return $countries->country_exists($countryCode) ? $countryCode : '';
    }

    /**
     * Returns state name by it's code
     *
     * @param string $countryCode
     * @param string $stateCode
     *
     * @return string
     */
    protected function getRegion(string $countryCode, string $stateCode)
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

    /**
     * Returns data for CRM field 'text'.
     * If type entity equals 'order', get address for order and use shipping address,
     * else get address for customer and use billing address.
     *
     * @return string
     */
    protected function getText($wcEntity, $typeEntity = 'customer')
    {
        if ($typeEntity === 'order') {
            return empty($wcEntity->get_shipping_address_2())
                ? $wcEntity->get_shipping_address_1()
                : $this->joinAddresses($wcEntity->get_shipping_address_1(), $wcEntity->get_shipping_address_2());
        } else {
            return empty($wcEntity->get_billing_address_2())
                ? $wcEntity->get_billing_address_1()
                : $this->joinAddresses($wcEntity->get_billing_address_1(), $wcEntity->get_billing_address_2());
        }
    }
}
