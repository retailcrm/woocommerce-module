<?php

use datasets\DataUploadPriceRetailCrm;

/**
 * PHP version 7.0
 *
 * WC_Retailcrm_Upload_Discount_Price_Test
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Upload_Discount_Price_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;

    public function setUp()
    {
        WC_Helper_Product::create_simple_product();
        WC_Helper_Product::create_variation_product();

        $this->setOptions();

        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock()
        ;

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods(['storePricesUpload', 'getSingleSiteForKey', 'getPriceTypes', 'editPriceType'])
            ->getMock()
        ;

        $this->responseMock->setResponse(['success' => true]);
        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
        $this->setMockResponse($this->apiMock, 'getSingleSiteForKey', 'woo');

        $this->responseMock->setResponse(DataUploadPriceRetailCrm::dataGetPriceTypes());
        $this->setMockResponse($this->apiMock, 'getPriceTypes', $this->responseMock);
    }

    public function testUpload()
    {
        $this->apiMock
            ->expects($this->exactly(1))
            ->method('storePricesUpload')
            ->with($this->callback(
                function ($parameter) {
                    if (is_array($parameter)) {
                        return true;
                    }

                    return false;
                }
            ), $this->equalTo('woo'))
        ;

        $this->apiMock
            ->expects($this->exactly(1))
            ->method('editPriceType')
            ->with($this->identicalTo(DataUploadPriceRetailCrm::willSendPriceType()))
            ->willReturn($this->responseMock)
        ;

        $uploadService = new WC_Retailcrm_Upload_Discount_Price($this->apiMock);
        $uploadService->upload();
    }
}
