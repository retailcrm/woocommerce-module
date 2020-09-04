<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Base
 * @category Integration
 * @author   RetailCRM
 */

if (!class_exists('WC_Retailcrm_Base')) {
    if (!class_exists('WC_Retailcrm_Abstracts_Settings')) {
        include_once 'abstracts/class-wc-retailcrm-abstracts-settings.php';
    }

    /**
     * Class WC_Retailcrm_Base
     */
    class WC_Retailcrm_Base extends WC_Retailcrm_Abstracts_Settings
    {
        /** @var string */
        protected $api_url;

        /** @var string */
        protected $api_key;

        /** @var \WC_Retailcrm_Proxy|WC_Retailcrm_Client_V4|WC_Retailcrm_Client_V5|bool */
        protected $apiClient;

        /** @var mixed */
        protected $order_item;

        /** @var mixed */
        protected $order_address;

        /** @var \WC_Retailcrm_Customers */
        protected $customers;

        /** @var \WC_Retailcrm_Orders */
        protected $orders;

        /**
         * Init and hook in the integration.
         * @param \WC_Retailcrm_Proxy|WC_Retailcrm_Client_V4|WC_Retailcrm_Client_V5|bool $retailcrm (default = false)
         */
        public function __construct($retailcrm = false) {
            parent::__construct();

            if (!class_exists( 'WC_Retailcrm_Proxy')) {
                include_once(__DIR__ . '/api/class-wc-retailcrm-proxy.php');
            }

            if ($retailcrm === false) {
                $this->apiClient = $this->getApiClient();
            } else {
                $this->apiClient = $retailcrm;
                $this->init_settings_fields();
            }

            $this->customers = new WC_Retailcrm_Customers(
                $this->apiClient,
                $this->settings,
                new WC_Retailcrm_Customer_Address
            );

            $this->orders = new WC_Retailcrm_Orders(
                $this->apiClient,
                $this->settings,
                new WC_Retailcrm_Order_Item($this->settings),
                new WC_Retailcrm_Order_Address,
                $this->customers,
                new WC_Retailcrm_Order($this->settings),
                new WC_Retailcrm_Order_Payment($this->settings)
            );

            // Actions.
            add_action('woocommerce_update_options_integration_' .  $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'api_sanitized'));
            add_action('admin_bar_menu', array($this, 'add_retailcrm_button'), 100 );
            add_action('woocommerce_checkout_order_processed', array($this, 'retailcrm_process_order'), 10, 1);
            add_action('retailcrm_history', array($this, 'retailcrm_history_get'));
            add_action('retailcrm_icml', array($this, 'generate_icml'));
            add_action('retailcrm_inventories', array($this, 'load_stocks'));
            add_action('wp_ajax_do_upload', array($this, 'upload_to_crm'));
            add_action('wp_ajax_generate_icml', array($this, 'generate_icml'));
            add_action('wp_ajax_order_upload', array($this, 'order_upload'));
            add_action('admin_print_footer_scripts', array($this, 'ajax_upload'), 99);
            add_action('admin_print_footer_scripts', array($this, 'ajax_generate_icml'), 99);
            add_action('admin_print_footer_scripts', array($this, 'ajax_selected_order'), 99);
            add_action('woocommerce_created_customer', array($this, 'create_customer'), 10, 1);
            add_action('woocommerce_update_customer', array($this, 'update_customer'), 10, 1);
            add_action('user_register', array($this, 'create_customer'), 10, 2);
            add_action('profile_update', array($this, 'update_customer'), 10, 2);
            add_action('wp_print_scripts', array($this, 'initialize_analytics'), 98);
            add_action('wp_print_scripts', array($this, 'initialize_daemon_collector'), 99);
            add_action('wp_print_scripts', array($this, 'initialize_online_assistant'), 101);
            add_action('wp_print_footer_scripts', array($this, 'send_analytics'), 99);
            add_action('woocommerce_new_order', array($this, 'create_order'), 11, 1);

            if (!$this->get_option('deactivate_update_order')
                || $this->get_option('deactivate_update_order') == static::NO
            ) {
                add_action('woocommerce_update_order', array($this, 'update_order'), 11, 1);
            }

            // Deactivate hook
            add_action('retailcrm_deactivate', array($this, 'deactivate'));
        }

        /**
         * Init settings fields
         */
        public function init_settings_fields()
        {
            $this->init_form_fields();
            $this->init_settings();
        }

         /**
         * @param $settings
         *
         * @return array
         */
        public function api_sanitized($settings)
        {
            if (isset($settings['sync']) && $settings['sync'] == static::YES) {
                if (!wp_next_scheduled('retailcrm_inventories')) {
                    wp_schedule_event(time(), 'fiveteen_minutes', 'retailcrm_inventories');
                }
            } elseif (isset($settings['sync']) && $settings['sync'] == static::NO) {
                wp_clear_scheduled_hook('retailcrm_inventories');
            }

            if (isset($settings['history']) && $settings['history'] == static::YES) {
                if (!wp_next_scheduled('retailcrm_history')) {
                    wp_schedule_event(time(), 'five_minutes', 'retailcrm_history');
                }
            } elseif (isset($settings['history']) && $settings['history'] == static::NO) {
                wp_clear_scheduled_hook('retailcrm_history');
            }

            if (isset($settings['icml']) && $settings['icml'] == static::YES) {
                if (!wp_next_scheduled('retailcrm_icml')) {
                    wp_schedule_event(time(), 'three_hours', 'retailcrm_icml');
                }
            } elseif (isset($settings['icml']) && $settings['icml'] == static::NO) {
                wp_clear_scheduled_hook('retailcrm_icml');
            }

            if (!$this->get_errors() && !get_option('retailcrm_active_in_crm')) {
                $this->activate_integration($settings);
            }

            return $settings;
        }

        public function generate_icml() {
            $retailcrm_icml = new WC_Retailcrm_Icml();
            $retailcrm_icml->generate();
        }

        /**
         * Get history
         */
        public function retailcrm_history_get() {
            $retailcrm_history = new WC_Retailcrm_History($this->apiClient);
            $retailcrm_history->getHistory();
        }

        /**
         * @param int $order_id
         */
        public function retailcrm_process_order($order_id) {
            $this->orders->orderCreate($order_id);
        }

        /**
         * Load stock from retailCRM
         */
        public function load_stocks() {
            $inventories = new WC_Retailcrm_Inventories($this->apiClient);
            $inventories->updateQuantity();
        }

        /**
         * Upload selected orders
         */
        public function order_upload() {
            $ids = false;

            if (isset($_GET['order_ids_retailcrm'])) {
                $appendix = array();
                $ids = explode(',', $_GET['order_ids_retailcrm']);

                foreach ($ids as $key => $id) {
                    if (stripos($id, '-') !== false) {
                        $idSplit = explode('-', $id);

                        if (count($idSplit) == 2) {
                            $expanded = array();
                            $first = (int) $idSplit[0];
                            $last = (int) $idSplit[1];

                            for ($i = $first; $i <= $last; $i++) {
                                $expanded[] = $i;
                            }

                            $appendix = array_merge($appendix, $expanded);
                            unset($ids[$key]);
                        }
                    }
                }

                $ids = array_unique(array_merge($ids, $appendix));
            }

            if ($ids) {
                $this->orders->ordersUpload($ids);
            }
        }

        /**
         * Upload archive customers and order to retailCRM
         */
        public function upload_to_crm()
        {
            $options = array_filter(get_option(static::$option_key));

            $this->customers->customersUpload();
            $this->orders->ordersUpload();

            $options['uploads'] = static::YES;
            update_option(static::$option_key, $options);
        }

        /**
         * Create customer in retailCRM
         *
         * @param int $customer_id
         *
         * @return void
         * @throws \Exception
         */
        public function create_customer($customer_id)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

	        $client = $this->getApiClient();

	        if (empty($client)) {
		        return;
	        }

	        $wcCustomer = new WC_Customer($customer_id);
	        $email = $wcCustomer->get_billing_email();

	        if (empty($email)) {
	            $email = $wcCustomer->get_email();
            }

	        if (empty($email)) {
	            return;
            } else {
	            $wcCustomer->set_billing_email($email);
	            $wcCustomer->save();
            }

	        $response = $client->customersList(array('email' => $email));

	        if (!empty($response)
                && $response->isSuccessful()
                && isset($response['customers'])
                && count($response['customers']) > 0
            ) {
		        $customers = $response['customers'];
		        $customer = reset($customers);

		        if (isset($customer['id'])) {
		            $this->customers->updateCustomerById($customer_id, $customer['id']);
		            $builder = new WC_Retailcrm_WC_Customer_Builder();
		            $builder
                        ->setWcCustomer($wcCustomer)
                        ->setPhones(isset($customer['phones']) ? $customer['phones'] : array())
                        ->setAddress(isset($customer['address']) ? $customer['address'] : false)
                        ->build()
                        ->getResult()
                        ->save();
                }
	        } else {
                $this->customers->createCustomer($customer_id);
            }
        }

        /**
         * Edit customer in retailCRM
         * @param int $customer_id
         */
        public function update_customer($customer_id)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            if (empty($customer_id)) {
                return;
            }

            $this->customers->updateCustomer($customer_id);
        }

        /**
         * Create order in retailCRM from admin panel
         *
         * @param int $order_id
         */
        public function create_order($order_id)
        {
            if (is_admin()) {
                $this->retailcrm_process_order($order_id);
            }
        }

        /**
         * Edit order in retailCRM
         *
         * @param int $order_id
         *
         * @throws \Exception
         */
        public function update_order($order_id)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            $this->orders->updateOrder($order_id);
        }

        /**
         * Init google analytics code
         */
        public function initialize_analytics()
        {
            if ($this->get_option('ua') && $this->get_option('ua_code')) {
                $retailcrm_analytics = WC_Retailcrm_Google_Analytics::getInstance($this->settings);
                echo $retailcrm_analytics->initialize_analytics();
            } else {
                echo '';
            }
        }

        /**
         * Google analytics send code
         */
        public function send_analytics()
        {
            if ($this->get_option('ua') == static::YES && $this->get_option('ua_code') && is_checkout()) {
                $retailcrm_analytics = WC_Retailcrm_Google_Analytics::getInstance($this->settings);
                echo $retailcrm_analytics->send_analytics();
            } else {
                echo '';
            }
        }

        /**
         * Daemon collector
         */
        public function initialize_daemon_collector()
        {
            if ($this->get_option('daemon_collector') == static::YES && $this->get_option('daemon_collector_key')) {
                $retailcrm_daemon_collector = WC_Retailcrm_Daemon_Collector::getInstance($this->settings);
                echo $retailcrm_daemon_collector->initialize_daemon_collector();
            } else {
                echo '';
            }
        }
    
        /**
         * Initialize online consultant
         */
        public function initialize_online_assistant()
        {
            if (!is_admin() && !is_wplogin()) {
                echo $this->get_option('online_assistant');
            }
        }

        /**
        * Get retailcrm api client
        *
        * @return bool|WC_Retailcrm_Proxy|\WC_Retailcrm_Client_V4|\WC_Retailcrm_Client_V5
        */
        public function getApiClient()
        {
            if ($this->get_option('api_url') && $this->get_option('api_key')) {
                return new WC_Retailcrm_Proxy(
                    $this->get_option('api_url'),
                    $this->get_option('api_key'),
                    $this->get_option('corporate_enabled', 'no') === 'yes'
                );
            }

            return false;
        }

        /**
         * Deactivate module in marketplace retailCRM
         *
         * @return void
         */
        public function deactivate()
        {
            $api_client = $this->getApiClient();
            $clientId = get_option('retailcrm_client_id');

            WC_Retailcrm_Plugin::integration_module($api_client, $clientId, false);
            delete_option('retailcrm_active_in_crm');
        }

        /**
         * @param $settings
         *
         * @return void
         */
        private function activate_integration($settings)
        {
            $client_id = get_option('retailcrm_client_id');

            if (!$client_id) {
                $client_id = uniqid();
            }

            if ($settings['api_url'] && $settings['api_key']) {
                $api_client = new WC_Retailcrm_Proxy(
                    $settings['api_url'],
                    $settings['api_key'],
                    $settings['corporate_enabled'] === 'yes'
                );

                $result = WC_Retailcrm_Plugin::integration_module($api_client, $client_id);

                if ($result) {
                    update_option('retailcrm_active_in_crm', true);
                    update_option('retailcrm_client_id', $client_id);
                }
            }
        }
    }
}
