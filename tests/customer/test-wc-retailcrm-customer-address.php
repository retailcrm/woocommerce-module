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

class WC_Retailcrm_Customer_Address_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $customer;

    public function setUp()
    {
        parent::setUp();

        $this->customer = WC_Helper_Customer::create_customer();
    }

    public function test_build()
    {
        $customer_address = new WC_Retailcrm_Customer_Address;
        $data = $customer_address->build($this->customer)->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertArrayHasKey('countryIso', $data);
        $this->assertNotEmpty($data['index']);
        $this->assertNotEmpty($data['city']);
        $this->assertNotEmpty($data['region']);
        $this->assertNotEmpty($data['text']);
        $this->assertNotEmpty($data['countryIso']);
    }
}
