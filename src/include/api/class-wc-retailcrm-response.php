<?php

if (!class_exists('WC_Retailcrm_Exception_Json')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-exception-json.php'));
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Response -  Response class.
 *
 * @category Integration
 * @package  WC_Retailcrm_Response
 * @author   RetailCRM <dev@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://retailcrm.ru/docs/Developers/ApiVersion5
 */
class WC_Retailcrm_Response implements \ArrayAccess
{
    // HTTP response status code
    protected $statusCode;

    // response assoc array
    protected $response;

    // response raw data
    protected $rawResponse;

    /**
     * ApiResponse constructor.
     *
     * @param int   $statusCode   HTTP status code
     * @param mixed $responseBody HTTP body
     *
     * @throws WC_Retailcrm_Exception_Json
     */
    public function __construct($statusCode, $responseBody = null)
    {
        $this->statusCode = (int) $statusCode;
        $this->rawResponse = $responseBody;

        if (!empty($responseBody)) {
            $response = json_decode($responseBody, true);

            if (!$response && JSON_ERROR_NONE !== ($error = json_last_error())) {
                throw new WC_Retailcrm_Exception_Json(sprintf(
                    "Invalid JSON in the API response body. Error code #%s", esc_attr($error)),
                    esc_attr($error)
                );
            }

            $this->response = $response;
        }
    }

    /**
     * Return HTTP response status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * HTTP request was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode < 400;
    }

    /**
     * Allow to access for the property throw class method
     *
     * @param string $name      method name
     * @param mixed  $arguments method parameters
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // convert getSomeProperty to someProperty
        $propertyName = strtolower(substr($name, 3, 1)) . substr($name, 4);

        if (!isset($this->response[$propertyName])) {
            throw new \InvalidArgumentException(sprintf("Method '%s' not found", esc_attr($name)));
        }

        return $this->response[$propertyName];
    }

    /**
     * Allow to access for the property throw object property
     *
     * @param string $name property name
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->response[$name])) {
            throw new \InvalidArgumentException(sprintf("Property '%s' not found", esc_attr($name)));
        }

        return $this->response[$name];
    }

    /**
     * Offset set
     *
     * @param mixed $offset offset
     * @param mixed $value  value
     *
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('This activity not allowed');
    }

    /**
     * Offset unset
     *
     * @param mixed $offset offset
     *
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('This call not allowed');
    }

    /**
     * Check offset
     *
     * @param mixed $offset offset
     *
     */
    public function offsetExists($offset): bool
    {
        return isset($this->response[$offset]);
    }

    /**
     * Get offset
     *
     * @param mixed $offset offset
     * @return mixed
     * @throws \InvalidArgumentException
     *
     * TODO PHP < 8.0 не поддерживает тип mixed. Оператор | для перечисления типов также не поддерживается.
     */
    public function offsetGet($offset)
    {
        if (!isset($this->response[$offset])) {
            throw new \InvalidArgumentException(sprintf("Property '%s' not found", esc_attr($offset)));
        }

        return $this->response[$offset];
    }

    /**
     * Returns error string. If there's multiple errors present - they will be squashed into single string.
     *
     * @return string
     */
    public function getErrorString()
    {
        if ($this->offsetExists('errorMsg')) {
            return (string) $this->response['errorMsg'];
        }

        if (is_array($this->response['errors']) && $this->offsetExists('errors')) {
            $errorMessage = '';

            foreach ($this->response['errors'] as $error) {
                $errorMessage .= $error . ' >';
            }

            if (strlen($errorMessage) > 2) {
                return (string) substr($errorMessage, 0, strlen($errorMessage) - 2);
            }

            return $errorMessage;
        }

        return '';
    }

    /**
     * @return mixed|null
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }
}
