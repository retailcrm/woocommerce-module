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
     * @param WC_Customer $customer
     *
     * @return self
     */
    public function build($customer)
    {
        $data = array(
            'index' => $customer->get_billing_postcode(),
            'countryIso' => $customer->get_billing_country(),
            'region' => $this->get_state_name($customer->get_billing_country(), $customer->get_billing_state()),
            'city' => $customer->get_billing_city(),
            'text' => $customer->get_billing_address_1() . ', ' . $customer->get_billing_address_2()
        );

        $this->set_data_fields($data);

        return $this;
    }
}
