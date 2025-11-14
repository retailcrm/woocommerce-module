<?php
/**
 * Plugin Name: Simla.com
 * Plugin URI: https://wordpress.org/plugins/woo-retailcrm/
 * Description: Integration plugin for Simla.com
 * Author: RetailDriver LLC
 * Author URI: http://retailcrm.pro/
 * License: MIT
 * License URI: https://github.com/retailcrm/woocommerce-module/blob/master/LICENSE
 * Version: 5.0.4
 * Tested up to: 6.8
 * Requires Plugins: woocommerce
 * WC requires at least: 5.4
 * WC tested up to: 10.0
 * Text Domain: woo-retailcrm
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!class_exists( 'WC_Integration_Retailcrm')) :

    /**
     * Class WC_Integration_Retailcrm
     *
     * @codeCoverageIgnore
     */
    class WC_Integration_Retailcrm {
        const WOOCOMMERCE_SLUG = 'woocommerce';
        const WOOCOMMERCE_PLUGIN_PATH = 'woocommerce/woocommerce.php';
        const MODULE_VERSION = '5.0.4';

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
            if (class_exists( 'WC_Integration' )) {
                self::load_module();
                add_filter('woocommerce_integrations', [$this, 'add_integration']);
            } else {
                add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            }
        }

        public function woocommerce_missing_notice() {
            if (static::isWooCommerceInstalled()) {
                if (!is_plugin_active(static::WOOCOMMERCE_PLUGIN_PATH)) {
                    printf(
                        '<div class="error"><p>%1$s <a href="%2$s" aria-label="%3$s">%4$s</a></p></div>',
                        esc_html__( 'Activate WooCommerce in order to enable Simla integration!', 'woo-retailcrm' ),
                        esc_url( wp_nonce_url( admin_url( 'plugins.php' ) ) ),
                        esc_attr__( 'Activate WooCommerce', 'woo-retailcrm' ),
                        esc_html__( 'Click here to open plugins manager', 'woo-retailcrm' )
                    );
                }
            } else {
                printf(
                    '<div class="error"><p><a href="%1$s" aria-label="%2$s">%3$s</a> %4$s</p></div>',
                    esc_url( static::generatePluginInstallationUrl( static::WOOCOMMERCE_SLUG ) ),
                    esc_attr__( 'Install WooCommerce', 'woo-retailcrm' ),
                    esc_html__( 'Install WooCommerce', 'woo-retailcrm' ),
                    esc_html__( 'in order to enable RetailCRM integration!', 'woo-retailcrm' )
                );
            }
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
         * Loads module classes.
         */
        public static function load_module()
        {
            require_once(self::checkCustomFile('include/interfaces/class-wc-retailcrm-builder-interface.php'));
            require_once(self::checkCustomFile('include/models/class-wc-retailcrm-customer-switcher-state.php'));
            require_once(self::checkCustomFile('include/models/class-wc-retailcrm-customer-switcher-result.php'));
            require_once(self::checkCustomFile('include/components/class-wc-retailcrm-logger.php'));
            require_once(self::checkCustomFile('include/components/class-wc-retailcrm-history-assembler.php'));
            require_once(self::checkCustomFile('include/components/class-wc-retailcrm-customer-switcher.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-retailcrm-abstract-builder.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-retailcrm-abstracts-settings.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-retailcrm-abstracts-data.php'));
            require_once(self::checkCustomFile('include/abstracts/class-wc-retailcrm-abstracts-address.php'));
            require_once(self::checkCustomFile('include/customer/woocommerce/class-wc-retailcrm-wc-customer-builder.php'));
            require_once(self::checkCustomFile('include/order/class-wc-retailcrm-order.php'));
            require_once(self::checkCustomFile('include/order/class-wc-retailcrm-order-payment.php'));
            require_once(self::checkCustomFile('include/order/class-wc-retailcrm-order-item.php'));
            require_once(self::checkCustomFile('include/order/class-wc-retailcrm-order-address.php'));
            require_once(self::checkCustomFile('include/customer/class-wc-retailcrm-customer-address.php'));
            require_once(self::checkCustomFile('include/customer/class-wc-retailcrm-customer-corporate-address.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-icml.php'));
            require_once(self::checkCustomFile('include/icml/class-wc-retailcrm-icml-writer.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-orders.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-cart.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-loyalty.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-customers.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-inventories.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-history.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-ga.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-daemon-collector.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-base.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-uploader.php'));
            require_once(self::checkCustomFile('include/functions.php'));
            require_once(self::checkCustomFile('include/validators/url-validator/class-wc-retailcrm-url-constraint.php'));
            require_once(self::checkCustomFile('include/validators/url-validator/class-wc-retailcrm-url-validator.php'));
            require_once(self::checkCustomFile('include/validators/class-wc-retailcrm-validator-exception.php'));
            require_once(self::checkCustomFile('include/components/class-wc-retailcrm-loyalty-form.php'));
            require_once(self::checkCustomFile('include/validators/loyalty-validator/class-wc-retailcrm-loyalty-constraint.php'));
            require_once(self::checkCustomFile('include/validators/loyalty-validator/class-wc-retailcrm-loyalty-validator.php'));
            require_once(self::checkCustomFile('include/class-wc-retailcrm-upload-discount-price.php'));
        }

        /**
         * Check custom file
         *
         * @param string $file
         *
         * @return string
         */
        public static function checkCustomFile($file)
        {
            $wooPath = WP_PLUGIN_DIR . '/woo-retailcrm/' . $file;
            $withoutInclude = WP_CONTENT_DIR . '/retailcrm-custom/' . str_replace('include/', '', $file);

            if (file_exists($withoutInclude)) {
                return $withoutInclude;
            }

            if (file_exists($wooPath)) {
                return $wooPath;
            }

            return dirname(__FILE__) . '/' . $file;
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
                    [
                        'action' => $action,
                        'plugin' => $pluginSlug
                    ],
                    admin_url( 'update.php' )
                ),
                $action.'_'.$pluginSlug
            );
        }
    }

    if (!class_exists('WC_Retailcrm_Plugin')) {
        require_once(WC_Integration_Retailcrm::checkCustomFile('include/class-wc-retailcrm-plugin.php'));
    }

    $plugin = WC_Retailcrm_Plugin::getInstance(__FILE__);
    $plugin->register_activation_hook();
    $plugin->register_deactivation_hook();

    add_action('plugins_loaded', ['WC_Integration_Retailcrm', 'get_instance'], 0);

    add_action('before_woocommerce_init', function() {
        if (class_exists( Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });
endif;
