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

    /** @var bool $explicitIsMain */
    protected $explicitIsMain;

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
        $address = $this->getCustomerAddress($customer, $order);

        if (!empty($address)) {
            if ($this->isMain) {
                $address['isMain'] = true;
            } elseif ($this->explicitIsMain) {
                $address['isMain'] = false;
            }

            $corporateCustomerAddress = apply_filters(
                'retailcrm_process_customer_corporate_address',
                WC_Retailcrm_Plugin::clearArray(array_merge(
                    $address,
                    array('isMain' => $address['isMain'])
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
