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
 * Class WC_Retailcrm_Order_Item
 */
class WC_Retailcrm_Order_Item extends WC_Retailcrm_Abstracts_Data
{
    protected $filter_name = 'order_item';

    /**
     * @var array order item
     */
    protected $data = array(
        'offer' => array(),
        'productName' => '',
        'initialPrice' => 0.00,
        'quantity' => 0
    );

    /**
     * @var array
     */
    protected $settings = array();

    /**
     * WC_Retailcrm_Order_Item constructor.
     *
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param WC_Order_Item_Product $item
     *
     * @return self
     */
    public function build($item)
    {
        $price = $this->calculate_price($item);
        $discount_price = $this->calculate_discount($item, $price);

        $data['productName'] = $item['name'];
        $data['initialPrice'] = (float)$price;
        $data['quantity'] = $item['qty'];

        $this->set_data_fields($data);
        $this->set_offer($item);

        if ($this->settings['api_version'] == 'v5' && round($discount_price, 2)) {
            $this->set_data_field('discountManualAmount',round($discount_price, 2));
        } elseif ($this->settings['api_version'] == 'v4' && round($discount_price, 2)) {
            $this->set_data_field('discount', round($discount_price, 2));
        }

        return $this;
    }

    /**
     * @param WC_Order_Item_Product $item
     *
     * @return void
     */
    private function set_offer(WC_Order_Item_Product $item)
    {
        $uid = ($item['variation_id'] > 0) ? $item['variation_id'] : $item['product_id'] ;
        $offer = array('externalId' => $uid);

        if (isset($this->settings['bind_by_sku']) && $this->settings['bind_by_sku'] == WC_Retailcrm_Base::YES) {
            $offer['xmlId'] = $item->get_product()->get_sku();
        }

        $this->set_data_field('offer', $offer);
    }

    /**
     * @param WC_Order_Item_Product $item
     *
     * @return float
     */
    private function calculate_price(WC_Order_Item_Product $item)
    {
        $price = ($item['line_subtotal'] / $item->get_quantity()) + ($item['line_subtotal_tax'] / $item->get_quantity());

        return round($price, 2);
    }

    /**
     * @param WC_Order_Item_Product $item
     * @param $price
     *
     * @return float|int
     */
    private function calculate_discount(WC_Order_Item_Product $item, $price)
    {
        $product_price = $item->get_total() ? $item->get_total() / $item->get_quantity() : 0;
        $product_tax  = $item->get_total_tax() ? $item->get_total_tax() / $item->get_quantity() : 0;
        $price_item = $product_price + $product_tax;
        $discount_price = $price - $price_item;

        return $discount_price;
    }

    /**
     * Reset data for object
     */
    public function reset_data()
    {
        $this->data = array(
            'offer' => array(),
            'productName' => '',
            'initialPrice' => 0.00,
            'quantity' => 0
        );
    }
}
