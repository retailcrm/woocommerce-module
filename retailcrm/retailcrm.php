<?php
/**
 * Version: 1.1
 * Plugin Name: WooCommerce RetailCRM
 * Plugin URI: https://wordpress.org/plugins/retailcrm/
 * Description: Integration plugin for WooCommerce & RetailCRM
 * Author: RetailDriver LLC
 * Author URI: http://retailcrm.ru/
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

function check_custom_icml()
{
    if (file_exists( __DIR__ . '/include-custom/class-wc-retailcrm-icml.php' )) {
        $file = '/include-custom/class-wc-retailcrm-icml.php';
    } else {
        $file = '/include/class-wc-retailcrm-icml.php';
    }
    return $file;
}

function check_custom_order()
{
    if (file_exists( __DIR__ . '/include-custom/class-wc-retailcrm-orders.php' )) {
        $file = '/include-custom/class-wc-retailcrm-orders.php';
    } else {
        $file = '/include/class-wc-retailcrm-orders.php';
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
        include_once( __DIR__ . '/include/class-wc-retailcrm-inventories.php' );
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
        include_once( __DIR__ . check_custom_icml() );
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
        include_once( __DIR__ . check_custom_order() );
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
        include_once( __DIR__ . check_custom_order() );
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
        include_once( __DIR__ . check_custom_order() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->orderUpdatePayment($order_id);
}

/**
 * Update order
 *
 * @param $meta_id, $order_id, $meta_key, $_meta_value
 */
function retailcrm_update_order($meta_id, $order_id, $meta_key, $_meta_value)
{   
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( __DIR__ . check_custom_order() );
    }
    $order_class = new WC_Retailcrm_Orders();

    if ($meta_key == '_payment_method') {
        $order_class->orderUpdatePaymentType($order_id, $_meta_value);
    }

    $address = array();

    if ($meta_key == '_shipping_first_name') $address['firstName'] = $_meta_value;
    if ($meta_key == '_shipping_last_name') $address['lastName'] = $_meta_value;
    if ($meta_key == '_billing_phone') $address['phone'] = $_meta_value;
    if ($meta_key == '_billing_email') $address['email'] = $_meta_value;
    if ($meta_key == '_shipping_city') $address['delivery']['address']['city'] = $_meta_value;
    if ($meta_key == '_shipping_state') $address['delivery']['address']['region'] = $_meta_value;
    if ($meta_key == '_shipping_postcode') $address['delivery']['address']['index'] = $_meta_value;
    if ($meta_key == '_shipping_country') $address['delivery']['address']['countryIso'] = $_meta_value;
    if ($meta_key == '_shipping_address_1') $address['delivery']['address']['text'] = $_meta_value;
    if ($meta_key == '_shipping_address_2') $address['delivery']['address']['text'] .= $_meta_value;
    
    if (!empty($address)) {
        $order_class->orderUpdateShippingAddress($order_id, $address);
    }
}

/**
 * Update order items
 *
 * @param $order_id, $data
 */
function retailcrm_update_order_items($order_id, $data)
{   
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( __DIR__ . check_custom_order() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->orderUpdateItems($order_id, $data);
}

function retailcrm_history_get()
{
    if ( ! class_exists( 'WC_Retailcrm_History' ) ) {
        include_once( __DIR__ . '/include/class-wc-retailcrm-history.php' );
    }

    $history_class = new WC_Retailcrm_History();
    $history_class->getHistory();
}

function create_customer($customer_id) {
    if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) {
        include_once( __DIR__ . '/include/class-wc-retailcrm-customers.php' );
    }

    $customer_class = new WC_Retailcrm_Customers();
    $customer_class->createCustomer($customer_id);
}

function update_customer($customer_id, $data) {
    if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) {
        include_once( __DIR__ . '/include/class-wc-retailcrm-customers.php' );
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
        include_once( __DIR__ . check_custom_order() );
    }

    if ( ! class_exists( 'WC_Retailcrm_Customers' ) ) {
        include_once( __DIR__ . '/include/class-wc-retailcrm-customers.php' );
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
                alert('Заказы и клиенты выгружены');
                console.log('AJAX response : ',response);
            }
        });
    });
    </script>
    <?php
}

/**
 * update order (woocommerce 3.0)
 *
 * @param int $order_id
 *
 * @return void
 */
function update_order($order_id) {
    if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
        include_once( __DIR__ . check_custom_order() );
    }

    $order_class = new WC_Retailcrm_Orders();
    $order_class->updateOrder($order_id);
}

/**
 * create order (woocommerce 3.0)
 *
 * @param int $order_id
 *
 * @return void
 */

function create_order($order_id) {
    if ( get_post_type($order_id) == 'shop_order' && get_post_status( $order_id ) != 'auto-draft' ) {
        if ( ! class_exists( 'WC_Retailcrm_Orders' ) ) {
            include_once( __DIR__ . check_custom_order() );
        }

        $order_class = new WC_Retailcrm_Orders();
        $order_class->orderCreate($order_id);
    }
}

register_activation_hook( __FILE__, 'retailcrm_install' );
register_deactivation_hook( __FILE__, 'retailcrm_deactivation' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option( 'active_plugins')))) {
    load_plugin_textdomain('wc_retailcrm', false, dirname(plugin_basename( __FILE__ )) . '/');
    add_filter('cron_schedules', 'filter_cron_schedules', 10, 1);
    add_action('retailcrm_history', 'retailcrm_history_get');
    add_action('retailcrm_icml', 'generate_icml');
    add_action('retailcrm_inventories', 'load_stocks');
    add_action( 'init', 'check_inventories');
    add_action( 'init', 'register_icml_generation');
    add_action( 'init', 'register_retailcrm_history');
    add_action( 'wp_ajax_do_upload', 'upload_to_crm' );
    add_action('admin_print_footer_scripts', 'ajax_upload', 99);
    add_action( 'woocommerce_created_customer', 'create_customer', 10, 1 );
    add_action( 'woocommerce_checkout_update_user_meta', 10, 2 );

    if (version_compare(get_option('woocommerce_db_version'), '3.0', '<' )) {
        add_action('woocommerce_checkout_order_processed', 'retailcrm_process_order', 10, 1);
        add_action('woocommerce_order_status_changed', 'retailcrm_update_order_status', 11, 1);
        add_action('woocommerce_saved_order_items', 'retailcrm_update_order_items', 10, 2);
        add_action('update_post_meta', 'retailcrm_update_order', 11, 4);
        add_action('woocommerce_payment_complete', 'retailcrm_update_order_payment', 11, 1);
    } else {
        add_action('woocommerce_update_order', 'update_order', 11, 1);
        add_action('wp_insert_post', 'create_order' );
    }
}
