<?php
/**
 * Version: 2.1.4
 * Plugin Name: WooCommerce RetailCRM
 * Plugin URI: https://wordpress.org/plugins/woo-retailcrm/
 * Description: Integration plugin for WooCommerce & RetailCRM
 * Author: RetailDriver LLC
 * Author URI: http://retailcrm.ru/
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

        /**
         * Construct the plugin.
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }

        /**
         * Initialize the plugin.
         */
        public function init() {
            if ( class_exists( 'WC_Integration' ) ) {
                include_once 'include/class-wc-retailcrm-base.php';
                include_once 'include/functions.php';
                add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
            } else {
                // throw an admin error if you like
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

    }

    $WC_Integration_Retailcrm = new WC_Integration_Retailcrm( __FILE__ );
endif;

/*
 * Check icml custom class
 */
function check_custom_icml()
{
    if (file_exists( WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-icml.php' )) {
        $file = WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-icml.php';
    } else {
        $file = __DIR__ . '/include/class-wc-retailcrm-icml.php';
    }
    return $file;
}

/*
 * Check orders custom class
 */
function check_custom_orders()
{
    if (file_exists( WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-orders.php' )) {
        $file =  WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-orders.php';
    } else {
        $file = __DIR__ . '/include/class-wc-retailcrm-orders.php';
    }
    return $file;
}

/*
 * Check customers custom class
 */
function check_custom_customers()
{
    if (file_exists( WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-customers.php' )) {
        $file = WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-customers.php';
    } else {
        $file = __DIR__ . '/include/class-wc-retailcrm-customers.php';
    }
    return $file;
}

/*
 * Check inventories custom class
 */
function check_custom_inventories()
{
    if (file_exists( WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-inventories.php' )) {
        $file = WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-inventories.php';
    } else {
        $file = __DIR__ . '/include/class-wc-retailcrm-inventories.php';
    }
    return $file;
}

/*
 * Check history custom class
 */
function check_custom_history()
{
    if (file_exists( WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-history.php' )) {
        $file = WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-history.php';
    } else {
        $file = __DIR__ . '/include/class-wc-retailcrm-history.php';
    }
    return $file;
}

/**
 * Activation action
 */
function retailcrm_install()
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option( 'active_plugins')))) {
        generate_icml();
    }
}

/**
 * Deactivation action
 */
function retailcrm_deactivation()
{
    if ( wp_next_scheduled ( 'retailcrm_icml' )) {
        wp_clear_scheduled_hook('retailcrm_icml');
    }
}

/**
 * Load stocks from CRM
 */
function load_stocks()
{
    if ( ! class_exists( 'WC_Retailcrm_Inventories' ) ) {
        include_once( check_custom_inventories() );
    }

    $inventories = new WC_Retailcrm_Inventories();
    $inventories->updateQuantity();
}

/**
 * Generate ICML file
 */
function generate_icml() 
{
    if ( ! class_exists( 'WC_Retailcrm_Icml' ) ) {
        include_once( check_custom_icml() );
    }

    $icml = new WC_Retailcrm_Icml();
    $icml->generate();
}

/**
 * Create order
 *
 * @param $order_id
 */
function retailcrm_process_order($order_id)
{
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( check_custom_orders() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->orderCreate($order_id);
}

/**
 * Update order status
 *
 * @param $order_id
 */
function retailcrm_update_order_status($order_id)
{
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( check_custom_orders() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->orderUpdateStatus($order_id);
}

/**
 * Update order payment
 *
 * @param $order_id
 */
function retailcrm_update_order_payment($order_id)
{
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( check_custom_orders() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->orderUpdatePayment($order_id);
}

/**
 * Update order items
 *
 * @param $order_id, $data
 */
function retailcrm_update_order_items($order_id, $data)
{   
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( check_custom_orders() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->orderUpdateItems($order_id, $data);
}

function retailcrm_history_get()
{
    if ( ! class_exists( 'WC_Retailcrm_History' ) ) {
        include_once( check_custom_history() );
    }

    $history_class = new WC_Retailcrm_History();
    $history_class->getHistory();
}

function create_customer($customer_id) {
    if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) {
        include_once( check_custom_customers() );
    }

    $customer_class = new WC_Retailcrm_Customers();
    $customer_class->createCustomer($customer_id);
}

function update_customer($customer_id) {
    if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) {
        include_once( check_custom_customers() );
    }

    $customer_class = new WC_Retailcrm_Customers();
    $customer_class->updateCustomer($customer_id);
}

function register_icml_generation() {
    // Make sure this event hasn't been scheduled
    if( !wp_next_scheduled( 'retailcrm_icml' ) ) {
        // Schedule the event
        wp_schedule_event( time(), 'three_hours', 'retailcrm_icml' );
    }
}

function register_retailcrm_history() {
    // Make sure this event hasn't been scheduled
    if( !wp_next_scheduled( 'retailcrm_history' ) ) {
        // Schedule the event
        wp_schedule_event( time(), 'five_minutes', 'retailcrm_history' );
    }
}

function check_inventories() {
    if( !wp_next_scheduled( 'retailcrm_inventories' ) ) {
        // Schedule the event
        wp_schedule_event( time(), 'fiveteen_minutes', 'retailcrm_inventories' );
    }
}

function filter_cron_schedules($param) {
    return array(
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
    );
}

function upload_to_crm() {
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( check_custom_orders() );
    }

    if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) {
        include_once( check_custom_customers() );
    }

    $options = array_filter(get_option( 'woocommerce_integration-retailcrm_settings' ));
  
    $orders = new WC_Retailcrm_Orders();
    $customers = new WC_Retailcrm_Customers();
    $customers->customersUpload();
    $orders->ordersUpload();

    $options['uploads'] = 'yes';
    update_option('woocommerce_integration-retailcrm_settings', $options);
}

function ajax_upload() {
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <script type="text/javascript" >
    jQuery('#uploads-retailcrm').bind('click', function() {
        jQuery.ajax({
            type: "POST",
            url: '<?php echo $ajax_url; ?>?action=do_upload',
            success: function (response) {
                alert('<?php echo __('Customers and orders were unloaded', 'retailcrm'); ?>');
                console.log('AJAX response : ',response);
            }
        });
    });
    </script>
    <?php
}

function ajax_generate_icml() {
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <script type="text/javascript" >
    jQuery('#icml-retailcrm').bind('click', function() {
        jQuery.ajax({
            type: "POST",
            url: '<?php echo $ajax_url; ?>?action=generate_icml',
            success: function (response) {
                alert('<?php echo __('Catalog were generated', 'retailcrm'); ?>');
                console.log('AJAX response : ',response);
            }
        });
    });
    </script>
    <?php
}

function update_order($order_id) {
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( check_custom_orders() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->updateOrder($order_id);
}

function initialize_analytics() {
    $options = get_option('woocommerce_integration-retailcrm_settings');

    if ($options && is_array($options)) {
        $options = array_filter($options);

        if (isset($options['ua']) && $options['ua'] == 'yes') {
            ?>
            <script>
                (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

                ga('create', '<?php echo $options['ua_code']; ?>', 'auto');

                function getRetailCrmCookie(name) {
                    var matches = document.cookie.match(new RegExp(
                        '(?:^|; )' + name + '=([^;]*)'
                    ));
                    return matches ? decodeURIComponent(matches[1]) : '';
                }

                ga('set', 'dimension<?php echo $options['ua_custom']; ?>', getRetailCrmCookie('_ga'));
                ga('send', 'pageview');
            </script>
            <?php
        }
    }
}

function send_analytics() {
    $options = get_option('woocommerce_integration-retailcrm_settings');

    if ($options && is_array($options)) {
        $options = array_filter($options);

        if (isset($_GET['key']) && isset($options['ua']) && $options['ua'] == 'yes') {
            $orderid = wc_get_order_id_by_order_key($_GET['key']);
            $order = new WC_Order($orderid);
            foreach ($order->get_items() as $item) {
                $uid = ($item['variation_id'] > 0) ? $item['variation_id'] : $item['product_id'] ;
                $_product = wc_get_product($uid);
                if ($_product) {
                    $order_item = array(
                        'id' => $uid,
                        'name' => $item['name'],
                        'price' => (float)$_product->get_price(),
                        'quantity' => $item['qty'],
                    );

                    $order_items[] = $order_item;
                }
            }

            $url = parse_url(get_site_url());
            $domain = $url['host'];
            ?>
            <script type="text/javascript">
                ga('require', 'ecommerce', 'ecommerce.js');
                ga('ecommerce:addTransaction', {
                    'id': <?php echo $order->get_id(); ?>,
                    'affiliation': '<?php echo $domain; ?>',
                    'revenue': <?php echo $order->get_total(); ?>,
                    'shipping': <?php echo $order->get_total_tax(); ?>,
                    'tax': <?php echo $order->get_shipping_total(); ?>
                });
                <?php
                foreach ($order_items as $item) {?>
                ga('ecommerce:addItem', {
                    'id': <?php echo $order->get_id(); ?>,
                    'sku': <?php echo $item['id']; ?>,
                    'name': '<?php echo $item['name']; ?>',
                    'price': <?php echo $item['price']; ?>,
                    'quantity': <?php echo $item['quantity']; ?>
                });
                <?php
                }?>
                ga('ecommerce:send');
            </script>
            <?php
        }
    }
}

function retailcrm_load_plugin_textdomain() {
    load_plugin_textdomain('retailcrm', FALSE, basename( dirname( __FILE__ ) ) . '/languages/');
}

register_activation_hook( __FILE__, 'retailcrm_install' );
register_deactivation_hook( __FILE__, 'retailcrm_deactivation' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option( 'active_plugins')))) {
    add_action('plugins_loaded', 'retailcrm_load_plugin_textdomain');
    add_filter('cron_schedules', 'filter_cron_schedules', 10, 1);
    add_action('woocommerce_checkout_order_processed', 'retailcrm_process_order', 10, 1);
    add_action('retailcrm_history', 'retailcrm_history_get');
    add_action('retailcrm_icml', 'generate_icml');
    add_action('retailcrm_inventories', 'load_stocks');
    add_action('init', 'check_inventories');
    add_action('init', 'register_icml_generation');
    add_action('init', 'register_retailcrm_history');
    add_action('wp_ajax_do_upload', 'upload_to_crm');
    add_action('wp_ajax_generate_icml', 'generate_icml');
    add_action('admin_print_footer_scripts', 'ajax_upload', 99);
    add_action('admin_print_footer_scripts', 'ajax_generate_icml', 99);
    add_action('woocommerce_created_customer', 'create_customer', 10, 1);
    add_action('woocommerce_update_customer', 'update_customer', 10, 1);
    add_action('woocommerce_update_order', 'update_order', 11, 1);
    add_action('wp_print_scripts', 'initialize_analytics', 98);
    add_action('wp_print_footer_scripts', 'send_analytics', 99);
}
