<?php

class WC_Retailcrm_History_Test extends WC_Retailcrm_Test_Case_Helper
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\WC_Retailcrm_Proxy */
    protected $apiMock;

    /** @var WC_Retailcrm_Response_Helper */
    protected $customersHistoryResponse;

    /** @var WC_Retailcrm_Response_Helper */
    protected $ordersHistoryResponse;

    const STATUS_1 = 'status1';
    const STATUS_2 = 'status4';

    public function setUp()
    {
        $this->regenerateMocks();
        parent::setUp();
    }

    public function test_history_order_create()
    {
        $product = WC_Helper_Product::create_simple_product();
        $order = $this->get_history_data_new_order($product->get_id());

        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $order
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $orders = wc_get_orders(array('numberposts' => -1));
        $order_added = end($orders);

        if (!$order_added) {
	        $this->fail('$order_added is null - no orders were added after receiving history');
        }

        $order_added_items = $order_added->get_items();
        $order_added_item = reset($order_added_items);
        $shipping_address = $order_added->get_address('shipping');
        $billing_address = $order_added->get_address('billing');
        $options = get_option(\WC_Retailcrm_Base::$option_key);
        $this->assertEquals(self::STATUS_1, $options[$order_added->get_status()]);

        if (is_object($order_added_item)) {
            $this->assertEquals($product->get_id(), $order_added_item->get_product()->get_id());
        }

        $this->assertNotEmpty($order_added->get_date_created());
        $this->assertEquals($order_added->get_date_created()->date('Y-m-d H:i:s'), $order['history'][0]['createdAt']);
        $this->assertNotEmpty($shipping_address['first_name']);
        $this->assertNotEmpty($shipping_address['last_name']);
        $this->assertNotEmpty($shipping_address['postcode']);
        $this->assertNotEmpty($shipping_address['city']);
        $this->assertNotEmpty($shipping_address['country']);
        $this->assertNotEmpty($shipping_address['state']);

        if (isset($billing_address['phone'])) {
            $this->assertNotEmpty($billing_address['phone']);
        }

        if (isset($billing_address['email'])) {
            $this->assertNotEmpty($billing_address['email']);
        }

        $this->assertNotEmpty($billing_address['first_name']);
        $this->assertNotEmpty($billing_address['last_name']);
        $this->assertNotEmpty($billing_address['postcode']);
        $this->assertNotEmpty($billing_address['city']);
        $this->assertNotEmpty($billing_address['country']);
        $this->assertNotEmpty($billing_address['state']);

        if ($order_added->get_payment_method()) {
            $this->assertEquals('payment4', $options[$order_added->get_payment_method()]);
        }
    }

    public function test_history_order_add_product()
    {
        $product = WC_Helper_Product::create_simple_product();
        $order = WC_Helper_Order::create_order(0);

        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_data_product_add($product->get_id(), $order->get_id())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $order_updated = wc_get_order($order->get_id());
        $order_updated_items = $order_updated->get_items();
        $order_updated_item = end($order_updated_items);

        $this->assertEquals(2, count($order_updated_items));
        $this->assertEquals(2, $order_updated_item->get_quantity());
        $this->assertEquals($product->get_id(), $order_updated_item->get_product()->get_id());
    }

    public function test_history_order_update()
    {
        $order = WC_Helper_Order::create_order(0);

        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_data_update($order->get_id())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $order_updated = wc_get_order($order->get_id());
        $options = get_option(\WC_Retailcrm_Base::$option_key);

        $this->assertEquals(self::STATUS_2, $options[$order_updated->get_status()]);
        $this->assertEquals('payment2', $options[$order_updated->get_payment_method()]);
    }

    public function test_history_switch_customer_tests()
    {
        $this->deleteAllData();
        $this->regenerateMocks();
        $order_id = $this->history_order_create_for_changing_customer();
        $this->assertNotEmpty($order_id);

        $this->regenerateMocks();
        $this->history_order_switch_customer($order_id);

        $this->regenerateMocks();
        $this->history_order_switch_customer_to_corporate($order_id);

        $this->regenerateMocks();
        $this->history_order_switch_customer_to_another_corporate($order_id);

        $this->regenerateMocks();
        $this->history_order_switch_only_company($order_id);

        $this->regenerateMocks();
        $this->history_order_switch_only_contact($order_id);

        $this->regenerateMocks();
        $this->history_order_switch_back_to_individual($order_id);
    }

    public function history_order_create_for_changing_customer()
    {
        $product = WC_Helper_Product::create_simple_product();

        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_order_for_client_replace($product->get_id())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $orders = wc_get_orders(array('numberposts' => -1));
        $order_added = end($orders);

        if (!$order_added) {
            $this->fail('$order_added is null - no orders were added after receiving history');
        }

        $this->assertEquals('tester001@example.com', $order_added->get_billing_email());
        $this->assertNotEmpty($order_added->get_id());

        return $order_added->get_id();
    }

    /**
     * @param int $order_id
     *
     * @throws \Exception
     */
    public function history_order_switch_customer($order_id)
    {
        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_change_to_another_individual($order_id)
        );

        $this->ordersGetMock(
            true,
            $this->get_order_with_customer_and_contact($this->get_new_individual_for_order())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        try {
            $order = new WC_Order($order_id);
        } catch (\Exception $exception) {
            $post = get_post($order_id);

            if (!$post instanceof WP_Post) {
                $this->fail(sprintf('Cannot find order with id=%d', $order_id));
            }

            if (!in_array($post->post_type, wc_get_order_types())) {
                $this->fail(sprintf(
                    'Invalid order post type `%s`. Should be one of these: %s',
                    $post->post_type,
                    implode(', ', wc_get_order_types())
                ));
            } else {
                $this->fail(sprintf(
                    'Cannot determine what\'s wrong with order id=%d. Message from WooCommerce: %s',
                    $order_id,
                    $exception->getMessage()
                ));
            }

            return;
        }

        $this->assertEquals('tester002', $order->get_billing_first_name());
        $this->assertEquals('tester002', $order->get_billing_last_name());
        $this->assertEquals('ewtrhibehb126879@example.com', $order->get_billing_email());
        $this->assertEquals('с. Верхненазаровское', $order->get_billing_city());
        $this->assertEquals('34000', $order->get_billing_postcode());
        $this->assertEquals('Адыгея Республика', $order->get_billing_state());
        $this->assertEquals('с. Верхненазаровское', $order->get_shipping_city());
        $this->assertEquals('34000', $order->get_shipping_postcode());
        $this->assertEquals('Адыгея Республика', $order->get_shipping_state());
        $this->assertEquals('34687453268933', $order->get_billing_phone());
        $this->assertEmpty($order->get_billing_company());
        $this->assertEmpty($order->get_customer_id());
    }

    /**
     * @param int $order_id
     *
     * @throws \Exception
     */
    public function history_order_switch_customer_to_corporate($order_id)
    {
        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_change_to_corporate($order_id)
        );

        $this->ordersGetMock(
            true,
            $this->get_order_with_customer_and_contact(
                $this->get_new_corporate_for_order(),
                $this->get_new_contact_for_order(),
                array('name' => 'Компания1'),
                'legal-entity'
            )
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        try {
            $order = new WC_Order($order_id);
        } catch (\Exception $exception) {
            $post = get_post($order_id);

            if (!$post instanceof WP_Post) {
                $this->fail(sprintf('Cannot find order with id=%d', $order_id));
            }

            if (!in_array($post->post_type, wc_get_order_types())) {
                $this->fail(sprintf(
                    'Invalid order post type `%s`. Should be one of these: %s',
                    $post->post_type,
                    implode(', ', wc_get_order_types())
                ));
            } else {
                $this->fail(sprintf(
                    'Cannot determine what\'s wrong with order id=%d. Message from WooCommerce: %s',
                    $order_id,
                    $exception->getMessage()
                ));
            }

            return;
        }

        $this->assertEquals('psycho913', $order->get_billing_first_name());
        $this->assertEquals('psycho913', $order->get_billing_last_name());
        $this->assertEquals('psycho913@example.com', $order->get_billing_email());
        $this->assertEquals('Валдгейм', $order->get_shipping_city());
        $this->assertEquals('344091', $order->get_shipping_postcode());
        $this->assertEquals('Еврейская Автономная область', $order->get_shipping_state());
        $this->assertEquals('Компания1', $order->get_billing_company());
        $this->assertEquals('9135487458709', $order->get_billing_phone());
        $this->assertEmpty($order->get_customer_id());
    }

    /**
     * @param int $order_id
     *
     * @throws \Exception
     */
    public function history_order_switch_customer_to_another_corporate($order_id)
    {
        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_change_to_another_corporate($order_id)
        );

        $this->ordersGetMock(
            true,
            $this->get_order_with_customer_and_contact(
                $this->get_another_corporate_for_order(),
                $this->get_another_contact_for_order(),
                array('name' => 'TestCompany3428769'),
                'legal-entity'
            )
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        try {
            $order = new WC_Order($order_id);
        } catch (\Exception $exception) {
            $post = get_post($order_id);

            if (!$post instanceof WP_Post) {
                $this->fail(sprintf('Cannot find order with id=%d', $order_id));
            }

            if (!in_array($post->post_type, wc_get_order_types())) {
                $this->fail(sprintf(
                    'Invalid order post type `%s`. Should be one of these: %s',
                    $post->post_type,
                    implode(', ', wc_get_order_types())
                ));
            } else {
                $this->fail(sprintf(
                    'Cannot determine what\'s wrong with order id=%d. Message from WooCommerce: %s',
                    $order_id,
                    $exception->getMessage()
                ));
            }

            return;
        }

        $this->assertEquals('Tester4867', $order->get_billing_first_name());
        $this->assertEquals('Tester4867', $order->get_billing_last_name());
        $this->assertEquals('tester4867@example.com', $order->get_billing_email());
        $this->assertEquals('TestCompany3428769', $order->get_billing_company());
        $this->assertEquals('--', $order->get_shipping_city());
        $this->assertEquals('--', $order->get_shipping_postcode());
        $this->assertEquals('--', $order->get_shipping_state());
        $this->assertEquals('--', $order->get_shipping_country());
        $this->assertEquals('', $order->get_billing_phone());
        $this->assertEmpty($order->get_customer_id());
    }

    /**
     * @param int $order_id
     *
     * @throws \Exception
     */
    public function history_order_switch_only_company($order_id)
    {
        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_change_only_company($order_id)
        );

        $this->ordersGetMock(
            true,
            $this->get_order_with_customer_and_contact(
                $this->get_another_corporate_for_order(),
                $this->get_another_contact_for_order(),
                array('name' => 'TestCompany017089465'),
                'legal-entity'
            )
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        try {
            $order = new WC_Order($order_id);
        } catch (\Exception $exception) {
            $post = get_post($order_id);

            if (!$post instanceof WP_Post) {
                $this->fail(sprintf('Cannot find order with id=%d', $order_id));
            }

            if (!in_array($post->post_type, wc_get_order_types())) {
                $this->fail(sprintf(
                    'Invalid order post type `%s`. Should be one of these: %s',
                    $post->post_type,
                    implode(', ', wc_get_order_types())
                ));
            } else {
                $this->fail(sprintf(
                    'Cannot determine what\'s wrong with order id=%d. Message from WooCommerce: %s',
                    $order_id,
                    $exception->getMessage()
                ));
            }

            return;
        }

        $this->assertEquals('Tester4867', $order->get_billing_first_name());
        $this->assertEquals('Tester4867', $order->get_billing_last_name());
        $this->assertEquals('tester4867@example.com', $order->get_billing_email());
        $this->assertEquals('TestCompany017089465', $order->get_billing_company());
        $this->assertEquals('--', $order->get_shipping_city());
        $this->assertEquals('--', $order->get_shipping_postcode());
        $this->assertEquals('--', $order->get_shipping_state());
        $this->assertEquals('--', $order->get_shipping_country());
        $this->assertEquals('', $order->get_billing_phone());
        $this->assertEmpty($order->get_customer_id());
    }

    /**
     * @param int $order_id
     *
     * @throws \Exception
     */
    public function history_order_switch_only_contact($order_id)
    {
        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_change_only_contact($order_id)
        );

        $this->ordersGetMock(
            true,
            $this->get_order_with_customer_and_contact(
                $this->get_another_corporate_for_order(),
                $this->get_contact_when_only_contact_changed(),
                array('name' => 'TestCompany017089465'),
                'legal-entity'
            )
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        try {
            $order = new WC_Order($order_id);
        } catch (\Exception $exception) {
            $post = get_post($order_id);

            if (!$post instanceof WP_Post) {
                $this->fail(sprintf('Cannot find order with id=%d', $order_id));
            }

            if (!in_array($post->post_type, wc_get_order_types())) {
                $this->fail(sprintf(
                    'Invalid order post type `%s`. Should be one of these: %s',
                    $post->post_type,
                    implode(', ', wc_get_order_types())
                ));
            } else {
                $this->fail(sprintf(
                    'Cannot determine what\'s wrong with order id=%d. Message from WooCommerce: %s',
                    $order_id,
                    $exception->getMessage()
                ));
            }

            return;
        }

        $this->assertEquals('Tester2890', $order->get_billing_first_name());
        $this->assertEquals('Tester2890', $order->get_billing_last_name());
        $this->assertEquals('tester2890@example.com', $order->get_billing_email());
        $this->assertEquals('TestCompany017089465', $order->get_billing_company());
        $this->assertEquals('--', $order->get_shipping_city());
        $this->assertEquals('--', $order->get_shipping_postcode());
        $this->assertEquals('--', $order->get_shipping_state());
        $this->assertEquals('--', $order->get_shipping_country());
        $this->assertEquals('32418790888', $order->get_billing_phone());
        $this->assertEmpty($order->get_customer_id());
    }

    /**
     * @param int $order_id
     *
     * @throws \Exception
     */
    public function history_order_switch_back_to_individual($order_id)
    {
        $this->mockHistory(
            true,
            true,
            $this->empty_history(),
            $this->get_history_change_from_corporate_to_individual($order_id)
        );

        $this->ordersGetMock(
            true,
            $this->get_order_with_customer_and_contact($this->get_initial_regular_customer())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        try {
            $order = new WC_Order($order_id);
        } catch (\Exception $exception) {
            $post = get_post($order_id);

            if (!$post instanceof WP_Post) {
                $this->fail(sprintf('Cannot find order with id=%d', $order_id));
            }

            if (!in_array($post->post_type, wc_get_order_types())) {
                $this->fail(sprintf(
                    'Invalid order post type `%s`. Should be one of these: %s',
                    $post->post_type,
                    implode(', ', wc_get_order_types())
                ));
            } else {
                $this->fail(sprintf(
                    'Cannot determine what\'s wrong with order id=%d. Message from WooCommerce: %s',
                    $order_id,
                    $exception->getMessage()
                ));
            }

            return;
        }

        $this->assertEquals('tester001', $order->get_billing_first_name());
        $this->assertEquals('tester001', $order->get_billing_last_name());
        $this->assertEquals('tester001@example.com', $order->get_billing_email());
        $this->assertEquals('--', $order->get_billing_city());
        $this->assertEquals('--', $order->get_billing_postcode());
        $this->assertEquals('--', $order->get_billing_state());
        $this->assertEquals('--', $order->get_billing_country());
        $this->assertEquals('--', $order->get_shipping_city());
        $this->assertEquals('--', $order->get_shipping_postcode());
        $this->assertEquals('--', $order->get_shipping_state());
        $this->assertEquals('--', $order->get_shipping_country());
        $this->assertEquals('2354708915097', $order->get_billing_phone());
        $this->assertEquals('ул. Пушкина дом Колотушкина', $order->get_billing_address_1());
        $this->assertEmpty($order->get_billing_company());
        $this->assertEmpty($order->get_customer_id());
    }

    /**
     * Mock ordersGet response.
     *
     * @param bool $isSuccessful
     * @param array $response
     */
    private function ordersGetMock($isSuccessful, $response)
    {
        $mock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $mock->expects($this->any())
            ->method('isSuccessful')
            ->willReturn($isSuccessful);

        $mock->setResponse($response);
        $this->apiMock->expects($this->any())->method('ordersGet')->willReturn($mock);
    }

    /**
     * Mocks customers and orders history responses with provided data
     *
     * @param bool $isSuccessfulCustomers
     * @param bool $isSuccessfulOrders
     * @param array $customersHistoryResponse
     * @param array $orderHistoryResponse
     */
    private function mockHistory(
        $isSuccessfulCustomers,
        $isSuccessfulOrders,
        $customersHistoryResponse,
        $orderHistoryResponse
    ) {
        $this->setOptions();

        if (!add_option('retailcrm_orders_history_since_id', 0)) {
            update_option('retailcrm_orders_history_since_id', 0);
        }

        if (!add_option('retailcrm_customers_history_since_id', 0)) {
            update_option('retailcrm_customers_history_since_id', 0);
        }

        $this->customersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn($isSuccessfulCustomers);

        $this->ordersHistoryResponse->expects($this->any())
            ->method('isSuccessful')
            ->willReturn($isSuccessfulOrders);

        $this->customersHistoryResponse->setResponse($customersHistoryResponse);
        $this->ordersHistoryResponse->setResponse($orderHistoryResponse);

        $this->apiMock->expects($this->any())->method('customersHistory')->willReturn($this->customersHistoryResponse);
        $this->apiMock->expects($this->any())->method('ordersHistory')->willReturn($this->ordersHistoryResponse);
    }

    private function regenerateMocks()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'ordersHistory',
                'customersHistory',
                'ordersGet'
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
    }

    private function empty_history()
    {
        return array(
            'success' => true,
            'history' => array(),
            "pagination" => array(
                "limit" => 100,
                "totalCount" => 0,
                "currentPage" => 1,
                "totalPageCount" => 0
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
                        	'type' => 'customer',
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
                                'externalIds' =>array(
                                    array(
                                        'code' =>'woocomerce',
                                        'value' =>"160_".$product_create_id
                                    )
                                ),
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
            ),
            "pagination" => array(
                "limit" => 100,
                "totalCount" => 1,
                "currentPage" => 1,
                "totalPageCount" => 1
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
                        'externalIds' =>array(
                            array(
                                'code' =>'woocomerce',
                                'value' =>"160_".$product_add_id
                            )
                        ),
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
            ),
            "pagination" => array(
                "limit" => 100,
                "totalCount" => 1,
                "currentPage" => 1,
                "totalPageCount" => 1
            )
        );
    }

    private function get_history_data_update($order_id)
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
            ),
            "pagination" => array(
                "limit" => 100,
                "totalCount" => 1,
                "currentPage" => 1,
                "totalPageCount" => 1
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

        array_push($history['history'], $payment_v5);

        return $history;
    }

    private function get_history_order_for_client_replace($productId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-04 15:05:39',
            'history' => array(array(
                'id' => 25011,
                'createdAt' => '2020-06-04 15:05:10',
                'created' => true,
                'source' => 'user',
                'field' => 'status',
                'oldValue' => NULL,
                'newValue' => array ('code' => 'new'),
                'order' => array (
                    'slug' => 5868,
                    'id' => 5868,
                    'number' => '5868C',
                    'orderType' => 'test',
                    'orderMethod' => 'phone',
                    'countryIso' => 'RU',
                    'createdAt' => '2020-06-04 15:05:10',
                    'statusUpdatedAt' => '2020-06-04 15:05:10',
                    'summ' => 16,
                    'totalSumm' => 16,
                    'prepaySum' => 0,
                    'purchaseSumm' => 0,
                    'markDatetime' => '2020-06-04 15:05:10',
                    'lastName' => 'tester001',
                    'firstName' => 'tester001',
                    'phone' => '2354708915097',
                    'email' => 'tester001@example.com',
                    'call' => false,
                    'expired' => false,
                    'managerId' => 27,
                    'customer' => array(
                        'type' => 'customer',
                        'id' => 4228,
                        'externalId' => '2',
                        'isContact' => false,
                        'createdAt' => '2020-06-01 15:31:46',
                        'managerId' => 27,
                        'vip' => false,
                        'bad' => false,
                        'site' => 'bitrix-test',
                        'contragent' => array(
                            'contragentType' => 'individual',
                        ),
                        'tags' => array(),
                        'marginSumm' => 9412,
                        'totalSumm' => 9412,
                        'averageSumm' => 9412,
                        'ordersCount' => 1,
                        'costSumm' => 0,
                        'customFields' => array(),
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => array(
                            'id' => 3132,
                            'text' => 'ул. Пушкина дом Колотушкина',
                        ),
                        'segments' => array(),
                        'firstName' => 'tester001',
                        'lastName' => 'tester001',
                        'email' => 'tester001@example.com',
                        'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
                        'phones' => array(array('number' => '2354708915097'))
                    ),
                    'contact' => array(
                        'type' => 'customer',
                        'id' => 4228,
                        'externalId' => '2',
                        'isContact' => false,
                        'createdAt' => '2020-06-01 15:31:46',
                        'managerId' => 27,
                        'vip' => false,
                        'bad' => false,
                        'site' => 'bitrix-test',
                        'contragent' => array(
                            'contragentType' => 'individual',
                        ),
                        'tags' => array(),
                        'marginSumm' => 9412,
                        'totalSumm' => 9412,
                        'averageSumm' => 9412,
                        'ordersCount' => 1,
                        'costSumm' => 0,
                        'customFields' => array(),
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => array(
                            'id' => 3132,
                            'text' => 'ул. Пушкина дом Колотушкина',
                        ),
                        'segments' => array(),
                        'firstName' => 'tester001',
                        'lastName' => 'tester001',
                        'email' => 'tester001@example.com',
                        'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
                        'phones' => array(array('number' => '2354708915097'))
                    ),
                    'contragent' => array(
                        'contragentType' => 'individual',
                    ),
                    'delivery' => array(
                        'cost' => 0,
                        'netCost' => 0,
                        'address' => array(
                            'id' => 5864,
                            'countryIso' => 'RU',
                            'text' => 'ул. Пушкина дом Колотушкина',
                        ),
                    ),
                    'site' => 'woocommerce',
                    'status' => 'new',
                    'items' => array(
                        array(
                            'id' => 160,
                            'initialPrice' => 100,
                            'createdAt' => '2018-01-01 00:00:00',
                            'quantity' => 1,
                            'status' => 'new',
                            'externalIds' =>array(
                                array(
                                    'code' =>'woocomerce',
                                    'value' =>"160_".$productId
                                )
                            ),
                            'offer' => array(
                                'id' => 1,
                                'externalId' => $productId,
                                'xmlId' => '1',
                                'name' => 'Test name',
                                'vatRate' => 'none'
                            ),
                            'properties' => array(),
                            'purchasePrice' => 50
                        )
                    ),
                    'fromApi' => false,
                    'length' => 0,
                    'width' => 0,
                    'height' => 0,
                    'shipmentStore' => 'main',
                    'shipped' => false,
                    'customFields' => array()
                )
            )),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1,
            )
        );
    }

    private function get_history_change_to_another_individual($orderExternalId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-05 12:29:14',
            'history' => array(
                array(
                    'id' => 25398,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contact',
                    'oldValue' => array(
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ),
                    'newValue' => array(
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25399,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'customer',
                    'oldValue' => array(
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ),
                    'newValue' => array(
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25400,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'email',
                    'oldValue' => 'tester001@example.com',
                    'newValue' => 'ewtrhibehb126879@example.com',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25401,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'first_name',
                    'oldValue' => 'tester001',
                    'newValue' => 'tester002',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25402,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'last_name',
                    'oldValue' => 'tester001',
                    'newValue' => 'tester002',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25403,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'patronymic',
                    'oldValue' => null,
                    'newValue' => 'tester002',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25404,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'phone',
                    'oldValue' => '2354708915097',
                    'newValue' => '34687453268933',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 7,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ),
        );
    }

    private function get_history_change_to_corporate($orderExternalId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-05 15:24:19',
            'history' => array(
                array(
                    'id' => 25744,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'delivery_address.city',
                    'oldValue' => 'с. Верхненазаровское',
                    'newValue' => 'Валдгейм',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25745,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'delivery_address.index',
                    'oldValue' => '34000',
                    'newValue' => '344091',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25746,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'delivery_address.region',
                    'oldValue' => 'Адыгея Республика',
                    'newValue' => 'Еврейская Автономная область',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25747,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contragent.contragent_type',
                    'oldValue' => 'individual',
                    'newValue' => 'legal-entity',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25748,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contragent.legal_address',
                    'oldValue' => null,
                    'newValue' => '344090 * Москва упцупуцйпуц йцавафыафыафыафы',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25749,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'company',
                    'oldValue' => null,
                    'newValue' => array(
                        'id' => 591,
                        'name' => 'Компания1',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25750,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contact',
                    'oldValue' => array(
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ),
                    'newValue' => array(
                        'id' => 4219,
                        'externalId' => '4',
                        'site' => 'woocommerce',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25751,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'customer',
                    'oldValue' => array(
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ),
                    'newValue' => array(
                        'id' => 4220,
                        'site' => 'woocommerce',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25752,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'email',
                    'oldValue' => 'ewtrhibehb126879@example.com',
                    'newValue' => 'psycho913@example.com',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25753,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'first_name',
                    'oldValue' => 'tester002',
                    'newValue' => 'psycho913',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25754,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'last_name',
                    'oldValue' => 'tester002',
                    'newValue' => 'psycho913',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25755,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'patronymic',
                    'oldValue' => 'tester002',
                    'newValue' => null,
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25756,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'phone',
                    'oldValue' => '34687453268933',
                    'newValue' => '9135487458709',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 13,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ),
        );
    }

    private function get_history_change_to_another_corporate($orderExternalId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-05 16:37:53',
            'history' => array(
                array(
                    'id' => 25979,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contragent.legal_address',
                    'oldValue' => '344090 * Москва упцупуцйпуц йцавафыафыафыафы',
                    'newValue' => null,
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25980,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'company',
                    'oldValue' => array(
                        'id' => 591,
                        'name' => 'Компания1',
                    ),
                    'newValue' => array(
                        'id' => 621,
                        'name' => 'TestCompany3428769',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25981,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contact',
                    'oldValue' => array(
                        'id' => 4219,
                        'externalId' => '4',
                        'site' => 'woocommerce',
                    ),
                    'newValue' => array(
                        'id' => 4304,
                        'site' => 'woocommerce',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25982,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'customer',
                    'oldValue' => array(
                        'id' => 4220,
                        'site' => 'woocommerce',
                    ),
                    'newValue' => array(
                        'id' => 4303,
                        'site' => 'woocommerce',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25983,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'email',
                    'oldValue' => 'psycho913@example.com',
                    'newValue' => 'tester4867@example.com',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25984,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'first_name',
                    'oldValue' => 'psycho913',
                    'newValue' => 'Tester4867',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25985,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'last_name',
                    'oldValue' => 'psycho913',
                    'newValue' => 'Tester4867',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25986,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'patronymic',
                    'oldValue' => null,
                    'newValue' => 'Tester4867',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25987,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'phone',
                    'oldValue' => '9135487458709',
                    'newValue' => null,
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 9,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ),
        );
    }

    private function get_history_change_only_company($orderExternalId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-05 17:13:23',
            'history' => array(
                array(
                    'id' => 25988,
                    'createdAt' => '2020-06-05 17:13:17',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'company',
                    'oldValue' => array(
                        'id' => 621,
                        'name' => 'TestCompany3428769',
                    ),
                    'newValue' => array(
                        'id' => 622,
                        'name' => 'TestCompany017089465',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ),
        );
    }

    private function get_history_change_only_contact($orderExternalId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-05 17:36:28',
            'history' => array(
                array(
                    'id' => 25989,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contact',
                    'oldValue' => array(
                        'id' => 4304,
                        'site' => 'woocommerce',
                    ),
                    'newValue' => array(
                        'id' => 4305,
                        'site' => 'woocommerce',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25990,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'email',
                    'oldValue' => 'tester4867@example.com',
                    'newValue' => 'tester2890@example.com',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25991,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'first_name',
                    'oldValue' => 'Tester4867',
                    'newValue' => 'Tester2890',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25992,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'last_name',
                    'oldValue' => 'Tester4867',
                    'newValue' => 'Tester2890',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25993,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'patronymic',
                    'oldValue' => 'Tester4867',
                    'newValue' => 'Tester2890',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25994,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'phone',
                    'oldValue' => null,
                    'newValue' => '32418790888',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 6,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ),
        );
    }

    private function get_history_change_from_corporate_to_individual($orderExternalId)
    {
        return array(
            'success' => true,
            'generatedAt' => '2020-06-05 17:47:05',
            'history' => array(
                array(
                    'id' => 25995,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contragent.contragent_type',
                    'oldValue' => 'legal-entity',
                    'newValue' => 'individual',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25996,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'contact',
                    'oldValue' => array(
                        'id' => 4305,
                        'site' => 'woocommerce',
                    ),
                    'newValue' => array(
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25997,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'customer',
                    'oldValue' => array(
                        'id' => 4303,
                        'site' => 'woocommerce',
                    ),
                    'newValue' => array(
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ),
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25998,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'email',
                    'oldValue' => 'tester2890@example.com',
                    'newValue' => 'tester001@example.com',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 25999,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'first_name',
                    'oldValue' => 'Tester2890',
                    'newValue' => 'tester001',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 26000,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'last_name',
                    'oldValue' => 'Tester2890',
                    'newValue' => 'tester001',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 26001,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'patronymic',
                    'oldValue' => 'Tester2890',
                    'newValue' => null,
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
                array(
                    'id' => 26002,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => array(
                        'id' => 27,
                    ),
                    'field' => 'phone',
                    'oldValue' => '32418790888',
                    'newValue' => '2354708915097',
                    'order' => array(
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ),
                ),
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 8,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ),
        );
    }

    private function get_initial_regular_customer()
    {
        return array(
            'type' => 'customer',
            'id' => 4228,
            'externalId' => '2',
            'isContact' => false,
            'createdAt' => '2020-06-01 15:31:46',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'bitrix-test',
            'contragent' => array(
                'contragentType' => 'individual',
            ),
            'tags' => array(),
            'marginSumm' => 9428,
            'totalSumm' => 9428,
            'averageSumm' => 4714,
            'ordersCount' => 2,
            'costSumm' => 0,
            'personalDiscount' => 0,
            'address' => array(
                'id' => 3132,
                'text' => 'ул. Пушкина дом Колотушкина',
            ),
            'firstName' => 'tester001',
            'lastName' => 'tester001',
            'email' => 'tester001@example.com',
            'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
            'phones' => array(
                array(
                    'number' => '2354708915097',
                ),
            ),
        );
    }

    private function get_contact_when_only_contact_changed()
    {
        return array(
            'type' => 'customer',
            'id' => 4305,
            'isContact' => true,
            'createdAt' => '2020-06-05 17:11:53',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => array(),
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'costSumm' => 0,
            'customFields' => array(
                'galkatrue' => true,
            ),
            'personalDiscount' => 0,
            'segments' => array(),
            'firstName' => 'Tester2890',
            'lastName' => 'Tester2890',
            'patronymic' => 'Tester2890',
            'email' => 'tester2890@example.com',
            'phones' => array(
                array(
                    'number' => '32418790888',
                ),
            ),
        );
    }

    private function get_order_with_customer_and_contact(
        $customer,
        $contact = array(),
        $company = array(),
        $contragentType = 'individual'
    ) {
        $order = array(
            'success' => true,
            'order' => array(
                'slug' => 5868,
                'id' => 5868,
                'number' => '5868C',
                'externalId' => '85',
                'customer' => $customer,
                'contact' => empty($contact) ? $customer : $contact,
                'orderType' => 'test',
                'orderMethod' => 'phone',
                'countryIso' => 'RU',
                'createdAt' => '2020-06-04 15:05:10',
                'statusUpdatedAt' => '2020-06-04 15:05:10',
                'summ' => 16,
                'totalSumm' => 16,
                'prepaySum' => 0,
                'purchaseSumm' => 0,
                'markDatetime' => '2020-06-04 15:05:10',
                'lastName' => 'tester002',
                'firstName' => 'tester002',
                'patronymic' => 'tester002',
                'phone' => '34687453268933',
                'email' => 'ewtrhibehb126879@example.com',
                'call' => false,
                'expired' => false,
                'managerId' => 27,
                'contragent' => array(
                    'contragentType' => $contragentType,
                ),
                'delivery' => array(
                    'cost' => 0,
                    'netCost' => 0,
                    'address' => array(
                        'index' => '34000',
                        'countryIso' => 'RU',
                        'region' => 'Адыгея Республика',
                        'regionId' => 26,
                        'city' => 'с. Верхненазаровское',
                        'cityId' => 240863,
                        'street' => 'ул. Зеленая',
                        'streetId' => 962815,
                        'building' => '22',
                        'text' => 'ул. Зеленая, д. 22',
                    ),
                ),
                'site' => 'woocommerce',
                'status' => 'new',
                'items' => array(
                    array(
                        'markingCodes' => array(),
                        'id' => 8955,
                        'externalIds' => array(
                            array(
                                'code' => 'woocomerce',
                                'value' => '23_31',
                            ),
                        ),
                        'priceType' => array(
                            'code' => 'base',
                        ),
                        'initialPrice' => 16,
                        'discountTotal' => 0,
                        'vatRate' => 'none',
                        'createdAt' => '2020-06-04 14:54:54',
                        'quantity' => 1,
                        'status' => 'new',
                        'offer' => array(
                            'displayName' => 'Cap',
                            'id' => 67424,
                            'externalId' => '23',
                            'name' => 'Cap',
                            'vatRate' => 'none',
                            'unit' => array(
                                'code' => 'pc',
                                'name' => 'Штука',
                                'sym' => 'шт.',
                            ),
                        ),
                        'properties' => array(),
                        'purchasePrice' => 0,
                    ),
                ),
                'payments' => array(),
                'fromApi' => false,
                'length' => 0,
                'width' => 0,
                'height' => 0,
                'shipmentStore' => 'main',
                'shipped' => false,
                'customFields' => array(
                    'galka' => false,
                    'test_number' => 0,
                    'otpravit_dozakaz' => false,
                ),
            ),
        );

        if (!empty($company)) {
            $order['order']['company'] = $company;
        }

        return $order;
    }

    private function get_new_individual_for_order()
    {
        return array(
            'type' => 'customer',
            'id' => 4231,
            'isContact' => false,
            'createdAt' => '2020-06-01 15:50:33',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'bitrix-test',
            'contragent' => array(
                'contragentType' => 'individual',
            ),
            'tags' => array(),
            'marginSumm' => 2144,
            'totalSumm' => 2144,
            'averageSumm' => 1072,
            'ordersCount' => 2,
            'costSumm' => 0,
            'customFields' => array(
                'galkatrue' => true,
            ),
            'personalDiscount' => 0,
            'address' => array(
                'id' => 3135,
                'index' => '34000',
                'countryIso' => 'RU',
                'region' => 'Адыгея Республика',
                'regionId' => 26,
                'city' => 'с. Верхненазаровское',
                'cityId' => 240863,
                'street' => 'ул. Зеленая',
                'streetId' => 962815,
                'building' => '22',
                'text' => 'ул. Зеленая, д. 22',
            ),
            'firstName' => 'tester002',
            'lastName' => 'tester002',
            'patronymic' => 'tester002',
            'email' => 'ewtrhibehb126879@example.com',
            'phones' => array(
                array(
                    'number' => '34687453268933',
                ),
            ),
        );
    }

    private function get_new_corporate_for_order()
    {
        return array(
            'type' => 'customer_corporate',
            'id' => 4220,
            'nickName' => 'Компания1',
            'mainAddress' => array(
                'id' => 3131,
                'name' => 'Компания2',
            ),
            'createdAt' => '2020-05-27 15:20:33',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => array(),
            'marginSumm' => 604,
            'totalSumm' => 604,
            'averageSumm' => 604,
            'ordersCount' => 1,
            'costSumm' => 0,
            'customFields' => array(
                'galkatrue' => true,
            ),
            'personalDiscount' => 0,
            'mainCustomerContact' => array(
                'id' => 711,
                'customer' => array(
                    'id' => 4219,
                    'externalId' => '4',
                ),
                'companies' => array(),
            ),
            'mainCompany' => array(
                'id' => 591,
                'name' => 'Компания1',
            ),
        );
    }

    private function get_new_contact_for_order()
    {
        return array(
            'type' => 'customer',
            'id' => 4219,
            'externalId' => '4',
            'isContact' => false,
            'createdAt' => '2020-05-27 12:09:00',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'contragent' => array(
                'contragentType' => 'individual',
            ),
            'tags' => array(),
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'costSumm' => 0,
            'customFields' => array(
                'galkatrue' => true,
            ),
            'personalDiscount' => 0,
            'address' => array(
                'id' => 3130,
                'index' => '344091',
                'countryIso' => 'RU',
                'region' => 'Еврейская Автономная область',
                'regionId' => 47,
                'city' => 'Валдгейм',
                'text' => 'упцупуцйпуц, йцавафыафыафыафы',
            ),
            'firstName' => 'psycho913',
            'lastName' => 'psycho913',
            'email' => 'psycho913@example.com',
            'phones' => array(
                array(
                    'number' => '9135487458709',
                ),
            ),
        );
    }

    private function get_another_corporate_for_order()
    {
        return array(
            'type' => 'customer_corporate',
            'id' => 4303,
            'nickName' => 'Another Test Legal Entity',
            'mainAddress' => array(
                'id' => 3177,
                'name' => 'Test Address',
            ),
            'createdAt' => '2020-06-05 16:34:05',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => array(),
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'customFields' => array(
                'galkatrue' => true,
            ),
            'personalDiscount' => 0,
            'mainCustomerContact' => array(
                'id' => 748,
                'customer' => array(
                    'id' => 4304,
                ),
                'companies' => array(
                    array(
                        'id' => 110,
                        'company' => array(
                            'id' => 621,
                            'name' => 'TestCompany3428769',
                        ),
                    ),
                ),
            ),
            'mainCompany' => array(
                'id' => 621,
                'name' => 'TestCompany3428769',
            ),
        );
    }

    private function get_another_contact_for_order()
    {
        return array(
            'type' => 'customer',
            'id' => 4304,
            'isContact' => true,
            'createdAt' => '2020-06-05 16:34:27',
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => array(),
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'personalDiscount' => 0,
            'segments' => array(),
            'firstName' => 'Tester4867',
            'lastName' => 'Tester4867',
            'patronymic' => 'Tester4867',
            'sex' => 'male',
            'email' => 'tester4867@example.com',
            'phones' => array(),
        );
    }
}
