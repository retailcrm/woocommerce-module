<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Order_Address_Test - Testing WC_Retailcrm_Order_Address.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
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

    public function test_build_address()
    {
        $order_address = new WC_Retailcrm_Order_Address();
        $data = $order_address->build($this->order)->getData();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertEquals('000000', $data['index']);
        $this->assertEquals('TestCity', $data['city']);
        $this->assertEquals('TestState', $data['region']);
        $this->assertEquals('TestAddress1 || TestAddress2', $data['text']);
    }

    public function test_empty_address()
    {
        $orderAddress = new WC_Retailcrm_Order_Address();

        $addressData = $orderAddress
        ->build(null)
        ->getData();

        $this->assertInternalType('array', $addressData);
        $this->assertEquals([], $addressData);
    }
}

