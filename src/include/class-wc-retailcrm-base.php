<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Base - Main settings plugin.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

if (!class_exists('WC_Retailcrm_Base')) {
    if (!class_exists('WC_Retailcrm_Abstracts_Settings')) {
        include_once(WC_Integration_Retailcrm::checkCustomFile('include/abstracts/class-wc-retailcrm-abstracts-settings.php'));
    }

    class WC_Retailcrm_Base extends WC_Retailcrm_Abstracts_Settings
    {
        /** @var WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5|bool */
        protected $apiClient;

        /** @var \WC_Retailcrm_Customers */
        protected $customers;

        /** @var \WC_Retailcrm_Orders */
        protected $orders;

        /** @var WC_Retailcrm_Uploader */
        protected $uploader;

        /**
         * Init and hook in the integration.
         *
         * @param WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5|bool $retailcrm (default = false)
         */
        public function __construct($retailcrm = false)
        {
            parent::__construct();

            if (!class_exists('WC_Retailcrm_Proxy')) {
                include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-proxy.php'));
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
                new WC_Retailcrm_Customer_Address()
            );

            $this->orders = new WC_Retailcrm_Orders(
                $this->apiClient,
                $this->settings,
                new WC_Retailcrm_Order_Item($this->settings),
                new WC_Retailcrm_Order_Address(),
                $this->customers,
                new WC_Retailcrm_Order($this->settings),
                new WC_Retailcrm_Order_Payment($this->settings)
            );

            $this->uploader = new WC_Retailcrm_Uploader($this->apiClient, $this->orders, $this->customers);

            // Actions.
            add_action('woocommerce_update_options_integration_' .  $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'api_sanitized'));
            add_action('admin_bar_menu', array($this, 'add_retailcrm_button'), 100);
            add_action('woocommerce_checkout_order_processed', array($this, 'retailcrm_process_order'), 10, 1);
            add_action('retailcrm_history', array($this, 'retailcrm_history_get'));
            add_action('retailcrm_icml', array($this, 'generate_icml'));
            add_action('retailcrm_inventories', array($this, 'load_stocks'));
            add_action('wp_ajax_do_upload', array($this, 'upload_to_crm'));
            add_action('wp_ajax_cron_info', array($this, 'get_cron_info'), 99);
            add_action('wp_ajax_set_meta_fields', array($this, 'set_meta_fields'), 99);
            add_action('wp_ajax_content_upload', array($this, 'count_upload_data'), 99);
            add_action('wp_ajax_generate_icml', array($this, 'generate_icml'));
            add_action('wp_ajax_upload_selected_orders', array($this, 'upload_selected_orders'));
            add_action('admin_print_footer_scripts', array($this, 'ajax_generate_icml'), 99);
            add_action('woocommerce_update_customer', array($this, 'update_customer'), 10, 1);
            add_action('user_register', array($this, 'create_customer'), 10, 2);
            add_action('profile_update', array($this, 'update_customer'), 10, 2);
            add_action('wp_print_scripts', array($this, 'initialize_analytics'), 98);
            add_action('wp_print_scripts', array($this, 'initialize_daemon_collector'), 99);
            add_action('wp_print_scripts', array($this, 'initialize_online_assistant'), 101);
            add_action('wp_enqueue_scripts', array($this, 'include_whatsapp_icon_style'), 101);
            add_action('wp_print_footer_scripts', array($this, 'initialize_whatsapp'), 101);
            add_action('wp_print_footer_scripts', array($this, 'send_analytics'), 99);
            add_action('admin_enqueue_scripts', array($this, 'include_files_for_admin'), 101);
            add_action('woocommerce_new_order', array($this, 'create_order'), 11, 1);

            if (
                !$this->get_option('deactivate_update_order')
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

        /**
         * Generate ICML
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function generate_icml()
        {
            /*
             * A temporary solution.
             * We have rebranded the module and changed the name of the ICML file.
             * This solution checks the url specified to the ICML file and updates it if necessary.
             */

            $codeSite   = '';
            $infoApiKey = $this->apiClient->credentials();

            if (empty($infoApiKey) === false && $infoApiKey->isSuccessful() === true) {
                if (empty($infoApiKey['siteAccess']) === false && $infoApiKey['siteAccess'] === 'access_selective') {
                    if (empty($infoApiKey['sitesAvailable']) === false && count($infoApiKey['sitesAvailable']) === 1) {
                        $codeSite = $infoApiKey['sitesAvailable'][0];
                    }
                }
            }

            if (empty($codeSite) === false) {
                $getSites = $this->apiClient->sitesList();

                if (empty($getSites['sites']) === false && $getSites->isSuccessful() === true) {
                    if (empty($getSites['sites'][$codeSite]) === false) {
                        $dataSite = $getSites['sites'][$codeSite];

                        if (empty($dataSite['ymlUrl']) === false) {
                            $ymlUrl = $dataSite['ymlUrl'];

                            if (strpos($ymlUrl, 'simla') === false) {
                                $ymlUrl = str_replace('/retailcrm.xml', '/simla.xml', $ymlUrl);
                                $dataSite['ymlUrl'] = $ymlUrl;

                                $this->apiClient->sitesEdit($dataSite);
                            }
                        }
                    }
                }
            }

            $retailCrmIcml = new WC_Retailcrm_Icml();
            $retailCrmIcml->generate();
        }

        /**
         * Get history
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_history_get()
        {
            $retailcrm_history = new WC_Retailcrm_History($this->apiClient);
            $retailcrm_history->getHistory();
        }

        /**
         * @param int $order_id
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_process_order($order_id)
        {
            $this->orders->orderCreate($order_id);
        }

        /**
         * Load stock from retailCRM
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function load_stocks()
        {
            $inventories = new WC_Retailcrm_Inventories($this->apiClient);
            $inventories->updateQuantity();
        }

        /**
         * Upload selected orders
         *
         * @codeCoverageIgnore Check in another tests
         *
         * @return void
         */
        public function upload_selected_orders()
        {
            $this->uploader->uploadSelectedOrders();
        }

        /**
         * Upload archive customers and order to retailCRM
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function upload_to_crm()
        {
            $page = filter_input(INPUT_POST, 'Step');
            $entity = filter_input(INPUT_POST, 'Entity');

            if ($entity === 'customer') {
                $this->uploader->uploadArchiveCustomers($page);
            } else {
                $this->uploader->uploadArchiveOrders($page);
            }
        }

        /**
         * Create customer in retailCRM
         *
         * @codeCoverageIgnore There is a task for analysis
         *
         * @param int $customerId
         *
         * @return void
         * @throws Exception
         */
        public function create_customer($customerId)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            if (empty($customerId)) {
                WC_Retailcrm_Logger::add('Error: Customer externalId is empty');

                return;
            }

            $this->customers->registerCustomer($customerId);
        }

        /**
         * Edit customer in retailCRM
         *
         * @codeCoverageIgnore Check in another tests
         *
         * @param int $customerId
         *
         * @return void
         * @throws Exception
         */
        public function update_customer($customerId)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            if (empty($customerId)) {
                WC_Retailcrm_Logger::add('Error: Customer externalId is empty');

                return;
            }

            $this->customers->updateCustomer($customerId);
        }

        /**
         * Create order in RetailCRM from admin panel
         *
         * @codeCoverageIgnore Check in another tests
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
         * @codeCoverageIgnore Check in another tests
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
         *
         * @codeCoverageIgnore Check in another tests
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
         *
         * @codeCoverageIgnore Check in another tests
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
         * In this method we include files in admin WP
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        public function include_files_for_admin()
        {
            $this->include_css_files_for_admin();
            $this->include_js_scripts_for_admin();
        }

        /**
         * In this method we include CSS file
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        private function include_css_files_for_admin()
        {
            $path =  plugins_url() . '/woo-retailcrm/assets/css/';

            // Include style for export
            wp_register_style('retailcrm-export-style', $path . 'progress-bar.min.css', false, '0.1');
            wp_enqueue_style('retailcrm-export-style');

            // Include style for debug info
            wp_register_style('retailcrm-debug-info-style', $path . 'debug-info.min.css', false, '0.1');
            wp_enqueue_style('retailcrm-debug-info-style');

            // Include style for meta fields
            wp_register_style('retailcrm-meta-fields-style', $path . 'meta-fields.min.css', false, '0.1');
            wp_enqueue_style('retailcrm-meta-fields-style');
        }

        /**
         * In this method we include JS scripts.
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        private function include_js_scripts_for_admin()
        {
            $path =  plugins_url() . '/woo-retailcrm/assets/js/';

            wp_register_script('retailcrm-export', $path . 'retailcrm-export.js', false, '0.1');
            wp_enqueue_script('retailcrm-export', $path . 'retailcrm-export.js', '', '', true);

            wp_register_script('retailcrm-cron-info', $path . 'retailcrm-cron-info.js', false, '0.1');
            wp_enqueue_script('retailcrm-cron-info', $path . 'retailcrm-export.js', '', '', true);

            wp_register_script('retailcrm-meta-fields', $path . 'retailcrm-meta-fields.js', false, '0.1');
            wp_enqueue_script('retailcrm-meta-fields', $path . 'retailcrm-meta-fields.js', '', '', true);
        }

        /**
         * Include style for WhatsApp icon
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        public function include_whatsapp_icon_style()
        {
            wp_register_style('whatsapp_icon_style', plugins_url() . '/woo-retailcrm/assets/css/whatsapp-icon.min.css', false, '0.1');
            wp_enqueue_style('whatsapp_icon_style');
        }

        /**
         * Initialize WhatsApp
         */
        public function initialize_whatsapp()
        {
            if ($this->get_option('whatsapp_active') === 'yes' && !is_admin() && !is_wplogin()) {
                $phoneNumber = $this->get_option('whatsapp_number');
                $positionIcon = $this->get_option('whatsapp_location_icon') === 'yes' ? 'right' : 'left';
                $whatsAppHtml = '<div class="whatsapp-icon whatsapp-icon_%s">
                                <a class="whatsapp-icon__link" href="https://api.whatsapp.com/send/?phone=%s&text&app_absent=0" target="_blank">
                                  <svg class="whatsapp-icon__icon" fill="none" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12z" fill="#25D366"/><path d="M12.396 18.677h-.003a7.13 7.13 0 01-3.41-.869L5.2 18.8l1.013-3.697a7.123 7.123 0 01-.953-3.567C5.262 7.6 8.463 4.4 12.396 4.4c1.909.001 3.7.744 5.047 2.093a7.093 7.093 0 012.088 5.048c-.001 3.934-3.201 7.134-7.135 7.136zm-3.238-2.16l.217.128c.91.54 1.954.826 3.018.827h.003a5.94 5.94 0 005.93-5.931 5.895 5.895 0 00-1.735-4.196 5.89 5.89 0 00-4.193-1.74 5.94 5.94 0 00-5.933 5.931c0 1.12.313 2.212.907 3.156l.14.225-.599 2.188 2.245-.588zm6.588-3.44c.125.06.209.101.245.161.044.074.044.431-.104.848-.15.416-.861.796-1.204.848a2.443 2.443 0 01-1.123-.071 10.223 10.223 0 01-1.016-.376c-1.672-.721-2.801-2.341-3.015-2.648l-.031-.044-.002-.002c-.094-.126-.726-.97-.726-1.842 0-.821.403-1.252.589-1.45l.035-.038a.655.655 0 01.475-.223c.12 0 .238.001.342.007h.04c.104 0 .233-.001.361.306.05.118.121.293.197.478.154.373.323.786.353.846.044.089.074.193.015.312l-.025.05c-.045.092-.078.159-.153.247l-.092.11c-.061.075-.123.15-.176.203-.09.089-.182.185-.078.364.104.178.462.762.992 1.235.57.508 1.065.723 1.316.832.049.02.088.038.118.053.178.089.282.074.386-.045.104-.119.446-.52.564-.7.12-.178.238-.148.402-.088.163.06 1.04.49 1.218.58l.097.048z" fill="#FDFDFD"/></svg>
                                </a>
                                <div class="chat-btn__wrapper">
                                  <p class="chat-btn__text">
                                    Powered<br>by
                                    <a href="https://www.simla.com?utm_source=woocommerce&utm_medium=home_button&utm_campaign=whatsapp_link" target="_blank" class="chat-btn__link">Simla.com</a>  
                                  </p>
                                </div>
                            </div>';

                echo sprintf($whatsAppHtml, $positionIcon, $phoneNumber);
            }
        }

        /**
         * Return count upload data
         */
        public function count_upload_data()
        {
            $translate = array(
                'tr_order'          => __('Orders', 'retailcrm'),
                'tr_customer'       => __('Customers', 'retailcrm'),
                'tr_empty_field'    => __('The field cannot be empty, enter the order ID', 'retailcrm'),
                'tr_successful'     => __('Orders were uploaded', 'retailcrm'),
            );

            echo json_encode(
                array(
                    'count_orders' => $this->uploader->getCountOrders(),
                    'count_users'  => $this->uploader->getCountUsers(),
                    'translate'    => $translate,
                )
            );

            wp_die();
        }

        /**
         * Return time work next cron
         */
        public function get_cron_info() {
            $defaultValue = __('This option is disabled', 'retailcrm');
            $icml         = $defaultValue;
            $history      = $defaultValue;
            $inventories  = $defaultValue;
            $translate    = array(
                'tr_td_cron'        => __('Cron launches', 'retailcrm'),
                'tr_td_icml'        => __('Generation ICML', 'retailcrm'),
                'tr_td_history'     => __('Syncing history', 'retailcrm'),
                'tr_td_inventories' => __('Syncing inventories', 'retailcrm'),
            );

            if (isset($this->settings['history']) && $this->settings['history'] == static::YES) {
                $history = date( 'H:i:s d-m-Y', wp_next_scheduled('retailcrm_history'));
            }

            if (isset($this->settings['icml']) && $this->settings['icml'] == static::YES) {
                $icml = date( 'H:i:s d-m-Y', wp_next_scheduled('retailcrm_icml'));
            }

            if (isset($this->settings['sync']) && $this->settings['sync'] == static::YES) {
                $inventories = date( 'H:i:s d-m-Y ', wp_next_scheduled('retailcrm_inventories'));
            }

            echo json_encode(
                array(
                    'history'     => $history,
                    'icml'        => $icml,
                    'inventories' => $inventories,
                    'translate'   => $translate,
                )
            );

            wp_die();
        }

        /**
         * Set meta fields in settings
         */
        public function set_meta_fields()
        {
            $orderMetaData        = $this->getMetaData('order');
            $customerMetaData     = $this->getMetaData('user');
            $orderCustomFields    = $this->getCustomFields('order');
            $customerCustomFields = $this->getCustomFields('customer');

            $translate = array(
                'tr_lb_order'    => __('Custom fields for order', 'retailcrm'),
                'tr_lb_customer' => __('Custom fields for customer', 'retailcrm'),
                'tr_btn'         => __('Add new select for order', 'retailcrm'),
            );

            echo json_encode(
                array(
                    'order'     => array('custom' => $orderCustomFields, 'meta' => $orderMetaData),
                    'customer'  => array('custom' => $customerCustomFields, 'meta' => $customerMetaData),
                    'translate' => $translate,
                )
            );

            wp_die();
        }

        /**
         * Get custom fields with CRM
         *
         * @return array
         */
        private function getCustomFields($entity)
        {
            $customFields = array('default_retailcrm' => __('Select value', 'retailcrm'));
            $getCustomFields = $this->apiClient->customFieldsList(array('entity' => $entity), 100);

            if (!empty($getCustomFields['customFields']) && $getCustomFields->isSuccessful()) {
                foreach ($getCustomFields['customFields'] as $field) {
                    if (!empty($field['code']) && $field['name']) {
                        $customFields[$field['code']] = $field['name'];
                    }
                }
            }

            return $customFields;
        }

        /**
         * Get meta data with CMS
         *
         * @return array
         */
        private function getMetaData($entity)
        {
            global $wpdb;

            $table = $entity === 'order' ? $wpdb->postmeta : $wpdb->usermeta;

            $metaData = array('default_retailcrm' => __('Select value', 'retailcrm'));
            $sqlQuery = "SELECT DISTINCT `meta_key` FROM $table ORDER BY `meta_key`";
            $defaultMetaFields = file(
                __DIR__ . '/../assets/default/default_meta_fields.txt',
                FILE_IGNORE_NEW_LINES
            );

            foreach ($wpdb->get_results($sqlQuery) as $metaValue) {
                $metaData[$metaValue->meta_key] = $metaValue->meta_key;
            }

            return array_diff($metaData, $defaultMetaFields);
        }

        /**
         * Get retailcrm api client
         *
         * @return bool|WC_Retailcrm_Proxy
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

