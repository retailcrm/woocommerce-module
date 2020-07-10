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
 * Class WC_Retailcrm_Order_Address_Test
 */
class WC_Retailcrm_Order_Address_Test extends WC_Retailcrm_Test_Case_Helper
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
        $order_address = new WC_Retailcrm_Order_Address();
        $data = $order_address
	        ->setWCAddressType(WC_Retailcrm_Abstracts_Address::ADDRESS_TYPE_BILLING)
	        ->build($this->order)
	        ->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
    }
}
