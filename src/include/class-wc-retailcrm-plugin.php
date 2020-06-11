<?php

class WC_Retailcrm_Plugin {

    public $file;

    public static $history_run = false;
    private static $instance = null;

    const MARKETPLACE_LOGO = 'https://s3.eu-central-1.amazonaws.com/retailcrm-billing/images/5b69ce4bda663-woocommercesvg2.svg';
    const INTEGRATION_CODE = 'woocommerce';

    public static function getInstance($file) {
        if (self::$instance === null) {
            self::$instance = new self($file);
        }

        return self::$instance;
    }

    private function __construct($file) {
        $this->file = $file;

        add_filter('cron_schedules', array($this, 'filter_cron_schedules'), 10, 1);
    }

    public function filter_cron_schedules($schedules) {
        return array_merge(
            $schedules,
            array(
                'five_minutes' => array(
                    'interval' => 300, // seconds
                    'display'  => __('Every 5 minutes')
                ),
                'three_hours' => array(
                    'interval' => 10800, // seconds
                    'display'  => __('Every 3 hours')
                ),
                'fiveteen_minutes' => array(
                    'interval' => 900, // seconds
                    'display'  => __('Every 15 minutes')
                )
            )
        );
    }

    public function register_activation_hook() {
        register_activation_hook($this->file, array($this, 'activate'));
    }

    public function register_deactivation_hook() {
        register_deactivation_hook($this->file, array($this, 'deactivate'));
    }

    public function activate() {
        if (!class_exists('WC_Retailcrm_Icml')) {
            require_once (dirname(__FILE__) . '/class-wc-retailcrm-icml.php');
        }

        if (!class_exists('WC_Retailcrm_Base')) {
            require_once (dirname(__FILE__) . '/class-wc-retailcrm-base.php');
        }

        $retailcrm_icml = new WC_Retailcrm_Icml();
        $retailcrm_icml->generate();
    }

    public function deactivate() {
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
     * @param WC_Retailcrm_Proxy $api_client
     * @param string $client_id
     * @param bool $active
     *
     * @return boolean
     */
    public static function integration_module($api_client, $client_id, $api_version, $active = true) {

        if (!$api_client) {
            return false;
        }

        $configuration = array(
            'name' => 'WooCommerce',
            'logo' => self::MARKETPLACE_LOGO,
            'code' => self::INTEGRATION_CODE . '-' . $client_id,
            'active' => $active,
        );

        if ($api_version == 'v4') {
            $configuration['configurationUrl'] = get_site_url();

            $response = $api_client->marketplaceSettingsEdit($configuration);
        } else {
            $configuration['integrationCode'] = self::INTEGRATION_CODE;
            $configuration['baseUrl'] = get_site_url();
            $configuration['clientId'] = $client_id;
            $configuration['accountUrl'] = get_site_url();

            $response = $api_client->integrationModulesEdit($configuration);
        }

        if (!$response) {
            return false;
        }

        if (!empty($response) && $response->isSuccessful()) {
            return true;
        }

        return false;
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

