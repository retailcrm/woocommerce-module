<?php

/**
 * PHP version 7.0
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

        $this->createVirtualProduct();
        $this->setOptions();
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
        $this->assertNotEmpty($xmlArray['shop']['offers']['offer'][0]);
        $this->assertNotEmpty($xmlArray['shop']['offers']['offer'][1]);
        $this->assertNotEmpty($xmlArray['shop']['offers']['offer'][2]);

        foreach ($xmlArray['shop']['offers']['offer'] as $product) {
            $this->assertNotEmpty($product['name']);
            $this->assertNotEmpty($product['productName']);
            $this->assertNotEmpty($product['price']);
            $this->assertNotEmpty($product['url']);
            $this->assertNotEmpty($product['param']);
            $this->assertNotEmpty($product['vatRate']);
            $this->assertEquals('none', $product['vatRate']);
            $this->assertContains('Dummy', $product['productName']);
            $this->assertNotEmpty($product['@attributes']['type']);
        }

        $attributesList = array_column($xmlArray['shop']['offers']['offer'], '@attributes');
        $typeList = array_column($attributesList, 'type');

        $this->assertContains('service', $typeList);
    }

    private function createVirtualProduct()
    {
        $product = wp_insert_post( array(
            'post_title'  => 'Dummy Product',
            'post_type'   => 'product',
            'post_status' => 'publish',
        ) );
        update_post_meta( $product, '_price', '10' );
        update_post_meta( $product, '_regular_price', '10' );
        update_post_meta( $product, '_sale_price', '' );
        update_post_meta( $product, '_sku', 'DUMMY SKU' );
        update_post_meta( $product, '_manage_stock', 'no' );
        update_post_meta( $product, '_tax_status', 'taxable' );
        update_post_meta( $product, '_downloadable', 'no' );
        update_post_meta( $product, '_virtual', 'yes' );
        update_post_meta( $product, '_stock_status', 'instock' );
        update_post_meta( $product, '_weight', '1.1' );
        wp_set_object_terms( $product, 'simple', 'product_type' );

        return new WC_Product_Simple( $product );
    }
}
