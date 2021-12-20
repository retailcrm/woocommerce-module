<?php

namespace datasets;

class DataBaseRetailCrm
{
    public static function getResponseStatuses()
    {
        return array(
            'success' => true,
            'statuses' => array(
                array(
                    'name' => 'status1',
                    'code' => 'status1',
                    'active' => true
                ),
                array(
                    'name' => 'status2',
                    'code' => 'status2',
                    'active' => false
                )
            )
        );
    }

    public static function getResponsePaymentTypes()
    {
        return array(
            'success' => true,
            'paymentTypes' => array(
                array(
                    'name' => 'payment1',
                    'code' => 'payment1',
                    'active' => true
                ),
                array(
                    'name' => 'payment2',
                    'code' => 'payment2',
                    'active' => false
                )
            )
        );
    }

    public static function getResponseDeliveryTypes()
    {
        return array(
            'success' => true,
            'deliveryTypes' => array(
                array(
                    'name' => 'delivery1',
                    'code' => 'delivery1',
                    'active' => true
                ),
                array(
                    'name' => 'delivery2',
                    'code' => 'delivery2',
                    'active' => false
                )
            )
        );
    }

    public static function getResponseOrderMethods()
    {
        return array(
            'success' => true,
            'orderMethods' => array(
                array(
                    'name' => 'orderMethod1',
                    'code' => 'orderMethod1',
                    'active' => true
                ),
                array(
                    'name' => 'orderMethod2',
                    'code' => 'orderMethod2',
                    'active' => false
                )
            )
        );
    }
}