<?php

use datasets\DataBaseRetailCrm;

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_client_v5_Test - Testing WC_Retailcrm_client_v5
 *
 * @category Integration
 * @author RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_client_V5 extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiClientMock;
    protected $responseMock;


    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods([
                'isSuccessful',
                'offsetExists'
            ])
            ->getMock();

        $this->responseMock->setResponse(['id' => 1]);

        $this->apiClientMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods([
                'cartGet',
                'cartSet',
                'cartClear'
            ])
            ->getMock();

        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
        $this->setMockResponse($this->responseMock, 'offsetExists', true);
        $this->setMockResponse($this->apiClientMock, 'cartGet',$this->responseMock);
        $this->setMockResponse($this->apiClientMock, 'cartSet', $this->responseMock);
        $this->setMockResponse($this->apiClientMock, 'cartClear', $this->responseMock);
    }

    public function testSetCart()
    {
        $response = $this->apiClientMock->cartSet(array(), 'test-site');
        $this->assertEquals(1,$response->__get('id'));
    }

    public function testGetCart()
    {
        $response = $this->apiClientMock->cartGet(1, 'test-site');
        $this->assertEquals(1, $response->__get('id'));

    }

    public function testClearCart()
    {
        $response = $this->apiClientMock->cartClear(array(), 'test-site');
        $this->assertEquals(1, $response->__get('id'));
    }
}