<?php

namespace datasets;

/**
 * PHP version 7.0
 *
 * Class DataUploadPriceRetailCrm - Data set for WC_Retailcrm_Upload_Discount_Price_Test.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class DataUploadPriceRetailCrm
{
    public static function dataGetPriceTypes() {
        return [
            'success' => true,
            'priceTypes' => [
                [
                    'code' => 'test',
                    'name' => 'test',
                    'active' => true,
                    'description' => 'test',
                    'ordering' => 999,
                    'promo' => true,
                    'default' => false
                ],
                [
                    'code' => 'default',
                    'name' => 'default',
                    'active' => true,
                    'description' => 'default',
                    'ordering' => 999,
                    'promo' => true,
                    'default' => true
                ],
            ]
        ];
    }

    public static function willSendPriceType() {
        return [
            'code' => 'woo-promotion-lp',
            'name' => 'Woocommerce promotional price',
            'active' => true,
            'description' => 'Promotional price type for Woocommerce store, generated automatically.
                     Necessary for correct synchronization work when loyalty program is enabled
                      (Do not delete. Do not deactivate)',
            'ordering' => 999,
            'promo' => true,
        ];
    }
}
