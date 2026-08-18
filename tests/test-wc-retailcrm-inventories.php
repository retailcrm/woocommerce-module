<?php

use datasets\DataInventoriesRetailCrm;

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Inventories_Test - Testing WC_Retailcrm_Inventories.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Inventories_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;

    public function setUp()
    {
        parent::setUp();

        $options = $this->setOptions();
        $options['shipping_store_mapping'] = [
            'local_pickup:1' => 'main',
            'local_pickup:2' => 'missing',
        ];
        update_option(WC_Retailcrm_Base::$option_key, $options);

        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMock, 'isSuccessful', true);

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(['storeInventories'])
            ->getMock();

    }

    /**
     * @param $retailcrm
     * @param $response
     *
     * @dataProvider dataProviderLoadStocks
     */
    public function test_load_stocks_simple_product($retailcrm, $response)
    {
        $offer = WC_Helper_Product::create_simple_product();
        $offer->save();

        if (null !== $response) {
            $response['offers'][0]['externalId'] = $offer->get_id();
        }

        $this->responseMock->setResponse($response);

        if ($retailcrm) {
            $this->setMockResponse($retailcrm, 'storeInventories', $this->responseMock);
        }

        $retailcrm_inventories = new WC_Retailcrm_Inventories($retailcrm);
        $retailcrm_inventories->updateQuantity();

        $this->checkProductData($retailcrm, $response, $offer->get_id(), 'simple');
    }

    /**
     * @param $retailcrm
     * @param $response
     *
     * @dataProvider dataProviderLoadStocks
     */
    public function test_load_stocks_variation_product($retailcrm, $response)
    {
        $offer = WC_Helper_Product::create_variation_product();
        $offer->save();

        $childrens = $offer->get_children();

        if (null !== $response) {
            $response['offers'][0]['externalId'] = $childrens[0];
        }

        $this->responseMock->setResponse($response);

        if ($retailcrm) {
            $this->setMockResponse($retailcrm, 'storeInventories', $this->responseMock);
        }

        $retailcrm_inventories = new WC_Retailcrm_Inventories($retailcrm);
        $retailcrm_inventories->updateQuantity();

        $this->checkProductData($retailcrm, $response, $childrens[0], 'variation');
    }

    public function test_sync_off()
    {
        $options = $this->getOptions();
        $options['sync'] = 'no';

        update_option(WC_Retailcrm_Base::$option_key, $options);

        $retailcrm_inventories = new WC_Retailcrm_Inventories($this->apiMock);
        $result = $retailcrm_inventories->updateQuantity();

        $this->assertEquals(false, $result);
    }

    public function test_variation_store_stock_sum()
    {
        $variableProduct = WC_Helper_Product::create_variation_product();
        $children = $variableProduct->get_children();
        $response = DataInventoriesRetailCrm::getResponseData();
        $secondOffer = $response['offers'][0];
        $response['offers'][0]['externalId'] = $children[0];
        $secondOffer['externalId'] = $children[1];
        $response['offers'][] = $secondOffer;
        $this->responseMock->setResponse($response);
        $this->setMockResponse($this->apiMock, 'storeInventories', $this->responseMock);

        $inventories = new WC_Retailcrm_Inventories($this->apiMock);
        $inventories->updateQuantity();

        $parent = wc_get_product($variableProduct->get_id());
        $this->assertEquals(100, $parent->get_stock_quantity());
        $this->assertEquals(
            ['main' => 50, 'missing' => 0],
            $parent->get_meta(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, true)
        );
    }

    public function test_partial_sync_updates_parent_and_cache()
    {
        $variableProduct = WC_Helper_Product::create_variation_product();
        $children = $variableProduct->get_children();
        $firstPage = DataInventoriesRetailCrm::getResponseData();
        $firstPage['pagination']['totalPageCount'] = 2;
        $firstPage['offers'][0]['externalId'] = $children[0];

        $successfulResponse = clone $this->responseMock;
        $successfulResponse->setResponse($firstPage);
        $failedResponse = clone $this->responseMock;
        $failedResponse->setResponse(null);

        $this->apiMock->method('storeInventories')->willReturnOnConsecutiveCalls(
            $successfulResponse,
            $failedResponse
        );
        set_transient('shipping-transient-version', 'review-version');

        $inventories = new WC_Retailcrm_Inventories($this->apiMock);
        $inventories->updateQuantity();

        $parent = wc_get_product($variableProduct->get_id());
        $this->assertEquals(
            ['main' => 25, 'missing' => 0],
            $parent->get_meta(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, true)
        );
        $this->assertNotEquals(
            'review-version',
            WC_Cache_Helper::get_transient_version('shipping')
        );
    }

    private function checkProductData($retailcrm, $response, $offerId, $entity)
    {
        $product = wc_get_product($offerId);

        if ($retailcrm && null !== $response) {
            $this->assertInstanceOf('WC_Product', $product);
            $this->assertEquals($entity, $product->get_type());
            $this->assertEquals(50, $product->get_stock_quantity());
            $this->assertEquals(
                ['main' => 25, 'missing' => 0],
                $product->get_meta(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, true)
            );

            if ($entity === 'variation') {
                $parent = wc_get_product($product->get_parent_id());
                $this->assertEquals(
                    ['main' => 25, 'missing' => 0],
                    $parent->get_meta(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, true)
                );
            }
        } else {
            $this->assertNotEquals(50, $product->get_stock_quantity());
        }
    }

    public function dataProviderLoadStocks()
    {
        $this->setUp();

        $response = DataInventoriesRetailCrm::getResponseData();

        return array(
            array(
                'retailcrm' => $this->apiMock,
                'response' => $response
            ),
            array(
                'retailcrm' => false,
                'response' => $response
            ),
            array(
                'retailcrm' => $this->apiMock,
                'response' => null
            )
        );
    }
}
