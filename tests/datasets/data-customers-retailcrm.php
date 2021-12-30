<?php

namespace datasets;

/**
 * PHP version 5.6
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
    public static function getCustomerAddress()
    {
        return array(
            'success' => true,
            'addresses' => array (
                'id' => 3503,
                'index' => 144566,
                'countryIso' => 'ES',
                'region' => 'Region',
                'city' => 'City',
                'text' => 'street Test 777',
            )
        );
    }
}

