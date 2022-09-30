<?php

if (!class_exists('WC_Retailcrm_Logger') && class_exists('WC_Log_Levels')) :

    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Logger - Allows display important debug information.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     * @codeCoverageIgnore
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
         * @var array $additionalHandlers
         */
        private static $additionalHandlers;

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
                static::$instance = new WC_Logger(self::$additionalHandlers);
            }

            return static::$instance;
        }

        /**
         * @param array $additionalHandlers
         */
        public static function setAdditionalHandlers($additionalHandlers)
        {
            self::$additionalHandlers = $additionalHandlers;
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
         * Regular logging with caller prefix
         *
         * @param string $caller
         * @param string $message
         * @param string $level
         */
        public static function addCaller($caller, $message, $level = WC_Log_Levels::NOTICE)
        {
            self::add(sprintf('<%s> => %s', $caller, $message), $level);
        }

        /**
         * Log error
         *
         * @param string $message
         */
        public static function error($message)
        {
            self::add($message, WC_Log_Levels::ERROR);
        }

        /**
         * Debug logging. Contains a lot of debug data like full requests & responses.
         * This log will work only if debug mode is enabled (see retailcrm_is_debug() for details).
         * Caller should be specified, or message will be ignored at all.
         *
         * @param string        $method
         * @param array|string  $messages
         */
        public static function debug($method, $messages)
        {
            if (retailcrm_is_debug()) {
                if (!empty($method) && !empty($messages)) {
                    $result = is_array($messages) ? substr(
                        array_reduce(
                            $messages,
                            function ($carry, $item) {
                                $carry .= ' ' . print_r($item, true);
                                return $carry;
                            }
                        ),
                        1
                    ) : $messages;

                    self::getInstance()->add(
                        self::HANDLE . '_debug',
                        sprintf(
                            '<%s> => %s',
                            $method,
                            $result
                        ),
                        WC_Log_Levels::DEBUG
                    );
                }
            }
        }
    }
endif;
