<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Base
 * @category Integration
 * @author   RetailCRM
 */

if ( ! class_exists( 'WC_Retailcrm_Base' ) ) :

    /**
     * Class WC_Retailcrm_Base
     */
    class WC_Retailcrm_Base extends WC_Integration {

    protected $api_url;
    protected $api_key;

    /**
     * Init and hook in the integration.
     */
    public function __construct() {
        //global $woocommerce;

        if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) {
            include_once( __DIR__ . '/api/class-wc-retailcrm-proxy.php' );
        }

        $this->id                 = 'integration-retailcrm';
        $this->method_title       = __( 'RetailCRM', 'woocommerce-integration-retailcrm' );
        $this->method_description = __( 'Интеграция с системой управления Retailcrm.', 'woocommerce-integration-retailcrm' );

        // Load the settings.

        $this->init_form_fields();
        $this->init_settings();

        // Actions.
        add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields() {

        $this->form_fields = array(
            array( 'title' => __( 'General Options', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

            'api_url' => array(
                'title'             => __( 'API URL', 'woocommerce-integration-retailcrm' ),
                'type'              => 'text',
                'description'       => __( 'Введите адрес вашей CRM (https://yourdomain.retailcrm.ru).', 'woocommerce-integration-retailcrm' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'api_key' => array(
                'title'             => __( 'API Key', 'woocommerce-integration-retailcrm' ),
                'type'              => 'text',
                'description'       => __( 'Введите ключ API. Вы можете найти его в интерфейсе администратора Retailcrm.', 'woocommerce-integration-retailcrm' ),
                'desc_tip'          => true,
                'default'           => ''
            )
        );

        $api_version_list = array('v4' => 'v4','v5' => 'v5');

        $this->form_fields[] = array(
            'title'       => __( 'Настройки API', 'woocommerce' ),
            'type'        => 'title',
            'description' => '',
            'id'          => 'api_options'
        );

        $this->form_fields['api_version'] = array(
            'title'       => __( 'API версия', 'textdomain' ),
            'description' => __( 'Выберите версию API, которую Вы хотите использовать', 'textdomain' ),
            'css'         => 'min-width:50px;',
            'class'       => 'select',
            'type'        => 'select',
            'options'     => $api_version_list,
            'desc_tip'    =>  true,
        );

        $this->form_fields[] = array(
            'title'       => __( 'Настройки каталога', 'woocommerce' ),
            'type'        => 'title',
            'description' => '',
            'id'          => 'catalog_options'
        );

        foreach (get_post_statuses() as $status_key => $status_value) {
            $this->form_fields['p_' . $status_key] = array(
                'title'       => __( $status_value, 'textdomain' ),
                'label'       => __( ' ', 'textdomain' ), 
                'description' => '',
                'class'       => 'checkbox',
                'type'        => 'checkbox',
                'desc_tip'    =>  true,
            );
        }

        if ($this->get_option( 'api_url' ) != '' && $this->get_option( 'api_key' ) != '') {
            if (isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'integration') {
                $retailcrm = new WC_Retailcrm_Proxy(
                    $this->get_option( 'api_url' ),
                    $this->get_option( 'api_key' ),
                    $this->get_option( 'api_version')
                );

                /**
                 * Shipping options
                 */
                $shipping_option_list = array();
                $retailcrm_shipping_list = $retailcrm->deliveryTypesList();

                if ($retailcrm_shipping_list->isSuccessful()) {
                    foreach ($retailcrm_shipping_list['deliveryTypes'] as $retailcrm_shipping_type) {
                        $shipping_option_list[$retailcrm_shipping_type['code']] = $retailcrm_shipping_type['name'];
                    }

                    $wc_shipping_list = get_wc_shipping_methods();

                    $this->form_fields[] = array(
                        'title' => __( 'Способы доставки', 'woocommerce' ),
                        'type' => 'title',
                        'description' => '',
                        'id' => 'shipping_options'
                    );

                    foreach ( $wc_shipping_list as  $shipping_code => $shipping ) {
                        if ( isset( $shipping['enabled'] ) && $shipping['enabled'] == 'yes' ) {
                            $this->form_fields[$shipping_code] = array(
                                'title'          => __( $shipping['title'], 'textdomain' ),
                                'description' => __( $shipping['description'], 'textdomain' ),
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
                $retailcrm_payment_list = $retailcrm->paymentTypesList();

                if ($retailcrm_payment_list->isSuccessful()) {
                    foreach ($retailcrm_payment_list['paymentTypes'] as $retailcrm_payment_type) {
                        $payment_option_list[$retailcrm_payment_type['code']] = $retailcrm_payment_type['name'];
                    }

                    $wc_payment = new WC_Payment_Gateways();

                    $this->form_fields[] = array(
                        'title' => __( 'Способы оплаты', 'woocommerce' ),
                        'type' => 'title',
                        'description' => '',
                        'id' => 'payment_options'
                    );

                    foreach ( $wc_payment->payment_gateways as $payment ) {
                        if ( isset( $payment->enabled ) && $payment->enabled == 'yes' ) {
                            $key = $payment->id;
                            $name = $key;
                            $this->form_fields[$name] = array(
                                'title'          => __( $payment->method_title, 'textdomain' ),
                                'description' => __( $payment->method_description, 'textdomain' ),
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
                $retailcrm_statuses_list = $retailcrm->statusesList();

                if ($retailcrm_statuses_list->isSuccessful()) {
                    foreach ($retailcrm_statuses_list['statuses'] as $retailcrm_status) {
                        $statuses_option_list[$retailcrm_status['code']] = $retailcrm_status['name'];
                    }

                    $wc_statuses = wc_get_order_statuses();

                    $this->form_fields[] = array(
                        'title'       => __( 'Статусы', 'woocommerce' ),
                        'type'        => 'title',
                        'description' => '',
                        'id'          => 'statuses_options'
                    );

                    foreach ( $wc_statuses as $idx => $name ) {
                        $uid = str_replace('wc-', '', $idx);
                        $this->form_fields[$uid] = array(
                            'title'    => __( $name, 'textdomain' ),
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
                    'title'       => __( 'Настройки выгрузки остатков', 'woocommerce' ),
                    'type'        => 'title',
                    'description' => '',
                    'id'          => 'invent_options'
                );

                $this->form_fields['sync'] = array(
                    'label'       => __( 'Выгружать остатки из CRM', 'textdomain' ),
                    'title'       => 'Остатки',
                    'class'       => 'checkbox',
                    'type'        => 'checkbox',
                    'description' => 'Отметьте данный пункт, если хотите выгружать остатки товаров из CRM в магазин.'
                );

                /**
                 * Uploads options
                 */
                $options = array_filter(get_option( 'woocommerce_integration-retailcrm_settings' ));

                if (!isset($options['uploads'])) {
                    $this->form_fields[] = array(
                        'title'       => __( 'Выгрузка клиентов и заказов', 'woocommerce' ),
                        'type'        => 'title',
                        'description' => '',
                        'id'          => 'upload_options'
                    );

                    $this->form_fields['upload-button'] = array(
                        'label'             => 'Выгрузить',
                        'title'             => __( 'Выгрузка клиентов и заказов', 'woocommerce-integration-retailcrm' ),
                        'type'              => 'button',
                        'description'       => __( 'Пакетная выгрузка существующих клиентов и заказов.', 'woocommerce-integration-retailcrm' ),
                        'desc_tip'          => true,
                        'id'                => 'uploads-retailcrm'
                    );
                }
                
                /*
                 * Generate icml file
                 */
                $this->form_fields[] = array(
                    'title'       => __( 'Генерация каталога товаров', 'woocommerce' ),
                    'type'        => 'title',
                    'description' => '',
                    'id'          => 'icml_options'
                );
                
                $this->form_fields[] = array(
                    'label'             => 'Сгенерировать',
                    'title'             => __( 'Генерация icml', 'woocommerce-integration-retailcrm' ),
                    'type'              => 'button',
                    'description'       => __( 'Данный функционал позволяет сгенерировать каталог товаров для выгрузки в CRM.', 'woocommerce-integration-retailcrm' ),
                    'desc_tip'          => true,
                    'id'                => 'icml-retailcrm'
                );
            }
        }
    }

    public function generate_button_html( $key, $data ) {
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

    public function validate_api_version_field( $key, $value ) {
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
                WC_Admin_Settings::add_error( esc_html__( '"Выбранная версия API недоступна"', 'woocommerce-integration-retailcrm' ) );
                $value = '';
            }

            return $value;
        }
    }

    public function validate_api_url_field( $key, $value ) {
        $post = $this->get_post_data();
        $api = new WC_Retailcrm_Proxy(
            $value,
            $post[$this->plugin_id . $this->id . '_api_key']
        );

        $response = $api->apiVersions();

        if ($response == NULL) {
            WC_Admin_Settings::add_error( esc_html__( '"Введите корректный адрес CRM"', 'woocommerce-integration-retailcrm' ) );
            $value = '';
        }

        return $value;
    }
    
    public function validate_api_key_field( $key, $value ) {
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
            WC_Admin_Settings::add_error( esc_html__( '"Введитe правильный API ключ"', 'woocommerce-integration-retailcrm' ) );
            $value = '';
        }

        return $value;
    }
}

endif;
