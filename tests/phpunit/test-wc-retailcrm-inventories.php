<?php

class WC_Retailcrm_Inventories_Test extends  WC_Unit_Test_Case
{
    protected $apiMock;
    protected $responseMock;
    protected $offer;

    public function setUp()
    {
        $this->offer = new WC_Product_Simple();
        $this->offer->save();

        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response')
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

        $this->apiMock->expects($this->any())
            ->method('storeInventories')
            ->willReturn($this->getTestData());

        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderLoadStocks
     */
    public function test_load_stocks($retailcrm)
    {
        $retailcrm_inventories = new WC_Retailcrm_Inventories($retailcrm);
        $retailcrm_inventories->load_stocks();
    }

    private function getTestData()
    {
        return array(
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
                    'externalId' => $this->offer->get_id(),
                    'xmlId' => 'xmlId',
                    'quantity' => 1
                )
            )
        );
    }

    public function dataProviderLoadStocks()
    {
        $this->setUp();

        return array(
            array(
                'retailcrm' => $this->apiMock
            ),
            array(
                'retailcrm' => false
            )
        );
    }
}