<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Plugin - Internal plugin settings.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Plugin
{

    public $file;
    public static $history_run = false;
    private static $instance = null;

    const MARKETPLACE_LOGO = 'https://s3.eu-central-1.amazonaws.com/retailcrm-billing/images/5b69ce4bda663-woocommercesvg2.svg';
    const INTEGRATION_CODE = 'woocommerce';

    public static function getInstance($file)
    {
        if (self::$instance === null) {
            self::$instance = new self($file);
        }

        return self::$instance;
    }

    /**
     * @param $file
     *
     * @codeCoverageIgnore
     */
    private function __construct($file)
    {
        $this->file = $file;

        add_filter('cron_schedules', [$this, 'filter_cron_schedules'], 10, 1);
    }

    public function filter_cron_schedules($schedules)
    {
        return array_merge(
            $schedules,
            [
                'five_minutes' => [
                    'interval' => 300, // seconds
                    'display'  => __('Every 5 minutes')
                ],
                'three_hours' => [
                    'interval' => 10800, // seconds
                    'display'  => __('Every 3 hours')
                ],
                'fiveteen_minutes' => [
                    'interval' => 900, // seconds
                    'display'  => __('Every 15 minutes')
                ]
            ],
            apply_filters('retailcrm_add_cron_interval', $schedules)
        );
    }

    public function register_activation_hook()
    {
        register_activation_hook($this->file, array($this, 'activate'));
    }

    public function register_deactivation_hook()
    {
        register_deactivation_hook($this->file, array($this, 'deactivate'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function activate()
    {
        if (!class_exists('WC_Integration')) {
            add_action('admin_notices', [new WC_Integration_Retailcrm(), 'woocommerce_missing_notice']);

            return;
        }

        if (!class_exists('WC_Retailcrm_Icml')) {
            require_once(WC_Integration_Retailcrm::checkCustomFile('include/class-wc-retailcrm-icml.php'));
        }

        if (!class_exists('WC_Retailcrm_Icml_Writer')) {
            require_once(WC_Integration_Retailcrm::checkCustomFile('include/icml/class-wc-retailcrm-icml-writer.php'));
        }

        if (!class_exists('WC_Retailcrm_Base')) {
            require_once(WC_Integration_Retailcrm::checkCustomFile('include/class-wc-retailcrm-base.php'));
        }

        $retailcrm_icml = new WC_Retailcrm_Icml();
        $retailcrm_icml->generate();
    }

    public function deactivate()
    {
        do_action('retailcrm_deactivate');

        if (wp_next_scheduled('retailcrm_icml')) {
            wp_clear_scheduled_hook('retailcrm_icml');
        }

        if (wp_next_scheduled('retailcrm_history')) {
            wp_clear_scheduled_hook('retailcrm_history');
        }

        if (wp_next_scheduled('retailcrm_inventories')) {
            wp_clear_scheduled_hook('retailcrm_inventories');
        }
    }

    /**
     * Edit configuration in CRM
     *
     * @param WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5 $api_client
     * @param string $client_id
     * @param bool $active
     *
     * @return bool
     */
    public static function integration_module($api_client, $client_id, $active = true)
    {
        if (!$api_client instanceof WC_Retailcrm_Proxy) {
            return false;
        }

        $configuration = array(
            'name' => 'WooCommerce',
            'logo' => self::MARKETPLACE_LOGO,
            'code' => self::INTEGRATION_CODE . '-' . $client_id,
            'active' => $active,
        );

        $configuration['integrationCode'] = self::INTEGRATION_CODE;
        $configuration['baseUrl'] = get_site_url();
        $configuration['clientId'] = $client_id;
        $configuration['accountUrl'] = get_site_url();

        $response = $api_client->integrationModulesEdit($configuration);

        return !empty($response) && $response->isSuccessful();
    }

    /**
     * Unset empty fields
     *
     * @param array $arr input array
     *
     * @return array
     */
    public static function clearArray(array $arr)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $result = array();

        foreach ($arr as $index => $node) {
            $result[$index] = (is_array($node))
                ? self::clearArray($node)
                : $node;

            if (
                $result[$index] === ''
                || $result[$index] === null
                || (is_array($result[$index]) && count($result[$index]) < 1)
            ) {
                unset($result[$index]);
            }
        }

        return $result;
    }

    /**
     * Check running history
     *
     * @return boolean
     */
    public static function history_running()
    {
        return self::$history_run;
    }
}
