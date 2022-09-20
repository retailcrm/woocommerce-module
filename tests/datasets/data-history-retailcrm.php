<?php

namespace datasets;

/**
 * PHP version 5.6
 *
 * Class DataHistoryRetailCrm - Data set for WC_Retailcrm_History.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class DataHistoryRetailCrm
{
    public static function empty_history()
    {
        return [
            'success' => true,
            'history' => [],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 0,
                'currentPage' => 1,
                'totalPageCount' => 0
            ]
        ];
    }

    public static function get_history_data_new_customer()
    {
        return [
            'success' => true,
            'history'  => [
                [
                    'id' => 18009,
                    'createdAt' => '2021-12-03 13:22:45',
                    'created' => true,
                    'source' => 'user',
                    'user' => [ 'id' => 11 ],
                    'field' => 'id',
                    'oldValue' => null,
                    'newValue' => 3758,
                    'customer' => [
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
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => [
                            'id' => 3503,
                            'index' => 123456,
                            'countryIso' => 'ES',
                            'region' => 'Region',
                            'city' => 'City',
                            'text' => 'Street',
                        ],
                        'customFields' => ['crm_customer' => 'test_customer'],
                        'segments' => [],
                        'firstName' => 'Test_Name',
                        'lastName' => 'Test_LastName',
                        'email' => 'mail_test@mail.es',
                        'phones' => [ '0' => [ 'number' => '+79184563200' ] ],
                        'birthday' => '2021-10-01'
                    ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ]
        ];
    }

    public static function get_history_data_new_order($product_create_id)
    {
        return [
            'success' => true,
            'history'  => [
                [
                    'id' => 1,
                    'createdAt' => '2018-01-01 00:00:00',
                    'created' => true,
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'status',
                    'oldValue' => null,
                    'newValue' => [
                        'code' => 'status1'
                    ],
                    'order' => [
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
                        'firstName' => 'Test_Name',
                        'lastName' => 'Test_LastName',
                        'phone' => '80000000000',
                        'call' => false,
                        'expired' => false,
                        'customFields' => ['crm_order' => 'test_order'],
                        'customer' => [
                            'type' => 'customer',
                            'segments' => [],
                            'id' => 1,
                            'firstName' => 'Test_Name',
                            'lastName' => 'Test_LastName',
                            'email' => 'email@test.ru',
                            'phones' => [
                                [
                                    'number' => '111111111111111'
                                ],
                                [
                                    'number' => '+7111111111'
                                ]
                            ],
                            'address' => [
                                'index' => 123456,
                                'countryIso' => 'ES',
                                'region' => 'Region',
                                'city' => 'City',
                                'text' => 'Street'
                            ],
                            'createdAt' => '2018-01-01 00:00:00',
                            'managerId' => 1,
                            'vip' => false,
                            'bad' => false,
                            'site' => 'test-com',
                            'contragent' => [
                                'contragentType' => 'individual'
                            ],
                            'personalDiscount' => 0,
                            'cumulativeDiscount' => 0,
                            'marginSumm' => 58654,
                            'totalSumm' => 61549,
                            'averageSumm' => 15387.25,
                            'ordersCount' => 4,
                            'costSumm' => 101,
                        ],
                        'contragent' => [],
                        'delivery' => [
                            'cost' => 0,
                            'netCost' => 0,
                            'address' => [
                                'index' => 123456,
                                'countryIso' => 'ES',
                                'region' => 'Region',
                                'city' => 'City',
                                'text' => 'Street1 || Street2'
                            ]
                        ],
                        'site' => 'test-com',
                        'status' => 'status4',
                        'items' => [
                            [
                                'id' => 160,
                                'initialPrice' => 100,
                                'discountTotal' => 5,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'externalIds' => [
                                    [
                                        'code' => 'woocomerce',
                                        'value' => '160_' . $product_create_id
                                    ]
                                ],
                                'initialPrice' => 15,
                                'discountTotal' => 1,
                                'offer' => [
                                    'id' => 1,
                                    'externalId' => $product_create_id,
                                    'xmlId' => '1',
                                    'name' => 'Test name',
                                    'vatRate' => 'none'
                                ],
                                'properties' => [],
                                'purchasePrice' => 50
                            ]
                        ],
                        'paymentType' => 'payment4',
                        'payments' => [
                            [
                                'id' => 1,
                                'type' => 'payment4',
                                'amount' => 100,
                            ]
                        ],
                        'fromApi' => false,
                        'length' => 0,
                        'width' => 0,
                        'height' => 0,
                        'shipmentStore' => 'main',
                        'shipped' => false,
                        'uploadedToExternalStoreSystem' => false
                    ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ]
        ];
    }

    public static function get_history_data_new_order_deleted_items($product_create_id, $product_delete_id)
    {
        return [
            'success' => true,
            'history' => [
                [
                    'id' => 1,
                    'createdAt' => '2018-01-01 00:00:00',
                    'created' => true,
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'status',
                    'oldValue' => null,
                    'newValue' => [
                        'code' => 'status1'
                    ],
                    'order' => [
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
                        'firstName' => 'Test_Name',
                        'lastName' => 'Test_LastName',
                        'phone' => '80000000000',
                        'call' => false,
                        'expired' => false,
                        'customer' => [
                            'type' => 'customer',
                            'segments' => [],
                            'id' => 1,
                            'firstName' => 'Test_Name',
                            'lastName' => 'Test_LastName',
                            'email' => 'email@test.ru',
                            'phones' => [
                                [
                                    'number' => '111111111111111'
                                ],
                                [
                                    'number' => '+7111111111'
                                ]
                            ],
                            'address' => [
                                'index' => 123456,
                                'countryIso' => 'ES',
                                'region' => 'Region',
                                'city' => 'City',
                                'text' => 'Street'
                            ],
                            'createdAt' => '2018-01-01 00:00:00',
                            'managerId' => 1,
                            'vip' => false,
                            'bad' => false,
                            'site' => 'test-com',
                            'contragent' => [
                                'contragentType' => 'individual'
                            ],
                            'personalDiscount' => 0,
                            'cumulativeDiscount' => 0,
                            'marginSumm' => 58654,
                            'totalSumm' => 61549,
                            'averageSumm' => 15387.25,
                            'ordersCount' => 4,
                            'costSumm' => 101,
                        ],
                        'contragent' => [],
                        'delivery' => [
                            'cost' => 0,
                            'netCost' => 0,
                            'address' => [
                                'index' => 123456,
                                'countryIso' => 'ES',
                                'region' => 'Region',
                                'city' => 'City',
                                'text' => 'Street'
                            ]
                        ],
                        'site' => 'test-com',
                        'status' => 'status1',
                        'items' => [
                            [
                                'id' => 160,
                                'initialPrice' => 15,
                                'discountTotal' => 1,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'externalIds' => [
                                    [
                                        'code' => 'woocomerce',
                                        'value' => '160_' . $product_create_id
                                    ]
                                ],
                                'offer' => [
                                    'id' => 1,
                                    'externalId' => $product_create_id,
                                    'xmlId' => '1',
                                    'name' => 'Test name',
                                    'vatRate' => 'none'
                                ],
                                'properties' => [],
                                'purchasePrice' => 10
                            ],
                            [
                                'id' => 161,
                                'initialPrice' => 100,
                                'discountTotal' => 5,
                                'createdAt' => '2018-01-01 00:00:00',
                                'quantity' => 1,
                                'status' => 'new',
                                'externalIds' => [
                                    [
                                        'code' => 'woocomerce',
                                        'value' => '161_' . $product_delete_id
                                    ]
                                ],
                                'offer' => [
                                    'id' => 2,
                                    'externalId' => $product_delete_id,
                                    'xmlId' => '2',
                                    'name' => 'Test name 2',
                                    'vatRate' => 'none'
                                ],
                                'properties' => [],
                                'purchasePrice' => 50
                            ]
                        ],
                        'paymentType' => 'payment4',
                        'payments' => [
                            [
                                'id' => 1,
                                'type' => 'payment4',
                                'amount' => 100,
                            ]
                        ],
                        'fromApi' => false,
                        'length' => 0,
                        'width' => 0,
                        'height' => 0,
                        'shipmentStore' => 'main',
                        'shipped' => false,
                        'uploadedToExternalStoreSystem' => false
                    ]
                ],
                [
                    'id' => 2,
                    'createdAt' => '2018-01-01 00:01:00',
                    'source' => 'api',
                    'field' => 'order_product',
                    'oldValue' => [
                        'id' => 161,
                        'offer' => [
                            'id' => 2,
                            'externalId' => $product_delete_id
                        ]
                    ],
                    'newValue' => null,
                    'order' => [
                        'id' => 3,
                        'site' => 'test-com',
                        'status' => 'status1'
                    ],
                    'item' => [
                        'id' => 161,
                        'initialPrice' => 100,
                        'discountTotal' => 5,
                        'createdAt' => '2018-01-01 00:00:00',
                        'quantity' => 1,
                        'status' => 'new',
                        'externalIds' => [
                            [
                                'code' => 'woocomerce',
                                'value' => '161_' . $product_delete_id
                            ]
                        ],
                        'offer' => [
                            'id' => 2,
                            'externalId' => $product_delete_id,
                            'xmlId' => '2',
                            'name' => 'Test name 2',
                            'vatRate' => 'none'
                        ],
                        'properties' => [],
                        'purchasePrice' => 50
                    ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 2,
                'currentPage' => 1,
                'totalPageCount' => 1
            ]
        ];
    }

    public static function get_history_data_product_add($product_add_id, $order_id)
    {
        return [
            'success' => true,
            'history'  => [
                [
                    'id' => 2,
                    'createdAt' => '2018-01-01 00:00:01',
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'order_product',
                    'oldValue' => null,
                    'newValue' => [
                        'id' => 2,
                        'offer' => [
                            'id' => 2,
                            'externalId' => $product_add_id,
                        ]
                    ],
                    'order' => [
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                    ],
                    'item' => [
                        'id' => 2,
                        'initialPrice' => 999,
                        'createdAt' => '2018-01-01 00:02:00',
                        'quantity' => 2,
                        'status' => 'status1',
                        'externalIds' => [
                            [
                                'code' => 'woocomerce',
                                'value' => '160_' . $product_add_id
                            ]
                        ],
                        'offer' => [
                            'id' => 2,
                            'externalId' => $product_add_id,
                            'name' => 'Test name 2'
                        ],
                        'properties' => [],
                        'purchasePrice' => 500
                    ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ]
        ];
    }

    public static function get_history_data_update($order_id)
    {
        $history =  [
            'success' => true,
            'history'  => [
                [
                    'id' => 3,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'status',
                    'oldValue' => [
                        'code' => 'new'
                    ],
                    'newValue' => [
                        'code' => 'status4'
                    ],
                    'order' => [
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4'
                    ]
                ],
                [
                    'id' => 4,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'manager_comment',
                    'oldValue' => '',
                    'newValue' =>  'managerCommentTest'
                    ,
                    'order' => [
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerCommentTest'
                    ]
                ],
                [
                    'id' => 5,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'customer_comment',
                    'oldValue' => '',
                    'newValue' => 'customerCommentTest',
                    'order' => [
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerCommentTest',
                        'customerComment' => 'customerCommentTest'
                    ]
                ],
                [
                    'id' => 6,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'phone',
                    'oldValue' => [
                        'code' => ''
                    ],
                    'newValue' => [
                        'code' => '12345678'
                    ],
                    'order' => [
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerCommentTest',
                        'customerComment' => 'customerCommentTest',
                        'phone' => '12345678'
                    ]
                ],
                [
                    'id' => 7,
                    'createdAt' => '2018-01-01 00:03:00',
                    'source' => 'user',
                    'user' => [
                        'id' => 1
                    ],
                    'field' => 'email',
                    'oldValue' => [
                        'code' => ''
                    ],
                    'newValue' => [
                        'code' => 'tester001@example.com'
                    ],
                    'order' => [
                        'id' => 2,
                        'externalId' => $order_id,
                        'managerId' => 6,
                        'site' => 'test-com',
                        'status' => 'status4',
                        'managerComment' => 'managerCommentTest',
                        'customerComment' => 'customerCommentTest',
                        'phone' => '12345678',
                        'email' => 'tester001@example.com'
                    ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ]
        ];

        $payment_v5 = [
            'id' => 4,
            'createdAt' => '2018-01-01 00:03:00',
            'source' => 'user',
            'user' => [
                'id' => 1
            ],
            'field' => 'payments',
            'oldValue' => null,
            'newValue' => [
                'code' => 'payment2'
            ],
            'order' => [
                'id' => 2,
                'externalId' => $order_id,
                'managerId' => 6,
                'site' => 'test-com',
                'status' => 'status4'
            ],
            'payment' => [
                'id' => 1,
                'type' => 'payment2',
                'amount' => 100
            ]
        ];

        array_push($history['history'], $payment_v5);

        return $history;
    }

    public static function get_history_order_for_client_replace($productId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-04 15:05:39',
            'history' => [
                [
                'id' => 25011,
                'createdAt' => '2020-06-04 15:05:10',
                'created' => true,
                'source' => 'user',
                'field' => 'status',
                'oldValue' => null,
                'newValue' => [ 'code' => 'new' ],
                'order' => [
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
                    'customer' => [
                        'type' => 'customer',
                        'id' => 4228,
                        'externalId' => '2',
                        'isContact' => false,
                        'createdAt' => '2020-06-01 15:31:46',
                        'managerId' => 27,
                        'vip' => false,
                        'bad' => false,
                        'site' => 'bitrix-test',
                        'contragent' => [
                            'contragentType' => 'individual',
                        ],
                        'tags' => [],
                        'marginSumm' => 9412,
                        'totalSumm' => 9412,
                        'averageSumm' => 9412,
                        'ordersCount' => 1,
                        'costSumm' => 0,
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => [
                            'id' => 3132,
                            'text' => 'ул. Пушкина дом Колотушкина',
                        ],
                        'segments' => [],
                        'firstName' => 'tester001',
                        'lastName' => 'tester001',
                        'email' => 'tester001@example.com',
                        'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
                        'phones' => [ [ 'number' => '2354708915097' ] ]
                    ],
                    'contact' => [
                        'type' => 'customer',
                        'id' => 4228,
                        'externalId' => '2',
                        'isContact' => false,
                        'createdAt' => '2020-06-01 15:31:46',
                        'managerId' => 27,
                        'vip' => false,
                        'bad' => false,
                        'site' => 'bitrix-test',
                        'contragent' => [
                            'contragentType' => 'individual',
                        ],
                        'tags' => [],
                        'marginSumm' => 9412,
                        'totalSumm' => 9412,
                        'averageSumm' => 9412,
                        'ordersCount' => 1,
                        'costSumm' => 0,
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => [
                            'id' => 3132,
                            'text' => 'ул. Пушкина дом Колотушкина',
                        ],
                        'segments' => [],
                        'firstName' => 'tester001',
                        'lastName' => 'tester001',
                        'email' => 'tester001@example.com',
                        'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
                        'phones' => [ [ 'number' => '2354708915097' ] ]
                    ],
                    'contragent' => [
                        'contragentType' => 'individual',
                    ],
                    'delivery' => [
                        'cost' => 0,
                        'netCost' => 0,
                        'address' => [
                            'id' => 5864,
                            'countryIso' => 'RU',
                            'text' => 'ул. Пушкина дом Колотушкина',
                        ],
                    ],
                    'site' => 'woocommerce',
                    'status' => 'new',
                    'items' => [
                        [
                            'id' => 160,
                            'initialPrice' => 100,
                            'discountTotal' => 5,
                            'createdAt' => '2018-01-01 00:00:00',
                            'quantity' => 1,
                            'status' => 'new',
                            'externalIds' => [
                                [
                                    'code' => 'woocomerce',
                                    'value' => '160_' . $productId
                                ]
                            ],
                            'initialPrice' => 15,
                            'discountTotal' => 1,
                            'offer' => [
                                'id' => 1,
                                'externalId' => $productId,
                                'xmlId' => '1',
                                'name' => 'Test name',
                                'vatRate' => 'none'
                            ],
                            'properties' => [],
                            'purchasePrice' => 50
                        ]
                    ],
                    'fromApi' => false,
                    'length' => 0,
                    'width' => 0,
                    'height' => 0,
                    'shipmentStore' => 'main',
                    'shipped' => false,
                    'customFields' => []
                ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ]
        ];
    }

    public static function get_history_change_to_another_individual($orderExternalId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-05 12:29:14',
            'history' => [
                [
                    'id' => 25398,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contact',
                    'oldValue' => [
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ],
                    'newValue' => [
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25399,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'customer',
                    'oldValue' => [
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ],
                    'newValue' => [
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25400,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'email',
                    'oldValue' => 'tester001@example.com',
                    'newValue' => 'ewtrhibehb126879@example.com',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25401,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'first_name',
                    'oldValue' => 'tester001',
                    'newValue' => 'tester002',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25402,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'last_name',
                    'oldValue' => 'tester001',
                    'newValue' => 'tester002',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25403,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'patronymic',
                    'oldValue' => null,
                    'newValue' => 'tester002',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25404,
                    'createdAt' => '2020-06-05 12:29:08',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'phone',
                    'oldValue' => '2354708915097',
                    'newValue' => '34687453268933',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 7,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ],
        ];
    }

    public static function get_history_change_to_corporate($orderExternalId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-05 15:24:19',
            'history' => [
                [
                    'id' => 25744,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'delivery_address.city',
                    'oldValue' => 'с. Верхненазаровское',
                    'newValue' => 'Валдгейм',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25745,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'delivery_address.index',
                    'oldValue' => '34000',
                    'newValue' => '344091',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25746,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'delivery_address.region',
                    'oldValue' => 'Адыгея Республика',
                    'newValue' => 'Еврейская Автономная область',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25747,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contragent.contragent_type',
                    'oldValue' => 'individual',
                    'newValue' => 'legal-entity',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25748,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contragent.legal_address',
                    'oldValue' => null,
                    'newValue' => '344090 * Москва упцупуцйпуц йцавафыафыафыафы',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25749,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'company',
                    'oldValue' => null,
                    'newValue' => [
                        'id' => 591,
                        'name' => 'Компания1',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25750,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contact',
                    'oldValue' => [
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ],
                    'newValue' => [
                        'id' => 4219,
                        'externalId' => '4',
                        'site' => 'woocommerce',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25751,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'customer',
                    'oldValue' => [
                        'id' => 4231,
                        'site' => 'bitrix-test',
                    ],
                    'newValue' => [
                        'id' => 4220,
                        'site' => 'woocommerce',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25752,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'email',
                    'oldValue' => 'ewtrhibehb126879@example.com',
                    'newValue' => 'psycho913@example.com',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25753,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'first_name',
                    'oldValue' => 'tester002',
                    'newValue' => 'psycho913',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25754,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'last_name',
                    'oldValue' => 'tester002',
                    'newValue' => 'psycho913',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25755,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'patronymic',
                    'oldValue' => 'tester002',
                    'newValue' => null,
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25756,
                    'createdAt' => '2020-06-05 15:24:12',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'phone',
                    'oldValue' => '34687453268933',
                    'newValue' => '9135487458709',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 13,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ],
        ];
    }

    public static function get_history_change_to_another_corporate($orderExternalId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-05 16:37:53',
            'history' => [
                [
                    'id' => 25979,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contragent.legal_address',
                    'oldValue' => '344090 * Москва упцупуцйпуц йцавафыафыафыафы',
                    'newValue' => null,
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25980,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'company',
                    'oldValue' => [
                        'id' => 591,
                        'name' => 'Компания1',
                    ],
                    'newValue' => [
                        'id' => 621,
                        'name' => 'TestCompany3428769',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25981,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contact',
                    'oldValue' => [
                        'id' => 4219,
                        'externalId' => '4',
                        'site' => 'woocommerce',
                    ],
                    'newValue' => [
                        'id' => 4304,
                        'site' => 'woocommerce',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25982,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'customer',
                    'oldValue' => [
                        'id' => 4220,
                        'site' => 'woocommerce',
                    ],
                    'newValue' => [
                        'id' => 4303,
                        'site' => 'woocommerce',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25983,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'email',
                    'oldValue' => 'psycho913@example.com',
                    'newValue' => 'tester4867@example.com',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25984,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'first_name',
                    'oldValue' => 'psycho913',
                    'newValue' => 'Tester4867',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25985,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'last_name',
                    'oldValue' => 'psycho913',
                    'newValue' => 'Tester4867',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25986,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'patronymic',
                    'oldValue' => null,
                    'newValue' => 'Tester4867',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25987,
                    'createdAt' => '2020-06-05 16:37:46',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'phone',
                    'oldValue' => '9135487458709',
                    'newValue' => null,
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 9,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ],
        ];
    }

    public static function get_history_change_only_company($orderExternalId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-05 17:13:23',
            'history' => [
                [
                    'id' => 25988,
                    'createdAt' => '2020-06-05 17:13:17',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'company',
                    'oldValue' => [
                        'id' => 621,
                        'name' => 'TestCompany3428769',
                    ],
                    'newValue' => [
                        'id' => 622,
                        'name' => 'TestCompany017089465',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ],
        ];
    }

    public static function get_history_change_only_contact($orderExternalId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-05 17:36:28',
            'history' => [
                [
                    'id' => 25989,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contact',
                    'oldValue' => [
                        'id' => 4304,
                        'site' => 'woocommerce',
                    ],
                    'newValue' => [
                        'id' => 4305,
                        'site' => 'woocommerce',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25990,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'email',
                    'oldValue' => 'tester4867@example.com',
                    'newValue' => 'tester2890@example.com',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25991,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'first_name',
                    'oldValue' => 'Tester4867',
                    'newValue' => 'Tester2890',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25992,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'last_name',
                    'oldValue' => 'Tester4867',
                    'newValue' => 'Tester2890',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25993,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'patronymic',
                    'oldValue' => 'Tester4867',
                    'newValue' => 'Tester2890',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25994,
                    'createdAt' => '2020-06-05 17:36:20',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'phone',
                    'oldValue' => null,
                    'newValue' => '32418790888',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 6,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ],
        ];
    }

    public static function get_history_change_from_corporate_to_individual($orderExternalId)
    {
        return [
            'success' => true,
            'generatedAt' => '2020-06-05 17:47:05',
            'history' => [
                [
                    'id' => 25995,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contragent.contragent_type',
                    'oldValue' => 'legal-entity',
                    'newValue' => 'individual',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25996,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'contact',
                    'oldValue' => [
                        'id' => 4305,
                        'site' => 'woocommerce',
                    ],
                    'newValue' => [
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25997,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'customer',
                    'oldValue' => [
                        'id' => 4303,
                        'site' => 'woocommerce',
                    ],
                    'newValue' => [
                        'id' => 4228,
                        'externalId' => '2',
                        'site' => 'bitrix-test',
                    ],
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25998,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'email',
                    'oldValue' => 'tester2890@example.com',
                    'newValue' => 'tester001@example.com',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 25999,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'first_name',
                    'oldValue' => 'Tester2890',
                    'newValue' => 'tester001',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 26000,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'last_name',
                    'oldValue' => 'Tester2890',
                    'newValue' => 'tester001',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 26001,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'patronymic',
                    'oldValue' => 'Tester2890',
                    'newValue' => null,
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
                [
                    'id' => 26002,
                    'createdAt' => '2020-06-05 17:46:58',
                    'source' => 'user',
                    'user' => [
                        'id' => 27,
                    ],
                    'field' => 'phone',
                    'oldValue' => '32418790888',
                    'newValue' => '2354708915097',
                    'order' => [
                        'id' => 5868,
                        'externalId' => $orderExternalId,
                        'managerId' => 27,
                        'site' => 'woocommerce',
                        'status' => 'new',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 8,
                'currentPage' => 1,
                'totalPageCount' => 1,
            ],
        ];
    }

    public static function get_initial_regular_customer()
    {
        return [
            'type' => 'customer',
            'id' => 4228,
            'externalId' => '2',
            'isContact' => false,
            'createdAt' => '2020-06-01 15:31:46',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'bitrix-test',
            'contragent' => [
                'contragentType' => 'individual',
            ],
            'tags' => [],
            'marginSumm' => 9428,
            'totalSumm' => 9428,
            'averageSumm' => 4714,
            'ordersCount' => 2,
            'costSumm' => 0,
            'personalDiscount' => 0,
            'address' => [
                'id' => 3132,
                'text' => 'ул. Пушкина дом Колотушкина',
            ],
            'firstName' => 'tester001',
            'lastName' => 'tester001',
            'email' => 'tester001@example.com',
            'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
            'phones' => [
                [
                    'number' => '2354708915097',
                ],
            ],
        ];
    }

    public static function get_contact_when_only_contact_changed()
    {
        return [
            'type' => 'customer',
            'id' => 4305,
            'isContact' => true,
            'createdAt' => '2020-06-05 17:11:53',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => [],
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'costSumm' => 0,
            'customFields' => [
                'galkatrue' => true,
            ],
            'personalDiscount' => 0,
            'segments' => [],
            'firstName' => 'Tester2890',
            'lastName' => 'Tester2890',
            'patronymic' => 'Tester2890',
            'email' => 'tester2890@example.com',
            'phones' => [
                [
                    'number' => '32418790888',
                ],
            ],
        ];
    }

    public static function get_order_with_customer_and_contact(
        $customer,
        $contact = [],
        $company = [],
        $contragentType = 'individual'
    ) {
        $order = [
            'success' => true,
            'order' => [
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
                'contragent' => [
                    'contragentType' => $contragentType,
                ],
                'delivery' => [
                    'cost' => 0,
                    'netCost' => 0,
                    'address' => [
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
                    ],
                ],
                'site' => 'woocommerce',
                'status' => 'new',
                'items' => [
                    [
                        'markingCodes' => [],
                        'id' => 8955,
                        'externalIds' => [
                            [
                                'code' => 'woocomerce',
                                'value' => '23_31',
                            ],
                        ],
                        'priceType' => [
                            'code' => 'base',
                        ],
                        'initialPrice' => 16,
                        'discountTotal' => 5,
                        'vatRate' => 'none',
                        'createdAt' => '2020-06-04 14:54:54',
                        'quantity' => 1,
                        'status' => 'new',
                        'offer' => [
                            'displayName' => 'Cap',
                            'id' => 67424,
                            'externalId' => '23',
                            'name' => 'Cap',
                            'vatRate' => 'none',
                            'unit' => [
                                'code' => 'pc',
                                'name' => 'Штука',
                                'sym' => 'шт.',
                            ],
                        ],
                        'properties' => [],
                        'purchasePrice' => 0,
                    ],
                ],
                'payments' => [],
                'fromApi' => false,
                'length' => 0,
                'width' => 0,
                'height' => 0,
                'shipmentStore' => 'main',
                'shipped' => false,
                'customFields' => [
                    'galka' => false,
                    'test_number' => 0,
                    'otpravit_dozakaz' => false,
                ],
            ],
        ];

        if (!empty($company)) {
            $order['order']['company'] = $company;
        }

        return $order;
    }

    public static function get_new_individual_for_order()
    {
        return [
            'type' => 'customer',
            'id' => 4231,
            'isContact' => false,
            'createdAt' => '2020-06-01 15:50:33',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'bitrix-test',
            'contragent' => [
                'contragentType' => 'individual',
            ],
            'tags' => [],
            'marginSumm' => 2144,
            'totalSumm' => 2144,
            'averageSumm' => 1072,
            'ordersCount' => 2,
            'costSumm' => 0,
            'customFields' => [
                'galkatrue' => true,
            ],
            'personalDiscount' => 0,
            'address' => [
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
            ],
            'firstName' => 'tester002',
            'lastName' => 'tester002',
            'patronymic' => 'tester002',
            'email' => 'ewtrhibehb126879@example.com',
            'phones' => [
                [
                    'number' => '34687453268933',
                ],
            ],
        ];
    }

    public static function get_new_corporate_for_order()
    {
        return [
            'type' => 'customer_corporate',
            'id' => 4220,
            'nickName' => 'Компания1',
            'mainAddress' => [
                'id' => 3131,
                'name' => 'Компания2',
            ],
            'createdAt' => '2020-05-27 15:20:33',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => [],
            'marginSumm' => 604,
            'totalSumm' => 604,
            'averageSumm' => 604,
            'ordersCount' => 1,
            'costSumm' => 0,
            'customFields' => [
                'galkatrue' => true,
            ],
            'personalDiscount' => 0,
            'mainCustomerContact' => [
                'id' => 711,
                'customer' => [
                    'id' => 4219,
                    'externalId' => '4',
                ],
                'companies' => [],
            ],
            'mainCompany' => [
                'id' => 591,
                'name' => 'Компания1',
            ],
        ];
    }

    public static function get_new_contact_for_order()
    {
        return [
            'type' => 'customer',
            'id' => 4219,
            'externalId' => '4',
            'isContact' => false,
            'createdAt' => '2020-05-27 12:09:00',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'contragent' => [
                'contragentType' => 'individual',
            ],
            'tags' => [],
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'costSumm' => 0,
            'customFields' => [
                'galkatrue' => true,
            ],
            'personalDiscount' => 0,
            'address' => [
                'id' => 3130,
                'index' => '344091',
                'countryIso' => 'RU',
                'region' => 'Еврейская Автономная область',
                'regionId' => 47,
                'city' => 'Валдгейм',
                'text' => 'упцупуцйпуц, йцавафыафыафыафы',
            ],
            'firstName' => 'psycho913',
            'lastName' => 'psycho913',
            'email' => 'psycho913@example.com',
            'phones' => [
                [
                    'number' => '9135487458709',
                ],
            ],
        ];
    }

    public static function get_another_corporate_for_order()
    {
        return [
            'type' => 'customer_corporate',
            'id' => 4303,
            'nickName' => 'Another Test Legal Entity',
            'mainAddress' => [
                'id' => 3177,
                'name' => 'Test Address',
            ],
            'createdAt' => '2020-06-05 16:34:05',
            'managerId' => 27,
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => [],
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'customFields' => [
                'galkatrue' => true,
            ],
            'personalDiscount' => 0,
            'mainCustomerContact' => [
                'id' => 748,
                'customer' => [
                    'id' => 4304,
                ],
                'companies' => [
                    [
                        'id' => 110,
                        'company' => [
                            'id' => 621,
                            'name' => 'TestCompany3428769',
                        ],
                    ],
                ],
            ],
            'mainCompany' => [
                'id' => 621,
                'name' => 'TestCompany3428769',
            ],
        ];
    }

    public static function get_another_contact_for_order()
    {
        return [
            'type' => 'customer',
            'id' => 4304,
            'isContact' => true,
            'createdAt' => '2020-06-05 16:34:27',
            'vip' => false,
            'bad' => false,
            'site' => 'woocommerce',
            'tags' => [],
            'marginSumm' => 0,
            'totalSumm' => 0,
            'averageSumm' => 0,
            'ordersCount' => 0,
            'personalDiscount' => 0,
            'segments' => [],
            'firstName' => 'Tester4867',
            'lastName' => 'Tester4867',
            'patronymic' => 'Tester4867',
            'sex' => 'male',
            'email' => 'tester4867@example.com',
            'phones' => [],
        ];
    }
}
