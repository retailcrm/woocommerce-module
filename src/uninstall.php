<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 *
 * @link       https://wordpress.org/plugins/woo-retailcrm/
 *
 * @version    4.7.4
 *
 * @package    RetailCRM
 */

// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    exit;
}

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

wp_clear_scheduled_hook('retailcrm_icml');
wp_clear_scheduled_hook('retailcrm_history');
wp_clear_scheduled_hook('retailcrm_inventories');

global $wpdb;

$options = [
    'retailcrm_client_id',
    'retailcrm_active_in_crm',
    'retailcrm_orders_history_since_id',
    'retailcrm_customers_history_since_id',
    'woocommerce_integration-retailcrm_settings',
];

foreach ($options as $option) {
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name = {$option}");
}

// Clear any cached data that has been removed
wp_cache_flush();
// @codeCoverageIgnoreEnd
