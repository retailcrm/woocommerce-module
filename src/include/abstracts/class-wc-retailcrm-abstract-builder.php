<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Abstract_Builder - Builds data for CRM.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
abstract class WC_Retailcrm_Abstract_Builder implements WC_Retailcrm_Builder_Interface
{
    /** @var array|mixed $data */
    protected $data;

    /**
     * @param array|mixed $data
     *
     * @return \WC_Retailcrm_Abstract_Builder
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array|mixed
     *
     * @codeCoverageIgnore
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return $this|\WC_Retailcrm_Builder_Interface
     */
    public function reset()
    {
        $this->data = [];

        return $this;
    }

    /**
     * Returns key if it's present in data array (or object which implements ArrayAccess).
     * Returns default value if key is not present in data, or data is not accessible as array.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function dataValue($key, $default = '')
    {
        return self::arrayValue($this->data, $key, $default);
    }

    /**
     * Returns key from array if it's present in array
     *
     * @param array|\ArrayObject $data
     * @param mixed              $key
     * @param string             $default
     *
     * @return mixed|string
     */
    protected static function arrayValue($data, $key, $default = '')
    {
        if (!is_array($data) && !($data instanceof ArrayAccess)) {
            return $default;
        }

        if (array_key_exists($key, $data) && !empty($data[$key])) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @return \WC_Retailcrm_Builder_Interface
     */
    abstract public function build();

    /**
     * Returns builder result. Should return null if WC_Retailcrm_Abstract_Builder::isBuilt() == false.
     *
     * @return mixed|null
     */
    abstract public function getResult();
}
