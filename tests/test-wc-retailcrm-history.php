<?php

use datasets\DataHistoryRetailCrm;

class WC_Retailcrm_History_Test extends WC_Retailcrm_Test_Case_Helper
{
    /** @var WC_Retailcrm_Proxy */
    protected $apiMock;

    /** @var WC_Retailcrm_Response_Helper */
    protected $customersHistoryResponse;

    /** @var WC_Retailcrm_Response_Helper */
    protected $ordersHistoryResponse;

    public function setUp()
    {
        $this->regenerateMocks();
        parent::setUp();
    }

    public function test_history_order_create()
    {
        $product = WC_Helper_Product::create_simple_product();
        $order = DataHistoryRetailCrm::get_history_data_new_order($product->get_id());

        $this->mockHistory(true, DataHistoryRetailCrm::empty_history(), $order);

        $retailcrm_history = new WC_Retailcrm_History($this->apiMock);
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

        $this->assertEquals('status1', $options[$order_added->get_status()]);

        if (is_object($order_added_item)) {
            $this->assertEquals($product->get_id(), $order_added_item->get_product()->get_id());
        }

        $this->assertNotEmpty($order_added->get_date_created());
        $this->assertEquals("2018-01-01 00:00:00", $order['history'][0]['createdAt']);
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

    public function test_history_order_create_deleted_items()
    {
        $product = WC_Helper_Product::create_simple_product();
        $product_deleted = WC_Helper_Product::create_simple_product();

        $order = DataHistoryRetailCrm::get_history_data_new_order_deleted_items(
            $product->get_id(),
            $product_deleted->get_id()
        );

        $this->mockHistory(true, DataHistoryRetailCrm::empty_history(), $order);

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $orders = wc_get_orders(array('numberposts' => -1));
        $order_added = end($orders);

        if (!$order_added) {
            $this->fail('$order_added is null - no orders were added after receiving history');
        }

        $order_added_items = $order_added->get_items();
        $this->assertEquals(1, count($order_added_items));

        $order_added_item = reset($order_added_items);
        $shipping_address = $order_added->get_address('shipping');
        $billing_address = $order_added->get_address('billing');
        $options = get_option(\WC_Retailcrm_Base::$option_key);
        $this->assertEquals('status1', $options[$order_added->get_status()]);

        if (is_object($order_added_item)) {
            $this->assertEquals($product->get_id(), $order_added_item->get_product()->get_id());
        }

        $this->assertNotEmpty($order_added->get_date_created());
        $this->assertEquals("2018-01-01 00:00:00", $order['history'][0]['createdAt']);
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_data_product_add($product->get_id(), $order->get_id())
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_data_update($order->get_id())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $order_updated = wc_get_order($order->get_id());
        $options = get_option(\WC_Retailcrm_Base::$option_key);

        $this->assertEquals('status4', $options[$order_updated->get_status()]);
        $this->assertEquals('payment2', $options[$order_updated->get_payment_method()]);
        $this->assertEquals('customerComment', $order_updated->get_customer_note());
        $this->assertEquals(12345678, $order_updated->get_billing_phone());
        $this->assertEquals('tester001@example.com', $order_updated->get_billing_email());

        //Check order note
        $notes = wc_get_order_notes(array('limit' => 100, 'order_id' => $order->get_id()));

        foreach ($notes as $note) {
            if ($note->content === 'managerComment') {
                $managerComment = $note->content;
            }
        }

        $this->assertEquals('managerComment', $managerComment);
    }

    public function test_history_order_update_empty_order()
    {
        $order = WC_Helper_Order::create_order(0);
        $order->set_status('');

        $this->mockHistory(
            true,
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_data_update($order->get_id())
        );

        $retailcrm_history = new \WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $order_updated = wc_get_order($order->get_id());

        $this->assertNotEquals('status4', $order_updated->get_status());
    }


    public function test_history_customer_create()
    {
        $this->mockHistory(
            true,
            DataHistoryRetailCrm::get_history_data_new_customer(),
            DataHistoryRetailCrm::empty_history()
        );

        $retailcrm_history = new WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $sinceId = get_option('retailcrm_orders_history_since_id');

        $this->assertEquals(0, $sinceId);
    }

    public function test_history_customer_update()
    {
        $customerId = wc_create_new_customer('mail_test@mail.es', 'test');
        $customer = DataHistoryRetailCrm::get_history_data_new_customer();

        $customer['history'][0]['customer']['externalId'] = $customerId;

        $this->mockHistory(
            true,
            $customer,
            DataHistoryRetailCrm::empty_history()
        );

        $retailcrm_history = new WC_Retailcrm_History($this->apiMock);
        $retailcrm_history->getHistory();

        $wcCustomer = new WC_Customer($customerId);

        $this->assertNotEmpty($wcCustomer);
        $this->assertEquals('Test_Name', $wcCustomer->get_first_name());
        $this->assertEquals('City', $wcCustomer->get_billing_city());
        $this->assertEquals(123456, $wcCustomer->get_billing_postcode());
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_order_for_client_replace($product->get_id())
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_change_to_another_individual($order_id)
        );

        $this->ordersGetMock(
            true,
            DataHistoryRetailCrm::get_order_with_customer_and_contact(DataHistoryRetailCrm::get_new_individual_for_order())
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_change_to_corporate($order_id)
        );

        $this->ordersGetMock(
            true,
            DataHistoryRetailCrm::get_order_with_customer_and_contact(
                DataHistoryRetailCrm::get_new_corporate_for_order(),
                DataHistoryRetailCrm::get_new_contact_for_order(),
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_change_to_another_corporate($order_id)
        );

        $this->ordersGetMock(
            true,
            DataHistoryRetailCrm::get_order_with_customer_and_contact(
                DataHistoryRetailCrm::get_another_corporate_for_order(),
                DataHistoryRetailCrm::get_another_contact_for_order(),
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
        $this->assertEquals('', $order->get_shipping_city());
        $this->assertEquals('', $order->get_shipping_postcode());
        $this->assertEquals('', $order->get_shipping_state());
        $this->assertEquals('', $order->get_shipping_country());
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_change_only_company($order_id)
        );

        $this->ordersGetMock(
            true,
            DataHistoryRetailCrm::get_order_with_customer_and_contact(
                DataHistoryRetailCrm::get_another_corporate_for_order(),
                DataHistoryRetailCrm::get_another_contact_for_order(),
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
        $this->assertEquals('', $order->get_shipping_city());
        $this->assertEquals('', $order->get_shipping_postcode());
        $this->assertEquals('', $order->get_shipping_state());
        $this->assertEquals('', $order->get_shipping_country());
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_change_only_contact($order_id)
        );

        $this->ordersGetMock(
            true,
            DataHistoryRetailCrm::get_order_with_customer_and_contact(
                DataHistoryRetailCrm::get_another_corporate_for_order(),
                DataHistoryRetailCrm::get_contact_when_only_contact_changed(),
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
        $this->assertEquals('', $order->get_shipping_city());
        $this->assertEquals('', $order->get_shipping_postcode());
        $this->assertEquals('', $order->get_shipping_state());
        $this->assertEquals('', $order->get_shipping_country());
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
            DataHistoryRetailCrm::empty_history(),
            DataHistoryRetailCrm::get_history_change_from_corporate_to_individual($order_id)
        );

        $this->ordersGetMock(
            true,
            DataHistoryRetailCrm::get_order_with_customer_and_contact(DataHistoryRetailCrm::get_initial_regular_customer())
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
        $this->assertEquals('', $order->get_billing_city());
        $this->assertEquals('', $order->get_billing_postcode());
        $this->assertEquals('', $order->get_billing_state());
        $this->assertEquals('', $order->get_billing_country());
        $this->assertEquals('', $order->get_shipping_city());
        $this->assertEquals('', $order->get_shipping_postcode());
        $this->assertEquals('', $order->get_shipping_state());
        $this->assertEquals('', $order->get_shipping_country());
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
                     ->setMethods(array('isSuccessful'))
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
     * @param $isSuccessfulResponse
     * @param array $customerHistoryResponse
     * @param array $orderHistoryResponse
     */
    private function mockHistory($isSuccessfulResponse, $customerHistoryResponse, $orderHistoryResponse)
    {
        $this->setOptions();

        if (!add_option('retailcrm_orders_history_since_id', 0)) {
            update_option('retailcrm_orders_history_since_id', 1);
        }

        if (!add_option('retailcrm_customers_history_since_id', 0)) {
            update_option('retailcrm_customers_history_since_id', 1);
        }

        $this->customersHistoryResponse->expects($this->any())
                                       ->method('isSuccessful')
                                       ->willReturn($isSuccessfulResponse);

        $this->ordersHistoryResponse->expects($this->any())
                                    ->method('isSuccessful')
                                    ->willReturn($isSuccessfulResponse);

        $this->customersHistoryResponse->setResponse($customerHistoryResponse);
        $this->ordersHistoryResponse->setResponse($orderHistoryResponse);

        $this->apiMock->expects($this->any())
                      ->method('customersHistory')
                      ->willReturn($this->customersHistoryResponse);

        $this->apiMock->expects($this->any())
                      ->method('ordersHistory')
                      ->willReturn($this->ordersHistoryResponse);
    }

    private function regenerateMocks()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
                              ->disableOriginalConstructor()
                              ->setMethods(array('ordersHistory', 'customersHistory', 'ordersGet'))
                              ->getMock();

        $this->customersHistoryResponse = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                                               ->disableOriginalConstructor()
                                               ->setMethods(array('isSuccessful'))
                                               ->getMock();

        $this->ordersHistoryResponse = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                                            ->disableOriginalConstructor()
                                            ->setMethods(array('isSuccessful'))
                                            ->getMock();
    }
}
