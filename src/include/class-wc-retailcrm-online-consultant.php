<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Online_Consultant
 * @category Integration
 * @author   RetailCRM
 */

if (!class_exists('WC_Retailcrm_Online_Consultant')) {

    /**
     * WC_Retailcrm_Online_Consultant
     */
    class WC_Retailcrm_Online_Consultant {
        private static $instance;
        private $options;

        /**
         * @param array $options
         * Online_Consultant
         * @return WC_Retailcrm_Online_Consultant
         */
        public static function getInstance($options = array())
        {
            if (self::$instance === null) {
                self::$instance = new self($options);
            }

            return self::$instance;
        }

        /**
         * WC_Retailcrm_Online_Consultant constructor.
         *
         * @param array $options
         */
        private function __construct($options = array())
        {
            $this->options = $options;
        }

        /**
         * Initialize online consultant
         * @return string
         */
        public function initialize_consultant() 
        {
            return apply_filters('retailcrm_initialize_consultant', "<script>{$this->options[consultant_textarea]}</script>");
        }
    }
}
