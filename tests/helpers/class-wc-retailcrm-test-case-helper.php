<?php

class WC_Retailcrm_Test_Case_Helper extends WC_Unit_Test_Case
{
    protected function setOptions($apiVesrion)
    {
        $options = array(
            'api_url' => 'https://example.retailcrm.ru',
            'api_key' => 'dhsHJGYdjkHHJKJSGjhasjhgajsgJGHsg',
            'api_version' => $apiVesrion,
            'p_draft' => 'no',
            'p_pending' => 'no',
            'p_private' => 'no',
            'p_publish' => 'no',
            'order_methods' => '',
            'flat_rate' => 'delivery',
            'flat_rate:1' => 'delivery1',
            'free_shipping:7' => 'delivery2',
            'flat_rate:8' => 'delivery3',
            'local_pickup:9' => 'delivery4',
            'bacs' => 'payment1',
            'cheque' => 'payment2',
            'cod' => 'payment3',
            'paypal' => 'payment4',
            'ppec_paypal' => 'payment5',
            'pending' => 'status1',
            'processing' => 'status2',
            'on-hold' => 'status3',
            'completed' => 'status4',
            'cancelled' => 'status5',
            'refunded' => 'status6',
            'failed' => 'status7',
            'sync' => 'no',
            'ua' => 'no',
            'ua_code' => '',
            'ua_custom' => '',
            'upload-button' => ''
        );

        update_option(WC_Retailcrm_Base::$option_key, $options);

        return $options;
    }
}