<?php

/**
 * Class WC_Retailcrm_Shipping_Stock_Test.
 */
class WC_Retailcrm_Shipping_Stock_Test extends WC_Retailcrm_Test_Case_Helper
{
    public function setUp()
    {
        parent::setUp();
        $this->setOptions();
    }

    public function test_insufficient_stock()
    {
        $product = WC_Helper_Product::create_simple_product();
        $product->update_meta_data(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, ['main' => 2]);
        $product->save();

        $filter = new WC_Retailcrm_Shipping_Stock(['local_pickup:1' => 'main']);
        $rates = ['local_pickup:1' => new stdClass(), 'flat_rate:2' => new stdClass()];
        $package = ['contents' => [
            ['data' => $product, 'quantity' => 3],
        ]];

        $this->assertArrayNotHasKey('local_pickup:1', $filter->filter_rates($rates, $package));
        $this->assertArrayHasKey('flat_rate:2', $filter->filter_rates($rates, $package));
    }

    public function test_enough_stock()
    {
        $product = WC_Helper_Product::create_simple_product();
        $product->update_meta_data(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, ['main' => 3]);
        $product->save();

        $filter = new WC_Retailcrm_Shipping_Stock(['local_pickup:1' => 'main']);
        $rates = ['local_pickup:1' => new stdClass()];
        $package = ['contents' => [
            ['data' => $product, 'quantity' => 3],
        ]];

        $this->assertArrayHasKey('local_pickup:1', $filter->filter_rates($rates, $package));
    }

    public function test_no_stock_data()
    {
        $product = WC_Helper_Product::create_simple_product();
        $filter = new WC_Retailcrm_Shipping_Stock(['local_pickup:1' => 'main']);
        $rates = ['local_pickup:1' => new stdClass()];
        $package = ['contents' => [
            ['data' => $product, 'quantity' => 100],
        ]];

        $this->assertArrayHasKey('local_pickup:1', $filter->filter_rates($rates, $package));
    }

    public function test_duplicate_cart_lines()
    {
        $product = WC_Helper_Product::create_simple_product();
        $product->update_meta_data(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, ['main' => 3]);
        $product->save();

        $filter = new WC_Retailcrm_Shipping_Stock(['local_pickup:1' => 'main']);
        $rates = ['local_pickup:1' => new stdClass()];
        $package = ['contents' => [
            ['data' => $product, 'quantity' => 2],
            ['data' => $product, 'quantity' => 2],
        ]];

        $this->assertArrayNotHasKey('local_pickup:1', $filter->filter_rates($rates, $package));
    }

    public function test_any_product_unavailable()
    {
        $availableProduct = WC_Helper_Product::create_simple_product();
        $availableProduct->update_meta_data(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, ['main' => 5]);
        $availableProduct->save();
        $unavailableProduct = WC_Helper_Product::create_simple_product();
        $unavailableProduct->update_meta_data(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, ['main' => 0]);
        $unavailableProduct->save();

        $filter = new WC_Retailcrm_Shipping_Stock(['local_pickup:1' => 'main']);
        $rates = ['local_pickup:1' => new stdClass()];
        $package = ['contents' => [
            ['data' => $availableProduct, 'quantity' => 1],
            ['data' => $unavailableProduct, 'quantity' => 1],
        ]];

        $this->assertArrayNotHasKey('local_pickup:1', $filter->filter_rates($rates, $package));
    }

    public function test_missing_store()
    {
        $product = WC_Helper_Product::create_simple_product();
        $product->update_meta_data(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, ['other' => 10]);
        $product->save();

        $filter = new WC_Retailcrm_Shipping_Stock(['local_pickup:1' => 'main']);
        $rates = ['local_pickup:1' => new stdClass()];
        $package = ['contents' => [
            ['data' => $product, 'quantity' => 1],
        ]];

        $this->assertArrayHasKey('local_pickup:1', $filter->filter_rates($rates, $package));
    }
}
