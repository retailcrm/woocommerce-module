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

        const TYPE = array(
            0 => 'REQUEST',
            1 => 'RESPONSE',
            2 => 'EXCEPTION'
        );

        /**
         * @var \WC_Logger_Interface $instance
         */
        private static $instance;

        /**
         * @var array $additionalHandlers
         */
        private static $additionalHandlers;

        /**
         * @var string $logIdentifier
         */
        private static $logIdentifier;

        /**
         * First called action name
         *
         * @var string $entrypoint
         */
        private static $entrypoint;

        /**
         * First called action time
         *
         * @var float $startTime
         */
        private static $startTime;

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
         * Called in base class for action hooks
         *
         * @param string $action
         * @param $id
         * @return void
         */
        public static function setEntry(string $action, $id = null)
        {
            if (empty(static::$entrypoint)) {
                static::$entrypoint = $id === null ? $action : sprintf('%s-%s', $action, $id);
            }
        }

        private static function getIdentifier()
        {
            if (empty(static::$logIdentifier)) {
                static::$logIdentifier = substr(uniqid('', false), -8);
            }

            return static::$logIdentifier;
        }

        private static function getStartTime()
        {
            if (empty(static::$startTime)) {
                static::$startTime = microtime(true);
            }

            return static::$startTime;
        }

        /**
         * Error logging
         *
         * @param string $method
         * @param string $message
         * @param null|string $type
         */
        public static function error(string $method, string $message, $type = null)
        {
            self::log($method, $message, $type, WC_Log_Levels::ERROR);
        }

        /**
         * Info logging
         *
         * @param string $method
         * @param string $message
         * @param null|string $type
         */
        public static function info(string $method, string $message, $type = null)
        {
            self::log($method, $message, $type, WC_Log_Levels::INFO);
        }

        /**
         * Regular logging function.
         *
         * @param string $method
         * @param string $message
         * @param string|null $type
         * @param string|null $level
         */
        private static function log(string $method, string $message, $type = null, $level = null)
        {
            $time = self::getStartTime();
            $context = ['time' => round((microtime(true) - $time), 3), 'source' => self::HANDLE];

            $message = sprintf(
                '%s [%s] <%s> %s=> %s',
                self::getIdentifier(),
                self::$entrypoint,
                $method,
                $type ? $type . ' ' : '',
                $message
            );

            self::getInstance()->log($level ?? 'info', $message, $context);
        }

        public static function formatWCObject($object): string
        {
            if ($object instanceof WC_Order) {
                return json_encode([
                    'id' => $object->get_id(),
                    'status' => $object->get_status(),
                    'date_modified' => $object->get_date_modified(),
                    'total' => $object->get_total(),
                    'shipping' => [
                        "first_name" => $object->get_shipping_first_name(),
                        "last_name" => $object->get_shipping_last_name(),
                        "company" => $object->get_shipping_company(),
                        "address_1" => $object->get_shipping_address_1(),
                        "address_2" => $object->get_shipping_address_2(),
                        "city" => $object->get_shipping_city(),
                        "state" => $object->get_shipping_state(),
                        "postcode" => $object->get_shipping_postcode(),
                        "country" => $object->get_shipping_country(),
                        "phone" => $object->get_shipping_phone()
                    ],
                    'email' => $object->get_billing_email(),
                    'payment_method_title' => $object->get_payment_method_title(),
                ]);
            }

            if ($object instanceof WC_Customer) {
                return json_encode([
                    'id' => $object->get_id(),
                    'date_modified' => $object->get_date_modified(),
                    'email' => $object->get_email(),
                    'display_name' => $object->get_display_name(),
                    'role' => $object->get_role(),
                    'username' => $object->get_username(),
                    'shipping' => [
                        "first_name" => $object->get_shipping_first_name(),
                        "last_name" => $object->get_shipping_last_name(),
                        "company" => $object->get_shipping_company(),
                        "address_1" => $object->get_shipping_address_1(),
                        "address_2" => $object->get_shipping_address_2(),
                        "city" => $object->get_shipping_city(),
                        "state" => $object->get_shipping_state(),
                        "postcode" => $object->get_shipping_postcode(),
                        "country" => $object->get_shipping_country(),
                        "phone" => $object->get_shipping_phone()
                    ],
                ]);
            }

            return method_exists($object, 'get_data') ?
                json_encode(array_filter($object->get_data())) : json_encode($object);
        }
    }
endif;
