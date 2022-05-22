<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Icml_Test - Testing WC_Retailcrm_Icml.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Icml_Test extends WC_Retailcrm_Test_Case_Helper
{
    public function setUp()
    {
        WC_Helper_Product::create_simple_product();
        WC_Helper_Product::create_variation_product();
    }

    public function testGenerate()
    {
        $icml = new WC_Retailcrm_Icml();

        $icml->generate();
        $this->assertFileExists(ABSPATH . 'simla.xml');

        $xml = simplexml_load_file(ABSPATH . 'simla.xml');

        $this->assertNotEmpty($xml);

        $xmlArray = json_decode(json_encode($xml), true);

        $this->assertNotEmpty($xmlArray['shop']['categories']['category']);
        $this->assertCount(2, $xmlArray['shop']['categories']['category']);
        $this->assertNotEmpty($xmlArray['shop']['offers']['offer']);
        $this->assertCount(7, $xmlArray['shop']['offers']['offer']);
        $this->assertNotEmpty($xmlArray['shop']['offers']['offer'][0]);
        $this->assertNotEmpty($xmlArray['shop']['offers']['offer'][1]);

        foreach ($xmlArray['shop']['offers']['offer'] as $product) {
            $this->assertNotEmpty($product['name']);
            $this->assertNotEmpty($product['productName']);
            $this->assertNotEmpty($product['price']);
            $this->assertNotEmpty($product['url']);
            $this->assertNotEmpty($product['param']);
            $this->assertNotEmpty($product['vatRate']);
            $this->assertEquals('none', $product['vatRate']);
            $this->assertContains('Dummy', $product['productName']);
        }
    }
}
