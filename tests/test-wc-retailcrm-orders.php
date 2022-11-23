<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Orders_Test - Testing WC_Retailcrm_Orders.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Orders_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $order;
    protected $options;

    public function setUp()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
                              ->disableOriginalConstructor()
                              ->setMethods(
                                  [
                                      'ordersGet',
                                      'ordersCreate',
                                      'ordersEdit',
                                      'customersGet',
                                      'customersCreate',
                                      'ordersPaymentCreate',
                                      'ordersPaymentDelete',
                                      'customersList',
                                      'getCorporateEnabled',
                                      'customersCorporateCompanies',
                                      'customersCorporateList',
                                      'customersCorporateCreate',
                                      'getSingleSiteForKey',
                                      'customersCorporateAddressesCreate',
                                      'customersCorporateCompaniesCreate'
                                  ]
                              )
                              ->getMock();

        $this->options = $this->setOptions();

        parent::setUp();
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderRetailcrm
     * @throws Exception
     */
    public function test_order_create($retailcrm)
    {
        if ($retailcrm) {
            $responseMock = $this->createResponseMock();
            $responseMockCustomers = $this->createResponseMock();

            $responseMockCustomers->setResponse(
                [
                    'success' => true,
                    'customers' => [
                        ['externalId' => 1]
                    ]
                ]
            );

            $this->setMockResponse($retailcrm, 'ordersCreate', $responseMock);
            $this->setMockResponse($retailcrm, 'customersCreate', $responseMock);
            $this->setMockResponse($retailcrm, 'customersList', $responseMockCustomers);
        }

        $this->createTestOrder();

        $retailcrmOrders = $this->getRetailcrmOrders($retailcrm);
        $order = $retailcrmOrders->orderCreate($this->order->get_id());
        $orderData = $retailcrmOrders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $orderData);
            $this->assertArrayHasKey('status', $orderData);
            $this->assertArrayHasKey('externalId', $orderData);
            $this->assertArrayHasKey('firstName', $orderData);
            $this->assertArrayHasKey('lastName', $orderData);
            $this->assertArrayHasKey('email', $orderData);
            $this->assertArrayHasKey('delivery', $orderData);
            $this->assertArrayHasKey('code', $orderData['delivery']);
            $this->assertArrayHasKey('address', $orderData['delivery']);
            $this->assertArrayHasKey('index', $orderData['delivery']['address']);
            $this->assertArrayHasKey('city', $orderData['delivery']['address']);
            $this->assertEquals($this->order->get_id(), $orderData['externalId']);
            $this->assertEquals('status1', $orderData['status']);
            $this->assertEquals('Jeroen', $orderData['firstName']);
            $this->assertEquals('Sormani', $orderData['lastName']);
            $this->assertEquals('admin@example.org', $orderData['email']);
            $this->assertEquals('US', $orderData['countryIso']);
            if (mb_strlen($orderData['delivery']['address']['index']) === 6) {
                $this->assertEquals('123456', $orderData['delivery']['address']['index']);
            } else {
                $this->assertEquals('12345', $orderData['delivery']['address']['index']);
            }
            $this->assertEquals('WooCity', $orderData['delivery']['address']['city']);
            $this->assertEquals('delivery', $orderData['delivery']['code']);

            $this->assertArrayHasKey('payments', $orderData);
            $this->assertInternalType('array', $orderData['payments']);
            $this->assertArrayHasKey('type', $orderData['payments'][0]);
            $this->assertArrayHasKey('externalId', $orderData['payments'][0]);
            $this->assertEquals('payment1', $orderData['payments'][0]['type']);
            $this->assertEquals($orderData['customFields']['crm_order'], 'test_custom_fields');
        } else {
            $this->assertEquals(null, $order);
        }
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_create_with_corporate_customer($retailcrm)
    {
        if ($retailcrm) {
            $responseMock = $this->createResponseMock();

            // Mock response for search customer
            $responseMockSearch = $this->createResponseMock();
            $this->setMockResponse($responseMockSearch, 'isSuccessful', true);

            $responseMockSearch->setResponse(
                [
                    'success'   => true,
                    'customersCorporate' => []
                ]
            );

            // Mock response for create customer corporate, his addresses and companies
            $responseMockCustomerCorporate = $this->createResponseMock();
            $this->setMockResponse($responseMockCustomerCorporate, 'isSuccessful', true);

            $responseMockCustomerCorporate->setResponse(
                [
                    'success' => true,
                    'id' => 1
                ]
            );

            // Mock response for get companies
            $responseMockCompany = $this->createResponseMock();
            $this->setMockResponse($responseMockCompany, 'isSuccessful', true);

            $responseMockCompany->setResponse(
                [
                    'success'   => true,
                    'companies' => [
                        ['name' => 'WooCompany', 'id' => 777]
                    ]
                ]
            );

            $this->setMockResponse($retailcrm, 'ordersCreate', $responseMock);
            $this->setMockResponse($retailcrm, 'getSingleSiteForKey', 'woo');
            $this->setMockResponse($retailcrm, 'customersCorporateCreate', $responseMockCustomerCorporate);
            $this->setMockResponse($retailcrm, 'customersCorporateAddressesCreate', $responseMockCustomerCorporate);
            $this->setMockResponse($retailcrm, 'customersCorporateCompaniesCreate', $responseMockCustomerCorporate);
            $this->setMockResponse($retailcrm, 'customersCorporateList', $responseMockSearch);
            $this->setMockResponse($retailcrm, 'getCorporateEnabled', true);
            $this->setMockResponse($retailcrm, 'customersCorporateCompanies', $responseMockCompany);
        }

        $this->createTestOrder();

        $retailcrmOrders = $this->getRetailcrmOrders($retailcrm);
        $order           = $retailcrmOrders->orderCreate($this->order->get_id());
        $orderData       = $retailcrmOrders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertArrayHasKey('customer', $orderData);
            $this->assertArrayHasKey('id', $orderData['customer']);
            $this->assertEquals(1, $orderData['customer']['id']);
            $this->assertArrayHasKey('company', $orderData);
            $this->assertArrayHasKey('id', $orderData['company']);
            $this->assertArrayHasKey('name', $orderData['company']);
            $this->assertEquals(777, $orderData['company']['id']);
            $this->assertEquals($this->order->get_billing_company(), $orderData['company']['name']);
            $this->assertEquals($orderData['customFields']['crm_order'], 'test_custom_fields');
        } else {
            $this->assertEquals(null, $order);
        }
    }

    /**
     * @param $isSuccessful
     * @param $retailcrm
     *
     * @throws Exception
     * @dataProvider dataProviderUpdateOrder
     */
    public function test_update_order($isSuccessful, $retailcrm)
    {
        $this->createTestOrder();

        if ($retailcrm) {
            $responseMock = $this->createResponseMock();

            $this->setMockResponse($responseMock, 'isSuccessful', $isSuccessful);
            $this->setMockResponse($retailcrm, 'ordersEdit', $responseMock);
            $this->setMockResponse($retailcrm, 'ordersPaymentDelete', $responseMock);

            $response = $this->getResponseData($this->order->get_id());
            $responseMock->setResponse($response);

            $this->setMockResponse($retailcrm, 'ordersGet', $responseMock);
        }

        $retailcrmOrders = $this->getRetailcrmOrders($retailcrm);
        $order = $retailcrmOrders->updateOrder($this->order->get_id());
        $orderData = $retailcrmOrders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $orderData);
            $this->assertArrayHasKey('status', $orderData);
            $this->assertArrayHasKey('externalId', $orderData);
            $this->assertArrayHasKey('firstName', $orderData);
            $this->assertArrayHasKey('lastName', $orderData);
            $this->assertArrayHasKey('email', $orderData);
            $this->assertArrayHasKey('delivery', $orderData);
            $this->assertArrayHasKey('code', $orderData['delivery']);
            $this->assertArrayHasKey('address', $orderData['delivery']);
            $this->assertArrayHasKey('index', $orderData['delivery']['address']);
            $this->assertArrayHasKey('city', $orderData['delivery']['address']);
            $this->assertEquals($this->order->get_id(), $orderData['externalId']);
            $this->assertEquals('status1', $orderData['status']);
            $this->assertEquals('Jeroen', $orderData['firstName']);
            $this->assertEquals('Sormani', $orderData['lastName']);
            $this->assertEquals('admin@example.org', $orderData['email']);
            $this->assertEquals('US', $orderData['countryIso']);
            $this->assertEquals(0, $orderData['discountManualAmount']);
            $this->assertEquals(0, $orderData['discountManualPercent']);

            $this->assertEquals($orderData['customFields']['crm_order'], 'test_custom_fields');


            if (mb_strlen($orderData['delivery']['address']['index']) === 6) {
                $this->assertEquals('123456', $orderData['delivery']['address']['index']);
            } else {
                $this->assertEquals('12345', $orderData['delivery']['address']['index']);
            }

            $this->assertEquals('WooCity', $orderData['delivery']['address']['city']);
            $this->assertEquals('delivery', $orderData['delivery']['code']);

            $payment = $retailcrmOrders->getPayment();

            $this->assertInternalType('array', $payment);

            if (!empty($payment)) {
                $this->assertArrayHasKey('type', $payment);
                $this->assertArrayHasKey('order', $payment);
                $this->assertArrayHasKey('externalId', $payment);
                $this->assertEquals('payment1', $payment['type']);
                $this->assertArrayNotHasKey('amount', $payment);
            } else {
                $this->assertEquals([], $payment);
            }
        } else {
            $this->assertEquals(null, $order);
        }
    }

    /**
     * @param $isSuccessful
     * @param $retailcrm
     *
     * @throws Exception
     * @dataProvider dataProviderUpdateOrder
     */
    public function test_update_order_statuses($isSuccessful, $retailcrm)
    {
        $this->createTestOrder();

        if ($retailcrm) {
            $responseMock = $this->createResponseMock();

            $this->setMockResponse($responseMock, 'isSuccessful', $isSuccessful);
            $this->setMockResponse($retailcrm, 'ordersEdit', $responseMock);
            $this->setMockResponse($retailcrm, 'ordersPaymentDelete', $responseMock);

            $response = $this->getResponseData($this->order->get_id());
            $responseMock->setResponse($response);

            $this->setMockResponse($retailcrm, 'ordersGet', $responseMock);
        }

        //Check change status to not-uploaded in CRM
        $this->order->set_status('cancelled');
        $this->order->save();

        $retailcrmOrders = $this->getRetailcrmOrders($retailcrm);
        $order = $retailcrmOrders->updateOrder($this->order->get_id());
        $orderData = $retailcrmOrders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $orderData);
            $this->assertArrayNotHasKey('status', $orderData);

            //Check change status to uploaded in CRM
            $this->order->set_status('completed');
            $this->order->save();

            $order = $retailcrmOrders->updateOrder($this->order->get_id());
            $orderData = $retailcrmOrders->getOrder();

            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $orderData);
            $this->assertArrayHasKey('status', $orderData);
            $this->assertEquals('status4', $orderData['status']);
        } else {
            $this->assertEquals(null, $order);
        }
    }

    /**
     * @param $isSuccessful
     * @param $retailcrm
     *
     * @throws Exception
     * @dataProvider dataProviderUpdateOrder
     */
    public function test_payment_on_delivery($isSuccessful, $retailcrm)
    {
        $this->createTestOrder();
        $this->order->set_payment_method('cod');
        $this->order->save();

        if ($retailcrm) {
            $responseMock = $this->createResponseMock();

            $this->setMockResponse($responseMock, 'isSuccessful', $isSuccessful);
            $this->setMockResponse($retailcrm, 'ordersEdit', $responseMock);
            $this->setMockResponse($retailcrm, 'ordersPaymentDelete', $responseMock);

            $response = $this->getResponseData($this->order->get_id());
            $responseMock->setResponse($response);

            $this->setMockResponse($retailcrm, 'ordersGet', $responseMock);
        }

        $retailcrmOrders = $this->getRetailcrmOrders($retailcrm);
        $order = $retailcrmOrders->updateOrder($this->order->get_id());
        $orderData = $retailcrmOrders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $orderData);

            $payment = $retailcrmOrders->getPayment();

            $this->assertInternalType('array', $payment);

            if (!empty($payment)) {
                $this->assertArrayHasKey('type', $payment);
                $this->assertArrayHasKey('order', $payment);
                $this->assertArrayHasKey('externalId', $payment);
                $this->assertEquals('payment3', $payment['type']);

                $this->assertArrayNotHasKey('amount', $payment);
                $this->assertArrayNotHasKey('status', $payment);
                $this->assertArrayNotHasKey('paidAt', $payment);
            } else {
                $this->assertEquals([], $payment);
            }

            // Check payment status
            $this->order->set_status('completed');
            $this->order->save();

            $order = $retailcrmOrders->updateOrder($this->order->get_id());
            $orderData = $retailcrmOrders->getOrder();

            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $orderData);
            $this->assertArrayHasKey('status', $orderData);
            $this->assertEquals('status4', $orderData['status']);

            $payment = $retailcrmOrders->getPayment();

            $this->assertInternalType('array', $payment);

            if (!empty($payment)) {
                $this->assertArrayHasKey('type', $payment);
                $this->assertArrayHasKey('order', $payment);
                $this->assertArrayHasKey('externalId', $payment);
                $this->assertArrayHasKey('status', $payment);
                $this->assertArrayHasKey('paidAt', $payment);
                $this->assertEquals('payment3', $payment['type']);
                $this->assertEquals('paid', $payment['status']);
            } else {
                $this->assertEquals([], $payment);
            }
        } else {
            $this->assertEquals(null, $order);
        }
    }

    public function test_is_corporate_order()
    {
        $this->createTestOrder();
        $this->order->set_billing_company('Test');

        $this->assertEquals(true, WC_Retailcrm_Orders::isCorporateOrder($this->order));

        //Check not corporate order
        $this->order->set_billing_company('');

        $this->assertEquals(false, WC_Retailcrm_Orders::isCorporateOrder($this->order));
    }

    public function test_get_shipping_rates()
    {
        $rate = getShippingRate();

        $this->assertEquals(null, $rate);
    }

    public function test_is_corporate_crm_order()
    {
        $this->assertEquals(
            true,
            WC_Retailcrm_Orders::isCorporateCrmOrder(
                [
                    'customer' => [
                        'type' => 'customer_corporate'
                    ]
                ]
            )
        );

        //Check not corporate order
        $this->assertEquals(
            false,
            WC_Retailcrm_Orders::isCorporateCrmOrder(
                [
                    'customer' => [
                        'type' => 'customer'
                    ]
                ]
            )
        );
    }

    public function test_is_order_customer_was_changed()
    {
        $this->createTestOrder();

        // First case
        $this->order->set_billing_company('Test');

        $this->assertEquals(
            true,
            WC_Retailcrm_Orders::isOrderCustomerWasChanged(
                $this->order,
                [
                    'customer' => [
                        'type' => 'customer'
                    ]
                ]
            )
        );

        // Second case
        $this->assertEquals(
            true,
            WC_Retailcrm_Orders::isOrderCustomerWasChanged(
                $this->order,
                [
                    'customer' => [
                        'type' => 'customer_corporate'
                    ],
                    'company' => [
                        'name' => 'Test1'
                    ]
                ]
            )
        );

        // Third case
        $this->order->set_customer_id(1);

        $this->assertEquals(
            true,
            WC_Retailcrm_Orders::isOrderCustomerWasChanged(
                $this->order,
                [
                    'customer' => [
                        'type' => 'customer_corporate',
                        'externalId' => 2
                    ],
                    'company' => [
                        'name' => 'Test'
                    ]
                ]
            )
        );

        // Fourth case
        $this->order->set_billing_email('test@mail.es');

        $this->assertEquals(
            true,
            WC_Retailcrm_Orders::isOrderCustomerWasChanged(
                $this->order,
                [
                    'customer' => [
                        'type' => 'customer_corporate',
                        'externalId' => 1,
                        'email' => 'test1@mail.es'
                    ],
                    'company' => [
                        'name' => 'Test'
                    ]
                ]
            )
        );

        // Customer not changed
        $this->assertEquals(
            false,
            WC_Retailcrm_Orders::isOrderCustomerWasChanged(
                $this->order,
                [
                    'customer' => [
                        'type' => 'customer_corporate',
                        'externalId' => 1,
                        'email' => 'test@mail.es'
                    ],
                    'company' => [
                        'name' => 'Test'
                    ]
                ]
            )
        );
    }

    public function dataProviderUpdateOrder()
    {
        $this->setUp();

        return [
            [
                'is_successful' => true,
                'retailcrm' => $this->apiMock
            ],
            [
                'is_successful' => true,
                'retailcrm' => false
            ],
            [
                'is_successful' => false,
                'retailcrm' => false
            ],
            [
                'is_successful' => false,
                'retailcrm' => $this->apiMock
            ]
        ];
    }

    public function dataProviderRetailcrm()
    {
        $this->setUp();

        return [
            [
                'retailcrm' => $this->apiMock
            ],
            [
                'retailcrm' => false
            ]
        ];
    }

    private function createTestOrder()
    {
        /** @var WC_Order order */
        $this->order = WC_Helper_Order::create_order(0);

        foreach ($this->order->get_address('billing') as $prop => $value) {
            if (method_exists($this->order, 'set_shipping_' . $prop)) {
                $this->order->{'set_shipping_' . $prop}($value);
            }
        }

        $this->order->save();

        update_post_meta($this->order->get_id(), 'woo_order', 'test_custom_fields');
    }

    private function getResponseData($externalId)
    {
        return [
            'success' => true,
            'order' => [
                'payments' => [
                    [
                        'id' => 1,
                        'externalId' => $externalId,
                        'type' => 'payment2'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $retailcrm
     *
     * @return WC_Retailcrm_Orders
     */
    private function getRetailcrmOrders($retailcrm)
    {
        return new WC_Retailcrm_Orders(
            $retailcrm,
            $this->getOptions(),
            new WC_Retailcrm_Order_Item($this->getOptions()),
            new WC_Retailcrm_Order_Address(),
            new WC_Retailcrm_Customers(
                $retailcrm,
                $this->getOptions(),
                new WC_Retailcrm_Customer_Address()
            ),
            new WC_Retailcrm_Order($this->getOptions()),
            new WC_Retailcrm_Order_Payment($this->getOptions())
        );
    }


    /**
     * @return mixed
     */
    private function createResponseMock()
    {
        return $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                    ->disableOriginalConstructor()
                    ->setMethods(['isSuccessful'])
                    ->getMock();
    }
}

