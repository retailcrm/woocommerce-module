<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Customers
 * @category Integration
 * @author   RetailCRM
 */

if (!class_exists('WC_Retailcrm_Logger') && class_exists('WC_Log_Levels')):
    /**
     * Class WC_Retailcrm_Logger
     */
    class WC_Retailcrm_Logger
    {
        /** @var string */
        const HANDLE = 'retailcrm';

        /**
         * @var \WC_Logger_Interface $instance
         */
        private static $instance;

        /**
         * WC_Retailcrm_Logger constructor.
         */
        private function __construct() {}

        /**
         * Instantiates logger with file handler.
         *
         * @return \WC_Logger_Interface
         */
        private static function getInstance()
        {
            if (empty(static::$instance)) {
                static::$instance = new WC_Logger();
            }

            return static::$instance;
        }

        /**
         * Regular logging
         *
         * @param string $message
         * @param string $level
         */
        public static function add($message, $level = WC_Log_Levels::NOTICE)
        {
            self::getInstance()->add(self::HANDLE, $message, $level);
        }

        /**
         * Debug logging. Contains a lot of debug data like full requests & responses.
         *
         * @param string $method
         * @param string $message
         * @param string $level
         */
        public static function debug($method, $message, $level = WC_Log_Levels::DEBUG)
        {
            if (retailcrm_is_debug()) {
                if (!empty($method)) {
                    $message = sprintf(
                        '<%s> => %s',
                        $method,
                        $message
                    );
                }

                self::getInstance()->add(self::HANDLE . '_debug', $message, $level);
            }
        }
    }
endif;
