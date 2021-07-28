<?php

class WC_Retailcrm_Plugin_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;
    protected $plugin;
    private $path = __DIR__ . '/src/retailcrm.php';

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                                   ->disableOriginalConstructor()
                                   ->setMethods(array('isSuccessful'))
                                   ->getMock();

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
                              ->disableOriginalConstructor()
                              ->setMethods(array('integrationModulesEdit'))
                              ->getMock();

        $this->plugin = WC_Retailcrm_Plugin::getInstance($this->path);

        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @param $responseStatus
     *
     * @dataProvider dataProviderIntegrationModule
     */
    public function test_integration_module($retailcrm, $responseStatus)
    {
        $this->setMockResponse($this->responseMock, 'isSuccessful', $responseStatus);

        $this->responseMock->setResponse(array('success' => $responseStatus));

        if ($retailcrm) {
            $this->setMockResponse($retailcrm, 'integrationModulesEdit', $this->responseMock);
        }

        $client_id = uniqid();
        $result = WC_Retailcrm_Plugin::integration_module($retailcrm, $client_id);

        if (!$retailcrm || !$responseStatus) {
            $this->assertEquals(false, $result);
        } else {
            $this->assertEquals(true, $result);
        }
    }

    public function test_filter_cron_schedules()
    {
        $schedules = $this->plugin->filter_cron_schedules(array());

        $this->assertNotEmpty($schedules['five_minutes']);
        $this->assertEquals(300, $schedules['five_minutes']['interval']);
        $this->assertNotEmpty($schedules['three_hours']);
        $this->assertEquals(10800, $schedules['three_hours']['interval']);
        $this->assertNotEmpty($schedules['fiveteen_minutes']);
        $this->assertEquals(900, $schedules['fiveteen_minutes']['interval']);
    }

    public function test_deactivate()
    {
        wp_schedule_event(time(), 'three_hours', 'retailcrm_icml');
        wp_schedule_event(time(), 'five_minutes', 'retailcrm_history');
        wp_schedule_event(time(), 'fiveteen_minutes', 'retailcrm_inventories');

        $this->plugin->deactivate();

        $this->assertEquals(false, wp_next_scheduled('retailcrm_icml'));
        $this->assertEquals(false, wp_next_scheduled('retailcrm_history'));
        $this->assertEquals(false, wp_next_scheduled('retailcrm_inventories'));
    }

    public function test_register_deactivation_and_activation_hook()
    {
        global $wp_filter;

        $this->plugin->register_activation_hook();
        $this->plugin->register_deactivation_hook();

        $actions = array();

        foreach (array_keys($wp_filter) as $key) {
            if (false !== strpos($key, 'retailcrm')) {
                if (false !== strpos($key, 'deactivate_')) {
                    $actions['deactivate'] = $key;
                }

                if (false !== strpos($key, 'activate_')) {
                    $actions['activate'] = $key;
                }
            }
        }

        $this->assertArrayHasKey('deactivate', $actions);
        $this->assertNotEmpty($actions['deactivate']);
        $this->assertArrayHasKey('activate', $actions);
        $this->assertNotEmpty($actions['activate']);
    }

    public function dataProviderIntegrationModule()
    {
        $this->setUp();

        return array(
            array(
                'retailcrm' => $this->apiMock,
                'responseStatus' => true
            ),
            array(
                'retailcrm' => false,
                'responseStatus' => true
            ),
            array(
                'retailcrm' => $this->apiMock,
                'responseStatus' => false
            ),
            array(
                'retailcrm' => false,
                'responseStatus' => false
            ),
        );
    }
}

