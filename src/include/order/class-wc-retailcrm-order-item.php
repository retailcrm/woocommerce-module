<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Order_Item - Build items for CRM order.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Order_Item extends WC_Retailcrm_Abstracts_Data
{
    /**
     * @var array order item
     */
    protected $data = array(
        'offer' => array(),
        'productName' => '',
        'initialPrice' => 0.00,
        'quantity' => 0.00
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
        $data['quantity'] = (double)$item['qty'];

        $itemId = ($item['variation_id'] > 0) ? $item['variation_id'] : $item['product_id'];
        $data['externalIds'] = array(
            array(
                'code' =>'woocomerce',
                'value' => $itemId . '_' . $item->get_id(),
            )
        );

        $this->set_data_fields($data);
        $this->set_offer($item);
        $this->set_data_field('discountManualAmount', (float) round($discount_price, 2));

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

        $product = $item->get_product();

        if (!empty($product) &&
            isset($this->settings['bind_by_sku']) &&
            $this->settings['bind_by_sku'] == WC_Retailcrm_Base::YES
        ) {
            $offer['xmlId'] = $product->get_sku();
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
     * @todo Rounding issues with prices in pennies / cents should be expected here.
     */
    private function calculate_discount(WC_Order_Item_Product $item, $price)
    {
        $product_price = $item->get_total() ? $item->get_total() / $item->get_quantity() : 0;
        $product_tax  = $item->get_total_tax() ? $item->get_total_tax() / $item->get_quantity() : 0;
        $price_item = $product_price + $product_tax;
        $discount_price = $price - $price_item;

        return round($discount_price, 2);
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
            'quantity' => 0.00
        );
    }
}
