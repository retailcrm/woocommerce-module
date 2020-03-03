<?php

$_tests_dir = getenv('WP_TESTS_DIR');

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
    require $plugin_dir . 'src/retailcrm.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require '/tmp/woocommerce/tests/bootstrap.php';

$plugin_dir = dirname(dirname(__FILE__)) . '/';
// helpers
require $plugin_dir . 'tests/helpers/class-wc-retailcrm-response-helper.php';
require $plugin_dir . 'tests/helpers/class-wc-retailcrm-test-case-helper.php';
