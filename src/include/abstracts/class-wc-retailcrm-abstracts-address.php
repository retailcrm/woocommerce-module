<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

abstract class WC_Retailcrm_Abstracts_Address extends WC_Retailcrm_Abstracts_Data
{
    protected $data = array(
        'index' => '',
        'city' => '',
        'region' => '',
        'text' => '',
    );

    public function reset_data()
    {
        $this->data = array(
            'index' => '',
            'city' => '',
            'region' => '',
            'text' => '',
        );
    }
}
