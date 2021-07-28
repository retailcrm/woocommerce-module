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

class WC_Retailcrm_Order_Test extends WC_Retailcrm_Test_Case_Helper {
    /** @var WC_Order */
    protected $order;

    public function setUp()
    {
        parent::setUp();

        $this->order = WC_Helper_Order::create_order();
    }

    public function test_reset_data()
    {
        $buildOrder = new WC_Retailcrm_Order($this->getOptions());
        $data = $buildOrder->build($this->order)->get_data();

        $this->assertNotEmpty($data);

        $buildOrder->reset_data();

        $this->assertEmpty(array_filter($buildOrder->get_data()));
    }

    public function test_empty_shipping_data()
    {
        $buildOrder = new WC_Retailcrm_Order($this->getOptions());
        $data = $buildOrder->build($this->order)->get_data();

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('lastName', $data);
        $this->assertEquals($this->order->get_billing_first_name(), $data['firstName']);
        $this->assertEquals($this->order->get_billing_last_name(), $data['lastName']);
    }

    public function test_empty_country_iso()
    {
        $buildOrder = new WC_Retailcrm_Order($this->getOptions());

        $this->order->set_shipping_country('');

        $data = $buildOrder->build($this->order)->get_data();

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('countryIso', $data);
        $this->assertNotEquals('', $data['countryIso']);
    }
}
