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

class WC_Retailcrm_Order_Item_Test extends WC_Retailcrm_Test_Case_Helper
{
    /** @var WC_Order */
    protected $order;

    public function setUp()
    {
        parent::setUp();

        $this->order = WC_Helper_Order::create_order();
    }

    public function test_build()
    {
        $order_item = new WC_Retailcrm_Order_Item($this->getOptions());

        /** @var WC_Order_Item_Product $item */
        foreach ($this->order->get_items() as $item) {
            $data = $order_item->build($item)->get_data();

            $this->assertArrayHasKey('productName', $data);
            $this->assertArrayHasKey('initialPrice', $data);
            $this->assertArrayHasKey('quantity', $data);
            $this->assertArrayHasKey('offer', $data);
        }
    }

    public function test_bind_by_sku()
    {
        $order_item = new WC_Retailcrm_Order_Item(['bind_by_sku' => 'yes']);

        foreach ($this->order->get_items() as $item) {
            $data = $order_item->build($item)->get_data();

            $this->assertArrayHasKey('offer', $data);
            $this->assertArrayHasKey('xmlId', $data['offer']);
        }
    }
}
