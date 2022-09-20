<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Customer_Address_Test - Testing WC_Retailcrm_Customer_Address.
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

        $this->customer->set_billing_country('CO');
        $this->customer->set_billing_postcode('000000');
        $this->customer->set_billing_state('TestState');
        $this->customer->set_billing_city('TestCity');
        $this->customer->set_billing_address_1('TestAddress1');
        $this->customer->set_billing_address_2('TestAddress2');
    }

    public function test_build_address()
    {
        $customer_address = new WC_Retailcrm_Customer_Address();
        $data = $customer_address->build($this->customer)->getData();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertArrayHasKey('countryIso', $data);
        $this->assertEquals('000000', $data['index']);
        $this->assertEquals('TestCity', $data['city']);
        $this->assertEquals('TestState', $data['region']);
        $this->assertEquals('TestAddress1 || TestAddress2', $data['text']);
        $this->assertEquals('CO', $data['countryIso']);
    }

    public function test_empty_address()
    {
        $customerAddress = new WC_Retailcrm_Customer_Address();

        $addressData = $customerAddress
            ->build(null)
            ->getData();

        $this->assertInternalType('array', $addressData);
        $this->assertEquals([], $addressData);
    }
}

