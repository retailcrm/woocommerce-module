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
    /** @var string $filter_name */
    protected $filter_name = 'order_address';

    /**
     * @param WC_Order $order
     *
     * @return self
     */
    public function build($order)
    {
        $address = $this->getOrderAddress($order);

        $postcode = isset($address['postcode']) ? $address['postcode'] : '';
        $city = isset($address['city']) ? $address['city'] : '';
        $state = isset($address['state']) ? $address['state'] : '';
        $country = isset($address['country']) ? $address['country'] : '';
        $region = $this->get_state_name($country, $state);
        $address_1 = isset($address['address_1']) ? $address['address_1'] : '';
        $address_2 = isset($address['address_2']) ? $address['address_2'] : '';

        if (!empty($address)) {
            $data = array(
                'index' => $postcode,
                'city' => $city,
                'region' => $region,
                'text' => sprintf(
                    '%s %s %s %s %s',
                    $postcode,
                    $state,
                    $city,
                    $address_1,
                    $address_2
                )
            );

            $this->set_data_fields($data);
        }

        return $this;
    }
}
