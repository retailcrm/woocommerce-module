<?php

namespace datasets;

/**
 * PHP version 7.0
 *
 * Class DataCartRetailCrm - Data set for WC_Cart_Customers_Test.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

class DataCartRetailCrm
{
    public static function dataGetCart() {
        return [
            'success' => true,
            'cart' => [
                'clearedAt' => new \DateTime('now'),
                'externalId' => '1',
                'updateAt' => new \DateTime('now'),
                'droppedAt' => new \DateTime('now'),
                'link' => 'https:://link/cart/152',
                'items' => [
                    0 => [
                        'quantity' => 3,
                        'price' => 1500,
                        'createdAt' => new \DateTime('now'),
                        'updatedAt' => new \DateTime('now'),
                        'offer' => [
                            'id' => 1,
                            'externalId' => '1',
                            'name' => 'test product',
                            'properties' => [
                                'prop1' => 'prop'
                            ],
                            'unit' => [
                                'code' => 'test code',
                                'name' => 'test unit name',
                                'sym' => 'sym',
                            ],
                            'barcode' => '123456789',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function dataSetCart() {
        return [
            'cart' => [
                'clearedAt' => new \DateTime('now'),
                'externalId' => '1',
                'updateAt' => new \DateTime('now'),
                'droppedAt' => new \DateTime('now'),
                'link' => 'https:://link/cart/152',
                'customer' => [
                    'id' => 1,
                    'externalId' => '1',
                    'browserId' => '145874',
                    'site' => 'test-site',
                ],
                'items' => [
                    0 => [
                        'quantity' => 3,
                        'price' => 1500,
                        'createdAt' => new \DateTime('now'),
                        'updatedAt' => new \DateTime('now'),
                        'offer' => [
                            'id' => 1,
                            'externalId' => '1',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function dataClearCart() {
        return [
            'cart' => [
                'clearedAt' => new \DateTime('now'),
                'customer' => [
                    'id' => 1,
                    'externalId' => '1',
                    'browserId' => '145874',
                ],
                'order' => [
                    'id' => '1',
                    'externalId' => '1',
                    'number' => '152C',
                ],
            ],
        ];
    }

}