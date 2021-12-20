<?php

namespace datasets;

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