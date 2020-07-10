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
class WC_Retailcrm_Customer_Address extends WC_Retailcrm_Abstracts_Address
{
    protected $filter_name = 'customer_address';

    /**
     * @param WC_Customer    $customer
     * @param \WC_Order|null $order
     *
     * @return self
     */
    public function build($customer, $order = null)
    {
        $customerBillingAddress = $customer->get_billing_address();

        if ($order instanceof WC_Order && empty($customerBillingAddress)) {
            $data = array(
                'index' => $order->get_billing_postcode(),
                'countryIso' => $order->get_billing_country(),
                'region' => $this->get_state_name($order->get_billing_country(), $order->get_billing_state()),
                'city' => $order->get_billing_city(),
                'text' => $order->get_billing_address_1() . ', ' . $order->get_billing_address_2()
            );
        } else {
            $data = array(
                'index' => $customer->get_billing_postcode(),
                'countryIso' => $customer->get_billing_country(),
                'region' => $this->get_state_name($customer->get_billing_country(), $customer->get_billing_state()),
                'city' => $customer->get_billing_city(),
                'text' => $customer->get_billing_address_1() . ', ' . $customer->get_billing_address_2()
            );
        }

        $this->set_data_fields($data);

        return $this;
    }
}
