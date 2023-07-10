<?php

namespace datasets;

/**
 * PHP version 7.0
 *
 * Class DataInventoriesRetailCrm - Data set for WC_Retailcrm_Inventories.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class DataInventoriesRetailCrm {
    public static function getResponseData()
    {
        return  [
            'success' => true,
            'pagination' => [
                'limit' => 250,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ],
            'offers' => [
                [
                    'id' => 1,
                    'xmlId' => 'xmlId',
                    'quantity' => 100,
                    'stores' => [
                        [
                            'quantity' => 25,
                            'purchasePrice' => 0,
                            'store' => 'main'
                        ],
                        [
                            'quantity' => 25,
                            'purchasePrice' => 0,
                            'store' => 'woocommerce'
                        ],
                        [
                            'quantity' => 50,
                            'purchasePrice' => 0,
                            'store' => 'prestashop'
                        ],

                    ]
                ]
            ]
        ];
    }
}
