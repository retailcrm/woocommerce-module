<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Abstracts_Settings - Settings plugin Simla.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
abstract class WC_Retailcrm_Abstracts_Settings extends WC_Integration
{
    /** @var string */
    const YES = 'yes';

    /** @var string */
    const NO = 'no';

    /** @var string */
    public static $option_key;

    /** @var WC_Retailcrm_Url_Validator*/
    private $urlValidator;

    /** @var string */
    private $crmUrl;

    /**
     * WC_Retailcrm_Abstracts_Settings constructor.
     */
    public function __construct()
    {
        $this->id                 = 'integration-retailcrm';
        $this->method_title       = __('Simla.com', 'retailcrm');
        $this->method_description = __('Integration with Simla.com management system', 'retailcrm');

        static::$option_key = $this->get_option_key();

        if (
            isset($_GET['page']) && $_GET['page'] == 'wc-settings'
            && isset($_GET['tab']) && $_GET['tab'] == 'integration'
        ) {
            add_action('init', [$this, 'init_settings_fields'], 99);
        }

        // Initialization validator
        $this->urlValidator = new WC_Retailcrm_Url_Validator();
    }

    /**
     * @codeCoverageIgnore
     */
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


    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            [ 'title' => __('Main settings', 'retailcrm'), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ],

            'api_url' => [
                'title'             => __('API of URL', 'retailcrm'),
                'type'              => 'text',
                'description'       => __('Enter API URL (https://yourdomain.simla.com)', 'retailcrm'),
                'desc_tip'          => true,
                'default'           => ''
            ],
            'api_key' => [
                'title'             => __('API key', 'retailcrm'),
                'type'              => 'text',
                'description'       => __('Enter your API key. You can find it in the administration section of Simla.com', 'retailcrm'),
                'desc_tip'          => true,
                'default'           => ''
            ]
        ];

        if ($this->apiClient) {
            if (
                isset($_GET['page']) && $_GET['page'] == 'wc-settings'
                && isset($_GET['tab']) && $_GET['tab'] == 'integration'
            ) {
                add_action('admin_print_footer_scripts', [$this, 'show_blocks'], 99);

                $this->form_fields[] = [
                    'title'       => __('API settings', 'retailcrm'),
                    'type'        => 'title',
                    'description' => '',
                    'id'          => 'api_options'
                ];

                $this->form_fields['online_assistant'] = [
                    'title'       => __('Online assistant', 'retailcrm'),
                    'type'        => 'textarea',
                    'id'          => 'online_assistant',
                    'placeholder' => __('Insert the Online consultant code here', 'retailcrm')
                ];

                $this->form_fields[] = [
                    'title'       => __('Catalog settings', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'catalog_options'
                ];

                $this->form_fields['product_description'] = [
                    'type'        => 'select',
                    'class'       => 'select',
                    'title'       => __('Product description', 'retailcrm'),
                    'options'     => [
                            'full'  => __('Full description', 'retailcrm'),
                            'short' => __('Short description', 'retailcrm'),
                            ],
                    'desc_tip'    => true,
                    'description' => __(
                        'In the catalog, you can use a full or short description of the product',
                        'retailcrm'
                    ),
                ];

                foreach (get_post_statuses() as $status_key => $status_value) {
                    $this->form_fields['p_' . $status_key] = [
                        'title'       => $status_value,
                        'label'       => ' ',
                        'description' => '',
                        'class'       => 'checkbox',
                        'type'        => 'checkbox',
                        'desc_tip'    =>  true,
                    ];
                }

                /**
                 * Order methods options
                 */
                $order_methods_option = [];
                $order_methods_list = $this->apiClient->orderMethodsList();

                if ($order_methods_list->isSuccessful() && !empty($order_methods_list['orderMethods'])) {
                    foreach ($order_methods_list['orderMethods'] as $order_method) {
                        if (!$order_method['active']) {
                            continue;
                        }

                        $order_methods_option[$order_method['code']] = $order_method['name'];
                    }

                    $this->form_fields[] = [
                        'title' => __('Order methods', 'retailcrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'order_methods_options'
                    ];

                    $this->form_fields['order_methods'] = [
                        'label'       =>  ' ',
                        'title'       => __('Order methods available for uploading from Simla.com', 'retailcrm'),
                        'class'       => '',
                        'type'        => 'multiselect',
                        'options'     => $order_methods_option,
                        'css'         => 'min-height:100px;',
                        'select_buttons' => true,
                        'description' => __(
                            'Select order methods which will be uploaded from Simla.com to the website',
                            'retailcrm'
                        ),
                    ];
                }

                $crmSite = $this->apiClient->getSingleSiteForKey();

                /**
                 * Delivery options
                 */
                $crmDeliveryList = $this->apiClient->deliveryTypesList();

                if ($crmDeliveryList instanceof WC_Retailcrm_Response && $crmDeliveryList->isSuccessful()) {
                    $shippingOptionList = [];

                    foreach ($crmDeliveryList['deliveryTypes'] as $crmDelivery) {
                        if ($crmDelivery['active'] === false) {
                            continue;
                        }

                        if (!empty($crmDelivery['sites']) && in_array($crmSite, $crmDelivery['sites']) === false) {
                            continue;
                        }

                        $shippingOptionList[$crmDelivery['code']] = $crmDelivery['name'];
                    }

                    $wc_shipping_list = get_wc_shipping_methods();

                    $this->form_fields[] = [
                        'title' => __('Delivery types', 'retailcrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'shipping_options'
                    ];

                    foreach ($wc_shipping_list as $shipping_code => $shipping) {
                        if (isset($shipping['enabled']) && $shipping['enabled'] == static::YES) {
                            $this->form_fields[$shipping_code] = [
                                'title'          => __($shipping['title'], 'woocommerce'),
                                'description' => __($shipping['description'], 'woocommerce'),
                                'css'            => 'min-width:350px;',
                                'class'          => 'select',
                                'type'           => 'select',
                                'options'        => $shippingOptionList,
                                'desc_tip'    =>  true,
                            ];
                        }
                    }
                }

                /**
                 * Payment options
                 */
                $crmPaymentsList = $this->apiClient->paymentTypesList();

                if ($crmPaymentsList instanceof WC_Retailcrm_Response && $crmPaymentsList->isSuccessful()) {
                    $paymentOptionList   = [];
                    $integrationPayments = [];

                    foreach ($crmPaymentsList['paymentTypes'] as $crmPayment) {
                        if ($crmPayment['active'] === false) {
                            continue;
                        }

                        if (!empty($crmPayment['sites']) && in_array($crmSite, $crmPayment['sites']) === false) {
                            continue;
                        }

                        if (isset($crmPayment['integrationModule'])) {
                            $integrationPayments['code'][] = $crmPayment['code'];
                            $integrationPayments['name'][] = $crmPayment['name'];

                            $crmPayment['name'] .= ' - ' . __('Integration payment', 'retailcrm');
                        }

                        $paymentOptionList[$crmPayment['code']] = $crmPayment['name'];
                    }

                    $wc_payment = WC_Payment_Gateways::instance();

                    $this->form_fields[] = [
                            'id' => 'payment_options',
                            'type' => 'heading',
                            'title' => __('Payment types', 'retailcrm'),
                    ];

                    if (!empty($integrationPayments['name'])) {
                        $this->form_fields['payment_notification'] = [
                                'id'                => 'payment_options',
                                'css'               => 'max-width:400px;resize: none;',
                                'type'              => 'textarea',
                                'title'             => __('Attention!', 'retailcrm'),
                                'value'             => '',
                                'placeholder'       => __('If payment type linked to the CRM integration module choosed, payment must be proceed in the CRM', 'retailcrm'),
                                'custom_attributes' => ['readonly' => 'readonly'],
                        ];
                    }

                    foreach ($wc_payment->get_available_payment_gateways() as $payment) {
                        $title = empty($payment->method_title) ? $payment->id : $payment->method_title;
                        $description = empty($payment->method_description)
                            ? $payment->description
                            : $payment->method_description;

                        $this->form_fields[$payment->id] = [
                            'css'         => 'min-width:350px;',
                            'type'        => 'select',
                            'title'       => __($title, 'woocommerce'),
                            'class'       => 'select',
                            'options'     => $paymentOptionList,
                            'desc_tip'    =>  true,
                            'description' => __($description, 'woocommerce'),
                        ];
                    }
                }

                if (!empty($integrationPayments['code'])) {
                    update_option('retailcrm_integration_payments', $integrationPayments['code']);
                }

                /**
                 * Statuses options
                 */
                $statuses_option_list = ['not-upload' => __("Don't send to CRM", 'retailcrm')];
                $retailcrm_statuses_list = $this->apiClient->statusesList();

                if (!empty($retailcrm_statuses_list) && $retailcrm_statuses_list->isSuccessful()) {
                    foreach ($retailcrm_statuses_list['statuses'] as $retailcrm_status) {
                        if ($retailcrm_status['active'] == false) {
                            continue;
                        }

                        $statuses_option_list[$retailcrm_status['code']] = $retailcrm_status['name'];
                    }

                    $wc_statuses = wc_get_order_statuses();

                    $this->form_fields[] = [
                        'title'       => __('Statuses', 'retailcrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'statuses_options'
                    ];

                    foreach ($wc_statuses as $idx => $name) {
                        $uid = str_replace('wc-', '', $idx);
                        $this->form_fields[$uid] = [
                            'title'    => __($name, 'woocommerce'),
                            'css'      => 'min-width:350px;',
                            'class'    => 'select',
                            'type'     => 'select',
                            'options'  => $statuses_option_list,
                            'desc_tip' =>  true,
                        ];
                    }
                }

                /**
                 * Meta data options
                 */
                $this->form_fields[] = [
                    'title'       => __('Custom fields', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'class'       => 'meta-fields'
                ];

                $this->form_fields['order-meta-data-retailcrm'] = [
                    'type'        => 'textarea',
                    'class'       => 'order-meta-data-retailcrm',
                ];

                $this->form_fields['customer-meta-data-retailcrm'] = [
                    'type'        => 'textarea',
                    'class'       => 'customer-meta-data-retailcrm',
                ];

                /**
                 * Inventories options
                 */
                $this->form_fields[] = [
                    'title'       => __('Setting of the stock balance', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'invent_options'
                ];

                $this->form_fields['sync'] = [
                    'label'       => __('Synchronization of the stock balance', 'retailcrm'),
                    'title'       => __('Stock balance', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => __('Enable this setting if you would like to get information on leftover stocks from Simla.com to the website', 'retailcrm')
                ];

                $crmStores = [];
                $crmStoresList = $this->apiClient->storesList();

                if ($crmStoresList->isSuccessful() && !empty($crmStoresList['stores'])) {
                    foreach ($crmStoresList['stores'] as $store) {
                        if (!$store['active']) {
                            continue;
                        }

                        $crmStores[$store['code']] = $store['name'];
                    }
                }

                $this->form_fields['stores_for_uploading'] = [
                        'label'       =>  ' ',
                        'title'       => __('Warehouses available in CRM', 'retailcrm'),
                        'class'       => '',
                        'type'        => 'multiselect',
                        'options'     => $crmStores,
                        'css'         => 'min-height:100px;',
                        'select_buttons' => true,
                        'description' => __('Select warehouses to receive balances from CRM. To select several warehouses, hold down CTRL (for Windows and Linux) or ⌘ Command (for MacOS)',
                            'retailcrm'
                        ),
                ];

                /**
                 * UA options
                 */
                $this->form_fields[] = [
                    'title'       => __('UA settings', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'ua_options'
                ];

                $this->form_fields['ua'] = [
                    'label'       => __('Activate UA', 'retailcrm'),
                    'title'       => __('UA', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => __('Enable this setting for uploading data to UA', 'retailcrm')
                ];

                $this->form_fields['ua_code'] = [
                    'title'       => __('UA tracking code', 'retailcrm'),
                    'class'       => 'input',
                    'type'        => 'input'
                ];

                $this->form_fields['ua_custom'] = [
                    'title'       => __('User parameter', 'retailcrm'),
                    'class'       => 'input',
                    'type'        => 'input'
                ];

                /**
                 * Daemon collector settings
                 */
                $this->form_fields[] = [
                    'title'       => __('Daemon Collector settings', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'invent_options'
                ];

                $this->form_fields['daemon_collector'] = [
                    'label'       => __('Activate Daemon Collector', 'retailcrm'),
                    'title'       => __('Daemon Collector', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => __('Enable this setting for activate Daemon Collector on site', 'retailcrm')
                ];

                $this->form_fields['daemon_collector_key'] = [
                    'title'       => __('Site key', 'retailcrm'),
                    'class'       => 'input',
                    'type'        => 'input'
                ];

                /**
                 * Uploads options
                 */
                $this->form_fields[] = [
                    'title'       => __('Settings of uploading', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'upload_options'
                ];

                $this->form_fields['upload-button'] = [
                    'label'       => __('Upload', 'retailcrm'),
                    'title'       => __('Uploading all customers and orders', 'retailcrm'),
                    'type'        => 'button',
                    'description' => __('You can export all orders and customers from CMS to Simla.com by clicking the «Upload» button. This process can take much time and before it is completed, you need to keep the tab open', 'retailcrm'),
                    'desc_tip'    => true,
                    'id'          => 'export-orders-submit'
                ];

                $this->form_fields['export_selected_orders_ids'] = [
                    'label'             => __('Orders identifiers', 'retailcrm'),
                    'title'             => __('Orders identifiers', 'retailcrm'),
                    'type'              => 'text',
                    'description'       => __('Enter orders identifiers separated by a comma, but no more than 50', 'retailcrm'),
                    'desc_tip'          => true,
                    'id'                => 'export_selected_orders_ids'
                ];

                $this->form_fields['export_selected_orders_btn'] = [
                    'label'             => __('Upload', 'retailcrm'),
                    'title'             => __('Uploading orders by identifiers', 'retailcrm'),
                    'type'              => 'button',
                    'description'       => __('This functionality allows to upload orders to Simla.com differentially', 'retailcrm'),
                    'desc_tip'          => true,
                    'id'                => 'export_selected_orders_btn'
                ];

                /**
                 * WhatsApp options
                 */
                $this->form_fields[] = [
                    'title'       => __('Settings of WhatsApp', 'retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'whatsapp_options'
                ];

                $this->form_fields['whatsapp_active'] = [
                    'label'       => __('Activate WhatsApp', 'retailcrm'),
                    'title'       => __('WhatsApp', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => __('Activate this setting to activate WhatsApp on the website', 'retailcrm')
                ];

                $this->form_fields['whatsapp_location_icon'] = [
                    'label'       => __('Place in the lower right corner of the website', 'retailcrm'),
                    'title'       => __('WhatsApp icon location', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => __(
                        'By default, WhatsApp icon is located in the lower left corner of the website',
                        'retailcrm'
                    )
                ];

                $this->form_fields['whatsapp_number'] = [
                    'title'       => __('Enter your phone number', 'retailcrm'),
                    'class'       => '',
                    'type'        => 'text',
                    'description' => __('WhatsApp chat will be opened with this contact', 'retailcrm')
                ];

                /**
                 * Generate icml file
                 */
                $this->form_fields[] = [
                    'title'       => __('Generating ICML catalog', 'retailcrm'),
                    'type'        => 'title',
                    'description' => '',
                    'id'          => 'icml_options'
                ];

                $this->form_fields[] = [
                    'label'             => __('Generate now', 'retailcrm'),
                    'title'             => __('Generating ICML', 'retailcrm'),
                    'type'              => 'button',
                    'desc_tip'          => true,
                    'id'                => 'icml-retailcrm',
                    'description'       => __(
                        'This functionality allows to generate ICML products catalog for uploading to Simla.com',
                        'retailcrm'
                    ),
                ];

                $this->form_fields['icml'] = [
                    'label'       => __('Generating ICML', 'retailcrm'),
                    'title'       => __('Generating ICML catalog by wp-cron', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox'
                ];

                $this->form_fields['corporate_enabled'] = [
                    'title'       => __('Corporate customers support', 'retailcrm'),
                    'label'       => __('Enabled'),
                    'description' => '',
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                ];

                $this->form_fields['abandoned_carts_enabled'] = [
                    'title'       => __('Abandoned carts', 'retailcrm'),
                    'label'       => __('Upload abandoned carts', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => __(
                            'Enable if you want to in CRM abandoned shopping carts were unloaded',
                            'retailcrm'
                    ),
                ];

                $this->form_fields['history'] = [
                    'label'       => __('Activate history uploads', 'retailcrm'),
                    'title'       => __('Upload data from Simla.com', 'retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox'
                ];

                $this->form_fields['deactivate_update_order'] = [
                        'label'       => __('Disable data editing in Simla.com', 'retailcrm'),
                        'title'       => __('Data updating in Simla.com', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                $this->form_fields['bind_by_sku'] = [
                        'label'       => __('Activate the binding via sku (xml)', 'retailcrm'),
                        'title'       => __('Stock synchronization and link between products', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                $this->form_fields['update_number'] = [
                        'label'       => __('Enable transferring the number to Simla.com', 'retailcrm'),
                        'title'       => __('Transferring the order number', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                $this->form_fields['debug_mode'] = [
                        'label'       => __('Enable debug mode in module', 'retailcrm'),
                        'title'       => __('Debug mode', 'retailcrm'),
                        'description' => __('Is required to enable debug mode for advanced logs', 'retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                /**
                 * Debug information
                 */
                $this->form_fields['debug-info'] = [
                    'title'       => __('Debug information', 'retailcrm'),
                    'type'        => 'heading',
                    'class'       => 'debug_info_options'
                ];

                $this->form_fields['clear_cron_tasks'] = [
                    'label'       => __('Clear', 'retailcrm'),
                    'title'       => __('Clear cron tasks', 'retailcrm'),
                    'type'        => 'button',
                    'description' => __('If you change the time interval, need to clear the old cron tasks', 'retailcrm'),
                    'desc_tip'    => true,
                    'id'          => 'clear_cron_tasks'
                ];
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
     *
     * @codeCoverageIgnore
     */
    public function generate_button_html($key, $data)
    {
        $field    = $this->plugin_id . $this->id . '_' . $key;
        $defaults = [
            'class'             => 'button-secondary',
            'css'               => '',
            'custom_attributes' => [],
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        ];

        $data = wp_parse_args($data, $defaults);

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field); ?>"><?php echo wp_kses_post($data['title']); ?></label>
                <?php echo $this->get_tooltip_html($data); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['label']); ?></span></legend>
                    <button id="<?php echo $data['id']; ?>" class="<?php echo esc_attr($data['class']); ?>" type="button" name="<?php echo esc_attr($field); ?>" id="<?php echo esc_attr($field); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php echo $this->get_custom_attribute_html($data); ?>><?php echo wp_kses_post($data['label']); ?></button>
                    <?php echo $this->get_description_html($data); ?>
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
     *
     * @codeCoverageIgnore
     */
    public function generate_heading_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults  = ['title' => '', 'class' => ''];
        $data      = wp_parse_args($data, $defaults);

        ob_start();
        ?>
            </table>
            <h2 class="wc-settings-sub-title retailcrm_hidden <?php echo esc_attr($data['class']); ?>" id="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <span style="opacity:0.5;float: right;">&#11015;</span></h2>
            <?php if (! empty($data['description'])) : ?>
                <p><?php echo wp_kses_post($data['description']); ?></p>
            <?php endif; ?>
            <table class="form-table" style="display: none;">
        <?php

        return ob_get_clean();
    }

    /**
    * Returns the original value for the online_consultant field (ignores woocommerce validation)
    *
    * @param $key
    * @param $value
    *
    * @return string
    */
    public function validate_online_assistant_field($key, $value)
    {
        $onlineAssistant = $_POST['woocommerce_integration-retailcrm_online_assistant'];

        if (!empty($onlineAssistant) && is_string($onlineAssistant)) {
            return wp_unslash($onlineAssistant);
        }

        return '';
    }

    /**
    * Validate CRM URL.
     *
     * @param string $key
     * @param string $value
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function validate_api_url_field($key, $value)
    {
        $validateMessage = $this->urlValidator->validateUrl($value);

        if ('' !== $validateMessage) {
            $value = '';

            WC_Admin_Settings::add_error(esc_html__($validateMessage, 'retailcrm'));
        }

        $this->crmUrl = $value;

        return $value;
    }

    /**
     * Validate API key
     *
     * @param string $key
     * @param string $value
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function validate_api_key_field($key, $value)
    {
        // If entered the wrong URL, don't need to validate the API key.
        if ('' === $this->crmUrl) {
            return $value;
        }

        $api = new WC_Retailcrm_Proxy($this->crmUrl, $value);
        $response = $api->apiVersions();

        if (empty($response) || !$response->isSuccessful()) {
            $value = '';

            WC_Admin_Settings::add_error(esc_html__('Enter the correct API key', 'retailcrm'));
        } else {
            header("Refresh:0");
        }

        return $value;
    }


    /**
     * Validate whatsapp phone number
     *
     * @param string $key
     * @param string $value
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function validate_whatsapp_number_field($key, $value)
    {
        $post = $this->get_post_data();

        if (!empty($post['woocommerce_integration-retailcrm_whatsapp_active'])) {
            $phoneNumber = preg_replace('/[^+0-9]/', '', $value);

            if (empty($value) || strlen($value) > 25 || strlen($phoneNumber) !== strlen($value)) {
                WC_Admin_Settings::add_error(esc_html__('Introduce the correct phone number', 'retailcrm'));
                $value = '';
            }
        }

        return $value;
    }


    /**
     * Scritp show|hide block settings
     *
     * @codeCoverageIgnore
     */
    function show_blocks()
    {
        ?>
        <script type="text/javascript">
            jQuery('h2.retailcrm_hidden').hover().css({
                'cursor':'pointer',
                'width':'310px'
            });
            jQuery('h2.retailcrm_hidden').bind(
                'click',
                function() {
                    if(jQuery(this).next('table.form-table').is(":hidden")) {
                        jQuery(this).next('table.form-table').show(100);
                        jQuery(this).find('span').html('&#11014;');
                    } else {
                        jQuery(this).next('table.form-table').hide(100);
                        jQuery(this).find('span').html('&#11015;');
                    }
                }
            );
        </script>
        <?php
    }

    /**
     * Add button in admin
     *
     * @codeCoverageIgnore
     */
    function add_retailcrm_button()
    {
        global $wp_admin_bar;
        if (!is_super_admin() || !is_admin_bar_showing() || !is_admin()) {
            return;
        }

        $wp_admin_bar->add_menu(
            [
                'id' => 'retailcrm_top_menu',
                'title' => __('Simla.com', 'retailcrm')
            ]
        );

        $wp_admin_bar->add_menu(
            [
                'id' => 'retailcrm_ajax_generate_icml',
                'title' => __('Generating ICML catalog', 'retailcrm'),
                'href' => '#',
                'parent' => 'retailcrm_top_menu',
                'class' => 'retailcrm_ajax_generate_icml'
            ]
        );

        $wp_admin_bar->add_menu(
            [
                'id' => 'retailcrm_ajax_generate_setings',
                'title' => __('Settings', 'retailcrm'),
                'href' => get_site_url() . '/wp-admin/admin.php?page=wc-settings&tab=integration&section=integration-retailcrm',
                'parent' => 'retailcrm_top_menu',
                'class' => 'retailcrm_ajax_settings'
            ]
        );
    }
}
