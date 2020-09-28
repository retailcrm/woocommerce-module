<?php

class WC_Retailcrm_Orders_Test extends  WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $order;
    protected $options;

    public function setUp()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'ordersGet',
                'ordersUpload',
                'ordersCreate',
                'ordersEdit',
                'customersGet',
                'customersCreate',
                'ordersPaymentCreate',
                'ordersPaymentDelete',
                'customersList'
            ))
            ->getMock();

        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_upload($retailcrm)
    {
        $this->options = $this->setOptions();
        $retailcrm_orders = $this->getRetailcrmOrders($retailcrm);
        $upload_orders = $retailcrm_orders->ordersUpload();

        if ($retailcrm) {
            $this->assertInternalType('array', $upload_orders);
        } else {
            $this->assertEquals(null, $upload_orders);
        }
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_create($retailcrm)
    {
        if ($retailcrm) {
            $responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                ->disableOriginalConstructor()
                ->setMethods(array(
                    'isSuccessful'
                ))
                ->getMock();

            $responseMockCustomers = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                ->disableOriginalConstructor()
                ->setMethods(array(
                    'isSuccessful'
                ))
                ->getMock();
            $responseMockCustomers->setResponse(
                array('success' => true,
                    'customers' => array(
                        array('externalId' => 1)
                    )
                )
            );

            $retailcrm->expects($this->any())
                ->method('customersCreate')
                ->willReturn($responseMock);
            $retailcrm->expects($this->any())
                ->method('customersList')
                ->willReturn($responseMockCustomers);
        }

        $this->createTestOrder();
        $this->options = $this->setOptions();
        $retailcrm_orders = $this->getRetailcrmOrders($retailcrm);
        $order = $retailcrm_orders->orderCreate($this->order->get_id());
        $order_send = $retailcrm_orders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $order_send);
            $this->assertArrayHasKey('status', $order_send);
            $this->assertArrayHasKey('externalId', $order_send);
            $this->assertArrayHasKey('firstName', $order_send);
            $this->assertArrayHasKey('lastName', $order_send);
            $this->assertArrayHasKey('email', $order_send);
            $this->assertArrayHasKey('delivery', $order_send);
            $this->assertArrayHasKey('code', $order_send['delivery']);
            $this->assertArrayHasKey('address', $order_send['delivery']);
            $this->assertArrayHasKey('index', $order_send['delivery']['address']);
            $this->assertArrayHasKey('city', $order_send['delivery']['address']);
            $this->assertEquals($this->order->get_id(), $order_send['externalId']);
            $this->assertEquals('status1', $order_send['status']);
            $this->assertEquals('Jeroen', $order_send['firstName']);
            $this->assertEquals('Sormani', $order_send['lastName']);
            $this->assertEquals('admin@example.org', $order_send['email']);
            $this->assertEquals('US', $order_send['countryIso']);
            if (mb_strlen($order_send['delivery']['address']['index']) === 6) {
                $this->assertEquals('123456', $order_send['delivery']['address']['index']);
            } else {
                $this->assertEquals('12345', $order_send['delivery']['address']['index']);
            }
            $this->assertEquals('WooCity', $order_send['delivery']['address']['city']);
            $this->assertEquals('delivery', $order_send['delivery']['code']);

            $this->assertArrayHasKey('payments', $order_send);
            $this->assertInternalType('array', $order_send['payments']);
            $this->assertArrayHasKey('type', $order_send['payments'][0]);
            $this->assertArrayHasKey('externalId', $order_send['payments'][0]);
            $this->assertEquals('payment1', $order_send['payments'][0]['type']);
        } else {
            $this->assertEquals(null, $order);
        }
    }

    /**
     * @param $isSuccessful
     * @param $retailcrm

     * @dataProvider dataProviderUpdateOrder
     */
    public function test_update_order($isSuccessful, $retailcrm)
    {
        $this->createTestOrder();
        $this->options = $this->setOptions();

        if ($retailcrm) {
            $responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                ->disableOriginalConstructor()
                ->setMethods(array(
                    'isSuccessful'
                ))
                ->getMock();

            $responseMock->expects($this->any())
                ->method('isSuccessful')
                ->willReturn($isSuccessful);

            $retailcrm->expects($this->any())
                ->method('ordersEdit')
                ->willReturn($responseMock);

            $retailcrm->expects($this->any())
                ->method('ordersPaymentDelete')
                ->willReturn($responseMock);

            $response = $this->getResponseData($this->order->get_id());
            $responseMock->setResponse($response);

            $retailcrm->expects($this->any())
                ->method('ordersGet')
                ->willReturn($responseMock);
        }

        $retailcrm_orders = $this->getRetailcrmOrders($retailcrm);
        $order = $retailcrm_orders->updateOrder($this->order->get_id());
        $order_send = $retailcrm_orders->getOrder();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Order', $order);
            $this->assertInternalType('array', $order_send);
            $this->assertArrayHasKey('status', $order_send);
            $this->assertArrayHasKey('externalId', $order_send);
            $this->assertArrayHasKey('firstName', $order_send);
            $this->assertArrayHasKey('lastName', $order_send);
            $this->assertArrayHasKey('email', $order_send);
            $this->assertArrayHasKey('delivery', $order_send);
            $this->assertArrayHasKey('code', $order_send['delivery']);
            $this->assertArrayHasKey('address', $order_send['delivery']);
            $this->assertArrayHasKey('index', $order_send['delivery']['address']);
            $this->assertArrayHasKey('city', $order_send['delivery']['address']);
            $this->assertEquals($this->order->get_id(), $order_send['externalId']);
            $this->assertEquals('status1', $order_send['status']);
            $this->assertEquals('Jeroen', $order_send['firstName']);
            $this->assertEquals('Sormani', $order_send['lastName']);
            $this->assertEquals('admin@example.org', $order_send['email']);
            $this->assertEquals('US', $order_send['countryIso']);
            $this->assertEquals(0, $order_send['discountManualAmount']);
            $this->assertEquals(0, $order_send['discountManualPercent']);
            
            if (mb_strlen($order_send['delivery']['address']['index']) === 6) {
                $this->assertEquals('123456', $order_send['delivery']['address']['index']);
            } else {
                $this->assertEquals('12345', $order_send['delivery']['address']['index']);
            }

            $this->assertEquals('WooCity', $order_send['delivery']['address']['city']);
            $this->assertEquals('delivery', $order_send['delivery']['code']);

            $payment = $retailcrm_orders->getPayment();
            $this->assertInternalType('array', $payment);

            if (!empty($payment)) {
                $this->assertArrayHasKey('type', $payment);
                $this->assertArrayHasKey('order', $payment);
                $this->assertArrayHasKey('externalId', $payment);
                $this->assertEquals('payment1', $payment['type']);

                if (!empty($this->options['send_payment_amount']) && $this->options['send_payment_amount'] == 'yes') {
                    $this->assertArrayHasKey('amount', $payment);
                } else {
                    $this->assertArrayNotHasKey('amount', $payment);
                }
            }
        } else {
            $this->assertEquals(null, $order);
        }
    }

    public function dataProviderUpdateOrder()
    {
        $this->setUp();

        return array(
            array(
                'is_successful' => true,
                'retailcrm' => $this->apiMock
            ),
            array(
                'is_successful' => true,
                'retailcrm' => false
            ),
            array(
                'is_successful' => false,
                'retailcrm' => false
            ),
            array(
                'is_successful' => false,
                'retailcrm' => $this->apiMock
            )
        );
    }

    public function dataProviderRetailcrm()
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
    }

    private function getResponseData($externalId)
    {
        return array(
            'success' => true,
            'order' => array(
                'payments' => array(
                    array(
                        'id' => 1,
                        'externalId' => $externalId,
                        'type' => 'payment2'
                    )
                )
            )
        );
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
            new WC_Retailcrm_Order_Address,
            new WC_Retailcrm_Customers(
                $retailcrm, $this->getOptions(), new WC_Retailcrm_Customer_Address
            ),
            new WC_Retailcrm_Order($this->getOptions()),
            new WC_Retailcrm_Order_Payment($this->getOptions())
        );
    }
}
