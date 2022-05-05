<?php

namespace datasets;

/**
 * PHP version 5.6
 *
 * Class DataBaseRetailCrm - Data set for WC_Retailcrm_Base.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class DataBaseRetailCrm
{
    public static function getResponseStatuses()
    {
        return [
            'success' => true,
            'statuses' => [
                [
                    'name' => 'status1',
                    'code' => 'status1',
                    'active' => true
                ],
                [
                    'name' => 'status2',
                    'code' => 'status2',
                    'active' => false
                ]
            ]
        ];
    }

    public static function getResponsePaymentTypes()
    {
        return [
            'success' => true,
            'paymentTypes' => [
                [
                    'name' => 'payment1',
                    'code' => 'payment1',
                    'active' => true
                ],
                [
                    'name' => 'payment2',
                    'code' => 'payment2',
                    'active' => false
                ],
                [
                    'name' => 'payment3',
                    'code' => 'payment3',
                    'integrationModule' => [
                        'name' => 'test',
                        'active' => true,
                    ],
                    'active' => true
                ],
            ]
        ];
    }

    public static function getResponseDeliveryTypes()
    {
        return [
            'success' => true,
            'deliveryTypes' => [
                [
                    'name' => 'delivery1',
                    'code' => 'delivery1',
                    'active' => true
                ],
                [
                    'name' => 'delivery2',
                    'code' => 'delivery2',
                    'active' => false
                ]
            ]
        ];
    }

    public static function getResponseOrderMethods()
    {
        return [
            'success' => true,
            'orderMethods' => [
                [
                    'name' => 'orderMethod1',
                    'code' => 'orderMethod1',
                    'active' => true
                ],
                [
                    'name' => 'orderMethod2',
                    'code' => 'orderMethod2',
                    'active' => false
                ]
            ]
        ];
    }
}
