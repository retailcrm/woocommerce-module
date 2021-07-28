<?php

namespace datasets;

class DataHistoryRetailCrm
{
    public static function empty_history()
    {
        return array(
            'success' => true,
            'history' => array(),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 0,
                'currentPage' => 1,
                'totalPageCount' => 0
            )
        );
    }

    public static function get_history_data_new_customer()
    {
        return array(
            'success' => true,
            'history'  => array(
                array (
                    'id' => 18009,
                    'createdAt' => '2021-12-03 13:22:45',
                    'created' => true,
                    'source' => 'user',
                    'user' => array('id' => 11),
                    'field' => 'id',
                    'oldValue' => null,
                    'newValue' => 3758,
                    'customer' => array (
                        'type' => 'customer',
                        'id' => 3758,
                        'isContact' => false,
                        'createdAt' => '2021-12-03 13:22:45',
                        'vip' => false,
                        'bad' => false,
                        'site' => 'woocomerce',
                        'marginSumm' => 0,
                        'totalSumm' => 0,
                        'averageSumm' => 0,
                        'ordersCount' => 0,
                        'customFields' => array(),
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => array (
                            'id' => 3503,
                            'index' => 123456,
                            'countryIso' => 'ES',
                            'region' => 'Region',
                            'city' => 'City',
                            'text' => 'street Test 777',
                        ),
                        'segments' => array(),
                        'firstName' => 'Test_Name',
                        'lastName' => 'Test',
                        'email' => 'mail_test@mail.es',
                        'phones' => array('0' => array('number' => '+79184563200')),
                        'birthday' => '2021-10-01'
                    )
                )
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            )
        );
    }

    public static function get_history_data_new_order($product_create_id)
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
                        'code' => 'status1'
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
                        'status' => 'status1',
                        'items' => array(
                            array(
                                'id' => 160,
                                'initialPrice' => 100,
                                'discountTotal' => 5,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'externalIds' => array(
                                    array(
                                        'code' => 'woocomerce',
                                        'value' => '160_' . $product_create_id
                                    )
                                ),
                                'initialPrice' => 15,
                                'discountTotal' => 1,
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
                                'id' => 1,
                                'type' => 'payment4',
                                'amount' => 100,
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
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            )
        );
    }

    public static function get_history_data_new_order_deleted_items($product_create_id, $product_delete_id)
    {
        return array(
            'success' => true,
            'history' => array(
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
                        'code' => 'status1'
                    ),
                    'order' => array(
                        'slug' => 3,
                        'id' => 3,
                        'number' => '3C',
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
                        'status' => 'status1',
                        'items' => array(
                            array(
                                'id' => 160,
                                'initialPrice' => 15,
                                'discountTotal' => 1,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'externalIds' => array(
                                    array(
                                        'code' => 'woocomerce',
                                        'value' => '160_' . $product_create_id
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
                                'purchasePrice' => 10
                            ),
                            array(
                                'id' => 161,
                                'initialPrice' => 100,
                                'discountTotal' => 5,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'externalIds' => array(
                                    array(
                                        'code' => 'woocomerce',
                                        'value' => '161_' . $product_delete_id
                                    )
                                ),
                                'offer' => array(
                                    'id' => 2,
                                    'externalId' => $product_delete_id,
                                    'xmlId' => '2',
                                    'name' => 'Test name 2',
                                    'vatRate' => 'none'
                                ),
                                'properties' => array(),
                                'purchasePrice' => 50
                            )
                        ),
                        'paymentType' => 'payment4',
                        'payments' => array(
                            array(
                                'id' => 1,
                                'type' => 'payment4',
                                'amount' => 100,
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
                ),
                array(
                    'id' => 2,
                    'createdAt' => '2018-01-01 00:01:00',
                    'source' => 'api',
                    'field' => 'order_product',
                    'oldValue' => array(
                        'id' => 161,
                        'offer' => array(
                            'id' => 2,
                            'externalId' => $product_delete_id
                        )
                    ),
                    'newValue' => null,
                    'order' => array(
                        'id' => 3,
                        'site' => 'test-com',
                        'status' => 'status1'
                    ),
                    'item' => array(
                        'id' => 161,
                        'initialPrice' => 100,
                        'discountTotal' => 5,
                        'createdAt' => '2018-01-01 00:00:00',
                        'quantity' => 1,
                        'status' => 'new',
                        'externalIds' => array(
                            array(
                                'code' => 'woocomerce',
                                'value' => '161_' . $product_delete_id
                            )
                        ),
                        'offer' => array(
                            'id' => 2,
                            'externalId' => $product_delete_id,
                            'xmlId' => '2',
                            'name' => 'Test name 2',
                            'vatRate' => 'none'
                        ),
                        'properties' => array(),
                        'purchasePrice' => 50
                    )
                )
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 2,
                'currentPage' => 1,
                'totalPageCount' => 1
            )
        );
    }

    public static function get_history_data_product_add($product_add_id, $order_id)
    {
        return array(
            'success' => true,
            'history'  => array(
                array(
                    'id' => 2,
                    'createdAt' => '2018-01-01 00:00:01',
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'order_product',
                    'oldValue' => null,
                    'newValue' => array(
                        'id' => 2,
                        'offer' => array(
                            'id' => 2,
                            'externalId' => $product_add_id,
                            'xmlId' => 'xmlId'
                        )
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'site' => 'test-com',
                        'status' => 'status1'
                    ),
                    'item' => array(
                        'id' => 2,
                        'initialPrice' => 999,
                        'createdAt' => '2018-01-01 00:02:00',
                        'quantity' => 2,
                        'status' => 'status1',
                        'externalIds' => array(
                            array(
                                'code' => 'woocomerce',
                                'value' => '160_' . $product_add_id
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
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            )
        );
    }

    public static function get_history_data_update($order_id)
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
                        'code' => 'status4'
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4'
                    )
                ),
                array(
                    'id' => 4,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'managerComment',
                    'oldValue' => array(
                        'code' => ''
                    ),
                    'newValue' => array(
                        'code' => 'managerComment'
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerComment'
                    )
                ),
                array(
                    'id' => 5,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'customerComment',
                    'oldValue' => array(
                        'code' => ''
                    ),
                    'newValue' => array(
                        'code' => 'customerComment'
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerComment',
                        'customerComment' => 'customerComment'
                    )
                ),
                array(
                    'id' => 6,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'phone',
                    'oldValue' => array(
                        'code' => ''
                    ),
                    'newValue' => array(
                        'code' => '12345678'
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerComment',
                        'customerComment' => 'customerComment',
                        'phone' => '12345678'
                    )
                ),
                array(
                    'id' => 7,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => array(
                        'id' => 1
                    ),
                    'field' => 'email',
                    'oldValue' => array(
                        'code' => ''
                    ),
                    'newValue' => array(
                        'code' => 'tester001@example.com'
                    ),
                    'order' => array(
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerComment',
                        'customerComment' => 'customerComment',
                        'phone' => '12345678',
                        'email' => 'tester001@example.com'
                    )
                )
            ),
            'pagination' => array(
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
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
                'status' => 'status4'
            ),
            'payment' => array(
                'id' => 1,
                'type' => 'payment2',
                'amount' => 100
            )
        );

        array_push($history['history'], $payment_v5);

        return $history;
    }

    public static function get_history_order_for_client_replace($productId)
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
                'oldValue' => null,
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
                            'discountTotal' => 5,
                            'createdAt' => '2018-01-01 00:00:00',
                            'quantity' => 1,
                            'status' => 'new',
                            'externalIds' => array(
                                array(
                                    'code' => 'woocomerce',
                                    'value' => '160_' . $productId
                                )
                            ),
                            'initialPrice' => 15,
                            'discountTotal' => 1,
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

    public static function get_history_change_to_another_individual($orderExternalId)
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

    public static function get_history_change_to_corporate($orderExternalId)
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

    public static function get_history_change_to_another_corporate($orderExternalId)
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

    public static function get_history_change_only_company($orderExternalId)
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

    public static function get_history_change_only_contact($orderExternalId)
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

    public static function get_history_change_from_corporate_to_individual($orderExternalId)
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

    public static function get_initial_regular_customer()
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

    public static function get_contact_when_only_contact_changed()
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

    public static function get_order_with_customer_and_contact(
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
                        'discountTotal' => 5,
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

    public static function get_new_individual_for_order()
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

    public static function get_new_corporate_for_order()
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

    public static function get_new_contact_for_order()
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

    public static function get_another_corporate_for_order()
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

    public static function get_another_contact_for_order()
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
