<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Base
 * @category Integration
 * @author   RetailCRM
 */

if (!class_exists('WC_Retailcrm_Base')) {

    /**
     * Class WC_Retailcrm_Base
     */
    class WC_Retailcrm_Base extends WC_Integration {

        public static $option_key;

        protected $api_url;
        protected $api_key;

        private $apiClient;

        /**
         * Init and hook in the integration.
         * @param $retailcrm (default = false)
         */
        public function __construct($retailcrm = false) {
            //global $woocommerce;

            if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) {
                include_once( __DIR__ . '/api/class-wc-retailcrm-proxy.php' );
            }

            $this->id                 = 'integration-retailcrm';
            $this->method_title       = __('retailCRM', 'retailcrm');
            $this->method_description = __('Integration with retailCRM management system.', 'retailcrm');

            if ($retailcrm === false) {
                $this->apiClient = $this->getApiClient();
            } else {
                $this->apiClient = $retailcrm;
            }

            self::$option_key = $this->get_option_key();
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

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
            add_action('woocommerce_update_order', array($this, 'update_order'), 11, 1);
            add_action('wp_print_scripts', array($this, 'initialize_analytics'), 98);
            add_action('wp_print_footer_scripts', array($this, 'send_analytics'), 99);
        }

        public function api_sanitized($settings)
        {
            if (isset($settings['sync']) && $settings['sync'] == 'yes') {
                if (!wp_next_scheduled('retailcrm_inventories')) {
                    wp_schedule_event(time(), 'fiveteen_minutes', 'retailcrm_inventories');
                }
            } elseif (isset($settings['sync']) && $settings['sync'] == 'no') {
                wp_clear_scheduled_hook('retailcrm_inventories');
            }

            if (isset($settings['history']) && $settings['history'] == 'yes') {
                if (!wp_next_scheduled('retailcrm_history')) {
                    wp_schedule_event(time(), 'five_minutes', 'retailcrm_history');
                }
            } elseif (isset($settings['history']) && $settings['history'] == 'no') {
                wp_clear_scheduled_hook('retailcrm_history');
            }

            if (isset($settings['icml']) && $settings['icml'] == 'yes') {
                if (!wp_next_scheduled('retailcrm_icml')) {
                    wp_schedule_event(time(), 'three_hours', 'retailcrm_icml');
                }
            } elseif (isset($settings['icml']) && $settings['icml'] == 'no') {
                wp_clear_scheduled_hook('retailcrm_icml');
            }

            return $settings;
        }

        /**
         * Check custom file
         *
         * @param string $file
         *
         * @return string
         */
        public static function checkCustomFile($file) {
            if (file_exists( WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-' . $file . '.php' )) {
                return WP_CONTENT_DIR . '/retailcrm-custom/class-wc-retailcrm-' . $file . '.php';
            }

            return 'class-wc-retailcrm-' . $file . '.php';
        }

        public function generate_icml() {
            if (!class_exists('WC_Retailcrm_Icml')) {
                require_once (self::checkCustomFile('icml'));
            }

            $retailcrm_icml = new WC_Retailcrm_Icml();
            $retailcrm_icml->generate();
        }

        /**
         * Get history
         */
        public function retailcrm_history_get() {
            if (!class_exists('WC_Retailcrm_History')) {
                include_once(self::checkCustomFile('history'));
            }

            $retailcrm_history = new WC_Retailcrm_History($this->apiClient);
            $retailcrm_history->getHistory();
        }

        /**
         * @param int $order_id
         */
        public function retailcrm_process_order($order_id) {
            if (!class_exists('WC_Retailcrm_Orders')) {
                include_once(self::checkCustomFile('orders'));
            }

            $retailcm_order = new WC_Retailcrm_Orders($this->apiClient);
            $retailcm_order->orderCreate($order_id);
        }

        /**
         * Load stock from retailCRM
         */
        public function load_stocks() {
            if (!class_exists('WC_Retailcrm_Inventories')) {
                include_once(self::checkCustomFile('inventories'));
            }

            $inventories = new WC_Retailcrm_Inventories($this->apiClient);
            $inventories->updateQuantity();
        }

        /**
         * Upload selected orders
         */
        public function order_upload() {
            if (!class_exists('WC_Retailcrm_Orders')) {
                include_once(self::checkCustomFile('orders'));
            }

            $ids = false;

            if (isset($_GET['order_ids_retailcrm'])) {
                $ids = explode(',', $_GET['order_ids_retailcrm']);
            }

            $retailcm_order = new WC_Retailcrm_Orders($this->apiClient);

            if ($ids) {
                $retailcm_order->ordersUpload($ids, true);
            }
        }

        /**
         * Upload archive customers and order to retailCRM
         */
        public function upload_to_crm()
        {
            if (!class_exists('WC_Retailcrm_Orders')) {
                include_once(self::checkCustomFile('orders'));
            }

            if (!class_exists('WC_Retailcrm_Customers')) {
                include_once(self::checkCustomFile('customers'));
            }

            $options = array_filter(get_option(self::$option_key));

            $retailcrm_customers = new WC_Retailcrm_Customers($this->apiClient);
            $retailcrm_orders = new WC_Retailcrm_Orders($this->apiClient);

            $retailcrm_customers->customersUpload();
            $retailcrm_orders->ordersUpload();

            $options['uploads'] = 'yes';
            update_option(self::$option_key, $options);
        }

        public function ajax_upload()
        {
            $ajax_url = admin_url('admin-ajax.php');
            ?>
            <script type="text/javascript">
            jQuery('#uploads-retailcrm').bind('click', function() {
                jQuery.ajax({
                    type: "POST",
                    url: '<?php echo $ajax_url; ?>?action=do_upload',
                    success: function (response) {
                        alert('<?php echo __('Customers and orders were uploaded', 'retailcrm'); ?>');
                        console.log('AJAX response : ',response);
                    }
                });
            });
            </script>
            <?php
        }

        public function ajax_generate_icml()
        {
            $ajax_url = admin_url('admin-ajax.php');
            ?>
            <script type="text/javascript">
            jQuery('#icml-retailcrm, #wp-admin-bar-retailcrm_ajax_generate_icml').bind('click', function() {
                jQuery.ajax({
                    type: "POST",
                    url: '<?php echo $ajax_url; ?>?action=generate_icml',
                    success: function (response) {
                        alert('<?php echo __('Catalog was generated', 'retailcrm'); ?>');
                        console.log('AJAX response : ', response);
                    }
                });
            });
            </script>
            <?php
        }

        public function ajax_selected_order()
        {
            $ajax_url = admin_url('admin-ajax.php');
            $ids = $this->plugin_id . $this->id . '_single_order';
            ?>
            <script type="text/javascript">
            jQuery('#single_order_btn').bind('click', function() {
                if (jQuery('#<?php echo $ids; ?>').val() == '') {
                    alert('<?php echo __('The field cannot be empty, enter the order ID', 'retailcrm'); ?>');
                } else {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo $ajax_url; ?>?action=order_upload&order_ids_retailcrm=' + jQuery('#<?php echo $ids; ?>').val(),
                        success: function (response) {
                            alert('<?php echo __('Orders were uploaded', 'retailcrm'); ?>');
                        }
                    });
                }
            });
            </script>
            <?php
        }

        /**
         * Create customer in retailCRM
         * @param int $customer_id
         */
        public function create_customer($customer_id)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            if (!class_exists( 'WC_Retailcrm_Customers')) {
                include_once(self::checkCustomFile('customers'));
            }

            $retailcrm_customer = new WC_Retailcrm_Customers($this->apiClient);
            $retailcrm_customer->createCustomer($customer_id);
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

            if (!class_exists('WC_Retailcrm_Customers')) {
                include_once(self::checkCustomFile('customers'));
            }

            $retailcrm_customer = new WC_Retailcrm_Customers($this->apiClient);
            $retailcrm_customer->updateCustomer($customer_id);
        }

        /**
         * Edit order in retailCRM
         * @param int $order_id
         */
        public function update_order($order_id)
        {
            if (WC_Retailcrm_Plugin::history_running() === true) {
                return;
            }

            if (!class_exists('WC_Retailcrm_Orders')) {
                include_once(self::checkCustomFile('orders'));
            }

            $retailcrm_order = new WC_Retailcrm_Orders($this->apiClient);
            $retailcrm_order->updateOrder($order_id);
        }

        /**
         * Init google analytics code
         */
        public function initialize_analytics()
        {
            if (!class_exists('WC_Retailcrm_Google_Analytics')) {
                include_once(self::checkCustomFile('ga'));
            }

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
            if (!class_exists('WC_Retailcrm_Google_Analytics')) {
                include_once(self::checkCustomFile('ga'));
            }

            if ($this->get_option('ua') && $this->get_option('ua_code') && is_checkout()) {
                $retailcrm_analytics = WC_Retailcrm_Google_Analytics::getInstance($this->settings);
                echo $retailcrm_analytics->send_analytics();
            } else {
                echo '';
            }
        }

        /**
         * Initialize integration settings form fields.
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                array( 'title' => __( 'Main settings', 'retailcrm' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

                'api_url' => array(
                    'title'             => __( 'API of URL', 'retailcrm' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter API of URL (https://yourdomain.retailcrm.pro).', 'retailcrm' ),
                    'desc_tip'          => true,
                    'default'           => ''
                ),
                'api_key' => array(
                    'title'             => __( 'API key', 'retailcrm' ),
                    'type'              => 'text',
                    'description'       => __( 'Enter your API key. You can find it in the administration section of retailCRM', 'retailcrm' ),
                    'desc_tip'          => true,
                    'default'           => ''
                )
            );

            $api_version_list = array(
                'v4' => 'v4',
                'v5' => 'v5'
            );

            $this->form_fields[] = array(
                'title'       => __( 'API settings', 'retailcrm' ),
                'type'        => 'title',
                'description' => '',
                'id'          => 'api_options'
            );

            $this->form_fields['api_version'] = array(
                'title'       => __( 'API version', 'retailcrm' ),
                'description' => __( 'Select API version', 'retailcrm' ),
                'css'         => 'min-width:50px;',
                'class'       => 'select',
                'type'        => 'select',
                'options'     => $api_version_list,
                'desc_tip'    =>  true,
            );

            $this->form_fields[] = array(
                'title'       => __( 'Catalog settings', 'retailcrm' ),
                'type'        => 'title',
                'description' => '',
                'id'          => 'catalog_options'
            );

            foreach (get_post_statuses() as $status_key => $status_value) {
                $this->form_fields['p_' . $status_key] = array(
                    'title'       => $status_value,
                    'label'       => ' ',
                    'description' => '',
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'desc_tip'    =>  true,
                );
            }

            if ($this->apiClient) {
                if (isset($_GET['page']) && $_GET['page'] == 'wc-settings'
                    && isset($_GET['tab']) && $_GET['tab'] == 'integration'
                ) {
                    add_action('admin_print_footer_scripts', array($this, 'show_blocks'), 99);

                    /**
                     * Order methods options
                     */
                    $order_methods_option = array();
                    $order_methods_list = $this->apiClient->orderMethodsList();

                    if ($order_methods_list->isSuccessful()) {
                        foreach ($order_methods_list['orderMethods'] as $order_method) {
                            if ($order_method['active'] == false) {
                                continue;
                            }

                            $order_methods_option[$order_method['code']] = $order_method['name'];
                        }

                        $this->form_fields[] = array(
                            'title' => __('Order methods', 'retailcrm'),
                            'type' => 'heading',
                            'description' => '',
                            'id' => 'order_methods_options'
                        );

                        $this->form_fields['order_methods'] = array(
                            'label'       =>  ' ',
                            'title'       => __('Order methods available for uploading from retailCRM', 'retailcrm'),
                            'class'       => '',
                            'type'        => 'multiselect',
                            'description' => __('Select order methods which will be uploaded from retailCRM to the website', 'retailcrm'),
                            'options'     => $order_methods_option,
                            'css'         => 'min-height:100px;',
                            'select_buttons' => true
                        );
                    }

                    /**
                     * Shipping options
                     */
                    $shipping_option_list = array();
                    $retailcrm_shipping_list = $this->apiClient->deliveryTypesList();

                    if ($retailcrm_shipping_list->isSuccessful()) {
                        foreach ($retailcrm_shipping_list['deliveryTypes'] as $retailcrm_shipping_type) {
                            $shipping_option_list[$retailcrm_shipping_type['code']] = $retailcrm_shipping_type['name'];
                        }

                        $wc_shipping_list = get_wc_shipping_methods();

                        $this->form_fields[] = array(
                            'title' => __('Delivery types', 'retailcrm'),
                            'type' => 'heading',
                            'description' => '',
                            'id' => 'shipping_options'
                        );

                        foreach ($wc_shipping_list as  $shipping_code => $shipping) {
                            if (isset($shipping['enabled']) && $shipping['enabled'] == 'yes') {
                                $this->form_fields[$shipping_code] = array(
                                    'title'          => __($shipping['title'], 'woocommerce'),
                                    'description' => __($shipping['description'], 'woocommerce'),
                                    'css'            => 'min-width:350px;',
                                    'class'          => 'select',
                                    'type'           => 'select',
                                    'options'        => $shipping_option_list,
                                    'desc_tip'    =>  true,
                                );
                            }
                        }
                    }

                    /**
                     * Payment options
                     */
                    $payment_option_list = array();
                    $retailcrm_payment_list = $this->apiClient->paymentTypesList();

                    if ($retailcrm_payment_list->isSuccessful()) {
                        foreach ($retailcrm_payment_list['paymentTypes'] as $retailcrm_payment_type) {
                            $payment_option_list[$retailcrm_payment_type['code']] = $retailcrm_payment_type['name'];
                        }

                        $wc_payment = WC_Payment_Gateways::instance();

                        $this->form_fields[] = array(
                            'title' => __('Payment types', 'retailcrm'),
                            'type' => 'heading',
                            'description' => '',
                            'id' => 'payment_options'
                        );

                        foreach ($wc_payment->payment_gateways() as $payment) {
                            if (isset($payment->enabled) && $payment->enabled == 'yes') {
                                $this->form_fields[$payment->id] = array(
                                    'title'          => __($payment->method_title, 'woocommerce'),
                                    'description' => __($payment->method_description, 'woocommerce'),
                                    'css'            => 'min-width:350px;',
                                    'class'          => 'select',
                                    'type'           => 'select',
                                    'options'        => $payment_option_list,
                                    'desc_tip'    =>  true,
                                );
                            }
                        }
                    }

                    /**
                     * Statuses options
                     */
                    $statuses_option_list = array();
                    $retailcrm_statuses_list = $this->apiClient->statusesList();

                    if ($retailcrm_statuses_list->isSuccessful()) {
                        foreach ($retailcrm_statuses_list['statuses'] as $retailcrm_status) {
                            $statuses_option_list[$retailcrm_status['code']] = $retailcrm_status['name'];
                        }

                        $wc_statuses = wc_get_order_statuses();

                        $this->form_fields[] = array(
                            'title'       => __('Statuses', 'retailcrm'),
                            'type'        => 'heading',
                            'description' => '',
                            'id'          => 'statuses_options'
                        );

                        foreach ($wc_statuses as $idx => $name) {
                            $uid = str_replace('wc-', '', $idx);
                            $this->form_fields[$uid] = array(
                                'title'    => __($name, 'woocommerce'),
                                'css'      => 'min-width:350px;',
                                'class'    => 'select',
                                'type'     => 'select',
                                'options'  => $statuses_option_list,
                                'desc_tip' =>  true,
                            );
                        }
                    }

                    /**
                     * Inventories options
                     */
                    $this->form_fields[] = array(
                        'title'       => __('Setting of the stock balance', 'retailcrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'invent_options'
                    );

                    $this->form_fields['sync'] = array(
                        'label'       => __('Synchronization of the stock balance', 'retailcrm'),
                        'title'       => __('Stock balance', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox',
                        'description' => __('Enable this setting if you would like to get information on leftover stocks from retailCRM to the website.', 'retailcrm')
                    );

                    /**
                     * UA options
                     */
                    $this->form_fields[] = array(
                        'title'       => __('UA settings', 'retailcrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'invent_options'
                    );

                    $this->form_fields['ua'] = array(
                        'label'       => __('Activate UA', 'retailcrm'),
                        'title'       => __('UA', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox',
                        'description' => __('Enable this setting for uploading data to UA', 'retailcrm')
                    );

                    $this->form_fields['ua_code'] = array(
                        'title'       => __('UA tracking code', 'retailcrm'),
                        'class'       => 'input',
                        'type'        => 'input'
                    );

                    $this->form_fields['ua_custom'] = array(
                        'title'       => __('User parameter', 'retailcrm'),
                        'class'       => 'input',
                        'type'        => 'input'
                    );

                    /**
                     * Uploads options
                     */
                    $options = array_filter(get_option(self::$option_key));

                    if (!isset($options['uploads'])) {
                        $this->form_fields[] = array(
                            'title'       => __('Settings of uploading', 'retailcrm'),
                            'type'        => 'heading',
                            'description' => '',
                            'id'          => 'upload_options'
                        );

                        $this->form_fields['upload-button'] = array(
                            'label'             => __('Upload', 'retailcrm'),
                            'title'             => __('Uploading all customers and orders', 'retailcrm' ),
                            'type'              => 'button',
                            'description'       => __('Uploading the existing customers and orders to retailCRM', 'retailcrm' ),
                            'desc_tip'          => true,
                            'id'                => 'uploads-retailcrm'
                        );
                    }

                    /*
                     * Generate icml file
                     */
                    $this->form_fields[] = array(
                        'title'       => __('Generating ICML catalog', 'retailcrm'),
                        'type'        => 'title',
                        'description' => '',
                        'id'          => 'icml_options'
                    );

                    $this->form_fields[] = array(
                        'label'             => __('Generate now', 'retailcrm'),
                        'title'             => __('Generating ICML', 'retailcrm'),
                        'type'              => 'button',
                        'description'       => __('This functionality allows to generate ICML products catalog for uploading to retailCRM.', 'retailcrm'),
                        'desc_tip'          => true,
                        'id'                => 'icml-retailcrm'
                    );

                    $this->form_fields['icml'] = array(
                        'label'       => __('Generating ICML', 'retailcrm'),
                        'title'       => __('Generating ICML catalog by wp-cron', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                    );

                    /*
                     * Upload single order
                     */
                    $this->form_field[] = array(
                        'title'       => __('Upload the order by ID', 'retailcrm'),
                        'type'        => 'title',
                        'description' => '',
                        'id'          => 'order_options'
                    );

                    $this->form_fields['single_order'] = array(
                        'label'             => __('Order identifier', 'retailcrm'),
                        'title'             => __('Orders identifiers', 'retailcrm'),
                        'type'              => 'input',
                        'description'       => __('Enter orders identifiers separated by a comma.', 'retailcrm'),
                        'desc_tip'          => true
                    );

                    $this->form_fields[] = array(
                        'label'             => __('Upload', 'retailcrm'),
                        'title'             => __('Uploading orders by identifiers.', 'retailcrm'),
                        'type'              => 'button',
                        'description'       => __('This functionality allows to upload orders to CRM differentially.', 'retailcrm'),
                        'desc_tip'          => true,
                        'id'                => 'single_order_btn'
                    );

                     $this->form_fields['history'] = array(
                        'label'       => __('Activate history uploads', 'retailcrm'),
                        'title'       => __('Upload data from retailCRM', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                    );
                }
            }
        }

        /**
         * Generate html button
         *
         * @param string $key
         * @param array $data
         *
         * @return string
         */
        public function generate_button_html($key, $data)
        {
            $field    = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class'             => 'button-secondary',
                'css'               => '',
                'custom_attributes' => array(),
                'desc_tip'          => false,
                'description'       => '',
                'title'             => '',
            );

            $data = wp_parse_args( $data, $defaults );

            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html( $data ); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['label'] ); ?></span></legend>
                        <button id="<?php echo $data['id']; ?>" class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['label'] ); ?></button>
                        <?php echo $this->get_description_html( $data ); ?>
                    </fieldset>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        /**
         * Generate html title block settings
         *
         * @param string $key
         * @param array $data
         *
         * @return string
         */
        public function generate_heading_html($key, $data)
        {
            $field_key = $this->get_field_key( $key );
            $defaults  = array(
                'title' => '',
                'class' => '',
            );

            $data = wp_parse_args( $data, $defaults );

            ob_start();
            ?>
                </table>
                <h2 class="wc-settings-sub-title retailcrm_hidden <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <span style="opacity:0.5;float: right;">&#11015;</span></h2>
                <?php if ( ! empty( $data['description'] ) ) : ?>
                    <p><?php echo wp_kses_post( $data['description'] ); ?></p>
                <?php endif; ?>
                <table class="form-table" style="display: none;">
            <?php

            return ob_get_clean();
        }

        /**
         * Validate API version
         *
         * @param string $key
         * @param string $value
         *
         * @return string
         */
        public function validate_api_version_field($key, $value)
        {
            $post = $this->get_post_data();

            $versionMap = array(
                'v4' => '4.0',
                'v5' => '5.0'
            );

            $api = new WC_Retailcrm_Proxy(
                $post[$this->plugin_id . $this->id . '_api_url'],
                $post[$this->plugin_id . $this->id . '_api_key']
            );

            $response = $api->apiVersions();

            if ($response && $response->isSuccessful()) {
                if (!in_array($versionMap[$value], $response['versions'])) {
                    WC_Admin_Settings::add_error( esc_html__( 'The selected API version is unavailable', 'retailcrm' ) );
                    $value = '';
                }

                return $value;
            }
        }

        /**
         * Validate API url
         *
         * @param string $key
         * @param string $value
         *
         * @return string
         */
        public function validate_api_url_field($key, $value)
        {
            $post = $this->get_post_data();
            $api = new WC_Retailcrm_Proxy(
                $value,
                $post[$this->plugin_id . $this->id . '_api_key']
            );

            $response = $api->apiVersions();

            if ($response == null) {
                WC_Admin_Settings::add_error(esc_html__( 'Enter the correct URL of CRM', 'retailcrm'));
                $value = '';
            }

            return $value;
        }

        /**
         * Validate API key
         *
         * @param string $key
         * @param string $value
         *
         * @return string
         */
        public function validate_api_key_field($key, $value)
        {
            $post = $this->get_post_data();
            $api = new WC_Retailcrm_Proxy(
                $post[$this->plugin_id . $this->id . '_api_url'],
                $value
            );

            $response = $api->apiVersions();

            if (!is_object($response)) {
                $value = '';
            }

            if (!$response->isSuccessful()) {
                WC_Admin_Settings::add_error( esc_html__( 'Enter the correct API key', 'retailcrm' ) );
                $value = '';
            }

            return $value;
        }

        /**
         * Scritp show|hide block settings
         */
        function show_blocks()
        {
            ?>
            <script type="text/javascript">
                jQuery('h2.retailcrm_hidden').hover().css({
                    'cursor':'pointer',
                    'width':'310px'
                });
                jQuery('h2.retailcrm_hidden').toggle(
                    function() {
                        jQuery(this).next('table.form-table').show(100);
                        jQuery(this).find('span').html('&#11014;');
                    },
                    function() {
                        jQuery(this).next('table.form-table').hide(100);
                        jQuery(this).find('span').html('&#11015;');
                    }
                );
            </script>
            <?php
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
                    $this->get_option('api_version')
                );
            }

            return false;
        }
        /**
         * Add button in admin
         */
        function add_retailcrm_button() {
            global $wp_admin_bar;
            if ( !is_super_admin() || !is_admin_bar_showing() || !is_admin())
                return;

            $wp_admin_bar->add_menu(
                array(
                    'id' => 'retailcrm_top_menu',
                    'title' => __('retailCRM', 'retailcrm')
                )
            );
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'retailcrm_ajax_generate_icml',
                    'title' => __('Generating ICML catalog', 'retailcrm'),
                    'href' => '#',
                    'parent' => 'retailcrm_top_menu',
                    'class' => 'retailcrm_ajax_generate_icml'
                )
            );

            $wp_admin_bar->add_menu(
                array(
                    'id' => 'retailcrm_ajax_generate_setings',
                    'title' => __('Settings', 'retailcrm'),
                    'href'=> get_site_url().'/wp-admin/admin.php?page=wc-settings&tab=integration&section=integration-retailcrm',
                    'parent' => 'retailcrm_top_menu',
                    'class' => 'retailcrm_ajax_settings'
                )
            );
        }
    }
}
