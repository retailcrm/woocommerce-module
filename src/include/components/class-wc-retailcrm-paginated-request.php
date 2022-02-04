<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Paginated_Request - It will merge request with pagination data into one big monstrous array.
 * Use with caution, it can cause problems with very large data sets (due to memory exhaustion, obviously).
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Paginated_Request
{
    /**
     * @var \WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V5
     */
    private $api;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $data;

    /**
     * Sets retailCRM api client to request
     *
     * @param \WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V5 $api
     *
     * @return self
     */
    public function setApi($api)
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Sets API client method to request
     *
     * @param string $method
     *
     * @return self
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Sets method params for API client.
     *
     * @param array $params
     *
     * @return self
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Executes request
     *
     * @return $this
     */
    public function execute()
    {
        $response = call_user_func_array(
            [$this->api, $this->method],
            $this->params
        );

        if ($response->isSuccessful() && !empty($response['history'])) {
            $this->data = array_merge($this->data, $response['history']);
        }

        time_nanosleep(0, 300000000);

        return $this;
    }

    /**
     * Returns data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Reset paginated request
     *
     * @return $this
     */
    public function reset()
    {
        $this->data   = [];
        $this->method = '';

        return $this;
    }
}
