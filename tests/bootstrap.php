<?php

$_tests_dir = getenv('WP_TESTS_DIR');
$_wcOldBootstrap = '/tmp/woocommerce/tests/bootstrap.php';
$_wcNewBootstrap = '/tmp/woocommerce/tests/legacy/bootstrap.php';

if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    $plugin_dir =  dirname(dirname(__FILE__)) . '/';

    require $plugin_dir . 'src/include/class-wc-retailcrm-orders.php';
    require $plugin_dir . 'src/include/class-wc-retailcrm-customers.php';
    require $plugin_dir . 'src/include/class-wc-retailcrm-inventories.php';
    require $plugin_dir . 'src/include/class-wc-retailcrm-ga.php';
    require $plugin_dir . 'src/include/class-wc-retailcrm-daemon-collector.php';
    require $plugin_dir . 'src/include/class-wc-retailcrm-history.php';
    require $plugin_dir . 'src/include/class-wc-retailcrm-icml.php';
    require $plugin_dir . 'src/retailcrm.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

if (file_exists($_wcOldBootstrap)) {
    require $_wcOldBootstrap;
} elseif (file_exists($_wcNewBootstrap)) {
    require $_wcNewBootstrap;
}

$plugin_dir = dirname(dirname(__FILE__)) . '/';

// helpers
require $plugin_dir . 'src/include/components/class-wc-retailcrm-logger.php';
require $plugin_dir . 'tests/helpers/class-wc-retailcrm-response-helper.php';
require $plugin_dir . 'tests/helpers/class-wc-retailcrm-test-case-helper.php';
require $plugin_dir . 'tests/helpers/class-wc-retailcrm-log-handler-stdout.php';

WC_Retailcrm_Logger::setAdditionalHandlers(array(new WC_Retailcrm_Log_Handler_Stdout()));
