<?php

/**
 * Class WC_Retailcrm_Paginated_Request
 * It will merge request with pagination data into one big monstrous array.
 * Use with caution, it can cause problems with very large data sets (due to memory exhaustion, obviously).
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
     * @var string
     */
    private $dataKey;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private $data;

    /**
     * WC_Retailcrm_Paginated_Request constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

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
     * Sets method params for API client (leave `{{page}}` instead of page and `{{limit}}` instead of limit)
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
     * Sets dataKey (key with data in response)
     *
     * @param string $dataKey
     *
     * @return self
     */
    public function setDataKey($dataKey)
    {
        $this->dataKey = $dataKey;
        return $this;
    }

    /**
     * Sets record limit per request
     *
     * @param int $limit
     *
     * @return self
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Executes request
     *
     * @return $this
     */
    public function execute()
    {
        $this->data = array();
        $response = true;
        $page = 1;

        do {
            $response = call_user_func_array(
                array($this->api, $this->method),
                $this->buildParams($this->params, $page)
            );

            if ($response instanceof WC_Retailcrm_Response && $response->offsetExists($this->dataKey)) {
                $this->data = array_merge($response[$this->dataKey]);
                $page = $response['pagination']['currentPage'] + 1;
            }

            time_nanosleep(0, 300000000);
        } while ($response && (isset($response['pagination'])
            && $response['pagination']['currentPage'] < $response['pagination']['totalPageCount']));

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
        $this->method = '';
        $this->limit = 100;
        $this->data = array();

        return $this;
    }

    /**
     * buildParams
     *
     * @param array $placeholderParams
     * @param int   $currentPage
     *
     * @return mixed
     */
    private function buildParams($placeholderParams, $currentPage)
    {
        foreach ($placeholderParams as $key => $param) {
            if ($param == '{{page}}') {
                $placeholderParams[$key] = $currentPage;
            }

            if ($param == '{{limit}}') {
                $placeholderParams[$key] = $this->limit;
            }
        }

        return $placeholderParams;
    }
}
