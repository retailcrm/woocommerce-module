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

    /**
     * Returns state name by it's code
     *
     * @param string $countryCode
     * @param string $stateCode
     *
     * @return string
     */
    protected function get_state_name($countryCode, $stateCode)
    {
        if (preg_match('/^[A-Z\-0-9]{0,5}$/', $stateCode) && !is_null($countryCode)) {
            $countriesProvider = new WC_Countries();
            $states = $countriesProvider->get_states($countryCode);

            if (!empty($states) && array_key_exists($stateCode, $states)) {
                return (string) $states[$stateCode];
            }
        }

        return $stateCode;
    }
}
