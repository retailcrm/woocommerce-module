<?php
class WC_Retailcrm_Plugin_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;
    
    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'marketplaceSettingsEdit'
            ))
            ->getMock();

        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @param $response
     *
     * @dataProvider dataProviderIntegrationModule
     */
    public function test_integration_module($retailcrm, $response)
    {
        $client_id = uniqid();
        $result = WC_Retailcrm_Plugin::integration_module($retailcrm, $client_id);

        if (!$retailcrm || $response['success'] == false) {
            $this->assertEquals(false, $result);
        } else {
            $this->assertEquals(true, $result);
        }    
    }

    public function test_filter_cron_schedules()
    {
        $plugin = WC_Retailcrm_Plugin::getInstance(dirname(__DIR__ . '/../src/retailcrm.php'));
        $schedules = $plugin->filter_cron_schedules(array());

        $this->assertNotEmpty($schedules['five_minutes']);
        $this->assertEquals(300, $schedules['five_minutes']['interval']);
        $this->assertNotEmpty($schedules['three_hours']);
        $this->assertEquals(10800, $schedules['three_hours']['interval']);
        $this->assertNotEmpty($schedules['fiveteen_minutes']);
        $this->assertEquals(900, $schedules['fiveteen_minutes']['interval']);
    }

    private function getResponseData()
    {
        return array(
            "true" => array(
                "success" => true
            ),
            "false" => array(
                "success" => false,
                "errorMsg" => "Forbidden"
            )
        );
    }

    public function dataProviderIntegrationModule()
    {
        $this->setUp();
        $responseData = $this->getResponseData();

        return array(
            array(
                'retailcrm' => $this->getApiMock($responseData['true']),
                'response' => $responseData['true']
            ),
            array(
                'retailcrm' => false,
                'response' => $responseData['true']
            ),
            array(
                'retailcrm' => $this->getApiMock($responseData['false']),
                'response' => $responseData['false']
            ),
            array(
                'retailcrm' => false,
                'response' => $responseData['false']
            )
        );
    }

    private function getApiMock($response)
    {
        $responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        if ($response['success'] == true) {
            $responseMock->expects($this->any())
                ->method('isSuccessful')
                ->willReturn(true);
        } elseif ($response['success'] == false) {
            $responseMock->expects($this->any())
                ->method('isSuccessful')
                ->willReturn(false);
        }

        $responseMock->setResponse($response);

        $apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'integrationModulesEdit'
            ))
            ->getMock();

        $apiMock->expects($this->any())
            ->method('integrationModulesEdit')
            ->willReturn($responseMock);

        return $apiMock;
    }
}

