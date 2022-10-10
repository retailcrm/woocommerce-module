<?php

$wpVersion = getenv('WP_VERSION');

if (empty($wpVersion)) {
    die('WP version is empty!');
}

$pluginDirectory = dirname(dirname(__FILE__)) . '/';

// Require for WP 5.9 and 6.0 versions
if ($wpVersion === '5.9' || $wpVersion === '6.0') {
    echo 'Test';
    require_once $pluginDirectory . 'vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
    echo 'Test1';
}

$testsDir = getenv('WP_TESTS_DIR');

if (!$testsDir) {
    $testsDir = '/tmp/wordpress-tests-lib';
}

require_once $testsDir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

$wcVersion = getenv('WC_VERSION');

if (empty($wcVersion)) {
    die('WC version is empty!');
}

if ($wcVersion[0] === '6') {
    $wcOldBootstrap = '/tmp/woocommerce/plugins/woocommerce/tests/bootstrap.php';
    $wcNewBootstrap = '/tmp/woocommerce/plugins/woocommerce/tests/legacy/bootstrap.php';
} else {
    $wcOldBootstrap = '/tmp/woocommerce/tests/bootstrap.php';
    $wcNewBootstrap = '/tmp/woocommerce/tests/legacy/bootstrap.php';
}

if (file_exists($wcOldBootstrap)) {
    require_once $wcOldBootstrap;
} elseif (file_exists($wcNewBootstrap)) {
    require_once $wcNewBootstrap;
}

$outputLogsStdout = getenv('MODULE_LOGS_TO_STDOUT');

// helpers
require_once $pluginDirectory . 'src/include/components/class-wc-retailcrm-logger.php';
require_once $pluginDirectory . 'tests/helpers/class-wc-retailcrm-response-helper.php';
require_once $pluginDirectory . 'tests/helpers/class-wc-retailcrm-test-case-helper.php';
require_once $pluginDirectory . 'tests/helpers/class-wc-retailcrm-log-handler-stdout.php';

if (!empty($outputLogsStdout) && $outputLogsStdout == '1') {
    WC_Retailcrm_Logger::setAdditionalHandlers([new WC_Retailcrm_Log_Handler_Stdout()]);
}

// Call after require WooCommerce bootstrap
function _manually_load_plugin()
{
    $pluginDirectory = dirname(dirname(__FILE__)) . '/';

    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-customers.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-daemon-collector.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-ga.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-history.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-icml.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-inventories.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-orders.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-plugin.php';
    require_once $pluginDirectory . 'src/include/class-wc-retailcrm-uploader.php';
    require_once $pluginDirectory . 'src/retailcrm.php';
}
