<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Customer_Address - Builds a billing address for a customer.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Customer_Address extends WC_Retailcrm_Abstracts_Address
{
    /**
     * @param WC_Customer    $customer
     * @param \WC_Order|null $order
     *
     * @return self
     */
    public function build($customer, $order = null)
    {
        $address = $this->getCustomerAddress($customer, $order);

        if (!empty($address)) {
            $customerAddress = apply_filters(
                'retailcrm_process_customer_address',
                WC_Retailcrm_Plugin::clearArray($address),
                $customer,
                $order
            );

            $this->setDataFields($customerAddress);
        } else {
            WC_Retailcrm_Logger::add('Error Customer address is empty');
        }

        return $this;
    }
}
