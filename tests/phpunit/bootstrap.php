<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    $plugin_dir = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/';
    // helpers
    require $plugin_dir . 'woocommerce-retailcrm/tests/helpers/class-wc-retailcrm-response-helper.php';

    require $plugin_dir . 'woocommerce-retailcrm/woo-retailcrm/include/class-wc-retailcrm-orders.php';
    require $plugin_dir . 'woocommerce-retailcrm/woo-retailcrm/include/class-wc-retailcrm-customers.php';
    require $plugin_dir . 'woocommerce-retailcrm/woo-retailcrm/include/class-wc-retailcrm-inventories.php';
    require $plugin_dir . 'woocommerce-retailcrm/woo-retailcrm/retailcrm.php';
    require '/woocommerce/woocommerce.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require '/woocommerce/tests/bootstrap.php';

$wc_tests_framework_base_dir = '/woocommerce/tests/framework/';

require_once( $wc_tests_framework_base_dir . 'class-wc-mock-session-handler.php' );
require_once( $wc_tests_framework_base_dir . 'class-wc-unit-test-case.php' );
require_once( $wc_tests_framework_base_dir . 'helpers/class-wc-helper-product.php' );
require_once( $wc_tests_framework_base_dir . 'helpers/class-wc-helper-coupon.php' );
require_once( $wc_tests_framework_base_dir . 'helpers/class-wc-helper-fee.php' );
require_once( $wc_tests_framework_base_dir . 'helpers/class-wc-helper-shipping.php' );
require_once( $wc_tests_framework_base_dir . 'helpers/class-wc-helper-customer.php' );
require_once( $wc_tests_framework_base_dir . 'helpers/class-wc-helper-order.php' );