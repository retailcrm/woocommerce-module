<?php

if (!class_exists('WC_Retailcrm_Response')) {
    require_once dirname(__FILE__) . '/../../src/include/api/class-wc-retailcrm-response.php';
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Response_Helper - Set response for mock object.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Response_Helper extends WC_Retailcrm_Response
{
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
