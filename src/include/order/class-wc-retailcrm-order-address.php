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
	public function build( $order )
	{
		$address = $order->get_address($this->address_type);

		$address = apply_filters( 'wc_retail_crm_order_address', $address, $order, $this->address_type );

        if (!empty($address)) {
            $data = array(
                'index' => $address['postcode'],
                'city' => $address['city'],
                'region' => $this->get_state_name($address['country'], $address['state'])
            );

            $this->set_data_fields($data);
        }

		$formatted = sprintf(
			"%s %s %s %s %s",
			$address['postcode'],
			$address['state'],
			$address['city'],
			$address['address_1'],
			$address['address_2']
		);

		$formatted = apply_filters( 'wc_retail_crm_formatted_address', $formatted, $address );

		$this->set_data_field( 'text', $formatted );

        return $this;
    }
}
