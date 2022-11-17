<?php

if (!class_exists('WC_Retailcrm_Exception_Curl')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-exception-curl.php'));
}

if (!class_exists('WC_Retailcrm_Response')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-response.php'));
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Request - Request class.
 *
 * @category Integration
 * @package  WC_Retailcrm_Request
 * @author   RetailCRM <dev@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://retailcrm.ru/docs/Developers/ApiVersion5
 */
class WC_Retailcrm_Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    protected $url;
    protected $defaultParameters;

    /**
     * Client constructor.
     *
     * @param string $url               api url
     * @param array  $defaultParameters array of parameters
     *
     */
    public function __construct($url, array $defaultParameters = [])
    {
        $this->url = $url;
        $this->defaultParameters = $defaultParameters;
    }

    /**
     * Make HTTP request
     *
     * @param string $path       request url
     * @param string $method     (default: 'GET')
     * @param array  $parameters (default: array())
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @throws \InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     *
     * @return WC_Retailcrm_Response
     */
    public function makeRequest(
        $path,
        $method,
        array $parameters = array()
    ) {
        $allowedMethods = array(self::METHOD_GET, self::METHOD_POST);

        if (!in_array($method, $allowedMethods, false)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Method "%s" is not valid. Allowed methods are %s',
                    $method,
                    implode(', ', $allowedMethods)
                )
            );
        }

        $parameters = array_merge($this->defaultParameters, $parameters);

        $url = $this->url . $path;

        if (self::METHOD_GET === $method && count($parameters)) {
            $url .= '?' . http_build_query($parameters, '', '&');
        }

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_FAILONERROR, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 30);

        if (self::METHOD_POST === $method) {
            curl_setopt($curlHandler, CURLOPT_POST, true);
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $parameters);
        }

        $responseBody = curl_exec($curlHandler);
        $statusCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        $errno = curl_errno($curlHandler);
        $error = curl_error($curlHandler);

        curl_close($curlHandler);

        if ($errno) {
            throw new WC_Retailcrm_Exception_Curl($error, $errno);
        }

        return new WC_Retailcrm_Response($statusCode, $responseBody);
    }
}
