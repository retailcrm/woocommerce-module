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

    /** @var bool */
    public $cancelLoyalty = false;

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
    public function build($item, $crmItem = null)
    {
        $decimalPlaces = wc_get_price_decimals();

        // Calculate price and discount
        $price         = $this->calculatePrice($item, $decimalPlaces);
        $discountPrice = $this->calculateDiscount($item, $price, $decimalPlaces, $crmItem);

        $data['productName'] = $item['name'];
        $data['initialPrice'] = $price;
        $data['quantity'] = (float) $item['qty'];

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
        if (isset($this->settings['loyalty']) && $this->settings['loyalty'] === WC_Retailcrm_Base::YES) {
            $product = $item->get_product();
            $price = wc_get_price_including_tax($product, ["price" => $product->get_regular_price()]);
        } else {
            $price = ($item['line_subtotal'] / $item->get_quantity()) + ($item['line_subtotal_tax'] / $item->get_quantity());
        }

        return round($price, $decimalPlaces);
    }

    /**
     * @param WC_Order_Item_Product $item
     * @param $price
     * @param int $decimalPlaces Price rounding from WC settings
     * @param array|null $crmItem Current trade position in CRM
     * @return float|int
     */
    private function calculateDiscount(
        WC_Order_Item_Product $item,
        $price,
        int $decimalPlaces,
        $crmItem = null
    ) {

        if ($crmItem && isset($this->settings['loyalty']) && $this->settings['loyalty'] === WC_Retailcrm_Base::YES) {
            $loyaltyDiscount = 0;

            foreach ($crmItem['discounts'] as $discount) {
                if (in_array($discount['type'], ['bonus_charge', 'loyalty_level'])) {
                    $loyaltyDiscount += $discount['amount'];

                    break;
                }
            }

            /**
             * The loyalty program discount is calculated within the CRM system. It must be deleted during transfer to avoid duplication.
             */
            $productPrice = ($item->get_total() / $item->get_quantity()) + ($loyaltyDiscount / $crmItem['quantity']);

            if ($this->cancelLoyalty) {
                if ($item->get_total() + $loyaltyDiscount <= $item->get_subtotal()) {
                    $item->set_total($item->get_total() + $loyaltyDiscount);
                    $item->calculate_taxes();
                    $item->save();
                }

                $productPrice = $item->get_total() / $item->get_quantity();
            }
        } else {
            $productPrice = $item->get_total() ? $item->get_total() / $item->get_quantity() : 0;
        }

        $productTax = $item->get_total_tax() ? $item->get_total_tax() / $item->get_quantity() : 0;
        $itemPrice = $productPrice + $productTax;

        return round($price - $itemPrice, $decimalPlaces);
    }

    /**
     * Reset item data.
     */
    public function resetData($cancelLoyalty)
    {
        $this->data = [
            'offer' => [],
            'productName' => '',
            'initialPrice' => 0.00,
            'quantity' => 0.00
        ];

        $this->cancelLoyalty = $cancelLoyalty;
    }

    /**
     * Checking whether the loyalty program discount needs to be canceled. (Changing the sales items in the order)
     *
     * @param array $wcItems
     * @param array $crmItems
     *
     * @return bool
     */
    public function isCancelLoyalty($wcItems, $crmItems): bool
    {
        /** If the number of sales items does not match */
        if (count($wcItems) !== count($crmItems)) {
            $this->cancelLoyalty = true;

            return true;
        }

        foreach ($wcItems as $id => $item) {
            $loyaltyDiscount = 0;

            /** If a trading position has been added/deleted */
            if (!isset($crmItems[$id])) {
                $this->cancelLoyalty = true;

                return true;
            }

            /** If the quantity of goods in a trade item does not match */
            if ($item->get_quantity() !== $crmItems[$id]['quantity']) {
                $this->cancelLoyalty = true;

                return true;
            }

            foreach ($crmItems[$id]['discounts'] as $discount) {
                if (in_array($discount['type'], ['bonus_charge', 'loyalty_level'])) {
                    $loyaltyDiscount = $discount['amount'];

                    break;
                }
            }

            /**
             * If the sum of the trade item including discounts and loyalty program discount exceeds the cost without discounts.
             * (Occurs when recalculating an order, deleting/adding coupons)
             */
            if (($item->get_total() + $loyaltyDiscount) > $item->get_subtotal()) {
                $this->cancelLoyalty = true;

                return true;
            }
        }

        return false;
    }
}
