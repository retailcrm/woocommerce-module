<?php

if (!class_exists('WC_Retailcrm_Response')) {
    require_once dirname(__FILE__) . '/../../src/include/api/class-wc-retailcrm-response.php';
}

class WC_Retailcrm_Response_Helper extends WC_Retailcrm_Response
{
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
