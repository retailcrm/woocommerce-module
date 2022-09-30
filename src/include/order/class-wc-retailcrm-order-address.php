<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Order_Address - Build address for CRM order.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Order_Address extends WC_Retailcrm_Abstracts_Address
{
    /**
     * @param WC_Order $order
     *
     * @return self
     */
    public function build($order)
    {
        $address = $this->getOrderAddress($order);

        if (!empty($address)) {
            $orderAddress = apply_filters(
                'retailcrm_process_order_address',
                WC_Retailcrm_Plugin::clearArray($address),
                $order
            );

            $this->setDataFields($orderAddress);
        } else {
            WC_Retailcrm_Logger::add('Error: Order address is empty');
        }

        return $this;
    }
}
