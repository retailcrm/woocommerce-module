<?php

class WC_Retailcrm_Orders_Test extends  WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;
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
                'ordersPaymentDelete'
            ))
            ->getMock();

        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @param $apiVersion
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_upload($retailcrm, $apiVersion)
    {
        $this->options = $this->setOptions($apiVersion);
        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
        $upload_orders = $retailcrm_orders->ordersUpload();

        if ($retailcrm) {
            $this->assertInternalType('array', $upload_orders);
        } else {
            $this->assertEquals(null, $upload_orders);
        }
    }

    /**
     * @param $retailcrm
     * @param $apiVersion
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_create($retailcrm, $apiVersion)
    {
        $this->createTestOrder();
        $this->options = $this->setOptions($apiVersion);
        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
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
            $this->assertEquals('123456', $order_send['delivery']['address']['index']);
            $this->assertEquals('WooCity', $order_send['delivery']['address']['city']);
            $this->assertEquals('delivery5', $order_send['delivery']['code']);

            if ($apiVersion == 'v4') {
                $this->assertArrayHasKey('paymentType', $order_send);
                $this->assertEquals('payment1', $order_send['paymentType']);
            } elseif ($apiVersion == 'v5') {
                $this->assertArrayHasKey('payments', $order_send);
                $this->assertInternalType('array', $order_send['payments']);
                $this->assertArrayHasKey('type', $order_send['payments'][0]);
                $this->assertEquals('payment1', $order_send['payments'][0]['type']);
            }
        } else {
            $this->assertEquals(null, $order);
        }
    }

    /**
     * @param $isSuccessful
     * @param $retailcrm
     * @param $apiVersion
     * @dataProvider dataProviderUpdateOrder
     */
    public function test_update_order($isSuccessful, $retailcrm, $apiVersion)
    {
        $this->createTestOrder();
        $this->options = $this->setOptions($apiVersion);

        if ($retailcrm && $apiVersion == 'v5') {
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

        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
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
            $this->assertEquals('123456', $order_send['delivery']['address']['index']);
            $this->assertEquals('WooCity', $order_send['delivery']['address']['city']);
            $this->assertEquals('delivery5', $order_send['delivery']['code']);

            if ($apiVersion == 'v4') {
                $this->assertArrayHasKey('paymentType', $order_send);
                $this->assertEquals('payment1', $order_send['paymentType']);
            } elseif ($apiVersion == 'v5') {
                $payment = $retailcrm_orders->getPayment();
                $this->assertInternalType('array', $payment);

                if (!empty($payment)) {
                    $this->assertArrayHasKey('type', $payment);
                    $this->assertArrayHasKey('amount', $payment);
                    $this->assertArrayHasKey('order', $payment);
                    $this->assertEquals('payment1', $payment['type']);
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
                'retailcrm' => $this->apiMock,
                'api_version' => 'v5'
            ),
            array(
                'is_successful' => true,
                'retailcrm' => false,
                'api_version' => 'v5'
            ),
            array(
                'is_successful' => false,
                'retailcrm' => false,
                'api_version' => 'v5'
            ),
            array(
                'is_successful' => false,
                'retailcrm' => $this->apiMock,
                'api_version' => 'v5'
            ),
            array(
                'is_successful' => false,
                'retailcrm' => $this->apiMock,
                'api_version' => 'v4'
            ),
            array(
                'is_successful' => true,
                'retailcrm' => $this->apiMock,
                'api_version' => 'v4'
            ),
            array(
                'is_successful' => false,
                'retailcrm' => false,
                'api_version' => 'v4'
            ),
            array(
                'is_successful' => true,
                'retailcrm' => false,
                'api_version' => 'v4'
            )
        );
    }

    public function dataProviderRetailcrm()
    {
        $this->setUp();

        return array(
            array(
                'retailcrm' => $this->apiMock,
                'api_version' => 'v4'
            ),
            array(
                'retailcrm' => false,
                'api_version' => 'v4'
            ),
            array(
                'retailcrm' => $this->apiMock,
                'api_version' => 'v5'
            ),
            array(
                'retailcrm' => false,
                'api_version' => 'v5'
            )
        );
    }

    private function createTestOrder()
    {
        $this->order = WC_Helper_Order::create_order(0);
//        var_dump($this->order);
//        $this->order = new WC_Order();
//        $this->order->set_payment_method('bacs');
//        $this->order->set_billing_first_name('testFirstName');
//        $this->order->set_billing_last_name('testLastName');
//        $this->order->set_billing_country('RU');
//        $this->order->set_billing_address_1('testAddress1');
//        $this->order->set_billing_city('testCity');
//        $this->order->set_billing_postcode('111111');
//        $this->order->set_billing_email('test@mail.com');
//        $this->order->save();
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
}
