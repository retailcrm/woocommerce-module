<?php

if (!class_exists('WC_Retailcrm_Client_V5')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-client-v5.php'));
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Loyalty_Client_Test
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

class WC_Retailcrm_Loyalty_Client_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $responseMock;
    protected $apiMock;

    /** @var \WC_Retailcrm_Client_V5 */
    protected $clientMock;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock()
        ;

        $this->responseMock->setResponse(['success' => true]);
        $this->setMockResponse($this->responseMock, 'isSuccessful', true);

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Request')
            ->disableOriginalConstructor()
            ->setMethods(['makeRequest'])
            ->getMock()
        ;

        $this->setMockResponse($this->apiMock, 'makeRequest', $this->responseMock);

        $this->clientMock = new \WC_Retailcrm_Client_V5('https://test@retailcrm.ru', 'test', 'test');

        $reflection = new ReflectionClass($this->clientMock);
        $reflection_property = $reflection->getProperty('client');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($this->clientMock, $this->apiMock);
    }

    /**
     * @dataProvider requestLoyaltyData
     */
    public function testLoyaltyRequest($method, $parameters)
    {
        /** @var WC_Retailcrm_Response $test */
        $test = call_user_func([$this->clientMock, $method], ...$parameters);

        $this->assertTrue($test->isSuccessful());
    }

    public function requestLoyaltyData()
    {
        return [
            [
                'method' => 'createLoyaltyAccount',
                'parameters' => [['test'], 'testSite']
            ],
            [
                'method' => 'getLoyaltyClientInfo',
                'parameters' => [1]
            ],
            [
                'method' => 'activateLoyaltyAccount',
                'parameters' => [1]
            ],
            [
                'method' => 'editLoyaltyAccount',
                'parameters' => [1, ['test']]
            ],
            [
                'method' => 'getLoyaltyAccountList',
                'parameters' => [['filter'], 20, 1]
            ],
            [
                'method' => 'getListLoyalty',
                'parameters' => [['filter'], 20, 1]
            ],
            [
                'method' => 'getLoyalty',
                'parameters' => [1]
            ],
            [
                'method' => 'chargeBonusLoyalty',
                'parameters' => [1, 100, 'test']
            ],
            [
                'method' => 'creditBonusLoyalty',
                'parameters' => [1, ['amount' => 100]]
            ],
            [
                'method' => 'getClientBonusHistory',
                'parameters' => [1, ['filter'], 20, 1]
            ],
            [
                'method' => 'getDetailClientBonus',
                'parameters' => [1, 'status', ['filter'], 20, 1]
            ],
            [
                'method' => 'getBonusHistory',
                'parameters' => ['cursor', ['filter'], 20]
            ],
            [
                'method' => 'calculateDiscountLoyalty',
                'parameters' => ['site', ['order']]
            ],
            [
                'method' => 'applyBonusToOrder',
                'parameters' => ['site', ['order'], 100]
            ],
            [
                'method' => 'cancelBonusOrder',
                'parameters' => ['site', ['order']]
            ],
        ];
    }
}
