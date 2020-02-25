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
     * @param $apiVersion
     *
     * @dataProvider dataProviderIntegrationModule
     */
    public function test_integration_module($retailcrm,$response, $apiVersion)
    {
        $client_id = uniqid();
        $result = WC_Retailcrm_Plugin::integration_module($retailcrm, $client_id, $apiVersion);

        if (!$retailcrm || $response['success'] == false) {
            $this->assertEquals(false, $result);
        } else {
            $this->assertEquals(true, $result);
        }    
    }

    private function getResponseData()
    {
        return array(
            'v4' => array(
                "true" => array(
                    "success" => true
                ),
                "false" => array(
                    "success" => false
                )
            ),
            'v5' => array(
                "true" => array(
                    "success" => true
                ),
                "false" => array(
                    "success" => false,
                    "errorMsg" => "Forbidden"
                )
            )
        );
    }

    public function dataProviderIntegrationModule()
    {
        $this->setUp();

        return array(
            array(
                'retailcrm' => $this->getApiMock(
                    'v4',
                    $this->getResponseData['v4']['true']
                ),
                'response' => $this->getResponseData['v4']['true'],
                'apiVersion' => 'v4'
            ),
            array(
                'retailcrm' => false,
                'response' => $this->getResponseData['v4']['true'],
                'apiVersion' => 'v4'
            ),
            array(
                'retailcrm' => $this->getApiMock(
                    'v4',
                    $this->getResponseData['v4']['false']
                ),
                'response' => $this->getResponseData['v4']['false'],
                'apiVersion' => 'v4'
            ),
            array(
                'retailcrm' => false,
                'response' => $this->getResponseData['v4']['false'],
                'apiVersion' => 'v4'
            ),
            array(
                'retailcrm' => $this->getApiMock(
                    'v5',
                    $this->getResponseData['v5']['true']
                ),
                'response' => $this->getResponseData['v5']['true'],
                'apiVersion' => 'v5'
            ),
            array(
                'retailcrm' => false,
                'response' => $this->getResponseData['v5']['true'],
                'apiVersion' => 'v5'
            ),
            array(
                'retailcrm' => $this->getApiMock(
                    'v5',
                    $this->getResponseData['v5']['false']
                ),
                'response' => $this->getResponseData['v5']['false'],
                'apiVersion' => 'v5'
            ),
            array(
                'retailcrm' => false,
                'response' => $this->getResponseData['v5']['false'],
                'apiVersion' => 'v5'
            )
        );
    }

    private function getApiMock($apiVersion, $response)
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

        if ($apiVersion == 'v4') {
            $apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'marketplaceSettingsEdit'
            ))
            ->getMock();

            $apiMock->expects($this->any())
                ->method('marketplaceSettingsEdit')
                ->willReturn($responseMock);
        } else {
            $apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'integrationModulesEdit'
            ))
            ->getMock();

            $apiMock->expects($this->any())
                ->method('integrationModulesEdit')
                ->willReturn($responseMock);
        }

        return $apiMock;
    }
}

