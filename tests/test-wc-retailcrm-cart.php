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
    protected $cart;
    protected $apiMock;
    protected $responseMock;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods(['cartGet', 'cartSet', 'cartClear'])
            ->getMock();

        $this->responseMock->setResponse(['success' => true, ]);
        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
        $this->setMockResponse($this->apiMock, 'cartGet', ['cart' => ['externalId' => 1]]);
        $this->setMockResponse($this->apiMock, 'cartSet', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'cartClear', $this->responseMock);

        $this->cart = new WC_Retailcrm_Cart($this->apiMock, $this->getOptions());
    }

    public function testApiGetCart()
    {
        $this->responseMock->setResponse(DataCartRetailCrm::dataGetCart());

        $response = $this->apiMock->cartGet(1, 'test-site');

        $this->assertNotEmpty($response['cart']);
        $this->assertNotEmpty($response['cart']['externalId']);
        $this->assertEquals(1, $response['cart']['externalId']);
    }

    public function testApiSetCart()
    {
        $response = $this->apiMock->cartSet(DataCartRetailCrm::dataSetCart(), 'test-site');

        $this->assertNotEmpty($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testApiClearCart()
    {
        $response = $this->apiMock->cartClear(DataCartRetailCrm::dataClearCart(), 'test-site');

        $this->assertNotEmpty($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testSetCart()
    {
        $wcCart = new WC_Cart();
        $product = WC_Helper_Product::create_simple_product();
        $customerId = wc_create_new_customer('mail_test@mail.es', 'test');

        $wcCart->add_to_cart($product->get_id(), 1, 0, [], []);

        $this->assertTrue($this->cart->processCart($customerId, $wcCart->get_cart(), 'woo', true));
    }

    public function testGetCart()
    {
        $this->assertTrue($this->cart->isCartExist(1, 'woo'));
    }

    public function testClearCart()
    {
        $this->assertTrue($this->cart->clearCart(1, 'woo', true));
    }
}
