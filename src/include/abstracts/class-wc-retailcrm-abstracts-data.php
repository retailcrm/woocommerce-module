<?php
/**
 * PHP version 5.6
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
    protected $data = array();

    /**
     * @return void
     */
    abstract public function reset_data();

    /**
     * @param $data
     *
     * @return self
     */
    abstract public function build($data);

    /**
     * @codeCoverageIgnore
     */
    protected function set_data_field($field, $value)
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
    protected function set_data_fields($fields)
    {
        if (!empty($fields)) {
            foreach ($fields as $field => $value) {
                $this->set_data_field($field, $value);
            }
        }
    }

    /**
     * @return array
     */
    public function get_data()
    {
        return $this->data;
    }
}
