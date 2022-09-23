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
        return  array(
            'success' => true,
            'pagination' => array(
                'limit' => 250,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ),
            'offers' => array(
                array(
                    'id' => 1,
                    'xmlId' => 'xmlId',
                    'quantity' => 10
                )
            )
        );
    }
}