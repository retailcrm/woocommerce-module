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
        private const HANDLE = 'retailcrm';
        public const REQUEST = 'REQUEST';
        public const RESPONSE = 'RESPONSE';
        public CONST EXCEPTION = 'EXCEPTION';

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
         * @var string $currentHook
         */
        private static $currentHook;

        /**
         * @var float $startTime
         */
        private static $startTime;

        private function __construct() {}

        private static function getInstance(): WC_Logger_Interface
        {
            if (!static::$instance instanceof WC_Logger) {
                static::$instance = new WC_Logger(self::$additionalHandlers);
            }

            return static::$instance;
        }

        public static function setAdditionalHandlers(array $additionalHandlers): void
        {
            self::$additionalHandlers = $additionalHandlers;
        }

        public static function setHook(string $action, $id = null): void
        {
            static::$currentHook = $id === null ? $action : sprintf('%1$s-%2$d', $action, (int) $id);
        }

        private static function getIdentifier(): string
        {
            if (!is_string(static::$logIdentifier)) {
                static::$logIdentifier = substr(wp_generate_uuid4(), 0, 8);
            }

            return static::$logIdentifier;
        }

        private static function getStartTime(): float
        {
            if (!is_float(static::$startTime)) {
                static::$startTime = microtime(true);
            }

            return static::$startTime;
        }

        public static function exception(string $method, Throwable $exception, string $additionalMessage = ''): void
        {
            self::error(
                $method,
                sprintf(
                    '%1$s%2$s - Exception in file %3$s on line %4$s',
                    $additionalMessage,
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ),
                ['trace' => $exception->getTraceAsString()],
                self::EXCEPTION
            );
        }

        public static function error(string $method, string $message, array $context = [], $type = null): void
        {
            self::log($method, $message, $context, $type, WC_Log_Levels::ERROR);
        }

        public static function info(string $method, string $message, array $context = [], $type = null): void
        {
            self::log($method, $message, $context, $type, WC_Log_Levels::INFO);
        }

        private static function log(string $method, string $message, array $context = [], $type = null, $level = 'info'): void
        {
            $time = self::getStartTime();
            $context['time'] = round((microtime(true) - $time), 3);
            $context['source'] = self::HANDLE;

            $message = sprintf(
                '%1$s [%2$s] <%3$s> %4$s=> %5$s',
                self::getIdentifier(),
                self::$currentHook,
                $method,
                $type ? $type . ' ' : '',
                $message
            );

            self::getInstance()->log($level, $message, $context);
        }

        /**
         * Extracts information useful for logs from an object
         *
         * @param $object
         * @return array
         */
        public static function formatWcObject($object): array
        {
            if ($object instanceof WC_Order) {
                return self::formatWcOrder($object);
            }

            if ($object instanceof WC_Customer) {
                return self::formatWcCustomer($object);
            }

            if (is_object($object)) {
                return method_exists($object, 'get_data') ? (array_filter($object->get_data())) : [$object];
            }

            return [$object];
        }

        public static function formatWcOrder(WC_Order $order) {
            return [
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'date_modified' => $order->get_date_modified(),
                'total' => $order->get_total(),
                'shipping' => [
                    'first_name' => $order->get_shipping_first_name(),
                    'last_name' => $order->get_shipping_last_name(),
                    'company' => $order->get_shipping_company(),
                    'address_1' => $order->get_shipping_address_1(),
                    'address_2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'postcode' => $order->get_shipping_postcode(),
                    'country' => $order->get_shipping_country(),
                    'phone' => method_exists($order, 'get_shipping_phone') ? $order->get_shipping_phone() : '',
                ],
                'billing' => [
                    'phone' => $order->get_billing_phone()
                ],
                'email' => $order->get_billing_email(),
                'payment_method_title' => $order->get_payment_method_title(),
                'date_paid' => $order->get_date_paid(),
            ];
        }

        public static function formatWcCustomer(WC_Customer $customer)
        {
            return [
                'id' => $customer->get_id(),
                'date_modified' => $customer->get_date_modified(),
                'firstName' => $customer->get_first_name(),
                'lastName' => $customer->get_last_name(),
                'email' => $customer->get_email(),
                'display_name' => $customer->get_display_name(),
                'role' => $customer->get_role(),
                'username' => $customer->get_username(),
                'shipping' => [
                    'first_name' => $customer->get_shipping_first_name(),
                    'last_name' => $customer->get_shipping_last_name(),
                    'company' => $customer->get_shipping_company(),
                    'address_1' => $customer->get_shipping_address_1(),
                    'address_2' => $customer->get_shipping_address_2(),
                    'city' => $customer->get_shipping_city(),
                    'state' => $customer->get_shipping_state(),
                    'postcode' => $customer->get_shipping_postcode(),
                    'country' => $customer->get_shipping_country(),
                    'phone' => method_exists($customer, 'get_shipping_phone') ? $customer->get_shipping_phone() : '',
                ],
                'billing' => [
                    'phone' => $customer->get_billing_phone()
                ],
            ];
        }
    }
endif;
