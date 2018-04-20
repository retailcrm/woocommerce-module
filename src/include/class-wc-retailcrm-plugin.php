<?php

class WC_Retailcrm_Plugin {

    public $file;

    private static $instance = null;

    public static function getInstance($file) {
        if (self::$instance === null) {
            self::$instance = new self($file);
        }

        return self::$instance;
    }

    private function __construct($file) {
        $this->file = $file;
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
        if ( wp_next_scheduled ( 'retailcrm_icml' )) {
            wp_clear_scheduled_hook('retailcrm_icml');
        }

        if ( wp_next_scheduled ( 'retailcrm_history' )) {
            wp_clear_scheduled_hook('retailcrm_history');
        }

        if ( wp_next_scheduled ( 'retailcrm_inventories' )) {
            wp_clear_scheduled_hook('retailcrm_inventories');
        }
    }
}
