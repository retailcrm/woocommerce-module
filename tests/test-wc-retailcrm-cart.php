<?php

use datasets\DataCartRetailCrm;

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Cart_Test
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Cart_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiClientMock;
    protected $responseMock;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'isSuccessful',
                    'offsetExists',
                ]
            )
            ->getMock();

        $this->responseMock->setResponse(['id' => 1]);

        $this->apiClientMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'cartGet',
                    'cartSet',
                    'cartClear',
                ]
            )
            ->getMock();

        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
        $this->setMockResponse($this->responseMock, 'offsetExists', true);
        $this->setMockResponse($this->apiClientMock, 'cartSet', $this->responseMock);
        $this->setMockResponse($this->apiClientMock, 'cartClear', $this->responseMock);
        $this->setMockResponse($this->apiClientMock, 'cartGet',$this->responseMock);
    }

    public function testGetCart()
    {
        $this->responseMock->setResponse(DataCartRetailCrm::dataGetCart());
        $response = $this->apiClientMock->cartGet(1, 'test-site');
        $this->assertNotEmpty($response->__get('cart'));
        $this->assertTrue($response->__get('success'));

    }
    public function testSetCart()
    {
        $response = $this->apiClientMock->cartSet(DataCartRetailCrm::dataSetCart(), 'test-site');
        $this->assertEquals(1, $response->__get('id'));
    }

    public function testClearCart()
    {
        $response = $this->apiClientMock->cartClear(DataCartRetailCrm::dataClearCart(), 'test-site');
        $this->assertEquals(1, $response->__get('id'));
    }
}
