<?php

if (!defined( 'ABSPATH')) {
    exit;
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Log_Handler_Stdout - Handles log entries by writing to a stdout.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Log_Handler_Stdout extends WC_Log_Handler
{
    /**
     * @var array
     */
    protected $cached_logs = array();

    /**
     * Constructor for the logger.
     */
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'write_cached_logs'));
    }

    /**
     * @param int    $timestamp
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return bool
     */
    public function handle( $timestamp, $level, $message, $context )
    {
        if (isset( $context['source'] ) && $context['source']) {
            $handle = $context['source'];
        } else {
            $handle = 'log';
        }

        $entry = self::format_entry($timestamp, $level, $message, $context);

        return $this->add($entry, $handle);
    }

    /**
     * @param int    $timestamp
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    protected static function format_entry($timestamp, $level, $message, $context)
    {
        if (isset($context['_legacy'] ) && true === $context['_legacy']) {
            if (isset($context['source']) && $context['source']) {
                $handle = $context['source'];
            } else {
                $handle = 'log';
            }

            $message = apply_filters('woocommerce_logger_add_message', $message, $handle);
            $time = date_i18n('m-d-Y @ H:i:s');
            $entry = sprintf('%s - %s', $time, $message);
        } else {
            $entry = parent::format_entry( $timestamp, $level, $message, $context );
        }

        return $entry;
    }

    /**
     * @param string $entry Log entry text.
     * @param string $handle Log entry handle.
     *
     * @return bool True if write was successful.
     */
    protected function add($entry, $handle)
    {
        $result = false;

        if (is_resource(STDOUT)) {
            $result = fwrite(STDOUT, $entry . PHP_EOL);
        } else {
            $this->cache_log($entry, $handle);
        }

        return false !== $result;
    }

    /**
     * Cache log to write later.
     *
     * @param string $entry Log entry text.
     * @param string $handle Log entry handle.
     */
    protected function cache_log($entry, $handle)
    {
        $this->cached_logs[] = array(
            'entry'  => $entry,
            'handle' => $handle,
        );
    }

    /**
     * Write cached logs.
     */
    public function write_cached_logs()
    {
        foreach ($this->cached_logs as $log) {
            $this->add($log['entry'], $log['handle']);
        }
    }
}
