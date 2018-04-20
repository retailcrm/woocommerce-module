<?php

class WC_Retailcrm_Inventories_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;
    protected $offer;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'storeInventories'
            ))
            ->getMock();

        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @param $response
     *
     * @dataProvider dataProviderLoadStocks
     */
    public function test_load_stocks($retailcrm, $response)
    {
        $offer = WC_Helper_Product::create_simple_product();
        $offer->save();

        if ($response['success'] == true) {
            $response['offers'][0]['externalId'] = $offer->get_id();
            $this->responseMock->expects($this->any())
                ->method('isSuccessful')
                ->willReturn(true);
        } elseif ($response['success'] == false) {
            $this->responseMock->expects($this->any())
                ->method('isSuccessful')
                ->willReturn(false);
        }

        $this->responseMock->setResponse($response);

        if ($retailcrm) {
            $retailcrm->expects($this->any())
                ->method('storeInventories')
                ->willReturn($this->responseMock);
        }

        $retailcrm_inventories = new WC_Retailcrm_Inventories($retailcrm);
        $result = $retailcrm_inventories->load_stocks();

        if ($retailcrm && $response['success'] == true) {
            $product = new WC_Product_Simple($result[0]);
            $this->assertInstanceOf('WC_Product', $product);
            $this->assertEquals(10, $product->get_stock_quantity());
            $this->assertContains($product->get_id(), $result);
            $this->assertInternalType('array', $result);
        } else {
            $this->assertEquals(null, $result);
        }
    }

    private function getResponseData()
    {
        return array(
            'true' => array(
                'success' => true,
                'pagination' => array(
                    'limit' => 250,
                    'totalCount' => 1,
                    'currentPage' => 1,
                    'totalPageCount' => 1
                ),
                'offers' => array(
                    array(
                        'id' => 1,
                        'xmlId' => 'xmlId',
                        'quantity' => 10
                    )
                )
            ),
            'false' => array(
                'success' => false,
                'errorMsg' => 'Forbidden'
            )
        );
    }

    public function dataProviderLoadStocks()
    {
        $this->setUp();

        $response = $this->getResponseData();

        return array(
            array(
                'retailcrm' => $this->apiMock,
                'response' => $response['true']
            ),
            array(
                'retailcrm' => false,
                'response' => $response['true']
            ),
            array(
                'retailcrm' => $this->apiMock,
                'response' => $response['false']
            ),
            array(
                'retailcrm' => false,
                'response' => $response['false']
            )
        );
    }
}
