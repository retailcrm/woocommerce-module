<?php

/**
 * PHP version 7.0
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
    protected $data = [
        'offer' => [],
        'productName' => '',
        'initialPrice' => 0.00,
        'quantity' => 0.00
    ];

    /**
     * @var array
     */
    protected $settings = [];

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
        $decimalPlaces = wc_get_price_decimals();

        // Calculate price and discount
        $price         = $this->calculatePrice($item, $decimalPlaces);
        $discountPrice = $this->calculateDiscount($item, $price, $decimalPlaces);

        $data['productName']  = $item['name'];
        $data['initialPrice'] = $price;
        $data['quantity']     = (double)$item['qty'];

        $itemId = ($item['variation_id'] > 0) ? $item['variation_id'] : $item['product_id'];
        $data['externalIds'] = [
            [
                'code' => 'woocomerce',
                'value' => $itemId . '_' . $item->get_id(),
            ]
        ];

        $this->setDataFields($data);
        $this->setOffer($item);
        $this->setField('discountManualAmount', $discountPrice);

        return $this;
    }

    /**
     * @param WC_Order_Item_Product $item
     *
     * @return void
     */
    private function setOffer(WC_Order_Item_Product $item)
    {
        $uid   = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'] ;
        $offer = ['externalId' => $uid];

        $product = $item->get_product();

        if (
            !empty($product)
            && isset($this->settings['bind_by_sku'])
            && $this->settings['bind_by_sku'] == WC_Retailcrm_Base::YES
        ) {
            $offer['xmlId'] = $product->get_sku();

            unset($offer['externalId']);
        }

        $this->setField('offer', $offer);
    }

    /**
     * @param WC_Order_Item_Product $item
     * @param int $decimalPlaces Price rounding from WC settings
     *
     * @return float
     */
    private function calculatePrice(WC_Order_Item_Product $item, int $decimalPlaces)
    {
        $price = ($item['line_subtotal'] / $item->get_quantity()) + ($item['line_subtotal_tax'] / $item->get_quantity());

        return round($price, $decimalPlaces);
    }

    /**
     * @param WC_Order_Item_Product $item
     * @param $price
     * @param int $decimalPlaces Price rounding from WC settings
     *
     * @return float|int
     */
    private function calculateDiscount(WC_Order_Item_Product $item, $price, int $decimalPlaces)
    {
        $productPrice  = $item->get_total() ? $item->get_total() / $item->get_quantity() : 0;
        $productTax    = $item->get_total_tax() ? $item->get_total_tax() / $item->get_quantity() : 0;
        $itemPrice     = $productPrice + $productTax;

        return round($price - $itemPrice, $decimalPlaces);
    }

    /**
     * Reset item data.
     */
    public function resetData()
    {
        $this->data = [
            'offer' => [],
            'productName' => '',
            'initialPrice' => 0.00,
            'quantity' => 0.00
        ];
    }
}
