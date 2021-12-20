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

class WC_Retailcrm_Customer_Corporate_Address_Test extends WC_Retailcrm_Test_Case_Helper
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

    public function test_build_and_reset_address()
    {
        $customer_address = new WC_Retailcrm_Customer_Corporate_Address();
        $data = $customer_address
            ->setIsMain(true)
            ->build($this->customer)
            ->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertArrayHasKey('countryIso', $data);
        $this->assertArrayHasKey('isMain', $data);
        $this->assertEquals('000000', $data['index']);
        $this->assertEquals('TestCity', $data['city']);
        $this->assertEquals('TestState', $data['region']);
        $this->assertEquals('TestAddress1, TestAddress2', $data['text']);
        $this->assertEquals('CO', $data['countryIso']);
        $this->assertEquals(true, $data['isMain']);

        // Check reset customer corporate address data
        $customer_address->reset_data();

        $data = $customer_address->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertEquals('', $data['index']);
        $this->assertEquals('', $data['city']);
        $this->assertEquals('', $data['region']);
        $this->assertEquals('', $data['text']);
    }

    public function test_build_not_main_company()
    {
        $customer_address = new WC_Retailcrm_Customer_Corporate_Address();
        $data = $customer_address
            ->setIsMain(false)
            ->build($this->customer)
            ->get_data();

        $this->assertArrayHasKey('index', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('region', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertArrayHasKey('countryIso', $data);
        $this->assertArrayHasKey('isMain', $data);
        $this->assertEquals('000000', $data['index']);
        $this->assertEquals('TestCity', $data['city']);
        $this->assertEquals('TestState', $data['region']);
        $this->assertEquals('TestAddress1, TestAddress2', $data['text']);
        $this->assertEquals('CO', $data['countryIso']);
        $this->assertEquals(false, $data['isMain']);
    }


    public function test_empty_address()
    {
        $customer_address = new WC_Retailcrm_Customer_Corporate_Address();
        $data = $customer_address
            ->setIsMain(false)
            ->build(null)
            ->get_data();

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

