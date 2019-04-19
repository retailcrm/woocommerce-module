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

class WC_Retailcrm_Order_Address extends WC_Retailcrm_Abstracts_Address
{
    protected $filter_name = 'order_address';

    /**
     * @param WC_Order $order
     *
     * @return self
     */
    public function build($order)
    {
        $address = $order->get_address('shipping');

        if (!empty($address)) {
            $data = array(
                'index' => $address['postcode'],
                'city' => $address['city'],
                'region' => $address['state']
            );

            $this->set_data_fields($data);
        }

        $this->set_data_field('text', sprintf(
            "%s %s %s %s %s",
            $address['postcode'],
            $address['state'],
            $address['city'],
            $address['address_1'],
            $address['address_2']
        ));

        return $this;
    }
}
