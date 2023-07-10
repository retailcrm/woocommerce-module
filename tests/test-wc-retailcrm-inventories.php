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
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMock, 'isSuccessful', true);

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(['storeInventories'])
            ->getMock();

        parent::setUp();
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

    private function checkProductData($retailcrm, $response, $offerId, $entity)
    {
        $product = wc_get_product($offerId);

        if ($retailcrm && null !== $response) {
            $this->assertInstanceOf('WC_Product', $product);
            $this->assertEquals($entity, $product->get_type());
            $this->assertEquals(50, $product->get_stock_quantity());
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
