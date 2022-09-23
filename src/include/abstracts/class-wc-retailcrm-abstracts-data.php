<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Abstracts_Data - Class manage different data.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
abstract class WC_Retailcrm_Abstracts_Data
{
    /** @var array */
    protected $data = [];

    /**
     * @param $data
     *
     * @return self
     */
    abstract public function build($data);

    /**
     * @codeCoverageIgnore
     */
    protected function setField($field, $value)
    {
        if (isset($this->data[$field]) && \gettype($value) !== \gettype($this->data[$field])) {
            return false;
        }

        $this->data[$field] = $value;

        return true;
    }

    /**
     * @param $fields
     */
    protected function setDataFields($fields)
    {
        if (!empty($fields)) {
            foreach ($fields as $field => $value) {
                $this->setField($field, $value);
            }
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
