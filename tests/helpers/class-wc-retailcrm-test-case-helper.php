<?php

/**
 * Class WC_Retailcrm_Test_Case_Helper
 */
class WC_Retailcrm_Test_Case_Helper extends WC_Unit_Test_Case
{
    /**
     * @return array
     */
    protected function setOptions()
    {
        $options = array(
            'api_url' => 'https://example.retailcrm.ru',
            'api_key' => 'test_key',
            'corporate_enabled' => 'yes',
            'online_assistant' => 'code',
            'p_draft' => 'no',
            'p_pending' => 'no',
            'p_private' => 'no',
            'p_publish' => 'no',
            'order_methods' => array(0 => 'phone'),
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
            'sync' => 'yes',
            'ua' => 'yes',
            'ua_code' => 'UA-XXXXXXX-XX',
            'ua_custom' => '1',
            'daemon_collector' => 'yes',
            'upload-button' => '',
            'whatsapp_active' => 'yes',
            'whatsapp_location_icon' => 'yes',
            'whatsapp_number' => '+79184567234',
            'icml' => 'yes',
            'single_order' => '123',
            'history' => 'yes',
            'deactivate_update_order' => 'no',
            'bind_by_sku' => 'no',
            'update_number' => 'yes',
            'debug_mode' => 'yes',
            'debug-info' => ''
        );

        update_option(WC_Retailcrm_Base::$option_key, $options);

        return $options;
    }

    /**
     * Removes all data from the DB.
     */
    protected function deleteAllData()
    {
        if (function_exists('_delete_all_data')) {
            _delete_all_data();
        } else {
            global $wpdb;

            foreach ( array(
                          $wpdb->posts,
                          $wpdb->postmeta,
                          $wpdb->comments,
                          $wpdb->commentmeta,
                          $wpdb->term_relationships,
                          $wpdb->termmeta,
                      ) as $table ) {
                //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "DELETE FROM {$table}" );
            }

            foreach ( array(
                          $wpdb->terms,
                          $wpdb->term_taxonomy,
                      ) as $table ) {
                //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "DELETE FROM {$table} WHERE term_id != 1" );
            }

            $wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET count = 0" );

            $wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID != 1" );
            $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id != 1" );
        }
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return get_option(WC_Retailcrm_Base::$option_key);
    }


    /**
     * @param $mock
     * @param $method
     * @param $response
     *
     * @return void
     */
    protected function setMockResponse($mock, $method, $response)
    {
        $mock->expects($this->any())
             ->method($method)
             ->willReturn($response);
    }
}
