<?php
/**
 * Version: 3.5.4
 * WC requires at least: 3.0
 * WC tested up to: 3.5.5
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
        const WOOCOMMERCE_SLUG = 'woocommerce';
        const WOOCOMMERCE_PLUGIN_PATH = 'woocommerce/woocommerce.php';

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
                require_once(dirname(__FILE__ ) . '/include/abstracts/class-wc-retailcrm-abstracts-settings.php');
                require_once(dirname(__FILE__ ) . '/include/abstracts/class-wc-retailcrm-abstracts-data.php');
                require_once(dirname(__FILE__ ) . '/include/abstracts/class-wc-retailcrm-abstracts-address.php');
                require_once(dirname(__FILE__ ) . '/include/order/class-wc-retailcrm-order.php');
                require_once(dirname(__FILE__ ) . '/include/order/class-wc-retailcrm-order-payment.php');
                require_once(dirname(__FILE__ ) . '/include/order/class-wc-retailcrm-order-item.php');
                require_once(dirname(__FILE__ ) . '/include/order/class-wc-retailcrm-order-address.php');
                require_once(dirname(__FILE__ ) . '/include/customer/class-wc-retailcrm-customer-address.php');
                require_once(dirname(__FILE__ ) . '/include/class-wc-retailcrm-base.php');
                require_once(dirname(__FILE__ ) . '/include/functions.php');
                add_filter('woocommerce_integrations', array( $this, 'add_integration'));
            } else {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            }
        }

        public function woocommerce_missing_notice() {
            if (static::isWooCommerceInstalled()) {
                if (!is_plugin_active(static::WOOCOMMERCE_PLUGIN_PATH)) {
                    echo '
                    <div class="error">
                        <p>
                            Activate WooCommerce in order to enable retailCRM integration!
                            <a href="' . wp_nonce_url(admin_url('plugins.php')) . '" aria-label="Activate WooCommerce">
                                Click here to open plugins manager
                            </a>
                        </p>
                    </div>
                    ';
                }
            } else {
                echo '
                <div class="error">
                    <p>
                        <a href="'
                    . static::generatePluginInstallationUrl(static::WOOCOMMERCE_SLUG)
                    . '" aria-label="Install WooCommerce">Install WooCommerce</a> in order to enable retailCRM integration!
                    </p>
                </div>
                ';
            }
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

        /**
         * Returns true if WooCommerce was found in plugin cache
         *
         * @return bool
         */
        private function isWooCommerceInstalled()
        {
            $plugins = wp_cache_get( 'plugins', 'plugins' );

            if (!$plugins) {
                $plugins = get_plugins();
            } elseif (isset($plugins[''])) {
                $plugins = $plugins[''];
            }

            if (!isset($plugins[static::WOOCOMMERCE_PLUGIN_PATH])) {
                return false;
            }

            return true;
        }

        /**
         * Generate plugin installation url
         *
         * @param $pluginSlug
         *
         * @return string
         */
        private function generatePluginInstallationUrl($pluginSlug)
        {
            $action = 'install-plugin';

            return wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => $action,
                        'plugin' => $pluginSlug
                    ),
                    admin_url( 'update.php' )
                ),
                $action.'_'.$pluginSlug
            );
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
