<?php

if (!class_exists('WC_Retailcrm_Base')) {
    if (!class_exists('WC_Retailcrm_Abstracts_Settings')) {
        include_once(WC_Integration_Retailcrm::checkCustomFile('include/abstracts/class-wc-retailcrm-abstracts-settings.php'));
    }

    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Base - Main settings plugin.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Base extends WC_Retailcrm_Abstracts_Settings
    {
        const ASSETS_DIR = '/woo-retailcrm/assets';

        /** @var WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5|bool */
        protected $apiClient;

        /** @var \WC_Retailcrm_Customers */
        protected $customers;

        /** @var \WC_Retailcrm_Orders */
        protected $orders;

        /** @var WC_Retailcrm_Uploader */
        protected $uploader;

        /** @var WC_Retailcrm_Cart */
        protected $cart;

        /** @var WC_Retailcrm_Loyalty */
        protected $loyalty;

        /** @var array */
        protected $updatedOrderId = [];

        /** @var array */
        protected $createdOrderId = [];

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
            add_action('woocommerce_update_options_integration_' .  $this->id, [$this, 'process_admin_options']);
            add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, [$this, 'retailcrm_api_sanitized']);
            add_action('admin_bar_menu', [$this, 'add_retailcrm_button'], 100);
            add_action('woocommerce_checkout_order_processed', [$this, 'retailcrm_process_order'], 10, 1);
            add_action('retailcrm_history', [$this, 'retailcrm_history_get']);
            add_action('retailcrm_icml', [$this, 'retailcrm_generate_icml']);
            add_action('retailcrm_inventories', [$this, 'retailcrm_load_stocks']);
            add_action('wp_ajax_retailcrm_do_upload', [$this, 'retailcrm_upload_to_crm']);
            add_action('wp_ajax_retailcrm_cron_info', [$this, 'retailcrm_get_cron_info'], 99);
            add_action('wp_ajax_retailcrm_set_meta_fields', [$this, 'retailcrm_set_meta_fields'], 99);
            add_action('wp_ajax_retailcrm_content_upload', [$this, 'retailcrm_count_upload_data'], 99);
            add_action('wp_ajax_retailcrm_generate_icml', [$this, 'retailcrm_generate_icml']);
            add_action('wp_ajax_retailcrm_upload_selected_orders', [$this, 'retailcrm_upload_selected_orders']);
            add_action('wp_ajax_retailcrm_clear_cron_tasks', [$this, 'retailcrm_clear_cron_tasks']);
            add_action('wp_ajax_retailcrm_get_status_coupon', [$this, 'retailcrm_get_status_coupon']);
            add_action('admin_print_footer_scripts', [$this, 'ajax_retailcrm_generate_icml'], 99);
            add_action('woocommerce_update_customer', [$this, 'retailcrm_update_customer'], 10, 1);
            add_action('user_register', [$this, 'retailcrm_create_customer'], 10, 2);
            add_action('profile_update', [$this, 'retailcrm_update_customer'], 10, 2);
            add_action('wp_print_scripts', [$this, 'retailcrm_initialize_analytics'], 98);
            add_action('wp_print_scripts', [$this, 'retailcrm_initialize_daemon_collector'], 99);
            add_action('wp_print_scripts', [$this, 'retailcrm_initialize_online_assistant'], 101);
            add_action('wp_enqueue_scripts', [$this, 'retailcrm_include_whatsapp_icon_style'], 101);
            add_action('wp_enqueue_scripts', [$this, 'retailcrm_include_js_script_for_tracker'], 101);
            add_action('wp_print_footer_scripts', [$this, 'retailcrm_initialize_whatsapp'], 101);
            add_action('wp_print_footer_scripts', [$this, 'retailcrm_send_analytics'], 99);
            add_action('admin_enqueue_scripts', [$this, 'retailcrm_include_files_for_admin'], 101);
            add_action('woocommerce_new_order', [$this, 'retailcrm_fill_array_create_orders'], 11, 1);
            add_action('shutdown', [$this, 'retailcrm_create_order'], -2);
            add_action('wp_console_upload', [$this, 'retailcrm_console_upload'], 99, 2);
            add_action('wp_footer', [$this, 'add_retailcrm_tracking_script'], 102);

            //Tracker
            add_action('wp_ajax_retailcrm_get_cart_items_for_tracker', [$this, 'retailcrm_get_cart_items_for_tracker'], 99);
            add_action('wp_ajax_retailcrm_get_customer_info_for_tracker', [$this, 'retailcrm_get_customer_info_for_tracker'], 99);
            add_action('wp_ajax_nopriv_retailcrm_get_cart_items_for_tracker', [$this, 'retailcrm_get_cart_items_for_tracker'], 99);
            add_action('wp_ajax_nopriv_retailcrm_get_customer_info_for_tracker', [$this, 'retailcrm_get_customer_info_for_tracker'], 99);

            if (
                !$this->get_option('deactivate_update_order')
                || $this->get_option('deactivate_update_order') == static::NO
            ) {
                add_action('woocommerce_update_order', [$this, 'retailcrm_fill_array_update_orders'], 11, 1);
                add_action('shutdown', [$this, 'retailcrm_update_order'], -1);
                add_action('woocommerce_saved_order_items', [$this, 'retailcrm_update_order_items'], 10, 1);
            }

            if (isLoyaltyActivate($this->settings)) {
                add_action('wp_ajax_retailcrm_register_customer_loyalty', [$this, 'retailcrm_register_customer_loyalty']);
                add_action('wp_ajax_retailcrm_activate_customer_loyalty', [$this, 'retailcrm_activate_customer_loyalty']);
                add_action('init', [$this, 'retailcrm_add_loyalty_endpoint'], 11, 1);
                add_action('woocommerce_account_menu_items', [$this, 'retailcrm_add_loyalty_item'], 11, 1);
                add_action('woocommerce_account_loyalty_endpoint', [$this, 'retailcrm_show_loyalty'], 11, 1);

                // Add coupon hooks for loyalty program
                add_action('woocommerce_cart_coupon', [$this, 'retailcrm_coupon_info'], 11, 1);
                //Remove coupons when cart changes
                add_action('woocommerce_add_to_cart', [$this, 'retailcrm_refresh_loyalty_coupon'], 11, 1);
                add_action('woocommerce_after_cart_item_quantity_update', [$this, 'retailcrm_refresh_loyalty_coupon'], 11, 1);
                add_action('woocommerce_cart_item_removed', [$this, 'retailcrm_refresh_loyalty_coupon'], 11, 1);
                add_action('woocommerce_before_cart_empted', [$this, 'retailcrm_clear_loyalty_coupon'], 11, 1);
                add_action('woocommerce_removed_coupon', [$this, 'retailcrm_remove_coupon'], 11, 1);
                add_action('woocommerce_applied_coupon', [$this, 'retailcrm_apply_coupon'], 11, 1);
                add_action('woocommerce_review_order_before_payment', [$this, 'retailcrm_reviewCreditBonus'], 11, 1);
                add_action('wp_trash_post', [$this, 'retailcrm_trash_order_action'], 10, 1);
                add_action('retailcrm_loyalty_upload_price', [$this, 'retailcrm_upload_loyalty_price']);
                add_action('admin_print_footer_scripts', [$this, 'ajax_retailcrm_upload_loyalty_price'], 99);
                add_action('wp_ajax_retailcrm_upload_loyalty_price', [$this, 'retailcrm_upload_loyalty_price']);
            }

            // Subscribed hooks
            add_action('register_form', [$this, 'retailcrm_checkout_form'], 99);
            add_action('woocommerce_register_form', [$this, 'retailcrm_checkout_form'], 99);

            if (get_option('woocommerce_enable_signup_and_login_from_checkout') === static::YES) {
                add_action(
                    'woocommerce_before_checkout_registration_form',
                    [$this, 'retailcrm_checkout_form'],
                    99
                );
            }

            if ($this->get_option('abandoned_carts_enabled') === static::YES) {
                $this->cart = new WC_Retailcrm_Cart($this->apiClient, $this->settings);

                add_action('woocommerce_add_to_cart', [$this, 'retailcrm_set_cart']);
                add_action('woocommerce_after_cart_item_quantity_update', [$this, 'retailcrm_set_cart']);
                add_action('woocommerce_cart_item_removed', [$this, 'retailcrm_set_cart']);
                add_action('woocommerce_cart_emptied', [$this, 'retailcrm_clear_cart']);
            }

            $this->loyalty = new WC_Retailcrm_Loyalty($this->apiClient, $this->settings);

            // Deactivate hook
            add_action('retailcrm_deactivate', [$this, 'retailcrm_deactivate']);

            //Activation configured module
            $this->activateModule();
        }

        function retailcrm_get_cart_items_for_tracker()
        {
            $cartItems = [];

            foreach (WC()->cart->get_cart() as $item) {
                $product = $item['data'];

                $cartItems[] = [
                    'id' => (string) $product->get_id(),
                    'sku' => $product->get_sku(),
                    'price' => wc_get_price_including_tax($product),
                    'quantity' => $item['quantity'],
                ];
            }

            wp_send_json_success($cartItems);
        }

        function retailcrm_get_customer_info_for_tracker()
        {
            if (is_user_logged_in()) {
                $user = wp_get_current_user();

                // TODO: В будущем можно получить больше данных.
                wp_send_json_success(['email' => $user->user_email, 'externalId' => $user->ID]);
            }
        }

        public function retailcrm_console_upload($entity, $page = 0)
        {
            $this->uploader->uploadConsole($entity, $page);
        }
        /**
         * Init settings fields
         */
        public function init_settings_fields()
        {
            WC_Retailcrm_Logger::setHook(current_action());
            $this->init_form_fields();
            $this->init_settings();
        }

        /**
         * @param $settings
         *
         * @return array
         */
        public function retailcrm_api_sanitized($settings)
        {
            WC_Retailcrm_Logger::setHook(current_action());
            WC_Retailcrm_Logger::info(
                __METHOD__,
                'Module settings',
                ['settings' => $settings]
            );
            $isLoyaltyUploadPrice = false;

            if (
                isset($settings['icml'], $settings['loyalty'])
                && $settings['icml'] === static::YES
                && $settings['loyalty'] === static::YES
            ) {
                $isLoyaltyUploadPrice = true;
            }

            $timeInterval = apply_filters(
                'retailcrm_cron_schedules',
                [
                    'icml' => 'three_hours',
                    'history' => 'five_minutes',
                    'inventories' => 'fiveteen_minutes',
                    'loyalty_upload_price' => 'four_hours'
                ]
            );

            if (isset($settings['sync']) && $settings['sync'] == static::YES) {
                if (!wp_next_scheduled('retailcrm_inventories')) {
                    wp_schedule_event(time(), $timeInterval['inventories'], 'retailcrm_inventories');
                }
            } elseif (isset($settings['sync']) && $settings['sync'] == static::NO) {
                wp_clear_scheduled_hook('retailcrm_inventories');
            }

            if (isset($settings['history']) && $settings['history'] == static::YES) {
                if (!wp_next_scheduled('retailcrm_history')) {
                    wp_schedule_event(time(), $timeInterval['history'], 'retailcrm_history');
                }
            } elseif (isset($settings['history']) && $settings['history'] == static::NO) {
                wp_clear_scheduled_hook('retailcrm_history');
            }

            if (isset($settings['icml']) && $settings['icml'] == static::YES) {
                if (!wp_next_scheduled('retailcrm_icml')) {
                    wp_schedule_event(time(), $timeInterval['icml'], 'retailcrm_icml');
                }
            } elseif (isset($settings['icml']) && $settings['icml'] == static::NO) {
                wp_clear_scheduled_hook('retailcrm_icml');
            }

            if ($isLoyaltyUploadPrice && !wp_next_scheduled('retailcrm_loyalty_upload_price')) {
                wp_schedule_event(time(), $timeInterval['loyalty_upload_price'], 'retailcrm_loyalty_upload_price');
            } elseif (!$isLoyaltyUploadPrice) {
                wp_clear_scheduled_hook('retailcrm_loyalty_upload_price');
            }

            if (!$this->get_errors() && !get_option('retailcrm_active_in_crm')) {
                $this->activate_integration($settings);
            }

            return $settings;
        }

        /**
         * Displaying the checkbox in the WP registration form(wp-login.php).
         * Displaying the checkbox in the WC registration form.
         * Displaying the checkbox in the Checkout order form.
         */
        public function retailcrm_checkout_form()
        {
            $style = is_wplogin()
                ? 'margin-left: 2em; display: block; position: relative; margin-top: -1.4em; line-height: 1.4em;'
                : '';

            $html = sprintf(
                '<div style="margin-bottom:15px">
                            <input type="checkbox" id="subscribeEmail" name="subscribe" value="subscribed"/>
                            <label style="%s" for="subscribeEmail">%s</label>
                        </div>',
                $style,
                esc_html__('I agree to receive promotional newsletters', 'woo-retailcrm')
            );

            $allowed_tags = [
                'div' => ['style' => []],
                'input' => [
                    'type'  => [],
                    'id'    => [],
                    'name'  => [],
                    'value' => [],
                ],
                'label' => ['for' => [], 'style' => []],
            ];

            echo wp_kses($html, $allowed_tags);
        }

        /**
         * If you change the time interval, need to clear the old cron tasks
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_clear_cron_tasks()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            WC_Retailcrm_Logger::setHook(current_action());
            wp_clear_scheduled_hook('retailcrm_icml');
            wp_clear_scheduled_hook('retailcrm_history');
            wp_clear_scheduled_hook('retailcrm_inventories');
            wp_clear_scheduled_hook('retailcrm_loyalty_upload_price');

            //Add new cron tasks
            $this->retailcrm_api_sanitized($this->settings);
        }

        /**
         * Generate ICML
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_generate_icml()
        {
            WC_Retailcrm_Logger::setHook(current_action());

            $this->accessCheck('woo-retailcrm-admin-nonce');

            /*
             * A temporary solution.
             * We have rebranded the module and changed the name of the ICML file.
             * This solution checks the url specified to the ICML file and updates it if necessary.
             */

            if (!$this->apiClient instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $codeSite   = '';
            $infoApiKey = $this->apiClient->credentials();

            if (
                $infoApiKey->isSuccessful()
                && !empty($infoApiKey['siteAccess'])
                && !empty($infoApiKey['sitesAvailable'])
                && $infoApiKey['siteAccess'] === 'access_selective'
                && count($infoApiKey['sitesAvailable']) === 1
            ) {
                $codeSite = $infoApiKey['sitesAvailable'][0];
            }

            if (!empty($codeSite)) {
                $getSites = $this->apiClient->sitesList();

                if ($getSites->isSuccessful() && !empty($getSites['sites'][$codeSite])) {
                    $dataSite = $getSites['sites'][$codeSite];

                    if (!empty($dataSite['ymlUrl'])) {
                        $ymlUrl = $dataSite['ymlUrl'];

                        if (strpos($ymlUrl, 'simla') === false) {
                            $ymlUrl = str_replace('/retailcrm.xml', '/simla.xml', $ymlUrl);
                            $dataSite['ymlUrl'] = $ymlUrl;

                            $this->apiClient->sitesEdit($dataSite);
                        }
                    }
                }
            }

            $retailCrmIcml = new WC_Retailcrm_Icml();

            // Generate new ICML catalog, because change bind_by_sku
            if (isset($_POST['useXmlId'])) {
                $retailCrmIcml->changeBindBySku(wp_unslash($_POST['useXmlId']));
            }

            $retailCrmIcml->generate();

            $this->uploadCatalog($infoApiKey);
        }

        /**
         * Add task for automatically upload catalog in CRM
         */
        private function uploadCatalog($infoApiKey)
        {
            WC_Retailcrm_Logger::info(__METHOD__, 'Add task for automatically upload catalog in CRM');

            if ($infoApiKey->isSuccessful() && !empty($infoApiKey['scopes'])) {
                if (!in_array('analytics_write', $infoApiKey['scopes'])) {
                    WC_Retailcrm_Logger::error(
                        __METHOD__,
                        'To automatically load the catalog in CRM, you need to enable analytics_write for the API key'
                    );

                    return;
                }

                $statisticUpdate = $this->apiClient->statisticUpdate();

                if ($statisticUpdate->isSuccessful()) {
                    WC_Retailcrm_Logger::info(
                        __METHOD__,
                        'Catalog generated, upload task added to CRM'
                    );
                } else {
                    WC_Retailcrm_Logger::error(
                        __METHOD__,
                        $statisticUpdate['errorMsg'] ?? 'Unrecognized error when adding catalog upload task to CRM'
                    );
                }
            }
        }

        public function retailcrm_upload_loyalty_price()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            if (!$this->apiClient instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $discountPriceUpload = new WC_Retailcrm_Upload_Discount_Price($this->apiClient);
            $discountPriceUpload->upload();
        }

        /**
         * Get history
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_history_get()
        {
            WC_Retailcrm_Logger::setHook(current_action());
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
            WC_Retailcrm_Logger::setHook(current_action(), $order_id);
            $this->orders->orderCreate($order_id);
        }

        /**
         * Load stock from retailCRM
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_load_stocks()
        {
            WC_Retailcrm_Logger::setHook(current_action());
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
        public function retailcrm_upload_selected_orders()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            WC_Retailcrm_Logger::setHook(current_action());
            $this->uploader->uploadSelectedOrders();
        }

        /**
         * Upload archive customers and order to retailCRM
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_upload_to_crm()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            WC_Retailcrm_Logger::setHook(current_action());
            $page = filter_input(INPUT_POST, 'Step', FILTER_SANITIZE_NUMBER_INT);
            $entity = filter_input(INPUT_POST, 'Entity', FILTER_SANITIZE_STRING);

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
        public function retailcrm_create_customer($customerId)
        {
            WC_Retailcrm_Logger::setHook(current_action(), $customerId);

            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            if (empty($customerId)) {
                WC_Retailcrm_Logger::error(__METHOD__, 'Error: Customer externalId is empty');

                return;
            }

            $post = $this->get_post_data();
            $this->customers->isSubscribed = !empty($post['subscribe']);

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
        public function retailcrm_update_customer($customerId)
        {
            WC_Retailcrm_Logger::setHook(current_action(), $customerId);

            if (WC_Retailcrm_Plugin::history_running() === true) {
                WC_Retailcrm_Logger::info(__METHOD__, 'History in progress, skip');

                return;
            }

            if (empty($customerId)) {
                WC_Retailcrm_Logger::error(__METHOD__, 'Error: Customer externalId is empty');

                return;
            }

            $this->customers->updateCustomer($customerId);
        }

        public function retailcrm_fill_array_create_orders($order_id)
        {
            WC_Retailcrm_Logger::setHook(current_action(), $order_id);

            if (WC_Retailcrm_Plugin::history_running() === true) {
                WC_Retailcrm_Logger::info(__METHOD__, 'History in progress, skip');

                return;
            }

            $this->createdOrderId[$order_id] = $order_id;
        }

        /**
         * Create order in RetailCRM from admin panel
         *
         * @codeCoverageIgnore Check in another tests
         *
         * @param int $order_id
         */
        public function retailcrm_create_order()
        {
            WC_Retailcrm_Logger::setHook(current_action());

            if (did_action('woocommerce_new_order') === 0) {
                return;
            }

            if (did_action('woocommerce_checkout_order_processed')) {
                WC_Retailcrm_Logger::info(
                    __METHOD__,
                    'There was a hook woocommerce_checkout_order_processed'
                );

                return;
            }

            $logText = 'Creation order';

            if (is_admin()) {
                $logText = 'Creation order from admin panel';
            }

            foreach ($this->createdOrderId as $orderId) {
                WC_Retailcrm_Logger::info(__METHOD__, sprintf('%1$s (%2$s)', $logText, $orderId));
                $this->retailcrm_process_order($orderId);
            }
        }

        /**
         * Create and update cart in CRM
         *
         * @codeCoverageIgnore Check in another tests
         *
         * @return void
         */
        public function retailcrm_set_cart()
        {
            global $woocommerce;

            WC_Retailcrm_Logger::setHook(current_action());

            try {
                $site = $this->apiClient->getSingleSiteForKey();
                $cartItems = $woocommerce->cart->get_cart();
                $customerId = $woocommerce->customer->get_id();

                if (empty($site)) {
                    WC_Retailcrm_Logger::error(
                        __METHOD__,
                        'Error with CRM credentials: need an valid apiKey assigned to one certain site'
                    );
                } elseif (empty($customerId)) {
                    WC_Retailcrm_Logger::error(
                        __METHOD__,
                        'Abandoned carts work only for registered customers'
                    );
                } else {
                    $isCartExist = $this->cart->isCartExist($customerId, $site);
                    $isSuccessful = $this->cart->processCart($customerId, $cartItems, $site, $isCartExist);

                    if ($isSuccessful) {
                        WC_Retailcrm_Logger::info(
                            __METHOD__,
                            'Cart for customer ID: ' . $customerId . ' processed. Hook: ' . current_filter()
                        );
                    } else {
                        WC_Retailcrm_Logger::error(
                            __METHOD__,
                            'Cart for customer ID: ' . $customerId . ' not processed. Hook: ' . current_filter()
                        );
                    }
                }
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
            }
        }

        /**
         * Clear the cart in CRM for 2 cases:
         * 1. Delete all items from the basket;
         * 2. Create an order, items from the cart are automatically deleted.
         *
         * The hook is called 3 times.
         *
         * @codeCoverageIgnore Check in another tests
         *
         * @return void
         */
        public function retailcrm_clear_cart()
        {
            global $woocommerce;

            WC_Retailcrm_Logger::setHook(current_action());

            try {
                $site = $this->apiClient->getSingleSiteForKey();
                $customerId = $woocommerce->customer->get_id();

                if (empty($site)) {
                    WC_Retailcrm_Logger::info(
                        __METHOD__,
                        'Error with CRM credentials: need an valid apiKey assigned to one certain site'
                    );
                } elseif (empty($customerId)) {
                    WC_Retailcrm_Logger::info(
                        __METHOD__,
                        'Abandoned carts work only for registered customers'
                    );
                } else {
                    $isCartExist = $this->cart->isCartExist($customerId, $site);
                    $isSuccessful = $this->cart->clearCart($customerId, $site, $isCartExist);

                    if ($isSuccessful) {
                        WC_Retailcrm_Logger::info(
                            __METHOD__,
                            'Cart for customer ID: ' . $customerId . ' cleared. Hook: ' . current_filter()
                        );
                    } elseif ($isCartExist) {
                        WC_Retailcrm_Logger::info(
                            __METHOD__,
                            'Cart for customer ID: ' . $customerId . ' not cleared. Hook: ' . current_filter()
                        );
                    }
                }
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
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
        public function retailcrm_fill_array_update_orders($order_id)
        {
            WC_Retailcrm_Logger::setHook(current_action(), $order_id);

            if (
                WC_Retailcrm_Plugin::history_running() === true
                || did_action('woocommerce_checkout_order_processed')
                || did_action('woocommerce_new_order')
            ) {
                WC_Retailcrm_Logger::info(
                    __METHOD__,
                    'History in progress or already did actions (woocommerce_checkout_order_processed;woocommerce_new_order), skip'
                );

                return;
            }

            $this->updatedOrderId[$order_id] = $order_id;
        }

        public function retailcrm_update_order()
        {
            WC_Retailcrm_Logger::setHook(current_action());

            foreach ($this->updatedOrderId as $orderId) {
                if (!isset($this->createdOrderId[$orderId])) {
                    $this->orders->updateOrder($orderId);
                }
            }
        }

        public function retailcrm_update_order_items($orderId)
        {
            if (is_admin()) {
                WC_Retailcrm_Logger::setHook(current_action(), $orderId);
                $this->orders->updateOrder($orderId);
            }
        }

        public function retailcrm_trash_order_action($id)
        {
            if ('shop_order' == get_post_type($id)) {
                WC_Retailcrm_Logger::setHook(current_action(), $id);
                $this->orders->updateOrder($id, true);
            }
        }

        /**
         * Init google analytics code
         */
        public function retailcrm_initialize_analytics()
        {
            if ($this->get_option('ua') && $this->get_option('ua_code')) {
                $retailcrm_analytics = WC_Retailcrm_Google_Analytics::getInstance($this->settings);

                echo wp_kses($retailcrm_analytics->initialize_analytics(), ['script' => ['type' => []]]);
            } else {
                echo '';
            }
        }

        /**
         * Google analytics send code
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_send_analytics()
        {
            if ($this->get_option('ua') == static::YES && $this->get_option('ua_code') && is_checkout()) {
                $retailcrm_analytics = WC_Retailcrm_Google_Analytics::getInstance($this->settings);

                echo wp_kses($retailcrm_analytics->send_analytics(), ['script' => ['type' => []]]);
            } else {
                echo '';
            }
        }

        /**
         * Daemon collector
         *
         * @codeCoverageIgnore Check in another tests
         */
        public function retailcrm_initialize_daemon_collector()
        {
            if ($this->get_option('daemon_collector') == static::YES && $this->get_option('daemon_collector_key')) {
                $retailcrm_daemon_collector = WC_Retailcrm_Daemon_Collector::getInstance($this->settings);

                echo wp_kses($retailcrm_daemon_collector->initialize_daemon_collector(), ['script' => ['type' => []]]);
            } else {
                echo '';
            }
        }

        /**
         * Initialize online consultant
         */
        public function retailcrm_initialize_online_assistant()
        {
            if (!is_admin() && !is_wplogin()) {
                echo wp_kses($this->get_option('online_assistant'), ['script' => ['type' => []]]);
            }
        }

        /**
         * In this method we include files in admin WP
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        public function retailcrm_include_files_for_admin()
        {
            $this->include_css_files_for_admin();
            $this->include_js_scripts_for_admin();
        }

        public function retailcrm_get_status_coupon()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            $coupon_settings_url = admin_url('admin.php?page=wc-settings');

            echo wp_json_encode(
                [
                    'coupon_status' => get_option('woocommerce_enable_coupons'),
                    'translate' => [
                        'coupon_warning' => sprintf(
                            /* translators: %s: HTML link to coupon settings page. */
                            __( 'To activate the loyalty program it is necessary to activate the %s.', 'woo-retailcrm'),
                            '<a href="' . esc_url($coupon_settings_url) . '">' . esc_html__( 'Enable use of coupons option', 'woo-retailcrm' ) . '</a>'
                        )
                    ]
                ]);

            wp_die();
        }

        public function retailcrm_register_customer_loyalty()
        {
            $this->accessCheck('woo-retailcrm-loyalty-actions-nonce', false);

            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_NUMBER_INT);
            $site = $this->apiClient->getSingleSiteForKey();
            $isSuccessful = false;

            if (!empty($site) && $userId && $phone) {
                $isSuccessful = $this->loyalty->registerCustomer($userId, $phone, $site);
            }

            if (!$isSuccessful) {
                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    sprintf(
                        'Errors when registering a loyalty program. Passed parameters: %s',
                        wp_json_encode(['site' => $site, 'userId' => $userId, 'phone' => $phone])
                    )
                );
                echo wp_json_encode(['error' => esc_html__('Error while registering in the loyalty program. Try again later', 'woo-retailcrm')]);
            } else {
                echo wp_json_encode(['isSuccessful' => true]);
            }

            wp_die();
        }

        public function retailcrm_activate_customer_loyalty()
        {
            $this->accessCheck('woo-retailcrm-loyalty-actions-nonce', false);

            $loyaltyId = filter_input(INPUT_POST, 'loyaltyId', FILTER_SANITIZE_NUMBER_INT);
            $isSuccessful = false;

            if ($loyaltyId) {
                $isSuccessful = $this->loyalty->activateLoyaltyCustomer($loyaltyId);
            }

            if (!$isSuccessful) {
                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    'Errors when activate loyalty program. Passed parameters: ' . wp_json_encode(['loyaltyId' => $loyaltyId])
                );
                echo wp_json_encode(['error' => esc_html__('Error when activating the loyalty program. Try again later', 'woo-retailcrm')]);
            } else {
                echo wp_json_encode(['isSuccessful' => true]);
            }

            wp_die();
        }

        public function retailcrm_coupon_info()
        {
            WC_Retailcrm_Logger::setHook(current_action());

            try {
                $result = $this->loyalty->createLoyaltyCoupon();

                if ($result) {
                    echo wp_kses($result, [
                        'div' => [
                            'style' => true,
                            'id'    => true,
                            'onclick' => true,
                        ],
                        'b'   => [],
                        'i'   => ['style' => true],
                        'u'   => [],
                    ]);
                }

                $jsScriptPath = plugins_url() . self::ASSETS_DIR . '/js/retailcrm-loyalty-cart.js';

                wp_register_script('retailcrm-loyalty-cart', $jsScriptPath, false, filemtime($jsScriptPath), true);
                wp_enqueue_script('retailcrm-loyalty-cart', $jsScriptPath, '', filemtime($jsScriptPath), true);
                wp_localize_script('retailcrm-loyalty-cart', 'RetailcrmAdminCoupon', [
                    'url' => get_admin_url(),
                    'nonce' => wp_create_nonce('woo-retailcrm-coupon-info-nonce')
                ]);
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
            }
        }

        public function retailcrm_refresh_loyalty_coupon()
        {
            WC_Retailcrm_Logger::setHook(current_action());

            try {
                $this->loyalty->createLoyaltyCoupon(true);
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
            }
        }

        public function retailcrm_clear_loyalty_coupon()
        {
            WC_Retailcrm_Logger::setHook(current_action());

            try {
                $this->loyalty->clearLoyaltyCoupon();
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
            }
        }

        public function retailcrm_remove_coupon($couponCode)
        {
            WC_Retailcrm_Logger::setHook(current_action());

            try {
                if (!$this->loyalty->deleteLoyaltyCoupon($couponCode)) {
                    $this->loyalty->createLoyaltyCoupon(true);
                }
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
            }
        }

        public function retailcrm_apply_coupon($couponCode)
        {
            WC_Retailcrm_Logger::setHook(current_action());

            try {
                if (!$this->loyalty->isLoyaltyCoupon($couponCode)) {
                    $this->loyalty->createLoyaltyCoupon(true);
                }
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);
            }
        }

        public function retailcrm_reviewCreditBonus()
        {
            WC_Retailcrm_Logger::setHook(current_action());
            $resultHtml = $this->loyalty->getCreditBonuses();

            if ($resultHtml) {
                echo wp_kses($resultHtml, ['b' => ['style' => true], 'u' => ['style' => true], 'i' => ['style' => true]]);
            }
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
            $path =  plugins_url() . self::ASSETS_DIR . '/css/';

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
            $jsScripts = [
                'retailcrm-export',
                'retailcrm-cron-info',
                'retailcrm-meta-fields',
                'retailcrm-module-settings',
                'retailcrm-loyalty',
                'retailcrm-tracker-interface',
            ];

            $jsScriptsPath =  plugins_url() . self::ASSETS_DIR . '/js/';

            foreach ($jsScripts as $scriptName) {
                $scriptDir = $jsScriptsPath . $scriptName . '.js';

                wp_register_script($scriptName, $scriptDir, false, filemtime($scriptDir), true);
                wp_enqueue_script($scriptName, $scriptDir, '', filemtime($scriptDir), true);
            }

            // In this method transfer wp-admin url in JS scripts.
            wp_localize_script($scriptName, 'RetailcrmAdmin', [
                'url' => get_admin_url(),
                'nonce' => wp_create_nonce('woo-retailcrm-admin-nonce')
            ]);

            $this->include_js_translates_for_tracker();
        }

        public function retailcrm_include_js_script_for_tracker()
        {
            $scriptName = 'retailcrm-tracker';
            $jsScriptsPath = plugins_url() . self::ASSETS_DIR . '/js/' . $scriptName . '.js';

            wp_register_script($scriptName, $jsScriptsPath, false, filemtime($jsScriptsPath), true);
            wp_enqueue_script($scriptName, $jsScriptsPath, '', filemtime($jsScriptsPath), true);
            wp_localize_script($scriptName, 'RetailcrmTracker', ['url' => get_admin_url()]);
        }

        public function include_js_translates_for_tracker()
        {
            $translations = [
                'tracker_activity' => esc_html__('Activate event tracking', 'woo-retailcrm'),
                'page_view' => esc_html__('Page View', 'woo-retailcrm'),
                'cart' => esc_html__('Cart', 'woo-retailcrm'),
                'open_cart' => esc_html__('Open Cart', 'woo-retailcrm'),
                'page_view_desc' => esc_html__('Tracks user page views', 'woo-retailcrm'),
                'cart_desc' => esc_html__('Tracks changes in the cart (adding/removing items)', 'woo-retailcrm'),
                'open_cart_desc' => esc_html__('Tracks when the user opens the cart', 'woo-retailcrm'),
            ];

            wp_localize_script('retailcrm-tracker-interface', 'retailcrm_localized', $translations);
        }

        /**
         * Include style for WhatsApp icon
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        public function retailcrm_include_whatsapp_icon_style()
        {
            wp_register_style('whatsapp_icon_style', plugins_url() . self::ASSETS_DIR . '/css/whatsapp-icon.min.css', false, '0.1');
            wp_enqueue_style('whatsapp_icon_style');
        }

        /**
         * Initialize WhatsApp
         */
        public function retailcrm_initialize_whatsapp()
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

                $allowed_tags = [
                    'div' => ['class' => true],
                    'a' => ['href' => true, 'target' => true, 'class' => true],
                    'p' => ['class' => true],
                    'svg' => ['class' => true, 'fill' => true, 'viewbox' => true, 'xmlns' => true],
                    'path' => ['d' => true, 'fill' => true],
                    'br' => [],
                ];

                echo wp_kses(sprintf($whatsAppHtml, $positionIcon, $phoneNumber), $allowed_tags);
            }
        }

        /**
         * Return count upload data
         */
        public function retailcrm_count_upload_data()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            $translate = [
                'tr_order'       => esc_html__('Orders', 'woo-retailcrm'),
                'tr_customer'    => esc_html__('Customers', 'woo-retailcrm'),
                'tr_empty_field' => esc_html__('The field cannot be empty, enter the order ID', 'woo-retailcrm'),
                'tr_successful'  => esc_html__('Orders were uploaded', 'woo-retailcrm'),
            ];

            echo wp_json_encode(
                [
                    'count_orders' => $this->uploader->getCountOrders(),
                    'count_users'  => $this->uploader->getCountUsers(),
                    'translate'    => $translate,
                ]
            );

            wp_die();
        }

        /**
         * Return time work next cron
         */
        public function retailcrm_get_cron_info()
        {
            $this->accessCheck('woo-retailcrm-admin-nonce');

            $defaultValue = esc_html__('This option is disabled', 'woo-retailcrm');
            $icml         = $defaultValue;
            $history      = $defaultValue;
            $inventories  = $defaultValue;
            $loyaltyUploadPrice = $defaultValue;

            $translate    = [
                'tr_td_cron'        => esc_html__('Cron launches', 'woo-retailcrm'),
                'tr_td_icml'        => esc_html__('Generation ICML', 'woo-retailcrm'),
                'tr_td_history'     => esc_html__('Syncing history', 'woo-retailcrm'),
                'tr_successful'     => esc_html__('Cron tasks cleared', 'woo-retailcrm'),
                'tr_td_inventories' => esc_html__('Syncing inventories', 'woo-retailcrm'),
                'tr_td_loyaltyUploadPrice' => esc_html__('Unloading promotional prices of offers', 'woo-retailcrm')
            ];

            if (isset($this->settings['history']) && $this->settings['history'] == static::YES) {
                $history = gmdate('H:i:s d-m-Y', wp_next_scheduled('retailcrm_history'));
            }

            if (isset($this->settings['icml']) && $this->settings['icml'] == static::YES) {
                $icml = gmdate('H:i:s d-m-Y', wp_next_scheduled('retailcrm_icml'));

                if (isset($this->settings['loyalty']) && $this->settings['loyalty'] === static::YES) {
                    $loyaltyUploadPrice = gmdate('H:i:s d-m-Y', wp_next_scheduled('retailcrm_loyalty_upload_price'));
                }
            }

            if (isset($this->settings['sync']) && $this->settings['sync'] == static::YES) {
                $inventories = gmdate('H:i:s d-m-Y ', wp_next_scheduled('retailcrm_inventories'));
            }

            echo wp_json_encode(
                [
                    'history'     => $history,
                    'icml'        => $icml,
                    'inventories' => $inventories,
                    'translate'   => $translate,
                    'loyaltyUploadPrice' => $loyaltyUploadPrice
                ]
            );

            wp_die();
        }

        /**
         * Set meta fields in settings
         */
        public function retailcrm_set_meta_fields()
        {
            if (!$this->apiClient instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $this->accessCheck('woo-retailcrm-admin-nonce');

            WC_Retailcrm_Logger::setHook(current_action());

            $orderMetaData        = $this->getMetaData('order');
            $customerMetaData     = $this->getMetaData('user');
            $orderCustomFields    = $this->getCustomFields('order');
            $customerCustomFields = $this->getCustomFields('customer');
            $defaultCrmOrderFields = $this->getDefaultCrmOrderFields();
            $defaultCrmCustomerFields = $this->getDefaultCrmCustomerFields();

            $translate = [
                'tr_lb_order'    => esc_html__('Custom fields for order', 'woo-retailcrm'),
                'tr_lb_customer' => esc_html__('Custom fields for customer', 'woo-retailcrm'),
                'tr_btn'         => esc_html__('Add new select for order', 'woo-retailcrm'),
            ];

            echo wp_json_encode(
                [
                    'order' => [
                        'meta' => $orderMetaData,
                        'custom' => $orderCustomFields,
                        'crmDefault' => $defaultCrmOrderFields,
                        'tr_default_crm_fields' => esc_html__('Standard CRM fields', 'woo-retailcrm'),
                    ],
                    'customer' => [
                        'meta' => $customerMetaData,
                        'custom' => $customerCustomFields,
                        'crmDefault' => $defaultCrmCustomerFields,
                        'tr_default_crm_fields' => esc_html__('Standard CRM fields', 'woo-retailcrm'),
                    ],
                    'translate' => $translate,
                ]
            );

            wp_die();
        }

        public function retailcrm_add_loyalty_item($items)
        {
            WC_Retailcrm_Logger::setHook(current_action());
            $items['loyalty'] = esc_html__('Loyalty program', 'woo-retailcrm');

            return $items;
        }

        public function retailcrm_add_loyalty_endpoint()
        {
            add_rewrite_endpoint('loyalty', EP_PAGES);
        }

        public function retailcrm_show_loyalty()
        {
            $userId = get_current_user_id();

            if (!isset($userId)) {
                return;
            }

            WC_Retailcrm_Logger::setHook(current_action());

            $jsScript = 'retailcrm-loyalty-actions';
            $loyaltyUrl = ['url' => get_admin_url()];
            $jsScriptsPath =  plugins_url() . self::ASSETS_DIR . '/js/';
            $cssPath = plugins_url() . self::ASSETS_DIR . '/css/';
            $messagePhone = esc_html__('Enter the correct phone number', 'woo-retailcrm');

            $loyaltyTemrs = $this->settings['loyalty_terms'] ?? '';
            $loyaltyPersonal = $this->settings['loyalty_personal'] ?? '';

            $scriptDir = $jsScriptsPath . $jsScript . '.js';

            wp_register_script($jsScript, $scriptDir, false, filemtime($scriptDir), true);
            wp_enqueue_script($jsScript, $scriptDir, '', filemtime($scriptDir), true);
            wp_localize_script($jsScript, 'retailcrmLoyaltyUrl', $loyaltyUrl);
            wp_localize_script($jsScript, 'retailcrmCustomerId', $userId);
            wp_localize_script($jsScript, 'retailcrmMessagePhone', $messagePhone);
            wp_localize_script($jsScript, 'retailcrmTermsLoyalty', $loyaltyTemrs);
            wp_localize_script($jsScript, 'retailcrmPrivacyLoyalty',  $loyaltyPersonal);
            wp_localize_script($jsScript, 'retailcrmNonce', wp_create_nonce('woo-retailcrm-loyalty-actions-nonce'));
            wp_register_style('retailcrm-loyalty-style', $cssPath . 'retailcrm-loyalty-style.css', false, '0.1');
            wp_enqueue_style('retailcrm-loyalty-style');

            $result = $this->loyalty->getForm($userId, $loyaltyTemrs, $loyaltyPersonal);

            if ([] === $result) {
                echo wp_kses('<p style="color: red">'. esc_html__('Error while retrieving data. Try again later', 'woo-retailcrm') . '</p>', ['p' => ['style' => true],]);
            } else {
                wp_localize_script($jsScript, 'retailcrmLoyaltyId', $result['loyaltyId'] ?? null);
                $allowed_tags = [
                    'form' => ['id' => true, 'method' => true],
                    'input' => ['type' => true, 'id' => true, 'name' => true, 'value' => true, 'placeholder' => true, 'required' => true],
                    'p' => ['style' => true],
                    'b' => ['style' => true],
                    'u' => ['style' => true],
                    'i' => ['style' => true],
                    'a' => ['id' => true, 'class' => true, 'href' => true, 'target' => true],
                    'div' => ['id' => true, 'class' => true],
                    'br' => [],
                    'table' => ['style' => true, 'border' => true],
                    'tbody' => ['style' => true],
                    'tr' => ['style' => true],
                    'td' => ['style' => true]
                ];

                echo wp_kses($result['form'], $allowed_tags);
            }
        }


        /**
         * Get custom fields with CRM
         *
         * @return array
         */
        private function getCustomFields($entity)
        {
            $customFields    = ['default_retailcrm' => esc_html__('Select value', 'woo-retailcrm')];
            $getCustomFields = $this->apiClient->customFieldsList(['entity' => $entity], 100);

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

            if ('user' === $entity) {
                $table = $wpdb->usermeta;
            } else {
                $table = useHpos() ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->postmeta;
            }

            $metaData = ['default_retailcrm' => esc_html__('Select value', 'woo-retailcrm')];
            $defaultMetaFields = file(
                WP_PLUGIN_DIR . self::ASSETS_DIR . '/default/default_meta_fields.txt',
                FILE_IGNORE_NEW_LINES
            );

            foreach ($wpdb->get_results("SELECT DISTINCT `meta_key` FROM $table ORDER BY `meta_key`") as $metaValue) {
                $metaData[$metaValue->meta_key] = $metaValue->meta_key;
            }

            $defaultMetaFields = apply_filters('retailcrm_change_default_meta_fields', $defaultMetaFields);

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
        public function retailcrm_deactivate()
        {
            WC_Retailcrm_Logger::setHook(current_action());
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

        private function getDefaultCrmOrderFields()
        {
            return [
                'default-crm-field#firstName' => esc_html__('firstName', 'woo-retailcrm'),
                'default-crm-field#lastName' => esc_html__('lastName', 'woo-retailcrm'),
                'default-crm-field#phone' => esc_html__('phone', 'woo-retailcrm'),
                'default-crm-field#email' => esc_html__('email', 'woo-retailcrm'),
                'default-crm-field#delivery#address#index' => esc_html__('addressIndex', 'woo-retailcrm'),
                'default-crm-field#delivery#address#region' => esc_html__('addressRegion', 'woo-retailcrm'),
                'default-crm-field#delivery#address#city' => esc_html__('addressCity', 'woo-retailcrm'),
                'default-crm-field#delivery#address#text' => esc_html__('addressText', 'woo-retailcrm'),
                'default-crm-field#customerComment' => esc_html__('customerComment', 'woo-retailcrm'),
                'default-crm-field#managerComment' => esc_html__('managerComment', 'woo-retailcrm'),
            ];
        }

        private function getDefaultCrmCustomerFields()
        {
            return [
                'default-crm-field#firstName' => esc_html__('firstName', 'woo-retailcrm'),
                'default-crm-field#lastName' => esc_html__('lastName', 'woo-retailcrm'),
                'default-crm-field#phones' => esc_html__('phone', 'woo-retailcrm'),
                'default-crm-field#email' => esc_html__('email', 'woo-retailcrm'),
                'default-crm-field#address#index' => esc_html__('addressIndex', 'woo-retailcrm'),
                'default-crm-field#address#region' => esc_html__('addressRegion', 'woo-retailcrm'),
                'default-crm-field#address#city' => esc_html__('addressCity', 'woo-retailcrm'),
                'default-crm-field#address#text' => esc_html__('addressText', 'woo-retailcrm'),
                'default-crm-field#tags' => esc_html__('tags', 'woo-retailcrm'),
            ];
        }

        private function activateModule()
        {
            $clientId = get_option('retailcrm_client_id');
            $isActive = get_option('retailcrm_active_in_crm');

            if ($this->apiClient && $clientId && !$isActive) {
                WC_Retailcrm_Plugin::integration_module($this->apiClient, $clientId);
                update_option('retailcrm_active_in_crm', true);
            }
        }

        public function add_retailcrm_tracking_script() {
            $trackerSettings = isset($this->settings['tracker_settings']) ? json_decode($this->settings['tracker_settings'], true) : null;
            
            if (isset($trackerSettings['tracker_enabled']) && $trackerSettings['tracker_enabled'] === true) {
                $trackedEvents = $trackerSettings['tracked_events'];
                $isPageView = in_array('page_view', $trackedEvents) ? 'page_view' : null;
                $isCart = in_array('cart', $trackedEvents) ? 'cart' : null;
                $isCartOpen = in_array('open_cart', $trackedEvents) ? 'open_cart' : null;

                if (count($trackedEvents) > 0) {
                    ?>
                    <script>
                        jQuery(function() {
                            var pageView = <?php echo wp_json_encode($isPageView); ?>;
                            var cart = <?php echo wp_json_encode($isCart); ?>;
                            var openCart = <?php echo wp_json_encode($isCartOpen); ?>;

                            startTrack(pageView, openCart, cart);
                        });
                    </script>
                    <?php
                }
            }
        }

        private function accessCheck(string $prefixNonce, $checkPermissions = true): void
        {
            if (!wp_doing_ajax()) {
                return;
            }

            if (check_ajax_referer($prefixNonce, '_ajax_nonce', false) !== 1) {
                echo wp_json_encode(['error' => esc_html__('Token is not valid', 'woo-retailcrm')]);

                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    sprintf(
                        'Nonce token is missing or expired (%s)',
                        $prefixNonce
                    )
                );

                wp_die();
            }

            if ($checkPermissions && !(current_user_can('manage_woocommerce') || current_user_can('manage_options'))) {
                echo wp_json_encode(['error' => esc_html__('Access denied', 'woo-retailcrm')]);

                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    sprintf(
                        'User does not have permission to call method. User role: %s',
                        implode(', ', wp_get_current_user()->roles)
                    )
                );

                wp_die();
            }
        }
    }
}   
