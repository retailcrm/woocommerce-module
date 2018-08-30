<?php
/**
 * Version: 3.3.3
 * WC requires at least: 3.0
 * WC tested up to: 3.4.3
 * Plugin Name: WooCommerce RetailCRM
 * Plugin URI: https://wordpress.org/plugins/woo-retailcrm/
 * Description: Integration plugin for WooCommerce & RetailCRM
 * Author: RetailDriver LLC
 * Author URI: http://retailcrm.pro/
 * Text Domain: retailcrm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists( 'WC_Integration_Retailcrm')) :

    /**
     * Class WC_Integration_Retailcrm
     */
    class WC_Integration_Retailcrm {

        private static $instance;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Construct the plugin.
         */
        public function __construct() {
            $this->load_plugin_textdomain();

            if (class_exists( 'WC_Integration' ) ) {
                require_once(dirname(__FILE__ ) . '/include/class-wc-retailcrm-base.php');
                require_once(dirname(__FILE__ ) . '/include/functions.php');
                add_filter('woocommerce_integrations', array( $this, 'add_integration'));
            } else {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            }
        }

        public function woocommerce_missing_notice() {
            echo '<div class="error"><p>Woocommerce is not installed</p></div>';
        }

        public function load_plugin_textdomain() {
            load_plugin_textdomain('retailcrm', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * Add a new integration to WooCommerce.
         *
         * @param $integrations
         *
         * @return array
         */
        public function add_integration( $integrations ) {
            $integrations[] = 'WC_Retailcrm_Base';
            return $integrations;
        }
    }

    if (!class_exists('WC_Retailcrm_Plugin')) {
        require_once (dirname(__FILE__) . '/include/class-wc-retailcrm-plugin.php');
    }

    $plugin = WC_Retailcrm_Plugin::getInstance(__FILE__);
    $plugin->register_activation_hook();
    $plugin->register_deactivation_hook();

    add_action('plugins_loaded', array('WC_Integration_Retailcrm', 'get_instance'), 0);
endif;
