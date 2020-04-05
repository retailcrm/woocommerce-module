<?php

class WC_Retailcrm_Icml_Test extends WC_Retailcrm_Test_Case_Helper
{
    public function setUp()
    {
        for ($i = 0; $i < 10; $i++) {
            WC_Helper_Product::create_simple_product();
        }

        WC_Helper_Product::create_variation_product();
    }

    public function testGenerate()
    {
        $icml = new WC_Retailcrm_Icml();
        $icml->generate();

        $this->assertFileExists(ABSPATH . 'retailcrm.xml');
        $xml = simplexml_load_file(ABSPATH . 'retailcrm.xml');
        $res = $xml->xpath('/yml_catalog/shop/categories/category[@id]');

        $this->assertNotEmpty($res);

        foreach ($res as $node) {
            $this->assertEquals('category', $node->getName());
        }
    }
}
