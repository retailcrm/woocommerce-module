<?php

class WC_Retailcrm_History_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $customersHistoryResponse;
    protected $ordersHistoryResponse;

    const STATUS_1 = 'status1';
    const STATUS_2 = 'status4';

    public function setUp()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'ordersHistory',
                'customersHistory'
            ))
            ->getMock();

        $this->customersHistoryResponse = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->ordersHistoryResponse = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        parent::setUp();
    }

    /**
     * @dataProvider dataProvider
     * @param $api_version
     */
    public function test_history_order_create($api_version)
    {
        $this->setOptions($api_version);

        $this->customersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->customersHistoryResponse->setResponse(array('success' => true, 'history' => array()));

        $this->ordersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $product = WC_Helper_Product::create_simple_product();

        $this->ordersHistoryResponse->setResponse(
            $this->get_history_data_new_order($product->get_id())
        );

        $this->apiMock->expects($this->any())->method('customersHistory')->willReturn($this->customersHistoryResponse);
        $this->apiMock->expects($this->any())->method('ordersHistory')->willReturn($this->ordersHistoryResponse);

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $orders = wc_get_orders(array('numberposts' => -1));
        $order_added = end($orders);
        $order_added_items = $order_added->get_items();
        $order_added_item = reset($order_added_items);
        $shipping_address = $order_added->get_address('shipping');
        $billing_address = $order_added->get_address('billing');

        $options = get_option(\WC_Retailcrm_Base::$option_key);

        $this->assertEquals(self::STATUS_1, $options[$order_added->get_status()]);
        $this->assertEquals($product->get_id(), $order_added_item->get_product()->get_id());
        $this->assertNotEmpty($shipping_address['first_name']);
        $this->assertNotEmpty($shipping_address['last_name']);
        $this->assertNotEmpty($shipping_address['postcode']);
        $this->assertNotEmpty($shipping_address['city']);
        $this->assertNotEmpty($shipping_address['country']);
        $this->assertNotEmpty($shipping_address['state']);
        $this->assertNotEmpty($billing_address['phone']);
        $this->assertNotEmpty($billing_address['email']);
        $this->assertNotEmpty($billing_address['first_name']);
        $this->assertNotEmpty($billing_address['last_name']);
        $this->assertNotEmpty($billing_address['postcode']);
        $this->assertNotEmpty($billing_address['city']);
        $this->assertNotEmpty($billing_address['country']);
        $this->assertNotEmpty($billing_address['state']);
        $this->assertEquals('payment4', $options[$order_added->get_payment_method()]);
    }

    /**
     * @dataProvider dataProvider
     * @param $api_version
     */
    public function test_history_order_add_product($api_version)
    {
        $this->setOptions($api_version);

        $this->customersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->customersHistoryResponse->setResponse(array('success' => true, 'history' => array()));

        $this->ordersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $product = WC_Helper_Product::create_simple_product();
        $order = WC_Helper_Order::create_order(0);

        $this->ordersHistoryResponse->setResponse(
            $this->get_history_data_product_add($product->get_id(), $order->get_id())
        );

        $this->apiMock->expects($this->any())->method('customersHistory')->willReturn($this->customersHistoryResponse);
        $this->apiMock->expects($this->any())->method('ordersHistory')->willReturn($this->ordersHistoryResponse);

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $order_updated = wc_get_order($order->get_id());
        $order_updated_items = $order_updated->get_items();
        $order_updated_item = end($order_updated_items);

        $this->assertEquals(2, count($order_updated_items));
        $this->assertEquals(2, $order_updated_item->get_quantity());
        $this->assertEquals($product->get_id(), $order_updated_item->get_product()->get_id());
    }

    /**
     * @dataProvider dataProvider
     * @param $api_version
     */
    public function test_history_order_update($api_version)
    {
        $this->setOptions($api_version);

        $this->customersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->customersHistoryResponse->setResponse(array('success' => true, 'history' => array()));

        $this->ordersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $order = WC_Helper_Order::create_order(0);

        $this->ordersHistoryResponse->setResponse(
            $this->get_history_data_update($order->get_id(), $api_version)
        );

        $this->apiMock->expects($this->any())->method('customersHistory')->willReturn($this->customersHistoryResponse);
        $this->apiMock->expects($this->any())->method('ordersHistory')->willReturn($this->ordersHistoryResponse);

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $order_updated = wc_get_order($order->get_id());
        $options = get_option(\WC_Retailcrm_Base::$option_key);

        $this->assertEquals(self::STATUS_2, $options[$order_updated->get_status()]);
        $this->assertEquals('payment2', $options[$order_updated->get_payment_method()]);
    }

    public function dataProvider()
    {
        return array(
            array(
                'api_version' => 'v4'
            ),
            array(
                'api_version' => 'v5'
            )
        );
    }

    private function get_history_data_new_order($product_create_id)
    {
        return array(
            'success' => true,
            'history'  => array(
                array(
                    'id' => 1,
                    'createdAt' => '2018-01-01 00:00:00',
                    'created' => true,
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'status',
                    'oldValue' => null,
                    'newValue' => array(
                        'code' => self::STATUS_1
                    ),
                    'order' => array(
                        'slug' => 1,
                        'id' => 1,
                        'number' => '1C',
                        'orderType' => 'eshop-individual',
                        'orderMethod' => 'phone',
                        'countryIso' => 'RU',
                        'createdAt' => '2018-01-01 00:00:00',
                        'statusUpdatedAt' => '2018-01-01 00:00:00',
                        'summ' => 100,
                        'totalSumm' => 100,
                        'prepaySum' => 0,
                        'purchaseSumm' => 50,
                        'markDatetime' => '2018-01-01 00:00:00',
                        'firstName' => 'Test',
                        'lastName' => 'Test',
                        'phone' => '80000000000',
                        'call' => false,
                        'expired' => false,
                        'customer' => array(
                            'segments' => array(),
                            'id' => 1,
                            'firstName' => 'Test',
                            'lastName' => 'Test',
                            'email' => 'email@test.ru',
                            'phones' => array(
                                array(
                                    'number' => '111111111111111'
                                ),
                                array(
                                    'number' => '+7111111111'
                                )
                            ),
                            'address' => array(
                                'index' => '111111',
                                'countryIso' => 'RU',
                                'region' => 'Test region',
                                'city' => 'Test',
                                'text' => 'Test text address'
                            ),
                            'createdAt' => '2018-01-01 00:00:00',
                            'managerId' => 1,
                            'vip' => false,
                            'bad' => false,
                            'site' => 'test-com',
                            'contragent' => array(
                                'contragentType' => 'individual'
                            ),
                            'personalDiscount' => 0,
                            'cumulativeDiscount' => 0,
                            'marginSumm' => 58654,
                            'totalSumm' => 61549,
                            'averageSumm' => 15387.25,
                            'ordersCount' => 4,
                            'costSumm' => 101,
                            'customFields' => array(
                                'custom' => 'test'
                            )
                        ),
                        'contragent' => array(),
                        'delivery' => array(
                            'cost' => 0,
                            'netCost' => 0,
                            'address' => array(
                                'index' => '111111',
                                'countryIso' => 'RU',
                                'region' => 'Test region',
                                'city' => 'Test',
                                'text' => 'Test text address'
                            )
                        ),
                        'site' => 'test-com',
                        'status' => self::STATUS_1,
                        'items' => array(
                            array(
                                'id' => 160,
                                'initialPrice' => 100,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'offer' => array(
                                    'id' => 1,
                                    'externalId' => $product_create_id,
                                    'xmlId' => '1',
                                    'name' => 'Test name',
                                    'vatRate' => 'none'
                                ),
                                'properties' => array(),
                                'purchasePrice' => 50
                            )
                        ),
                        'paymentType' => 'payment4',
                        'payments' => array(
                            array(
                                'id'=> 1,
                                'type'=> 'payment4',
                                'amount'=> 100,
                            )
                        ),
                        'fromApi' => false,
                        'length' => 0,
                        'width' => 0,
                        'height' => 0,
                        'shipmentStore' => 'main',
                        'shipped' => false,
                        'customFields' => array(),
                        'uploadedToExternalStoreSystem' => false
                    )
                )
            )
        );
    }

    private function get_history_data_product_add($product_add_id, $order_id)
    {
        return array(
            'success' => true,
            'history'  => array(
                array(
                    'id' => 2,
                    'createdAt' => '2018-01-01 00:00:01',
                    'source'=> 'user',
                    'user' => array(
                        'id'=> 1
                    ),
                    'field' => 'order_product',
                    'oldValue' => null,
                    'newValue' => array(
                        'id' => 2,
                        'offer' => array(
                            'id'=> 2,
                            'externalId' => $product_add_id,
                            'xmlId' => 'xmlId'
                        )
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'site' => 'test-com',
                        'status' => self::STATUS_1
                    ),
                    'item' => array(
                        'id' => 2,
                        'initialPrice' => 999,
                        'createdAt' => '2018-01-01 00:02:00',
                        'quantity' => 2,
                        'status' => self::STATUS_1,
                        'offer' => array(
                            'id' => 2,
                            'externalId' => $product_add_id,
                            'xmlId' => 'xmlId',
                            'name' => 'Test name 2'
                        ),
                        'properties' => array(),
                        'purchasePrice' => 500
                    )
                )
            )
        );
    }

    private function get_history_data_update($order_id, $api_version)
    {
        $history =  array(
            'success' => true,
            'history'  => array(
                array(
                    'id' => 3,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'status',
                    'oldValue' => array(
                        'code' => 'new'
                    ),
                    'newValue' => array(
                        'code' => self::STATUS_2
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => self::STATUS_2
                    )
                )
            )
        );

        $payment_v5 = array(
            'id' => 4,
            'createdAt' => '2018-01-01 00:03:00',
            'source' => 'user',
            'user' => array(
                'id' => 1
            ),
            'field' => 'payments',
            'oldValue' => null,
            'newValue' => array(
                'code' => 'payment2'
            ),
            'order' => array(
                'id' => 2,
                'externalId' => $order_id,
                'managerId' => 6,
                'site' => 'test-com',
                    'status' => self::STATUS_2
                ),
                'payment' => array(
                    'id' => 1,
                    'type' => 'payment2',
                    "amount" => 100
                )
            );

        $payment_v4 = array(
            'id' => 4,
            'createdAt' => '2018-01-01 00:03:00',
            'source' => 'user',
            'user' => array(
                'id' => 1
            ),
            'field' => 'payment_type',
            'oldValue' => null,
            'newValue' => array(
                'code' => 'payment2'
            ),
            'order' => array(
                'id' => 2,
                'externalId' => $order_id,
                'managerId' => 6,
                'site' => 'test-com',
                'status' => self::STATUS_2
            ),
        );

        if ($api_version == 'v4') {
            array_push($history['history'], $payment_v4);
        }

        if ($api_version == 'v5') {
            array_push($history['history'], $payment_v5);
        }

        return $history;
    }
}
