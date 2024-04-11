<?php

use datasets\DataLoyaltyRetailCrm;

if (!class_exists('WC_Retailcrm_Loyalty')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('class-wc-retailcrm-loyalty.php'));
}

if (!class_exists('WC_Retailcrm_Response')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-response.php'));
}


/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Loyalty_Test
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Loyalty_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $responseMock;
    protected $apiMock;

    /** @var \WC_Retailcrm_Loyalty */
    protected $loyalty;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock()
        ;

        $this->responseMock->setResponse(['success' => true]);
        $this->setMockResponse($this->responseMock, 'isSuccessful', true);

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods(['customersGet', 'getLoyaltyAccountList', 'createLoyaltyAccount', 'activateLoyaltyAccount'])
            ->getMock()
        ;

        $this->setMockResponse($this->apiMock, 'customersGet', ['customer' => ['id' => 1]]);
        $this->setMockResponse($this->apiMock, 'createLoyaltyAccount', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'activateLoyaltyAccount', $this->responseMock);

        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);
    }

    /**
     * @dataProvider responseLoyalty
     */
    public function testGetForm($isSuccessful, $body, $expected)
    {
        $response = new WC_Retailcrm_Response($isSuccessful ? 200 : 400, $body);

        $this->setMockResponse($this->apiMock, 'getLoyaltyAccountList', $response);
        $this->loyalty = new WC_Retailcrm_Loyalty($this->apiMock, []);

        $result = $this->loyalty->getForm(1);

        if (isset($result['form'])) {
            $this->assertTrue((bool) stripos($result['form'], $expected));
        }
    }

    public function responseLoyalty()
    {
        return [
            [
                'isSuccessful' => true,
                'body' => json_encode(['loyaltyAccounts' => []]),
                'expected' => 'id="loyaltyRegisterForm"'
            ],
            [
                'isSuccessful' => true,
                'body' => json_encode(
                    [
                        'loyaltyAccounts' => [
                            0 => [
                                'active' => false,
                                'customer' => [
                                    'externalId' => 1
                                ],
                                'id' => 1
                            ]
                        ]
                    ]
                ),
                'expected' => 'id="loyaltyActivateForm"'
            ],
            [
                'isSuccessful' => true,
                'body' => json_encode(['loyaltyAccounts' => [0 => DataLoyaltyRetailCrm::getDataLoyalty()]]),
                'expected' => 'accrual of 1 bonus for each'
            ]
        ];
    }
}
