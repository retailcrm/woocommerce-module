<?php

/**
 * Class WC_Retailcrm_Test_Case_Helper
 */
class WC_Retailcrm_Test_Case_Helper extends WC_Unit_Test_Case
{
    /**
     * @param string $apiVersion
     *
     * @return array
     */
    protected function setOptions($apiVersion = 'v5')
    {
        $options = array(
            'api_url' => 'https://example.retailcrm.ru',
            'api_key' => 'dhsHJGYdjkHHJKJSGjhasjhgajsgJGHsg',
            'api_version' => $apiVersion,
            'p_draft' => 'no',
            'p_pending' => 'no',
            'p_private' => 'no',
            'p_publish' => 'no',
            'send_payment_amount' => 'yes',
            'order_methods' => '',
            'flat_rate_shipping' => 'delivery',
            'free_shipping' => 'delivery2',
            'local_pickup' => 'delivery3',
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
            'ua' => 'yes',
            'ua_code' => 'UA-XXXXXXX-XX',
            'ua_custom' => '1',
            'upload-button' => ''
        );

        update_option(WC_Retailcrm_Base::$option_key, $options);

        return $options;
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return get_option(WC_Retailcrm_Base::$option_key);
    }
}
