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
        $this->method_title       = esc_html__('Simla.com', 'woo-retailcrm');
        $this->method_description = esc_html__('Integration with Simla.com management system', 'woo-retailcrm');

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
    public function ajax_retailcrm_generate_icml()
    {
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script type="text/javascript">
        jQuery('#icml-retailcrm, #wp-admin-bar-retailcrm_ajax_generate_icml').bind('click', function() {
            jQuery.ajax({
                type: "POST",
                url: '<?php echo esc_js($ajax_url . '?action=retailcrm_generate_icml'); ?>',
                data: { _ajax_nonce: '<?php echo esc_js(wp_create_nonce('woo-retailcrm-admin-nonce')); ?>' },
                success: function (response) {
                    alert('<?php echo esc_html__('Catalog was generated', 'woo-retailcrm'); ?>');
                    console.log('AJAX response : ', response);
                }
            });
        });
        </script>
        <?php
    }

    public function ajax_retailcrm_upload_loyalty_price()
    {
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script type="text/javascript">
            jQuery('#upload-loyalty-price-retailcrm').bind('click', function () {
                jQuery.ajax({
                    type: "POST",
                    url: '<?php echo esc_js($ajax_url . '?action=retailcrm_upload_loyalty_price'); ?>',
                    data: { _ajax_nonce: '<?php echo esc_js(wp_create_nonce('woo-retailcrm-admin-nonce')); ?>'},
                    success: function (response) {
                        alert('<?php echo esc_html__('Promotional prices unloaded', 'woo-retailcrm');?>');
                        console.log('AJAX response : ', response);
                    }
                });
            })
        </script>
        <?php
    }

    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            [ 'title' => esc_html__('Main settings', 'woo-retailcrm'), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ],

            'api_url' => [
                'title'             => esc_html__('API of URL', 'woo-retailcrm'),
                'type'              => 'text',
                'description'       => esc_html__('Enter API URL (https://yourdomain.simla.com)', 'woo-retailcrm'),
                'desc_tip'          => true,
                'default'           => ''
            ],
            'api_key' => [
                'title'             => esc_html__('API key', 'woo-retailcrm'),
                'type'              => 'text',
                'description'       => esc_html__('Enter your API key. You can find it in the administration section of Simla.com', 'woo-retailcrm'),
                'desc_tip'          => true,
                'default'           => ''
            ]
        ];

        if ($this->apiClient) {
            // The field is highlighted in red if the CRM site is invalid
            $this->form_fields['api_key']['class'] = $this->isValidCrmSite($this->apiClient)
            ? ''
            : 'red-selected-retailcrm';

            if (
                isset($_GET['page']) && $_GET['page'] == 'wc-settings'
                && isset($_GET['tab']) && $_GET['tab'] == 'integration'
            ) {
                add_action('admin_print_footer_scripts', [$this, 'show_blocks'], 99);

                $this->form_fields[] = [
                    'title'       => esc_html__('API settings', 'woo-retailcrm'),
                    'type'        => 'title',
                    'description' => '',
                    'id'          => 'api_options'
                ];

                $this->form_fields['online_assistant'] = [
                    'title'       => esc_html__('Online assistant/Event tracker', 'woo-retailcrm'),
                    'css' => 'width:400px; height:215px; resize: horizontal;',
                    'type'        => 'textarea',
                    'id'          => 'online_assistant',
                    'placeholder' => esc_html__('Insert the Online consultant/Event tracker code here', 'woo-retailcrm')
                ];

                $this->form_fields['tracker_settings'] = [
                    'type'        => 'textarea',
                    'css'         => 'display: none',
                ];

                $this->form_fields[] = [
                    'title'       => esc_html__('Catalog settings', 'woo-retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'catalog_options'
                ];

                $this->form_fields['product_description'] = [
                    'type'        => 'select',
                    'class'       => 'select',
                    'title'       => esc_html__('Product description', 'woo-retailcrm'),
                    'options'     => [
                            'full'  => esc_html__('Full description', 'woo-retailcrm'),
                            'short' => esc_html__('Short description', 'woo-retailcrm'),
                            ],
                    'desc_tip'    => true,
                    'description' => esc_html__(
                        'In the catalog, you can use a full or short description of the product',
                        'woo-retailcrm'
                    ),
                ];

                $this->form_fields['icml_unload_services'] = [
                    'label' => esc_html__('Enabled', 'woo-retailcrm'),
                    'title' => esc_html__('Uploading services', 'woo-retailcrm'),
                    'class' => 'checkbox',
                    'type' => 'checkbox',
                    'desc_tip' => true,
                    'description' => esc_html__(
                        "Goods with the 'virtual' option enabled will be uploaded to Simla as services",
                        "woo-retailcrm"
                    ),
                ];

                foreach (get_post_statuses() as $statusKey => $statusValue) {
                    $this->form_fields['p_' . $statusKey] = [
                        'title'       => $statusValue,
                        'label'       => ' ',
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
                        'title' => esc_html__('Order methods', 'woo-retailcrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'order_methods_options'
                    ];

                    $this->form_fields['order_methods'] = [
                        'label'       =>  ' ',
                        'title'       => esc_html__('Order methods available for uploading from Simla.com', 'woo-retailcrm'),
                        'class'       => '',
                        'type'        => 'multiselect',
                        'options'     => $order_methods_option,
                        'css'         => 'min-height:100px;',
                        'select_buttons' => true,
                        'description' => esc_html__(
                            'Select order methods which will be uploaded from Simla.com to the website',
                            'woo-retailcrm'
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
                        'title' => esc_html__('Delivery types', 'woo-retailcrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'shipping_options'
                    ];

                    foreach ($wc_shipping_list as $shipping_code => $shipping) {
                        if (isset($shipping['enabled']) && $shipping['enabled'] == static::YES) {
                            $this->form_fields[$shipping_code] = [
                                'title'          => $shipping['title'],
                                'description' => $shipping['description'],
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

                            $crmPayment['name'] .= ' - ' . esc_html__('Integration payment', 'woo-retailcrm');
                        }

                        $paymentOptionList[$crmPayment['code']] = $crmPayment['name'];
                    }

                    $wc_payment = WC_Payment_Gateways::instance();

                    $this->form_fields[] = [
                            'id' => 'payment_options',
                            'type' => 'heading',
                            'title' => esc_html__('Payment types', 'woo-retailcrm'),
                    ];

                    if (!empty($integrationPayments['name'])) {
                        $this->form_fields['payment_notification'] = [
                                'id'                => 'payment_options',
                                'css'               => 'max-width:400px;resize: none;',
                                'type'              => 'textarea',
                                'title'             => esc_html__('Attention!', 'woo-retailcrm'),
                                'value'             => '',
                                'placeholder'       => esc_html__('If payment type linked to the CRM integration module choosed, payment must be proceed in the CRM', 'woo-retailcrm'),
                                'custom_attributes' => ['readonly' => 'readonly'],
                        ];
                    }

                    foreach ($wc_payment->get_available_payment_gateways() as $payment) {
                        $title = empty($payment->method_title) ?$payment->id : $payment->method_title;
                        $description = empty($payment->method_description)
                            ? $payment->description
                            : $payment->method_description;

                        $this->form_fields[$payment->id] = [
                            'css'         => 'min-width:350px;',
                            'type'        => 'select',
                            'title'       => esc_html($title),
                            'class'       => 'select',
                            'options'     => $paymentOptionList,
                            'desc_tip'    =>  true,
                            'description' => esc_html($description),
                        ];
                    }
                }

                if (!empty($integrationPayments['code'])) {
                    update_option('retailcrm_integration_payments', $integrationPayments['code']);
                }

                /**
                 * Statuses options
                 */
                $statuses_option_list = ['not-upload' => esc_html__("Don't send to CRM", 'woo-retailcrm')];
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
                        'title'       => esc_html__('Statuses', 'woo-retailcrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'statuses_options'
                    ];

                    foreach ($wc_statuses as $idx => $name) {
                        $uid = str_replace('wc-', '', $idx);
                        $this->form_fields[$uid] = [
                            'title'    => esc_html($name),
                            'css'      => 'min-width:350px;',
                            'class'    => 'select',
                            'type'     => 'select',
                            'options'  => $statuses_option_list,
                            'desc_tip' =>  true,
                        ];
                    }
                }

                /**
                * Coupon options
                */
                $coupon_option_list = ['not-upload' => esc_html__("Don't send to CRM", 'woo-retailcrm')];
                $retailcrm_metaFiels_list = $this->apiClient->customFieldsList(
                        ['entity' => 'order', 'type' => ['string', 'text']]
                );

                if (!empty($retailcrm_metaFiels_list) && $retailcrm_metaFiels_list->isSuccessful()) {
                    foreach ($retailcrm_metaFiels_list['customFields'] as $retailcrm_metaField) {
                        $coupon_option_list[$retailcrm_metaField['code']] = $retailcrm_metaField['name'];
                    }

                    $this->form_fields[] = [
                            'title' => esc_html__("Coupon", 'woo-retailcrm'),
                            'type' => 'heading',
                            'description' => '',
                            'id' => 'coupon_options'
                    ];

                    $this->form_fields['coupon_notification'] = [
                            'id' => 'coupon_options',
                            'css'               => 'max-width:400px;resize: none;height:215px;',
                            'type'              => 'textarea',
                            'title'             => esc_html__('Attention!', 'woo-retailcrm'),
                            'value'             => '',
                            'placeholder'       => esc_html__('When working with coupons via CRM, it is impossible to transfer manual discounts.', 'woo-retailcrm') .
                            PHP_EOL . PHP_EOL .
                            esc_html__('The user field must be in the String or Text format.', 'woo-retailcrm') .
                            PHP_EOL .
                            esc_html__('When using multiple coupons, separation is supported using spaces, line breaks, characters `;` `,`.', 'woo-retailcrm') .
                            PHP_EOL .
                            esc_html__('For example: code_coupon_1; code_coupon_2, code_coupon_3 code_coupon_4', 'woo-retailcrm'),
                            'custom_attributes' => ['readonly' => 'readonly'],
                    ];

                    $this->form_fields['woo_coupon_apply_field'] = [
                            'title' => esc_html__('Coupon', 'woo-retailcrm'),
                            'css' => 'min-width:350px;',
                            'class' => 'select',
                            'type' => 'select',
                            'options' => $coupon_option_list,
                            'desc_tip' => true,
                    ];
                }

                /**
                 * Meta data options
                 */
                $this->form_fields[] = [
                    'title'       => esc_html__('Custom fields', 'woo-retailcrm'),
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
                    'title'       => esc_html__('Setting of the stock balance', 'woo-retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'invent_options'
                ];

                $this->form_fields['sync'] = [
                    'label'       => esc_html__('Synchronization of the stock balance', 'woo-retailcrm'),
                    'title'       => esc_html__('Stock balance', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => esc_html__('Enable this setting if you would like to get information on leftover stocks from Simla.com to the website', 'woo-retailcrm')
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
                        'title'       => esc_html__('Warehouses available in CRM', 'woo-retailcrm'),
                        'class'       => '',
                        'type'        => 'multiselect',
                        'options'     => $crmStores,
                        'css'         => 'min-height:100px;',
                        'select_buttons' => true,
                        'description' => esc_html__('Select warehouses to receive balances from CRM. To select several warehouses, hold down CTRL (for Windows and Linux) or ⌘ Command (for MacOS)',
                            'woo-retailcrm'
                        ),
                ];

                /**
                 * UA options
                 */
                $this->form_fields[] = [
                    'title'       => esc_html__('UA settings', 'woo-retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'ua_options'
                ];

                $this->form_fields['ua'] = [
                    'label'       => esc_html__('Activate UA', 'woo-retailcrm'),
                    'title'       => esc_html__('UA', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => esc_html__('Enable this setting for uploading data to UA', 'woo-retailcrm')
                ];

                $this->form_fields['ua_code'] = [
                    'title'       => esc_html__('UA tracking code', 'woo-retailcrm'),
                    'class'       => 'input',
                    'type'        => 'input'
                ];

                $this->form_fields['ua_custom'] = [
                    'title'       => esc_html__('User parameter', 'woo-retailcrm'),
                    'class'       => 'input',
                    'type'        => 'input'
                ];

                /**
                 * Daemon collector settings
                 */
                if ($this->get_option('daemon_collector') === 'yes') {
                    $this->form_fields[] = [
                        'title'       => esc_html__('Daemon Collector settings', 'woo-retailcrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'invent_options'
                    ];
                }

                $this->form_fields['daemon_collector'] = [
                    'label'       => esc_html__('Activate Daemon Collector', 'woo-retailcrm'),
                    'title'       => esc_html__('Daemon Collector', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => esc_html__('Enable this setting for activate Daemon Collector on site', 'woo-retailcrm')
                ];

                $this->form_fields['daemon_collector_key'] = [
                    'title'       => esc_html__('Site key', 'woo-retailcrm'),
                    'class'       => 'input',
                    'type'        => 'input'
                ];

                /**
                 * Uploads options
                 */
                $this->form_fields[] = [
                    'title'       => esc_html__('Settings of uploading', 'woo-retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'upload_options'
                ];

                $this->form_fields['upload-button'] = [
                    'label'       => esc_html__('Upload', 'woo-retailcrm'),
                    'title'       => esc_html__('Uploading all customers and orders', 'woo-retailcrm'),
                    'type'        => 'button',
                    'description' => esc_html__('You can export all orders and customers from CMS to Simla.com by clicking the «Upload» button. This process can take much time and before it is completed, you need to keep the tab open', 'woo-retailcrm'),
                    'desc_tip'    => true,
                    'id'          => 'export-orders-submit'
                ];

                $this->form_fields['export_selected_orders_ids'] = [
                    'label'             => esc_html__('Orders identifiers', 'woo-retailcrm'),
                    'title'             => esc_html__('Orders identifiers', 'woo-retailcrm'),
                    'type'              => 'text',
                    'description'       => esc_html__('Enter orders identifiers separated by a comma, but no more than 50', 'woo-retailcrm'),
                    'desc_tip'          => true,
                    'id'                => 'export_selected_orders_ids'
                ];

                $this->form_fields['export_selected_orders_btn'] = [
                    'label'             => esc_html__('Upload', 'woo-retailcrm'),
                    'title'             => esc_html__('Uploading orders by identifiers', 'woo-retailcrm'),
                    'type'              => 'button',
                    'description'       => esc_html__('This functionality allows to upload orders to Simla.com differentially', 'woo-retailcrm'),
                    'desc_tip'          => true,
                    'id'                => 'export_selected_orders_btn'
                ];

                /**
                 * WhatsApp options
                 */
                $this->form_fields[] = [
                    'title'       => esc_html__('Settings of WhatsApp', 'woo-retailcrm'),
                    'type'        => 'heading',
                    'description' => '',
                    'id'          => 'whatsapp_options'
                ];

                $this->form_fields['whatsapp_active'] = [
                    'label'       => esc_html__('Activate WhatsApp', 'woo-retailcrm'),
                    'title'       => esc_html__('WhatsApp', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => esc_html__('Activate this setting to activate WhatsApp on the website', 'woo-retailcrm')
                ];

                $this->form_fields['whatsapp_location_icon'] = [
                    'label'       => esc_html__('Place in the lower right corner of the website', 'woo-retailcrm'),
                    'title'       => esc_html__('WhatsApp icon location', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => esc_html__(
                        'By default, WhatsApp icon is located in the lower left corner of the website',
                        'woo-retailcrm'
                    )
                ];

                $this->form_fields['whatsapp_number'] = [
                    'title'       => esc_html__('Enter your phone number', 'woo-retailcrm'),
                    'class'       => '',
                    'type'        => 'text',
                    'description' => esc_html__('WhatsApp chat will be opened with this contact', 'woo-retailcrm')
                ];

                 /**
                * Loyalty Program
                */

                $this->form_fields[] = [
                    'title' => esc_html__('Loyalty program', 'woo-retailcrm'),
                    'type' => 'heading',
                    'description' => '',
                    'id' => 'loyalty_options'
                ];

                $linkDescriptionLoyalty = wp_kses(
                    __(
                        "<a href='https://docs.simla.com/Users/Integration/SiteModules/WooCommerce/PLWoocommerce'>documentation loyalty program</a>",
                        'woo-retailcrm'
                    ),
                    array(
                        'a' => array(
                            'href' => true,
                        ),
                    )
                );

                $this->form_fields['loyalty'] = [
                    'label'       => esc_html__('Activate program loyalty', 'woo-retailcrm'),
                    'title'       => esc_html__('Loyalty program', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => '<b style="color: red">' .
                     esc_html__('Attention! When activating the loyalty program, the method of ICML catalog generation changes. Details in', 'woo-retailcrm') .
                      ' </b>' . $linkDescriptionLoyalty
                ];

                $this->form_fields['loyalty_terms'] = [
                    'title' => esc_html__('Terms of loyalty program', 'woo-retailcrm'),
                    'css' => 'width:400px; height:215px; resize: horizontal;',
                    'type' => 'textarea',
                    'id' => 'loyalty_terms',
                    'placeholder' => esc_html__('Insert the terms and conditions of the loyalty program', 'woo-retailcrm')
                ];

                $this->form_fields['loyalty_personal'] = [
                    'title' => esc_html__('Conditions of personal data processing', 'woo-retailcrm'),
                    'css' => 'width:400px; height:215px; resize: horizontal;',
                    'type' => 'textarea',
                    'id' => 'loyalty_personal',
                    'placeholder' => esc_html__('Insert the terms and conditions for processing personal data', 'woo-retailcrm')
                ];

                /**
                 * Generate icml file
                 */
                $this->form_fields[] = [
                    'title'       => esc_html__('Generating ICML catalog', 'woo-retailcrm'),
                    'type'        => 'title',
                    'description' => '',
                    'id'          => 'icml_options'
                ];

                $this->form_fields[] = [
                    'label'             => esc_html__('Generate now', 'woo-retailcrm'),
                    'title'             => esc_html__('Generating ICML', 'woo-retailcrm'),
                    'type'              => 'button',
                    'desc_tip'          => true,
                    'id'                => 'icml-retailcrm',
                    'description'       => esc_html__(
                        'This functionality allows to generate ICML products catalog for uploading to Simla.com',
                        'woo-retailcrm'
                    ),
                ];

                $this->form_fields[] = [
                    'label' => esc_html__('Upload prices now', 'woo-retailcrm'),
                    'title' => esc_html__('Uploaded discount price', 'woo-retailcrm'),
                    'type' => 'button',
                    'desc_tip' => true,
                    'id' => 'upload-loyalty-price-retailcrm',
                    'description' => esc_html__(
                        'This functionality loads the promotional prices offers into Simla.com',
                        'woo-retailcrm'
                    ),
                ];

                $this->form_fields['icml'] = [
                    'label'       => esc_html__('Generating ICML', 'woo-retailcrm'),
                    'title'       => esc_html__('Generating ICML catalog by wp-cron', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox'
                ];

                $this->form_fields['corporate_enabled'] = [
                    'title'       => esc_html__('Corporate customers support', 'woo-retailcrm'),
                    'label'       => esc_html__('Enabled', 'woo-retailcrm'),
                    'description' => '',
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                ];

                $this->form_fields['abandoned_carts_enabled'] = [
                    'title'       => esc_html__('Abandoned carts', 'woo-retailcrm'),
                    'label'       => esc_html__('Upload abandoned carts', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => esc_html__(
                            'Enable if you want to in CRM abandoned shopping carts were unloaded',
                            'woo-retailcrm'
                    ),
                ];

                $this->form_fields['history'] = [
                    'label'       => esc_html__('Activate history uploads', 'woo-retailcrm'),
                    'title'       => esc_html__('Upload data from Simla.com', 'woo-retailcrm'),
                    'class'       => 'checkbox',
                    'type'        => 'checkbox'
                ];

                $this->form_fields['deactivate_update_order'] = [
                        'label'       => esc_html__('Disable data editing in Simla.com', 'woo-retailcrm'),
                        'title'       => esc_html__('Data updating in Simla.com', 'woo-retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                $this->form_fields['bind_by_sku'] = [
                        'label'       => esc_html__('Activate the binding via sku (xml)', 'woo-retailcrm'),
                        'title'       => esc_html__('Stock synchronization and link between products', 'woo-retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                $this->form_fields['update_number'] = [
                        'label'       => esc_html__('Enable transferring the number to Simla.com', 'woo-retailcrm'),
                        'title'       => esc_html__('Transferring the order number', 'woo-retailcrm'),
                        'class'       => 'checkbox',
                        'type'        => 'checkbox'
                ];

                /**
                 * Debug information
                 */
                $this->form_fields['debug-info'] = [
                    'title'       => esc_html__('Debug information', 'woo-retailcrm'),
                    'type'        => 'heading',
                    'class'       => 'debug_info_options'
                ];

                $this->form_fields['clear_cron_tasks'] = [
                    'label'       => esc_html__('Clear', 'woo-retailcrm'),
                    'title'       => esc_html__('Clear cron tasks', 'woo-retailcrm'),
                    'type'        => 'button',
                    'description' => esc_html__('If you change the time interval, need to clear the old cron tasks', 'woo-retailcrm'),
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
                <?php echo esc_attr($this->get_tooltip_html($data)); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['label']); ?></span></legend>
                    <button id="<?php echo esc_attr($data['id']); ?>" class="<?php echo esc_attr($data['class']); ?>" type="button" name="<?php echo esc_attr($field); ?>" id="<?php echo esc_attr($field); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php echo esc_attr($this->get_custom_attribute_html($data)); ?>><?php echo wp_kses_post($data['label']); ?></button>
                    <?php echo esc_attr($this->get_description_html($data)); ?>
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

        if ($onlineAssistant === '') {
            return '';
        }

        if (strpos($onlineAssistant, 'c.retailcrm.tech/widget/loader.js') !== false) {
            return wp_unslash($onlineAssistant);
        }

        WC_Admin_Settings::add_error(esc_html__('Incorrect code of Online consultant/Event tracker', 'woo-retailcrm'));

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

            WC_Admin_Settings::add_error(esc_html__('Incorrect URL.', 'woo-retailcrm'));
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

        $isValidCrmSite = $this->isValidCrmSite(new WC_Retailcrm_Proxy($this->crmUrl, $value));

        // The field is highlighted in red if the CRM site is invalid
        if ($isValidCrmSite) {
            $this->form_fields['api_key']['class'] = '';

            header("Refresh:0");
        } else {
            $value = '';
            $this->form_fields['api_key']['class'] = 'red-selected-retailcrm';
        }

        return $value;
    }

    private function isValidCrmSite($api)
    {
        $errorMessage = '';
        $isValidCrmSite = true;
        $response = $api->sitesList();

        if (empty($response['sites']) || !$response->isSuccessful()) {
            $errorMessage = esc_html__('Enter the correct API key', 'woo-retailcrm');
            $isValidCrmSite = false;
        } elseif (count($response['sites']) > 1)  {
            $errorMessage = esc_html__('API key with one-shop access required', 'woo-retailcrm');
            $isValidCrmSite = false;
        } else {
            $site = current($response['sites']);

            if (get_woocommerce_currency() !== $site['currency']) {
                $errorMessage = esc_html__('The currency of the site differs from the currency of the store in CRM. For the integration to work correctly, the currencies in CRM and CMS must match', 'woo-retailcrm');
                $isValidCrmSite = false;
            }
        }

        if ('' !== $errorMessage) {
            WC_Admin_Settings::add_error($errorMessage);
        }

        return $isValidCrmSite;
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
                WC_Admin_Settings::add_error(esc_html__('Introduce the correct phone number', 'woo-retailcrm'));
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
                'title' => esc_html__('Simla.com', 'woo-retailcrm')
            ]
        );

        $wp_admin_bar->add_menu(
            [
                'id' => 'retailcrm_ajax_generate_icml',
                'title' => esc_html__('Generating ICML catalog', 'woo-retailcrm'),
                'href' => '#',
                'parent' => 'retailcrm_top_menu',
                'class' => 'retailcrm_ajax_generate_icml'
            ]
        );

        $wp_admin_bar->add_menu(
            [
                'id' => 'retailcrm_ajax_generate_setings',
                'title' => esc_html__('Settings', 'woo-retailcrm'),
                'href' => get_site_url() . '/wp-admin/admin.php?page=wc-settings&tab=integration&section=integration-retailcrm',
                'parent' => 'retailcrm_top_menu',
                'class' => 'retailcrm_ajax_settings'
            ]
        );
    }
}
