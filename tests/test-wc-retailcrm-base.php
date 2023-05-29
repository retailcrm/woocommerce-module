<?php

use datasets\DataBaseRetailCrm;

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Base_Test - Testing WC_Retailcrm_Base.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Base_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMockOrderMethods;
    protected $responseMockDeliveryTypes;
    protected $responseMockPaymentTypes;
    protected $responseMockStatuses;
    protected $responseMockCustomFields;
    protected $dataOptions;
    private $baseRetailcrm;

    public function setUp()
    {
        $this->apiMock = $this
            ->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'orderMethodsList',
                    'deliveryTypesList',
                    'paymentTypesList',
                    'statusesList',
                    'customFieldsList',
                    'getSingleSiteForKey',
                ]
            )
            ->getMock();

        $this->setMockResponse($this->apiMock, 'getSingleSiteForKey', ['woocommerce']);
        $this->setMockOrderMethods();
        $this->setMockDeliveryTypes();
        $this->setMockPaymentTypes();
        $this->setMockStatuses();
        $this->setMockCustomFields();

        $_GET['page'] = 'wc-settings';
        $_GET['tab'] = 'integration';

        $this->dataOptions = $this->setOptions();
        $this->baseRetailcrm = new WC_Retailcrm_Base($this->apiMock);
    }

    public function test_retailcrm_form_fields()
    {
        $this->assertInternalType('array', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('api_url', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('api_key', $this->baseRetailcrm->form_fields);

        foreach (get_post_statuses() as $key => $status) {
            $this->assertArrayHasKey('p_' . $key, $this->baseRetailcrm->form_fields);
        }

        $this->assertArrayHasKey('order_methods', $this->baseRetailcrm->form_fields);

        foreach (get_wc_shipping_methods() as $code => $value) {
            if (isset($value['enabled']) && $value['enabled'] == 'yes') {
                $this->assertArrayHasKey($code, $this->baseRetailcrm->form_fields);
            }
        }

        $wc_payment = WC_Payment_Gateways::instance();

        foreach ($wc_payment->get_available_payment_gateways() as $payment) {
            if (isset($payment->enabled) && $payment->enabled == 'yes') {
                $this->assertArrayHasKey($payment->id, $this->baseRetailcrm->form_fields);
            }
        }

        $integrationPayments = get_option('retailcrm_integration_payments');

        $this->assertNotEmpty($integrationPayments);
        $this->assertInternalType('array', $integrationPayments);
        $this->assertEquals('payment3', $integrationPayments[0]);

        foreach (wc_get_order_statuses() as $idx => $name) {
            $uid = str_replace('wc-', '', $idx);
            $this->assertArrayHasKey($uid, $this->baseRetailcrm->form_fields);
        }

        //Order settings
        $this->assertArrayHasKey('order_methods', $this->baseRetailcrm->form_fields);
        $this->assertInternalType('array', $this->baseRetailcrm->form_fields['order_methods']);

        //Payment settings
        $this->assertArrayHasKey('payment_notification', $this->baseRetailcrm->form_fields);
        $this->assertInternalType('array', $this->baseRetailcrm->form_fields['payment_notification']);
        $this->assertEquals('textarea', $this->baseRetailcrm->form_fields['payment_notification']['type']);

        //WhatsApp settings
        $this->assertArrayHasKey('whatsapp_active', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('whatsapp_location_icon', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('whatsapp_number', $this->baseRetailcrm->form_fields);

        //Cron settings
        $this->assertArrayHasKey('icml', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('history', $this->baseRetailcrm->form_fields);

        //Export orders/customers settings
        $this->assertArrayHasKey('export_selected_orders_ids', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('export_selected_orders_btn', $this->baseRetailcrm->form_fields);

        //Debug info settings
        $this->assertArrayHasKey('debug_mode', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('debug-info', $this->baseRetailcrm->form_fields);

        //Other settings
        $this->assertArrayHasKey('corporate_enabled', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('abandoned_carts_enabled', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('online_assistant', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('deactivate_update_order', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('bind_by_sku', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('update_number', $this->baseRetailcrm->form_fields);
        $this->assertArrayHasKey('product_description', $this->baseRetailcrm->form_fields);
    }

    public function test_retailcrm_form_fields_value()
    {
        foreach ($this->getOptions() as $key => $value) {
            if (is_array($value) === false) {
                $this->assertEquals($this->dataOptions[$key], $value);
            } else {
                $this->assertEquals($this->dataOptions[$key][0], $value[0]);
            }
        }
    }

    public function test_initialize_online_assistant()
    {
        ob_start();
        $this->baseRetailcrm->initialize_online_assistant($this->dataOptions);

        $this->assertEquals($this->dataOptions['online_assistant'], ob_get_contents());
        ob_end_clean();
    }

    public function test_option_cron_enabled()
    {
        $this->baseRetailcrm->api_sanitized($this->getOptions());

        $history = date('H:i:s d-m-Y', wp_next_scheduled('retailcrm_history'));
        $icml = date('H:i:s d-m-Y', wp_next_scheduled('retailcrm_icml'));
        $inventories = date('H:i:s d-m-Y', wp_next_scheduled('retailcrm_inventories'));

        $this->assertInternalType('string', $history);
        $this->assertInternalType('string', $icml);
        $this->assertInternalType('string', $inventories);
    }

    public function test_option_cron_disabled()
    {
        $settings = $this->baseRetailcrm->api_sanitized(
            [
                'api_url' => 'https://example.retailcrm.ru',
                'api_key' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX1',
                'corporate_enabled' => 'yes',
                'sync' => 'no',
                'icml' => 'no',
                'history' => 'no',
            ]
        );

        $history = date('H:i:s d-m-Y', wp_next_scheduled('retailcrm_history'));
        $icml = date('H:i:s d-m-Y', wp_next_scheduled('retailcrm_icml'));
        $inventories = date('H:i:s d-m-Y', wp_next_scheduled('retailcrm_inventories'));

        $this->assertEquals('00:00:00 01-01-1970', $history);
        $this->assertEquals('00:00:00 01-01-1970', $icml);
        $this->assertEquals('00:00:00 01-01-1970', $inventories);

        $this->assertInternalType('array', $settings);
        $this->assertArrayHasKey('api_url', $settings);
        $this->assertEquals('https://example.retailcrm.ru', $settings['api_url']);
        $this->assertArrayHasKey('api_key', $settings);
        $this->assertEquals('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX1', $settings['api_key']);
        $this->assertArrayHasKey('corporate_enabled', $settings);
        $this->assertEquals('yes', $settings['corporate_enabled']);
        $this->assertArrayHasKey('sync', $settings);
        $this->assertEquals('no', $settings['sync']);
        $this->assertArrayHasKey('icml', $settings);
        $this->assertEquals('no', $settings['icml']);
        $this->assertArrayHasKey('history', $settings);
        $this->assertEquals('no', $settings['history']);
    }


    public function test_get_cron_info()
    {
        ob_start();
        $this->baseRetailcrm->get_cron_info();

        $cronInfo = $this->getJsonData(ob_get_contents());

        $this->assertInternalType('array', $cronInfo);
        $this->assertArrayHasKey('history', $cronInfo);
        $this->assertArrayHasKey('icml', $cronInfo);
        $this->assertArrayHasKey('inventories', $cronInfo);
        $this->assertInternalType('string', $cronInfo['history']);
        $this->assertInternalType('string', $cronInfo['icml']);
        $this->assertInternalType('string', $cronInfo['inventories']);
        $this->assertNotEquals('This option is disabled', $cronInfo['history']);
        $this->assertNotEquals('This option is disabled', $cronInfo['icml']);
        $this->assertNotEquals('This option is disabled', $cronInfo['inventories']);
        ob_end_clean();
    }

    public function test_get_cron_info_off()
    {
        $this->baseRetailcrm->settings = ['sync' => 'no', 'icml' => 'no', 'history' => 'no'];

        ob_start();
        $this->baseRetailcrm->get_cron_info();

        $cronInfo = $this->getJsonData(ob_get_contents());

        $this->assertInternalType('array', $cronInfo);
        $this->assertArrayHasKey('history', $cronInfo);
        $this->assertArrayHasKey('icml', $cronInfo);
        $this->assertArrayHasKey('inventories', $cronInfo);
        $this->assertInternalType('string', $cronInfo['history']);
        $this->assertInternalType('string', $cronInfo['icml']);
        $this->assertInternalType('string', $cronInfo['inventories']);
        $this->assertEquals('This option is disabled', $cronInfo['history']);
        $this->assertEquals('This option is disabled', $cronInfo['icml']);
        $this->assertEquals('This option is disabled', $cronInfo['inventories']);
        ob_end_clean();
    }

    public function test_count_upload_data()
    {
        ob_start();
        $this->baseRetailcrm->count_upload_data();

        $uploadInfo = $this->getJsonData(ob_get_contents());

        $this->assertInternalType('array', $uploadInfo);
        $this->assertArrayHasKey('count_orders', $uploadInfo);
        $this->assertArrayHasKey('count_users', $uploadInfo);
        $this->assertInternalType('integer', $uploadInfo['count_orders']);
        $this->assertInternalType('integer', $uploadInfo['count_users']);
        ob_end_clean();
    }

    public function test_initialize_whatsapp()
    {
        ob_start();
        $this->baseRetailcrm->initialize_whatsapp();

        $js = ob_get_contents();

        $this->assertNotEquals('', $js);
        $this->assertContains('79184567234', $js);
        ob_end_clean();
    }

    public function test_initialize_whatsapp_off()
    {
        $this->baseRetailcrm->settings = [
            'whatsapp_active' => 'no',
            'whatsapp_location_icon' => 'no',
            'whatsapp_number' => '',
        ];

        ob_start();
        $this->baseRetailcrm->initialize_whatsapp();

        $this->assertEquals('', ob_get_contents());
        ob_end_clean();
    }

    public function test_initialize_daemon_collector_off()
    {
        $this->baseRetailcrm->settings = ['daemon_collector' => 'no', 'daemon_collector_key' => ''];

        ob_start();
        $this->baseRetailcrm->initialize_daemon_collector();

        $this->assertEquals('', ob_get_contents());
        ob_end_clean();
    }

    public function test_initialize_analytics()
    {
        ob_start();
        $this->baseRetailcrm->initialize_analytics();

        $js = ob_get_contents();

        $this->assertNotEquals('', $js);
        $this->assertContains('UA-XXXXXXX-XX', $js);
        ob_end_clean();
    }

    public function test_initialize_analytics_off()
    {
        $this->baseRetailcrm->settings = ['ua' => '', 'ua_code' => '', 'ua_custom' => ''];

        ob_start();
        $this->baseRetailcrm->initialize_analytics();

        $this->assertEquals('', ob_get_contents());
        ob_end_clean();
    }

    public function test_set_meta_fields()
    {
        ob_start();

        $this->baseRetailcrm->set_meta_fields();

        $jsonData = $this->getJsonData(ob_get_contents());

        $this->assertArrayHasKey('order', $jsonData);
        $this->assertArrayHasKey('customer', $jsonData);
        $this->assertArrayHasKey('translate', $jsonData);

        $this->assertArrayHasKey('custom', $jsonData['order']);
        $this->assertArrayHasKey('meta', $jsonData['order']);
        $this->assertArrayHasKey('custom', $jsonData['customer']);
        $this->assertArrayHasKey('meta', $jsonData['customer']);
        $this->assertArrayHasKey('tr_lb_order', $jsonData['translate']);
        $this->assertArrayHasKey('tr_lb_customer', $jsonData['translate']);
        $this->assertArrayHasKey('tr_btn', $jsonData['translate']);

        $this->assertArrayHasKey('default_retailcrm', $jsonData['order']['meta']);
        $this->assertArrayHasKey('default_retailcrm', $jsonData['order']['custom']);
        $this->assertArrayHasKey('test_upload', $jsonData['order']['custom']);
        $this->assertArrayHasKey('test', $jsonData['order']['custom']);

        $this->assertArrayHasKey('default_retailcrm', $jsonData['customer']['meta']);
        $this->assertArrayHasKey('default_retailcrm', $jsonData['customer']['custom']);
        $this->assertArrayHasKey('test_upload', $jsonData['customer']['custom']);
        $this->assertArrayHasKey('test', $jsonData['customer']['custom']);

        $this->assertEquals('Select value', $jsonData['order']['meta']['default_retailcrm']);
        $this->assertEquals('Select value', $jsonData['order']['custom']['default_retailcrm']);
        $this->assertEquals('Test_Upload', $jsonData['order']['custom']['test_upload']);
        $this->assertEquals('test123', $jsonData['order']['custom']['test']);

        $this->assertEquals('Select value', $jsonData['customer']['meta']['default_retailcrm']);
        $this->assertEquals('Select value', $jsonData['customer']['custom']['default_retailcrm']);
        $this->assertEquals('Test_Upload', $jsonData['customer']['custom']['test_upload']);
        $this->assertEquals('test123', $jsonData['customer']['custom']['test']);

        ob_end_clean();
    }

    public function test_validate_crm_url()
    {
        $this->assertEquals(
            'https://test.simla.com',
            $this->baseRetailcrm->validate_api_url_field('', 'https://test.simla.com')
        );
        $this->assertEquals(
            'https://test.retailcrm.ru',
            $this->baseRetailcrm->validate_api_url_field('', 'https://test.retailcrm.ru')
        );
        $this->assertEquals(
            'https://test.retailcrm.pro',
            $this->baseRetailcrm->validate_api_url_field('', 'https://test.retailcrm.pro')
        );
        $this->assertEquals(
            '',
            $this->baseRetailcrm->validate_api_url_field('', 'https://test.test.pro')
        );

        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', ''));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'https://tedsast.simla.comssd'));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'http://test.retailcrm.pro'));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'https://test.simla.com?query=test'));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'https://pass:user@test.simla.com'));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'https://test.simla.com#fragment'));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'https://test.simla.com:12345'));
        $this->assertEquals('', $this->baseRetailcrm->validate_api_url_field('', 'https://test.simla.com/test'));
    }

    private function getJsonData($text)
    {
        preg_match('/{.*}/', $text, $matches);

        return json_decode($matches[0], true);
    }

    private function setMockOrderMethods()
    {
        $this->responseMockOrderMethods = $this
            ->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMockOrderMethods, 'isSuccessful', true);

        $this->responseMockOrderMethods->setResponse(DataBaseRetailCrm::getResponseOrderMethods());
        $this->setMockResponse($this->apiMock, 'orderMethodsList', $this->responseMockOrderMethods);
    }

    private function setMockDeliveryTypes()
    {
        $this->responseMockDeliveryTypes = $this
            ->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMockDeliveryTypes, 'isSuccessful', true);

        $this->responseMockDeliveryTypes->setResponse(DataBaseRetailCrm::getResponseDeliveryTypes());
        $this->setMockResponse($this->apiMock, 'deliveryTypesList', $this->responseMockDeliveryTypes);
    }

    private function setMockPaymentTypes()
    {
        $this->responseMockPaymentTypes = $this
            ->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMockPaymentTypes, 'isSuccessful', true);

        $this->responseMockPaymentTypes->setResponse(DataBaseRetailCrm::getResponsePaymentTypes());
        $this->setMockResponse($this->apiMock, 'paymentTypesList', $this->responseMockPaymentTypes);
    }

    private function setMockStatuses()
    {
        $this->responseMockStatuses = $this
            ->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMockStatuses, 'isSuccessful', true);

        $this->responseMockStatuses->setResponse(DataBaseRetailCrm::getResponseStatuses());
        $this->setMockResponse($this->apiMock, 'statusesList', $this->responseMockStatuses);
    }

    private function setMockCustomFields()
    {
        $this->responseMockCustomFields = $this
            ->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->setMockResponse($this->responseMockCustomFields, 'isSuccessful', true);

        $this->responseMockCustomFields->setResponse(DataBaseRetailCrm::getResponseCustomFields());

        $this->setMockResponse($this->apiMock, 'customFieldsList', $this->responseMockCustomFields);
    }
}
