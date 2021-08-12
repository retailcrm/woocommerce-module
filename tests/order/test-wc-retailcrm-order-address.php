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

        $this->order->set_shipping_postcode('000000');
        $this->order->set_shipping_state('TestState');
        $this->order->set_shipping_city('TestCity');
        $this->order->set_shipping_address_1('TestAddress1');
        $this->order->set_shipping_address_2('TestAddress2');
    }

    public function test_build_and_reset_address()
    {
        $order_address = new WC_Retailcrm_Order_Address();
        $data = $order_address->build($this->order)->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertEquals('000000', $data['index']);
        $this->assertEquals('TestCity', $data['city']);
        $this->assertEquals('TestState', $data['region']);
        $this->assertEquals('000000 TestState TestCity TestAddress1 TestAddress2', $data['text']);

        // Check reset order address data
        $order_address->reset_data();

        $data = $order_address->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertEquals('', $data['index']);
        $this->assertEquals('', $data['city']);
        $this->assertEquals('', $data['region']);
        $this->assertEquals('', $data['text']);
    }

    public function test_empty_address()
    {
        $order_address = new WC_Retailcrm_Order_Address();
        $data = $order_address->build(null)->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertEquals('', $data['index']);
        $this->assertEquals('', $data['city']);
        $this->assertEquals('', $data['region']);
        $this->assertEquals('', $data['text']);
    }
}

