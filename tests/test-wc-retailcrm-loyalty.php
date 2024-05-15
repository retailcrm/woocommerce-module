<?php

use DataLoyaltyRetailCrm;

if (!class_exists('WC_Retailcrm_Loyalty')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('class-wc-retailcrm-loyalty.php'));
}

if (!class_exists('WC_Retailcrm_Response')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-response.php'));
}


/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Loyalty_Test
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Loyalty_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $responseMock;
    protected $apiMock;

    /** @var \WC_Retailcrm_Loyalty */
    protected $loyalty;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock()
        ;

        $this->responseMock->setResponse(['success' => true]);
        $this->setMockResponse($this->responseMock, 'isSuccessful', true);

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods(['customersGet', 'getLoyaltyAccountList', 'createLoyaltyAccount', 'activateLoyaltyAccount', 'calculateDiscountLoyalty', 'getSingleSiteForKey'])
            ->getMock()
        ;

        $this->setMockResponse($this->apiMock, 'customersGet', ['customer' => ['id' => 1]]);
        $this->setMockResponse($this->apiMock, 'createLoyaltyAccount', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'activateLoyaltyAccount', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'getSingleSiteForKey', 'woo');

        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);
    }

    /**
     * @dataProvider DataLoyaltyRetailCrm::getDataLoyalty()
     */
    public function testGetForm($isSuccessful, $body, $expected)
    {
        $response = new WC_Retailcrm_Response($isSuccessful ? 200 : 400, $body);

        $this->setMockResponse($this->apiMock, 'getLoyaltyAccountList', $response);
        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);

        $result = $this->loyalty->getForm(1);

        if (isset($result['form'])) {
            $this->assertTrue((bool) stripos($result['form'], $expected));
        }
    }

    public function testRegistrationLoyalty()
    {
        $result = $this->loyalty->registerCustomer(1, '89999999999', 'test');

        $this->assertTrue($result);
    }

    public function testActivateLoyalty()
    {
        $result = $this->loyalty->activateLoyaltyCustomer(1);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider DataLoyaltyRetailCrm::getDataCalculation()
     */
    public function testGetDiscountLp($response, $expected)
    {
        $responseMock = new WC_Retailcrm_Response(200, json_encode($response));
        $this->setMockResponse($this->apiMock, 'calculateDiscountLoyalty', $responseMock);

        $cartItems = $this->createCart();

        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);

        $method = $this->getPrivateMethod('getDiscountLp', $this->loyalty);

        $discount = $method->invokeArgs($this->loyalty, [$cartItems, 'test', 1]);
        $this->assertEquals($expected, $discount);

        foreach ($cartItems as $items) {
            $items['data']->delete(true);
        }
    }

    /**
     * @dataProvider DataLoyaltyRetailCrm::dataCheckLoyaltyCoupon()
     */
    public function testIsLoyaltyCoupon($code, $expected)
    {
        $this->assertEquals($expected, $this->loyalty->isLoyaltyCoupon($code));
    }

    /**
     * @dataProvider DataLoyaltyRetailCrm::dataValidUser();
     */
    public function testIsValidUser($customer, $corporate_enabled, $expected)
    {
        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, ['corporate_enabled' => $corporate_enabled]);

        $this->assertEquals($expected, $this->loyalty->isValidUser($customer));
    }

    /**
     * @dataProvider DataLoyaltyRetailCrm::dataGetEmailsForPersonalCoupon()
     */
    public function testGetCouponLoyalty($email, $expectedCode)
    {
        $coupons = $this->loyalty->getCouponLoyalty($email);

        if (!$coupons && $expectedCode === false) {
            $this->assertTrue(true);
        } else {
            $result = false;

            foreach ($coupons as $item) {
                $coupon = new WC_Coupon($item['code']);
                $result = true;

                $this->assertTrue($expectedCode === $item['code']);
                $coupon->delete(true);
            }

            $this->assertTrue($result);
        }
    }

    public function testCreateLoyaltyCouponWithoutAppliedCoupon()
    {
        $products = DataLoyaltyRetailCrm::createProducts();
        $user = DataLoyaltyRetailcrm::createUsers()[0];

        $cart = new WC_Cart();
        $cart->add_to_cart($products[0]->get_id());
        $cart->add_to_cart($products[1]->get_id());

        $woocommerce = wc();
        $woocommerce->cart = $cart;
        $woocommerce->customer = $user;

        $validatorMock = $this->getMockBuilder('\WC_Retailcrm_Loyalty_Validator')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->setMockResponse($validatorMock, 'checkAccount', true);
        $validatorMock->loyaltyAccount['level']['type'] = 'loyalty_level';

        $responseCalculation = DataLoyaltyRetailCrm::getDataCalculation()[1];
        $responseMock = new WC_Retailcrm_Response(200, json_encode($responseCalculation['response']));
        $this->setMockResponse($this->apiMock, 'calculateDiscountLoyalty', $responseMock);

        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);
        $reflection = new \ReflectionClass($this->loyalty);
        $reflection_property = $reflection->getProperty('validator');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->loyalty, $validatorMock);

        $GLOBALS['woocommerce'] = $woocommerce;
        $result = $this->loyalty->createLoyaltyCoupon();

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($this->loyalty->getCouponLoyalty($woocommerce->customer->get_email()));
    }

    public function testCreateLoyaltyCouponWithPercentDiscount()
    {
        $products = DataLoyaltyRetailCrm::createProducts();
        $user = DataLoyaltyRetailcrm::createUsers()[0];

        $cart = new WC_Cart();
        $cart->add_to_cart($products[0]->get_id());
        $cart->add_to_cart($products[1]->get_id());

        $woocommerce = wc();
        $woocommerce->cart = $cart;
        $woocommerce->customer = $user;

        $validatorMock = $this->getMockBuilder('\WC_Retailcrm_Loyalty_Validator')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->setMockResponse($validatorMock, 'checkAccount', true);
        $validatorMock->loyaltyAccount['level']['type'] = 'discount';

        $responseCalculation = DataLoyaltyRetailCrm::getDataCalculation()[1];
        $responseMock = new WC_Retailcrm_Response(200, json_encode($responseCalculation['response']));
        $this->setMockResponse($this->apiMock, 'calculateDiscountLoyalty', $responseMock);

        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);
        $reflection = new \ReflectionClass($this->loyalty);
        $reflection_property = $reflection->getProperty('validator');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->loyalty, $validatorMock);

        $GLOBALS['woocommerce'] = $woocommerce;
        $result = $this->loyalty->createLoyaltyCoupon();

        $this->assertEmpty($result);
        $this->assertNotEmpty($this->loyalty->getCouponLoyalty($woocommerce->customer->get_email()));
        $this->assertNotEmpty($woocommerce->cart->get_coupons());
    }

    public function testCreateLoyaltyCouponWithRefreshCoupon()
    {

    }



    private function getPrivateMethod($method, $class)
    {
        $reflection = new ReflectionClass($class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    private function createCart()
    {
        $products = DataLoyaltyRetailCrm::createProducts();

        return [
            [
                'data' => $products[0],
                'quantity' => 1,
                'line_subtotal' => $products[0]->get_regular_price(),
                'line_total' => $products[0]->get_regular_price(),//When sale_price identical regular price, sale is empty
            ],
            [
                'data' => $products[1],
                'quantity' => 3,
                'line_subtotal' => $products[1]->get_regular_price() * 3,
                'line_total' => $products[1]->get_sale_price() * 3,
            ],
        ];
    }
}
