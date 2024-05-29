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
            ->setMethods(['customersGet', 'getLoyaltyAccountList', 'createLoyaltyAccount', 'activateLoyaltyAccount', 'calculateDiscountLoyalty', 'getSingleSiteForKey', 'applyBonusToOrder'])
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
    public function testGetDiscountLoyalty($response, $expected)
    {
        $responseMock = new WC_Retailcrm_Response(200, json_encode($response));
        $this->setMockResponse($this->apiMock, 'calculateDiscountLoyalty', $responseMock);

        $cartItems = $this->createCart();

        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);

        $method = $this->getPrivateMethod('getDiscountLoyalty', $this->loyalty);

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
    public function testIsValidOrder($customer, $corporate_enabled, $expected, $orderCorporate)
    {
        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, ['corporate_enabled' => $corporate_enabled]);
        $wcOrder = new WC_Order();

        if ($orderCorporate) {
            $wcOrder->set_billing_company('OOO TEST');
        }

        $this->assertEquals($expected, $this->loyalty->isValidOrder($customer, $wcOrder));
    }

    /**
     * @dataProvider DataLoyaltyRetailCrm::dataGetEmailsForPersonalCoupon()
     */
    public function testGetCouponLoyalty($email, $code)
    {
        if ($code) {
            $coupon = new WC_Coupon();

            $coupon->set_usage_limit(0);
            $coupon->set_amount(100);
            $coupon->set_email_restrictions($email);
            $coupon->set_code($code);
            $coupon->save();
        }

        $coupons = $this->loyalty->getCouponLoyalty($email);

        if (!$coupons && $code === false) {
            $this->assertTrue(true);
        } else {
            $result = false;

            foreach ($coupons as $item) {
                $coupon = new WC_Coupon($item['code']);
                $result = true;

                $this->assertTrue($code === $item['code']);
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
        $products = DataLoyaltyRetailCrm::createProducts();
        $user = DataLoyaltyRetailcrm::createUsers()[0];

        $cart = new WC_Cart();
        $cart->add_to_cart($products[0]->get_id());
        $cart->add_to_cart($products[1]->get_id());

        $coupon = new WC_Coupon();
        $coupon->set_usage_limit(0);
        $coupon->set_amount('50');
        $coupon->set_email_restrictions($user->get_email());
        $coupon->set_code('loyalty' . mt_rand());
        $coupon->save();
        $cart->apply_coupon($coupon->get_code());

        $couponCode = $coupon->get_code();
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
        $result = $this->loyalty->createLoyaltyCoupon(true);

        $this->assertEmpty($result);
        $this->assertNotEmpty($this->loyalty->getCouponLoyalty($woocommerce->customer->get_email()));

        $coupons = $woocommerce->cart->get_coupons();

        $this->assertNotEmpty($coupons);
        $this->assertFalse(isset($coupons[$couponCode]));
    }

    public function testDeleteLoyaltyCouponInOrder()
    {
        $products = DataLoyaltyRetailCrm::createProducts();
        $user = DataLoyaltyRetailcrm::createUsers()[0];

        $coupon = new WC_Coupon();
        $coupon->set_usage_limit(0);
        $coupon->set_amount('50');
        $coupon->set_email_restrictions($user->get_email());
        $coupon->set_code('loyalty' . mt_rand());
        $coupon->save();

        $wcOrder = wc_create_order([
            'status'        => null,
            'customer_id'   => $user->get_id(),
            'customer_note' => null,
            'parent'        => null,
            'created_via'   => null,
            'cart_hash'     => null,
            'order_id'      => 0,
        ]);

        $wcOrder->add_product($products[0]);
        $wcOrder->add_product($products[1]);
        $wcOrder->apply_coupon($coupon->get_code());
        $wcOrder->calculate_totals();
        $wcOrder->save();

        $this->assertNotEmpty($wcOrder->get_coupons());
        $this->loyalty->deleteLoyaltyCouponInOrder($wcOrder);
        $this->assertEmpty($wcOrder->get_coupons());

        $wcOrder->delete(true);
    }

    public function testApplyLoyaltyDiscountWithBonuses()
    {
        $products = DataLoyaltyRetailCrm::createProducts();
        $user = DataLoyaltyRetailcrm::createUsers()[0];

        $wcOrder = wc_create_order([
            'status'        => null,
            'customer_id'   => $user->get_id(),
            'customer_note' => null,
            'parent'        => null,
            'created_via'   => null,
            'cart_hash'     => null,
            'order_id'      => 0,
        ]);

        $wcOrder->add_product($products[0]);
        $wcOrder->add_product($products[1]);
        $wcOrder->calculate_totals();
        $wcOrder->save();

        foreach ($wcOrder->get_items() as $id => $item) {
            $currentItemsPrice[$id] = $item->get_total();
            $itemIds[] = $id;
        }

        $createdCrmOrderResponse = ['site' => 'test', 'externalId' => 1, 'items' => [['discounts' => [],], ['discounts' => []]]];
        $response = new WC_Retailcrm_Response(
            200,
            json_encode([
                'order' => [
                    'items' => [
                        [
                            'externalIds' => [
                                [
                                    'value' => '11_' . $itemIds[0]
                                ]
                            ],
                            'discounts' => [
                                [
                                    'type' => 'bonus_charge',
                                    'amount' => 25
                                ]
                            ]
                        ],
                        [
                            'externalIds' => [
                                [
                                    'value' => '22_' . $itemIds[1]
                                ]
                            ],
                            'discounts' => [
                                [
                                    'type' => 'bonus_charge',
                                    'amount' => 25
                                ]
                            ]
                        ]
                    ]
                ]
            ])
        );

        $this->setMockResponse($this->apiMock, 'applyBonusToOrder', $response);
        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);

        $this->loyalty->applyLoyaltyDiscount($wcOrder, $createdCrmOrderResponse, 50);

        foreach ($wcOrder->get_items() as $id => $item) {
            $this->assertNotEquals($item->get_total(), $currentItemsPrice[$id]);
        }
    }

    public function testApplyLoyaltyDiscountWithPercentDiscount()
    {
        $products = DataLoyaltyRetailCrm::createProducts();
        $user = DataLoyaltyRetailcrm::createUsers()[0];

        $wcOrder = wc_create_order([
            'status'        => null,
            'customer_id'   => $user->get_id(),
            'customer_note' => null,
            'parent'        => null,
            'created_via'   => null,
            'cart_hash'     => null,
            'order_id'      => 0,
        ]);

        $wcOrder->add_product($products[0]);
        $wcOrder->add_product($products[1]);
        $wcOrder->calculate_totals();
        $wcOrder->save();

        foreach ($wcOrder->get_items() as $id => $item) {
            $currentItemsPrice[$id] = $item->get_total();
            $itemIds[] = $id;
        }

        $createdCrmOrderResponse = [
            'site' => 'test',
            'externalId' => 1,
            'items' => [
                [
                    'externalIds' => [
                        [
                            'value' => '11_' . $itemIds[0]
                        ]
                    ],
                    'discounts' => [
                        [
                            'type' => 'loyalty_level',
                            'amount' => 25
                        ]
                    ]
                ],
                [
                    'externalIds' => [
                        [
                            'value' => '22_' . $itemIds[1]
                        ]
                    ],
                    'discounts' => [
                        [
                            'type' => 'loyalty_level',
                            'amount' => 25
                        ]
                    ]
                ]
            ]
        ];

        $this->loyalty->applyLoyaltyDiscount($wcOrder, $createdCrmOrderResponse, 0);

        foreach ($wcOrder->get_items() as $id => $item) {
            $this->assertNotEquals($item->get_total(), $currentItemsPrice[$id]);
        }
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
