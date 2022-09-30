<?php

namespace datasets;

/**
 * PHP version 7.0
 *
 * Class DataCustomersRetailCrm - Data set for WC_Retailcrm_Customers.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class DataCustomersRetailCrm
{
    public static function getCustomerAddress() {
        return [
            'success'   => true,
            'addresses' => [[
                'index'      => 123456,
                'region'     => 'test_state',
                'city'       => 'test_city',
                'text'       => 'test_address_line',
                'isMain'     => false
            ]]
        ];
    }


    public static function getEmptyCustomersList() {
        return [
            'success'    => true,
            'pagination' => [
                'limit'          => 20,
                'totalCount'     => 0,
                'currentPage'    => 1,
                'totalPageCount' => 0
            ],
            'customers'  => [],
        ];
    }

    public static function getCustomersList() {
        return [
            'success'    => true,
            'pagination' => [
                'limit'          => 20,
                'totalCount'     => 0,
                'currentPage'    => 1,
                'totalPageCount' => 0
            ],
            'customers'  => [
                [
                    'type'       => 'customer',
                    'id'         => 4228,
                    'externalId' => 2,
                    'isContact'  => false,
                    'email'      => 'madrid@mail.es',
                    'phones'     => [['number' => '+3456234235']],
                    'addresses'  => [
                        'id'         => 3503,
                        'index'      => 144566,
                        'countryIso' => 'ES',
                        'region'     => 'Region',
                        'city'       => 'City',
                        'text'       => 'street Test 777',
                    ]
                ]

            ]
        ];
    }
}

