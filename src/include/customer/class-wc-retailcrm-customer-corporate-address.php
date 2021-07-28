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
    /** @var bool $isMain */
    protected $isMain = true;

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
     * @param WC_Customer    $customer
     * @param \WC_Order|null $order
     *
     * @return self
     */
    public function build($customer, $order = null)
    {
        $address = $this->getCustomerAddress($customer, $order);

        if (!empty($address)) {
            $address['isMain'] = $this->isMain;

            $corporateCustomerAddress = apply_filters(
                'retailcrm_process_customer_corporate_address',
                WC_Retailcrm_Plugin::clearArray(array_merge(
                    $address,
                    array('isMain' => $this->isMain)
                )),
                $customer
            );

            $this->set_data_fields($corporateCustomerAddress);
        } else {
            WC_Retailcrm_Logger::add('Error Corporate Customer address is empty');
        }

        return $this;
    }
}
